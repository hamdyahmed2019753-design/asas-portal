# Production Checklist — بوابة مستثمري أساس

قائمة فحص ما قبل النشر للإنتاج. راجِع كل بند قبل الإطلاق.

---

## 1. البيئة (.env)
- [ ] `APP_ENV=production`
- [ ] `APP_DEBUG=false` ← **إلزامي** (لمنع تسريب الأخطاء)
- [ ] `APP_URL=https://...` (نطاق HTTPS الفعلي)
- [ ] `APP_KEY` مولّد (`php artisan key:generate`)
- [ ] `SHOW_PUBLIC_RETURNS` مضبوط حسب القرار النظامي
- [ ] بيانات `DB_*` للإنتاج (مستخدم بصلاحيات محدودة، كلمة مرور قوية)
- [ ] `SESSION_SECURE_COOKIE=true` · `SESSION_DRIVER=database`/`redis`

## 2. الأمان
- [x] دخول لوحة الإدارة محصور على دور `admin` (`canAccessPanel`)
- [x] Policies على كل الموارد + فرض read-only
- [x] Security Headers (X-Frame-Options · X-Content-Type-Options · Referrer-Policy · Permissions-Policy)
- [x] Rate limiting على Login / Password Reset / Filament Login
- [ ] HTTPS مفروض (إعادة توجيه + HSTS على مستوى الخادم/البروكسي)
- [ ] (لاحقًا) مصادقة ثنائية (2FA) للأدمن
- [ ] تدوير المفاتيح والأسرار بعيدًا عن الكود

## 3. قاعدة البيانات
- [x] Indexes على status/type/dates/is_published/event/created_at
- [x] Foreign keys بـ `cascadeOnDelete`
- [ ] `php artisan migrate --force` عند النشر
- [ ] نسخ احتياطي مجدول (يومي) + اختبار الاستعادة

## 4. الأداء والكاش
- [ ] `php artisan config:cache`
- [ ] `php artisan route:cache`
- [ ] `php artisan view:cache`
- [ ] `php artisan filament:cache-components`
- [ ] `php artisan icons:cache`
- [ ] `composer install --optimize-autoloader --no-dev`
- [ ] `npm run build` (أصول الإنتاج)
- [ ] `CACHE_STORE=redis` (موصى به)

## 5. الطوابير والجدولة
- [ ] `QUEUE_CONNECTION=redis`/`database` + عامل طوابير (Supervisor)
- [ ] الـ Scheduler مفعّل عبر cron:
      `* * * * * php /path/artisan schedule:run >> /dev/null 2>&1`
- [ ] تأكيد عمل `payouts:refresh` يوميًا (مسجّل في `routes/console.php`)
- [ ] (لاحقًا عند الحاجة) Laravel Horizon لمراقبة الطوابير

## 6. المراقبة والسجلات
- [ ] `LOG_CHANNEL` مناسب للإنتاج (stack/daily) + تدوير السجلات
- [ ] أداة رصد أخطاء (Sentry/Flare) + تنبيهات
- [ ] مراقبة الصحة عبر `/up` (health endpoint مفعّل)
- [ ] سجل النشاط (Activity Log) مفعّل ويُراجَع دوريًا

## 7. التطبيق
- [ ] إنشاء أول أدمن (`php artisan make:filament-user` أو seeder)
- [ ] التحقق من اللغة العربية و RTL في كل الشاشات
- [ ] التحقق من Dark Mode
- [ ] تشغيل كامل الاختبارات قبل النشر (`phpunit`)

## 8. خارج نطاق الكود
- [ ] الترخيص النظامي مع هيئة السوق المالية (مع مستشار مرخّص)
- [ ] سياسة الخصوصية / الشروط والأحكام
