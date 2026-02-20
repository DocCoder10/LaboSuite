@props([
    'as' => 'section',
])

<{{ $as }} {{ $attributes->merge(['class' => 'ui-card']) }}>
    {{ $slot }}
</{{ $as }}>
