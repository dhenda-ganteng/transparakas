@props([
    'size' => 'md',
])

@php
    $boxClasses = match ($size) {
        'sm' => 'w-8 h-8 rounded-lg',
        'lg' => 'w-11 h-11 rounded-xl',
        default => 'w-9 h-9 rounded-lg',
    };

    $iconClasses = match ($size) {
        'sm' => 'w-5 h-5',
        'lg' => 'w-6 h-6',
        default => 'w-5 h-5',
    };
@endphp

<div {{ $attributes->merge(['class' => "{$boxClasses} bg-[#0F766E] border-2 border-[#064E3B] flex items-center justify-center text-white shadow-sm shrink-0"]) }} aria-hidden="true">
    <svg class="{{ $iconClasses }}" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <!-- Lembaran Uang Kertas -->
        <rect x="2.5" y="5.5" width="19" height="13" rx="2.5" fill="#14B8A6" fill-opacity="0.28" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
        <!-- Garis Sudut Uang -->
        <path d="M5.5 8.5H6.5M17.5 8.5H18.5M5.5 15.5H6.5M17.5 15.5H18.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
        <!-- Lingkaran Koin Tengah -->
        <circle cx="12" cy="12" r="3.25" fill="#FDE047" fill-opacity="0.22" stroke="#FDE047" stroke-width="2"/>
        <!-- Simbol Nilai Uang -->
        <path d="M12 10.2V13.8M10.9 11.2C10.9 10.75 11.38 10.45 12 10.45C12.62 10.45 13.1 10.75 13.1 11.2C13.1 11.65 12.62 11.9 12 11.9C11.38 11.9 10.9 12.2 10.9 12.68C10.9 13.15 11.38 13.5 12 13.5C12.62 13.5 13.1 13.2 13.1 12.75" stroke="#FEF08A" stroke-width="1.4" stroke-linecap="round"/>
    </svg>
</div>
