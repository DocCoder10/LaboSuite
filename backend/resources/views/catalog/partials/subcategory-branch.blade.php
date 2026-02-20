@php
    $childDepth = $depth + 1;
    $leafParameter = $subcategory->parameters->first();
    $hasSubcategoryParameters = $leafParameter !== null;
    $hasSubcategoryChildren = $subcategory->children->isNotEmpty();
    $leafReference = $leafParameter && ($leafParameter->normal_min !== null || $leafParameter->normal_max !== null)
        ? trim((string) $leafParameter->normal_min.' - '.(string) $leafParameter->normal_max)
        : ($leafParameter?->normal_text ?? '');
    $leafOptionsCsv = $leafParameter && is_array($leafParameter->options)
        ? implode(', ', $leafParameter->options)
        : '';
@endphp

<li>
    <details class="lms-tree-branch lms-tree-level-subcategory {{ $depth > 1 ? 'lms-tree-level-subcategory-nested' : '' }}">
        <summary
            class="lms-tree-node lms-tree-summary"
            data-node-type="subcategory"
            data-drag-enabled="1"
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
            data-has-parameters="{{ $hasSubcategoryParameters ? 1 : 0 }}"
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
            @if ($hasSubcategoryChildren)
                <span class="lms-tree-arrow" aria-hidden="true"></span>
            @endif
            <span class="lms-tree-label">{{ $subcategory->name }}</span>
            <span class="lms-tree-drag-handle" data-drag-handle title="{{ __('messages.drag_to_reorder') }}" aria-label="{{ __('messages.drag_to_reorder') }}">⋮⋮</span>
        </summary>

        <ul class="lms-tree-children">
            @foreach ($subcategory->children as $child)
                @include('catalog.partials.subcategory-branch', ['subcategory' => $child, 'category' => $category, 'depth' => $childDepth])
            @endforeach

            @if ($subcategory->children->isEmpty() && ! $hasSubcategoryParameters)
                <li class="lms-tree-empty">{{ __('messages.catalog_no_parameter') }}</li>
            @endif
        </ul>
    </details>
</li>
