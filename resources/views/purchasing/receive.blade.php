@extends('layouts.app')

@section('title', 'استلام أمر توريد')
@section('page-title', 'استلام PO-' . $order->no)

@section('content')
<div class="space-y-4">
    <div class="ds-card p-5">
        <div class="font-semibold">{{ $order->supplier?->name }}</div>
        <div class="text-sm text-slate-500">الحالة: {{ $order->status }}</div>
    </div>

    @if ($errors->any())
        <div class="bg-rose-50 border border-rose-200 text-rose-700 rounded-xl p-4 text-sm">
            @foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach
        </div>
    @endif

    @if (session('override_violations'))
        <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 space-y-2">
            <div class="font-bold text-amber-800">مخالفات تحتاج تجاوز</div>
            @foreach (session('override_violations', []) as $v)<div class="text-sm text-amber-800">• {{ $v }}</div>@endforeach
            <form method="POST" action="{{ route('purchasing.requestOverride', $order) }}" class="space-y-2 mt-2">
                @csrf
                @foreach (session('override_violations', []) as $v)<input type="hidden" name="violations[]" value="{{ $v }}">@endforeach
                <input type="hidden" name="value" value="{{ session('override_value', 0) }}">
                <label class="block text-sm">سبب طلب التجاوز<input name="reason" required minlength="5" class="mt-1 w-full ds-input"></label>
                <button class="ds-btn bg-amber-600 text-white hover:bg-amber-700 rounded-lg px-4 py-2 font-bold">اطلب تجاوز</button>
            </form>
        </div>
    @endif

    @if ($approved->isNotEmpty())
        <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-4 text-sm text-emerald-800">
            فيه تجاوز متوافق عليه لهذا الأمر: #{{ $approved->first()->id }} — {{ $approved->first()->reason }}
        </div>
    @endif

    @if (!in_array($order->status, ['open','partial']))
        <div class="bg-rose-50 border border-rose-200 text-rose-700 rounded-xl p-4">أمر التوريد ده مش مفتوح</div>
    @else
        <form method="POST" action="{{ route('purchasing.receive', $order) }}" class="ds-card p-5 space-y-3">
            @csrf
            <div class="flex flex-wrap gap-3">
                <input name="invoice_no" placeholder="رقم الفاتورة" class="ds-input">
                @if ($approved->isNotEmpty())
                    <label class="text-sm">استخدم تجاوز
                        <select name="override_id" class="ds-input">
                            <option value="">—</option>
                            @foreach ($approved as $ov)<option value="{{ $ov->id }}">#{{ $ov->id }} — {{ \Illuminate\Support\Str::limit($ov->reason, 40) }}</option>@endforeach
                        </select>
                    </label>
                @endif
                @can('override.recv')
                    <label class="text-sm">سبب تجاوز مباشر<input name="reason" class="ds-input" placeholder="لو هتتجاوز مباشرة"></label>
                @endcan
            </div>
            <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50/80 text-slate-500"><tr><th class="text-right px-2 py-2">الصنف</th><th class="text-right px-2 py-2">المطلوب</th><th class="text-right px-2 py-2">مستلم</th><th class="text-right px-2 py-2">موزون</th><th class="text-right px-2 py-2">مرفوض</th><th class="text-right px-2 py-2">السعر</th><th class="text-right px-2 py-2">حرارة</th><th class="text-right px-2 py-2">صلاحية</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($order->lines->where(fn($l)=>$l->received < $l->qty*0.99) as $line)
                        @php($spec = $specs[$line->item_id] ?? null)
                        <tr>
                            <td class="px-2 py-2">{{ $line->item?->name }} @if($spec)<span class="text-xs text-slate-400">v{{ $spec->version }}</span>@endif</td>
                            <td class="px-2 py-2">{{ $line->qty }}</td>
                            <td class="px-2 py-2">{{ $line->received }}</td>
                            <td class="px-2 py-1"><input name="lines[{{ $line->line }}][weighed]" type="number" step="0.001" min="0" class="w-24 rounded border border-slate-300 px-2 py-1"></td>
                            <td class="px-2 py-1"><input name="lines[{{ $line->line }}][rejected]" type="number" step="0.001" min="0" value="0" class="w-24 rounded border border-slate-300 px-2 py-1"></td>
                            <td class="px-2 py-1"><input name="lines[{{ $line->line }}][price]" type="number" step="0.01" value="{{ $line->price }}" class="w-24 rounded border border-slate-300 px-2 py-1"></td>
                            <td class="px-2 py-1"><input name="lines[{{ $line->line }}][temp]" type="number" step="0.1" class="w-20 rounded border border-slate-300 px-2 py-1"></td>
                            <td class="px-2 py-1"><input name="lines[{{ $line->line }}][expires]" type="date" class="rounded border border-slate-300 px-2 py-1"></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
            <button class="ds-btn ds-btn-accent rounded-lg px-5 py-2 font-bold">استلام</button>
        </form>
    @endif
</div>
@endsection
