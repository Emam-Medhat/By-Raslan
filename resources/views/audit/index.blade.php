@extends('layouts.app')

@section('title', 'السجل')
@section('page-title', 'سجل التعديلات')

@section('content')
<div class="bg-white rounded-xl border border-slate-200 overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-slate-500">
            <tr>
                <th class="text-right px-4 py-3">التاريخ</th>
                <th class="text-right px-4 py-3">المستخدم</th>
                <th class="text-right px-4 py-3">الإجراء</th>
                <th class="text-right px-4 py-3">الجدول</th>
                <th class="text-right px-4 py-3">السجل</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($logs as $log)
                <tr>
                    <td class="px-4 py-3">{{ $log->at }}</td>
                    <td class="px-4 py-3">{{ $log->user?->name ?? '—' }}</td>
                    <td class="px-4 py-3">{{ $log->action }}</td>
                    <td class="px-4 py-3">{{ $log->table_name ?? '—' }}</td>
                    <td class="px-4 py-3">{{ $log->record_id ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-4 py-6 text-center text-slate-400">لا يوجد سجل</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $logs->links() }}</div>
@endsection