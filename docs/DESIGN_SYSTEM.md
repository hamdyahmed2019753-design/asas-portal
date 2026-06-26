# Design System — بوابة مستثمري أساس (Admin)

> **المرجع التصميمي الرسمي.** كل قيمة هنا مُطبّقة كتوكِن في
> `resources/css/filament/admin/theme.css` و `tailwind.config.js`.
> **قاعدة ملزمة:** المكوّنات تشير للتوكِنز (CSS variables / utilities) — **ممنوع** قيم hex ثابتة داخل المكوّنات.

---

## 1. Color Palette

### Primary — Teal «أساس»
| 50 | 100 | 200 | 300 | 400 | **500** | 600 | 700 | 800 | 900 |
|---|---|---|---|---|---|---|---|---|---|
| `#ECFBF5` | `#CFF4E5` | `#A2E9CD` | `#6BD8B0` | `#36C291` | **`#15A878`** | `#0E8A63` | `#0C6E50` | `#0C5740` | `#093F2F` |

### Info — Blue
`50 #EAF3FB` · `500 #2D7FC4` · `700 #184B7E`

### Semantic
| | 50 | 500 | 700 |
|---|---|---|---|
| success | `#E9F7EE` | `#1F9D57` | `#0F6E3A` |
| warning | `#FEF5E6` | `#E2A00F` | `#8A5E00` |
| danger | `#FCECEA` | `#E04B43` | `#8F231D` |

### Neutral / Gray
`50 #F5F7F7` · `100 #EDF0F0` · `200 #E1E5E5` · `300 #CCD2D2` · `400 #9BA3A3` · `500 #6C7474` · `600 #4C5252` · `700 #363B3B` · `800 #232727` · `900 #141717`

### CSS Variable Tokens
كلها معرّفة كـ `--asas-{group}-{stop}` + الأسطح `--asas-app-bg / --asas-card-bg / --asas-border / --asas-text / --asas-text-secondary / --asas-text-muted`.
في Filament: `primary/info/success/warning/danger` مُسجّلة في `AdminPanelProvider->colors()` عبر `Color::hex(...)`.

### KPI Cards — توزيع الألوان (إلزامي، مش كله نفس اللون)
| الكارت | اللون |
|---|---|
| إجمالي المستثمرين | **info** (Blue) |
| إجمالي الاستثمارات | **primary** (Teal) |
| المشاركات المعلّقة | **warning** |
| التوزيعات المستحقة | **warning** |
| العقود المفتوحة | **success** |
| العقود الجارية | **primary** |

### Badges — مربوطة بالـ Enums (`status_color`/`status_label`)
`ContractStatus`: upcoming→info · open→success · running→primary · closed→warning · finished→gray.
`InvestmentStatus`: pending→warning · approved→success · rejected→danger.
`PayoutStatus`: scheduled→gray · due→warning · paid→success.

### Activity Timeline — ألوان الأحداث
| الحدث | اللون | توكِن |
|---|---|---|
| created | success | `--asas-activity-created` |
| updated | info | `--asas-activity-updated` |
| deleted | danger | `--asas-activity-deleted` |

---

## 2. Typography Scale
الخط: **Tajawal** (400 / 500 / 700) — مُسجّل عبر `->font('Tajawal')`.

| توكِن | حجم | line-height | وزن | الاستخدام |
|---|---|---|---|---|
| display | 24px | 1.3 | 700 | عنوان الصفحة |
| h2 | 20px | 1.35 | 700 | عنوان قسم |
| h3 | 16px | 1.45 | 500 | عنوان كارت |
| body | 14px | 1.65 | 400 | النص/الجداول |
| label | 13px | 1.5 | 500 | تسميات/شارات |
| small | 12px | 1.5 | 400 | تلميحات |
| kpi | 28px | 1.15 | 700 | رقم KPI |

---

## 3. Spacing System
أساس 4px: `1=4 · 2=8 · 3=12 · 4=16 · 5=20 · 6=24 · 8=32 · 10=40 · 12=48`.
ثوابت: **padding الكارت = 20** · **gap الشبكة = 16** · **إيقاع الأقسام = 24**.
Radius: `sm 6 · md 8 · lg 12 (كروت) · xl 16 · full 9999 (pills)`.
Shadow: card `0 1px 2px rgba(16,24,24,.06), 0 1px 3px rgba(16,24,24,.04)` · hover `0 4px 12px rgba(16,24,24,.08)`.

