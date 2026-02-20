<?php

namespace App\Http\Controllers;

use App\Models\LabSetting;
use App\Support\PatientFieldManager;
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
    private const HEADER_LOGO_OFFSET_PRESETS = [-16, -8, 0, 8, 16];

    public function edit(Request $request): View
    {
        $activeSection = $this->resolveSection((string) $request->query('section', 'lab'));
        $patientForm = PatientFieldManager::resolved(LabSetting::getValue('patient_form', []));
        $identity = $this->normalizeLabIdentity(LabSetting::getValue('lab_identity', []));

        return view('settings.edit', [
            'identity' => $identity,
            'layout' => LabSetting::getValue('report_layout', []),
            'patientForm' => $patientForm,
            'activeSection' => $activeSection,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $section = $this->resolveSection((string) $request->input('section', 'lab'));

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
                'header_logo_offset_x_left' => ['nullable', Rule::in(self::HEADER_LOGO_OFFSET_PRESETS)],
                'header_logo_offset_x_right' => ['nullable', Rule::in(self::HEADER_LOGO_OFFSET_PRESETS)],
                'logo_left' => ['nullable', 'file', 'mimetypes:image/jpeg,image/png,image/gif,image/bmp,image/webp,image/svg+xml,image/avif,image/tiff,image/x-icon,image/vnd.microsoft.icon', 'max:8192'],
                'logo_right' => ['nullable', 'file', 'mimetypes:image/jpeg,image/png,image/gif,image/bmp,image/webp,image/svg+xml,image/avif,image/tiff,image/x-icon,image/vnd.microsoft.icon', 'max:8192'],
                'remove_logo_left' => ['nullable', 'boolean'],
                'remove_logo_right' => ['nullable', 'boolean'],
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
            $offsetLeft = $this->normalizeLogoOffset((int) ($data['header_logo_offset_x_left'] ?? 0));
            $offsetRight = $this->normalizeLogoOffset((int) ($data['header_logo_offset_x_right'] ?? 0));

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
                'header_logo_offset_x_left' => $offsetLeft,
                'header_logo_offset_x_right' => $offsetRight,
                'logo_left_path' => $leftPath !== '' ? $leftPath : null,
                'logo_right_path' => $rightPath !== '' ? $rightPath : null,
            ]);
        }

        if ($section === 'pdf') {
            $request->validate([
                'show_unit_column' => ['nullable', 'boolean'],
                'highlight_abnormal' => ['nullable', 'boolean'],
            ]);

            $layout = LabSetting::getValue('report_layout', []);
            if (! is_array($layout)) {
                $layout = [];
            }

            LabSetting::putValue('report_layout', [
                ...$layout,
                'show_unit_column' => (bool) ($request->boolean('show_unit_column')),
                'highlight_abnormal' => (bool) ($request->boolean('highlight_abnormal', true)),
                'discipline_title_size' => 'text-xl',
                'category_title_size' => 'text-base',
            ]);
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

        $legacyLogo = trim((string) ($identity['logo_path'] ?? ''));
        $leftLogo = trim((string) ($identity['logo_left_path'] ?? ''));
        $rightLogo = trim((string) ($identity['logo_right_path'] ?? ''));

        if ($leftLogo === '' && $rightLogo === '' && $legacyLogo !== '') {
            $leftLogo = $legacyLogo;
        }

        $infoPosition = (string) ($identity['header_info_position'] ?? 'center');
        if (! in_array($infoPosition, self::HEADER_INFO_POSITIONS, true)) {
            $infoPosition = 'center';
        }

        $logoMode = $this->normalizeHeaderLogoMode($infoPosition, (string) ($identity['header_logo_mode'] ?? 'single_left'));

        return [
            'name' => (string) ($identity['name'] ?? ''),
            'address' => (string) ($identity['address'] ?? ''),
            'phone' => (string) ($identity['phone'] ?? ''),
            'email' => (string) ($identity['email'] ?? ''),
            'header_note' => (string) ($identity['header_note'] ?? ''),
            'footer_note' => (string) ($identity['footer_note'] ?? ''),
            'header_info_position' => $infoPosition,
            'header_logo_mode' => $logoMode,
            'header_logo_size_px' => $this->normalizeLogoSize((int) ($identity['header_logo_size_px'] ?? 170)),
            'header_logo_offset_x_left' => $this->normalizeLogoOffset((int) ($identity['header_logo_offset_x_left'] ?? 0)),
            'header_logo_offset_x_right' => $this->normalizeLogoOffset((int) ($identity['header_logo_offset_x_right'] ?? 0)),
            'logo_left_path' => $leftLogo !== '' ? $leftLogo : null,
            'logo_right_path' => $rightLogo !== '' ? $rightLogo : null,
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

    private function normalizeLogoOffset(int $offset): int
    {
        return in_array($offset, self::HEADER_LOGO_OFFSET_PRESETS, true) ? $offset : 0;
    }
}
