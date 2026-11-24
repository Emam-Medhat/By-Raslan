@extends('layouts.app')

@section('title', 'الحسابات')
@section('page-title', 'الملخص اليومي')

@section('content')
<div class="ds-table-wrap">
    <table class="ds-table">
        <thead>
            <tr>
                <th>اليوم</th>
                <th>المبيعات</th>
                <th>المصروفات</th>
                <th>المشتريات</th>
                <th>الصافي</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    <td class="num" style="font-size: 12px;">{{ $row['day'] }}</td>
                    <td class="num font-bold">{{ number_format($row['sales'], 2) }}</td>
                    <td class="num">{{ number_format($row['expenses'], 2) }}</td>
                    <td class="num">{{ number_format($row['purchases'], 2) }}</td>
                    <td class="num font-bold {{ $row['net'] >= 0 ? 'text-emerald-700' : 'text-rose-600' }}">{{ number_format($row['net'], 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection