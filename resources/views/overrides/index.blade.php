@extends('layouts.app')

@section('title', 'التجاوزات')
@section('page-title', 'التجاوزات')

@section('content')
<div class="bg-white rounded-xl border border-slate-200 overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-slate-500">
            <tr>
                <th class="text-right px-4 py-3">#</th>
                <th class="text-right px-4 py-3">النوع</th>
                <th class="text-right px-4 py-3">المرجع</th>
                <th class="text-right px-4 py-3">القيمة</th>
                <th class="text-right px-4 py-3">السبب</th>
                <th class="text-right px-4 py-3">الحالة</th>
                <th class="text-right px-4 py-3">إجراء</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($overrides as $override)
                <tr>
                    <td class="px-4 py-3">{{ $override->id }}</td>
                    <td class="px-4 py-3">{{ $override->kind }}</td>
                    <td class="px-4 py-3">{{ $override->ref ?? '—' }}</td>
                    <td class="px-4 py-3">{{ $override->value }}</td>
                    <td class="px-4 py-3">{{ $override->reason }}</td>
                    <td class="px-4 py-3">{{ $override->status }}</td>
                    <td class="px-4 py-3">
                        @can('override.recv')
                            @if ($override->status === 'pending')
                                <form method="POST" action="{{ route('overrides.decide', $override) }}" class="inline-flex gap-2">
                                    @csrf
                                    <input type="hidden" name="approve" value="1">
                                    <button class="text-xs text-emerald-700 underline">موافقة</button>
                                </form>
                                <form method="POST" action="{{ route('overrides.decide', $override) }}" class="inline">
                                    @csrf
                                    <input type="hidden" name="approve" value="0">
                                    <button class="text-xs text-rose-600 underline">رفض</button>
                                </form>
                            @endif
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-4 py-6 text-center text-slate-400">لا توجد تجاوزات</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $overrides->links() }}</div>
@endsection