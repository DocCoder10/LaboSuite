<?php

namespace App\Support;

class ReportLayoutSettings
{
    /**
     * @var array<int, string>
     */
    public const REPORT_FONT_FAMILIES = ['robotic', 'medical', 'legacy', 'inter', 'roboto', 'mono', 'serif'];

    /**
     * @return array<string, string>
     */
    public static function fontStacks(): array
    {
        return [
            'medical' => "'IBM Plex Sans', 'Source Sans 3', 'Segoe UI', 'Noto Sans', sans-serif",
            'legacy' => "'IBM Plex Sans', 'Segoe UI', 'Noto Sans', Arial, sans-serif",
            'inter' => "'Inter', 'Segoe UI', 'Noto Sans', Arial, sans-serif",
            'roboto' => "'Roboto', 'Arial', 'Noto Sans', 'Helvetica Neue', sans-serif",
            'robotic' => "'Orbitron', 'Rajdhani', 'Consolas', 'Lucida Console', 'Courier New', monospace",
            'mono' => "'JetBrains Mono', 'Fira Code', 'Cascadia Mono', 'SFMono-Regular', Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace",
            'serif' => "'Georgia', 'Times New Roman', 'Liberation Serif', serif",
        ];
    }

    /**
     * @param  mixed  $rawLayout
     * @return array<string, mixed>
     */
    public static function normalize(mixed $rawLayout): array
    {
        $layout = is_array($rawLayout) ? $rawLayout : [];
        $defaults = LabSettingsDefaults::reportLayout();

        $reportFontFamily = (string) ($layout['report_font_family'] ?? $defaults['report_font_family']);
        if (! in_array($reportFontFamily, self::REPORT_FONT_FAMILIES, true)) {
            $reportFontFamily = (string) $defaults['report_font_family'];
        }

        $reportLabNameSize = self::clampInt((int) ($layout['report_lab_name_size_px'] ?? $defaults['report_lab_name_size_px']), 14, 28);
        $reportLabMetaSize = self::clampInt((int) ($layout['report_lab_meta_size_px'] ?? $defaults['report_lab_meta_size_px']), 10, 20);
        $reportTitleSize = self::clampInt((int) ($layout['report_title_size_px'] ?? $defaults['report_title_size_px']), 16, 34);
        $reportPatientTitleSize = self::clampInt((int) ($layout['report_patient_title_size_px'] ?? $defaults['report_patient_title_size_px']), 11, 20);
        $reportPatientTextSize = self::clampInt((int) ($layout['report_patient_text_size_px'] ?? $defaults['report_patient_text_size_px']), 10, 18);
        $reportTableHeaderSize = self::clampInt((int) ($layout['report_table_header_size_px'] ?? $defaults['report_table_header_size_px']), 10, 16);
        $reportTableBodySize = self::clampInt((int) ($layout['report_table_body_size_px'] ?? $defaults['report_table_body_size_px']), 10, 16);
        $reportLevel0Size = self::clampInt((int) ($layout['report_level0_size_px'] ?? $defaults['report_level0_size_px']), 12, 20);
        $reportLevel1Size = self::clampInt((int) ($layout['report_level1_size_px'] ?? $defaults['report_level1_size_px']), 12, 18);
        $reportLevel2Size = self::clampInt((int) ($layout['report_level2_size_px'] ?? $defaults['report_level2_size_px']), 11, 17);
        $reportLevel3Size = self::clampInt((int) ($layout['report_level3_size_px'] ?? $defaults['report_level3_size_px']), 10, 16);
        $reportLeafSize = self::clampInt((int) ($layout['report_leaf_size_px'] ?? $defaults['report_leaf_size_px']), 10, 16);

        // Keep coherent document hierarchy and avoid broken typography structure.
        $reportLabNameSize = max($reportLabNameSize, $reportLabMetaSize + 2);
        $reportTitleSize = max($reportTitleSize, $reportLabNameSize + 1);
        $reportPatientTextSize = max($reportTableBodySize - 1, min($reportPatientTextSize, $reportTableBodySize + 1));
        $reportPatientTitleSize = max($reportPatientTitleSize, $reportPatientTextSize);
        $reportTableHeaderSize = max($reportTableBodySize - 1, min($reportTableHeaderSize, $reportTableBodySize + 1));

        $reportLevel0Size = max($reportLevel0Size, $reportTableBodySize + 2);
        $reportLevel1Size = max($reportLevel1Size, $reportTableBodySize + 1);
        $reportLevel2Size = max($reportLevel2Size, $reportTableBodySize);
        $reportLevel3Size = max($reportLevel3Size, $reportTableBodySize - 1);
        $reportLeafSize = max($reportLeafSize, $reportTableBodySize - 1);

        $reportLevel1Size = min($reportLevel1Size, $reportLevel0Size);
        $reportLevel2Size = min($reportLevel2Size, $reportLevel1Size);
        $reportLevel3Size = min($reportLevel3Size, $reportLevel2Size);
        $reportLeafSize = min($reportLeafSize, $reportLevel3Size);

        return [
            'show_unit_column' => self::normalizeBool($layout['show_unit_column'] ?? $defaults['show_unit_column']),
            'highlight_abnormal' => self::normalizeBool($layout['highlight_abnormal'] ?? $defaults['highlight_abnormal'], true),
            'discipline_title_size' => (string) ($layout['discipline_title_size'] ?? $defaults['discipline_title_size']),
            'category_title_size' => (string) ($layout['category_title_size'] ?? $defaults['category_title_size']),
            'report_font_family' => $reportFontFamily,
            'report_lab_name_size_px' => $reportLabNameSize,
            'report_lab_meta_size_px' => $reportLabMetaSize,
            'report_title_size_px' => $reportTitleSize,
            'report_patient_title_size_px' => $reportPatientTitleSize,
            'report_patient_text_size_px' => $reportPatientTextSize,
            'report_table_header_size_px' => $reportTableHeaderSize,
            'report_table_body_size_px' => $reportTableBodySize,
            'report_level0_size_px' => $reportLevel0Size,
            'report_level1_size_px' => $reportLevel1Size,
            'report_level2_size_px' => $reportLevel2Size,
            'report_level3_size_px' => $reportLevel3Size,
            'report_leaf_size_px' => $reportLeafSize,
        ];
    }

    private static function clampInt(int $value, int $min, int $max): int
    {
        return max($min, min($max, $value));
    }

    private static function normalizeBool(mixed $value, bool $default = false): bool
    {
        if ($value === null) {
            return $default;
        }

        $resolved = filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

        return $resolved ?? $default;
    }
}
