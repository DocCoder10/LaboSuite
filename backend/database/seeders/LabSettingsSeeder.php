<?php

namespace Database\Seeders;

use App\Models\LabSetting;
use App\Support\LabSettingsDefaults;
use Illuminate\Database\Seeder;

class LabSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultIdentity = LabSettingsDefaults::labIdentity();

        $existingIdentity = LabSetting::getValue('lab_identity', []);
        if (! is_array($existingIdentity)) {
            $existingIdentity = [];
        }

        LabSetting::putValue('lab_identity', [
            ...$defaultIdentity,
            ...$existingIdentity,
        ]);

        $defaultReportLayout = LabSettingsDefaults::reportLayout();

        $existingLayout = LabSetting::getValue('report_layout', []);
        if (! is_array($existingLayout)) {
            $existingLayout = [];
        }

        LabSetting::putValue('report_layout', [
            ...$defaultReportLayout,
            ...$existingLayout,
        ]);

        $defaultUiAppearance = LabSettingsDefaults::uiAppearance();
        $existingUiAppearance = LabSetting::getValue('ui_appearance', []);
        if (! is_array($existingUiAppearance)) {
            $existingUiAppearance = [];
        }

        LabSetting::putValue('ui_appearance', [
            ...$defaultUiAppearance,
            ...$existingUiAppearance,
        ]);
    }
}
