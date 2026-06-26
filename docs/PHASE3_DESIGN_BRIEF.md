# Phase 3 — Design Brief (لوحة الإدارة)

> **حالة الوثيقة:** للمراجعة والموافقة قبل أي كود. مستوحاة من Velzon Material Analytics RTL.
> الهدف: لوحة إدارة بمستوى تحليلي حقيقي (KPIs + مخططات + حالات + نشاط) — **مش Dashboard افتراضية بأرقام**.

---

## 1. لغة التصميم (Design Language)

| العنصر | القرار |
|---|---|
| الإلهام | Velzon Material Analytics (RTL) |
| الاتجاه | RTL كامل — القائمة الجانبية **يمين**، المحتوى يسار |
| الخط | Tajawal (متناسق مع واجهة المستثمر) |
| الزوايا | ناعمة (rounded-lg) على الكروت |
| الظل | خفيف جدًا / حدود رفيعة (أسلوب Material flat) |
| اللوحة اللونية | **أساسي: Teal/Green** (مالي/نمو) + Blue ثانوي + دلالات (warning/danger/success) |
| العملة | ريال سعودي (SAR) بتنسيق موحّد |

---

## 2. المقاربة التقنية لتحقيق المستوى ده في Filament 3

عشان نتجاوز شكل Filament الافتراضي، محتاجين 3 طبقات:

1. **Custom Filament Theme** عبر `php artisan make:filament-theme` →
   ملف Tailwind CSS مخصّص + بناء Vite + تسجيل `->viteTheme(...)` في `AdminPanelProvider`.
   فيه نضبط الألوان (Teal primary)، المسافات، شكل الكروت، والـ RTL.
2. **حزمة ApexCharts متوافقة مع Filament 3** (انظر قسم 4).
3. **Custom Widgets بـ Blade views** — مش `StatsOverviewWidget` الافتراضي، لأنه بسيط الشكل. كل KPI card وكل مخطط ككمبوننت بـ view خاص.

> هذه إضافات بنية (تخصيص ثيم + حزمة charts)، لا منطق أعمال. كل منطق الأعمال موجود بالفعل في Services/Actions من Phase 2.

---

## 3. تخطيط الـ Dashboard (4 صفوف) — يطابق الموك-أب

**Header:** شعار «أساس» (يمين) · بحث (وسط) · جرس إشعارات داخلي + قائمة المستخدم (يسار).
**Sidebar (يمين):** لوحة التحكم · العقود · المشاركات · المستثمرون · التوزيعات · الأخبار · سجل النشاط · الإعدادات.

| الصف | المحتوى | النوع |
|---|---|---|
| 1 — KPI Cards | إجمالي المستثمرين · إجمالي الاستثمارات · المشاركات المعلّقة · التوزيعات المستحقة | Custom Stat Widget |
| 2 — Analytics | نمو الاستثمارات · نمو المستثمرين | ApexCharts Area |
| 3 — Status | حالة العقود · حالة التوزيعات | ApexCharts Donut |
| 4 — Activity | أحدث المشاركات · أحدث المستثمرين · سجل النشاط | Table/List Widgets |

كل الأرقام تُحسب من الـ Models/Services الموجودة (مثلاً KPI «التوزيعات المستحقة» = `Payout::where(status, due)->count()`).

---

## 4. المخططات (Charts) — حزمة مطلوبة

نعتمد **ApexCharts** عبر حزمة:

```
leandrocfe/filament-apex-charts:^3.1
```

- متوافقة مع Filament 3، تتكامل كـ Widget (`ApexChartWidget`).
- **محتاجة موافقتك على تثبيتها** قبل ما أضيفها (composer require).
- المخططات: Area (نمو) × 2، Donut (حالات) × 2. كلها RTL وألوان الثيم.

---

## 5. الـ Design System داخل Filament

كمبوننتس Blade قابلة لإعادة الاستخدام (في `resources/views/filament/components` + الثيم):

| المكوّن | الوصف | التطبيق |
|---|---|---|
| **Custom Stats Cards** | كارت KPI: أيقونة + رقم كبير + label + trend | Blade view widget |
| **Custom Badges** | شارة حالة ملوّنة — **مدفوعة بـ `status_color`/`label` من الـ Enums** (صفر تكرار) | Blade component |
| **Custom Empty States** | شكل فاضي أنيق (أيقونة + نص + CTA) للجداول الفاضية | override لـ Filament empty state |
| **Custom Tables** | تنسيق صفوف/رؤوس بأسلوب Velzon + badges | theme CSS + column styling |
| **Skeleton Loaders** | هياكل تحميل للـ widgets والمخططات أثناء الجلب | CSS + `wire:loading` |
| **KPI Widgets** | الأساس المشترك لكروت المؤشرات | abstract widget + view |

---

## 6. تصاميم الـ Resources (Form / Table / Filters / Actions / Bulk)

> القاعدة الذهبية: **لا منطق أعمال داخل أي Resource**. الأكشنز الحسّاسة تنادي Action classes فقط.

### 6.1 ContractResource
- **Form:** أقسام — (بيانات العقد: title, activity_type, status, description) · (المبالغ والمدة: target/min/max amount, expected_return, duration_months, payouts_count) · (التوقيت: opens_at — يظهر عند upcoming).
- **Table:** title · activity_type · status (badge) · target_amount (SAR) · min/max · duration_months · payouts_count · investments_count · created_at.
- **Filters:** status · activity_type · created_at range.
- **Actions:** View · Edit · Delete.
- **Bulk:** Delete.
- **Relation Managers:** المشاركات (Investments) · المهتمون (Interests — عرض فقط).

