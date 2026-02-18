@php
    $showUnitColumn = (bool) ($layout['show_unit_column'] ?? false);
    $highlightAbnormal = (bool) ($layout['highlight_abnormal'] ?? true);
    $analysisDate = optional($analysis->analysis_date)->format('d/m/Y');
    $patientSex = $analysis->patient?->sex;
    $patientSexLabel = match ($patientSex) {
        'male' => __('messages.male'),
        'female' => __('messages.female'),
        'other' => __('messages.other'),
        default => '-',
    };
    $logoSrc = trim((string) ($identity['logo_url'] ?? ($identity['logo_path'] ?? '')));
@endphp

<article class="lms-card lms-report">
    <header class="lms-report-head">
        <div class="lms-report-lab">
            @if ($logoSrc !== '')
                <img src="{{ $logoSrc }}" alt="Laboratoire" class="lms-report-logo">
            @endif

            <div class="lms-report-lab-text">
                <h3>{{ $identity['name'] ?? __('messages.app_name') }}</h3>
                @if (! empty($identity['header_note']))
                    <p>{{ $identity['header_note'] }}</p>
                @endif
                @if (! empty($identity['address']))
                    <p>{{ $identity['address'] }}</p>
                @endif
                <p>
                    {{ $identity['phone'] ?? '' }}
                    @if (! empty($identity['phone']) && ! empty($identity['email']))
                        |
                    @endif
                    {{ $identity['email'] ?? '' }}
                </p>
            </div>
        </div>
    </header>

    <section class="lms-report-patient-card">
        <h4 class="lms-report-patient-title">INFORMATIONS PATIENT</h4>
        <div class="lms-report-patient-grid">
            <p><strong>{{ __('messages.patient') }}:</strong> {{ $analysis->patient?->full_name ?: '-' }}</p>
            <p><strong>{{ __('messages.sex') }}:</strong> {{ $patientSexLabel }}</p>
            <p><strong>{{ __('messages.age') }}:</strong> {{ $analysis->patient?->age ?? '-' }}</p>
            <p><strong>ID:</strong> {{ $analysis->patient?->identifier ?: '-' }}</p>
            <p><strong>{{ __('messages.analysis_date') }}:</strong> {{ $analysisDate ?: '-' }}</p>
            <p><strong>{{ __('messages.analysis_number') }}:</strong> {{ $analysis->analysis_number ?: '-' }}</p>
            <p><strong>{{ __('messages.phone') }}:</strong> {{ $analysis->patient?->phone ?: '-' }}</p>
        </div>
    </section>

    <h4 class="lms-report-title">COMPTE RENDU DES RÉSULTATS D’ANALYSE</h4>

    @if (empty($groupedResults))
        <p>{{ __('messages.no_results') }}</p>
    @else
        <table class="lms-table lms-report-table lms-report-table-single">
            <thead>
                <tr>
                    <th>Analyses</th>
                    <th>{{ __('messages.result') }}</th>
                    @if ($showUnitColumn)
                        <th>{{ __('messages.unit') }}</th>
                    @endif
                    <th>REFERENCE</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($groupedResults as $discipline)
                    <tr class="lms-report-row lms-report-row-level-0">
                        <td class="lms-report-analysis-cell lms-report-indent-0">{{ $discipline['label'] }}</td>
                        <td></td>
                        @if ($showUnitColumn)
                            <td></td>
                        @endif
                        <td></td>
                    </tr>

                    @foreach ($discipline['categories'] as $category)
                        <tr class="lms-report-row lms-report-row-level-1">
                            <td class="lms-report-analysis-cell lms-report-indent-1">{{ $category['label'] }}</td>
                            <td></td>
                            @if ($showUnitColumn)
                                <td></td>
                            @endif
                            <td></td>
                        </tr>

                        @foreach ($category['subcategories'] as $subcategory)
                            @foreach ($subcategory['lineage'] as $lineage)
                                @php
                                    $displayLevel = 1 + (int) $lineage['depth'];
                                @endphp
                                <tr class="lms-report-row lms-report-row-level-{{ $displayLevel }}">
                                    <td class="lms-report-analysis-cell lms-report-indent-{{ $displayLevel }}">
                                        {{ $lineage['label'] }}
                                    </td>
                                    <td></td>
                                    @if ($showUnitColumn)
                                        <td></td>
                                    @endif
                                    <td></td>
                                </tr>
                            @endforeach

                            @foreach ($subcategory['rows'] as $row)
                                @php
                                    $parameterLevel = 2 + count($subcategory['lineage']);
                                @endphp
                                <tr class="lms-report-row lms-report-row-leaf {{ $row['is_abnormal'] && $highlightAbnormal ? 'is-abnormal' : '' }}">
                                    <td class="lms-report-analysis-cell lms-report-indent-{{ $parameterLevel }}">
                                        {{ $row['parameter'] }}
                                    </td>
                                    <td class="lms-report-result-cell">{{ $row['result'] }}</td>
                                    @if ($showUnitColumn)
                                        <td>{{ $row['unit'] }}</td>
                                    @endif
                                    <td>{{ $row['reference'] }}</td>
                                </tr>
                            @endforeach
                        @endforeach
                    @endforeach
                @endforeach
            </tbody>
        </table>
    @endif

    @if (! empty($analysis->notes))
        <section class="lms-report-notes">
            <h5>{{ __('messages.notes') }}</h5>
            <p>{{ $analysis->notes }}</p>
        </section>
    @endif

    @if (! empty($identity['footer_note']))
        <footer class="lms-report-footer">
            <p>{{ $identity['footer_note'] }}</p>
        </footer>
    @endif

    <section class="lms-report-signature-wrap">
        <div class="lms-report-signature-box">
            <p class="lms-report-signature-date">Bamako, le {{ $analysisDate ?: '-' }}</p>
            <div class="lms-report-signature-space"></div>
            <p class="lms-report-signature-label">Signature</p>
        </div>
    </section>
</article>
