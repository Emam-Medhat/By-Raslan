@extends('layouts.app')

@section('title', 'الحسابات')
@section('page-title', 'الملخص اليومي')

@section('content')
<div class="bg-white rounded-xl border border-slate-200 overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-slate-500">
            <tr>
                <th class="text-right px-4 py-3">اليوم</th>
                <th class="text-right px-4 py-3">المبيعات</th>
                <th class="text-right px-4 py-3">المصروفات</th>
                <th class="text-right px-4 py-3">المشتريات</th>
                <th class="text-right px-4 py-3">الصافي</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @foreach ($rows as $row)
                <tr>
                    <td class="px-4 py-3">{{ $row['day'] }}</td>
                    <td class="px-4 py-3 font-semibold">{{ $row['sales'] }}</td>
                    <td class="px-4 py-3">{{ $row['expenses'] }}</td>
                    <td class="px-4 py-3">{{ $row['purchases'] }}</td>
                    <td class="px-4 py-3 font-semibold {{ $row['net'] >= 0 ? 'text-emerald-700' : 'text-rose-600' }}">{{ $row['net'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection