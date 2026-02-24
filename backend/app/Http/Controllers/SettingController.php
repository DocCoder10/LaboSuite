<?php

namespace App\Http\Controllers;

use App\Models\LabSetting;
use App\Support\LabSettingsDefaults;
use App\Support\PatientFieldManager;
use App\Support\ReportLayoutSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SettingController extends Controller
{
    /**
     * @var array<int, string>
     */
    private const SECTIONS = ['lab', 'pdf', 'patient'];
    private const HEADER_INFO_POSITIONS = ['left', 'center', 'right'];
    private const HEADER_LOGO_MODES = ['single_left', 'single_right', 'both_distinct', 'both_same'];
    private const HEADER_LOGO_POSITIONS = ['left', 'center', 'right'];
    private const APP_FONT_FAMILIES = ['legacy', 'inter', 'roboto', 'medical', 'robotic', 'mono', 'serif'];
    private const UI_FONT_SIZE_LEVELS = ['compact', 'standard', 'comfortable'];
    private const LABEL_FONT_WEIGHTS = ['500', '600', '700'];
    private const LABEL_TEXT_TRANSFORMS = ['none', 'uppercase', 'capitalize'];
    private const MOTION_PROFILES = ['snappy', 'soft', 'fluid'];

    public function edit(Request $request): View
    {
        $activeSection = $this->resolveSection((string) $request->query('section', 'lab'));
        $patientForm = PatientFieldManager::resolved(LabSetting::getValue('patient_form', []));
        $identity = $this->normalizeLabIdentity(LabSetting::getValue('lab_identity', []));
        $layout = $this->normalizeReportLayout(LabSetting::getValue('report_layout', []));
        $uiAppearance = $this->normalizeUiAppearance(LabSetting::getValue('ui_appearance', []));

        return view('settings.edit', [
            'identity' => $identity,
            'layout' => $layout,
            'uiAppearance' => $uiAppearance,
            'patientForm' => $patientForm,
            'activeSection' => $activeSection,
            'uiOptions' => [
                'app_font_families' => self::APP_FONT_FAMILIES,
                'ui_font_size_levels' => self::UI_FONT_SIZE_LEVELS,
                'label_font_weights' => self::LABEL_FONT_WEIGHTS,
                'label_text_transforms' => self::LABEL_TEXT_TRANSFORMS,
                'motion_profiles' => self::MOTION_PROFILES,
                'report_font_families' => ReportLayoutSettings::REPORT_FONT_FAMILIES,
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $section = $this->resolveSection((string) $request->input('section', 'lab'));
        $action = (string) $request->input('action', 'save');

        if ($action === 'reset') {
            $this->resetSection($section);

            return redirect()
                ->route('settings.edit', ['section' => $section])
                ->with('status', __('messages.settings_reset_done', [
                    'section' => $this->sectionLabel($section),
                ]));
        }

        if ($section === 'lab') {
            $data = $request->validate([
                'name' => ['required', 'string', 'max:150'],
                'address' => ['nullable', 'string', 'max:255'],
                'phone' => ['nullable', 'string', 'max:40'],
                'email' => ['nullable', 'email', 'max:120'],
                'header_note' => ['nullable', 'string', 'max:255'],
                'footer_note' => ['nullable', 'string', 'max:255'],
                'header_info_position' => ['required', Rule::in(self::HEADER_INFO_POSITIONS)],
                'header_logo_mode' => ['nullable', Rule::in(self::HEADER_LOGO_MODES)],
                'header_logo_size_px' => ['nullable', 'integer', 'min:96', 'max:240'],
                'header_logo_position_left' => ['nullable', Rule::in(self::HEADER_LOGO_POSITIONS)],
                'header_logo_position_right' => ['nullable', Rule::in(self::HEADER_LOGO_POSITIONS)],
                'logo_left' => ['nullable', 'file', 'mimetypes:image/jpeg,image/png,image/gif,image/bmp,image/webp,image/svg+xml,image/avif,image/tiff,image/x-icon,image/vnd.microsoft.icon', 'max:8192'],
                'logo_right' => ['nullable', 'file', 'mimetypes:image/jpeg,image/png,image/gif,image/bmp,image/webp,image/svg+xml,image/avif,image/tiff,image/x-icon,image/vnd.microsoft.icon', 'max:8192'],
                'remove_logo_left' => ['nullable', 'boolean'],
                'remove_logo_right' => ['nullable', 'boolean'],
                'app_font_family' => ['nullable', Rule::in(self::APP_FONT_FAMILIES)],
                'ui_font_size_level' => ['nullable', Rule::in(self::UI_FONT_SIZE_LEVELS)],
                'label_font_size_px' => ['nullable', 'integer', 'min:11', 'max:18'],
                'label_font_weight' => ['nullable', Rule::in(self::LABEL_FONT_WEIGHTS)],
                'label_letter_spacing_em' => ['nullable', 'numeric', 'min:-0.02', 'max:0.12'],
                'label_text_transform' => ['nullable', Rule::in(self::LABEL_TEXT_TRANSFORMS)],
                'motion_profile' => ['nullable', Rule::in(self::MOTION_PROFILES)],
            ]);

            $identity = $this->normalizeLabIdentity(LabSetting::getValue('lab_identity', []));
            $leftPath = trim((string) ($identity['logo_left_path'] ?? ''));
            $rightPath = trim((string) ($identity['logo_right_path'] ?? ''));

            if ($request->boolean('remove_logo_left')) {
                $this->deleteStoredLogo($leftPath);
                $leftPath = '';
            }

            if ($request->boolean('remove_logo_right')) {
                $this->deleteStoredLogo($rightPath);
                $rightPath = '';
            }

            if ($request->file('logo_left') instanceof UploadedFile) {
                $newLeftPath = $this->storeLogo($request->file('logo_left'));
                $this->deleteStoredLogo($leftPath);
                $leftPath = $newLeftPath;
            }

            if ($request->file('logo_right') instanceof UploadedFile) {
                $newRightPath = $this->storeLogo($request->file('logo_right'));
                $this->deleteStoredLogo($rightPath);
                $rightPath = $newRightPath;
            }

            $infoPosition = (string) ($data['header_info_position'] ?? 'center');
            $logoMode = $this->normalizeHeaderLogoMode(
                $infoPosition,
                (string) ($data['header_logo_mode'] ?? 'single_left')
            );
            $logoSizePx = $this->normalizeLogoSize((int) ($data['header_logo_size_px'] ?? 170));
            $positionLeft = $this->normalizeLogoPosition((string) ($data['header_logo_position_left'] ?? 'center'));
            $positionRight = $this->normalizeLogoPosition((string) ($data['header_logo_position_right'] ?? 'center'));

            LabSetting::putValue('lab_identity', [
                'name' => $data['name'],
                'address' => $data['address'] ?? '',
                'phone' => $data['phone'] ?? '',
                'email' => $data['email'] ?? '',
                'header_note' => $data['header_note'] ?? '',
                'footer_note' => $data['footer_note'] ?? '',
                'header_info_position' => $infoPosition,
                'header_logo_mode' => $logoMode,
                'header_logo_size_px' => $logoSizePx,
                'header_logo_position_left' => $positionLeft,
                'header_logo_position_right' => $positionRight,
                'logo_left_path' => $leftPath !== '' ? $leftPath : null,
                'logo_right_path' => $rightPath !== '' ? $rightPath : null,
            ]);

            $uiAppearance = $this->normalizeUiAppearance(LabSetting::getValue('ui_appearance', []));
            LabSetting::putValue('ui_appearance', $this->normalizeUiAppearance([
                ...$uiAppearance,
                'app_font_family' => (string) $request->input('app_font_family', $uiAppearance['app_font_family']),
                'ui_font_size_level' => (string) $request->input('ui_font_size_level', $uiAppearance['ui_font_size_level']),
                'label_font_size_px' => (int) $request->input('label_font_size_px', $uiAppearance['label_font_size_px']),
                'label_font_weight' => (string) $request->input('label_font_weight', $uiAppearance['label_font_weight']),
                'label_letter_spacing_em' => (float) $request->input('label_letter_spacing_em', $uiAppearance['label_letter_spacing_em']),
                'label_text_transform' => (string) $request->input('label_text_transform', $uiAppearance['label_text_transform']),
                'motion_profile' => (string) $request->input('motion_profile', $uiAppearance['motion_profile']),
            ]));
        }

        if ($section === 'pdf') {
            $request->validate([
                'show_unit_column' => ['nullable', 'boolean'],
                'highlight_abnormal' => ['nullable', 'boolean'],
                'report_font_family' => ['nullable', Rule::in(ReportLayoutSettings::REPORT_FONT_FAMILIES)],
                'report_lab_name_size_px' => ['nullable', 'integer', 'min:14', 'max:28'],
                'report_lab_meta_size_px' => ['nullable', 'integer', 'min:10', 'max:20'],
                'report_title_size_px' => ['nullable', 'integer', 'min:16', 'max:34'],
                'report_patient_title_size_px' => ['nullable', 'integer', 'min:11', 'max:20'],
                'report_patient_text_size_px' => ['nullable', 'integer', 'min:10', 'max:18'],
                'report_table_header_size_px' => ['nullable', 'integer', 'min:10', 'max:16'],
                'report_table_body_size_px' => ['nullable', 'integer', 'min:10', 'max:16'],
                'report_level0_size_px' => ['nullable', 'integer', 'min:12', 'max:20'],
                'report_level1_size_px' => ['nullable', 'integer', 'min:12', 'max:18'],
                'report_level2_size_px' => ['nullable', 'integer', 'min:11', 'max:17'],
                'report_level3_size_px' => ['nullable', 'integer', 'min:10', 'max:16'],
                'report_leaf_size_px' => ['nullable', 'integer', 'min:10', 'max:16'],
            ]);

            $layout = $this->normalizeReportLayout(LabSetting::getValue('report_layout', []));

            LabSetting::putValue('report_layout', $this->normalizeReportLayout([
                ...$layout,
                'show_unit_column' => (bool) ($request->boolean('show_unit_column')),
                'highlight_abnormal' => (bool) ($request->boolean('highlight_abnormal', true)),
                'report_font_family' => (string) $request->input('report_font_family', $layout['report_font_family']),
                'report_lab_name_size_px' => (int) $request->input('report_lab_name_size_px', $layout['report_lab_name_size_px']),
                'report_lab_meta_size_px' => (int) $request->input('report_lab_meta_size_px', $layout['report_lab_meta_size_px']),
                'report_title_size_px' => (int) $request->input('report_title_size_px', $layout['report_title_size_px']),
                'report_patient_title_size_px' => (int) $request->input('report_patient_title_size_px', $layout['report_patient_title_size_px']),
                'report_patient_text_size_px' => (int) $request->input('report_patient_text_size_px', $layout['report_patient_text_size_px']),
                'report_table_header_size_px' => (int) $request->input('report_table_header_size_px', $layout['report_table_header_size_px']),
                'report_table_body_size_px' => (int) $request->input('report_table_body_size_px', $layout['report_table_body_size_px']),
                'report_level0_size_px' => (int) $request->input('report_level0_size_px', $layout['report_level0_size_px']),
                'report_level1_size_px' => (int) $request->input('report_level1_size_px', $layout['report_level1_size_px']),
                'report_level2_size_px' => (int) $request->input('report_level2_size_px', $layout['report_level2_size_px']),
                'report_level3_size_px' => (int) $request->input('report_level3_size_px', $layout['report_level3_size_px']),
                'report_leaf_size_px' => (int) $request->input('report_leaf_size_px', $layout['report_leaf_size_px']),
            ]));
        }

        if ($section === 'patient') {
            $patientForm = PatientFieldManager::normalizeForStorage(
                $request->input('patient_fields', []),
                $request->boolean('patient_identifier_required'),
                $request->input('patient_new', [])
            );

            LabSetting::putValue('patient_form', $patientForm);
        }

        return redirect()
            ->route('settings.edit', ['section' => $section])
            ->with('status', __('messages.settings_saved'));
    }

    private function resolveSection(string $section): string
    {
        return in_array($section, self::SECTIONS, true) ? $section : 'lab';
    }

    /**
     * @param  mixed  $rawIdentity
     * @return array<string, mixed>
     */
    private function normalizeLabIdentity(mixed $rawIdentity): array
    {
        $identity = is_array($rawIdentity) ? $rawIdentity : [];
        $defaults = LabSettingsDefaults::labIdentity();

        $legacyLogo = trim((string) ($identity['logo_path'] ?? ''));
        $leftLogo = trim((string) ($identity['logo_left_path'] ?? ''));
        $rightLogo = trim((string) ($identity['logo_right_path'] ?? ''));

        if ($leftLogo === '' && $rightLogo === '' && $legacyLogo !== '') {
            $leftLogo = $legacyLogo;
        }

        $infoPosition = (string) ($identity['header_info_position'] ?? 'center');
        if (! in_array($infoPosition, self::HEADER_INFO_POSITIONS, true)) {
            $infoPosition = (string) ($defaults['header_info_position'] ?? 'center');
        }

        $logoMode = $this->normalizeHeaderLogoMode($infoPosition, (string) ($identity['header_logo_mode'] ?? ($defaults['header_logo_mode'] ?? 'single_left')));

        $legacyLeftOffset = (int) ($identity['header_logo_offset_x_left'] ?? 0);
        $legacyRightOffset = (int) ($identity['header_logo_offset_x_right'] ?? 0);
        $leftPosition = (string) ($identity['header_logo_position_left'] ?? $this->legacyOffsetToLogoPosition($legacyLeftOffset));
        $rightPosition = (string) ($identity['header_logo_position_right'] ?? $this->legacyOffsetToLogoPosition($legacyRightOffset));

        return [
            'name' => (string) ($identity['name'] ?? $defaults['name'] ?? ''),
            'address' => (string) ($identity['address'] ?? $defaults['address'] ?? ''),
            'phone' => (string) ($identity['phone'] ?? $defaults['phone'] ?? ''),
            'email' => (string) ($identity['email'] ?? $defaults['email'] ?? ''),
            'header_note' => (string) ($identity['header_note'] ?? $defaults['header_note'] ?? ''),
            'footer_note' => (string) ($identity['footer_note'] ?? $defaults['footer_note'] ?? ''),
            'header_info_position' => $infoPosition,
            'header_logo_mode' => $logoMode,
            'header_logo_size_px' => $this->normalizeLogoSize((int) ($identity['header_logo_size_px'] ?? $defaults['header_logo_size_px'] ?? 170)),
            'header_logo_position_left' => $this->normalizeLogoPosition($leftPosition),
            'header_logo_position_right' => $this->normalizeLogoPosition($rightPosition),
            'logo_left_path' => $leftLogo !== '' ? $leftLogo : null,
            'logo_right_path' => $rightLogo !== '' ? $rightLogo : null,
        ];
    }

    /**
     * @param  mixed  $rawLayout
     * @return array<string, mixed>
     */
    private function normalizeReportLayout(mixed $rawLayout): array
    {
        return ReportLayoutSettings::normalize($rawLayout);
    }

    /**
     * @param  mixed  $rawUiAppearance
     * @return array<string, mixed>
     */
    private function normalizeUiAppearance(mixed $rawUiAppearance): array
    {
        $appearance = is_array($rawUiAppearance) ? $rawUiAppearance : [];
        $defaults = LabSettingsDefaults::uiAppearance();

        $appFontFamily = (string) ($appearance['app_font_family'] ?? $defaults['app_font_family']);
        if (! in_array($appFontFamily, self::APP_FONT_FAMILIES, true)) {
            $appFontFamily = (string) $defaults['app_font_family'];
        }

        $sizeLevel = (string) ($appearance['ui_font_size_level'] ?? $defaults['ui_font_size_level']);
        if (! in_array($sizeLevel, self::UI_FONT_SIZE_LEVELS, true)) {
            $sizeLevel = (string) $defaults['ui_font_size_level'];
        }

        $labelWeight = (string) ($appearance['label_font_weight'] ?? $defaults['label_font_weight']);
        if (! in_array($labelWeight, self::LABEL_FONT_WEIGHTS, true)) {
            $labelWeight = (string) $defaults['label_font_weight'];
        }

        $labelTextTransform = (string) ($appearance['label_text_transform'] ?? $defaults['label_text_transform']);
        if (! in_array($labelTextTransform, self::LABEL_TEXT_TRANSFORMS, true)) {
            $labelTextTransform = (string) $defaults['label_text_transform'];
        }

        $motionProfile = (string) ($appearance['motion_profile'] ?? $defaults['motion_profile']);
        if (! in_array($motionProfile, self::MOTION_PROFILES, true)) {
            $motionProfile = (string) $defaults['motion_profile'];
        }

        return [
            'app_font_family' => $appFontFamily,
            'ui_font_size_level' => $sizeLevel,
            'label_font_size_px' => $this->clampInt((int) ($appearance['label_font_size_px'] ?? $defaults['label_font_size_px']), 11, 18),
            'label_font_weight' => $labelWeight,
            'label_letter_spacing_em' => round($this->clampFloat((float) ($appearance['label_letter_spacing_em'] ?? $defaults['label_letter_spacing_em']), -0.02, 0.12), 2),
            'label_text_transform' => $labelTextTransform,
            'motion_profile' => $motionProfile,
        ];
    }

    private function normalizeHeaderLogoMode(string $infoPosition, string $requestedMode): string
    {
        if ($infoPosition === 'left') {
            return 'single_right';
        }

        if ($infoPosition === 'right') {
            return 'single_left';
        }

        return in_array($requestedMode, self::HEADER_LOGO_MODES, true) ? $requestedMode : 'single_left';
    }

    private function storeLogo(UploadedFile $file): string
    {
        return $file->store('lab-logos', 'public');
    }

    private function deleteStoredLogo(string $path): void
    {
        if ($path === '') {
            return;
        }

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    private function normalizeLogoSize(int $size): int
    {
        return max(96, min(240, $size));
    }

    private function normalizeLogoPosition(string $position): string
    {
        return in_array($position, self::HEADER_LOGO_POSITIONS, true) ? $position : 'center';
    }

    private function legacyOffsetToLogoPosition(int $offset): string
    {
        if ($offset <= -8) {
            return 'left';
        }

        if ($offset >= 8) {
            return 'right';
        }

        return 'center';
    }

    private function resetSection(string $section): void
    {
        if ($section === 'lab') {
            $identity = $this->normalizeLabIdentity(LabSetting::getValue('lab_identity', []));
            $this->removeIdentityLogos($identity);

            LabSetting::putValue('lab_identity', LabSettingsDefaults::labIdentity());
            LabSetting::putValue('ui_appearance', LabSettingsDefaults::uiAppearance());
        }

        if ($section === 'pdf') {
            LabSetting::putValue('report_layout', LabSettingsDefaults::reportLayout());
        }

        if ($section === 'patient') {
            LabSetting::putValue('patient_form', PatientFieldManager::normalizeForStorage([], false, []));
        }
    }

    /**
     * @param  array<string, mixed>  $identity
     */
    private function removeIdentityLogos(array $identity): void
    {
        $leftPath = trim((string) ($identity['logo_left_path'] ?? ''));
        $rightPath = trim((string) ($identity['logo_right_path'] ?? ''));

        $this->deleteStoredLogo($leftPath);
        if ($rightPath !== $leftPath) {
            $this->deleteStoredLogo($rightPath);
        }
    }

    private function sectionLabel(string $section): string
    {
        return match ($section) {
            'lab' => __('messages.settings_nav_lab'),
            'pdf' => __('messages.settings_nav_pdf'),
            default => __('messages.settings_nav_patient'),
        };
    }

    private function clampInt(int $value, int $min, int $max): int
    {
        return max($min, min($max, $value));
    }

    private function clampFloat(float $value, float $min, float $max): float
    {
        return max($min, min($max, $value));
    }
}
