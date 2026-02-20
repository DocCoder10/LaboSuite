    <dialog id="modal-confirm-delete" class="lms-modal lms-modal-confirm">
        <article class="lms-modal-card lms-stack">
            <header class="lms-modal-head">
                <h4>{{ __('messages.delete') }}</h4>
                <button type="button" class="lms-modal-close" data-modal-close>&times;</button>
            </header>
            <p class="lms-muted" data-delete-message>{{ __('messages.confirm_delete') }}</p>
            <form method="POST" data-delete-confirm-form class="lms-inline-actions">
                @csrf
                @method('DELETE')
                <input type="hidden" name="force" value="0" data-delete-force-input>
                <button type="button" class="lms-btn lms-btn-soft" data-modal-close>{{ __('messages.close') }}</button>
                <button class="lms-btn lms-btn-danger" type="submit">{{ __('messages.delete') }}</button>
            </form>
        </article>
    </dialog>
