@props([
    'variant' => 'primary',
    'size' => 'md',
    'icon' => null,
    'iconRight' => null,
    'href' => null,
    'type' => 'button',
])

@php
    $variantClass = match ($variant) {
        'secondary' => 'ui-btn-secondary',
        'ghost' => 'ui-btn-ghost',
        'danger' => 'ui-btn-danger',
        default => 'ui-btn-primary',
    };

    $sizeClass = match ($size) {
        'sm' => 'px-3 py-2 text-sm',
        'lg' => 'px-5 py-3 text-base',
        default => '',
    };

    $classes = trim('ui-btn '.$variantClass.' '.$sizeClass);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon)
            <x-ui.icon :name="$icon" class="h-4 w-4" />
        @endif
        <span>{{ $slot }}</span>
        @if ($iconRight)
            <x-ui.icon :name="$iconRight" class="h-4 w-4" />
        @endif
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon)
            <x-ui.icon :name="$icon" class="h-4 w-4" />
        @endif
        <span>{{ $slot }}</span>
        @if ($iconRight)
            <x-ui.icon :name="$iconRight" class="h-4 w-4" />
        @endif
    </button>
@endif
