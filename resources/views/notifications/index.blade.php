@extends('layouts.app')

@section('title', 'الإشعارات')
@section('page-title', 'الإشعارات')

@section('content')
<div class="bg-white rounded-xl border border-slate-200 overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-slate-500">
            <tr>
                <th class="text-right px-4 py-3">التاريخ</th>
                <th class="text-right px-4 py-3">العنوان</th>
                <th class="text-right px-4 py-3">النص</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($notifications as $n)
                <tr class="{{ $n->read_at ? '' : 'bg-amber-50' }}">
                    <td class="px-4 py-3">{{ $n->created_at }}</td>
                    <td class="px-4 py-3 font-semibold">{{ $n->data['title'] ?? $n->type }}</td>
                    <td class="px-4 py-3">{{ $n->data['body'] ?? '' }}</td>
                </tr>
            @empty
                <tr><td colspan="3" class="px-4 py-6 text-center text-slate-400">لا توجد إشعارات</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $notifications->links() }}</div>
@endsection