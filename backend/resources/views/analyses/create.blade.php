@extends('layouts.app')

@section('content')
    @php
        $locale = app()->getLocale();
        $draftPatient = $draft['patient'] ?? [];
        $draftSelectedCategories = $draft['selected_categories'] ?? [];
    @endphp

    <form method="POST" action="{{ route('analyses.selection.store') }}" class="lms-grid">
        @csrf

        <section class="lms-card lms-stack">
            <h3>{{ __('messages.step_patient') }}</h3>
            <div class="lms-grid-3">
                <label class="lms-field">
                    <span>{{ __('messages.patient_identifier') }}</span>
                    <input type="text" name="patient[identifier]" value="{{ old('patient.identifier', $draftPatient['identifier'] ?? '') }}" required>
                </label>
                <label class="lms-field">
                    <span>{{ __('messages.first_name') }}</span>
                    <input type="text" name="patient[first_name]" value="{{ old('patient.first_name', $draftPatient['first_name'] ?? '') }}" required>
                </label>
                <label class="lms-field">
                    <span>{{ __('messages.last_name') }}</span>
                    <input type="text" name="patient[last_name]" value="{{ old('patient.last_name', $draftPatient['last_name'] ?? '') }}" required>
                </label>
                <label class="lms-field">
                    <span>{{ __('messages.sex') }}</span>
                    @php($selectedSex = old('patient.sex', $draftPatient['sex'] ?? 'male'))
                    <select name="patient[sex]" required>
                        <option value="male" @selected($selectedSex === 'male')>{{ __('messages.male') }}</option>
                        <option value="female" @selected($selectedSex === 'female')>{{ __('messages.female') }}</option>
                        <option value="other" @selected($selectedSex === 'other')>{{ __('messages.other') }}</option>
                    </select>
                </label>
                <label class="lms-field">
                    <span>{{ __('messages.age') }}</span>
                    <input type="number" name="patient[age]" min="0" max="130" value="{{ old('patient.age', $draftPatient['age'] ?? '') }}">
                </label>
                <label class="lms-field">
                    <span>{{ __('messages.phone') }}</span>
                    <input type="text" name="patient[phone]" value="{{ old('patient.phone', $draftPatient['phone'] ?? '') }}">
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
                                @php($isChecked = in_array($category->id, old('selected_categories', $draftSelectedCategories)))
                                <input type="checkbox" name="selected_categories[]" value="{{ $category->id }}" @checked($isChecked)>
                                <span>{{ $category->label($locale) }}</span>
                            </label>
                        @endforeach
                    </div>
                @endforeach
            </div>

            <div class="lms-inline-actions">
                <button class="lms-btn" type="submit">{{ __('messages.continue_to_results') }}</button>
            </div>
        </section>
    </form>
@endsection
