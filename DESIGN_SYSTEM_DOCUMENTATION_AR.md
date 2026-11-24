# توثيق نظام التصميم — BY RASLAN ERP

## الهوية البصرية
- **Primary (كحلي):** `#17324D` — العناوين، التنقل، الأزرار الأساسية، العناصر الهيكلية.
- **Primary Soft:** `#28557B` — حالة hover للأزرار الأساسية.
- **Accent (تركواز):** `#159D91` — المؤشرات الإيجابية، الحالات النشطة، الرسوم البيانية، عناصر التفاعل الثانوية.
- **Accent Soft:** `#D6F4EF` — خلفيات ناعمة للحالات الإيجابية.
- **الخلفيات:** أبيض `#FFFFFF` للبطاقات، `#F4F7FA` لخلفية التطبيق، `#F8FAFC` للأسطح الثانوية.
- **الحدود:** `#E5EBF2` افتراضي، `#CFDBE7` قوي.
- **النص:** `#17324D` أساسي، `#64748B` ثانوي، `#94A3B8` خافت.
- **الحالات:** نجاح `#159D91`، تحذير `#D97706`، خطر `#DC2626`.

تم منع الألوان النيون، التدرجات المبالغ فيها، الظلال الثقيلة، والـ dark theme الافتراضي. التدرج المستخدم مقتصر على هيدر Dashboard بلونين من نفس هوية الكحلي.

## الخطوط
- الخط الأساسي: **Cairo** مع fallback `ui-sans-serif, system-ui`.
- أوزان: 400/500/600/700/800.
- الأرقام المالية/الكميات تُعرض بـ `dir="ltr"` و`tabular-nums` حيث يلزم.

## المسافات والزوايا
- Radius البطاقة: `14px`.
- Radius الحقول والأزرار: `10px`.
- Padding البطاقة: `1.25rem` افتراضي.
- ظلال: `0 1px 2px rgba(23,50,77,.04), 0 8px 24px rgba(23,50,77,.04)`.

## المكونات الموحدة
تم تأسيس مكونات/أنماط موحدة في `resources/css/app.css` وداخل layout:
- **Buttons:** `.ds-btn`, `.ds-btn-primary`, `.ds-btn-accent`, `.ds-btn-secondary`, `.ds-btn-danger`, `.ds-btn-sm`.
- **Inputs:** `.ds-input`, `.ds-select`, `.ds-textarea`, `.ds-label`.
- **Cards/KPI:** `.ds-card`, `.ds-kpi`, `.ds-kpi-label`, `.ds-kpi-value`, `.ds-kpi-meta`.
- **Tables:** `.ds-table-wrap`, `.ds-table`, `.ds-empty`.
- **Badges:** `.ds-badge-*` للحالات.
- **Flash/Alerts:** `layouts/partials/flash.blade.php`.
- **Layout:** `layouts/app.blade.php`, `layouts/partials/sidebar.blade.php`, `layouts/partials/topbar.blade.php`.
- **Blade components:** `resources/views/components/ui/card.blade.php`, `page-header.blade.php`, `stat-card.blade.php`.

## التوافق مع الصفحات القديمة
تم إضافة Legacy class mapping في CSS لتحويل الأنماط القديمة (`bg-brand-600`, `text-brand-700`, إلخ) إلى الهوية الجديدة، مع pass آلي على ملفات Blade لتوحيد البطاقات والجداول والأزرار والحقول.

## RTL/LTR
- الاتجاه يُحدد من locale (`dir=rtl` افتراضي للعربية).
- Sidebar يدعم الطي/التوسيع وحالة الموبايل.
- الأرقام/التواريخ/المبالغ الحساسة تُعرض `dir="ltr"`.

## الملفات الأساسية
- `resources/css/app.css` — Design tokens وutilities.
- `resources/views/layouts/app.blade.php` — App shell.
- `resources/views/layouts/guest.blade.php` — Guest shell.
- `resources/views/layouts/partials/sidebar.blade.php`
- `resources/views/layouts/partials/topbar.blade.php`
- `resources/views/layouts/partials/flash.blade.php`
- `resources/views/dashboard.blade.php`
- `resources/views/statistics/index.blade.php`
