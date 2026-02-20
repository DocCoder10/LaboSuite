    <x-ui.modal id="modal-add-child" :title="__('messages.add_child')">
        <form method="dialog" class="lms-stack" data-form-add-child>
            <p class="lms-muted">{{ __('messages.catalog_parent_label') }}: <span data-add-child-parent-name>-</span></p>
            <label class="lms-field"><span>{{ __('messages.name') }}</span><input name="name" required data-add-child-name></label>
            <div class="lms-inline-actions">
                <button type="button" class="lms-btn lms-btn-soft" data-modal-close>{{ __('messages.close') }}</button>
                <button class="lms-btn" type="submit">{{ __('messages.add_child') }}</button>
            </div>
        </form>
    </x-ui.modal>
