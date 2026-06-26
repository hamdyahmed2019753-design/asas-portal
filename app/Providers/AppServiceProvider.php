<?php

namespace App\Providers;

use App\Models\NewsUpdate;
use App\Observers\NewsUpdateObserver;
use App\Policies\ActivityPolicy;
use App\Services\Portal\NotificationCenterService;
use App\Support\Settings;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\Mime\Address;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Align generated absolute URLs (Livewire's signed upload URL, signed
        // document-download links) with the origin the browser actually used
        // to reach the app. Without this, a mismatch between APP_URL and the
        // access origin makes Livewire's signed upload URL cross-origin, so
        // the browser blocks the upload POST and FileUpload hangs forever at
        // "Waiting for upload completion". No-op in CLI/queue (queued mail
        // etc. still use APP_URL) and in production (APP_URL == access origin).
        if (! app()->runningInConsole() && app()->bound('request')) {
            URL::forceRootUrl(request()->root());
            URL::forceScheme(request()->isSecure() ? 'https' : 'http');
        }

        // The Spatie Activity model lives outside App\Models, so its policy
        // must be registered explicitly (auto-discovery won't find it).
        Gate::policy(Activity::class, ActivityPolicy::class);

        // Rate limiter for authentication endpoints (login + password reset),
        // keyed by email + IP. Filament's own login also throttles (5/min).
        RateLimiter::for('auth', function (Request $request) {
            $key = (string) $request->input('email').'|'.$request->ip();

            return Limit::perMinute(6)->by($key);
        });

        // Notify investors/members when a news item is published.
        NewsUpdate::observe(NewsUpdateObserver::class);

        // Note: RecordUserLogin is auto-discovered for the Login event
        // (Laravel listener auto-discovery) — no manual registration needed.

        // Single source of truth for the portal layout's shared data. Every
        // view that uses layouts.portal receives the navigation items + the
        // (cached) unread-notification count for the nav bell — no controller
        // duplication, no inline template variables.
        View::composer('layouts.portal', function ($view): void {
            $view->with('navItems', auth()->check()
                ? [
                    ['label' => 'لوحتي', 'route' => 'portal.dashboard'],
                    ['label' => 'محفظتي', 'route' => 'portal.portfolio'],
                    ['label' => 'مشاركاتي', 'route' => 'portal.investments'],
                    ['label' => 'التوزيعات', 'route' => 'portal.payouts'],
                    ['label' => 'مستنداتي', 'route' => 'portal.documents'],
                    ['label' => 'العقود', 'route' => 'portal.contracts'],
                    ['label' => 'الأخبار', 'route' => 'portal.news'],
                ]
                : [
                    ['label' => 'الرئيسية', 'route' => 'home'],
                    ['label' => 'العقود', 'route' => 'contracts.index'],
                ]);

            $user = auth()->user();
            $view->with('navUnreadCount', $user ? app(NotificationCenterService::class)->unreadCount($user) : 0);

            // Footer: contact + company info, all sourced from the (cached)
            // Settings store. Kept here — not as inline block @php in the
            // layout — because inline block @php is unreliable in this layout
            // and left these variables undefined. Single source of truth.
            $view->with([
                'supportEmail' => setting('general.support_email'),
                'supportPhone' => setting('general.support_phone'),
                'whatsapp' => setting('contact.whatsapp'),
                'address' => setting('general.company_address'),
                'mapsUrl' => setting('contact.google_maps_url'),
                'companyName' => setting('general.company_name'),
                'crNumber' => setting('general.commercial_registration'),
                'taxNumber' => setting('general.tax_number'),
            ]);
        });

        $this->applyGeneralSettings();
        $this->applyMailSettings();

        // Livewire temporary uploads: route them through the existing `public`
        // disk so Filament's FileUpload completion/preview resolves a direct
        // URL. With the default disk (the private `local` disk) the upload
        // completes server-side but the completion/preview path hangs at
        // "Waiting for upload completion". No disk definitions are changed —
        // only the existing public disk (served via the public/storage symlink)
        // is reused for the short-lived temp uploads.
        config([
            'livewire.temporary_file_upload.disk' => 'public',
            'livewire.temporary_file_upload.directory' => 'livewire-tmp',
        ]);

        // Global Reply-To: applied to every outgoing email from the Settings
        // store (mail.reply_to_address / mail.reply_to_name) without modifying
        // any individual Notification class. Skipped when the message already
        // carries its own Reply-To, and when no Reply-To address is configured.
        Event::listen(MessageSending::class, function (MessageSending $event): void {
            $settings = app(Settings::class);
            $replyTo = $settings->get('mail.reply_to_address');

            if (blank($replyTo) || ! empty($event->message->getReplyTo())) {
                return;
            }

            $event->message->replyTo(new Address($replyTo, (string) $settings->get('mail.reply_to_name')));
        });
    }

    /**
     * Apply the stored general settings (site name, timezone, language) to the
     * runtime config so the database remains the single source of truth.
     */
    private function applyGeneralSettings(): void
    {
        $settings = app(Settings::class);

        if (filled($name = $settings->get('general.site_name'))) {
            config(['app.name' => $name]);
        }

        if (filled($tz = $settings->get('general.timezone'))) {
            config(['app.timezone' => $tz]);
            date_default_timezone_set($tz);
        }

        if (filled($locale = $settings->get('general.default_language'))) {
            config(['app.locale' => $locale]);
            $this->app->setLocale($locale);
        }
    }

    /**
     * Override mail config with values stored in the DB (admin Settings page),
     * so the configured SMTP credentials are used without touching .env.
     */
    private function applyMailSettings(): void
    {
        $settings = app(Settings::class);

        $host = $settings->get('mail.host');
        if (blank($host)) {
            return; // Not configured yet → fall back to .env defaults.
        }

        config([
            'mail.default' => $settings->get('mail.mailer', 'smtp'),
            'mail.mailers.smtp.host' => $host,
            'mail.mailers.smtp.port' => (int) $settings->get('mail.port', 587),
            'mail.mailers.smtp.username' => $settings->get('mail.username'),
            'mail.mailers.smtp.password' => $settings->get('mail.password'),
            'mail.mailers.smtp.encryption' => $settings->get('mail.encryption') ?: null,
            'mail.from.address' => $settings->get('mail.from_address') ?: config('mail.from.address'),
            'mail.from.name' => $settings->get('mail.from_name') ?: config('mail.from.name'),
        ]);
    }
}
