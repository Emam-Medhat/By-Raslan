# BRANCHES, USERS & SHIFTS IMPLEMENTATION — تنفيذ الفروع والمستخدمين والورديات

## Phase 1: Branch-User Linking (location_user pivot)

### 1.1 New Migration: `create_location_user_table.php`

```php
Schema::create('location_user', function (Blueprint $table) {
    $table->id();
    $table->string('location_id', 64);
    $table->char('user_id', 36);
    $table->string('role', 20)->default('cashier'); // owner, manager, cashier, staff
    $table->boolean('is_primary')->default(false);
    $table->json('permissions')->nullable();
    $table->char('assigned_by', 36)->nullable();
    $table->timestamps();

    $table->foreign('location_id')->references('id')->on('locations')->cascadeOnDelete();
    $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
    $table->unique(['location_id', 'user_id']);
});
```

### 1.2 New Model: `LocationUser.php`

```php
class LocationUser extends Model
{
    protected $fillable = ['location_id', 'user_id', 'role', 'is_primary', 'permissions', 'assigned_by'];
    protected $casts = ['permissions' => 'array', 'is_primary' => 'boolean'];

    public function location(): BelongsTo { return $this->belongsTo(Location::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function assignedBy(): BelongsTo { return $this->belongsTo(User::class, 'assigned_by'); }
}
```

### 1.3 Update User Model

Add relationship:
```php
public function locations(): BelongsToMany
{
    return $this->belongsToMany(Location::class, 'location_user')
        ->withPivot(['role', 'is_primary', 'permissions', 'assigned_by'])
        ->withTimestamps();
}
```

### 1.4 Update Location Model

Add relationship:
```php
public function users(): BelongsToMany
{
    return $this->belongsToMany(User::class, 'location_user')
        ->withPivot(['role', 'is_primary', 'permissions', 'assigned_by'])
        ->withTimestamps();
}
```

### 1.5 New Controller: `LocationUserController.php`

Methods:
- `index()`: List all users with their location assignments
- `create()`: Form to assign user to location
- `store()`: Assign user to location with role and permissions
- `edit()`: Edit assignment
- `update()`: Update assignment
- `destroy()`: Remove assignment

### 1.6 New Service: `LocationUserService.php`

```php
class LocationUserService
{
    public function assign(User $user, Location $location, string $role, array $permissions = [], bool $isPrimary = false): LocationUser
    {
        return LocationUser::updateOrCreate(
            ['location_id' => $location->id, 'user_id' => $user->id],
            ['role' => $role, 'permissions' => $permissions, 'is_primary' => $isPrimary, 'assigned_by' => auth()->id()]
        );
    }

    public function remove(Location $location, User $user): void
    {
        LocationUser::where('location_id', $location->id)->where('user_id', $user->id)->delete();
    }
}
```

## Phase 2: Shifts Full CRUD

### 2.1 Update Shift Model

Add new fields to fillable:
```php
'closed_by', 'close_notes', 'logged_out_at', 'pos_terminal',
'expenses_total', 'sent_to_manager', 'carryover_to_next_shift'
```

Add relationships:
```php
public function expenses(): HasMany { return $this->hasMany(ShiftExpense::class); }
public function cashTransfers(): HasMany { return $this->hasMany(CashTransfer::class, 'from_shift_id'); }
public function incomingTransfers(): HasMany { return $this->hasMany(CashTransfer::class, 'to_shift_id'); }
```

### 2.2 New Migration: `add_shift_enhancement_fields.php`

```php
Schema::table('shifts', function (Blueprint $table) {
    $table->decimal('expenses_total', 12, 2)->default(0)->after('expected_cash');
    $table->decimal('sent_to_manager', 12, 2)->default(0)->after('expenses_total');
    $table->decimal('carryover_to_next_shift', 12, 2)->default(0)->after('sent_to_manager');
    $table->text('notes')->nullable()->after('close_notes');
    $table->string('status', 10)->default('open'); // open, paused, closed
});
```

### 2.3 New Migration: `create_shift_expenses_table.php`

```php
Schema::create('shift_expenses', function (Blueprint $table) {
    $table->id();
    $table->foreignId('shift_id')->constrained('shifts')->cascadeOnDelete();
    $table->char('user_id', 36);
    $table->string('location_id', 64);
    $table->string('title');
    $table->decimal('amount', 12, 2)->default(0);
    $table->timestamp('expense_date')->nullable();
    $table->string('status', 10)->default('pending'); // pending, approved, rejected
    $table->char('approved_by', 36)->nullable();
    $table->text('notes')->nullable();
    $table->string('receipt_image')->nullable();
    $table->timestamps();

    $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
    $table->foreign('location_id')->references('id')->on('locations')->cascadeOnDelete();
});
```

