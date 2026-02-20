    <dialog id="modal-confirm-convert-values" class="lms-modal lms-modal-confirm">
        <article class="lms-modal-card lms-stack">
            <header class="lms-modal-head">
                <h4>{{ __('messages.warning') }}</h4>
                <button type="button" class="lms-modal-close" data-modal-close>&times;</button>
            </header>
            <p class="lms-muted">{{ __('messages.catalog_child_conversion_warning') }}</p>
            <div class="lms-inline-actions">
                <button type="button" class="lms-btn lms-btn-soft" data-convert-cancel>{{ __('messages.cancel') }}</button>
                <button type="button" class="lms-btn" data-convert-confirm>{{ __('messages.confirm') }}</button>
            </div>
        </article>
    </dialog>
