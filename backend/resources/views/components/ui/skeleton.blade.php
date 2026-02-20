@props([
    'height' => '1rem',
    'width' => '100%',
])

<div {{ $attributes->merge(['class' => 'ui-skeleton']) }} style="height: {{ $height }}; width: {{ $width }};"></div>