### 2.4 New Migration: `create_cash_transfers_table.php`

```php
Schema::create('cash_transfers', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('from_shift_id')->nullable();
    $table->unsignedBigInteger('to_shift_id')->nullable();
    $table->string('location_id', 64);
    $table->char('from_user_id', 36)->nullable();
    $table->char('to_user_id', 36)->nullable();
    $table->string('type', 20)->default('to_manager'); // to_manager, to_next_shift, to_safe
    $table->decimal('amount', 12, 2)->default(0);
    $table->string('status', 10)->default('approved'); // pending, approved, rejected
    $table->char('approved_by', 36)->nullable();
    $table->text('notes')->nullable();
    $table->timestamps();

    $table->foreign('from_shift_id')->references('id')->on('shifts')->nullOnDelete();
    $table->foreign('to_shift_id')->references('id')->on('shifts')->nullOnDelete();
    $table->foreign('location_id')->references('id')->on('locations')->nullOnDelete();
});
```

### 2.5 New Service: `ShiftService.php` (Enhancement)

Enhance existing PosService with:
- `createShift()`: Create new shift with full data
- `openShift()`: Open shift (already exists)
- `closeShift()`: Close shift with full calculation (already exists, enhance)
- `calculateExpectedCash()`: Calculate expected cash
- `calculateExpenses()`: Calculate shift expenses
- `calculateTransfers()`: Calculate cash transfers
- `getShiftReport()`: Get shift report with all details

### 2.6 New Controller: `ShiftController.php` (Enhancement)

Add methods:
- `create()`: Show create form
- `store()`: Create shift
- `show()`: Show shift details
- `edit()`: Edit shift
- `update()`: Update shift
- `destroy()`: Delete shift
- `close()`: Close shift (enhanced)
- `movements()`: Show shift movements (already exists)

### 2.7 New Views

- `shifts/create.blade.php`: Create shift form
- `shifts/edit.blade.php`: Edit shift form
- `shifts/show.blade.php`: Shift details page
- `shifts/close.blade.php`: Close shift form (modal or page)

## Phase 3: Shift Page UI/UX Redesign

### 3.1 New `shifts/index.blade.php`

Features:
- Stats cards (total, open, closed, differences)
- Advanced filters (search, location, status, date range, cashier)
- Enhanced table with:
  - Color-coded status badges
  - Difference display (deficit/surplus/match)
  - Real balance calculation
  - Action buttons (close, print, view, edit, delete)
- Create shift modal
- Close shift modal
- Responsive design (desktop table + mobile cards)

### 3.2 New `shifts/show.blade.php`

Features:
- Shift header with status
- Stats cards (starting cash, expected, counted, difference)
- Sales list for this shift
- Expenses list for this shift
- Stock moves for this shift
- Cash transfers for this shift
- Print closing receipt button

### 3.3 New `shifts/movements.blade.php` (Enhancement)

Enhance existing movements page with:
- Better layout
- Color-coded entries
- Summary cards
- Print button

## Phase 4: Shift Expenses & Cash Transfers

### 4.1 ShiftExpense Model

```php
class ShiftExpense extends Model
{
    protected $fillable = ['shift_id', 'user_id', 'location_id', 'title', 'amount', 'expense_date', 'status', 'approved_by', 'notes', 'receipt_image'];
    protected $casts = ['expense_date' => 'datetime', 'amount' => 'decimal:2'];

    public function shift(): BelongsTo { return $this->belongsTo(Shift::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }
}
```

### 4.2 CashTransfer Model

```php
class CashTransfer extends Model
{
    protected $fillable = ['from_shift_id', 'to_shift_id', 'location_id', 'from_user_id', 'to_user_id', 'type', 'amount', 'status', 'approved_by', 'notes'];
    protected $casts = ['amount' => 'decimal:2'];

    public function fromShift(): BelongsTo { return $this->belongsTo(Shift::class, 'from_shift_id'); }
    public function toShift(): BelongsTo { return $this->belongsTo(Shift::class, 'to_shift_id'); }
}
```

### 4.3 ShiftExpenseController

Methods:
- `index()`: List shift expenses
- `create()`: Create form
- `store()`: Create expense
- `approve()`: Approve expense
- `reject()`: Reject expense

### 4.4 CashTransferController

Methods:
- `index()`: List cash transfers
- `create()`: Create form
- `store()`: Create transfer
- `approve()`: Approve transfer
- `reject()`: Reject transfer