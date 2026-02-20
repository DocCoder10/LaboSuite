    <x-ui.modal id="modal-add-discipline" :title="__('messages.add_discipline')">
        <form method="POST" action="{{ route('catalog.disciplines.store') }}" class="lms-stack">
            @csrf
            <label class="lms-field"><span>{{ __('messages.name') }}</span><input name="name" required></label>
            <div class="lms-inline-actions">
                <button type="button" class="lms-btn lms-btn-soft" data-modal-close>{{ __('messages.close') }}</button>
                <button class="lms-btn" type="submit">{{ __('messages.add_discipline') }}</button>
            </div>
        </form>
    </x-ui.modal>
