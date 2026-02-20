<?php

namespace App\Http\Controllers;

use App\Models\LabSetting;
use App\Support\PatientFieldManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    /**
     * @var array<int, string>
     */
    private const SECTIONS = ['lab', 'pdf', 'patient'];

    public function edit(Request $request): View
    {
        $activeSection = $this->resolveSection((string) $request->query('section', 'lab'));
        $patientForm = PatientFieldManager::resolved(LabSetting::getValue('patient_form', []));

        return view('settings.edit', [
            'identity' => LabSetting::getValue('lab_identity', []),
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
            ]);

            LabSetting::putValue('lab_identity', [
                'name' => $data['name'],
                'address' => $data['address'] ?? '',
                'phone' => $data['phone'] ?? '',
                'email' => $data['email'] ?? '',
                'header_note' => $data['header_note'] ?? '',
                'footer_note' => $data['footer_note'] ?? '',
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
}
