                <section class="lms-stack" data-editor-section="parameter" hidden>
                    <h4>{{ __('messages.edit_sub_analysis') }}</h4>
                    <form method="POST" data-editor-form="parameter-update" class="lms-stack">
                        @csrf
                        @method('PUT')
                        <label class="lms-field">
                            <span>{{ __('messages.category') }}</span>
                            <select name="category_id" data-editor-input="parameter-category" required>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="lms-field">
                            <span>{{ __('messages.subcategory') }}</span>
                            <select name="subcategory_id" data-editor-input="parameter-subcategory">
                                <option value="">{{ __('messages.no_subcategory') }}</option>
                                @foreach ($subcategories as $subcategory)
                                    <option value="{{ $subcategory->id }}" data-category-id="{{ $subcategory->category_id }}">
                                        {{ $subcategory->name }}
                                    </option>
                                @endforeach
                            </select>
                        </label>
                        <label class="lms-field"><span>{{ __('messages.subcategory') }}</span><input name="name" data-editor-input="parameter-name" required></label>
                        <input type="hidden" name="value_type" value="number" data-editor-input="parameter-value-type" data-value-type-hidden>
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
                        <label class="lms-field" data-value-type-field="number"><span>{{ __('messages.unit') }}</span><input name="unit" data-editor-input="parameter-unit"></label>
                        <label class="lms-field" data-value-type-field="number text"><span>{{ __('messages.reference') }}</span><input name="reference" data-editor-input="parameter-reference" placeholder="{{ __('messages.reference_placeholder_number') }}"></label>
                        <label class="lms-field" data-value-type-field="list"><span>{{ __('messages.options_csv') }}</span><input name="options_csv" data-editor-input="parameter-options" placeholder="NEGATIF, POSITIF"></label>
                        <label class="lms-checkbox" data-value-type-field="list">
                            <input type="checkbox" data-default-option-toggle data-editor-input="parameter-default-toggle">
                            <span>{{ __('messages.default_option') }}</span>
                        </label>
                        <label class="lms-field" data-value-type-field="list" data-default-option-wrap hidden>
                            <span>{{ __('messages.default_option_value') }}</span>
                            <input name="default_option_value" data-default-option-input data-editor-input="parameter-default-option">
                        </label>
                        <label class="lms-checkbox">
                            <input type="hidden" name="is_visible" value="0">
                            <input type="checkbox" name="is_visible" value="1" data-editor-input="parameter-visible">
                            <span>{{ __('messages.visible') }}</span>
                        </label>
                        <label class="lms-checkbox">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" data-editor-input="parameter-active">
                            <span>{{ __('messages.active') }}</span>
                        </label>
                        <div class="lms-inline-actions">
                            <button class="lms-btn" type="submit">{{ __('messages.update') }}</button>
                            <button class="lms-btn lms-btn-soft" type="button" data-editor-cancel="parameter">{{ __('messages.cancel_changes') }}</button>
                        </div>
                    </form>

                    <form method="POST" data-editor-form="parameter-delete" data-delete-form>
                        @csrf
                        @method('DELETE')
                        <button class="lms-btn lms-btn-danger" type="submit">{{ __('messages.delete') }}</button>
                    </form>
                </section>
