@extends('layouts.guest')

@section('title', 'اطلب أونلاين')

@section('content')
<div class="max-w-5xl mx-auto p-4 space-y-4">
    <div class="bg-white rounded-xl border border-slate-200 p-4 flex flex-wrap gap-3 items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold">اطلب أونلاين</h1>
            <p class="text-slate-500 text-sm">اختار الفرع والمنتجات وسيب بيانات التوصيل</p>
        </div>
        <form method="GET" action="{{ route('public.order') }}">
            <select name="location" onchange="this.form.submit()" class="rounded-lg border border-slate-300 px-3 py-2">
                @foreach ($branches as $b)
                    <option value="{{ $b['id'] }}" @selected($location === $b['id'])>{{ $b['name'] }} — توصيل {{ $b['fee'] }} ج</option>
                @endforeach
            </select>
        </form>
    </div>

    <div class="grid lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 p-4">
            <h2 class="font-bold mb-3">المنيو</h2>
            <div class="space-y-2" id="menu">
                @forelse ($menu as $m)
                    <label class="flex items-center justify-between gap-3 border border-slate-100 rounded-lg p-3">
                        <div>
                            <div class="font-semibold">{{ $m['name'] }}</div>
                            <div class="text-xs text-slate-500">{{ $m['price'] }} ج / {{ $m['unit'] }}</div>
                        </div>
                        <input type="number" min="0" step="0.5" value="0" data-item="{{ $m['item_id'] }}" class="menu-qty w-24 rounded-lg border border-slate-300 px-2 py-1">
                    </label>
                @empty
                    <div class="text-slate-400 text-sm">لا توجد أصناف متاحة لهذا الفرع.</div>
                @endforelse
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <h2 class="font-bold mb-3">بيانات الطلب</h2>
            <div class="space-y-2">
                <input id="name" placeholder="الاسم" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                <input id="phone" placeholder="الموبايل" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                <input id="address" placeholder="العنوان" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                <input id="area" placeholder="المنطقة" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                <select id="pay" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    <option value="cash">كاش</option>
                    <option value="card">فيزا عند الاستلام</option>
                    <option value="instapay">إنستاباي</option>
                </select>
                <textarea id="note" placeholder="ملاحظة" class="w-full rounded-lg border border-slate-300 px-3 py-2"></textarea>
                <button id="placeBtn" class="w-full bg-brand-600 hover:bg-brand-700 text-white rounded-lg px-4 py-2 font-bold">تأكيد الطلب</button>
                <div id="msg" class="text-sm"></div>
            </div>
        </div>
    </div>
</div>
<script>
document.getElementById('placeBtn')?.addEventListener('click', async () => {
    const msg = document.getElementById('msg');
    msg.className = 'text-sm text-slate-500';
    msg.textContent = 'جاري إرسال الطلب...';
    const lines = Array.from(document.querySelectorAll('.menu-qty')).map(i => ({item_id: Number(i.dataset.item), qty: Number(i.value)})).filter(l => l.qty > 0);
    const payload = {
        location_id: @json($location),
        name: document.getElementById('name').value,
        phone: document.getElementById('phone').value,
        address: document.getElementById('address').value,
        area: document.getElementById('area').value,
        pay: document.getElementById('pay').value,
        note: document.getElementById('note').value,
        channel: 'web',
        lines,
    };
    const res = await fetch('/api/orders', {method:'POST', headers:{'Content-Type':'application/json','Accept':'application/json'}, body: JSON.stringify(payload)});
    const data = await res.json();
    if (!res.ok || !data.ok) { msg.className='text-sm text-rose-600'; msg.textContent = data.error || 'تعذر إرسال الطلب'; return; }
    msg.className='text-sm text-emerald-700';
    msg.innerHTML = 'تم الطلب رقم ' + data.order.no + ' — <a class="underline" href="/t/' + data.order.token + '">تتبع الطلب</a>';
});
</script>
@endsection
