@extends('layouts.guest')

@section('title', 'BY RASLAN — اطلب أونلاين')

@section('content')
<div class="w-full max-w-3xl" x-data="shop(@js($branches))" x-cloak>
    <div class="bg-white rounded-2xl shadow-lg p-6">
        <h1 class="text-2xl font-extrabold text-brand-700 mb-4">BY RASLAN — اطلب أونلاين</h1>

        <div class="grid gap-4 md:grid-cols-3">
            <div class="md:col-span-2 space-y-3">
                <select x-model="branch" @change="loadMenu()" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    <option value="">— اختر الفرع —</option>
                    <template x-for="b in branches" :key="b.id">
                        <option :value="b.id" x-text="b.name + ' (توصيل ' + b.fee + ' ج)'"></option>
                    </template>
                </select>

                <div x-show="menu.length" class="bg-white rounded-lg border border-slate-200 divide-y divide-slate-100">
                    <template x-for="item in menu" :key="item.item_id">
                        <div class="flex items-center justify-between p-3">
                            <div>
                                <div class="font-semibold" x-text="item.name"></div>
                                <div class="text-xs text-slate-500" x-text="item.unit"></div>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-sm text-slate-500" x-text="item.price + ' ج'"></span>
                                <button @click="add(item)" class="w-7 h-7 rounded-full bg-brand-600 text-white font-bold">+</button>
                            </div>
                        </div>
                    </template>
                </div>
                <div x-show="!menu.length" class="text-center text-slate-400 py-6">اختر الفرع لعرض الأصناف</div>
            </div>

            <div class="bg-slate-50 rounded-lg p-4">
                <h3 class="font-bold mb-2">السلة</h3>
                <template x-for="(l, i) in cart" :key="i">
                    <div class="flex justify-between text-sm py-1">
                        <span x-text="l.name + ' ×' + l.qty"></span>
                        <span x-text="(l.price * l.qty).toFixed(2) + ' ج'"></span>
                    </div>
                </template>
                <div class="border-t mt-2 pt-2 font-bold flex justify-between">
                    <span>الإجمالي</span><span x-text="total.toFixed(2) + ' ج'"></span>
                </div>

                <div class="space-y-2 mt-3">
                    <input x-model="form.phone" placeholder="الموبايل" class="w-full rounded border border-slate-300 px-2 py-1" dir="ltr">
                    <input x-model="form.name" placeholder="الاسم" class="w-full rounded border border-slate-300 px-2 py-1">
                    <input x-model="form.address" placeholder="العنوان" class="w-full rounded border border-slate-300 px-2 py-1">
                </div>
                <button @click="submit()" :disabled="busy || cart.length === 0"
                        class="mt-3 w-full bg-brand-600 disabled:opacity-50 text-white rounded-lg py-2 font-bold">إرسال الطلب</button>
                <div x-show="error" x-text="error" class="mt-2 text-sm text-rose-600"></div>
                <div x-show="orderToken" class="mt-2 text-sm text-emerald-700">
                    تم الطلب! <a :href="'/track/' + orderToken" class="underline">تتبّع الطلب</a>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    const csrf = {{ Illuminate\Support\Js::from(csrf_token()) }};
    function shop(branches) {
        return {
            branches, branch: '', menu: [], cart: [], busy: false, error: '', orderToken: '',
            form: { phone: '', name: '', address: '' },
            get total() { return this.cart.reduce((s, l) => s + l.price * l.qty, 0); },
            async loadMenu() {
                this.menu = []; this.cart = [];
                if (!this.branch) return;
                const res = await fetch('/api/menu?location=' + encodeURIComponent(this.branch));
                this.menu = await res.json();
            },
            add(item) {
                const existing = this.cart.find(l => l.item_id === item.item_id);
                if (existing) existing.qty += 1;
                else this.cart.push({ item_id: item.item_id, name: item.name, price: item.price, qty: 1 });
            },
            async submit() {
                this.busy = true; this.error = ''; this.orderToken = '';
                try {
                    const res = await fetch('/api/orders', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                        body: JSON.stringify({
                            location_id: this.branch, channel: 'web', pay: 'cash',
                            phone: this.form.phone, name: this.form.name, address: this.form.address,
                            lines: this.cart.map(l => ({ item_id: l.item_id, qty: l.qty })),
                        }),
                    });
                    const data = await res.json();
                    if (!data.ok) { this.error = data.error; return; }
                    this.orderToken = data.order.token;
                    this.cart = [];
                } catch (e) { this.error = 'تعذر الاتصال'; }
                finally { this.busy = false; }
            },
        };
    }
</script>
@endpush
@endsection