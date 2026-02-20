@extends('layouts.app')

@section('content')
    <section class="lms-page-head">
        <h2>{{ __('messages.catalog_title') }}</h2>
    </section>

    <div class="lms-inline-actions lms-wrap-actions">
        <button type="button" class="lms-btn" data-modal-open="modal-add-discipline">{{ __('messages.add_discipline') }}</button>
        <button type="button" class="lms-btn lms-btn-soft" data-modal-open="modal-add-analysis">{{ __('messages.add_analysis') }}</button>
        <button type="button" class="lms-btn lms-btn-soft" data-modal-open="modal-add-subcategory">{{ __('messages.add_subcategory') }}</button>
        <button type="button" class="lms-btn lms-btn-soft" data-modal-open="modal-add-sub-analysis">{{ __('messages.add_sub_analysis') }}</button>
        <button type="button" class="lms-btn lms-btn-soft" data-add-child disabled>{{ __('messages.add_child') }}</button>
    </div>

    <div
        class="lms-catalog-layout"
        data-catalog-editor
        data-route-discipline-update="{{ url('/catalog/disciplines/__ID__') }}"
        data-route-discipline-delete="{{ url('/catalog/disciplines/__ID__') }}"
        data-route-category-update="{{ url('/catalog/categories/__ID__') }}"
        data-route-category-delete="{{ url('/catalog/categories/__ID__') }}"
        data-route-subcategory-update="{{ url('/catalog/subcategories/__ID__') }}"
        data-route-subcategory-delete="{{ url('/catalog/subcategories/__ID__') }}"
        data-route-parameter-store="{{ route('catalog.parameters.store') }}"
        data-route-parameter-update="{{ url('/catalog/parameters/__ID__') }}"
        data-route-parameter-delete="{{ url('/catalog/parameters/__ID__') }}"
        data-route-reorder="{{ route('catalog.reorder') }}"
        data-csrf-token="{{ csrf_token() }}"
    >
        <aside class="lms-card lms-stack lms-tree-panel">
            <h3>{{ __('messages.catalog_tree_title') }}</h3>
            <p class="lms-muted">{{ __('messages.catalog_tree_help') }}</p>

            <ul class="lms-tree-root">
                @if ($disciplines->isEmpty())
                    <li class="lms-tree-empty">{{ __('messages.catalog_no_discipline') }}</li>
                @else
                    @foreach ($disciplines as $discipline)
                        <li>
                            @php
                                $disciplineHasChildren = $discipline->categories->isNotEmpty();
                            @endphp
                            <details class="lms-tree-branch lms-tree-level-discipline" data-root-branch>
                                <summary
                                    class="lms-tree-node lms-tree-summary"
                                    data-node-type="discipline"
                                    data-id="{{ $discipline->id }}"
                                    data-name="{{ $discipline->name }}"
                                    data-sort-order="{{ $discipline->sort_order }}"
                                    data-active="{{ $discipline->is_active ? 1 : 0 }}"
                                    data-depth="1"
                                    data-has-children="{{ $disciplineHasChildren ? 1 : 0 }}"
                                >
                                    <span class="lms-tree-arrow" aria-hidden="true"></span>
                                    <span class="lms-tree-label">{{ $discipline->name }}</span>
                                </summary>

                                <ul class="lms-tree-children">
                                    @if ($discipline->categories->isEmpty())
                                        <li class="lms-tree-empty">{{ __('messages.catalog_no_category') }}</li>
                                    @else
                                        @foreach ($discipline->categories as $category)
                                            <li>
                                                @php
                                                    $hasSubcategories = $category->subcategories->isNotEmpty();
                                                    $hasCategoryChildren = $category->parameters->isNotEmpty() || $hasSubcategories;
                                                @endphp
                                                <details class="lms-tree-branch lms-tree-level-category">
                                                    <summary
                                                        class="lms-tree-node lms-tree-summary"
                                                        data-node-type="category"
                                                        data-drag-enabled="1"
                                                        data-id="{{ $category->id }}"
                                                        data-category-id="{{ $category->id }}"
                                                        data-parent-id="{{ $discipline->id }}"
                                                        data-parent-type="discipline"
                                                        data-name="{{ $category->name }}"
                                                        data-sort-order="{{ $category->sort_order }}"
                                                        data-active="{{ $category->is_active ? 1 : 0 }}"
                                                        data-depth="2"
                                                        data-has-children="{{ $hasCategoryChildren ? 1 : 0 }}"
                                                        data-has-subcategories="{{ $hasSubcategories ? 1 : 0 }}"
                                                        draggable="true"
                                                    >
                                                        <span class="lms-tree-arrow" aria-hidden="true"></span>
                                                        <span class="lms-tree-label">{{ $category->name }}</span>
                                                        <span class="lms-tree-drag-handle" data-drag-handle title="{{ __('messages.drag_to_reorder') }}" aria-label="{{ __('messages.drag_to_reorder') }}">⋮⋮</span>
                                                    </summary>

                                                    <ul class="lms-tree-children">
                                                        @foreach ($category->parameters as $parameter)
                                                            @php
                                                                $reference = $parameter->normal_min !== null || $parameter->normal_max !== null
                                                                    ? trim((string) $parameter->normal_min.' - '.(string) $parameter->normal_max)
                                                                    : ($parameter->normal_text ?? '');
                                                                $optionsCsv = is_array($parameter->options) ? implode(', ', $parameter->options) : '';
                                                            @endphp
                                                            <li>
                                                                <button
                                                                    type="button"
                                                                    class="lms-tree-node lms-tree-leaf"
                                                                    data-node-type="parameter"
                                                                    data-id="{{ $parameter->id }}"
                                                                    data-category-id="{{ $category->id }}"
                                                                    data-parent-id="{{ $category->id }}"
                                                                    data-parent-type="category"
                                                                    data-subcategory-id=""
                                                                    data-name="{{ $parameter->name }}"
                                                                    data-sort-order="{{ $parameter->sort_order }}"
                                                                    data-active="{{ $parameter->is_active ? 1 : 0 }}"
                                                                    data-visible="{{ $parameter->is_visible ? 1 : 0 }}"
                                                                    data-value-type="{{ $parameter->value_type }}"
                                                                    data-unit="{{ $parameter->unit ?? '' }}"
                                                                    data-reference="{{ $reference }}"
                                                                    data-options-csv="{{ $optionsCsv }}"
                                                                    data-default-value="{{ $parameter->default_value ?? '' }}"
                                                                    data-depth="3"
                                                                    data-has-children="0"
                                                                >
                                                                    {{ $parameter->name }}
                                                                </button>
                                                            </li>
                                                        @endforeach

                                                        @foreach ($category->subcategories as $subcategory)
                                                            @include('catalog.partials.subcategory-branch', ['subcategory' => $subcategory, 'category' => $category, 'depth' => 3])
                                                        @endforeach

                                                        @unless ($hasCategoryChildren)
                                                            <li class="lms-tree-empty">{{ __('messages.catalog_no_parameter') }}</li>
                                                        @endunless
                                                    </ul>
                                                </details>
                                            </li>
                                        @endforeach
                                    @endif
                                </ul>
                            </details>
                        </li>
                    @endforeach
                @endif
            </ul>
        </aside>

        <section class="lms-stack lms-editor-panel">
            <article class="lms-card lms-stack">
                <h3>{{ __('messages.catalog_editor_title') }}</h3>
                <p class="lms-muted" data-editor-empty>{{ __('messages.catalog_select_hint') }}</p>

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

                    <form method="POST" data-editor-form="category-delete" data-delete-form>
                        @csrf
                        @method('DELETE')
                        <button class="lms-btn lms-btn-danger" type="submit">{{ __('messages.delete') }}</button>
                    </form>
                </section>

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
                        <label class="lms-field">
                            <span>{{ __('messages.parent_subcategory') }}</span>
                            <select name="parent_subcategory_id" data-editor-input="subcategory-parent">
                                <option value="">{{ __('messages.no_parent_subcategory') }}</option>
                                @foreach ($subcategories->where('depth', 1) as $candidate)
                                    <option
                                        value="{{ $candidate->id }}"
                                        data-category-id="{{ $candidate->category_id }}"
                                        data-depth="{{ $candidate->depth }}"
                                    >
                                        {{ $candidate->name }}
                                    </option>
                                @endforeach
                            </select>
                        </label>
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
                </section>

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
                        <label class="lms-field" data-value-type-field="number text"><span>{{ __('messages.reference') }}</span><input name="reference" data-editor-input="parameter-reference" placeholder="12 - 16"></label>
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
            </article>
        </section>
    </div>

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

    <dialog id="modal-add-analysis" class="lms-modal">
        <article class="lms-modal-card lms-stack">
            <header class="lms-modal-head">
                <h4>{{ __('messages.add_analysis') }}</h4>
                <button type="button" class="lms-modal-close" data-modal-close>&times;</button>
            </header>
            <form method="POST" action="{{ route('catalog.categories.store') }}" class="lms-stack" data-form-create-category>
                @csrf
                <label class="lms-field">
                    <span>{{ __('messages.discipline') }}</span>
                    <select name="discipline_id" required data-create-category-discipline>
                        @foreach ($disciplines as $discipline)
                            <option value="{{ $discipline->id }}">{{ $discipline->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="lms-field"><span>{{ __('messages.analysis') }}</span><input name="name" required></label>
                <div class="lms-inline-actions">
                    <button type="button" class="lms-btn lms-btn-soft" data-modal-close>{{ __('messages.close') }}</button>
                    <button class="lms-btn" type="submit">{{ __('messages.add_analysis') }}</button>
                </div>
            </form>
        </article>
    </dialog>

    <dialog id="modal-add-subcategory" class="lms-modal">
        <article class="lms-modal-card lms-stack">
            <header class="lms-modal-head">
                <h4>{{ __('messages.add_subcategory') }}</h4>
                <button type="button" class="lms-modal-close" data-modal-close>&times;</button>
            </header>
            <form method="POST" action="{{ route('catalog.subcategories.store') }}" class="lms-stack" data-form-create-subcategory>
                @csrf
                <label class="lms-field">
                    <span>{{ __('messages.category') }}</span>
                    <select name="category_id" required data-create-subcategory-category>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="lms-field">
                    <span>{{ __('messages.parent_subcategory') }}</span>
                    <select name="parent_subcategory_id" data-create-subcategory-parent>
                        <option value="">{{ __('messages.no_parent_subcategory') }}</option>
                        @foreach ($subcategories->where('depth', 1) as $candidate)
                            <option
                                value="{{ $candidate->id }}"
                                data-category-id="{{ $candidate->category_id }}"
                                data-depth="{{ $candidate->depth }}"
                            >
                                {{ $candidate->name }}
                            </option>
                        @endforeach
                    </select>
                </label>
                <label class="lms-field"><span>{{ __('messages.subcategory') }}</span><input name="name" required></label>
                <div class="lms-inline-actions">
                    <button type="button" class="lms-btn lms-btn-soft" data-modal-close>{{ __('messages.close') }}</button>
                    <button class="lms-btn" type="submit">{{ __('messages.add_subcategory') }}</button>
                </div>
            </form>
        </article>
    </dialog>

    <dialog id="modal-add-sub-analysis" class="lms-modal">
        <article class="lms-modal-card lms-stack">
            <header class="lms-modal-head">
                <h4>{{ __('messages.add_sub_analysis') }}</h4>
                <button type="button" class="lms-modal-close" data-modal-close>&times;</button>
            </header>
            <form method="POST" action="{{ route('catalog.parameters.store') }}" class="lms-stack" data-form-create-parameter>
                @csrf
                <label class="lms-field">
                    <span>{{ __('messages.category') }}</span>
                    <select name="category_id" required data-create-parameter-category>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="lms-field">
                    <span>{{ __('messages.subcategory') }}</span>
                    <select name="subcategory_id" data-create-parameter-subcategory>
                        <option value="">{{ __('messages.no_subcategory') }}</option>
                        @foreach ($subcategories as $subcategory)
                            <option value="{{ $subcategory->id }}" data-category-id="{{ $subcategory->category_id }}">
                                {{ $subcategory->name }}
                            </option>
                        @endforeach
                    </select>
                </label>
                <label class="lms-field"><span>{{ __('messages.subcategory') }}</span><input name="name" required></label>
                <input type="hidden" name="value_type" value="number" data-value-type-hidden>
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
                <label class="lms-field" data-value-type-field="number"><span>{{ __('messages.unit') }}</span><input name="unit"></label>
                <label class="lms-field" data-value-type-field="number text"><span>{{ __('messages.reference') }}</span><input name="reference" placeholder="12 - 16"></label>
                <label class="lms-field" data-value-type-field="list"><span>{{ __('messages.options_csv') }}</span><input name="options_csv" placeholder="NEGATIF, POSITIF"></label>
                <label class="lms-checkbox" data-value-type-field="list">
                    <input type="checkbox" data-default-option-toggle>
                    <span>{{ __('messages.default_option') }}</span>
                </label>
                <label class="lms-field" data-value-type-field="list" data-default-option-wrap hidden>
                    <span>{{ __('messages.default_option_value') }}</span>
                    <input name="default_option_value" data-default-option-input>
                </label>
                <div class="lms-inline-actions">
                    <button type="button" class="lms-btn lms-btn-soft" data-modal-close>{{ __('messages.close') }}</button>
                    <button class="lms-btn" type="submit">{{ __('messages.add_sub_analysis') }}</button>
                </div>
            </form>
        </article>
    </dialog>

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
                <button type="button" class="lms-btn lms-btn-soft" data-modal-close>{{ __('messages.close') }}</button>
                <button class="lms-btn lms-btn-danger" type="submit">{{ __('messages.delete') }}</button>
            </form>
        </article>
    </dialog>

    <script>
        (() => {
            const editorRoot = document.querySelector('[data-catalog-editor]');

            if (!editorRoot) {
                return;
            }

            const labels = {
                confirmDelete: @json(__('messages.confirm_delete')),
                confirmDeleteWithChildren: @json(__('messages.confirm_delete_with_children')),
                reorderFailed: @json(__('messages.catalog_reorder_failed')),
            };

            const routeMap = {
                disciplineUpdate: editorRoot.dataset.routeDisciplineUpdate,
                disciplineDelete: editorRoot.dataset.routeDisciplineDelete,
                categoryUpdate: editorRoot.dataset.routeCategoryUpdate,
                categoryDelete: editorRoot.dataset.routeCategoryDelete,
                subcategoryUpdate: editorRoot.dataset.routeSubcategoryUpdate,
                subcategoryDelete: editorRoot.dataset.routeSubcategoryDelete,
                parameterStore: editorRoot.dataset.routeParameterStore,
                parameterUpdate: editorRoot.dataset.routeParameterUpdate,
                parameterDelete: editorRoot.dataset.routeParameterDelete,
                reorder: editorRoot.dataset.routeReorder,
            };

            const sections = {
                discipline: editorRoot.querySelector('[data-editor-section="discipline"]'),
                category: editorRoot.querySelector('[data-editor-section="category"]'),
                subcategory: editorRoot.querySelector('[data-editor-section="subcategory"]'),
                parameter: editorRoot.querySelector('[data-editor-section="parameter"]'),
            };

            const emptyState = editorRoot.querySelector('[data-editor-empty]');
            const addChildButton = document.querySelector('[data-add-child]');
            const deleteConfirmDialog = document.getElementById('modal-confirm-delete');
            const deleteConfirmForm = deleteConfirmDialog?.querySelector('[data-delete-confirm-form]');
            const deleteMessage = deleteConfirmDialog?.querySelector('[data-delete-message]');

            const nodeElements = Array.from(editorRoot.querySelectorAll('[data-node-type]'));
            const draftState = new Map();
            const typeFormState = new WeakMap();
            let currentSelection = null;
            let dragState = null;

            const fillRoute = (template, id) => template.replace('__ID__', String(id));

            const openDialog = (dialog) => {
                if (!dialog) {
                    return;
                }

                if (!dialog.open) {
                    dialog.showModal();
                }
            };

            const closeDialog = (dialog) => {
                if (dialog?.open) {
                    dialog.close();
                }
            };

            const getNodeElements = () => Array.from(editorRoot.querySelectorAll('[data-node-type]'));

            const getSupportedTypes = (field) => {
                const rawValue = field.dataset.valueTypeField ?? '';

                return rawValue
                    .split(' ')
                    .map((value) => value.trim())
                    .filter((value) => value !== '');
            };

            const initializeValueTypeForm = (form) => {
                const hiddenInput = form.querySelector('[data-value-type-hidden]');
                const typeChoices = Array.from(form.querySelectorAll('[data-value-type-choice]'));

                if (!hiddenInput || typeChoices.length === 0) {
                    return;
                }

                const typedFields = Array.from(form.querySelectorAll('[data-value-type-field]'));
                const defaultToggle = form.querySelector('[data-default-option-toggle]');
                const defaultWrap = form.querySelector('[data-default-option-wrap]');
                const defaultInput = form.querySelector('[data-default-option-input]');
                const validTypes = new Set(['number', 'text', 'list']);

                const refresh = () => {
                    let activeType = hiddenInput.value;

                    if (!validTypes.has(activeType)) {
                        activeType = 'number';
                        hiddenInput.value = activeType;
                    }

                    typeChoices.forEach((choice) => {
                        choice.checked = choice.value === activeType;
                    });

                    typedFields.forEach((field) => {
                        const visible = getSupportedTypes(field).includes(activeType);
                        field.hidden = !visible;

                        field.querySelectorAll('input, select, textarea').forEach((input) => {
                            if (input === hiddenInput || input.type === 'hidden') {
                                return;
                            }

                            if (input === defaultToggle) {
                                return;
                            }

                            input.disabled = !visible;
                        });
                    });

                    if (!defaultToggle || !defaultWrap || !defaultInput) {
                        return;
                    }

                    const optionsMode = activeType === 'list';
                    defaultToggle.disabled = !optionsMode;

                    if (!optionsMode) {
                        defaultToggle.checked = false;
                        defaultWrap.hidden = true;
                        defaultInput.disabled = true;
                        defaultInput.value = '';
                        return;
                    }

                    defaultWrap.hidden = !defaultToggle.checked;
                    defaultInput.disabled = !defaultToggle.checked;

                    if (!defaultToggle.checked) {
                        defaultInput.value = '';
                    }
                };

                typeChoices.forEach((choice) => {
                    choice.addEventListener('change', () => {
                        if (choice.checked) {
                            hiddenInput.value = choice.value;
                            refresh();
                            return;
                        }

                        if (hiddenInput.value === choice.value) {
                            choice.checked = true;
                        }
                    });
                });

                if (defaultToggle) {
                    defaultToggle.addEventListener('change', () => {
                        refresh();
                    });
                }

                typeFormState.set(form, { refresh });
                refresh();
            };

            const refreshValueTypeForm = (form) => {
                const state = typeFormState.get(form);

                if (state) {
                    state.refresh();
                }
            };

            const refreshValueTypeFormsInSection = (section) => {
                if (!section) {
                    return;
                }

                section.querySelectorAll('form').forEach((form) => refreshValueTypeForm(form));
            };

            const syncSubcategoryOptions = (categorySelect, subcategorySelect) => {
                if (!categorySelect || !subcategorySelect) {
                    return;
                }

                const selectedCategory = categorySelect.value;
                const options = subcategorySelect.querySelectorAll('option[data-category-id]');

                options.forEach((option) => {
                    option.hidden = option.dataset.categoryId !== selectedCategory;
                });

                const currentValue = subcategorySelect.value;
                const selectedOption = Array.from(subcategorySelect.options).find((option) => option.value === currentValue);

                if (currentValue !== '' && selectedOption && selectedOption.hidden) {
                    subcategorySelect.value = '';
                }
            };

            const syncParentSubcategoryOptions = (categorySelect, parentSelect, currentSubcategoryId = '') => {
                if (!categorySelect || !parentSelect) {
                    return;
                }

                const selectedCategory = categorySelect.value;
                const options = parentSelect.querySelectorAll('option[data-category-id]');

                options.forEach((option) => {
                    const wrongCategory = option.dataset.categoryId !== selectedCategory;
                    const tooDeep = Number(option.dataset.depth ?? '0') >= 2;
                    const sameAsCurrent = currentSubcategoryId !== '' && option.value === currentSubcategoryId;

                    option.hidden = wrongCategory || tooDeep || sameAsCurrent;
                });

                const currentValue = parentSelect.value;
                const selectedOption = Array.from(parentSelect.options).find((option) => option.value === currentValue);

                if (currentValue !== '' && selectedOption && selectedOption.hidden) {
                    parentSelect.value = '';
                }
            };

            const getParentNode = (node) => {
                const item = node.closest('li');
                const list = item ? item.parentElement : null;
                const parentDetails = list ? list.closest('details') : null;

                if (!parentDetails) {
                    return null;
                }

                return parentDetails.querySelector(':scope > summary.lms-tree-node');
            };

            const clearNodeHighlights = () => {
                getNodeElements().forEach((node) => {
                    node.classList.remove(
                        'is-selected',
                        'is-path-node',
                        'is-path-discipline',
                        'is-path-category',
                        'is-path-sub',
                        'is-path-sub-deep',
                        'is-path-leaf'
                    );
                });
            };

            const applyPathRoleClass = (node) => {
                const type = node.dataset.nodeType;

                if (type === 'discipline') {
                    node.classList.add('is-path-discipline');
                    return;
                }

                if (type === 'category') {
                    node.classList.add('is-path-category');
                    return;
                }

                if (type === 'subcategory') {
                    const depth = Number(node.dataset.depth ?? '0');
                    node.classList.add(depth >= 4 ? 'is-path-sub-deep' : 'is-path-sub');
                    return;
                }

                node.classList.add('is-path-leaf');
            };

            const setSelectedPath = (activeNode) => {
                clearNodeHighlights();

                let cursor = activeNode;

                while (cursor) {
                    cursor.classList.add('is-path-node');
                    applyPathRoleClass(cursor);

                    if (cursor === activeNode) {
                        cursor.classList.add('is-selected');
                    }

                    cursor = getParentNode(cursor);
                }
            };

            const hideSections = () => {
                Object.values(sections).forEach((section) => {
                    if (section) {
                        section.hidden = true;
                    }
                });
            };

            const setFormAction = (formSelector, route) => {
                const form = editorRoot.querySelector(formSelector);

                if (form) {
                    form.setAttribute('action', route);
                }
            };

            const captureSectionState = (type) => {
                const section = sections[type];

                if (!section) {
                    return {};
                }

                const state = {};

                section.querySelectorAll('[data-editor-input]').forEach((input) => {
                    const key = input.dataset.editorInput;

                    if (input.type === 'checkbox') {
                        state[key] = input.checked;
                        return;
                    }

                    state[key] = input.value;
                });

                return state;
            };

            const applySectionState = (type, state) => {
                const section = sections[type];

                if (!section) {
                    return;
                }

                section.querySelectorAll('[data-editor-input]').forEach((input) => {
                    const key = input.dataset.editorInput;

                    if (!Object.prototype.hasOwnProperty.call(state, key)) {
                        return;
                    }

                    if (input.type === 'checkbox') {
                        input.checked = Boolean(state[key]);
                        return;
                    }

                    input.value = state[key] ?? '';
                });

                refreshValueTypeFormsInSection(section);
            };

            const persistCurrentDraft = () => {
                if (!currentSelection) {
                    return;
                }

                draftState.set(currentSelection.key, captureSectionState(currentSelection.type));
            };

            const selectionKey = (node) => `${node.dataset.nodeType}:${node.dataset.id}`;

            const populateDisciplineSection = (node) => {
                const section = sections.discipline;

                if (!section) {
                    return;
                }

                section.querySelector('[data-editor-input="discipline-name"]').value = node.dataset.name ?? '';
                section.querySelector('[data-editor-input="discipline-active"]').checked = (node.dataset.active ?? '0') === '1';

                setFormAction('[data-editor-form="discipline-update"]', fillRoute(routeMap.disciplineUpdate, node.dataset.id));
                setFormAction('[data-editor-form="discipline-delete"]', fillRoute(routeMap.disciplineDelete, node.dataset.id));
            };

            const populateCategorySection = (node) => {
                const section = sections.category;

                if (!section) {
                    return;
                }

                section.querySelector('[data-editor-input="category-discipline"]').value = node.dataset.parentId ?? '';
                section.querySelector('[data-editor-input="category-name"]').value = node.dataset.name ?? '';
                section.querySelector('[data-editor-input="category-active"]').checked = (node.dataset.active ?? '0') === '1';

                setFormAction('[data-editor-form="category-update"]', fillRoute(routeMap.categoryUpdate, node.dataset.id));
                setFormAction('[data-editor-form="category-delete"]', fillRoute(routeMap.categoryDelete, node.dataset.id));
            };

            const populateSubcategorySection = (node) => {
                const section = sections.subcategory;

                if (!section) {
                    return;
                }

                const categoryInput = section.querySelector('[data-editor-input="subcategory-category"]');
                const parentInput = section.querySelector('[data-editor-input="subcategory-parent"]');

                if (!categoryInput || !parentInput) {
                    return;
                }

                categoryInput.value = node.dataset.categoryId ?? '';
                syncParentSubcategoryOptions(categoryInput, parentInput, node.dataset.id ?? '');
                parentInput.value = node.dataset.parentSubcategoryId ?? '';

                section.querySelector('[data-editor-input="subcategory-name"]').value = node.dataset.name ?? '';
                section.querySelector('[data-editor-input="subcategory-active"]').checked = (node.dataset.active ?? '0') === '1';

                setFormAction('[data-editor-form="subcategory-update"]', fillRoute(routeMap.subcategoryUpdate, node.dataset.id));
                setFormAction('[data-editor-form="subcategory-delete"]', fillRoute(routeMap.subcategoryDelete, node.dataset.id));
            };

            const populateParameterSection = (node) => {
                const section = sections.parameter;

                if (!section) {
                    return;
                }

                const categoryInput = section.querySelector('[data-editor-input="parameter-category"]');
                const subcategoryInput = section.querySelector('[data-editor-input="parameter-subcategory"]');
                const parameterForm = section.querySelector('[data-editor-form="parameter-update"]');

                if (!categoryInput || !subcategoryInput || !parameterForm) {
                    return;
                }

                categoryInput.value = node.dataset.categoryId ?? '';
                syncSubcategoryOptions(categoryInput, subcategoryInput);
                subcategoryInput.value = node.dataset.subcategoryId ?? '';

                section.querySelector('[data-editor-input="parameter-name"]').value = node.dataset.name ?? '';
                section.querySelector('[data-editor-input="parameter-value-type"]').value = node.dataset.valueType ?? 'number';
                section.querySelector('[data-editor-input="parameter-unit"]').value = node.dataset.unit ?? '';
                section.querySelector('[data-editor-input="parameter-reference"]').value = node.dataset.reference ?? '';
                section.querySelector('[data-editor-input="parameter-options"]').value = node.dataset.optionsCsv ?? '';

                const defaultToggleInput = section.querySelector('[data-editor-input="parameter-default-toggle"]');
                const defaultOptionInput = section.querySelector('[data-editor-input="parameter-default-option"]');

                if (defaultToggleInput && defaultOptionInput) {
                    defaultOptionInput.value = node.dataset.defaultValue ?? '';
                    defaultToggleInput.checked = defaultOptionInput.value !== '';
                }

                section.querySelector('[data-editor-input="parameter-visible"]').checked = (node.dataset.visible ?? '0') === '1';
                section.querySelector('[data-editor-input="parameter-active"]').checked = (node.dataset.active ?? '0') === '1';

                setFormAction('[data-editor-form="parameter-update"]', fillRoute(routeMap.parameterUpdate, node.dataset.id));
                setFormAction('[data-editor-form="parameter-delete"]', fillRoute(routeMap.parameterDelete, node.dataset.id));
                refreshValueTypeForm(parameterForm);
            };

            const populateSectionFromNode = (type, node) => {
                if (type === 'discipline') {
                    populateDisciplineSection(node);
                    return;
                }

                if (type === 'category') {
                    populateCategorySection(node);
                    return;
                }

                if (type === 'subcategory') {
                    populateSubcategorySection(node);
                    return;
                }

                if (type === 'parameter') {
                    populateParameterSection(node);
                }
            };

            const setAddChildAvailability = (node) => {
                if (!addChildButton) {
                    return;
                }

                if (!node) {
                    addChildButton.disabled = true;
                    return;
                }

                addChildButton.disabled = node.dataset.nodeType === 'parameter';
            };

            const showNodeEditor = (node) => {
                const type = node.dataset.nodeType;
                const section = sections[type];

                if (!section) {
                    return;
                }

                persistCurrentDraft();
                setSelectedPath(node);
                hideSections();

                if (emptyState) {
                    emptyState.hidden = true;
                }

                section.hidden = false;
                populateSectionFromNode(type, node);

                const key = selectionKey(node);

                if (draftState.has(key)) {
                    applySectionState(type, draftState.get(key));

                    if (type === 'subcategory') {
                        const categoryInput = section.querySelector('[data-editor-input="subcategory-category"]');
                        const parentInput = section.querySelector('[data-editor-input="subcategory-parent"]');
                        syncParentSubcategoryOptions(categoryInput, parentInput, node.dataset.id ?? '');
                    }

                    if (type === 'parameter') {
                        const categoryInput = section.querySelector('[data-editor-input="parameter-category"]');
                        const subcategoryInput = section.querySelector('[data-editor-input="parameter-subcategory"]');
                        syncSubcategoryOptions(categoryInput, subcategoryInput);
                    }
                }

                refreshValueTypeFormsInSection(section);

                currentSelection = {
                    key,
                    type,
                    node,
                };

                setAddChildAvailability(node);
            };

            const handleAddChild = () => {
                if (!currentSelection) {
                    return;
                }

                const node = currentSelection.node;
                const type = node.dataset.nodeType;

                if (type === 'discipline') {
                    const modal = document.getElementById('modal-add-analysis');
                    const disciplineSelect = modal?.querySelector('[data-create-category-discipline]');

                    if (disciplineSelect) {
                        disciplineSelect.value = node.dataset.id ?? '';
                    }

                    openDialog(modal);
                    return;
                }

                if (type === 'category') {
                    const modal = document.getElementById('modal-add-sub-analysis');
                    const categorySelect = modal?.querySelector('[data-create-parameter-category]');
                    const subcategorySelect = modal?.querySelector('[data-create-parameter-subcategory]');

                    if (categorySelect && subcategorySelect) {
                        categorySelect.value = node.dataset.id ?? '';
                        syncSubcategoryOptions(categorySelect, subcategorySelect);
                        subcategorySelect.value = '';
                    }

                    openDialog(modal);
                    return;
                }

                if (type === 'subcategory') {
                    const subcategoryDepth = Number(node.dataset.subcategoryDepth ?? '1');

                    if (subcategoryDepth >= 2) {
                        const modal = document.getElementById('modal-add-sub-analysis');
                        const categorySelect = modal?.querySelector('[data-create-parameter-category]');
                        const subcategorySelect = modal?.querySelector('[data-create-parameter-subcategory]');

                        if (categorySelect && subcategorySelect) {
                            categorySelect.value = node.dataset.categoryId ?? '';
                            syncSubcategoryOptions(categorySelect, subcategorySelect);
                            subcategorySelect.value = node.dataset.id ?? '';
                        }

                        openDialog(modal);
                        return;
                    }

                    const modal = document.getElementById('modal-add-subcategory');
                    const categoryInput = modal?.querySelector('[data-create-subcategory-category]');
                    const parentInput = modal?.querySelector('[data-create-subcategory-parent]');

                    if (categoryInput && parentInput) {
                        categoryInput.value = node.dataset.categoryId ?? '';
                        syncParentSubcategoryOptions(categoryInput, parentInput);
                        parentInput.value = node.dataset.id ?? '';
                    }

                    openDialog(modal);
                }
            };

            const isDraggableNode = (node) =>
                (node.dataset.nodeType === 'category' || node.dataset.nodeType === 'subcategory')
                && node.dataset.dragEnabled === '1';

            const isValidDropTarget = (draggedNode, targetNode) => {
                if (!draggedNode || !targetNode || draggedNode === targetNode) {
                    return false;
                }

                if (draggedNode.dataset.nodeType !== targetNode.dataset.nodeType) {
                    return false;
                }

                const draggedItem = draggedNode.closest('li');
                const targetItem = targetNode.closest('li');

                if (!draggedItem || !targetItem) {
                    return false;
                }

                return draggedItem.parentElement === targetItem.parentElement;
            };

            const collectOrderedIds = (list, nodeType) => Array.from(list.children)
                .map((item) => item.querySelector(':scope > details > summary.lms-tree-node'))
                .filter((summary) => summary && summary.dataset.nodeType === nodeType)
                .map((summary) => Number(summary.dataset.id))
                .filter((id) => Number.isInteger(id));

            const persistReorder = async (node, list) => {
                if (!routeMap.reorder) {
                    return;
                }

                const orderedIds = collectOrderedIds(list, node.dataset.nodeType);

                if (orderedIds.length === 0) {
                    return;
                }

                const payload = node.dataset.nodeType === 'category'
                    ? {
                        type: 'category',
                        discipline_id: Number(node.dataset.parentId ?? 0),
                        ordered_ids: orderedIds,
                    }
                    : {
                        type: 'subcategory',
                        category_id: Number(node.dataset.categoryId ?? 0),
                        parent_subcategory_id: node.dataset.parentType === 'subcategory'
                            ? Number(node.dataset.parentId ?? 0)
                            : null,
                        ordered_ids: orderedIds,
                    };

                const response = await fetch(routeMap.reorder, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': editorRoot.dataset.csrfToken ?? '',
                    },
                    body: JSON.stringify(payload),
                });

                if (!response.ok) {
                    let message = labels.reorderFailed;

                    try {
                        const data = await response.json();
                        const firstError = data?.errors ? Object.values(data.errors)?.[0]?.[0] : null;
                        message = firstError || data?.message || message;
                    } catch {
                        message = labels.reorderFailed;
                    }

                    throw new Error(message);
                }
            };

            editorRoot.querySelectorAll('[data-drag-handle]').forEach((handle) => {
                handle.addEventListener('click', (event) => {
                    event.preventDefault();
                    event.stopPropagation();
                });
            });

            nodeElements.forEach((node) => {
                node.addEventListener('click', () => {
                    if (dragState) {
                        return;
                    }

                    showNodeEditor(node);
                });

                if (!isDraggableNode(node)) {
                    return;
                }

                node.addEventListener('dragstart', (event) => {
                    const item = node.closest('li');
                    const list = item?.parentElement;

                    if (!item || !list) {
                        event.preventDefault();
                        return;
                    }

                    dragState = {
                        node,
                        item,
                        list,
                        moved: false,
                    };

                    item.classList.add('is-dragging');

                    if (event.dataTransfer) {
                        event.dataTransfer.effectAllowed = 'move';
                        event.dataTransfer.setData('text/plain', selectionKey(node));
                    }
                });

                node.addEventListener('dragend', async () => {
                    if (!dragState || dragState.node !== node) {
                        return;
                    }

                    const { list, moved, item } = dragState;
                    item.classList.remove('is-dragging');
                    dragState = null;

                    if (!moved) {
                        return;
                    }

                    try {
                        await persistReorder(node, list);
                    } catch (error) {
                        window.alert(error?.message || labels.reorderFailed);
                    }
                });
            });

            editorRoot.addEventListener('dragover', (event) => {
                if (!dragState) {
                    return;
                }

                const targetNode = event.target.closest('[data-node-type]');

                if (!isValidDropTarget(dragState.node, targetNode)) {
                    return;
                }

                const targetItem = targetNode.closest('li');

                if (!targetItem || targetItem === dragState.item) {
                    return;
                }

                event.preventDefault();

                const rect = targetNode.getBoundingClientRect();
                const insertAfter = event.clientY > rect.top + (rect.height / 2);
                const referenceNode = insertAfter ? targetItem.nextElementSibling : targetItem;
                dragState.list.insertBefore(dragState.item, referenceNode);
                dragState.moved = true;
            });

            editorRoot.addEventListener('drop', (event) => {
                if (dragState) {
                    event.preventDefault();
                }
            });

            Object.entries(sections).forEach(([type, section]) => {
                if (!section) {
                    return;
                }

                section.querySelectorAll('[data-editor-input]').forEach((input) => {
                    const capture = () => {
                        if (!currentSelection || currentSelection.type !== type) {
                            return;
                        }

                        draftState.set(currentSelection.key, captureSectionState(type));
                    };

                    input.addEventListener('input', capture);
                    input.addEventListener('change', capture);
                });

                const cancelButton = section.querySelector(`[data-editor-cancel="${type}"]`);

                if (cancelButton) {
                    cancelButton.addEventListener('click', () => {
                        if (!currentSelection || currentSelection.type !== type) {
                            return;
                        }

                        draftState.delete(currentSelection.key);
                        populateSectionFromNode(type, currentSelection.node);
                        refreshValueTypeFormsInSection(section);
                    });
                }
            });

            document.querySelectorAll('form').forEach((form) => initializeValueTypeForm(form));

            const parameterCategoryInput = editorRoot.querySelector('[data-editor-input="parameter-category"]');
            const parameterSubcategoryInput = editorRoot.querySelector('[data-editor-input="parameter-subcategory"]');

            if (parameterCategoryInput && parameterSubcategoryInput) {
                parameterCategoryInput.addEventListener('change', () => {
                    syncSubcategoryOptions(parameterCategoryInput, parameterSubcategoryInput);
                });
                syncSubcategoryOptions(parameterCategoryInput, parameterSubcategoryInput);
            }

            const subcategoryCategoryInput = editorRoot.querySelector('[data-editor-input="subcategory-category"]');
            const subcategoryParentInput = editorRoot.querySelector('[data-editor-input="subcategory-parent"]');

            if (subcategoryCategoryInput && subcategoryParentInput) {
                subcategoryCategoryInput.addEventListener('change', () => {
                    const currentId = currentSelection?.type === 'subcategory' ? currentSelection.node.dataset.id ?? '' : '';
                    syncParentSubcategoryOptions(subcategoryCategoryInput, subcategoryParentInput, currentId);
                });
                syncParentSubcategoryOptions(subcategoryCategoryInput, subcategoryParentInput);
            }

            const createParameterCategory = document.querySelector('[data-create-parameter-category]');
            const createParameterSubcategory = document.querySelector('[data-create-parameter-subcategory]');

            if (createParameterCategory && createParameterSubcategory) {
                createParameterCategory.addEventListener('change', () => {
                    syncSubcategoryOptions(createParameterCategory, createParameterSubcategory);
                });
                syncSubcategoryOptions(createParameterCategory, createParameterSubcategory);
            }

            const createSubcategoryCategory = document.querySelector('[data-create-subcategory-category]');
            const createSubcategoryParent = document.querySelector('[data-create-subcategory-parent]');

            if (createSubcategoryCategory && createSubcategoryParent) {
                createSubcategoryCategory.addEventListener('change', () => {
                    syncParentSubcategoryOptions(createSubcategoryCategory, createSubcategoryParent);
                });
                syncParentSubcategoryOptions(createSubcategoryCategory, createSubcategoryParent);
            }

            if (addChildButton) {
                addChildButton.addEventListener('click', handleAddChild);
            }

            const rootBranches = Array.from(editorRoot.querySelectorAll('[data-root-branch]'));
            const openRootOrder = [];

            rootBranches.forEach((branch) => {
                if (branch.open) {
                    openRootOrder.push(branch);
                }

                branch.addEventListener('toggle', () => {
                    const existingIndex = openRootOrder.indexOf(branch);

                    if (!branch.open) {
                        if (existingIndex !== -1) {
                            openRootOrder.splice(existingIndex, 1);
                        }
                        return;
                    }

                    if (existingIndex !== -1) {
                        openRootOrder.splice(existingIndex, 1);
                    }

                    openRootOrder.push(branch);

                    if (openRootOrder.length <= 3) {
                        return;
                    }

                    const oldestBranch = openRootOrder.shift();

                    if (oldestBranch && oldestBranch !== branch) {
                        oldestBranch.open = false;
                    }
                });
            });

            const dialogs = document.querySelectorAll('.lms-modal');

            document.querySelectorAll('[data-modal-open]').forEach((trigger) => {
                trigger.addEventListener('click', () => {
                    const dialog = document.getElementById(trigger.dataset.modalOpen);
                    openDialog(dialog);
                });
            });

            document.querySelectorAll('[data-modal-close]').forEach((trigger) => {
                trigger.addEventListener('click', () => {
                    const dialog = trigger.closest('dialog');
                    closeDialog(dialog);
                });
            });

            dialogs.forEach((dialog) => {
                dialog.addEventListener('click', (event) => {
                    const rect = dialog.getBoundingClientRect();
                    const isInDialog =
                        rect.top <= event.clientY &&
                        event.clientY <= rect.top + rect.height &&
                        rect.left <= event.clientX &&
                        event.clientX <= rect.left + rect.width;

                    if (!isInDialog) {
                        closeDialog(dialog);
                    }
                });
            });

            editorRoot.querySelectorAll('form[data-delete-form]').forEach((form) => {
                form.addEventListener('submit', (event) => {
                    if (!deleteConfirmDialog || !deleteConfirmForm || !deleteMessage) {
                        return;
                    }

                    event.preventDefault();
                    deleteConfirmForm.setAttribute('action', form.getAttribute('action') || '');

                    const hasChildren = currentSelection?.node?.dataset?.hasChildren === '1';
                    deleteMessage.textContent = hasChildren ? labels.confirmDeleteWithChildren : labels.confirmDelete;

                    openDialog(deleteConfirmDialog);
                });
            });

            setAddChildAvailability(null);
        })();
    </script>
@endsection
