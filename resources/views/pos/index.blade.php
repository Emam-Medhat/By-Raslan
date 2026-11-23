@extends('layouts.app')

@section('title', 'الكاشير')
@section('page-title', 'الكاشير')

@section('content')
<div x-data="pos(@js($openShift))" x-cloak class="space-y-4">

    {{-- No open shift --}}
    <template x-if="! shift">
        <div class="max-w-md mx-auto mt-10 bg-white rounded-2xl border border-slate-200 shadow-sm p-8">
            <div class="text-center mb-6">
                <div class="w-14 h-14 rounded-2xl bg-brand-600/10 text-brand-700 grid place-items-center mx-auto text-2xl">💳</div>
                <h2 class="font-extrabold text-lg mt-3">افتح وردية للبدء</h2>
                <p class="text-sm text-slate-500 mt-1">أدخل رصيد بداية الوردية</p>
            </div>
            <form method="POST" action="{{ route('pos.open') }}" class="space-y-3">
                @csrf
                <select name="location_id" class="w-full rounded-lg border border-slate-300 px-3 py-2.5">
                    @foreach ($locations as $location)
                        <option value="{{ $location->id }}">{{ $location->name }}</option>
                    @endforeach
                </select>
                <input name="opening_cash" type="number" step="0.01" min="0" placeholder="رصيد البداية (ج.م)" class="w-full rounded-lg border border-slate-300 px-3 py-2.5">
                <button class="w-full bg-brand-600 hover:bg-brand-700 text-white rounded-lg py-2.5 font-bold">فتح الوردية</button>
            </form>
        </div>
    </template>

    {{-- Open shift: POS terminal --}}
    <template x-if="shift">
        <div>
            {{-- Shift bar --}}
            <div class="flex flex-wrap items-center justify-between gap-3 bg-white rounded-xl border border-slate-200 px-4 py-2.5">
                <div class="flex items-center gap-4 text-sm">
                    <span class="inline-flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-emerald-500"></span> وردية مفتوحة</span>
                    <span class="text-slate-500">الفرع: <b class="text-slate-800" x-text="shift.location_id"></b></span>
                    <span class="text-slate-500">رصيد البداية: <b class="text-slate-800" x-text="shift.opening_cash"></b></span>
                </div>
                <div class="flex items-center gap-2">
                    <input x-model.number="counted" type="number" step="0.01" min="0" placeholder="النقد المعدود" class="w-32 rounded-lg border border-slate-300 px-2 py-1 text-sm">
                    <form method="POST" action="{{ route('pos.close') }}">
                        @csrf
                        <input type="hidden" name="shift_id" :value="shift.id">
                        <input type="hidden" name="counted" :value="counted">
                        <button class="bg-rose-600 hover:bg-rose-700 text-white rounded-lg px-3 py-1.5 text-sm font-bold">قفل الوردية</button>
                    </form>
                </div>
            </div>

            <div class="grid gap-4 lg:grid-cols-12 mt-4">
                {{-- Left: search + cart --}}
                <div class="lg:col-span-8 space-y-4">
                    {{-- Scan / search --}}
                    <div class="bg-white rounded-xl border border-slate-200 p-3 relative">
                        <div class="flex gap-2">
                            <div class="relative flex-1">
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400">🔎</span>
                                <input x-model="code"
                                       @input.debounce.250ms="doSearch()"
                                       @keydown.enter.prevent="addLine()"
                                       autofocus
                                       placeholder="امسح QR علبة أو باركود صنف، أو ابحث بالاسم ثم Enter"
                                       class="w-full rounded-lg border border-slate-300 pr-10 pl-3 py-2.5">
                            </div>
                            <button @click="addLine()" :disabled="busy"
                                    class="bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white rounded-lg px-5 py-2.5 font-bold">إضافة</button>
                        </div>

                        {{-- Live search results --}}
                        <div x-show="searchResults.length" @click.outside="searchResults = []"
                             class="absolute z-20 mt-1 w-full bg-white border border-slate-200 rounded-lg shadow-lg max-h-72 overflow-y-auto">
                            <template x-for="r in searchResults" :key="r.item_id">
                                <button @click="addProduct(r)" class="w-full flex items-center justify-between px-3 py-2 hover:bg-slate-50 text-sm">
                                    <span class="font-medium" x-text="r.name"></span>
                                    <span class="text-slate-500" x-text="r.price + ' ج'"></span>
                                </button>
                            </template>
                        </div>
                        <div x-show="error" x-text="error" class="mt-2 text-sm text-rose-600"></div>
                    </div>

                    {{-- Cart --}}
                    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                        <div class="flex items-center justify-between px-4 py-2.5 border-b border-slate-100">
                            <h3 class="font-bold text-sm">الفاتورة <span class="text-slate-400">(<span x-text="cart.length"></span> صنف)</span></h3>
                            <button x-show="cart.length" @click="cart = []" class="text-xs text-rose-600">تفريغ</button>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-slate-50 text-slate-500 text-xs">
                                    <tr>
                                        <th class="text-right px-4 py-2 font-semibold">#</th>
                                        <th class="text-right px-4 py-2 font-semibold">الصنف</th>
                                        <th class="text-right px-4 py-2 font-semibold">الكمية</th>
                                        <th class="text-right px-4 py-2 font-semibold">السعر</th>
                                        <th class="text-right px-4 py-2 font-semibold">الإجمالي</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <template x-for="(l, idx) in cart" :key="idx">
                                        <tr class="hover:bg-slate-50">
                                            <td class="px-4 py-2 text-slate-400" x-text="idx+1"></td>
                                            <td class="px-4 py-2 font-medium" x-text="l.item_name"></td>
                                            <td class="px-4 py-2">
                                                <template x-if="l.box_sn">
                                                    <span class="text-slate-500" x-text="'علبة #' + l.box_sn"></span>
                                                </template>
                                                <template x-if="!l.box_sn">
                                                    <div class="inline-flex items-center gap-1">
                                                        <button @click="bump(idx, -1)" class="w-6 h-6 rounded border border-slate-300">−</button>
                                                        <span class="w-8 text-center" x-text="l.qty"></span>
                                                        <button @click="bump(idx, 1)" class="w-6 h-6 rounded border border-slate-300">+</button>
                                                    </div>
                                                </template>
                                            </td>
                                            <td class="px-4 py-2 text-slate-500" x-text="l.unit_price"></td>
                                            <td class="px-4 py-2 font-semibold" x-text="(l.qty * l.unit_price).toFixed(2)"></td>
                                            <td class="px-4 py-2"><button @click="cart.splice(idx,1)" class="text-rose-500">✕</button></td>
                                        </tr>
                                    </template>
                                    <tr x-show="cart.length === 0">
                                        <td colspan="6" class="px-4 py-12 text-center text-slate-400">
                                            <div class="text-3xl mb-2">🛒</div>
                                            امسح أو ابحث لإضافة أصناف
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Right: payment --}}
                <div class="lg:col-span-4">
                    <div class="bg-white rounded-xl border border-slate-200 p-5 lg:sticky lg:top-20 space-y-4">
                        <div class="space-y-1.5 text-sm">
                            <div class="flex justify-between text-slate-600"><span>الإجمالي الفرعي</span><span x-text="subtotal.toFixed(2) + ' ج'"></span></div>
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-slate-600">خصم</span>
                                <input x-model.number="discount" type="number" step="0.01" min="0" class="w-24 rounded border border-slate-300 px-2 py-1 text-left">
                            </div>
                            <div class="flex justify-between font-extrabold text-lg border-t pt-2 mt-1"><span>الإجمالي</span><span x-text="total.toFixed(2) + ' ج'"></span></div>
                        </div>

                        <div class="space-y-2">
                            <div class="flex items-center justify-between gap-2">
                                <label class="text-sm text-slate-600">💵 نقدي</label>
                                <input x-model.number="cash" type="number" step="0.01" min="0" class="w-28 rounded-lg border border-slate-300 px-2 py-1.5 text-left">
                            </div>
                            <div class="flex items-center justify-between gap-2">
                                <label class="text-sm text-slate-600">💳 فيزا</label>
                                <input x-model.number="card" type="number" step="0.01" min="0" class="w-28 rounded-lg border border-slate-300 px-2 py-1.5 text-left">
                            </div>
                            <div class="flex justify-between text-emerald-700 font-bold"><span>الباقي للعميل</span><span x-text="change.toFixed(2) + ' ج'"></span></div>
                        </div>

                        <div x-show="error" x-text="error" class="text-sm text-rose-600"></div>

                        <button @click="checkout()" :disabled="busy || cart.length === 0"
                                class="w-full bg-brand-600 hover:bg-brand-700 disabled:opacity-50 text-white rounded-xl py-3.5 text-lg font-extrabold shadow-sm">
                            <span x-show="!busy">إتمام البيع</span>
                            <span x-show="busy" x-cloak>جارٍ الحفظ…</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>

