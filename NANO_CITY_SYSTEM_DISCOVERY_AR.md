# NANO CITY SYSTEM DISCOVERY — تحليل المشروع المرجعي

## 1. معلومات المشروع

| البيان | القيمة |
|--------|--------|
| اسم المشروع | Nano City |
| المسار | `/media/emam-medhat/New Volume/by raslan php laravel/nanocity` |
| Framework | Laravel 11 |
| Livewire | Livewire 4.1 |
| قاعدة البيانات | MySQL (nanoo6.sql) |
|设计 Pattern | MVC + Livewire + Service Layer + Observer Pattern |
| Root Namespace | `App\` |

## 2. هيكل المشروع

```
nanocity/
├── app/
│   ├── Actions/Fortify/          # Fortify actions (login, registration)
│   ├── Console/Commands/         # Artisan commands
│   ├── Events/                   # Domain events
│   ├── Facades/FacadesLogic/    # Custom facades
│   ├── Helpers/                  # Helper functions
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/            # Admin controllers
│   │   │   ├── Api/              # API controllers
│   │   │   ├── Dashboard/        # Dashboard controllers (Shift, Branch, etc.)
│   │   │   ├── Web/              # Web controllers
│   │   │   ├── AuthController.php
│   │   │   ├── BranchController.php (Dashboard)
│   │   │   └── ShiftController.php (Dashboard)
│   │   ├── Livewire/             # Livewire components (legacy)
│   │   ├── Middleware/           # CheckBranchPermission, etc.
│   │   ├── Requests/             # Form Requests
│   │   └── Resources/            # API Resources
│   ├── Livewire/                 # Livewire 4 components
│   │   ├── CreateShift.php
│   │   └── ShiftsList.php
│   ├── Models/                   # Eloquent models
│   │   ├── ERP/                  # ERP models
│   │   ├── Bi/                   # BI models
│   │   ├── MES/                  # Manufacturing models
│   │   ├── Quality/              # Quality models
│   │   ├── Branch.php
│   │   ├── BranchUser.php
│   │   ├── Shift.php
│   │   ├── ShiftExpense.php
│   │   ├── CashTransfer.php
│   │   ├── Account.php
│   │   └── ...
│   ├── Notifications/            # Notifications
│   ├── Observers/                # Model observers
│   │   ├── BranchObserver.php
│   │   ├── ShiftExpenseObserver.php
│   │   └── ShiftObserver.php
│   ├── Providers/                # Service providers
│   ├── Services/                 # Business logic services
│   │   ├── Accounting/
│   │   ├── Mrp/
│   │   ├── POS/
│   │   ├── Production/
│   │   ├── AccountingIntegrationService.php
│   │   ├── BranchService.php
│   │   └── ShiftService.php
│   ├── Support/                  # Support classes
│   └── Traits/
│       ├── BranchTrait.php
│       └── ShiftTrait.php
├── bootstrap/
├── config/
│   └── branch_permissions.php    # Branch permission definitions
├── database/
│   ├── migrations/
│   └── seeders/
├── public/
├── resources/
│   └── views/
│       ├── shifts/
│       │   ├── index.blade.php
│       │   ├── create.blade.php
│       │   ├── edit.blade.php
│       │   └── show.blade.php
│       ├── livewire/
│       │   ├── create-shift.blade.php
│       │   ├── shifts-list.blade.php
│       │   └── shift-closing-receipt.blade.php
│       ├── branches/
│       │   ├── index.blade.php
│       │   ├── create.blade.php
│       │   └── edit.blade.php
│       └── layouts/
│           └── app.blade.php
├── routes/
│   ├── web.php
│   ├── web(Main).php
│   └── api.php
└── tests/
```

## 3. Branches Management (إدارة الفروع)

### 3.1 Database Schema

**Table: `branches`**
| Column | Type | Notes |
|--------|------|-------|
| id | bigint | PK |
| created_by | bigint (FK users) | منشئ الفرع |
| name | varchar | اسم الفرع |
| code | varchar (nullable) | كود الفرع |
| phone | varchar (nullable) | هاتف الفرع |
| address | varchar (nullable) | عنوان الفرع |
| is_active | boolean | حالة الفرع |
| business_id | bigint (FK users) | معرف النشاط التجاري |
| owner_id | bigint (FK users) | مالك الفرع |
| region_id | bigint (FK regions) | المنطقة الجغرافية |

**Table: `branch_users`** (Pivot - ربط المستخدمين بالفروع)
| Column | Type | Notes |
|--------|------|-------|
| id | bigint | PK |
| branch_id | bigint (FK branches) | الفرع |
| user_id | bigint (FK users) | المستخدم |
| role | varchar | owner, main_manager, manager, cashier, staff |
| is_primary_manager | boolean | مدير أساسي |
| can_manage_permissions | boolean | يمكنه إدارة الصلاحيات |
| permissions | json | صلاحيات مخصصة |
| assigned_by | bigint (FK users) | من قام بالتعيين |
| UNIQUE(branch_id, user_id) | | منع التكرار |

**Table: `branch_links`** (ربط الفروع ببعضها)
**Table: `branch_creation_requests`** (طلبات إنشاء فروع)

### 3.2 Business Logic

- **BranchService**: يدير دورة حياة الفرع
  - `index()`: عرض الفروع مع pagination وfilters
  - `store()`: إنشاء فرع + ربط المالك كمدير أساسي تلقائياً
  - `update()`: تحديث بيانات الفرع
  - `destroy()`: حذف الفرع
  - `assignManager()`: ربط مستخدم بالفرع مع تحديد دوره وصلاحياته

### 3.3 Permission System

Nano City يستخدم نظام صلاحيات متعدد المستويات:
1. **Package permissions**: `package.permission:*` - صلاحيات على مستوى الحزمة
2. **Branch permissions**: `branch.permissions:*` - صلاحيات على مستوى الفرع
3. **User role**: `super_admin`, `admin`, `cashier`, `staff`, `employee`

### 3.4 Branch Permission Config

```php
// config/branch_permissions.php
[
    'key' => 'dashboard.access',        // الوصول للوحة التحكم
    'key' => 'pos.access',              // الوصول لنقطة البيع
    'key' => 'shifts.access',           // الوصول للورديات
    'key' => 'products.access',         // إدارة المنتجات
    'key' => 'orders.access',           // إدارة الطلبات
    'key' => 'management.access',       // الإدارة
    // ... المزيد
]
```

## 4. Users & Employees (المستخدمون والموظفون)

### 4.1 Database Schema

**Table: `users`** (مبسط - بدون أعمدة الفرع مباشرة)
- المستخدمين مرتبطين بالفروع عبر جدول `branch_users` pivot

### 4.2 User Roles

| Role | الوصف | الصلاحيات |
|------|-------|-----------|
| super_admin | مدير النظام | كل الصلاحيات |
| admin | مدير | إدارة الفروع والموظفين |
| cashier | كاشير | البيع والورديات |
| staff | موظف | عمليات محددة |
| employee | موظف | صلاحيات محددة |

### 4.3 User-Branch Relationship

- كل مستخدم يمكن ربطه بفروع متعددة
- كل ربط له دور مختلف (owner, manager, cashier, staff)
- مدير أساسي لكل فرع (is_primary_manager)
- صلاحيات مخصصة لكل فرع (JSON permissions)

## 5. Shifts Management (إدارة الورديات)

### 5.1 Database Schema

**Table: `shifts`**
| Column | Type | Notes |
|--------|------|-------|
| id | bigint | PK |
| user_id | bigint (FK users) | الكاشير |
| branch_id | bigint (FK branches, nullable) | الفرع |
| starting_cash | decimal(10,2) | رصيد البداية |
| expected_cash | decimal(10,2) | المتوقع |
| ending_cash | decimal(10,2, nullable) | رصيد النهاية |
| cash_difference | decimal(10,2, nullable) | الفرق |
| start_time | timestamp | وقت البداية |
| end_time | timestamp (nullable) | وقت النهاية |
| status | enum('active','paused','closed') | الحالة |
| closed_by | bigint (FK users, nullable) | أغلقها |
| notes | text (nullable) | ملاحظات |
| expenses_total | decimal(12,2) | إجمالي المصروفات |
| sent_to_manager | decimal(12,2) | المسلم للمدير |
| carryover_to_next_shift | decimal(12,2) | المرحل للوردية التالية |
| payments_breakdown | json (nullable) | تفصيل طرق الدفع |

**Table: `shift_expenses`** (مصروفات الوردية)
| Column | Type | Notes |
|--------|------|-------|
| id | bigint | PK |
| shift_id | bigint (FK shifts) | الوردية |
| user_id | bigint (FK users) | الموظف |
| branch_id | bigint (FK branches) | الفرع |
| title | varchar | البند |
| amount | decimal(12,2) | المبلغ |
| expense_date | datetime | التاريخ |
| status | enum('pending','approved','rejected') | الحالة |
| approved_by | bigint (FK users) | المعتمد |
| notes | text | ملاحظات |
| receipt_image | varchar | صورة الإيصال |

**Table: `cash_transfers`** (تحويلات نقدية)
| Column | Type | Notes |
|--------|------|-------|
| id | bigint | PK |
| from_shift_id | bigint (FK shifts, nullable) | من وردية |
| to_shift_id | bigint (FK shifts, nullable) | لوردية |
| branch_id | bigint (FK branches) | الفرع |
| from_user_id | bigint (FK users) | الكاشير |
| to_user_id | bigint (FK users) | المدير/الكاشير التالي |
| type | enum('to_manager','to_next_shift','to_safe') | النوع |
| amount | decimal(12,2) | المبلغ |
| status | enum('pending','approved','rejected') | الحالة |
| approved_by | bigint (FK users) | المعتمد |
| notes | text | ملاحظات |

**Table: `shift_incomes`** (إيرادات إضافية للوردية)

### 5.2 Shift Lifecycle (دورة حياة الوردية)

1. **فتح الوردية (Open Shift)**
   - إنشاء سجل وردية جديد
   - تحديد: user_id, branch_id, starting_cash, start_time
   - حالة: `active`
   - منع فتح وردية ثانية لنفس المستخدم (ensureNoActiveShiftForUser)
   - إنشاء سجل Attendance تلقائياً

2. **أثناء الوردية (During Shift)**
   - تسجيل المبيعات (ربط بـ shift_id)
   - تسجيل المصروفات (shift_expenses)
   - تحويلات نقدية (cash_transfers: to_manager, to_safe)
   - عرض تفاصيل الوردية
   - حساب الرصيد المتوقع

3. **إغلاق الوردية (Close Shift)**
   - حساب المصروفات (expenses_total)
   - حساب التحويلات للمدير (sent_to_manager)
   - حساب الرصيد المتوقع (expected_cash)
   - إدخال الرصيد الفعلي (ending_cash)
   - حساب الفرق (cash_difference = ending_cash - expected_cash)
   - ترحيل المتبقي للوردية التالية (carryover_to_next_shift)
   - تسجيل التسليم للمدير (إن وجد)
   - تحديث حالة الوردية إلى `closed`
   - تسجيل وقت الإغلاق والمغلق
   - إرسال إشعار
   - تسجيل قيد محاسبي للفروقات

### 5.3 Business Rules

1. **منع تكرار الورديات**: مستخدم واحد لا يمكنه فتح أكثر من وردية نشطة
2. **حالات الوردية**: active → paused → closed
3. **حساب الرصيد المتوقع**:
   ```
   expected_cash = starting_cash + cash_sales - expenses - transfers_to_manager
   ```
4. **حساب الفرق**:
   ```
   cash_difference = ending_cash - expected_cash
   ```
5. **الترحيل للوردية التالية**: المتبقي الفعلي في الدرج
6. **عدم تعديل وردية مغلقة**: throw ValidationException
7. **إغلاق الوردية**: requires ending_cash (الرصيد الفعلي)

### 5.4 ShiftObserver (مراقب الوردية)

عند إنشاء وردية جديدة:
- إرسال إشعار للمستخدم

عند تحديث حالة الوردية إلى `closed`:
- إرسال إشعار بالإغلاق
- إرسال إشعار بالفرق (عجز/زيادة)
- تسجيل قيد محاسبي للفروقات (AccountingIntegrationService)

## 6. Accounting & Cash Management (الحسابات)

### 6.1 Database Schema

**Table: `accounts`**
| Column | Type | Notes |
|--------|------|-------|
| id | bigint | PK |
| code | varchar (unique) | كود الحساب |
| name | varchar | اسم الحساب |
| type | enum('asset','liability','equity','revenue','expense') | النوع |
| parent_id | bigint (FK accounts, nullable) | الحساب الأب |
| balance | decimal(15,2) | الرصيد |
| is_active | boolean | نشط |
| branch_id | bigint (FK branches, nullable) | الفرع |
| opening_balance | decimal | الرصيد الافتتاحي |
| allow_posting | boolean | يسمح بالترحيل |

**Table: `journal_entries`** (القيود اليومية)
**Table: `journal_entry_lines`** (سطور القيود)

### 6.2 AccountingIntegrationService

تكامل محاسبي تلقائي:
- `postSale()`: قيد المبيعات (Dr. Cash / Cr. Revenue)
- `postOrderPayments()`: قيد المبيعات متعدد الدفع
- `postSalesReturn()`: قيد مرتجع المبيعات
- `postShiftClosing()`: قيد فروقات الصندوق (فائض/عجز)
- `postExpense()`: قيد المصروفات
- `postShiftExpense()`: قيد مصروفات الوردية
- `postStockCount()`: قيد فروقات الجرد
- `postGoodsReceipt()`: قيد استلام بضاعة
- `postInventoryMovement()`: قيد حركة المخزون
- `postPurchaseOrder()`: قيد أمر شراء

### 6.3 Account Resolution

```php
private function resolveAccount($code, $name, $type, $userId): Account
{
    // 1. Try user-scoped code (e.g., "1101-u71")
    // 2. Try base code
    // 3. Create new account if not found
}
```

## 7. Inventory & Stocktaking (المخزون والجرد)

### 7.1 Database Schema

**Table: `inventories`** (المخزون)
**Table: `inventory_movements`** (حركات المخزون)
**Table: `stock_counts`** (الجرد)
**Table: `stock_count_items`** (بنود الجرد)

### 7.2 Stock Count Workflow

1. إنشاء جرد جديد (stock_count)
2. إدخال الكميات الفعلية (actual_quantity)
3. مقارنة الكمية الفعلية بالكمية الدفترية (system_quantity)
4. حساب الفروقات (difference = actual - system)
5. اعتماد الجرد (approve)
6. تسجيل حركات المخزون للتسوية (inventory_movements)
7. تسجيل قيد محاسبي للفروقات (AccountingIntegrationService)

## 8. Shift Page (صفحة الوردية)

### 8.1 Layout

```
shifts/index.blade.php
├── Page Header (عنوان + أزرار)
├── Stats Cards (إجمالي، نشط، مغلق، الفروقات)
├── Filters (بحث، موظف، فرع، حالة، تاريخ)
├── Table (قائمة الورديات)
│   ├── # (رقم)
│   ├── الموظف
│   ├── المتجر/الفرع
│   ├── بداية الشيفت
│   ├── نهاية الشيفت
│   ├── عدد الساعات
│   ├── رصيد بداية الدرج
│   ├── رصيد نهاية الدرج
│   ├── الرصيد الحقيقي (بداية + مبيعات)
│   ├── التاريخ
│   ├── الحالة
│   └── الإجراءات (إغلاق، طباعة، عرض، تعديل، حذف)
├── Pagination
└── Modals (إضافة شيفت، إغلاق شيفت)
```

### 8.2 Key Features

- **Real-time balance calculation**: الرصيد الحقيقي = رصيد البداية + إجمالي المبيعات
- **Shift closing modal**: إغلاق الوردية مع إدخال الرصيد الفعلي
- **Print closing receipt**: طباعة تقرير إغلاق الوردية (58mm thermal printer)
- **Status indicators**: نشط (أخضر)، موقوف (أصفر)، مغلق (رمادي)
- **Difference display**: عجز (أحمر)، زيادة (أصفر)، مطابق (أخضر)
- **Responsive**: desktop table + mobile card view
- **Search**: بالاسم، رقم الشيفت، الكاشير، الفرع، الحالة

## 9. Design System

- **Framework**: AdminLTE 3 (Bootstrap 4) + Tailwind CSS
- **Icons**: Font Awesome
- **Notifications**: SweetAlert2
- **Charts**: Chart.js
- **RTL**: دعم كامل للعربية
- **Color scheme**: أزرق، أخضر، أحمر، أصفر

## 10. Key Routes

```php
// Shifts
Route::resource('shifts', ShiftController::class);
Route::post('shifts/{shift}/close', [ShiftController::class, 'close'])->name('shifts.close');
Route::get('shifts/{shift}/closing-receipt', [ShiftReceiptController::class, 'show'])->name('pos.shift.closing-reipt');

// Branches
Route::resource('settings.branches', BranchController::class);
Route::post('branches/{branch}/assign-manager', [BranchController::class, 'assignManager']);

// Accounting
Route::resource('accounts', AccountController::class);
Route::resource('shift-expenses', ShiftExpenseController::class);
Route::resource('cash-transfers', CashTransferController::class);
```

## 11. Summary

Nano City is a comprehensive restaurant/retail management system with:
- Full branch management with user-branch linking
- Complete shift lifecycle (open, manage, close)
- Shift expenses with approval workflow
- Cash transfers (to manager, to next shift, to safe)
- Accounting integration with automatic journal entries
- Inventory management with stocktaking
- Modern UI with Livewire components
- Multi-branch support with per-branch permissions
- Comprehensive reporting

The system follows a service-oriented architecture with clear separation between:
- Controllers (HTTP handling)
- Services (Business logic)
- Models (Data access)
- Observers (Event handling)
- Livewire Components (Interactive UI)