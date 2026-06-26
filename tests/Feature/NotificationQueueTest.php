<?php

namespace Tests\Feature;

use App\Enums\KycState;
use App\Models\Contract;
use App\Models\User;
use App\Notifications\ContractInterestNotification;
use App\Notifications\InvestmentApprovedNotification;
use App\Notifications\InvestmentRejectedNotification;
use App\Notifications\KycApprovedNotification;
use App\Notifications\KycRejectedNotification;
use App\Notifications\KycSubmittedNotification;
use App\Notifications\NewsPublishedNotification;
use App\Notifications\PayoutDueNotification;
use App\Notifications\PayoutPaidNotification;
use App\Services\Portal\ContractInterestService;
use App\Services\Portal\KycService;
use Database\Seeders\RolesSeeder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class NotificationQueueTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
    }

    private function member(?KycState $state = null): User
    {
        $user = User::forceCreate([
            'name' => 'مستثمر', 'email' => uniqid('u_').'@test.local', 'password' => 'secret123', 'email_verified_at' => now(),
            'kyc_state' => $state?->value,
        ]);
        $user->assignRole('member');

        return $user;
    }

    /** Every notification in the system must be queueable. */
    public function test_all_notifications_implement_should_queue(): void
    {
        $classes = [
            KycSubmittedNotification::class,
            KycApprovedNotification::class,
            KycRejectedNotification::class,
            InvestmentApprovedNotification::class,
            InvestmentRejectedNotification::class,
            PayoutPaidNotification::class,
            PayoutDueNotification::class,
            NewsPublishedNotification::class,
            ContractInterestNotification::class,
        ];

        foreach ($classes as $class) {
            $this->assertContains(
                ShouldQueue::class,
                class_implements($class),
                "{$class} must implement ShouldQueue",
            );
        }
    }

    public function test_kyc_approval_notification_is_dispatched_to_queue(): void
    {
        Queue::fake();
        $user = $this->member(KycState::UnderReview);

        app(KycService::class)->approve($user);

        Queue::assertPushed(SendQueuedNotifications::class);
    }

    public function test_contract_interest_notification_is_dispatched_to_queue(): void
    {
        Queue::fake();
        $user = $this->member(KycState::Approved);
        $contract = Contract::create([
            'title' => 'عقد', 'activity_type' => 'تجارة', 'target_amount' => 1_000_000,
            'min_amount' => 1_000, 'duration_months' => 12, 'payouts_count' => 4, 'status' => 'open',
        ]);

        app(ContractInterestService::class)->express($user, $contract);

        Queue::assertPushed(SendQueuedNotifications::class);
    }

    /** Regression: under the sync queue (tests), delivery still works end-to-end. */
    public function test_no_regression_notifications_still_delivered(): void
    {
        Notification::fake();
        $user = $this->member(KycState::UnderReview);

        app(KycService::class)->approve($user);

        Notification::assertSentTo($user, KycApprovedNotification::class);
    }
}
