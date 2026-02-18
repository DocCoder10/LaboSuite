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
                <label class="lms-field"><span>{{ __('messages.sort_order') }}</span><input type="number" name="sort_order" value="0"></label>
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
                <label class="lms-field"><span>{{ __('messages.sort_order') }}</span><input type="number" name="sort_order" value="0"></label>
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
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="lms-field"><span>{{ __('messages.code') }}</span><input name="code" required></label>
                <label class="lms-field"><span>{{ __('messages.name') }}</span><input name="name" required></label>
                <label class="lms-field"><span>{{ __('messages.label_fr') }}</span><input name="label_fr"></label>
                <label class="lms-field"><span>{{ __('messages.label_ar') }}</span><input name="label_ar"></label>
                <label class="lms-field"><span>{{ __('messages.sort_order') }}</span><input type="number" name="sort_order" value="0"></label>
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
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="lms-field">
                    <span>{{ __('messages.subcategory') }}</span>
                    <select name="subcategory_id">
                        <option value="">-</option>
                        @foreach ($subcategories as $subcategory)
                            <option value="{{ $subcategory->id }}">{{ $subcategory->name }}</option>
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
                <label class="lms-field"><span>{{ __('messages.sort_order') }}</span><input type="number" name="sort_order" value="0"></label>
                <button class="lms-btn" type="submit">{{ __('messages.add_parameter') }}</button>
            </form>
        </section>
    </div>

    <section class="lms-card lms-stack mt-6">
        <h3>{{ __('messages.add_parameter') }} - {{ __('messages.actions') }}</h3>
        <div class="lms-table-wrap">
            <table class="lms-table">
                <thead>
                    <tr>
                        <th>{{ __('messages.code') }}</th>
                        <th>{{ __('messages.parameter') }}</th>
                        <th>{{ __('messages.discipline') }}</th>
                        <th>{{ __('messages.category') }}</th>
                        <th>{{ __('messages.reference') }}</th>
                        <th>{{ __('messages.unit') }}</th>
                        <th>{{ __('messages.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($parameters as $parameter)
                        <tr>
                            <td>{{ $parameter->code }}</td>
                            <td>{{ $parameter->name }}</td>
                            <td>{{ $parameter->discipline?->name }}</td>
                            <td>{{ $parameter->category?->name }}</td>
                            <td>{{ $parameter->referenceRange() }}</td>
                            <td>{{ $parameter->unit ?: '-' }}</td>
                            <td>
                                <form method="POST" action="{{ route('catalog.parameters.destroy', $parameter) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="lms-btn lms-btn-danger" type="submit">{{ __('messages.delete') }}</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{ $parameters->links() }}
    </section>
@endsection
