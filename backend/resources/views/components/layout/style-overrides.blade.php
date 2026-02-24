@php
    use App\Models\LabSetting;
    use App\Support\LabSettingsDefaults;

    $clampInt = static fn (int $value, int $min, int $max): int => max($min, min($max, $value));
    $clampFloat = static fn (float $value, float $min, float $max): float => max($min, min($max, $value));

    $uiDefaults = LabSettingsDefaults::uiAppearance();
    $uiRaw = LabSetting::getValue('ui_appearance', []);
    if (! is_array($uiRaw)) {
        $uiRaw = [];
    }
    $ui = [
        ...$uiDefaults,
        ...$uiRaw,
    ];

    $appFontStacks = [
        'legacy' => "'Inter', 'IBM Plex Sans', 'Segoe UI', 'Noto Sans', 'Helvetica Neue', Arial, sans-serif",
        'inter' => "'Inter', 'Segoe UI', 'Noto Sans', Arial, sans-serif",
        'roboto' => "'Roboto', 'Arial', 'Noto Sans', 'Helvetica Neue', sans-serif",
        'medical' => "'Source Sans 3', 'Trebuchet MS', Verdana, 'Noto Sans', sans-serif",
        'robotic' => "'Orbitron', 'Rajdhani', 'Consolas', 'Lucida Console', 'Courier New', monospace",
        'mono' => "'JetBrains Mono', 'Fira Code', 'Cascadia Mono', 'SFMono-Regular', Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace",
        'serif' => "'Georgia', 'Times New Roman', 'Liberation Serif', serif",
    ];
    $appFontKey = (string) ($ui['app_font_family'] ?? $uiDefaults['app_font_family']);
    if (! array_key_exists($appFontKey, $appFontStacks)) {
        $appFontKey = (string) $uiDefaults['app_font_family'];
    }

    $uiFontScaleMap = [
        'compact' => 0.95,
        'standard' => 1.00,
        'comfortable' => 1.08,
    ];
    $uiScaleKey = (string) ($ui['ui_font_size_level'] ?? $uiDefaults['ui_font_size_level']);
    if (! array_key_exists($uiScaleKey, $uiFontScaleMap)) {
        $uiScaleKey = (string) $uiDefaults['ui_font_size_level'];
    }
    $uiFontScale = $uiFontScaleMap[$uiScaleKey];

    $labelFontSizePx = $clampInt((int) ($ui['label_font_size_px'] ?? $uiDefaults['label_font_size_px']), 11, 18);
    $labelFontSizeRem = $labelFontSizePx / 16;
    $labelWeight = (string) ($ui['label_font_weight'] ?? $uiDefaults['label_font_weight']);
    if (! in_array($labelWeight, ['500', '600', '700'], true)) {
        $labelWeight = (string) $uiDefaults['label_font_weight'];
    }

    $labelLetterSpacing = round($clampFloat((float) ($ui['label_letter_spacing_em'] ?? $uiDefaults['label_letter_spacing_em']), -0.02, 0.12), 2);
    $labelTransform = (string) ($ui['label_text_transform'] ?? $uiDefaults['label_text_transform']);
    if (! in_array($labelTransform, ['none', 'uppercase', 'capitalize'], true)) {
        $labelTransform = (string) $uiDefaults['label_text_transform'];
    }

    $motionProfile = (string) ($ui['motion_profile'] ?? $uiDefaults['motion_profile']);
    $motionProfiles = [
        'snappy' => [
            'duration' => 140,
            'enter' => 190,
            'exit' => 90,
            'ease' => 'cubic-bezier(0.36, 0.66, 0.4, 1)',
        ],
        'soft' => [
            'duration' => 200,
            'enter' => 260,
            'exit' => 130,
            'ease' => 'cubic-bezier(0.2, 0.8, 0.2, 1)',
        ],
        'fluid' => [
            'duration' => 260,
            'enter' => 320,
            'exit' => 170,
            'ease' => 'cubic-bezier(0.16, 1, 0.3, 1)',
        ],
    ];
    if (! array_key_exists($motionProfile, $motionProfiles)) {
        $motionProfile = (string) $uiDefaults['motion_profile'];
    }
    $motion = $motionProfiles[$motionProfile];

    $layoutDefaults = LabSettingsDefaults::reportLayout();
    $layoutRaw = LabSetting::getValue('report_layout', []);
    if (! is_array($layoutRaw)) {
        $layoutRaw = [];
    }
    $layout = [
        ...$layoutDefaults,
        ...$layoutRaw,
    ];

    $reportFontStacks = [
        'medical' => "'IBM Plex Sans', 'Source Sans 3', 'Segoe UI', 'Noto Sans', sans-serif",
        'legacy' => "'IBM Plex Sans', 'Segoe UI', 'Noto Sans', Arial, sans-serif",
        'inter' => "'Inter', 'Segoe UI', 'Noto Sans', Arial, sans-serif",
        'roboto' => "'Roboto', 'Arial', 'Noto Sans', 'Helvetica Neue', sans-serif",
        'robotic' => "'Orbitron', 'Rajdhani', 'Consolas', 'Lucida Console', 'Courier New', monospace",
        'mono' => "'JetBrains Mono', 'Fira Code', 'Cascadia Mono', 'SFMono-Regular', Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace",
        'serif' => "'Georgia', 'Times New Roman', 'Liberation Serif', serif",
    ];
    $reportFontKey = (string) ($layout['report_font_family'] ?? $layoutDefaults['report_font_family']);
    if (! array_key_exists($reportFontKey, $reportFontStacks)) {
        $reportFontKey = (string) $layoutDefaults['report_font_family'];
    }

    $reportLabNameSize = $clampInt((int) ($layout['report_lab_name_size_px'] ?? $layoutDefaults['report_lab_name_size_px']), 14, 28);
    $reportLabMetaSize = $clampInt((int) ($layout['report_lab_meta_size_px'] ?? $layoutDefaults['report_lab_meta_size_px']), 10, 20);
    $reportTitleSize = $clampInt((int) ($layout['report_title_size_px'] ?? $layoutDefaults['report_title_size_px']), 16, 34);
    $reportPatientTitleSize = $clampInt((int) ($layout['report_patient_title_size_px'] ?? $layoutDefaults['report_patient_title_size_px']), 11, 20);
    $reportPatientTextSize = $clampInt((int) ($layout['report_patient_text_size_px'] ?? $layoutDefaults['report_patient_text_size_px']), 10, 18);
    $reportTableHeaderSize = $clampInt((int) ($layout['report_table_header_size_px'] ?? $layoutDefaults['report_table_header_size_px']), 10, 16);
    $reportTableBodySize = $clampInt((int) ($layout['report_table_body_size_px'] ?? $layoutDefaults['report_table_body_size_px']), 10, 16);
    $reportLevel0Size = $clampInt((int) ($layout['report_level0_size_px'] ?? $layoutDefaults['report_level0_size_px']), 12, 20);
    $reportLevel1Size = $clampInt((int) ($layout['report_level1_size_px'] ?? $layoutDefaults['report_level1_size_px']), 12, 18);
    $reportLevel2Size = $clampInt((int) ($layout['report_level2_size_px'] ?? $layoutDefaults['report_level2_size_px']), 11, 17);
    $reportLevel3Size = $clampInt((int) ($layout['report_level3_size_px'] ?? $layoutDefaults['report_level3_size_px']), 10, 16);
    $reportLeafSize = $clampInt((int) ($layout['report_leaf_size_px'] ?? $layoutDefaults['report_leaf_size_px']), 10, 16);
@endphp

<style>
    :root {
        --font-sans: {{ $appFontStacks[$appFontKey] }};
        --lms-ui-font-scale: {{ number_format($uiFontScale, 2, '.', '') }};
        --lms-label-font-size: {{ number_format($labelFontSizeRem, 4, '.', '') }}rem;
        --lms-label-font-weight: {{ $labelWeight }};
        --lms-label-letter-spacing: {{ number_format($labelLetterSpacing, 2, '.', '') }}em;
        --lms-label-text-transform: {{ $labelTransform }};

        --ui-transition-duration: {{ $motion['duration'] }}ms;
        --ui-transition-ease: {{ $motion['ease'] }};
        --lms-route-enter-duration: {{ $motion['enter'] }}ms;
        --lms-route-exit-duration: {{ $motion['exit'] }}ms;

        --lms-report-font-family: {{ $reportFontStacks[$reportFontKey] }};
        --lms-report-lab-name-size: {{ $reportLabNameSize }}px;
        --lms-report-lab-meta-size: {{ $reportLabMetaSize }}px;
        --lms-report-title-size: {{ $reportTitleSize }}px;
        --lms-report-patient-title-size: {{ $reportPatientTitleSize }}px;
        --lms-report-patient-text-size: {{ $reportPatientTextSize }}px;
        --lms-report-table-header-size: {{ $reportTableHeaderSize }}px;
        --lms-report-table-body-size: {{ $reportTableBodySize }}px;
        --lms-report-level0-size: {{ $reportLevel0Size }}px;
        --lms-report-level1-size: {{ $reportLevel1Size }}px;
        --lms-report-level2-size: {{ $reportLevel2Size }}px;
        --lms-report-level3-size: {{ $reportLevel3Size }}px;
        --lms-report-leaf-size: {{ $reportLeafSize }}px;
    }
</style>
