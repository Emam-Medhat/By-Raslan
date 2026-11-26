@extends('layouts.app')

@section('title', 'الكاشير')
@section('page-title', 'نقطة البيع')

@section('content')
<div x-data="pos(@js($openShift))" x-cloak class="min-h-[calc(100vh-11rem)]">

    {{-- ============ NO OPEN SHIFT ============ --}}
    <template x-if="!shift">
        <div class="mx-auto max-w-2xl py-6">
            <div class="mb-6 text-center">
                <div class="mx-auto mb-4 flex h-20 w-20 items-center justify-center rounded-3xl bg-gradient-to-br from-primary-800 to-accent-500 text-white shadow-xl shadow-primary-800/20">
                    <x-icon name="monitor" size="28" />
                </div>
                <h1 class="text-2xl font-extrabold text-primary-800">نقطة البيع</h1>
                <p class="mt-1 text-sm text-slate-500">افتح وردية جديدة لبدء استقبال عمليات البيع</p>
            </div>

            <div class="ds-card p-6 sm:p-8">
                <form method="POST" action="{{ route('pos.open') }}" class="space-y-5">
                    @csrf
                    <div>
                        <label class="ds-label">موقع البيع</label>
                        <select name="location_id" class="ds-input h-12 text-[15px]">
                            @foreach ($locations as $location)
                                <option value="{{ $location->id }}">{{ $location->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="ds-label">رصيد بداية الوردية</label>
                        <div class="relative">
                            <input name="opening_cash" id="opening_cash" type="number" step="0.01" min="0" value="{{ $suggestedOpening[$locations->first()?->id] ?? 0 }}" class="ds-input h-12 pr-4 pl-14 text-[15px] font-semibold" placeholder="0.00">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">ج.م</span>
                        </div>
                        <p id="carryover_hint" class="mt-1 text-xs text-slate-500"></p>
                    </div>
                    <button class="ds-btn ds-btn-primary w-full h-12 text-base">
                        <x-icon name="unlock-keyhole" size="16" class="ml-1" /> فتح الوردية
                    </button>
                </form>
            </div>
        </div>
    </template>

    {{-- ============ OPEN SHIFT: POS TERMINAL ============ --}}
    <template x-if="shift">
        <div class="flex flex-col gap-4">

            {{-- Top bar --}}
            <div class="ds-card flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-accent-100 text-accent-600">
                        <x-icon name="store" size="20" />
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="font-extrabold text-primary-800 truncate" x-text="shift.location_name || 'نقطة البيع'"></span>
                            <span class="ds-badge ds-badge-success"><span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span> وردية مفتوحة</span>
                        </div>
                        <div class="text-xs text-slate-400 mt-0.5">
                            <span x-text="shift.cashier_name || ''"></span>
                            <span class="mx-1">·</span>
                            <span>منذ</span>
                            <span x-text="formatTime(shift.opened_at)"></span>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <div class="hidden sm:flex items-center gap-2 rounded-xl bg-slate-50 border border-slate-200 px-3 py-2 text-xs text-slate-500">
                        <x-icon name="clock" size="16" />
                        <span x-text="now"></span>
                    </div>
                    <button @click="closeModal = true" class="ds-btn ds-btn-danger ds-btn-sm">
                        <x-icon name="lock" size="16" class="ml-1" /> إغلاق الوردية
                    </button>
                </div>
            </div>

            <div class="grid gap-4 xl:grid-cols-[minmax(0,1.65fr)_minmax(360px,1fr)]">

                {{-- LEFT: Scan + Cart --}}
                <div class="space-y-4">
                    {{-- Scan / Search --}}
                    <div class="ds-card p-4 sm:p-5">
                        <label class="ds-label mb-2">مسح باركود / QR العلبة / بحث بالاسم</label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 right-4 flex items-center text-slate-400">
                                <x-icon name="barcode" size="20" />
                            </span>
                            <input x-model="code"
                                   @input.debounce.250ms="doSearch()"
                                   @keydown.enter.prevent="addLine()"
                                   x-ref="scanInput"
                                   autofocus
                                   placeholder="امسح الكود أو اكتب اسم الصنف..."
                                   class="ds-input h-14 pr-12 pl-4 text-base font-semibold"
                                   dir="auto">
                            <div x-show="busy" class="absolute left-4 top-1/2 -translate-y-1/2">
                                <x-icon name="spin" size="16" class="text-accent-500" />
                            </div>
                        </div>

                        <div x-show="error" x-transition class="mt-3 flex items-start gap-2 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                            <x-icon name="circle-alert" size="16" class="mt-0.5" />
                            <span class="flex-1" x-text="error"></span>
                            <button @click="error=''" class="text-red-400 hover:text-red-600"><x-icon name="x" size="16" /></button>
                        </div>

                        {{-- Search results --}}
                        <div x-show="searchResults.length" x-transition class="mt-3 overflow-hidden rounded-xl border border-slate-200">
                            <div class="bg-slate-50 px-3 py-2 text-[11px] font-bold text-slate-400 uppercase tracking-wider">نتائج البحث</div>
                            <div class="max-h-56 overflow-y-auto divide-y divide-slate-100">
                                <template x-for="r in searchResults" :key="r.item_id">
                                    <button @click="addProduct(r)" class="flex w-full items-center justify-between gap-3 px-4 py-3 text-right hover:bg-accent-50/50 transition-colors">
                                        <div class="min-w-0">
                                            <div class="font-semibold text-primary-800 truncate" x-text="r.name"></div>
                                            <div class="text-[11px] text-slate-400" x-text="r.unit"></div>
                                        </div>
                                        <div class="shrink-0 flex items-center gap-2">
                                            <span class="font-extrabold text-accent-700 num" x-text="Number(r.price).toFixed(2)"></span>
                                            <span class="text-[11px] text-slate-400">ج.م</span>
                                            <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-accent-100 text-accent-600"><x-icon name="plus" size="14" /></span>
                                        </div>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>

                    {{-- Cart --}}
                    <div class="ds-card overflow-hidden">
                        <div class="flex items-center justify-between border-b border-slate-100 px-4 sm:px-5 py-4">
                            <div class="flex items-center gap-2">
                                <h2 class="font-extrabold text-primary-800">سلة البيع</h2>
                                <span class="ds-badge ds-badge-primary" x-text="cart.length + ' صنف'"></span>
                            </div>
                            <button @click="clearCart()" x-show="cart.length" class="text-xs font-semibold text-rose-500 hover:text-rose-700 transition-colors">
                                <x-icon name="trash-2" size="16" class="ml-1" /> تفريغ
                            </button>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="ds-table min-w-[560px]">
                                <thead>
                                    <tr>
                                        <th>الصنف</th>
                                        <th class="!text-center">الكمية</th>
                                        <th>السعر</th>
                                        <th>الإجمالي</th>
                                        <th class="!w-12"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="(l, i) in cart" :key="i">
                                        <tr>
                                            <td>
                                                <div class="font-semibold text-primary-800" x-text="l.item_name"></div>
                                                <div class="flex items-center gap-2 mt-0.5">
                                                    <span x-show="l.box_sn" class="ds-badge ds-badge-neutral font-mono text-[10px]" x-text="'#' + l.box_sn"></span>
                                                    <span class="text-[11px] text-slate-400" x-text="l.unit"></span>
                                                </div>
                                            </td>
                                            <td>
                                                <template x-if="l.box_sn">
                                                    <div class="text-center font-semibold text-slate-600 num" x-text="fmtQty(l.qty)"></div>
                                                </template>
                                                <template x-if="!l.box_sn">
                                                    <div class="inline-flex items-center gap-1 rounded-xl border border-slate-200 bg-slate-50 p-1">
                                                        <button @click="bump(i, 1)" class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-white border border-slate-200 text-slate-600 hover:bg-accent-50 hover:text-accent-700 hover:border-accent-200 transition"><x-icon name="plus" size="14" /></button>
                                                        <input type="number" min="0.001" step="any" x-model.number="l.qty" class="w-16 bg-transparent text-center font-bold text-primary-800 outline-none num">
                                                        <button @click="bump(i, -1)" class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-white border border-slate-200 text-slate-600 hover:bg-rose-50 hover:text-rose-600 hover:border-rose-200 transition"><x-icon name="minus" size="14" /></button>
                                                    </div>
                                                </template>
                                            </td>
                                            <td class="num text-slate-600" x-text="Number(l.unit_price).toFixed(2)"></td>
                                            <td class="num font-extrabold text-primary-800" x-text="(l.qty * l.unit_price).toFixed(2)"></td>
                                            <td>
                                                <button @click="removeLine(i)" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 hover:bg-rose-50 hover:text-rose-600 transition" title="حذف">
                                                    <x-icon name="x" size="16" />
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                    <tr x-show="cart.length === 0">
                                        <td colspan="5" class="py-14">
                                            <div class="flex flex-col items-center gap-3 text-slate-400">
                                                <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-100">
                                                    <x-icon name="basket-shopping" size="24" class="text-slate-300" />
                                                </div>
                                                <div class="font-semibold text-slate-500">السلة فارغة</div>
                                                <div class="text-xs">امسح باركود العلبة أو ابحث عن صنف لإضافته</div>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- RIGHT: Payment --}}
                <div class="space-y-4 xl:sticky xl:top-24 self-start">
                    <div class="ds-card overflow-hidden">
                        <div class="bg-gradient-to-l from-primary-800 to-primary-700 px-5 py-4 text-white">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-semibold text-white/70">الإجمالي المستحق</span>
                                <span class="ds-badge bg-white/15 text-white border-0" x-text="cart.length + ' صنف'"></span>
                            </div>
                            <div class="mt-1 flex items-baseline gap-2">
                                <span class="text-4xl font-extrabold num tracking-tight" x-text="total.toFixed(2)"></span>
                                <span class="text-sm font-semibold text-white/60">ج.م</span>
                            </div>
                        </div>

                        <div class="p-5 space-y-4">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-slate-500">المجموع الفرعي</span>
                                <span class="font-bold text-primary-800 num" x-text="subtotal.toFixed(2) + ' ج.م'"></span>
                            </div>

                            <div class="grid gap-3 sm:grid-cols-2">
                                <div><label class="ds-label">اسم العميل (اختياري)</label><input x-model="customerName" class="ds-input h-11" placeholder="اسم العميل"></div>
                                <div><label class="ds-label">هاتف العميل</label><input x-model="customerPhone" class="ds-input h-11" dir="ltr" placeholder="01..."></div>
                            </div>

                            <div class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <label class="ds-label">الخصم</label>
                                    <div class="relative">
                                        <input x-model.number="discount" type="number" step="0.01" min="0" class="ds-input h-11 pl-14 font-semibold num" placeholder="0.00">
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">ج.م</span>
                                    </div>
                                </div>
                                <div>
                                    <label class="ds-label">خدمة التوصيل</label>
                                    <div class="relative">
                                        <input x-model.number="deliveryFee" type="number" step="0.01" min="0" class="ds-input h-11 pl-14 font-semibold num" placeholder="0.00">
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">ج.م</span>
                                    </div>
                                </div>
                            </div>
                            <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" x-model="delivery" class="rounded"> طلب توصيل</label>
                            <div x-show="delivery">
                                <label class="ds-label">الطيار</label>
                                <select x-model="driverId" class="ds-input h-11">
                                    <option value="">— اختر الطيار —</option>
                                    @foreach(($drivers ?? []) as $d)
                                        <option value="{{ $d->id }}">{{ $d->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="ds-label">العميل (اختياري — بحث برقم الموبايل)</label>
                                <div class="flex gap-2">
                                    <input x-model="customerPhone" type="text" class="ds-input h-11 flex-1" placeholder="01xxxxxxxxx" dir="ltr">
                                    <button type="button" @click="findCustomer()" class="ds-btn ds-btn-secondary px-3 h-11">بحث</button>
                                </div>
                                <div x-show="customerName" class="mt-1 text-xs font-semibold text-emerald-600" x-text="'العميل: ' + customerName"></div>
                            </div>

                            <div>
                                <label class="ds-label">رسوم التوصيل (0 = بيع مباشر بدون توصيل)</label>
                                <div class="relative">
                                    <input x-model.number="deliveryFee" type="number" step="0.01" min="0" class="ds-input h-11 pl-14 font-semibold num" placeholder="0.00">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">ج.م</span>
                                </div>
                            </div>

                            <div>
                                <label class="ds-label">الطيار (إجباري لو فيه رسوم توصيل)</label>
                                <select x-model="driverId" class="ds-input h-11">
                                    <option value="">— بدون طيار —</option>
                                    @foreach ($drivers as $driver)
                                        <option value="{{ $driver->id }}">{{ $driver->name }}</option>
                                    @endforeach
                                </select>
                                @if ($drivers->isEmpty())
                                    <p class="mt-1 text-[11px] text-amber-600">لا يوجد طيارين مربوطين بالفرع ده — أضف طيار من صفحة الطيارين.</p>
                                @endif
                            </div>

                            <div class="border-t border-slate-100 pt-4 space-y-3">
                                <div>
                                    <label class="ds-label">طريقة التحصيل</label>
                                    <select x-model="payMethod" class="ds-input h-11">
                                        <option value="cash">Cash</option>
                                        <option value="card">Visa</option>
                                        <option value="instapay">InstaPay</option>
                                        <option value="wallet">Wallet</option>
                                        <option value="mixed">مختلط</option>
                                    </select>
                                </div>
                                <div x-show="payMethod==='cash' || payMethod==='mixed'">
                                    <label class="ds-label">نقدي</label>
                                    <div class="relative">
                                        <input x-model.number="cash" type="number" step="0.01" min="0" class="ds-input h-11 pl-14 font-semibold num" :placeholder="total.toFixed(2)">
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">ج.م</span>
                                    </div>
                                </div>
                                <div class="flex flex-wrap gap-2" x-show="payMethod==='cash' || payMethod==='mixed'">
                                    <template x-for="v in quickCash()" :key="'c'+v">
                                        <button @click="cash = v" type="button" class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-bold text-slate-600 hover:border-accent-300 hover:bg-accent-50 hover:text-accent-700 transition">
                                            <span x-text="v === Math.ceil(total) && total > 0 ? 'مظبوط' : v"></span>
                                        </button>
                                    </template>
                                </div>
                                <div x-show="payMethod!=='cash'">
                                    <label class="ds-label">إلكتروني / بطاقة / محفظة</label>
                                    <div class="relative">
                                        <input x-model.number="card" type="number" step="0.01" min="0" class="ds-input h-11 pl-14 font-semibold num" placeholder="0.00">
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">ج.م</span>
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-xl bg-slate-50 border border-slate-200 p-3 space-y-2 text-sm">
                                <div class="flex items-center justify-between">
                                    <span class="text-slate-500">المدفوع</span>
                                    <span class="font-bold num" :class="paid >= total && total > 0 ? 'text-emerald-600' : 'text-slate-700'" x-text="paid.toFixed(2) + ' ج.م'"></span>
                                </div>
                                <div class="flex items-center justify-between" x-show="change > 0">
                                    <span class="text-slate-500">الباقي للعميل</span>
                                    <span class="font-extrabold text-accent-600 num" x-text="change.toFixed(2) + ' ج.م'"></span>
                                </div>
                                <div class="flex items-center justify-between text-rose-600" x-show="total > 0 && paid < total">
                                    <span>المتبقي</span>
                                    <span class="font-extrabold num" x-text="(total - paid).toFixed(2) + ' ج.م'"></span>
                                </div>
                            </div>

                            <div class="flex gap-2">
                                <button @click="prepare()"
                                        :disabled="busy || cart.length === 0"
                                        class="ds-btn ds-btn-secondary flex-1 h-14 text-base">
                                    <span class="flex items-center gap-2 justify-center">
                                        <x-icon name="receipt-text" size="16" />
                                        <span>تجهيز</span>
                                    </span>
                                </button>
                                <button @click="checkout()"
                                        :disabled="busy || cart.length === 0"
                                        class="ds-btn ds-btn-accent flex-1 h-14 text-base shadow-lg shadow-accent-500/20 disabled:shadow-none">
                                    <span x-show="!busy" class="flex items-center gap-2 justify-center">
                                        <x-icon name="circle-check" size="16" />
                                        <span>إتمام البيع</span>
                                        <span class="num" x-text="total.toFixed(2)"></span>
                                        <span>ج.م</span>
                                    </span>
                                    <span x-show="busy" class="flex items-center gap-2"><x-icon name="spin" size="16" /> جارٍ الحفظ...</span>
                                </button>
                            </div>
                            <p class="text-center text-[11px] text-slate-400">تجهيز بيعرض الفاتورة للمراجعة قبل الدفع · Enter لإضافة الكود · سيتم طباعة الإيصال بعد الحفظ</p>
                        </div>
                    </div>

                    <div x-show="rec" x-transition class="ds-card p-4 border-accent-200 bg-accent-50/30">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <div class="text-[11px] font-bold text-accent-600 uppercase tracking-wider">آخر فاتورة</div>
                                <div class="font-extrabold text-primary-800">#<span x-text="rec?.sale_no"></span></div>
                            </div>
                            <div class="text-left">
                                <div class="font-extrabold text-accent-700 num" x-text="Number(rec?.total||0).toFixed(2) + ' ج.م'"></div>
                                <button @click="window.print()" class="text-xs font-semibold text-slate-500 hover:text-primary-800"><x-icon name="printer" size="16" class="ml-1" />طباعة</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Receipt (print only) --}}
            <div class="receipt-print hidden" x-show="rec" x-cloak>
                <div style="text-align:center; margin-bottom:6px;">
                    <div style="font-size:14pt; font-weight:800;">BY RASLAN</div>
                    <div x-text="shift.location_name"></div>
                    <div>فاتورة #<span x-text="rec?.sale_no"></span></div>
                    <div style="font-size:8pt;" x-text="rec?.at"></div>
                </div>
                <hr style="border:0; border-top:1px dashed #000; margin:4px 0;">
                <template x-for="(l, i) in (rec?.lines||[])" :key="'r'+i">
                    <div style="display:flex; justify-content:space-between; gap:6px; margin:2px 0;">
                        <div style="flex:1; min-width:0;">
                            <div x-text="l.item_name"></div>
                            <div style="font-size:8pt; color:#333;">
                                <span x-text="fmtQty(l.qty)"></span> × <span x-text="Number(l.unit_price).toFixed(2)"></span>
                                <span x-show="l.box_sn" x-text="' #' + l.box_sn"></span>
                            </div>
                        </div>
                        <div style="white-space:nowrap; font-weight:700;" x-text="(l.qty*l.unit_price).toFixed(2)"></div>
                    </div>
                </template>
                <hr style="border:0; border-top:1px dashed #000; margin:4px 0;">
                <div style="display:flex; justify-content:space-between;"><span>المجموع</span><span x-text="Number(rec?.subtotal||0).toFixed(2)"></span></div>
                <div x-show="(rec?.disc||0) > 0" style="display:flex; justify-content:space-between;"><span>خصم</span><span x-text="'-' + Number(rec?.disc||0).toFixed(2)"></span></div>
                <div x-show="(rec?.delivery_fee||0) > 0" style="display:flex; justify-content:space-between;"><span>توصيل</span><span x-text="Number(rec?.delivery_fee||0).toFixed(2)"></span></div>
                <div style="display:flex; justify-content:space-between; font-weight:800; font-size:11pt; margin:2px 0;"><span>الإجمالي</span><span x-text="Number(rec?.total||0).toFixed(2)"></span></div>
                <div style="display:flex; justify-content:space-between;"><span>طريقة التحصيل</span><span x-text="rec?.pay_method || ''"></span></div>
                <div x-show="(rec?.cash||0) > 0" style="display:flex; justify-content:space-between;"><span>نقدي</span><span x-text="Number(rec?.cash||0).toFixed(2)"></span></div>
                <div x-show="(rec?.card||0) > 0" style="display:flex; justify-content:space-between;"><span>إلكتروني</span><span x-text="Number(rec?.card||0).toFixed(2)"></span></div>
                <div x-show="(rec?.change||0) > 0" style="display:flex; justify-content:space-between;"><span>الباقي</span><span x-text="Number(rec?.change||0).toFixed(2)"></span></div>
                <hr style="border:0; border-top:1px dashed #000; margin:4px 0;">
                <div style="text-align:center; font-size:8pt;">شكرًا لزيارتك — <span x-text="shift.cashier_name"></span></div>
            </div>

            {{-- Prepare preview modal --}}
            <div x-show="previewModal" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center p-4" aria-modal="true">
                <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" @click="previewModal = false"></div>
                <div class="relative w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-2xl">
                    <div class="flex items-center justify-between px-5 py-4 bg-gradient-to-l from-primary-800 to-primary-700 text-white">
                        <h3 class="font-extrabold">معاينة الفاتورة — تجهيز</h3>
                        <button @click="previewModal = false" class="text-white/80 hover:text-white">✕</button>
                    </div>
                    <div class="p-5 space-y-3" x-show="preview">
                        <template x-for="(l, i) in (preview?.lines||[])" :key="'p'+i">
                            <div class="flex items-center justify-between gap-3 text-sm border-b border-slate-100 pb-2">
                                <div>
                                    <div class="font-semibold" x-text="l.item_name"></div>
                                    <div class="text-[11px] text-slate-400"><span x-text="fmtQty(l.qty)"></span> × <span x-text="Number(l.unit_price).toFixed(2)"></span><span x-show="l.box_sn" x-text="' #' + l.box_sn"></span></div>
                                </div>
                                <div class="font-extrabold num" x-text="Number(l.total).toFixed(2)"></div>
                            </div>
                        </template>
                        <div class="space-y-1 text-sm">
                            <div class="flex justify-between"><span class="text-slate-500">المجموع الفرعي</span><span class="font-bold num" x-text="Number(preview?.subtotal||0).toFixed(2) + ' ج.م'"></span></div>
                            <div class="flex justify-between" x-show="(preview?.discount||0) > 0"><span class="text-slate-500">خصم</span><span class="font-bold num" x-text="'-' + Number(preview?.discount||0).toFixed(2) + ' ج.م'"></span></div>
                            <div class="flex justify-between" x-show="(preview?.delivery_fee||0) > 0"><span class="text-slate-500">رسوم توصيل</span><span class="font-bold num" x-text="Number(preview?.delivery_fee||0).toFixed(2) + ' ج.م'"></span></div>
                            <div class="flex justify-between text-base"><span class="font-bold">الإجمالي</span><span class="font-extrabold num" x-text="Number(preview?.total||0).toFixed(2) + ' ج.م'"></span></div>
                            <div class="flex justify-between" x-show="preview?.customer_name"><span class="text-slate-500">العميل</span><span class="font-semibold" x-text="preview?.customer_name"></span></div>
                            <div class="flex justify-between" x-show="preview?.driver_name"><span class="text-slate-500">الطيار</span><span class="font-semibold" x-text="preview?.driver_name"></span></div>
                        </div>
                        <div class="flex gap-2 pt-2">
                            <button @click="previewModal = false; checkout()" class="ds-btn ds-btn-accent flex-1 h-11">إتمام الدفع</button>
                            <button @click="previewModal = false" class="ds-btn ds-btn-secondary px-5 h-11">تعديل</button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Close shift modal --}}
            <div x-show="closeModal" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center p-4" aria-modal="true">
                <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" @click="closeModal = false"></div>
                <div class="relative w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl" @keydown.escape.window="closeModal = false">
                    <div class="flex items-center gap-3 bg-gradient-to-l from-amber-500 to-amber-600 px-5 py-4 text-white">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/20"><x-icon name="lock" size="16" /></div>
                        <div>
                            <h3 class="font-extrabold">إغلاق الوردية</h3>
                            <p class="text-xs text-white/80">تأكيد الرصيد الفعلي قبل الإغلاق</p>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('pos.close') }}" class="p-5 space-y-4">
                        @csrf
                        <input type="hidden" name="shift_id" :value="shift.id">
                        <div>
                            <label class="ds-label">النقد المعدود فعليًا</label>
                            <div class="relative">
                                <input name="counted" x-model.number="counted" type="number" step="0.01" min="0" required class="ds-input h-11 pl-14 font-semibold num">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">ج.م</span>
                            </div>
                        </div>
                        <div>
                            <label class="ds-label">ملاحظات <span class="text-slate-400 font-normal">(اختياري)</span></label>
                            <textarea name="notes" x-model="closeNotes" rows="2" class="ds-input" placeholder="أي ملاحظة على الوردية..."></textarea>
                        </div>
                        <div class="flex items-center gap-2 pt-2">
                            <button type="submit" class="ds-btn ds-btn-danger flex-1 h-11"><x-icon name="lock" size="16" class="ml-1" /> تأكيد الإغلاق</button>
                            <button type="button" @click="closeModal = false" class="ds-btn ds-btn-secondary px-5 h-11">إلغاء</button>
                        </div>
                        <p class="text-[11px] text-slate-400 text-center">سيتم تسجيل خروجك تلقائيًا بعد إغلاق الوردية.</p>
                    </form>
                </div>
            </div>
        </div>
    </template>
