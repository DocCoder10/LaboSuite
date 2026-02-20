@extends('layouts.app')

@section('content')
    <section class="lms-page-head">
        <h2>{{ __('messages.edit_results') }}: {{ $analysis->analysis_number }}</h2>
        <div class="lms-inline-actions lms-wrap-actions">
            <a class="lms-btn lms-btn-soft" href="{{ route('analyses.show', ['analysis' => $analysis] + $listQuery) }}">{{ __('messages.view') }}</a>
            <a class="lms-btn lms-btn-soft" href="{{ route('analyses.index', $listQuery) }}">{{ __('messages.back_to_list') }}</a>
            <a class="lms-btn lms-btn-soft" href="{{ route('analyses.print', ['analysis' => $analysis] + $listQuery) }}" target="_blank" rel="noopener">{{ __('messages.print') }}</a>
        </div>
    </section>

    <section class="lms-card lms-stack">
        <div class="lms-grid-3">
            <p><strong>{{ __('messages.patient') }}:</strong> {{ $analysis->patient?->full_name ?? '-' }}</p>
            <p><strong>{{ __('messages.patient_identifier') }}:</strong> {{ $analysis->patient?->display_identifier ?? '-' }}</p>
            <p><strong>{{ __('messages.analysis_date') }}:</strong> {{ optional($analysis->analysis_date)->format('Y-m-d') }}</p>
            <p><strong>{{ __('messages.created_at') }}:</strong> {{ optional($analysis->created_at)->format('Y-m-d H:i') }}</p>
            <p><strong>{{ __('messages.updated_at') }}:</strong> {{ optional($analysis->updated_at)->format('Y-m-d H:i') }}</p>
        </div>
    </section>

    <form method="POST" action="{{ route('analyses.update', ['analysis' => $analysis] + $listQuery) }}" class="lms-grid">
        @csrf
        @method('PUT')

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
                                        || mb_strtolower(trim((string) ($singleCategoryRow['label'] ?? ''))) !== mb_strtolower(trim((string) ($category['label'] ?? '')));
                                @endphp

                                @if ($showCategoryHeading)
                                    <tr class="lms-category-row">
                                        <td colspan="4"><strong>{{ $category['label'] }}</strong></td>
                                    </tr>
                                @endif

                                @foreach ($category['subcategories'] as $subcategory)
                                    @php
                                        $rows = $subcategory['rows'] ?? [];
                                        $subcategoryLabel = trim((string) ($subcategory['label'] ?? ''));
                                        $singleRow = count($rows) === 1 ? $rows[0] : null;
                                        $hideRedundantSubheading = $subcategoryLabel !== ''
                                            && $singleRow !== null
                                            && mb_strtolower(trim((string) ($singleRow['label'] ?? ''))) === mb_strtolower($subcategoryLabel);
                                    @endphp

                                    @if ($subcategoryLabel !== '' && ! $hideRedundantSubheading)
                                        <tr class="lms-subheading-row">
                                            <td colspan="4"><strong>{{ $subcategoryLabel }}</strong></td>
                                        </tr>
                                    @endif

                                    @foreach ($rows as $row)
                                        <tr>
                                            <td>{{ $row['label'] }}</td>
                                            <td>
                                                @if ($row['value_type'] === 'number')
                                                    <input type="number" step="any" name="results[{{ $row['id'] }}]" value="{{ old('results.'.$row['id'], $row['current_value']) }}" required>
                                                @elseif ($row['value_type'] === 'list')
                                                    @php
                                                        $selectedOption = old('results.'.$row['id'], $row['current_value']);
                                                        $options = $row['options'];
                                                    @endphp
                                                    <select name="results[{{ $row['id'] }}]" required>
                                                        <option value="">-</option>
                                                        @foreach ($options as $option)
                                                            <option value="{{ $option }}" @selected($selectedOption === $option)>{{ $option }}</option>
                                                        @endforeach
                                                        @if ($selectedOption !== '' && ! in_array($selectedOption, $options, true))
                                                            <option value="{{ $selectedOption }}" selected>{{ $selectedOption }}</option>
                                                        @endif
                                                    </select>
                                                @else
                                                    <input type="text" name="results[{{ $row['id'] }}]" value="{{ old('results.'.$row['id'], $row['current_value']) }}" required>
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

        <section class="lms-card lms-stack">
            <label class="lms-field">
                <span>{{ __('messages.notes') }}</span>
                <textarea name="notes" rows="3">{{ old('notes', $analysis->notes) }}</textarea>
            </label>

            <div class="lms-inline-actions">
                <a class="lms-btn lms-btn-soft" href="{{ route('analyses.show', ['analysis' => $analysis] + $listQuery) }}">{{ __('messages.cancel') }}</a>
                <button class="lms-btn" type="submit">{{ __('messages.save_changes') }}</button>
            </div>
        </section>
    </form>
@endsection
