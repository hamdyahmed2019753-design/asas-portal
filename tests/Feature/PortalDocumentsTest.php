<?php

namespace Tests\Feature;

use App\Enums\DocumentCategory;
use App\Models\Document;
use App\Models\User;
use App\Services\Portal\DocumentsCenterService;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class PortalDocumentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
    }

    private function member(): User
    {
        $user = User::create([
            'name' => 'مستثمر', 'email' => uniqid('u_').'@test.local', 'password' => 'secret123', 'email_verified_at' => now(),
        ]);
        $user->assignRole('member');

        return $user;
    }

    private function admin(): User
    {
        $user = User::create([
            'name' => 'مدير', 'email' => uniqid('a_').'@test.local', 'password' => 'secret123', 'email_verified_at' => now(),
        ]);
        $user->assignRole('admin');

        return $user;
    }

    private function doc(User $user, DocumentCategory $category = DocumentCategory::Contract, string $title = 'مستند', ?string $path = null): Document
    {
        return $user->documents()->create([
            'category' => $category->value,
            'title' => $title,
            'disk' => 'local',
            'path' => $path ?? ('documents/'.uniqid().'.pdf'),
            'size' => 12345,
            'original_name' => 'file.pdf',
        ]);
    }

    private function data(User $user, array $query = []): array
    {
        return app(DocumentsCenterService::class)->data($user, Request::create('/', 'GET', $query));
    }

    public function test_page_renders_for_member(): void
    {
        $this->actingAs($this->member())->get('/portal/documents')->assertOk()->assertSee('مستنداتي');
    }

    public function test_empty_state(): void
    {
        $this->actingAs($this->member())
            ->get('/portal/documents')
            ->assertOk()
            ->assertSee('لا توجد مستندات بعد')
            ->assertSee('استعرض العقود الاستثمارية');
    }

    public function test_summary_and_category_facets(): void
    {
        $user = $this->member();
        $this->doc($user, DocumentCategory::Kyc, 'الهوية');
        $this->doc($user, DocumentCategory::Kyc, 'الآيبان');
        $this->doc($user, DocumentCategory::Contract, 'عقد');

        $data = $this->data($user);

        $this->assertSame(3, $data['summary']['total']);
        $facets = collect($data['categories'])->keyBy('value');
        $this->assertSame(2, $facets['kyc']['count']);
        $this->assertSame(1, $facets['contract']['count']);
    }

    public function test_filter_by_category(): void
    {
        $user = $this->member();
        $this->doc($user, DocumentCategory::Kyc, 'الهوية');
        $this->doc($user, DocumentCategory::Contract, 'عقد');

        $this->assertCount(1, $this->data($user, ['category' => 'kyc'])['documents']->getCollection());
    }

    public function test_search(): void
    {
        $user = $this->member();
        $this->doc($user, DocumentCategory::Contract, 'عقد صندوق النمو');
        $this->doc($user, DocumentCategory::Contract, 'عقد التجارة');

        $this->assertCount(1, $this->data($user, ['q' => 'النمو'])['documents']->getCollection());
    }

    public function test_pagination(): void
    {
        $user = $this->member();
        for ($i = 0; $i < 15; $i++) {
            $this->doc($user, DocumentCategory::Contract, "مستند {$i}");
        }

        $documents = $this->data($user)['documents'];
        $this->assertSame(12, $documents->perPage());
        $this->assertCount(12, $documents);
        $this->assertSame(15, $documents->total());
    }

    // ----- Downloads / security -----

    public function test_owner_can_download_via_signed_url(): void
    {
        Storage::fake('local');
        $user = $this->member();
        Storage::disk('local')->put('documents/c.pdf', 'pdf');
        $doc = $this->doc($user, DocumentCategory::Contract, 'عقد', 'documents/c.pdf');

        $url = URL::temporarySignedRoute('documents.download', now()->addMinutes(10), ['document' => $doc->id]);

        $this->actingAs($user)->get($url)->assertOk();
    }

    public function test_unsigned_download_is_rejected(): void
    {
        $doc = $this->doc($this->member());

        $this->actingAs($doc->user)
            ->get(route('documents.download', $doc))
            ->assertForbidden();
    }

    public function test_other_user_cannot_download(): void
    {
        Storage::fake('local');
        $owner = $this->member();
        Storage::disk('local')->put('documents/c.pdf', 'pdf');
        $doc = $this->doc($owner, DocumentCategory::Contract, 'عقد', 'documents/c.pdf');

        $intruder = $this->member();
        $url = URL::temporarySignedRoute('documents.download', now()->addMinutes(10), ['document' => $doc->id]);

        // Valid signature, wrong user → policy denies.
        $this->actingAs($intruder)->get($url)->assertForbidden();
    }

    public function test_admin_can_download_any_document(): void
    {
        Storage::fake('local');
        $owner = $this->member();
        Storage::disk('local')->put('documents/c.pdf', 'pdf');
        $doc = $this->doc($owner, DocumentCategory::Contract, 'عقد', 'documents/c.pdf');

        $url = URL::temporarySignedRoute('documents.download', now()->addMinutes(10), ['document' => $doc->id]);

        $this->actingAs($this->admin())->get($url)->assertOk();
    }

    public function test_documents_are_scoped_to_owner(): void
    {
        $userA = $this->member();
        $a = $this->doc($userA, DocumentCategory::Contract, 'خاص A');
        $userB = $this->member();
        $this->doc($userB, DocumentCategory::Contract, 'خاص B');

        $listB = $this->data($userB)['documents']->getCollection();
        $this->assertFalse($listB->contains('id', $a->id));
        $this->assertCount(1, $listB);
    }

    // ----- Performance -----

    public function test_page_does_not_trigger_n_plus_one(): void
    {
        $user = $this->member();
        for ($i = 0; $i < 15; $i++) {
            $this->doc($user, DocumentCategory::Contract, "مستند {$i}");
        }

        DB::enableQueryLog();
        $this->actingAs($user)->get('/portal/documents')->assertOk();
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThan(15, $count);
    }
}
