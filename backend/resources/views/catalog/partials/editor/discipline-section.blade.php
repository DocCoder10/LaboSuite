                <section class="lms-stack" data-editor-section="discipline" hidden>
                    <h4>{{ __('messages.edit_discipline') }}</h4>
                    <form method="POST" data-editor-form="discipline-update" class="lms-stack">
                        @csrf
                        @method('PUT')
                        <label class="lms-field"><span>{{ __('messages.name') }}</span><input name="name" data-editor-input="discipline-name" required></label>
                        <label class="lms-checkbox">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" data-editor-input="discipline-active">
                            <span>{{ __('messages.active') }}</span>
                        </label>
                        <div class="lms-inline-actions">
                            <button class="lms-btn" type="submit">{{ __('messages.update') }}</button>
                            <button class="lms-btn lms-btn-soft" type="button" data-editor-cancel="discipline">{{ __('messages.cancel_changes') }}</button>
                        </div>
                    </form>

                    <form method="POST" data-editor-form="discipline-delete" data-delete-form>
                        @csrf
                        @method('DELETE')
                        <button class="lms-btn lms-btn-danger" type="submit">{{ __('messages.delete') }}</button>
                    </form>
                </section>
