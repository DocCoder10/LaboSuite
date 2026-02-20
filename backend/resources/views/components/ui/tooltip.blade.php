@props([
    'text' => '',
])

<span {{ $attributes->merge(['data-tooltip' => $text]) }}>
    {{ $slot }}
</span>
