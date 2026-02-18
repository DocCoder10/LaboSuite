<?php

namespace App\Http\Controllers;

use App\Models\LabSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function edit(): View
    {
        return view('settings.edit', [
            'identity' => LabSetting::getValue('lab_identity', []),
            'layout' => LabSetting::getValue('report_layout', []),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:120'],
            'header_note' => ['nullable', 'string', 'max:255'],
            'footer_note' => ['nullable', 'string', 'max:255'],
            'show_unit_column' => ['nullable', 'boolean'],
            'highlight_abnormal' => ['nullable', 'boolean'],
        ]);

        LabSetting::putValue('lab_identity', [
            'name' => $data['name'],
            'address' => $data['address'] ?? '',
            'phone' => $data['phone'] ?? '',
            'email' => $data['email'] ?? '',
            'header_note' => $data['header_note'] ?? '',
            'footer_note' => $data['footer_note'] ?? '',
        ]);

        LabSetting::putValue('report_layout', [
            'show_unit_column' => (bool) ($request->boolean('show_unit_column')),
            'highlight_abnormal' => (bool) ($request->boolean('highlight_abnormal', true)),
            'discipline_title_size' => 'text-xl',
            'category_title_size' => 'text-base',
        ]);

        return back()->with('status', __('messages.settings_saved'));
    }
}
