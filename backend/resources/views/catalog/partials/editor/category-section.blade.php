                <section class="lms-stack" data-editor-section="category" hidden>
                    <h4>{{ __('messages.edit_analysis') }}</h4>
                    <form method="POST" data-editor-form="category-update" class="lms-stack">
                        @csrf
                        @method('PUT')
                        <label class="lms-field">
                            <span>{{ __('messages.discipline') }}</span>
                            <select name="discipline_id" data-editor-input="category-discipline" required>
                                @foreach ($disciplines as $discipline)
                                    <option value="{{ $discipline->id }}">{{ $discipline->name }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="lms-field"><span>{{ __('messages.analysis') }}</span><input name="name" data-editor-input="category-name" required></label>
                        <label class="lms-checkbox">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" data-editor-input="category-active">
                            <span>{{ __('messages.active') }}</span>
                        </label>
                        <div class="lms-inline-actions">
                            <button class="lms-btn" type="submit">{{ __('messages.update') }}</button>
                            <button class="lms-btn lms-btn-soft" type="button" data-editor-cancel="category">{{ __('messages.cancel_changes') }}</button>
                        </div>
                    </form>

                    <div class="lms-stack" data-category-parameter-area>
                        <h5>{{ __('messages.catalog_leaf_parameters') }}</h5>
                        <p class="lms-muted" data-category-container-hint hidden>{{ __('messages.catalog_container_values_hint') }}</p>

                        <form method="POST" action="{{ route('catalog.parameters.store') }}" data-editor-form="category-parameter" class="lms-stack">
                            @csrf
                            <input type="hidden" name="_method" value="PUT" data-editor-input="category-parameter-method" disabled>
                            <input type="hidden" name="category_id" data-editor-input="category-parameter-category-id">
                            <input type="hidden" name="subcategory_id" value="">
                            <input type="hidden" name="name" data-editor-input="category-parameter-name">
                            <input type="hidden" name="value_type" value="number" data-editor-input="category-parameter-value-type" data-value-type-hidden>
                            <div class="lms-field">
                                <span>{{ __('messages.value_type') }}</span>
                                <div class="lms-type-picker" data-value-type-form>
                                    <label class="lms-checkbox">
                                        <input type="checkbox" value="number" data-value-type-choice>
                                        <span>{{ __('messages.value_type_number') }}</span>
                                    </label>
                                    <label class="lms-checkbox">
                                        <input type="checkbox" value="text" data-value-type-choice>
                                        <span>{{ __('messages.value_type_text') }}</span>
                                    </label>
                                    <label class="lms-checkbox">
                                        <input type="checkbox" value="list" data-value-type-choice>
                                        <span>{{ __('messages.value_type_list') }}</span>
                                    </label>
                                </div>
                            </div>
                            <label class="lms-field" data-value-type-field="number"><span>{{ __('messages.unit') }}</span><input name="unit" data-editor-input="category-parameter-unit"></label>
                            <label class="lms-field" data-value-type-field="number text"><span>{{ __('messages.reference') }}</span><input name="reference" data-editor-input="category-parameter-reference" placeholder="12 - 16"></label>
                            <label class="lms-field" data-value-type-field="list"><span>{{ __('messages.options_csv') }}</span><input name="options_csv" data-editor-input="category-parameter-options" placeholder="NEGATIF, POSITIF"></label>
                            <label class="lms-checkbox" data-value-type-field="list">
                                <input type="checkbox" data-default-option-toggle data-editor-input="category-parameter-default-toggle">
                                <span>{{ __('messages.default_option') }}</span>
                            </label>
                            <label class="lms-field" data-value-type-field="list" data-default-option-wrap hidden>
                                <span>{{ __('messages.default_option_value') }}</span>
                                <input name="default_option_value" data-default-option-input data-editor-input="category-parameter-default-option">
                            </label>
                            <label class="lms-checkbox">
                                <input type="hidden" name="is_visible" value="0">
                                <input type="checkbox" name="is_visible" value="1" data-editor-input="category-parameter-visible">
                                <span>{{ __('messages.visible') }}</span>
                            </label>
                            <label class="lms-checkbox">
                                <input type="hidden" name="is_active" value="0">
                                <input type="checkbox" name="is_active" value="1" data-editor-input="category-parameter-active">
                                <span>{{ __('messages.active') }}</span>
                            </label>
                            <div class="lms-inline-actions">
                                <button class="lms-btn" type="submit">{{ __('messages.save_parameters') }}</button>
                            </div>
                        </form>

                        <form method="POST" data-editor-form="category-parameter-delete" data-delete-form hidden>
                            @csrf
                            @method('DELETE')
                            <button class="lms-btn lms-btn-danger" type="submit">{{ __('messages.delete_parameters') }}</button>
                        </form>
                    </div>

                    <form method="POST" data-editor-form="category-delete" data-delete-form>
                        @csrf
                        @method('DELETE')
                        <button class="lms-btn lms-btn-danger" type="submit">{{ __('messages.delete') }}</button>
                    </form>
                </section>
