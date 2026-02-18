@extends('layouts.app')

@section('content')
    <section class="lms-page-head">
        <h2>{{ __('messages.catalog_title') }}</h2>
    </section>

    <div class="lms-grid-2">
        <section class="lms-card lms-stack">
            <h3>{{ __('messages.add_discipline') }}</h3>
            <form method="POST" action="{{ route('catalog.disciplines.store') }}" class="lms-stack">
                @csrf
                <label class="lms-field"><span>{{ __('messages.code') }}</span><input name="code" required></label>
                <label class="lms-field"><span>{{ __('messages.name') }}</span><input name="name" required></label>
                <label class="lms-field"><span>{{ __('messages.label_fr') }}</span><input name="label_fr"></label>
                <label class="lms-field"><span>{{ __('messages.label_ar') }}</span><input name="label_ar"></label>
                <label class="lms-field"><span>{{ __('messages.sort_order') }}</span><input type="number" name="sort_order" value="0" min="0"></label>
                <button class="lms-btn" type="submit">{{ __('messages.add_discipline') }}</button>
            </form>
        </section>

        <section class="lms-card lms-stack">
            <h3>{{ __('messages.add_category') }}</h3>
            <form method="POST" action="{{ route('catalog.categories.store') }}" class="lms-stack">
                @csrf
                <label class="lms-field">
                    <span>{{ __('messages.discipline') }}</span>
                    <select name="discipline_id" required>
                        @foreach ($disciplines as $discipline)
                            <option value="{{ $discipline->id }}">{{ $discipline->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="lms-field"><span>{{ __('messages.code') }}</span><input name="code" required></label>
                <label class="lms-field"><span>{{ __('messages.name') }}</span><input name="name" required></label>
                <label class="lms-field"><span>{{ __('messages.label_fr') }}</span><input name="label_fr"></label>
                <label class="lms-field"><span>{{ __('messages.label_ar') }}</span><input name="label_ar"></label>
                <label class="lms-field"><span>{{ __('messages.sort_order') }}</span><input type="number" name="sort_order" value="0" min="0"></label>
                <button class="lms-btn" type="submit">{{ __('messages.add_category') }}</button>
            </form>
        </section>

        <section class="lms-card lms-stack">
            <h3>{{ __('messages.add_subcategory') }}</h3>
            <form method="POST" action="{{ route('catalog.subcategories.store') }}" class="lms-stack">
                @csrf
                <label class="lms-field">
                    <span>{{ __('messages.category') }}</span>
                    <select name="category_id" required>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->discipline?->name }} / {{ $category->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="lms-field"><span>{{ __('messages.code') }}</span><input name="code" required></label>
                <label class="lms-field"><span>{{ __('messages.name') }}</span><input name="name" required></label>
                <label class="lms-field"><span>{{ __('messages.label_fr') }}</span><input name="label_fr"></label>
                <label class="lms-field"><span>{{ __('messages.label_ar') }}</span><input name="label_ar"></label>
                <label class="lms-field"><span>{{ __('messages.sort_order') }}</span><input type="number" name="sort_order" value="0" min="0"></label>
                <button class="lms-btn" type="submit">{{ __('messages.add_subcategory') }}</button>
            </form>
        </section>

        <section class="lms-card lms-stack">
            <h3>{{ __('messages.add_parameter') }}</h3>
            <form method="POST" action="{{ route('catalog.parameters.store') }}" class="lms-stack">
                @csrf
                <label class="lms-field">
                    <span>{{ __('messages.discipline') }}</span>
                    <select name="discipline_id" required>
                        @foreach ($disciplines as $discipline)
                            <option value="{{ $discipline->id }}">{{ $discipline->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="lms-field">
                    <span>{{ __('messages.category') }}</span>
                    <select name="category_id" required>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->discipline?->name }} / {{ $category->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="lms-field">
                    <span>{{ __('messages.subcategory') }}</span>
                    <select name="subcategory_id">
                        <option value="">-</option>
                        @foreach ($subcategories as $subcategory)
                            <option value="{{ $subcategory->id }}">{{ $subcategory->category?->name }} / {{ $subcategory->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="lms-field"><span>{{ __('messages.code') }}</span><input name="code" required></label>
                <label class="lms-field"><span>{{ __('messages.name') }}</span><input name="name" required></label>
                <label class="lms-field"><span>{{ __('messages.label_fr') }}</span><input name="label_fr"></label>
                <label class="lms-field"><span>{{ __('messages.label_ar') }}</span><input name="label_ar"></label>
                <label class="lms-field"><span>{{ __('messages.unit') }}</span><input name="unit"></label>
                <label class="lms-field">
                    <span>{{ __('messages.value_type') }}</span>
                    <select name="value_type" required>
                        <option value="number">{{ __('messages.value_type_number') }}</option>
                        <option value="text">{{ __('messages.value_type_text') }}</option>
                        <option value="list">{{ __('messages.value_type_list') }}</option>
                    </select>
                </label>
                <label class="lms-field"><span>{{ __('messages.normal_min') }}</span><input type="number" step="0.001" name="normal_min"></label>
                <label class="lms-field"><span>{{ __('messages.normal_max') }}</span><input type="number" step="0.001" name="normal_max"></label>
                <label class="lms-field"><span>{{ __('messages.normal_text') }}</span><input name="normal_text"></label>
                <label class="lms-field"><span>{{ __('messages.options_csv') }}</span><input name="options_csv"></label>
                <label class="lms-field"><span>{{ __('messages.sort_order') }}</span><input type="number" name="sort_order" value="0" min="0"></label>
                <button class="lms-btn" type="submit">{{ __('messages.add_parameter') }}</button>
            </form>
        </section>
    </div>

    <section class="lms-card lms-stack mt-6">
        <h3>{{ __('messages.manage_disciplines') }}</h3>
        <div class="lms-table-wrap">
            <table class="lms-table">
                <thead>
                    <tr>
                        <th>{{ __('messages.code') }}</th>
                        <th>{{ __('messages.name') }}</th>
                        <th>{{ __('messages.label_fr') }}</th>
                        <th>{{ __('messages.label_ar') }}</th>
                        <th>{{ __('messages.sort_order') }}</th>
                        <th>{{ __('messages.active') }}</th>
                        <th>{{ __('messages.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($disciplines as $discipline)
                        @php($disciplineFormId = 'discipline-update-'.$discipline->id)
                        <tr>
                            <td><input name="code" value="{{ $discipline->code }}" form="{{ $disciplineFormId }}" required></td>
                            <td><input name="name" value="{{ $discipline->name }}" form="{{ $disciplineFormId }}" required></td>
                            <td><input name="label_fr" value="{{ $discipline->labels['fr'] ?? '' }}" form="{{ $disciplineFormId }}"></td>
                            <td><input name="label_ar" value="{{ $discipline->labels['ar'] ?? '' }}" form="{{ $disciplineFormId }}"></td>
                            <td><input type="number" name="sort_order" value="{{ $discipline->sort_order }}" min="0" form="{{ $disciplineFormId }}"></td>
                            <td>
                                <input type="hidden" name="is_active" value="0" form="{{ $disciplineFormId }}">
                                <label class="lms-checkbox lms-checkbox-compact">
                                    <input type="checkbox" name="is_active" value="1" @checked($discipline->is_active) form="{{ $disciplineFormId }}">
                                </label>
                            </td>
                            <td>
                                <div class="lms-table-actions">
                                    <form id="{{ $disciplineFormId }}" method="POST" action="{{ route('catalog.disciplines.update', $discipline) }}">
                                        @csrf
                                        @method('PUT')
                                    </form>
                                    <button class="lms-btn lms-btn-soft" type="submit" form="{{ $disciplineFormId }}">{{ __('messages.update') }}</button>

                                    <form method="POST" action="{{ route('catalog.disciplines.destroy', $discipline) }}" onsubmit="return confirm('{{ __('messages.confirm_delete') }}');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="lms-btn lms-btn-danger" type="submit">{{ __('messages.delete') }}</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <section class="lms-card lms-stack mt-6">
        <h3>{{ __('messages.manage_categories') }}</h3>
        <div class="lms-table-wrap">
            <table class="lms-table">
                <thead>
                    <tr>
                        <th>{{ __('messages.discipline') }}</th>
                        <th>{{ __('messages.code') }}</th>
                        <th>{{ __('messages.name') }}</th>
                        <th>{{ __('messages.label_fr') }}</th>
                        <th>{{ __('messages.label_ar') }}</th>
                        <th>{{ __('messages.sort_order') }}</th>
                        <th>{{ __('messages.active') }}</th>
                        <th>{{ __('messages.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($categories as $category)
                        @php($categoryFormId = 'category-update-'.$category->id)
                        <tr>
                            <td>
                                <select name="discipline_id" form="{{ $categoryFormId }}" required>
                                    @foreach ($disciplines as $discipline)
                                        <option value="{{ $discipline->id }}" @selected($discipline->id === $category->discipline_id)>{{ $discipline->name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td><input name="code" value="{{ $category->code }}" form="{{ $categoryFormId }}" required></td>
                            <td><input name="name" value="{{ $category->name }}" form="{{ $categoryFormId }}" required></td>
                            <td><input name="label_fr" value="{{ $category->labels['fr'] ?? '' }}" form="{{ $categoryFormId }}"></td>
                            <td><input name="label_ar" value="{{ $category->labels['ar'] ?? '' }}" form="{{ $categoryFormId }}"></td>
                            <td><input type="number" name="sort_order" value="{{ $category->sort_order }}" min="0" form="{{ $categoryFormId }}"></td>
                            <td>
                                <input type="hidden" name="is_active" value="0" form="{{ $categoryFormId }}">
                                <label class="lms-checkbox lms-checkbox-compact">
                                    <input type="checkbox" name="is_active" value="1" @checked($category->is_active) form="{{ $categoryFormId }}">
                                </label>
                            </td>
                            <td>
                                <div class="lms-table-actions">
                                    <form id="{{ $categoryFormId }}" method="POST" action="{{ route('catalog.categories.update', $category) }}">
                                        @csrf
                                        @method('PUT')
                                    </form>
                                    <button class="lms-btn lms-btn-soft" type="submit" form="{{ $categoryFormId }}">{{ __('messages.update') }}</button>

                                    <form method="POST" action="{{ route('catalog.categories.destroy', $category) }}" onsubmit="return confirm('{{ __('messages.confirm_delete') }}');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="lms-btn lms-btn-danger" type="submit">{{ __('messages.delete') }}</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <section class="lms-card lms-stack mt-6">
        <h3>{{ __('messages.manage_subcategories') }}</h3>
        <div class="lms-table-wrap">
            <table class="lms-table">
                <thead>
                    <tr>
                        <th>{{ __('messages.category') }}</th>
                        <th>{{ __('messages.code') }}</th>
                        <th>{{ __('messages.name') }}</th>
                        <th>{{ __('messages.label_fr') }}</th>
                        <th>{{ __('messages.label_ar') }}</th>
                        <th>{{ __('messages.sort_order') }}</th>
                        <th>{{ __('messages.active') }}</th>
                        <th>{{ __('messages.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($subcategories as $subcategory)
                        @php($subcategoryFormId = 'subcategory-update-'.$subcategory->id)
                        <tr>
                            <td>
                                <select name="category_id" form="{{ $subcategoryFormId }}" required>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}" @selected($category->id === $subcategory->category_id)>{{ $category->discipline?->name }} / {{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td><input name="code" value="{{ $subcategory->code }}" form="{{ $subcategoryFormId }}" required></td>
                            <td><input name="name" value="{{ $subcategory->name }}" form="{{ $subcategoryFormId }}" required></td>
                            <td><input name="label_fr" value="{{ $subcategory->labels['fr'] ?? '' }}" form="{{ $subcategoryFormId }}"></td>
                            <td><input name="label_ar" value="{{ $subcategory->labels['ar'] ?? '' }}" form="{{ $subcategoryFormId }}"></td>
                            <td><input type="number" name="sort_order" value="{{ $subcategory->sort_order }}" min="0" form="{{ $subcategoryFormId }}"></td>
                            <td>
                                <input type="hidden" name="is_active" value="0" form="{{ $subcategoryFormId }}">
                                <label class="lms-checkbox lms-checkbox-compact">
                                    <input type="checkbox" name="is_active" value="1" @checked($subcategory->is_active) form="{{ $subcategoryFormId }}">
                                </label>
                            </td>
                            <td>
                                <div class="lms-table-actions">
                                    <form id="{{ $subcategoryFormId }}" method="POST" action="{{ route('catalog.subcategories.update', $subcategory) }}">
                                        @csrf
                                        @method('PUT')
                                    </form>
                                    <button class="lms-btn lms-btn-soft" type="submit" form="{{ $subcategoryFormId }}">{{ __('messages.update') }}</button>

                                    <form method="POST" action="{{ route('catalog.subcategories.destroy', $subcategory) }}" onsubmit="return confirm('{{ __('messages.confirm_delete') }}');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="lms-btn lms-btn-danger" type="submit">{{ __('messages.delete') }}</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <section class="lms-card lms-stack mt-6">
        <h3>{{ __('messages.manage_parameters') }}</h3>
        <div class="lms-table-wrap">
            <table class="lms-table lms-table-editor">
                <thead>
                    <tr>
                        <th>{{ __('messages.discipline') }}</th>
                        <th>{{ __('messages.category') }}</th>
                        <th>{{ __('messages.subcategory') }}</th>
                        <th>{{ __('messages.code') }}</th>
                        <th>{{ __('messages.name') }}</th>
                        <th>{{ __('messages.label_fr') }}</th>
                        <th>{{ __('messages.label_ar') }}</th>
                        <th>{{ __('messages.value_type') }}</th>
                        <th>{{ __('messages.unit') }}</th>
                        <th>{{ __('messages.normal_min') }}</th>
                        <th>{{ __('messages.normal_max') }}</th>
                        <th>{{ __('messages.normal_text') }}</th>
                        <th>{{ __('messages.options_csv') }}</th>
                        <th>{{ __('messages.sort_order') }}</th>
                        <th>{{ __('messages.visible') }}</th>
                        <th>{{ __('messages.active') }}</th>
                        <th>{{ __('messages.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($parameters as $parameter)
                        @php($parameterFormId = 'parameter-update-'.$parameter->id)
                        <tr>
                            <td>
                                <select name="discipline_id" form="{{ $parameterFormId }}" required>
                                    @foreach ($disciplines as $discipline)
                                        <option value="{{ $discipline->id }}" @selected($discipline->id === $parameter->discipline_id)>{{ $discipline->name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <select name="category_id" form="{{ $parameterFormId }}" required>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}" @selected($category->id === $parameter->category_id)>{{ $category->discipline?->name }} / {{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <select name="subcategory_id" form="{{ $parameterFormId }}">
                                    <option value="">-</option>
                                    @foreach ($subcategories as $subcategory)
                                        <option value="{{ $subcategory->id }}" @selected($subcategory->id === $parameter->subcategory_id)>{{ $subcategory->category?->name }} / {{ $subcategory->name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td><input name="code" value="{{ $parameter->code }}" form="{{ $parameterFormId }}" required></td>
                            <td><input name="name" value="{{ $parameter->name }}" form="{{ $parameterFormId }}" required></td>
                            <td><input name="label_fr" value="{{ $parameter->labels['fr'] ?? '' }}" form="{{ $parameterFormId }}"></td>
                            <td><input name="label_ar" value="{{ $parameter->labels['ar'] ?? '' }}" form="{{ $parameterFormId }}"></td>
                            <td>
                                <select name="value_type" form="{{ $parameterFormId }}" required>
                                    <option value="number" @selected($parameter->value_type === 'number')>{{ __('messages.value_type_number') }}</option>
                                    <option value="text" @selected($parameter->value_type === 'text')>{{ __('messages.value_type_text') }}</option>
                                    <option value="list" @selected($parameter->value_type === 'list')>{{ __('messages.value_type_list') }}</option>
                                </select>
                            </td>
                            <td><input name="unit" value="{{ $parameter->unit }}" form="{{ $parameterFormId }}"></td>
                            <td><input type="number" step="0.001" name="normal_min" value="{{ $parameter->normal_min }}" form="{{ $parameterFormId }}"></td>
                            <td><input type="number" step="0.001" name="normal_max" value="{{ $parameter->normal_max }}" form="{{ $parameterFormId }}"></td>
                            <td><input name="normal_text" value="{{ $parameter->normal_text }}" form="{{ $parameterFormId }}"></td>
                            <td><input name="options_csv" value="{{ implode(', ', $parameter->options ?? []) }}" form="{{ $parameterFormId }}"></td>
                            <td><input type="number" name="sort_order" value="{{ $parameter->sort_order }}" min="0" form="{{ $parameterFormId }}"></td>
                            <td>
                                <input type="hidden" name="is_visible" value="0" form="{{ $parameterFormId }}">
                                <label class="lms-checkbox lms-checkbox-compact">
                                    <input type="checkbox" name="is_visible" value="1" @checked($parameter->is_visible) form="{{ $parameterFormId }}">
                                </label>
                            </td>
                            <td>
                                <input type="hidden" name="is_active" value="0" form="{{ $parameterFormId }}">
                                <label class="lms-checkbox lms-checkbox-compact">
                                    <input type="checkbox" name="is_active" value="1" @checked($parameter->is_active) form="{{ $parameterFormId }}">
                                </label>
                            </td>
                            <td>
                                <div class="lms-table-actions">
                                    <form id="{{ $parameterFormId }}" method="POST" action="{{ route('catalog.parameters.update', $parameter) }}">
                                        @csrf
                                        @method('PUT')
                                    </form>
                                    <button class="lms-btn lms-btn-soft" type="submit" form="{{ $parameterFormId }}">{{ __('messages.update') }}</button>

                                    <form method="POST" action="{{ route('catalog.parameters.destroy', $parameter) }}" onsubmit="return confirm('{{ __('messages.confirm_delete') }}');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="lms-btn lms-btn-danger" type="submit">{{ __('messages.delete') }}</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{ $parameters->links() }}
    </section>
@endsection
