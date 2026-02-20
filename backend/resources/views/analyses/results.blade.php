@extends('layouts.app')

@section('content')
    @php
        $patient = $draft['patient'] ?? [];
        $extraFields = is_array($patient['extra_fields'] ?? null) ? $patient['extra_fields'] : [];
        $visiblePatientFields = collect($patientFields)
            ->filter(fn (array $field) => (bool) ($field['active'] ?? false))
            ->values();
        $normalizeLabel = static fn (?string $value): string => mb_strtolower(trim((string) $value));
        $entryLevelFromDepth = static fn (int $depth): int => max(2, $depth + 1);
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
            @foreach ($visiblePatientFields as $field)
                @php
                    $fieldKey = (string) ($field['key'] ?? '');
                    $fieldLabel = (string) ($field['label'] ?? $fieldKey);
                    $rawValue = ($field['built_in'] ?? false)
                        ? ($patient[$fieldKey] ?? null)
                        : ($extraFields[$fieldKey] ?? null);

                    if ($fieldKey === 'sex') {
                        $displayValue = __('messages.'.($rawValue ?? 'other'));
                    } else {
                        $displayValue = $rawValue;
                    }
                @endphp
                <p><strong>{{ $fieldLabel }}:</strong> {{ $displayValue === null || $displayValue === '' ? '-' : $displayValue }}</p>
            @endforeach
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

                    <div class="lms-table-wrap">
                        <table class="lms-table">
                            <thead>
                                <tr>
                                    <th>{{ __('messages.analysis_items') }}</th>
                                    <th>{{ __('messages.result') }}</th>
                                    <th>{{ __('messages.reference') }}</th>
                                    <th>{{ __('messages.unit') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($discipline['categories'] as $category)
                                    @php
                                        $categoryRows = collect($category['subcategories'] ?? [])->flatMap(fn (array $subgroup) => $subgroup['rows'] ?? [])->values();
                                        $singleCategoryRow = $categoryRows->count() === 1 ? $categoryRows->first() : null;
                                        $showCategoryHeading = $singleCategoryRow === null
                                            || $normalizeLabel($singleCategoryRow['label'] ?? '') !== $normalizeLabel($category['label'] ?? '');
                                    @endphp

                                    @if ($showCategoryHeading)
                                        <tr class="lms-category-row">
                                            <td colspan="4">
                                                <span class="lms-entry-label" style="--lms-entry-level: 1;">
                                                    <strong>{{ $category['label'] }}</strong>
                                                </span>
                                            </td>
                                        </tr>
                                    @endif

                                    @foreach ($category['subcategories'] as $subcategory)
                                        @php
                                            $rows = $subcategory['rows'] ?? [];
                                            $lineage = $subcategory['lineage'] ?? [];
                                            $singleRow = count($rows) === 1 ? $rows[0] : null;
                                            $lastLineage = ! empty($lineage) ? $lineage[count($lineage) - 1] : null;
                                            $mergeLeafIntoLineage = $singleRow !== null
                                                && is_array($lastLineage)
                                                && $normalizeLabel($lastLineage['label'] ?? '') === $normalizeLabel($singleRow['label'] ?? '');
                                            $lineageToRender = $mergeLeafIntoLineage ? array_slice($lineage, 0, -1) : $lineage;
                                            $hideRedundantSubheading = ! empty($subcategory['label'])
                                                && $singleRow !== null
                                                && $normalizeLabel($singleRow['label'] ?? '') === $normalizeLabel($subcategory['label'] ?? '');
                                        @endphp

                                        @foreach ($lineageToRender as $lineageNode)
                                            <tr class="lms-subheading-row">
                                                <td colspan="4">
                                                    <span class="lms-entry-label" style="--lms-entry-level: {{ $entryLevelFromDepth((int) ($lineageNode['depth'] ?? 1)) }};">
                                                        <strong>{{ $lineageNode['label'] }}</strong>
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach

                                        @if (! empty($subcategory['label']) && ! $hideRedundantSubheading && empty($lineageToRender))
                                            <tr class="lms-subheading-row">
                                                <td colspan="4">
                                                    <span class="lms-entry-label" style="--lms-entry-level: 2;">
                                                        <strong>{{ $subcategory['label'] }}</strong>
                                                    </span>
                                                </td>
                                            </tr>
                                        @endif

                                        @foreach ($rows as $row)
                                            @php
                                                $rowLevel = max(2, 2 + count($lineageToRender));
                                            @endphp
                                            <tr>
                                                <td>
                                                    <span class="lms-entry-label" style="--lms-entry-level: {{ $rowLevel }};">{{ $row['label'] }}</span>
                                                </td>
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
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    </div>
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
