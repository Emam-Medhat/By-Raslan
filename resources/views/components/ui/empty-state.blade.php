@props(['icon' => 'file-text', 'title' => 'لا توجد بيانات', 'description' => null])
<div {{ $attributes->merge(['class' => 'ds-empty']) }}>
    <div class="ds-empty-icon"><x-icon :name="$icon" size="24" /></div>
    <div class="font-bold text-primary-800">{{ $title }}</div>
    @if($description)<div class="text-sm text-slate-500 mt-1">{{ $description }}</div>@endif
    @if(trim($slot))<div class="mt-4 flex justify-center gap-2">{{ $slot }}</div>@endif
</div>
