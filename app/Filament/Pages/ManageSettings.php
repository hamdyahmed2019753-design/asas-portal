<?php

namespace App\Filament\Pages;

use App\Support\Settings;
use Filament\Actions\Action;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Throwable;

/**
 * Admin system-settings page (Filament). Organised into tabs and built to be
 * extended — every setting is a key/value persisted to the database via the
 * generic Settings service (never .env). Adding a new setting is just a new key.
 */
class ManageSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'الإعدادات';

    protected static ?string $title = 'الإعدادات';

    protected static ?string $navigationGroup = 'النظام';

    protected static ?int $navigationSort = 99;

    protected static string $view = 'filament.pages.manage-settings';

    /**
     * @var array<string, mixed>
     */
    public ?array $data = [];

    /**
     * Mapping of form-field name → setting key. The single source of truth for
     * loading (mount) and saving; new settings are added here only.
     *
     * @var array<string, string>
     */
    private const MAP = [
        // General
        'general_site_name' => 'general.site_name',
        'general_site_description' => 'general.site_description',
        'general_site_logo' => 'general.site_logo',
        'general_favicon' => 'general.favicon',
        'general_timezone' => 'general.timezone',
        'general_default_language' => 'general.default_language',
        'general_company_name' => 'general.company_name',
        'general_company_description' => 'general.company_description',
        'general_commercial_registration' => 'general.commercial_registration',
        'general_tax_number' => 'general.tax_number',
        'general_website_url' => 'general.website_url',
        // Contact
        'general_support_email' => 'general.support_email',
        'general_support_phone' => 'general.support_phone',
        'contact_whatsapp' => 'contact.whatsapp',
        'general_company_address' => 'general.company_address',
        'contact_google_maps_url' => 'contact.google_maps_url',
        // Bank accounts (transfer subscription)
        'bank_1_name' => 'bank.1.name',
        'bank_1_account_name' => 'bank.1.account_name',
        'bank_1_iban' => 'bank.1.iban',
        'bank_2_name' => 'bank.2.name',
        'bank_2_account_name' => 'bank.2.account_name',
        'bank_2_iban' => 'bank.2.iban',
        // Social
        'social_facebook' => 'social.facebook',
        'social_twitter' => 'social.twitter',
        'social_instagram' => 'social.instagram',
        'social_linkedin' => 'social.linkedin',
        'social_youtube' => 'social.youtube',
        'social_snapchat' => 'social.snapchat',
        'social_tiktok' => 'social.tiktok',
        // Mail
        'mail_mailer' => 'mail.mailer',
        'mail_host' => 'mail.host',
        'mail_port' => 'mail.port',
        'mail_username' => 'mail.username',
        'mail_password' => 'mail.password',
        'mail_encryption' => 'mail.encryption',
        'mail_from_name' => 'mail.from_name',
        'mail_from_address' => 'mail.from_address',
        'mail_reply_to_address' => 'mail.reply_to_address',
        'mail_reply_to_name' => 'mail.reply_to_name',
        // SEO
        'seo_meta_title' => 'seo.meta_title',
        'seo_meta_description' => 'seo.meta_description',
        'seo_meta_keywords' => 'seo.meta_keywords',
        'seo_og_image' => 'seo.og_image',
        // Security
        'security_session_timeout' => 'security.session_timeout',
        'security_password_min_length' => 'security.password_min_length',
        'security_enable_registration' => 'security.enable_registration',
        'security_require_email_verification' => 'security.require_email_verification',
        // Maintenance
        'maintenance_enabled' => 'maintenance.enabled',
        'maintenance_message' => 'maintenance.message',
        // Payments — Moyasar
        'pay_moyasar_enabled' => 'payments.moyasar.enabled',
        'pay_moyasar_api_key' => 'payments.moyasar.api_key',
        'pay_moyasar_secret_key' => 'payments.moyasar.secret_key',
        // Payments — HyperPay
        'pay_hyperpay_enabled' => 'payments.hyperpay.enabled',
        'pay_hyperpay_entity_id' => 'payments.hyperpay.entity_id',
        'pay_hyperpay_access_token' => 'payments.hyperpay.access_token',
        // Payments — Tap
        'pay_tap_enabled' => 'payments.tap.enabled',
        'pay_tap_secret_key' => 'payments.tap.secret_key',
        // Payments — Stripe
        'pay_stripe_enabled' => 'payments.stripe.enabled',
        'pay_stripe_publishable_key' => 'payments.stripe.publishable_key',
        'pay_stripe_secret_key' => 'payments.stripe.secret_key',
    ];

    /**
     * Boolean (toggle) fields — cast on load and save.
     *
     * @var array<int, string>
     */
    private const BOOLEANS = [
        'security_enable_registration',
        'security_require_email_verification',
        'maintenance_enabled',
        'pay_moyasar_enabled',
        'pay_hyperpay_enabled',
        'pay_tap_enabled',
        'pay_stripe_enabled',
    ];

    /**
     * File-upload fields and the disk they live on. Used in save() to delete
     * the previously-stored file when it is replaced or removed.
     *
     * @var array<string, string>
     */
    private const FILES = [
        'general_site_logo' => 'public',
        'general_favicon' => 'public',
        'seo_og_image' => 'public',
    ];

    /**
     * Defaults applied when a setting has never been saved.
     *
     * @var array<string, mixed>
     */
    private function defaults(): array
    {
        return [
            'general_site_name' => 'أساس',
            'general_timezone' => 'Asia/Riyadh',
            'general_default_language' => 'ar',
            'bank_1_name' => 'البنك الأهلي السعودي',
            'bank_2_name' => 'مصرف الإنماء',
            'mail_mailer' => 'smtp',
            'mail_port' => 587,
            'mail_encryption' => 'tls',
            'security_session_timeout' => config('session.lifetime'),
            'security_password_min_length' => 8,
            'security_enable_registration' => true,
            'security_require_email_verification' => true,
            'maintenance_enabled' => false,
            'pay_moyasar_enabled' => false,
            'pay_hyperpay_enabled' => false,
            'pay_tap_enabled' => false,
            'pay_stripe_enabled' => false,
        ];
    }

    public function mount(): void
    {
        $s = app(Settings::class);
        $defaults = $this->defaults();

        $state = [];
        foreach (self::MAP as $field => $key) {
            $value = $s->get($key, $defaults[$field] ?? null);
            $state[$field] = in_array($field, self::BOOLEANS, true) ? (bool) $value : $value;
        }

        $this->form->fill($state);
    }

    /**
     * Page header actions: WhatsApp shortcut (when configured), Restore Defaults
     * (non-sensitive only), and Clear Settings Cache.
     *
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('openWhatsApp')
                ->label('فتح واتساب')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->url(function (): ?string {
                    $wa = setting('contact.whatsapp');

                    return filled($wa) ? 'https://wa.me/'.preg_replace('/\D/', '', $wa) : null;
                })
                ->openUrlInNewTab()
                ->color('success')
                ->visible(fn (): bool => filled(setting('contact.whatsapp'))),

            Action::make('restoreDefaults')
                ->label('استعادة الافتراضي')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('استعادة الإعدادات الافتراضية')
                ->modalDescription('سيتم إرجاع الإعدادات غير الحساسة فقط (الاسم، اللغة، المنطقة، المنفذ، التشفير، الأمان…) إلى قيمها الافتراضية. لن يتم مسح: كلمة مرور SMTP، مفاتيح بوابات الدفع، الأسرار، والرموز.')
                ->modalSubmitActionLabel('استعادة')
                ->action(fn () => $this->restoreDefaults()),

            Action::make('clearCache')
                ->label('مسح ذاكرة الإعدادات')
                ->icon('heroicon-o-trash')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('مسح ذاكرة الإعدادات')
                ->modalDescription('سيُعاد تحميل جميع القيم من قاعدة البيانات.')
                ->modalSubmitActionLabel('مسح')
                ->action(function (): void {
                    app(Settings::class)->clearCache();
                    Notification::make()->title('تم مسح ذاكرة الإعدادات')->success()->send();
                }),
        ];
    }

    /**
     * Restore only the non-sensitive defaults. Sensitive keys (SMTP password,
     * payment API keys / secrets / tokens) are never present in defaults() and
     * are therefore left untouched. The cache is cleared and the form refilled.
     */
    public function restoreDefaults(): void
    {
        $settings = app(Settings::class);

        foreach ($this->defaults() as $field => $value) {
            $settings->set(
                self::MAP[$field],
                in_array($field, self::BOOLEANS, true) ? (bool) $value : $value,
            );
        }

        $settings->clearCache();

        Notification::make()->title('تمت استعادة الإعدادات الافتراضية')->success()->send();

        // Refresh the form so the restored values are visible immediately.
        $this->mount();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('ملخص النظام')
                    ->description('نظرة عامة على الحالة الحالية — للعرض فقط.')
                    ->schema([
                        Placeholder::make('summary_company')
                            ->label('اسم الشركة')
                            ->content(fn (): string => (string) (setting('general.company_name') ?: setting('general.site_name', 'أساس'))),
                        Placeholder::make('summary_environment')
                            ->label('البيئة')
                            ->content(fn (): string => app()->environment()),
                        Placeholder::make('summary_mail')
                            ->label('حالة البريد')
                            ->content(fn (): string => filled(setting('mail.host'))
                                ? 'مُعدّ ('.strtoupper((string) setting('mail.mailer', 'smtp')).')'
                                : 'افتراضي'),
                        Placeholder::make('summary_cache')
                            ->label('محرّك الكاش')
                            ->content(fn (): string => (string) config('cache.default')),
                        Placeholder::make('summary_storage')
                            ->label('محرّك التخزين')
                            ->content(fn (): string => (string) config('filesystems.default')),
                    ])
                    ->columns(5)
                    ->columnSpanFull(),

                Tabs::make('settings')
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make('عام')->icon('heroicon-o-globe-alt')->schema([
                            TextInput::make('general_site_name')->label('اسم الموقع')->maxLength(255),
                            TextInput::make('general_website_url')->label('رابط الموقع')->url()->maxLength(255),
                            Textarea::make('general_site_description')->label('وصف الموقع')->rows(2)->columnSpanFull(),
                            Select::make('general_timezone')->label('المنطقة الزمنية')
                                ->options(['Asia/Riyadh' => 'الرياض (Asia/Riyadh)', 'Asia/Dubai' => 'دبي (Asia/Dubai)', 'Africa/Cairo' => 'القاهرة (Africa/Cairo)', 'UTC' => 'UTC', 'Europe/London' => 'London']),
                            Select::make('general_default_language')->label('اللغة الافتراضية')
                                ->options(['ar' => 'العربية', 'en' => 'English']),
                            FileUpload::make('general_site_logo')->label('شعار الموقع')->image()->disk('public')->directory('branding')->maxSize(2048),
                            Placeholder::make('general_site_logo_preview')
                                ->label('المعاينة الحالية')
                                ->content(function (): HtmlString {
                                    $url = setting_file_url('general.site_logo');

                                    return new HtmlString($url
                                        ? '<img src="'.e($url).'" alt="logo" style="max-height:48px;width:auto;border-radius:8px;border:1px solid #e1e5e5;">'
                                        : '<span style="color:#9ba3a3;font-size:13px;">— لا توجد صورة</span>');
                                }),
                            FileUpload::make('general_favicon')->label('أيقونة الموقع (Favicon)')->image()->disk('public')->directory('branding')->maxSize(1024),
                            Placeholder::make('general_favicon_preview')
                                ->label('المعاينة الحالية')
                                ->content(function (): HtmlString {
                                    $url = setting_file_url('general.favicon');

                                    return new HtmlString($url
                                        ? '<img src="'.e($url).'" alt="favicon" style="max-height:32px;width:auto;border-radius:6px;border:1px solid #e1e5e5;">'
                                        : '<span style="color:#9ba3a3;font-size:13px;">— لا توجد أيقونة</span>');
                                }),
                            Section::make('بيانات الشركة')->schema([
                                TextInput::make('general_company_name')->label('اسم الشركة')->maxLength(255),
                                TextInput::make('general_commercial_registration')->label('رقم السجل التجاري')->maxLength(50),
                                TextInput::make('general_tax_number')->label('الرقم الضريبي')->maxLength(50),
                                Textarea::make('general_company_description')->label('وصف الشركة')->rows(2)->columnSpanFull(),
                            ])->columns(2)->columnSpanFull(),
                        ])->columns(2),

                        Tab::make('التواصل')->icon('heroicon-o-phone')->schema([
                            TextInput::make('general_support_email')->label('بريد الدعم')->email()->maxLength(255),
                            TextInput::make('general_support_phone')->label('هاتف الدعم')->maxLength(30),
                            TextInput::make('contact_whatsapp')->label('رقم واتساب')->maxLength(30),
                            TextInput::make('contact_google_maps_url')->label('رابط خرائط جوجل')->url()->maxLength(255),
                            Textarea::make('general_company_address')->label('عنوان الشركة')->rows(2)->columnSpanFull(),
                        ])->columns(2),

                        Tab::make('الحسابات البنكية')->icon('heroicon-o-building-library')->schema([
                            Section::make('الحساب الأول')->schema([
                                TextInput::make('bank_1_name')->label('اسم البنك')->maxLength(255),
                                TextInput::make('bank_1_account_name')->label('اسم الحساب')->maxLength(255),
                                TextInput::make('bank_1_iban')->label('الآيبان (IBAN)')->maxLength(34)->placeholder('SA00 0000 0000 0000 0000 0000'),
                            ])->columns(3),
                            Section::make('الحساب الثاني')->schema([
                                TextInput::make('bank_2_name')->label('اسم البنك')->maxLength(255),
                                TextInput::make('bank_2_account_name')->label('اسم الحساب')->maxLength(255),
                                TextInput::make('bank_2_iban')->label('الآيبان (IBAN)')->maxLength(34)->placeholder('SA00 0000 0000 0000 0000 0000'),
                            ])->columns(3),
                        ]),

                        Tab::make('التواصل الاجتماعي')->icon('heroicon-o-share')->schema([
                            TextInput::make('social_facebook')->label('Facebook')->url()->maxLength(255),
                            TextInput::make('social_twitter')->label('X (Twitter)')->url()->maxLength(255),
                            TextInput::make('social_instagram')->label('Instagram')->url()->maxLength(255),
                            TextInput::make('social_linkedin')->label('LinkedIn')->url()->maxLength(255),
                            TextInput::make('social_youtube')->label('YouTube')->url()->maxLength(255),
                            TextInput::make('social_snapchat')->label('Snapchat')->url()->maxLength(255),
                            TextInput::make('social_tiktok')->label('TikTok')->url()->maxLength(255),
                        ])->columns(2),

                        Tab::make('البريد')->icon('heroicon-o-envelope')->schema([
                            Select::make('mail_mailer')->label('مزوّد البريد')->options(['smtp' => 'SMTP', 'log' => 'Log', 'array' => 'Array'])->default('smtp'),
                            TextInput::make('mail_host')->label('خادم SMTP')->maxLength(255),
                            TextInput::make('mail_port')->label('المنفذ')->numeric()->default(587),
                            Select::make('mail_encryption')->label('التشفير')->options(['tls' => 'TLS', 'ssl' => 'SSL', '' => 'بدون']),
                            TextInput::make('mail_username')->label('اسم المستخدم')->maxLength(255),
                            TextInput::make('mail_password')->label('كلمة المرور')->password()->revealable()->maxLength(255),
                            TextInput::make('mail_from_name')->label('اسم المُرسِل')->maxLength(255),
                            TextInput::make('mail_from_address')->label('بريد المُرسِل')->email()->maxLength(255),
                            TextInput::make('mail_reply_to_name')->label('اسم الرد (Reply-To)')->maxLength(255),
                            TextInput::make('mail_reply_to_address')->label('بريد الرد (Reply-To)')->email()->maxLength(255),
                        ])->columns(2),

                        Tab::make('بريد اختباري')->icon('heroicon-o-paper-airplane')->schema([
                            Placeholder::make('test_mail_active')->label('الإعداد المستخدم حاليًا')
                                ->content(fn (): string => strtoupper((string) setting('mail.mailer', config('mail.default', 'log'))).' · '.(setting('mail.from_address', config('mail.from.address')) ?: '—')),
                            TextInput::make('test_email')->label('إرسال إلى')->email()->default(fn () => (string) (auth()->user()?->email ?? ''))->placeholder('admin@example.com'),
                            Actions::make([
                                FormAction::make('sendTestEmail')
                                    ->label('إرسال بريد اختباري')
                                    ->icon('heroicon-o-paper-airplane')
                                    ->action(fn (Get $get) => $this->sendTestEmail($get('test_email'))),
                            ]),
                        ]),

                        Tab::make('SEO')->icon('heroicon-o-magnifying-glass')->schema([
                            TextInput::make('seo_meta_title')->label('Meta Title')->maxLength(255),
                            TextInput::make('seo_meta_keywords')->label('Meta Keywords')->maxLength(255),
                            Textarea::make('seo_meta_description')->label('Meta Description')->rows(2)->columnSpanFull(),
                            FileUpload::make('seo_og_image')->label('Open Graph Image')->image()->disk('public')->directory('seo')->maxSize(2048),
                            Placeholder::make('seo_og_image_preview')
                                ->label('المعاينة الحالية')
                                ->content(function (): HtmlString {
                                    $url = setting_file_url('seo.og_image');

                                    return new HtmlString($url
                                        ? '<img src="'.e($url).'" alt="og" style="max-height:80px;width:auto;border-radius:8px;border:1px solid #e1e5e5;">'
                                        : '<span style="color:#9ba3a3;font-size:13px;">— لا توجد صورة</span>');
                                })->columnSpanFull(),
                        ])->columns(2),

                        Tab::make('الأمان')->icon('heroicon-o-shield-check')->schema([
                            TextInput::make('security_session_timeout')->label('مهلة الجلسة (دقائق)')->numeric()->minValue(1),
                            TextInput::make('security_password_min_length')->label('الحد الأدنى لطول كلمة المرور')->numeric()->minValue(6)->default(8),
                            Toggle::make('security_enable_registration')->label('السماح بالتسجيل')->default(true),
                            Toggle::make('security_require_email_verification')->label('إلزام توثيق البريد')->default(true),
                        ])->columns(2),

                        Tab::make('الصيانة')->icon('heroicon-o-wrench-screwdriver')->schema([
                            Toggle::make('maintenance_enabled')->label('تفعيل وضع الصيانة')->default(false),
                            Textarea::make('maintenance_message')->label('رسالة الصيانة')->rows(3)->columnSpanFull(),
                        ])->columns(2),

                        Tab::make('التخزين')->icon('heroicon-o-circle-stack')->schema([
                            Placeholder::make('storage_driver')->label('Filesystem Driver')
                                ->content(config('filesystems.disks.'.config('filesystems.default').'.driver', '—')),
                            Placeholder::make('storage_default_disk')->label('Default Disk')->content(config('filesystems.default')),
                            Placeholder::make('storage_upload_max')->label('Upload Max Size')->content(ini_get('upload_max_filesize') ?: '—'),
                            Placeholder::make('storage_path')->label('Storage Path')->content(storage_path()),
                        ])->columns(2),

                        Tab::make('المدفوعات')->icon('heroicon-o-credit-card')->schema([
                            Placeholder::make('payments_note')->label('')
                                ->content('بنية إعدادات بوابات الدفع — تُحفظ فقط ولم تُربط بأي بوابة بعد.')->columnSpanFull(),
                            Section::make('Moyasar')->schema([
                                Toggle::make('pay_moyasar_enabled')->label('مفعّلة')->default(false),
                                TextInput::make('pay_moyasar_api_key')->label('API Key')->password()->revealable()->maxLength(255),
                                TextInput::make('pay_moyasar_secret_key')->label('Secret Key')->password()->revealable()->maxLength(255),
                            ])->columns(2),
                            Section::make('HyperPay')->schema([
                                Toggle::make('pay_hyperpay_enabled')->label('مفعّلة')->default(false),
                                TextInput::make('pay_hyperpay_entity_id')->label('Entity ID')->maxLength(255),
                                TextInput::make('pay_hyperpay_access_token')->label('Access Token')->password()->revealable()->maxLength(255),
                            ])->columns(2),
                            Section::make('Tap')->schema([
                                Toggle::make('pay_tap_enabled')->label('مفعّلة')->default(false),
                                TextInput::make('pay_tap_secret_key')->label('Secret Key')->password()->revealable()->maxLength(255),
                            ])->columns(2),
                            Section::make('Stripe')->schema([
                                Toggle::make('pay_stripe_enabled')->label('مفعّلة')->default(false),
                                TextInput::make('pay_stripe_publishable_key')->label('Publishable Key')->maxLength(255),
                                TextInput::make('pay_stripe_secret_key')->label('Secret Key')->password()->revealable()->maxLength(255),
                            ])->columns(2),
                        ]),

                        Tab::make('النظام')->icon('heroicon-o-server')->schema([
                            Placeholder::make('laravel_version')->label('إصدار Laravel')->content(app()->version()),
                            Placeholder::make('php_version')->label('إصدار PHP')->content(PHP_VERSION),
                            Placeholder::make('environment')->label('البيئة')->content(app()->environment()),
                            Placeholder::make('queue_driver')->label('Queue Driver')->content(config('queue.default')),
                            Placeholder::make('cache_driver')->label('Cache Driver')->content(config('cache.default')),
                            Placeholder::make('session_driver')->label('Session Driver')->content(config('session.driver')),
                        ])->columns(2),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $settings = app(Settings::class);

        // Delete previously-stored media files when they are replaced or removed.
        foreach (self::FILES as $field => $disk) {
            $old = $settings->get(self::MAP[$field]);
            $new = $data[$field] ?? null;

            if (filled($old) && $old !== $new) {
                Storage::disk($disk)->delete($old);
            }
        }

        $values = [];
        foreach (self::MAP as $field => $key) {
            $value = $data[$field] ?? ($this->defaults()[$field] ?? null);
            $values[$key] = in_array($field, self::BOOLEANS, true) ? (bool) $value : $value;
        }

        $settings->setMany($values);

        Notification::make()->title('تم حفظ الإعدادات')->success()->send();
    }

    private function sendTestEmail(?string $email): void
    {
        if (blank($email)) {
            Notification::make()->title('أدخل بريدًا إلكترونيًا أولًا')->warning()->send();

            return;
        }

        try {
            Mail::raw('رسالة اختبار من بوابة مستثمري أساس. إعدادات البريد تعمل بنجاح.', function ($message) use ($email): void {
                $message->to($email)->subject('بريد اختباري — أساس');
            });
            Notification::make()->title('تم إرسال البريد الاختباري بنجاح')->success()->send();
        } catch (Throwable $e) {
            Notification::make()->title('فشل إرسال البريد')->body($e->getMessage())->danger()->send();
        }
    }
}
