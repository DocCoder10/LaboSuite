@props([
    'id',
    'title' => '',
    'confirm' => false,
    'openOnLoad' => false,
])

<dialog id="{{ $id }}" class="lms-modal ui-modal {{ $confirm ? 'lms-modal-confirm' : '' }}" data-ui-modal @if ($openOnLoad) data-open-on-load="1" @endif>
    <article class="lms-modal-card ui-modal-card lms-stack">
        <header class="lms-modal-head ui-modal-head">
            <h4>{{ $title }}</h4>
            <button type="button" class="lms-modal-close ui-modal-close" data-modal-close aria-label="{{ __('messages.close') }}">&times;</button>
        </header>
        {{ $slot }}
    </article>
</dialog>
