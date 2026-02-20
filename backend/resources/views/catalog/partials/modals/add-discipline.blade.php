    <dialog id="modal-add-discipline" class="lms-modal">
        <article class="lms-modal-card lms-stack">
            <header class="lms-modal-head">
                <h4>{{ __('messages.add_discipline') }}</h4>
                <button type="button" class="lms-modal-close" data-modal-close>&times;</button>
            </header>
            <form method="POST" action="{{ route('catalog.disciplines.store') }}" class="lms-stack">
                @csrf
                <label class="lms-field"><span>{{ __('messages.name') }}</span><input name="name" required></label>
                <div class="lms-inline-actions">
                    <button type="button" class="lms-btn lms-btn-soft" data-modal-close>{{ __('messages.close') }}</button>
                    <button class="lms-btn" type="submit">{{ __('messages.add_discipline') }}</button>
                </div>
            </form>
        </article>
    </dialog>
