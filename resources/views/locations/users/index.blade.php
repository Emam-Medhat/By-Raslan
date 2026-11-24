@extends('layouts.app')

@section('title', 'مستخدمو ' . $location->name)
@section('page-title', 'مستخدمو الفرع: ' . $location->name)

@section('content')
<div class="space-y-4">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold">مستخدمو الفرع</h1>
            <p class="text-sm text-slate-500">{{ $location->name }} ({{ $location->city ?? '—' }})</p>
        </div>
        <a href="{{ route('locations.index') }}" class="bg-slate-100 hover:bg-slate-200 rounded-lg px-4 py-2 text-sm">→ عودة للمواقع</a>
    </div>

    {{-- Assign Form --}}
    @if($availableUsers->isNotEmpty())
    <div x-data="{ show: false }" class="ds-card p-5">
        <button @click="show = !show" class="ds-btn ds-btn-primary rounded-lg px-4 py-2 text-sm font-bold">
            + إسناد مستخدم
        </button>

        <div x-show="show" x-cloak class="mt-4">
            <form method="POST" action="{{ route('locations.users.store', $location) }}" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                @csrf
                <select name="user_id" class="ds-input" required>
                    <option value="">اختر المستخدم</option>
                    @foreach($availableUsers as $user)
                        <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->phone }})</option>
                    @endforeach
                </select>
                <select name="role" class="ds-input" required>
                    @foreach(\App\Modules\Organization\Models\LocationUser::ROLES as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="is_primary" class="rounded"> مدير أساسي
                </label>
                <button class="bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg px-4 py-2 font-bold">حفظ</button>
            </form>
        </div>
    </div>
    @endif

    {{-- Current Assignments --}}
    <div class="ds-card overflow-x-auto">
        <div class="px-4 py-3 border-b border-slate-100 font-bold">المستخدمون المسندون</div>
        <table class="w-full text-sm">
            <thead class="bg-slate-50/80 text-slate-500">
                <tr>
                    <th class="text-right px-4 py-3 font-semibold">الاسم</th>
                    <th class="text-right px-4 py-3 font-semibold">الموبايل</th>
                    <th class="text-right px-4 py-3 font-semibold">الدور</th>
                    <th class="text-right px-4 py-3 font-semibold">مدير أساسي</th>
                    <th class="text-right px-4 py-3 font-semibold">الصلاحيات</th>
                    <th class="text-right px-4 py-3 font-semibold">إجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($assignments as $assignment)
                    <tr class="hover:bg-slate-50/80">
                        <td class="px-4 py-3 font-semibold">{{ $assignment->user->name }}</td>
                        <td class="px-4 py-3" dir="ltr">{{ $assignment->user->phone }}</td>
                        <td class="px-4 py-3">
                            <span class="text-xs rounded px-2 py-0.5 bg-slate-100">
                                {{ \App\Modules\Organization\Models\LocationUser::ROLES[$assignment->role] ?? $assignment->role }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            @if($assignment->is_primary)
                                <span class="text-xs text-emerald-600">✓ مدير أساسي</span>
                            @else
                                <span class="text-xs text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if($assignment->permissions)
                                <span class="text-xs text-slate-500">{{ count($assignment->permissions) }} صلاحية</span>
                            @else
                                <span class="text-xs text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <form method="POST" action="{{ route('locations.users.destroy', [$location, $assignment]) }}" class="inline">
                                @csrf
                                @method('DELETE')
                                <button class="text-xs text-rose-600 hover:underline">إزالة</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-slate-400">لا يوجد مستخدمون مسندون</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection