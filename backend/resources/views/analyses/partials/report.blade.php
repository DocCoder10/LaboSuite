@php
    $showUnitColumn = (bool) ($layout['show_unit_column'] ?? false);
    $highlightAbnormal = (bool) ($layout['highlight_abnormal'] ?? true);
    $indentBaseMm = 1;
    $indentStepMm = 8;
    $analysisDate = optional($analysis->analysis_date)->format('d/m/Y');
    $patientSex = $analysis->patient?->sex;
    $patientSexLabel = match ($patientSex) {
        'male' => __('messages.male'),
        'female' => __('messages.female'),
        'other' => __('messages.other'),
        default => '-',
    };
    $headerInfoPosition = (string) ($identity['header_info_position'] ?? 'center');
    if (! in_array($headerInfoPosition, ['left', 'center', 'right'], true)) {
        $headerInfoPosition = 'center';
    }
    $headerLogoMode = (string) ($identity['header_logo_mode'] ?? 'single_left');
    if ($headerInfoPosition === 'left') {
        $headerLogoMode = 'single_right';
    } elseif ($headerInfoPosition === 'right') {
        $headerLogoMode = 'single_left';
    } elseif (! in_array($headerLogoMode, ['single_left', 'single_right', 'both_distinct', 'both_same'], true)) {
        $headerLogoMode = 'single_left';
    }
    $leftLogoPath = trim((string) ($identity['logo_left_path'] ?? ($identity['logo_path'] ?? '')));
    $rightLogoPath = trim((string) ($identity['logo_right_path'] ?? ''));
    $legacyLogoUrl = trim((string) ($identity['logo_url'] ?? ''));
    $leftLogoSrc = $leftLogoPath !== '' ? \Illuminate\Support\Facades\Storage::disk('public')->url($leftLogoPath) : $legacyLogoUrl;
    $rightLogoSrc = $rightLogoPath !== '' ? \Illuminate\Support\Facades\Storage::disk('public')->url($rightLogoPath) : '';
    $displayLeftLogo = '';
    $displayRightLogo = '';
    $logoSizePx = max(96, min(240, (int) ($identity['header_logo_size_px'] ?? 170)));
    $logoSlotWidthPx = max(140, min(300, $logoSizePx + 24));

    $resolveLegacyPosition = static function (mixed $legacyOffset): string {
        $offset = (int) $legacyOffset;

        if ($offset <= -8) {
            return 'left';
        }

        if ($offset >= 8) {
            return 'right';
        }

        return 'center';
    };

    $normalizePosition = static function (mixed $position): string {
        $resolved = is_string($position) ? $position : 'center';

        return in_array($resolved, ['left', 'center', 'right'], true) ? $resolved : 'center';
    };

    $logoPositionLeft = $normalizePosition($identity['header_logo_position_left'] ?? $resolveLegacyPosition($identity['header_logo_offset_x_left'] ?? 0));
    $logoPositionRight = $normalizePosition($identity['header_logo_position_right'] ?? $resolveLegacyPosition($identity['header_logo_offset_x_right'] ?? 0));

    $resolveJustify = static fn (string $position): string => match ($position) {
        'left' => 'flex-start',
        'right' => 'flex-end',
        default => 'center',
    };
    $logoJustifyLeft = $resolveJustify($logoPositionLeft);
    $logoJustifyRight = $resolveJustify($logoPositionRight);

    if ($headerLogoMode === 'single_left') {
        $displayLeftLogo = $leftLogoSrc !== '' ? $leftLogoSrc : $rightLogoSrc;
    } elseif ($headerLogoMode === 'single_right') {
        $displayRightLogo = $rightLogoSrc !== '' ? $rightLogoSrc : $leftLogoSrc;
    } elseif ($headerLogoMode === 'both_distinct') {
        $displayLeftLogo = $leftLogoSrc;
        $displayRightLogo = $rightLogoSrc;
    } else {
        $sharedLogo = $leftLogoSrc !== '' ? $leftLogoSrc : $rightLogoSrc;
        $displayLeftLogo = $sharedLogo;
        $displayRightLogo = $sharedLogo;
    }

    $normalizeLabel = static fn (?string $value): string => mb_strtolower(trim((string) $value));
    $indentLevel = static fn (int $level): int => max(0, $level);
    $indentStyle = static fn (int $level) => 'padding-left: '.($indentBaseMm + ($indentLevel($level) * $indentStepMm)).'mm;';
    $reportFontStacks = \App\Support\ReportLayoutSettings::fontStacks();
    $reportFontKey = (string) ($layout['report_font_family'] ?? \App\Support\LabSettingsDefaults::reportLayout()['report_font_family']);
    if (! array_key_exists($reportFontKey, $reportFontStacks)) {
        $reportFontKey = (string) \App\Support\LabSettingsDefaults::reportLayout()['report_font_family'];
    }

    $reportTypographyInlineStyle = collect([
        '--lms-report-font-family: '.$reportFontStacks[$reportFontKey],
        '--lms-logo-slot-width: '.$logoSlotWidthPx.'px',
        '--lms-report-lab-name-size: '.((int) ($layout['report_lab_name_size_px'] ?? 18)).'px',
        '--lms-report-lab-meta-size: '.((int) ($layout['report_lab_meta_size_px'] ?? 13)).'px',
        '--lms-report-title-size: '.((int) ($layout['report_title_size_px'] ?? 20)).'px',
        '--lms-report-patient-title-size: '.((int) ($layout['report_patient_title_size_px'] ?? 13)).'px',
        '--lms-report-patient-text-size: '.((int) ($layout['report_patient_text_size_px'] ?? 13)).'px',
        '--lms-report-table-header-size: '.((int) ($layout['report_table_header_size_px'] ?? 12)).'px',
        '--lms-report-table-body-size: '.((int) ($layout['report_table_body_size_px'] ?? 13)).'px',
        '--lms-report-level0-size: '.((int) ($layout['report_level0_size_px'] ?? 16)).'px',
        '--lms-report-level1-size: '.((int) ($layout['report_level1_size_px'] ?? 15)).'px',
        '--lms-report-level2-size: '.((int) ($layout['report_level2_size_px'] ?? 14)).'px',
        '--lms-report-level3-size: '.((int) ($layout['report_level3_size_px'] ?? 13)).'px',
        '--lms-report-leaf-size: '.((int) ($layout['report_leaf_size_px'] ?? 13)).'px',
    ])->join('; ');
@endphp

<article class="lms-card lms-report" style="{{ $reportTypographyInlineStyle }}">
    <header class="lms-report-head">
        @if ($headerInfoPosition === 'center')
            <div class="lms-report-header-grid is-info-center">
                <div class="lms-report-logo-slot lms-report-logo-slot-left {{ $displayLeftLogo === '' ? 'is-empty' : '' }}" style="--lms-logo-justify: {{ $logoJustifyLeft }};">
                    @if ($displayLeftLogo !== '')
                        <img
                            src="{{ $displayLeftLogo }}"
                            alt="Logo gauche laboratoire"
                            class="lms-report-logo"
                            style="--lms-logo-max-width: {{ $logoSizePx }}px;"
                        >
                    @endif
                </div>

                <div class="lms-report-lab-text lms-report-lab-text-center">
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

                <div class="lms-report-logo-slot lms-report-logo-slot-right {{ $displayRightLogo === '' ? 'is-empty' : '' }}" style="--lms-logo-justify: {{ $logoJustifyRight }};">
                    @if ($displayRightLogo !== '')
                        <img
                            src="{{ $displayRightLogo }}"
                            alt="Logo droit laboratoire"
                            class="lms-report-logo"
                            style="--lms-logo-max-width: {{ $logoSizePx }}px;"
                        >
                    @endif
                </div>
            </div>
        @elseif ($headerInfoPosition === 'left')
            <div class="lms-report-header-grid is-info-left">
                <div class="lms-report-lab-text lms-report-lab-text-left">
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

                <div class="lms-report-logo-slot lms-report-logo-slot-right {{ $displayRightLogo === '' ? 'is-empty' : '' }}" style="--lms-logo-justify: {{ $logoJustifyRight }};">
                    @if ($displayRightLogo !== '')
                        <img
                            src="{{ $displayRightLogo }}"
                            alt="Logo droit laboratoire"
                            class="lms-report-logo"
                            style="--lms-logo-max-width: {{ $logoSizePx }}px;"
                        >
                    @endif
                </div>
            </div>
        @else
            <div class="lms-report-header-grid is-info-right">
                <div class="lms-report-logo-slot lms-report-logo-slot-left {{ $displayLeftLogo === '' ? 'is-empty' : '' }}" style="--lms-logo-justify: {{ $logoJustifyLeft }};">
                    @if ($displayLeftLogo !== '')
                        <img
                            src="{{ $displayLeftLogo }}"
                            alt="Logo gauche laboratoire"
                            class="lms-report-logo"
                            style="--lms-logo-max-width: {{ $logoSizePx }}px;"
                        >
                    @endif
                </div>

                <div class="lms-report-lab-text lms-report-lab-text-right">
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
        @endif
    </header>

    <section class="lms-report-patient-card">
        <h4 class="lms-report-patient-title">INFORMATIONS PATIENT</h4>
        <div class="lms-report-patient-grid">
            <p><strong>{{ __('messages.patient') }}:</strong> {{ $analysis->patient?->full_name ?: '-' }}</p>
            <p><strong>{{ __('messages.age') }}:</strong> {{ $analysis->patient?->age ?? '-' }}</p>
            <p><strong>{{ __('messages.sex') }}:</strong> {{ $patientSexLabel }}</p>
            <p><strong>{{ __('messages.phone') }}:</strong> {{ $analysis->patient?->phone ?: '-' }}</p>
            <p><strong>ID:</strong> {{ $analysis->patient?->display_identifier ?: '-' }}</p>
            <p><strong>{{ __('messages.analysis_date') }}:</strong> {{ $analysisDate ?: '-' }}</p>
            <p><strong>{{ __('messages.analysis_number') }}:</strong> {{ $analysis->analysis_number ?: '-' }}</p>
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
                    <tr class="lms-report-row lms-report-row-level-0 lms-report-row-discipline">
                        <td class="lms-report-analysis-cell lms-report-indented" style="{{ $indentStyle(0) }}">{{ $discipline['label'] }}</td>
                        <td></td>
                        @if ($showUnitColumn)
                            <td></td>
                        @endif
                        <td></td>
                    </tr>

                    @foreach ($discipline['categories'] as $category)
                        @php
                            $categorySubgroups = $category['subcategories'] ?? [];
                            $categoryMergeRow = null;
                            $categoryMergeEnabled = false;

                            if (count($categorySubgroups) === 1) {
                                $onlyGroup = $categorySubgroups[0];
                                $onlyGroupLineage = $onlyGroup['lineage'] ?? [];
                                $onlyGroupRows = $onlyGroup['rows'] ?? [];

                                if (count($onlyGroupLineage) === 0 && count($onlyGroupRows) === 1) {
                                    $candidateRow = $onlyGroupRows[0];
                                    $categoryMergeEnabled = $normalizeLabel($candidateRow['parameter'] ?? '') === $normalizeLabel($category['label'] ?? '');
                                    $categoryMergeRow = $categoryMergeEnabled ? $candidateRow : null;
                                }
                            }
                        @endphp
                        <tr class="lms-report-row lms-report-row-level-1 {{ $categoryMergeEnabled && ($categoryMergeRow['is_abnormal'] ?? false) && $highlightAbnormal ? 'is-abnormal' : '' }}">
                            <td class="lms-report-analysis-cell lms-report-indented" style="{{ $indentStyle(1) }}">{{ $category['label'] }}</td>
                            <td class="lms-report-result-cell">
                                @if ($categoryMergeEnabled)
                                    <span class="lms-report-result-value">{{ $categoryMergeRow['result'] ?? '' }}</span>
                                    @if (! empty($categoryMergeRow['abnormal_flag']))
                                        <span class="lms-report-result-flag">{{ $categoryMergeRow['abnormal_flag'] }}</span>
                                    @endif
                                @endif
                            </td>
                            @if ($showUnitColumn)
                                <td>{{ $categoryMergeEnabled ? ($categoryMergeRow['unit'] ?? '-') : '' }}</td>
                            @endif
                            <td>{{ $categoryMergeEnabled ? ($categoryMergeRow['reference'] ?? '-') : '' }}</td>
                        </tr>

                        @foreach ($categorySubgroups as $subcategory)
                            @php
                                $lineage = $subcategory['lineage'] ?? [];
                                $rows = $subcategory['rows'] ?? [];
                                $singleRow = count($rows) === 1 ? $rows[0] : null;
                                $lastLineage = ! empty($lineage) ? $lineage[count($lineage) - 1] : null;
                                $mergeLeafIntoLineage = $singleRow !== null
                                    && is_array($lastLineage)
                                    && $normalizeLabel($lastLineage['label'] ?? '') === $normalizeLabel($singleRow['parameter'] ?? '');
                                $lineageToRender = $mergeLeafIntoLineage ? array_slice($lineage, 0, -1) : $lineage;
                                $skipBecauseMergedIntoCategory = $categoryMergeEnabled
                                    && count($lineage) === 0
                                    && $singleRow !== null
                                    && $normalizeLabel($singleRow['parameter'] ?? '') === $normalizeLabel($category['label'] ?? '');
                            @endphp

                            @if ($skipBecauseMergedIntoCategory)
                                @continue
                            @endif

                            @foreach ($lineageToRender as $lineageNode)
                                @php
                                    $displayLevel = max(2, 1 + (int) ($lineageNode['depth'] ?? 1));
                                @endphp
                                <tr class="lms-report-row lms-report-row-level-{{ $displayLevel }}">
                                    <td class="lms-report-analysis-cell lms-report-indented" style="{{ $indentStyle($displayLevel) }}">
                                        {{ $lineageNode['label'] }}
                                    </td>
                                    <td></td>
                                    @if ($showUnitColumn)
                                        <td></td>
                                    @endif
                                    <td></td>
                                </tr>
                            @endforeach

                            @if ($mergeLeafIntoLineage && $singleRow !== null && is_array($lastLineage))
                                @php
                                    $leafLevel = max(2, 1 + (int) ($lastLineage['depth'] ?? 1));
                                @endphp
                                <tr class="lms-report-row lms-report-row-leaf {{ $singleRow['is_abnormal'] && $highlightAbnormal ? 'is-abnormal' : '' }}">
                                    <td class="lms-report-analysis-cell lms-report-indented" style="{{ $indentStyle($leafLevel) }}">
                                        {{ $lastLineage['label'] }}
                                    </td>
                                    <td class="lms-report-result-cell">
                                        <span class="lms-report-result-value">{{ $singleRow['result'] }}</span>
                                        @if (! empty($singleRow['abnormal_flag']))
                                            <span class="lms-report-result-flag">{{ $singleRow['abnormal_flag'] }}</span>
                                        @endif
                                    </td>
                                    @if ($showUnitColumn)
                                        <td>{{ $singleRow['unit'] }}</td>
                                    @endif
                                    <td>{{ $singleRow['reference'] }}</td>
                                </tr>
                            @else
                                @foreach ($rows as $row)
                                    @php
                                        $parameterLevel = max(2, 2 + count($lineage));
                                    @endphp
                                    <tr class="lms-report-row lms-report-row-leaf {{ $row['is_abnormal'] && $highlightAbnormal ? 'is-abnormal' : '' }}">
                                        <td class="lms-report-analysis-cell lms-report-indented" style="{{ $indentStyle($parameterLevel) }}">
                                            {{ $row['parameter'] }}
                                        </td>
                                        <td class="lms-report-result-cell">
                                            <span class="lms-report-result-value">{{ $row['result'] }}</span>
                                            @if (! empty($row['abnormal_flag']))
                                                <span class="lms-report-result-flag">{{ $row['abnormal_flag'] }}</span>
                                            @endif
                                        </td>
                                        @if ($showUnitColumn)
                                            <td>{{ $row['unit'] }}</td>
                                        @endif
                                        <td>{{ $row['reference'] }}</td>
                                    </tr>
                                @endforeach
                            @endif
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