---

## 4. Card Design
خلفية `--asas-card-bg` · حد `1px --asas-border` · زوايا `12px` · حشو `20px` · ظل card · رأس `h3` + أكشن اختياري.
**KPI Card:** chip أيقونة (40×40، radius 10، خلفية tint لون الكارت) + رقم `kpi` (28/700) + label `small` + سطر trend (success/danger + سهم).

---

## 5. Badge Design
pill · حشو `4px 10px` · `12/500` · خلفية `{color}-50` · نص `{color}-700` · نقطة اختيارية. يُولَّد من accessors الـ Enums (صفر `switch`).

---

## 6. Charts Rules
المحرّك: **ApexCharts** عبر `leandrocfe/filament-apex-charts`. **ممنوع ألوان عشوائية** — فقط توكِنز ثابتة:

| المخطط | النوع | الألوان |
|---|---|---|
| نمو الاستثمارات | Area | `--asas-chart-teal` (#15A878) |
| نمو المستثمرين | Area | `--asas-chart-blue` (#2D7FC4) |
| حالة التوزيعات | Donut | success · warning · gray |
| حالة العقود | Donut | success · warning · danger · gray (+ info/primary حسب الحالة) |

كل المخططات RTL، بدون gradients عشوائية، وألوانها من `--asas-chart-*`.

---

## 7. Layout Rules
- شبكة الـ Dashboard: 6 KPI في صفّين (3+3) ثم صف Charts (2) ثم Status (2) ثم Activity.
- الكروت على خلفية الصفحة `--asas-app-bg`، gap 16، إيقاع رأسي 24.
- Header: شعار «أساس» (يمين) · بحث · **Notification Center** (جرس + لوحة) · قائمة المستخدم.
- كل النصوص RTL، الأرقام بتنسيق `money()` للمبالغ.

---

## 8. Sidebar Rules
| الخاصية | القيمة |
|---|---|
| Expanded width | **280px** (`->sidebarWidth('280px')`) |
| Collapsed width | **80px** (`->collapsedSidebarWidth('80px')`) |
| Collapse | على الديسكتوب (`->sidebarCollapsibleOnDesktop()`) |
| Tooltips | عند الطي تظهر تلميحات أسماء العناصر |
| Transition | `width .25s ease` (في الثيم) |
| Active state | لون primary واضح على العنصر النشط |
| Mobile | Drawer (سلوك Filament الافتراضي للموبايل) |

الأقسام: لوحة التحكم · العقود · المشاركات · المستثمرون · التوزيعات · الأخبار · سجل النشاط · الإعدادات.

---

## 9. Dark Mode Rules
- مُفعّل من الأساس (مش مؤجّل). كل الألوان عبر CSS variables تنقلب تحت `.dark`.
- توكِنز Dark: `app-bg #0E1414` · `card #161D1D` · `border #283030` · `text #ECEFEF` · `text-secondary #AEB6B6` · `text-muted #8A9292` · ظل أغمق.
- **قاعدة:** أي مكوّن يستخدم `--asas-*` يعمل في الوضعين تلقائيًا. ممنوع لون ثابت لا ينقلب.

---

## 10. Money Helper
تنسيق المبالغ موحّد عبر `money($amount, 'SAR')` → مثال `money(15000)` = `15,000.00 ر.س`.
المنطق في `App\Support\Money`. **ممنوع `number_format()` مباشرة** في الواجهات أو الـ Resources.

---

## 11. مرجع قرارات Phase 3 المعتمدة
6 KPIs (info/primary/warning · warning/success/primary) · 4 charts بألوان ثابتة · Sidebar 280/80 + drawer · Dark mode من الأساس · Notification Center في الـ Header · `RejectInvestment` + `rejected_at` · Activity read-only Resource · `money()` للـ SAR · ثيم مخصّص بالكامل · bulk mark-paid مؤجّل.
