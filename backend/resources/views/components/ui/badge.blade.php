@props([
    'tone' => 'muted',
])

@php
    $toneClass = match ($tone) {
        'success' => 'is-success',
        'danger' => 'is-danger',
        default => 'is-muted',
    };
@endphp

<span {{ $attributes->merge(['class' => 'ui-badge '.$toneClass]) }}>
    {{ $slot }}
</span>
