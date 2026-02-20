@props([
    'icon' => 'circle',
    'label' => 'Action',
    'href' => null,
    'type' => 'button',
])

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => 'ui-icon-btn', 'aria-label' => $label, 'data-tooltip' => $label]) }}>
        <x-ui.icon :name="$icon" class="h-4 w-4" />
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => 'ui-icon-btn', 'aria-label' => $label, 'data-tooltip' => $label]) }}>
        <x-ui.icon :name="$icon" class="h-4 w-4" />
    </button>
@endif
