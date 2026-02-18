@php
    $childDepth = $depth + 1;
    $hasSubcategoryChildren = $subcategory->parameters->isNotEmpty() || $subcategory->children->isNotEmpty();
@endphp

<li>
    <details class="lms-tree-branch lms-tree-level-subcategory {{ $depth > 1 ? 'lms-tree-level-subcategory-nested' : '' }}">
        <summary
            class="lms-tree-node lms-tree-summary"
            data-node-type="subcategory"
            data-id="{{ $subcategory->id }}"
            data-category-id="{{ $category->id }}"
            data-parent-id="{{ $subcategory->parent_subcategory_id ?? $category->id }}"
            data-parent-type="{{ $subcategory->parent_subcategory_id ? 'subcategory' : 'category' }}"
            data-parent-subcategory-id="{{ $subcategory->parent_subcategory_id ?? '' }}"
            data-name="{{ $subcategory->name }}"
            data-sort-order="{{ $subcategory->sort_order }}"
            data-active="{{ $subcategory->is_active ? 1 : 0 }}"
            data-depth="{{ $depth }}"
            data-subcategory-depth="{{ $subcategory->depth }}"
            data-has-children="{{ $hasSubcategoryChildren ? 1 : 0 }}"
        >
            <span class="lms-tree-arrow" aria-hidden="true"></span>
            <span class="lms-tree-label">{{ $subcategory->name }}</span>
        </summary>

        <ul class="lms-tree-children">
            @foreach ($subcategory->parameters as $parameter)
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
                        data-parent-id="{{ $subcategory->id }}"
                        data-parent-type="subcategory"
                        data-subcategory-id="{{ $subcategory->id }}"
                        data-name="{{ $parameter->name }}"
                        data-sort-order="{{ $parameter->sort_order }}"
                        data-active="{{ $parameter->is_active ? 1 : 0 }}"
                        data-visible="{{ $parameter->is_visible ? 1 : 0 }}"
                        data-value-type="{{ $parameter->value_type }}"
                        data-unit="{{ $parameter->unit ?? '' }}"
                        data-reference="{{ $reference }}"
                        data-options-csv="{{ $optionsCsv }}"
                        data-depth="{{ $childDepth }}"
                        data-has-children="0"
                    >
                        {{ $parameter->name }}
                    </button>
                </li>
            @endforeach

            @foreach ($subcategory->children as $child)
                @include('catalog.partials.subcategory-branch', ['subcategory' => $child, 'category' => $category, 'depth' => $childDepth])
            @endforeach

            @if ($subcategory->parameters->isEmpty() && $subcategory->children->isEmpty())
                <li class="lms-tree-empty">{{ __('messages.catalog_no_parameter') }}</li>
            @endif
        </ul>
    </details>
</li>
