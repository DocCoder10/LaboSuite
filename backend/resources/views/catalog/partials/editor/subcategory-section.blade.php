                <section class="lms-stack" data-editor-section="subcategory" hidden>
                    <h4>{{ __('messages.edit_subcategory') }}</h4>
                    <form method="POST" data-editor-form="subcategory-update" class="lms-stack">
                        @csrf
                        @method('PUT')
                        <label class="lms-field">
                            <span>{{ __('messages.category') }}</span>
                            <select name="category_id" data-editor-input="subcategory-category" required>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </label>
                        <input type="hidden" name="parent_subcategory_id" data-editor-input="subcategory-parent">
                        <label class="lms-field"><span>{{ __('messages.subcategory') }}</span><input name="name" data-editor-input="subcategory-name" required></label>
                        <label class="lms-checkbox">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" data-editor-input="subcategory-active">
                            <span>{{ __('messages.active') }}</span>
                        </label>
                        <div class="lms-inline-actions">
                            <button class="lms-btn" type="submit">{{ __('messages.update') }}</button>
                            <button class="lms-btn lms-btn-soft" type="button" data-editor-cancel="subcategory">{{ __('messages.cancel_changes') }}</button>
                        </div>
                    </form>

                    <form method="POST" data-editor-form="subcategory-delete" data-delete-form>
                        @csrf
                        @method('DELETE')
                        <button class="lms-btn lms-btn-danger" type="submit">{{ __('messages.delete') }}</button>
                    </form>

                    <div class="lms-stack" data-subcategory-parameter-area>
                        <h5>{{ __('messages.catalog_leaf_parameters') }}</h5>
                        <p class="lms-muted" data-subcategory-container-hint hidden>{{ __('messages.catalog_container_values_hint') }}</p>

                        <form method="POST" action="{{ route('catalog.parameters.store') }}" data-editor-form="subcategory-parameter" class="lms-stack">
                            @csrf
                            <input type="hidden" name="_method" value="PUT" data-editor-input="subcategory-parameter-method" disabled>
                            <input type="hidden" name="category_id" data-editor-input="subcategory-parameter-category-id">
                            <input type="hidden" name="subcategory_id" data-editor-input="subcategory-parameter-subcategory-id">
                            <input type="hidden" name="name" data-editor-input="subcategory-parameter-name">
                            <input type="hidden" name="value_type" value="number" data-editor-input="subcategory-parameter-value-type" data-value-type-hidden>
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
                            <label class="lms-field" data-value-type-field="number"><span>{{ __('messages.unit') }}</span><input name="unit" data-editor-input="subcategory-parameter-unit"></label>
                            <label class="lms-field" data-value-type-field="number text"><span>{{ __('messages.reference') }}</span><input name="reference" data-editor-input="subcategory-parameter-reference" placeholder="{{ __('messages.reference_placeholder_number') }}"></label>
                            <label class="lms-field" data-value-type-field="list"><span>{{ __('messages.options_csv') }}</span><input name="options_csv" data-editor-input="subcategory-parameter-options" placeholder="NEGATIF, POSITIF"></label>
                            <label class="lms-checkbox" data-value-type-field="list">
                                <input type="checkbox" data-default-option-toggle data-editor-input="subcategory-parameter-default-toggle">
                                <span>{{ __('messages.default_option') }}</span>
                            </label>
                            <label class="lms-field" data-value-type-field="list" data-default-option-wrap hidden>
                                <span>{{ __('messages.default_option_value') }}</span>
                                <input name="default_option_value" data-default-option-input data-editor-input="subcategory-parameter-default-option">
                            </label>
                            <label class="lms-checkbox">
                                <input type="hidden" name="is_visible" value="0">
                                <input type="checkbox" name="is_visible" value="1" data-editor-input="subcategory-parameter-visible">
                                <span>{{ __('messages.visible') }}</span>
                            </label>
                            <label class="lms-checkbox">
                                <input type="hidden" name="is_active" value="0">
                                <input type="checkbox" name="is_active" value="1" data-editor-input="subcategory-parameter-active">
                                <span>{{ __('messages.active') }}</span>
                            </label>
                            <div class="lms-inline-actions">
                                <button class="lms-btn" type="submit">{{ __('messages.save_parameters') }}</button>
                            </div>
                        </form>

                        <form method="POST" data-editor-form="subcategory-parameter-delete" data-delete-form hidden>
                            @csrf
                            @method('DELETE')
                            <button class="lms-btn lms-btn-danger" type="submit">{{ __('messages.delete_parameters') }}</button>
                        </form>
                    </div>
                </section>
