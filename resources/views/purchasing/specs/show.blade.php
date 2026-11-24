@extends('layouts.app')

@section('title', 'مواصفة صنف')
@section('page-title', 'كارت الصنف — ' . $item->name)

@section('content')
<div class="space-y-4">
    <div class="ds-card p-5">
        <div class="font-bold">{{ $item->name }}</div>
        <div class="text-sm text-slate-500">الوحدة: {{ $item->unit ?? '—' }}</div>
    </div>

    @can('purchase.edit')
    <form method="POST" action="{{ route('purchasing.specs.save', $item) }}" class="ds-card p-5 space-y-3">
        @csrf
        <label class="block text-sm">بنود الفحص (سطر لكل بند)<textarea name="checks_text" rows="4" class="mt-1 w-full ds-input">{{ collect($current?->snapshot['checks'] ?? [])->pluck('t')->implode("\n") }}</textarea></label>
        <label class="block text-sm">المستندات المطلوبة (سطر لكل مستند)<textarea name="certs_text" rows="3" class="mt-1 w-full ds-input">{{ collect($current?->snapshot['certs'] ?? [])->pluck('n')->implode("\n") }}</textarea></label>
        <div class="grid sm:grid-cols-2 gap-3">
            <label class="text-sm">أقصى حرارة<input name="temp_max" type="number" step="0.1" value="{{ $current?->snapshot['temp_max'] ?? '' }}" class="mt-1 w-full ds-input"></label>
            <label class="text-sm">أقل صلاحية (يوم)<input name="min_shelf_days" type="number" value="{{ $current?->snapshot['min_shelf_days'] ?? 0 }}" class="mt-1 w-full ds-input"></label>
        </div>
        <button class="ds-btn ds-btn-primary">حفظ واعتماد نسخة</button>
    </form>
    @endcan

    <div class="ds-card p-5">
        <h2 class="font-bold mb-3">سجل الاعتماد</h2>
        @forelse ($versions as $v)
            <div class="text-sm py-1 border-b border-slate-100">v{{ $v->version }} — {{ $v->approved_at }}</div>
        @empty
            <div class="text-amber-700 text-sm">المواصفة لسه ماتعتمدتش.</div>
        @endforelse
    </div>
</div>
<script>
document.querySelector('form[action*="specs"]')?.addEventListener('submit', function(e){
  const checks = (this.querySelector('[name="checks_text"]').value||'').split('\n').map(s=>s.trim()).filter(Boolean);
  const certs = (this.querySelector('[name="certs_text"]').value||'').split('\n').map(s=>s.trim()).filter(Boolean);
  checks.forEach(v=>{let i=document.createElement('input');i.type='hidden';i.name='checks[]';i.value=v;this.appendChild(i);});
  certs.forEach(v=>{let i=document.createElement('input');i.type='hidden';i.name='certs[]';i.value=v;this.appendChild(i);});
});
</script>
@endsection
