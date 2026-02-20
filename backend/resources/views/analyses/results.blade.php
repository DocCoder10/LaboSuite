@extends('layouts.app')

@section('content')
    @php
        $patient = $draft['patient'] ?? [];
    @endphp

    <section class="lms-page-head">
        <h2>{{ __('messages.step_results') }}</h2>
        <div class="lms-inline-actions">
            <a class="lms-btn lms-btn-soft" href="{{ route('analyses.create') }}">{{ __('messages.change_selection') }}</a>
        </div>
    </section>

    <section class="lms-card lms-stack">
        <h3>{{ __('messages.patient_summary') }}</h3>
        <div class="lms-grid-3">
            <p><strong>{{ __('messages.patient_identifier') }}:</strong> {{ $patient['identifier'] ?? '-' }}</p>
            <p><strong>{{ __('messages.first_name') }}:</strong> {{ $patient['first_name'] ?? '-' }}</p>
            <p><strong>{{ __('messages.last_name') }}:</strong> {{ $patient['last_name'] ?? '-' }}</p>
            <p><strong>{{ __('messages.sex') }}:</strong> {{ __('messages.'.($patient['sex'] ?? 'other')) }}</p>
            <p><strong>{{ __('messages.age') }}:</strong> {{ $patient['age'] ?? '-' }}</p>
            <p><strong>{{ __('messages.phone') }}:</strong> {{ $patient['phone'] ?? '-' }}</p>
            <p><strong>{{ __('messages.analysis_date') }}:</strong> {{ $draft['analysis_date'] ?? '-' }}</p>
        </div>
    </section>

    <form method="POST" action="{{ route('analyses.store') }}" class="lms-grid">
        @csrf

        @if ($parameterCount === 0)
            <section class="lms-card lms-stack">
                <p class="lms-muted">{{ __('messages.no_results') }}</p>
            </section>
        @else
            @foreach ($groups as $discipline)
                <section class="lms-card lms-stack">
                    <h3>{{ mb_strtoupper($discipline['label']) }}</h3>

                    @foreach ($discipline['categories'] as $category)
                        <div class="lms-stack">
                            <h4>{{ $category['label'] }}</h4>

                            @foreach ($category['subcategories'] as $subcategory)
                                @if (! empty($subcategory['label']))
                                    <h5>{{ $subcategory['label'] }}</h5>
                                @endif

                                <div class="lms-table-wrap">
                                    <table class="lms-table">
                                        <thead>
                                            <tr>
                                                <th>{{ __('messages.parameter') }}</th>
                                                <th>{{ __('messages.result') }}</th>
                                                <th>{{ __('messages.reference') }}</th>
                                                <th>{{ __('messages.unit') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($subcategory['rows'] as $row)
                                                <tr>
                                                    <td>{{ $row['label'] }}</td>
                                                    <td>
                                                        @if ($row['value_type'] === 'number')
                                                            <input type="number" step="any" name="results[{{ $row['id'] }}]" value="{{ old('results.'.$row['id']) }}" required>
                                                        @elseif ($row['value_type'] === 'list')
                                                            @php
                                                                $selectedOption = old('results.'.$row['id'], $row['default_value'] ?? '');
                                                            @endphp
                                                            <select name="results[{{ $row['id'] }}]" required>
                                                                <option value="">-</option>
                                                                @foreach ($row['options'] as $option)
                                                                    <option value="{{ $option }}" @selected($selectedOption === $option)>{{ $option }}</option>
                                                                @endforeach
                                                            </select>
                                                        @else
                                                            <input type="text" name="results[{{ $row['id'] }}]" value="{{ old('results.'.$row['id']) }}" required>
                                                        @endif
                                                    </td>
                                                    <td>{{ $row['reference'] }}</td>
                                                    <td>{{ $row['unit'] ?: '-' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </section>
            @endforeach
        @endif

        <section class="lms-card lms-stack">
            <label class="lms-field">
                <span>{{ __('messages.notes') }}</span>
                <textarea name="notes" rows="3">{{ old('notes') }}</textarea>
            </label>

            <div class="lms-inline-actions">
                <a class="lms-btn lms-btn-soft" href="{{ route('analyses.create') }}">{{ __('messages.change_selection') }}</a>
                <button class="lms-btn" type="submit" @disabled($parameterCount === 0)>{{ __('messages.save_analysis') }}</button>
            </div>
        </section>
    </form>
@endsection
