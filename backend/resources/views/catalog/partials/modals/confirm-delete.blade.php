    <x-ui.modal id="modal-confirm-delete" :title="__('messages.delete')" confirm>
        <p class="lms-muted" data-delete-message>{{ __('messages.confirm_delete') }}</p>
        <form method="POST" data-delete-confirm-form class="lms-inline-actions">
            @csrf
            @method('DELETE')
            <input type="hidden" name="force" value="0" data-delete-force-input>
            <button type="button" class="lms-btn lms-btn-soft" data-modal-close>{{ __('messages.close') }}</button>
            <button class="lms-btn lms-btn-danger" type="submit">{{ __('messages.delete') }}</button>
        </form>
    </x-ui.modal>