</div>

<style>
    @media print {
        body * { visibility: hidden !important; }
        .receipt-print, .receipt-print * { visibility: visible !important; }
        .receipt-print { display: block !important; position: absolute; inset: 0; width: 80mm; margin: 0 auto; padding: 4mm; font-size: 9pt; color: #000; background: #fff; }
    }
</style>

@push('scripts')
<script>
    const csrf = {{ Illuminate\Support\Js::from(csrf_token()) }};

    // Prefill the opening cash with the custody carried over from the
    // previous shift in the chosen branch (cumulative custody).
    (function () {
        var map = @js($suggestedOpening ?? []);
        var select = document.querySelector('select[name="location_id"]');
        var input = document.getElementById('opening_cash');
        var hint = document.getElementById('carryover_hint');
        if (!select || !input) return;
        function apply() {
            var v = Number(map[select.value] || 0);
            input.value = v;
            if (hint) {
                hint.textContent = v > 0
                    ? 'عهدة مرحّلة من الوردية اللي فاتت في الفرع ده: ' + v.toFixed(2) + ' ج — عدّلها لو المبلغ الفعلي مختلف.'
                    : '';
            }
        }
        select.addEventListener('change', apply);
        apply();
    })();

    function pos(shift) {
        return {
            shift,
            code: '',
            cart: [],
            discount: 0,
            deliveryFee: 0,
            delivery: false,
            driverId: '',
            customerName: '',
            customerPhone: '',
            payMethod: 'cash',
            cash: 0,
            card: 0,
            deliveryFee: 0,
            driverId: '',
            customerPhone: '',
            customerId: null,
            customerName: '',
            preview: null,
            previewModal: false,
            counted: 0,
            closeModal: false,
            closeNotes: '',
            busy: false,
            error: '',
            searchResults: [],
            rec: null,
            now: '',
            init() {
                this.tick();
                setInterval(() => this.tick(), 1000);
            },
            tick() {
                const d = new Date();
                this.now = d.toLocaleTimeString('ar-EG', { hour: '2-digit', minute: '2-digit' });
            },
            get subtotal() { return this.cart.reduce((s, l) => s + (Number(l.qty)||0) * (Number(l.unit_price)||0), 0); },
            get total() { return Math.max(this.subtotal - (Number(this.discount)||0) + (Number(this.deliveryFee)||0), 0); },
            get paid() { return (Number(this.cash)||0) + (Number(this.card)||0); },
            get change() { return Math.max(this.paid - this.total, 0); },
            fmtQty(q) { const n = Number(q); return Number.isInteger(n) ? String(n) : n.toFixed(3).replace(/\.?0+$/, ''); },
            formatTime(v) { if (!v) return '—'; try { return new Date(v).toLocaleTimeString('ar-EG', { hour:'2-digit', minute:'2-digit' }); } catch(e){ return v; } },
            quickCash() {
                const t = Math.ceil(this.total);
                const set = new Set();
                if (t > 0) set.add(t);
                [50,100,200,500].forEach(v => { if (v >= this.total) set.add(v); });
                return Array.from(set).slice(0,4);
            },
            clearCart() { if (confirm('تفريغ السلة بالكامل؟')) { this.cart = []; this.discount = 0; this.deliveryFee = 0; this.delivery = false; this.driverId=''; this.customerName=''; this.customerPhone=''; this.payMethod='cash'; this.cash = 0; this.card = 0; this.error = ''; } },
            bump(idx, delta) {
                const l = this.cart[idx];
                const next = Math.max(0.001, (Number(l.qty) || 1) + delta);
                l.qty = Number(next.toFixed(3));
            },
            removeLine(i) { this.cart.splice(i, 1); },
            async doSearch() {
                if (!this.shift || this.code.trim().length < 2) { this.searchResults = []; return; }
                try {
                    const res = await fetch('{{ route('pos.search') }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                        body: JSON.stringify({ location_id: this.shift.location_id, q: this.code.trim() }),
                    });
                    this.searchResults = await res.json();
                } catch (e) { this.searchResults = []; }
            },
            addProduct(p) {
                const existing = this.cart.find(x => !x.box_sn && x.item_id === p.item_id);
                if (existing) existing.qty = Number((Number(existing.qty) + 1).toFixed(3));
                else this.cart.push({ item_id: p.item_id, item_name: p.name, unit: p.unit, unit_price: Number(p.price), qty: 1, box_sn: null });
                this.code = '';
                this.searchResults = [];
                this.$nextTick(() => this.$refs.scanInput?.focus());
            },
            async addLine() {
                if (!this.code.trim()) return;
                if (this.searchResults.length) { this.addProduct(this.searchResults[0]); return; }
                this.busy = true; this.error = '';
                try {
                    const res = await fetch('{{ route('pos.lookup') }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                        body: JSON.stringify({ location_id: this.shift.location_id, code: this.code.trim() }),
                    });
                    const data = await res.json();
                    if (!data.ok) { this.error = data.error; return; }
                    const l = data.line;
                    if (l.box_sn && this.cart.some(x => Number(x.box_sn) === Number(l.box_sn))) { this.error = 'العلبة مضافة بالفعل'; return; }
                    this.cart.push({ item_id: l.item_id, item_name: l.item_name, unit: l.unit, unit_price: Number(l.unit_price), qty: Number(l.qty), box_sn: l.box_sn });
                    this.code = '';
                    this.searchResults = [];
                } catch (e) { this.error = 'تعذر الاتصال بالخادم'; }
                finally { this.busy = false; this.$nextTick(() => this.$refs.scanInput?.focus()); }
            },
            async findCustomer() {
                if (!this.customerPhone.trim()) { this.customerId = null; this.customerName = ''; return; }
                try {
                    const res = await fetch('{{ route('pos.customer') }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                        body: JSON.stringify({ phone: this.customerPhone.trim() }),
                    });
                    const data = await res.json();
                    if (data.ok) { this.customerId = data.customer.id; this.customerName = data.customer.name; }
                    else { this.customerId = null; this.customerName = ''; this.error = 'العميل مش متسجل — هيتباع بدون عميل'; }
                } catch (e) { this.error = 'تعذر الاتصال بالخادم'; }
            },
            async prepare() {
                if (this.busy || this.cart.length === 0) return;
                this.busy = true; this.error = '';
                const lines = this.cart.map(l => l.box_sn ? { box_sn: l.box_sn } : { item_id: l.item_id, qty: Number(l.qty) });
                try {
                    const res = await fetch('{{ route('pos.prepare') }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                        body: JSON.stringify({
                            shift_id: this.shift.id, lines,
                            discount: Number(this.discount)||0,
                            delivery_fee: Number(this.deliveryFee)||0,
                            customer_id: this.customerId,
                            driver_id: this.driverId ? Number(this.driverId) : null,
                        }),
                    });
                    const data = await res.json();
                    if (!data.ok) { this.error = data.error || 'تعذر التجهيز'; return; }
                    this.preview = data.preview;
                    this.previewModal = true;
                } catch (e) { this.error = 'تعذر الاتصال بالخادم'; }
                finally { this.busy = false; }
            },
            async checkout() {
                if (this.busy || this.cart.length === 0) return;
                if (this.paid < this.total) { this.error = 'المدفوع أقل من الإجمالي'; return; }
                this.busy = true; this.error = '';
                const lines = this.cart.map(l => l.box_sn ? { box_sn: l.box_sn } : { item_id: l.item_id, qty: Number(l.qty) });
                const key = (crypto.randomUUID) ? crypto.randomUUID() : (String(Date.now()) + Math.random().toString(16).slice(2));
                const payload = {
                    discount: Number(this.discount)||0,
                    cash: Number(this.cash)||0,
                    card: Number(this.card)||0,
                    delivery_fee: Number(this.deliveryFee)||0,
                    pay_method: this.payMethod,
                    customer_name: this.customerName || null,
                    customer_phone: this.customerPhone || null,
                    driver_id: this.delivery ? (this.driverId || null) : null,
                    delivery: !!this.delivery,
                };
                try {
                    const res = await fetch('{{ route('pos.sale') }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                        body: JSON.stringify({ shift_id: this.shift.id, lines, ...payload, idempotency_key: key }),
                    });
                    const data = await res.json();
                    if (!data.ok) { this.error = data.error || 'تعذر إتمام البيع'; return; }
                    this.rec = {
                        sale_no: data.sale.no,
                        total: data.sale.total,
                        change: data.sale.change,
                        subtotal: this.subtotal,
                        disc: payload.discount,
                        delivery_fee: payload.delivery_fee,
                        pay_method: payload.pay_method,
                        cash: payload.cash,
                        card: payload.card,
                        at: new Date().toLocaleString('ar-EG'),
                        lines: this.cart.map(l => ({...l}))
                    };
                    this.cart = []; this.discount = 0; this.deliveryFee = 0; this.delivery=false; this.driverId=''; this.customerName=''; this.customerPhone=''; this.payMethod='cash'; this.cash = 0; this.card = 0; this.code = ''; this.searchResults = [];
                    this.$nextTick(() => { window.print(); this.$refs.scanInput?.focus(); });
                } catch (e) { this.error = 'تعذر الاتصال بالخادم'; }
                finally { this.busy = false; }
            },
        };
    }
</script>
@endpush
@endsection