@push('scripts')
<script>
    const csrf = {{ Illuminate\Support\Js::from(csrf_token()) }};
    function pos(shift) {
        return {
            shift,
            code: '',
            cart: [],
            discount: 0,
            cash: 0,
            card: 0,
            counted: 0,
            busy: false,
            error: '',
            searchResults: [],
            get subtotal() { return this.cart.reduce((s, l) => s + l.qty * l.unit_price, 0); },
            get total() { return Math.max(this.subtotal - (this.discount || 0), 0); },
            get change() { return (this.cash || 0) + (this.card || 0) - this.total; },
            bump(idx, delta) {
                const l = this.cart[idx];
                const next = Math.max(1, (Number(l.qty) || 1) + delta);
                l.qty = next;
            },
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
                this.cart.push({ item_id: p.item_id, item_name: p.name, unit: p.unit, unit_price: p.price, qty: 1, box_sn: null });
                this.code = '';
                this.searchResults = [];
            },
            async addLine() {
                if (!this.code.trim()) return;
                // If there are search results and the code matches a name, add first result.
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
                    if (l.box_sn && this.cart.some(x => x.box_sn === l.box_sn)) { this.error = 'العلبة مضافة بالفعل'; return; }
                    this.cart.push({ item_id: l.item_id, item_name: l.item_name, unit: l.unit, unit_price: l.unit_price, qty: l.qty, box_sn: l.box_sn });
                    this.code = '';
                    this.searchResults = [];
                } catch (e) { this.error = 'تعذر الاتصال'; }
                finally { this.busy = false; }
            },
            async checkout() {
                if (this.busy) return;
                this.busy = true; this.error = '';
                const lines = this.cart.map(l => l.box_sn ? { box_sn: l.box_sn } : { item_id: l.item_id, qty: l.qty });
                const key = (crypto.randomUUID) ? crypto.randomUUID() : String(Date.now());
                try {
                    const res = await fetch('{{ route('pos.sale') }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                        body: JSON.stringify({ shift_id: this.shift.id, lines, discount: this.discount || 0, cash: this.cash || 0, card: this.card || 0, idempotency_key: key }),
                    });
                    const data = await res.json();
                    if (!data.ok) { this.error = data.error; return; }
                    this.cart = []; this.discount = 0; this.cash = 0; this.card = 0;
                    alert('تم بيع فاتورة #' + data.sale.no + '\\nالإجمالي: ' + data.sale.total + ' ج — الباقي: ' + data.sale.change + ' ج');
                } catch (e) { this.error = 'تعذر الاتصال'; }
                finally { this.busy = false; }
            },
        };
    }
</script>
@endpush
@endsection