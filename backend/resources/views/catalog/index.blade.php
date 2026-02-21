@extends('layouts.app')

@section('content')
    <section class="lms-page-head lms-catalog-head">
        <h2>{{ __('messages.catalog_title') }}</h2>
    </section>

    <div class="lms-inline-actions lms-wrap-actions lms-catalog-actions">
        <x-ui.button type="button" icon="plus" data-modal-open="modal-add-discipline">{{ __('messages.add_discipline') }}</x-ui.button>
        <x-ui.button type="button" variant="secondary" icon="sparkles" data-add-child disabled>{{ __('messages.add_child') }}</x-ui.button>
    </div>

    <div
        class="lms-catalog-layout"
        data-catalog-editor
        data-route-discipline-update="{{ url('/catalog/disciplines/__ID__') }}"
        data-route-discipline-delete="{{ url('/catalog/disciplines/__ID__') }}"
        data-route-category-store="{{ route('catalog.categories.store') }}"
        data-route-category-update="{{ url('/catalog/categories/__ID__') }}"
        data-route-category-delete="{{ url('/catalog/categories/__ID__') }}"
        data-route-subcategory-store="{{ route('catalog.subcategories.store') }}"
        data-route-subcategory-update="{{ url('/catalog/subcategories/__ID__') }}"
        data-route-subcategory-delete="{{ url('/catalog/subcategories/__ID__') }}"
        data-route-parameter-store="{{ route('catalog.parameters.store') }}"
        data-route-parameter-update="{{ url('/catalog/parameters/__ID__') }}"
        data-route-parameter-delete="{{ url('/catalog/parameters/__ID__') }}"
        data-route-reorder="{{ route('catalog.reorder') }}"
        data-label-confirm-delete="{{ __('messages.confirm_delete') }}"
        data-label-confirm-delete-with-children="{{ __('messages.confirm_delete_with_children') }}"
        data-label-reorder-failed="{{ __('messages.catalog_reorder_failed') }}"
        data-label-create-failed="{{ __('messages.catalog_create_failed') }}"
        data-label-no-parameter="{{ __('messages.catalog_no_parameter') }}"
        data-label-drag-to-reorder="{{ __('messages.drag_to_reorder') }}"
        data-csrf-token="{{ csrf_token() }}"
    >
        @include('catalog.partials.tree-panel')
        @include('catalog.partials.editor.panel')
    </div>

    @include('catalog.partials.modals.index')
@endsection
