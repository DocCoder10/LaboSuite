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
                                    data-drag-enabled="1"
                                    data-id="{{ $discipline->id }}"
                                    data-name="{{ $discipline->name }}"
                                    data-sort-order="{{ $discipline->sort_order }}"
                                    data-active="{{ $discipline->is_active ? 1 : 0 }}"
                                    data-depth="1"
                                    data-has-children="{{ $disciplineHasChildren ? 1 : 0 }}"
                                    data-has-parameters="0"
                                    draggable="true"
                                >
                                    @if ($disciplineHasChildren)
                                        <span class="lms-tree-arrow" aria-hidden="true"></span>
                                    @endif
                                    <span class="lms-tree-label">{{ $discipline->name }}</span>
                                    <span class="lms-tree-drag-handle" data-drag-handle title="{{ __('messages.drag_to_reorder') }}" aria-label="{{ __('messages.drag_to_reorder') }}">⋮⋮</span>
                                </summary>

                                <ul class="lms-tree-children">
                                    @if ($discipline->categories->isEmpty())
                                        <li class="lms-tree-empty">{{ __('messages.catalog_no_category') }}</li>
                                    @else
                                        @foreach ($discipline->categories as $category)
                                            <li>
                                                @php
                                                    $hasSubcategories = $category->subcategories->isNotEmpty();
                                                    $hasCategoryChildren = $hasSubcategories;
                                                    $leafParameter = $category->parameters->firstWhere('subcategory_id', null);
                                                    $hasLeafParameter = $leafParameter !== null;
                                                    $leafReference = $leafParameter && ($leafParameter->normal_min !== null || $leafParameter->normal_max !== null)
                                                        ? trim((string) $leafParameter->normal_min.' - '.(string) $leafParameter->normal_max)
                                                        : ($leafParameter?->normal_text ?? '');
                                                    $leafOptionsCsv = $leafParameter && is_array($leafParameter->options)
                                                        ? implode(', ', $leafParameter->options)
                                                        : '';
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
                                                        data-has-parameters="{{ $hasLeafParameter ? 1 : 0 }}"
                                                        data-leaf-parameter-id="{{ $leafParameter?->id ?? '' }}"
                                                        data-leaf-parameter-name="{{ $leafParameter?->name ?? '' }}"
                                                        data-leaf-parameter-active="{{ $leafParameter?->is_active ? 1 : 0 }}"
                                                        data-leaf-parameter-visible="{{ $leafParameter?->is_visible ? 1 : 0 }}"
                                                        data-leaf-parameter-value-type="{{ $leafParameter?->value_type ?? 'number' }}"
                                                        data-leaf-parameter-unit="{{ $leafParameter?->unit ?? '' }}"
                                                        data-leaf-parameter-reference="{{ $leafReference }}"
                                                        data-leaf-parameter-options-csv="{{ $leafOptionsCsv }}"
                                                        data-leaf-parameter-default-value="{{ $leafParameter?->default_value ?? '' }}"
                                                        draggable="true"
                                                    >
                                                        @if ($hasCategoryChildren)
                                                            <span class="lms-tree-arrow" aria-hidden="true"></span>
                                                        @endif
                                                        <span class="lms-tree-label">{{ $category->name }}</span>
                                                        <span class="lms-tree-drag-handle" data-drag-handle title="{{ __('messages.drag_to_reorder') }}" aria-label="{{ __('messages.drag_to_reorder') }}">⋮⋮</span>
                                                    </summary>

                                                    <ul class="lms-tree-children">
                                                        @foreach ($category->subcategories as $subcategory)
                                                            @include('catalog.partials.subcategory-branch', ['subcategory' => $subcategory, 'category' => $category, 'depth' => 3])
                                                        @endforeach

                                                        @if (! $hasCategoryChildren && ! $hasLeafParameter)
                                                            <li class="lms-tree-empty">{{ __('messages.catalog_no_parameter') }}</li>
                                                        @endif
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