### 6.2 InvestmentResource  ⚠️ (الأهم)
- **Form:** user (searchable) · contract (searchable) · amount · status (للعرض) · start_date/end_date (للعرض بعد الاعتماد) · approved_at · rejection_reason.
- **Table:** user.name · contract.title · amount (SAR) · status (badge) · created_at · approved_at.
- **Filters:** status · contract · created_at range.
- **Actions:**
  - **«اعتماد»** — يظهر فقط لو `pending` — **ينادي `ApproveInvestment::execute()` فقط** (تأكيد modal). أي خطأ (مثل `InvestmentAlreadyProcessedException`) يظهر كـ notification.
  - **«رفض»** — يظهر فقط لو `pending` — modal بسبب الرفض → **ينادي `RejectInvestment::execute()`** *(أكشن صغير جديد مقترح في Phase 3 — انظر قسم 9)*.
  - View · Edit.
- **Bulk:** لا شيء (الاعتماد قرار فردي مقصود).

### 6.3 InvestorResource (users)
- **Form:** name · email · phone · kyc_status (select) · الأدوار (عرض).
- **Table:** name · email · phone · kyc_status (badge) · الأدوار · investments_count · created_at.
- **Filters:** kyc_status · الدور (admin/investor/member).
- **Actions:** View · Edit (تعديل `kyc_status` يدوي).
- **Bulk:** لا شيء (أو تصدير لاحقًا).
- **ملاحظة نطاق:** المورد يعرض المستخدمين مع فلتر الدور؛ افتراضيًا investor + member.

### 6.4 PayoutResource
- **Form:** investment (عرض) · type (عرض) · sequence · due_date · **amount (قابل للتعديل — للربح)** · status · paid_at · notes.
- **Table:** المستثمر · العقد · type (badge: ربح/رأس مال) · sequence · due_date · amount (SAR) · status (badge) · paid_at.
- **Filters:** status · type · contract · due_date range · «المستحقة فقط».
- **Actions:**
  - **«تعليم كمدفوع»** — يظهر لو ≠ paid — **ينادي `MarkPayoutPaid::execute()` فقط** (تأكيد modal). أخطاء (`PayoutAmountMissingException` / `PayoutAlreadyPaidException`) تظهر كـ notification بالعربي.
  - «تعديل المبلغ» (Edit).
- **Bulk:** (اختياري) «تعليم كمدفوع» جماعي يمر على `MarkPayoutPaid` صف-صف ويبلّغ بالفاشل — مبدئيًا مؤجّل، نبدأ فردي.

### 6.5 NewsResource
- **Form:** title · body · is_published (toggle) · published_at.
- **Table:** title · is_published (badge) · published_at · created_at.
- **Filters:** is_published · created_at range.
- **Actions:** View · Edit · Delete · «نشر/إلغاء نشر» (toggle).
- **Bulk:** نشر · إلغاء نشر · حذف.

### 6.6 سجل النشاط (Activity Log)
- مورد **للقراءة فقط** فوق موديل `Activity` (spatie): الكيان · الوصف · المُنفِّذ (causer) · التغييرات · التاريخ.
- **قرار:** نبنيه يدويًا (read-only Resource) بدون حزمة إضافية — أو نستخدم حزمة جاهزة. (انظر قسم 9).

---

## 7. تدفّق العمل لكل Resource

لكل مورد: **أعرض التصميم التفصيلي (Form/Table/Filters/Actions/Bulk) → أتوقف للموافقة → أنفّذ**. (حسب طلبك «قبل بناء كل Resource».)

---

## 8. خطة تنفيذ Phase 3 (خطوات فرعية، كل واحدة بموافقة)

| خطوة | المحتوى |
|---|---|
| 3.0 | **Design Brief (الوثيقة دي) + موك-أب** ← الحالية |
| 3.1 | تثبيت حزمة ApexCharts + إنشاء Custom Theme + لوحة ألوان Teal + RTL |
| 3.2 | Design System (Stats Cards, Badges, Empty States, Tables, Skeletons, KPI base) |
| 3.3 | Dashboard Widgets (KPIs + مخططات + حالات + نشاط) بمستوى الموك-أب |
| 3.4 | Resources واحدًا واحدًا (تصميم→موافقة→تنفيذ): Contract → Investment → Investor → Payout → News → Activity |

---

## 9. نقاط محتاجة قرارك قبل البدء

1. **حزمة ApexCharts** `leandrocfe/filament-apex-charts` — موافق على تثبيتها؟
2. **Custom Theme + بناء Vite** — موافق (هيتطلب `npm run build` للثيم)؟ ولو عندك **ألوان/لوجو أساس** الرسمية ابعتهم؛ غير كده هستخدم لوحة Teal مقترحة.
3. **أكشن `RejectInvestment` جديد صغير** (للرفض + سبب) — موافق أضيفه في Phase 3 (مع Unit test)؟
4. **سجل النشاط**: أبنيه read-only Resource يدوي (بدون حزمة) — ولا تفضّل حزمة جاهزة؟
5. **العملة**: ريال سعودي (SAR) — تأكيد.
6. **bulk «تعليم كمدفوع»**: نأجّله ونبدأ فردي — موافق؟
