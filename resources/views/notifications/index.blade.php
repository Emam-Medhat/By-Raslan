@extends('layouts.app')

@section('title', 'الإشعارات')
@section('page-title', 'الإشعارات')

@section('content')
<div class="ds-table-wrap">
    <table class="ds-table">
        <thead>
            <tr>
                <th>التاريخ</th>
                <th>العنوان</th>
                <th>النص</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($notifications as $n)
                <tr class="{{ $n->read_at ? '' : 'bg-amber-50' }}">
                    <td class="num" style="font-size: 12px;">{{ $n->created_at }}</td>
                    <td class="font-semibold">{{ $n->data['title'] ?? $n->type }}</td>
                    <td>{{ $n->data['body'] ?? '' }}</td>
                </tr>
            @empty
                <tr><td colspan="3" class="ds-empty">لا توجد إشعارات</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $notifications->links() }}</div>
@endsection