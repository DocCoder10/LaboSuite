@extends('layouts.app')

@section('content')
    @php
        $locale = app()->getLocale();

        $catalogPayload = $disciplines->map(function ($discipline) use ($locale) {
            return [
                'id' => $discipline->id,
                'label' => $discipline->label($locale),
                'categories' => $discipline->categories->map(function ($category) use ($locale) {
                    return [
                        'id' => $category->id,
                        'label' => $category->label($locale),
                        'parameters' => $category->parameters->map(function ($parameter) use ($locale) {
                            return [
                                'id' => $parameter->id,
                                'label' => $parameter->label($locale),
                                'reference' => $parameter->referenceRange(),
                                'unit' => $parameter->unit,
                                'value_type' => $parameter->value_type,
                                'options' => $parameter->options,
                                'subcategory_id' => $parameter->subcategory_id,
                            ];
                        })->values(),
                        'subcategories' => $category->subcategories->map(function ($subcategory) use ($locale) {
                            return [
                                'id' => $subcategory->id,
                                'label' => $subcategory->label($locale),
                            ];
                        })->values(),
                    ];
                })->values(),
            ];
        })->values();
    @endphp

    <form method="POST" action="{{ route('analyses.store') }}" class="lms-grid" data-analysis-builder data-catalog='@json($catalogPayload)' data-selected='@json(old("selected_categories", []))' data-old-results='@json(old("results", []))' data-msg-no-subcategory="{{ __('messages.no_subcategory') }}" data-msg-parameter="{{ __('messages.parameter') }}" data-msg-result="{{ __('messages.result') }}" data-msg-reference="{{ __('messages.reference') }}" data-msg-unit="{{ __('messages.unit') }}" data-msg-select-hint="{{ __('messages.result_input_hint') }}">
        @csrf

        <section class="lms-card lms-stack">
            <h3>{{ __('messages.step_patient') }}</h3>
            <div class="lms-grid-3">
                <label class="lms-field">
                    <span>{{ __('messages.patient_identifier') }}</span>
                    <input type="text" name="patient[identifier]" value="{{ old('patient.identifier') }}" required>
                </label>
                <label class="lms-field">
                    <span>{{ __('messages.first_name') }}</span>
                    <input type="text" name="patient[first_name]" value="{{ old('patient.first_name') }}" required>
                </label>
                <label class="lms-field">
                    <span>{{ __('messages.last_name') }}</span>
                    <input type="text" name="patient[last_name]" value="{{ old('patient.last_name') }}" required>
                </label>
                <label class="lms-field">
                    <span>{{ __('messages.sex') }}</span>
                    <select name="patient[sex]" required>
                        <option value="male" @selected(old('patient.sex') === 'male')>{{ __('messages.male') }}</option>
                        <option value="female" @selected(old('patient.sex') === 'female')>{{ __('messages.female') }}</option>
                        <option value="other" @selected(old('patient.sex') === 'other')>{{ __('messages.other') }}</option>
                    </select>
                </label>
                <label class="lms-field">
                    <span>{{ __('messages.age') }}</span>
                    <input type="number" name="patient[age]" min="0" max="130" value="{{ old('patient.age') }}">
                </label>
                <label class="lms-field">
                    <span>{{ __('messages.phone') }}</span>
                    <input type="text" name="patient[phone]" value="{{ old('patient.phone') }}">
                </label>
            </div>

            <label class="lms-field">
                <span>{{ __('messages.analysis_date') }}</span>
                <input type="date" name="analysis_date" value="{{ old('analysis_date', $analysisDate) }}" required>
            </label>
        </section>

        <section class="lms-card lms-stack">
            <h3>{{ __('messages.step_select') }}</h3>
            <p class="lms-muted">{{ __('messages.available_categories') }}</p>
            <div class="lms-check-grid">
                @foreach ($disciplines as $discipline)
                    <div class="lms-check-group">
                        <h4>{{ $discipline->label($locale) }}</h4>
                        @foreach ($discipline->categories as $category)
                            <label class="lms-checkbox">
                                <input type="checkbox" name="selected_categories[]" value="{{ $category->id }}" @checked(in_array($category->id, old('selected_categories', [])))>
                                <span>{{ $category->label($locale) }}</span>
                            </label>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </section>

        <section class="lms-card lms-stack">
            <h3>{{ __('messages.step_results') }}</h3>
            <div data-parameter-panel class="lms-stack"></div>
        </section>

        <section class="lms-card lms-stack">
            <h3>{{ __('messages.step_preview') }}</h3>
            <label class="lms-field">
                <span>{{ __('messages.notes') }}</span>
                <textarea name="notes" rows="3">{{ old('notes') }}</textarea>
            </label>

            <button class="lms-btn" type="submit">{{ __('messages.save_analysis') }}</button>
        </section>
    </form>
@endsection
