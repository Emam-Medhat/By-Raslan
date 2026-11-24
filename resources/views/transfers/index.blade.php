@extends('layouts.app')

@section('title', 'التحويلات')
@section('page-title', 'التحويلات')

@section('content')
<div class="space-y-4" x-data="{ createOpen: {{ $errors->any() ? 'true' : 'false' }} }">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold">التحويلات</h1>
        @can('stock.edit')
            <button type="button" @click="createOpen = true" class="ds-btn ds-btn-primary">تحويل جديد</button>
        @endcan
    </div>

    @can('stock.edit')
    <div x-show="createOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" aria-modal="true" role="dialog">
        <div class="absolute inset-0 bg-slate-900/45 backdrop-blur-sm" @click="createOpen = false"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>
        <div class="relative w-full max-w-2xl rounded-2xl border border-slate-200 bg-white shadow-2xl"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95 translate-y-2" x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100 translate-y-0" x-transition:leave-end="opacity-0 scale-95 translate-y-2"
             @keydown.escape.window="createOpen = false">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <div>
                    <h2 class="text-base font-extrabold text-primary-800">تحويل جديد</h2>
                    <p class="text-xs text-slate-400 mt-0.5">انقل علبًا بين المواقع</p>
                </div>
                <button type="button" @click="createOpen = false" class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-slate-400 hover:bg-slate-100 hover:text-slate-700 transition">
                    <x-icon name="x" size="16" />
                </button>
            </div>
            <form method="POST" action="{{ route('transfers.store') }}" class="p-5 space-y-4">
                @csrf
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="ds-label">من موقع</label>
                        <select name="from_loc" class="w-full ds-input @error('from_loc') !border-red-400 @enderror">
                            @foreach (($locations ?? []) as $location)
                                <option value="{{ $location->id }}" @selected(old('from_loc') == $location->id)>{{ $location->name }}</option>
                            @endforeach
                        </select>
                        @error('from_loc')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="ds-label">إلى موقع</label>
                        <select name="to_loc" class="w-full ds-input @error('to_loc') !border-red-400 @enderror">
                            @foreach (($locations ?? []) as $location)
                                <option value="{{ $location->id }}" @selected(old('to_loc') == $location->id)>{{ $location->name }}</option>
                            @endforeach
                        </select>
                        @error('to_loc')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div>
                    <label class="ds-label">أرقام العلب <span class="text-slate-400 font-normal">(امسح أو افصل بفاصلة)</span></label>
                    <textarea name="boxes" rows="3" class="w-full ds-input @error('boxes') !border-red-400 @enderror" dir="ltr" placeholder="101, 102, 103">{{ old('boxes') }}</textarea>
                    @error('boxes')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="ds-label">السبب</label>
                        <input name="reason" value="{{ old('reason') }}" placeholder="السبب" class="ds-input @error('reason') !border-red-400 @enderror">
                        @error('reason')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="ds-label">ملاحظة</label>
                        <input name="note" value="{{ old('note') }}" placeholder="ملاحظة" class="ds-input @error('note') !border-red-400 @enderror">
                        @error('note')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" @click="createOpen = false" class="ds-btn ds-btn-secondary px-4">إلغاء</button>
                    <button class="ds-btn ds-btn-primary px-5">إرسال التحويل</button>
                </div>
            </form>
        </div>
    </div>
    @endcan

    <div class="ds-table-wrap">
        <table class="ds-table">
            <thead>
                <tr>
                    <th>رقم</th>
                    <th>من</th>
                    <th>إلى</th>
                    <th>العلب</th>
                    <th>الحالة</th>
                    <th>التاريخ</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($transfers as $transfer)
                    <tr>
                        <td class="font-semibold">#{{ $transfer->no }}</td>
                        <td>{{ $transfer->from_loc }}</td>
                        <td>{{ $transfer->to_loc }}</td>
                        <td class="num">{{ $transfer->boxes_count }}</td>
                        <td>
                            <span class="ds-badge {{ $transfer->status === 'received' ? 'ds-badge-success' : 'ds-badge-warning' }}">
                                {{ $transfer->status === 'received' ? 'مستلم' : 'في الطريق' }}
                            </span>
                        </td>
                        <td class="num" style="font-size: 12px;">{{ $transfer->sent_at }}</td>
                        <td><a href="{{ route('transfers.show', $transfer) }}" class="ds-link">تفاصيل</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="ds-empty">لا توجد تحويلات</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection