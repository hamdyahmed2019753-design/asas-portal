# Architecture — بوابة مستثمري أساس

وثيقة معمارية حيّة تتحدّث مع كل مرحلة. الحالة الحالية: **نهاية Phase 2 (طبقة منطق الأعمال مكتملة ومُختبَرة)**.

النظام بوابة مستثمرين إلكترونية على **Laravel 12 + Filament 3 + Livewire 3**، عربية RTL، MySQL 8. كل المتابعة رقمية وداخلية — **لا دفع، لا نفاذ، لا إرسال خارجي**.

---

## مبادئ معمارية

1. **منطق الأعمال في `Services` و`Actions` فقط** — ممنوع داخل Controllers أو Filament Resources.
2. **مصدر واحد للحقيقة في العرض**: نصوص الحالات وألوانها (`label()` / `color()`) داخل الـ **Enums**، والموديلات تعرضها عبر accessors (`status_label`, `status_color`, `type_label`) — بدون أي `switch` مكرّر في الواجهة.
3. **DTOs و Exceptions في مجلداتها الخاصة** — لا توضع داخل `Services`.
4. **Enums مدعومة بقيم string** لكل الحالات، مع cast مباشر في الموديل.

---

## Domain Models

| Model | الجدول | الوصف |
|---|---|---|
| `User` | `users` | المستخدم. يحمل `phone` و`kyc_status` (يدوي)، والأدوار عبر spatie (`HasRoles`). |
| `Contract` | `contracts` | العقد الاستثماري. `expected_return` **عرض فقط** ولا يدخل أي حساب. |
| `Investment` | `investments` | مشاركة مستخدم في عقد. تُعتمد يدويًا → تُولّد التوزيعات. |
| `Payout` | `payouts` | توزيعة فردية. `type` = `profit` أو `capital`. |
| `ContractInterest` | `contract_interests` | اهتمام داخلي بعقد قادم (بديل «أبلغني») — بدون أي إرسال. فريد لكل (user, contract). |
| `NewsUpdate` | `news_updates` | خبر في «الجديد في أساس». |

### قرارات مفتاحية على الـ Payouts
- **التوزيعات = ربح فقط**؛ رأس المال يرجع كـ **توزيعة `capital` واحدة** تاريخها = نهاية العقد، ومبلغها = مبلغ المشاركة.
- مبلغ توزيعة الربح **يدوي** (`amount = null` عند التوليد، الإدارة تكتبه).
- `sequence` = 1..payouts_count لتوزيعات الربح، و`null` لتوزيعة رأس المال.

---

## Enums (`app/Enums`)

كل enum يوفّر `label()` (عربي) — وحالات الكيانات توفّر أيضًا `color()` (توكِن لون لـ Filament/الواجهة).

| Enum | القيم | label | color |
|---|---|---|---|
| `KycStatus` | pending, verified, rejected | ✅ | — |
| `ContractStatus` | upcoming, open, running, closed, finished | ✅ | ✅ |
| `InvestmentStatus` | pending, approved, rejected | ✅ | ✅ |
| `PayoutStatus` | scheduled, due, paid | ✅ | ✅ |
| `PayoutType` | profit, capital | ✅ | — |

### Accessors المعتمدة على الـ Enums
- `Contract`: `status_label`, `status_color`
- `Investment`: `status_label`, `status_color`
- `Payout`: `type_label`, `status_label`, `status_color`

---

## Relationships

```
User 1───* Investment *───1 Contract
User 1───* ContractInterest *───1 Contract
Investment 1───* Payout
```

| من | العلاقة | إلى |
|---|---|---|
| `User` | hasMany | `Investment`, `ContractInterest` |
| `Contract` | hasMany | `Investment`, `ContractInterest` |
| `Investment` | belongsTo / hasMany | `User`, `Contract` / `Payout` |
| `Payout` | belongsTo | `Investment` |
| `ContractInterest` | belongsTo | `User`, `Contract` |

كل الـ FKs عليها `cascadeOnDelete`. `contract_interests` عليها `unique(user_id, contract_id)`.

### Query Scopes
| Model | Scope | المنطق |
|---|---|---|
| `Contract` | `publicVisible()` | status ∈ {upcoming, open} |
| `Investment` | `approved()` | status = approved |
| `Payout` | `profit()` / `capital()` | حسب `type` |
| `Payout` | `paid()` | status = paid |
| `Payout` | `upcoming()` | status ∈ {scheduled, due} |
| `NewsUpdate` | `published()` | is_published = true **و** published_at ≤ now() |

---

## Activity Log Strategy

نستخدم `spatie/laravel-activitylog` على **Contract، Investment، Payout** فقط (الكيانات المالية الحسّاسة).

كل موديل منهم يستخدم:
```php
LogOptions::defaults()
    ->logOnly([... الحقول المهمة فقط ...])
    ->logOnlyDirty()        // يسجّل التغيير الفعلي فقط
    ->dontSubmitEmptyLogs() // لا سجل لو مفيش تغيير حقيقي
```

- **لا نسجّل `updated_at`/`created_at`** ولا الحقول غير المهمة.
- الحقول المسجّلة:
  - **Contract**: title, activity_type, expected_return, target_amount, min_amount, max_amount, duration_months, payouts_count, status, opens_at.
  - **Investment**: amount, status, start_date, end_date, approved_at, rejection_reason.
  - **Payout**: type, sequence, due_date, amount, status, paid_at, notes.

الهدف: مسار تدقيق نظيف لتغيّر الحالات والمبالغ (اعتماد مشاركة، تعليم توزيعة مدفوعة، تعديل مبلغ) دون ضوضاء.

---

## طبقة منطق الأعمال (Phase 2 — مكتملة)

```
app/
├── Actions/
│   ├── Investments/   ApproveInvestment
│   └── Payouts/       MarkPayoutPaid
├── Services/          PayoutScheduleGenerator, InvestorBalance
├── DTOs/              InvestorBalanceData
├── Exceptions/        InvestmentAlreadyProcessedException, ...
└── Console/Commands/  RefreshPayouts (payouts:refresh)
```

- **ApproveInvestment**: داخل `DB::transaction`، يمنع الاعتماد المكرر (`InvestmentAlreadyProcessedException`)، يضبط التواريخ، يولّد التوزيعات، يمنح دور `investor`.
- **PayoutScheduleGenerator**: يوزّع تواريخ الربح بالتساوي على المدة + يضيف توزيعة رأس المال في النهاية.
- **InvestorBalance**: يحسب الأرصدة ويرجّع `InvestorBalanceData`.
- **MarkPayoutPaid**: يمنع دفع توزيعة مدفوعة مسبقًا أو توزيعة ربح بمبلغ فارغ.
