@props([
    'title' => '',
    'value' => '-',
    'subtext' => '',
    'icon' => 'chart',
    'tone' => 'blue',
])

<article {{ $attributes->merge(['class' => 'lms-kpi-card']) }}>
    <div class="lms-kpi-top">
        <span class="lms-kpi-icon is-{{ $tone }}" aria-hidden="true">
            <x-ui.icon :name="$icon" class="h-4 w-4" />
        </span>
    </div>
    <p class="lms-kpi-title">{{ $title }}</p>
    <p class="lms-kpi-value">{{ $value }}</p>
    <p class="lms-kpi-subtext">{{ $subtext }}</p>
</article>
