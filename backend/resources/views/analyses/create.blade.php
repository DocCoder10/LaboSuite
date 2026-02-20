@extends('layouts.app')

@section('content')
    @php
        $locale = app()->getLocale();
        $draftPatient = $draft['patient'] ?? [];
        $draftExtraFields = is_array($draftPatient['extra_fields'] ?? null) ? $draftPatient['extra_fields'] : [];
        $draftSelectedCategories = $draft['selected_categories'] ?? [];
        $visiblePatientFields = collect($patientFields)
            ->filter(fn (array $field) => (bool) ($field['active'] ?? false))
            ->values();
    @endphp

    <form method="POST" action="{{ route('analyses.selection.store') }}" class="lms-grid">
        @csrf

        <section class="lms-card lms-stack">
            <h3>{{ __('messages.step_patient') }}</h3>

            <div class="lms-grid-3">
                @foreach ($visiblePatientFields as $field)
                    @php
                        $fieldKey = (string) ($field['key'] ?? '');
                        $fieldLabel = (string) ($field['label'] ?? $fieldKey);
                        $fieldType = (string) ($field['type'] ?? 'text');
                        $isBuiltIn = (bool) ($field['built_in'] ?? false);
                        $isRequired = (bool) ($field['required'] ?? false);
                    @endphp

                    <label class="lms-field">
                        <span>{{ $fieldLabel }}</span>

                        @if ($fieldKey === 'sex')
                            @php
                                $selectedSex = old('patient.sex', $draftPatient['sex'] ?? 'male');
                            @endphp
                            <select name="patient[sex]" @required($isRequired)>
                                <option value="male" @selected($selectedSex === 'male')>{{ __('messages.male') }}</option>
                                <option value="female" @selected($selectedSex === 'female')>{{ __('messages.female') }}</option>
                                <option value="other" @selected($selectedSex === 'other')>{{ __('messages.other') }}</option>
                            </select>
                        @elseif ($fieldKey === 'identifier' && $patientIdentifierRequired)
                            @php
                                $identifierValue = old('patient.identifier', $draftPatient['identifier'] ?? '');
                            @endphp
                            <input type="text" value="{{ $identifierValue }}" placeholder="{{ __('messages.identifier_auto_generated') }}" readonly>
                            <input type="hidden" name="patient[identifier]" value="{{ $identifierValue }}">
                        @else
                            @php
                                $fieldName = $isBuiltIn
                                    ? 'patient['.$fieldKey.']'
                                    : 'patient[extra_fields]['.$fieldKey.']';

                                $value = $isBuiltIn
                                    ? old('patient.'.$fieldKey, $draftPatient[$fieldKey] ?? '')
                                    : old('patient.extra_fields.'.$fieldKey, $draftExtraFields[$fieldKey] ?? '');
                            @endphp

                            <input
                                type="{{ $fieldType === 'number' ? 'number' : 'text' }}"
                                name="{{ $fieldName }}"
                                value="{{ $value }}"
                                @if ($fieldKey === 'age') min="0" max="130" @endif
                                @if ($fieldType === 'number') step="any" @endif
                                @required($isRequired)
                            >
                        @endif
                    </label>
                @endforeach
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
                                @php
                                    $isChecked = in_array($category->id, old('selected_categories', $draftSelectedCategories));
                                @endphp
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
