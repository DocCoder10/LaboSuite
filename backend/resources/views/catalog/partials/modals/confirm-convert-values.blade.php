    <x-ui.modal id="modal-confirm-convert-values" :title="__('messages.warning')" confirm>
        <p class="lms-muted">{{ __('messages.catalog_child_conversion_warning') }}</p>
        <div class="lms-inline-actions">
            <button type="button" class="lms-btn lms-btn-soft" data-convert-cancel>{{ __('messages.cancel') }}</button>
            <button type="button" class="lms-btn" data-convert-confirm>{{ __('messages.confirm') }}</button>
        </div>
    </x-ui.modal>
