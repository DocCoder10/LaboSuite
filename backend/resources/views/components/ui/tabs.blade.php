@props([
    'items' => [],
])

<nav {{ $attributes->merge(['class' => 'ui-tabs']) }}>
    @foreach ($items as $item)
        @php
            $href = $item['href'] ?? '#';
            $label = $item['label'] ?? '';
            $active = (bool) ($item['active'] ?? false);
        @endphp
        <a href="{{ $href }}" class="ui-tab {{ $active ? 'is-active' : '' }}">{{ $label }}</a>
    @endforeach
</nav>
