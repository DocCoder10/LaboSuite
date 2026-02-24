<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Discipline;
use App\Models\LabAnalysis;
use App\Models\LabSetting;
use App\Models\Patient;
use App\Support\LabSettingsDefaults;
use App\Support\PatientFieldManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PatientFormSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_form_displays_patient_fields_in_expected_order(): void
    {
        $response = $this->get(route('analyses.create'));

        $response->assertOk();

        $html = $response->getContent();

        $this->assertTrue(
            strpos($html, 'Prenom') < strpos($html, 'Nom')
            && strpos($html, 'Nom') < strpos($html, 'Age')
            && strpos($html, 'Age') < strpos($html, 'Sexe')
            && strpos($html, 'Sexe') < strpos($html, 'Telephone')
            && strpos($html, 'Telephone') < strpos($html, 'Identifiant')
        );
    }

    public function test_identifier_can_be_optional_in_selection_step(): void
    {
        $category = $this->createCategory();

        $response = $this->post(route('analyses.selection.store'), [
            'patient' => [
                'first_name' => 'Ali',
                'last_name' => 'Diallo',
                'age' => 32,
                'sex' => 'male',
                'phone' => '70000000',
                'identifier' => '',
            ],
            'analysis_date' => now()->toDateString(),
            'selected_categories' => [$category->id],
        ]);

        $response->assertRedirect(route('analyses.results'));

        $draft = session('analysis_draft');

        $this->assertIsArray($draft);
        $this->assertNull($draft['patient']['identifier'] ?? null);
    }

    public function test_identifier_is_auto_generated_when_required_setting_is_enabled(): void
    {
        $category = $this->createCategory();

        LabSetting::putValue('patient_form', [
            'identifier_required' => true,
            'fields' => [
                ['key' => 'sex', 'label' => 'Sexe', 'type' => 'text', 'active' => true],
                ['key' => 'phone', 'label' => 'Telephone', 'type' => 'text', 'active' => true],
                ['key' => 'identifier', 'label' => 'Identifiant', 'type' => 'text', 'active' => true],
            ],
        ]);

        $response = $this->post(route('analyses.selection.store'), [
            'patient' => [
                'first_name' => 'Amadou',
                'last_name' => 'Doumbia',
                'age' => 27,
                'sex' => 'male',
                'phone' => '70000111',
                'identifier' => '',
            ],
            'analysis_date' => now()->toDateString(),
            'selected_categories' => [$category->id],
        ]);

        $response->assertRedirect(route('analyses.results'));

        $draft = session('analysis_draft');
        $generatedIdentifier = $draft['patient']['identifier'] ?? '';

        $this->assertMatchesRegularExpression('/^[A-Z]{2}\d{3}[A-Z]*$/', (string) $generatedIdentifier);
    }

    public function test_settings_can_add_edit_and_delete_custom_patient_field(): void
    {
        $createResponse = $this->put(route('settings.update'), [
            'section' => 'patient',
            'patient_identifier_required' => '0',
            'patient_fields' => [
                ['key' => 'sex', 'label' => 'Sexe', 'type' => 'text', 'active' => '1'],
                ['key' => 'phone', 'label' => 'Telephone', 'type' => 'text', 'active' => '1'],
                ['key' => 'identifier', 'label' => 'ID Patient', 'type' => 'text', 'active' => '1'],
            ],
            'patient_new' => [
                'label' => 'Poids',
                'type' => 'number',
                'active' => '1',
            ],
        ]);

        $createResponse->assertSessionHasNoErrors();

        $createdConfig = PatientFieldManager::resolved(LabSetting::getValue('patient_form', []));
        $customField = collect($createdConfig['fields'])->first(fn (array $field) => ($field['key'] ?? '') === 'custom_poids');

        $this->assertNotNull($customField);
        $this->assertSame('number', $customField['type']);

        $updateResponse = $this->put(route('settings.update'), [
            'section' => 'patient',
            'patient_identifier_required' => '0',
            'patient_fields' => [
                ['key' => 'sex', 'label' => 'Sexe', 'type' => 'text', 'active' => '1'],
                ['key' => 'phone', 'label' => 'Telephone', 'type' => 'text', 'active' => '1'],
                ['key' => 'identifier', 'label' => 'ID Patient', 'type' => 'text', 'active' => '1'],
                ['key' => 'custom_poids', 'label' => 'Poids (kg)', 'type' => 'number', 'active' => '0'],
            ],
        ]);

        $updateResponse->assertSessionHasNoErrors();

        $updatedConfig = PatientFieldManager::resolved(LabSetting::getValue('patient_form', []));
        $updatedField = collect($updatedConfig['fields'])->first(fn (array $field) => ($field['key'] ?? '') === 'custom_poids');

        $this->assertNotNull($updatedField);
        $this->assertSame('Poids (kg)', $updatedField['label']);
        $this->assertFalse((bool) $updatedField['active']);

        $deleteResponse = $this->put(route('settings.update'), [
            'section' => 'patient',
            'patient_identifier_required' => '0',
            'patient_fields' => [
                ['key' => 'sex', 'label' => 'Sexe', 'type' => 'text', 'active' => '1'],
                ['key' => 'phone', 'label' => 'Telephone', 'type' => 'text', 'active' => '1'],
                ['key' => 'identifier', 'label' => 'ID Patient', 'type' => 'text', 'active' => '1'],
                ['key' => 'custom_poids', 'label' => 'Poids (kg)', 'type' => 'number', 'active' => '0', 'delete' => '1'],
            ],
        ]);

        $deleteResponse->assertSessionHasNoErrors();

        $deletedConfig = PatientFieldManager::resolved(LabSetting::getValue('patient_form', []));
        $deletedField = collect($deletedConfig['fields'])->first(fn (array $field) => ($field['key'] ?? '') === 'custom_poids');

        $this->assertNull($deletedField);
    }

    public function test_locked_fields_remain_required_active_and_not_configurable(): void
    {
        $response = $this->put(route('settings.update'), [
            'section' => 'patient',
            'patient_identifier_required' => '0',
            'patient_fields' => [
                ['key' => 'first_name', 'label' => 'Hack', 'type' => 'text', 'active' => '0', 'delete' => '1'],
                ['key' => 'sex', 'label' => 'Sexe', 'type' => 'text', 'active' => '1'],
                ['key' => 'phone', 'label' => 'Telephone', 'type' => 'text', 'active' => '1'],
                ['key' => 'identifier', 'label' => 'Identifiant', 'type' => 'text', 'active' => '1'],
            ],
        ]);

        $response->assertSessionHasNoErrors();

        $resolved = PatientFieldManager::resolved(LabSetting::getValue('patient_form', []));

        $firstNameField = collect($resolved['fields'])->first(fn (array $field) => $field['key'] === 'first_name');
        $lastNameField = collect($resolved['fields'])->first(fn (array $field) => $field['key'] === 'last_name');
        $ageField = collect($resolved['fields'])->first(fn (array $field) => $field['key'] === 'age');

        $this->assertTrue((bool) ($firstNameField['locked'] ?? false));
        $this->assertTrue((bool) ($firstNameField['required'] ?? false));
        $this->assertTrue((bool) ($firstNameField['active'] ?? false));

        $this->assertTrue((bool) ($lastNameField['locked'] ?? false));
        $this->assertTrue((bool) ($lastNameField['required'] ?? false));
        $this->assertTrue((bool) ($lastNameField['active'] ?? false));

        $this->assertTrue((bool) ($ageField['locked'] ?? false));
        $this->assertTrue((bool) ($ageField['required'] ?? false));
        $this->assertTrue((bool) ($ageField['active'] ?? false));
    }

    public function test_print_view_patient_info_order_is_consistent(): void
    {
        $patient = Patient::query()->create([
            'identifier' => 'AD005',
            'first_name' => 'Amadou',
            'last_name' => 'Diallo',
            'sex' => 'male',
            'age' => 29,
            'phone' => '70000444',
            'extra_fields' => ['public_identifier' => 'AD005'],
        ]);

        $analysis = LabAnalysis::query()->create([
            'analysis_number' => 'LMS-20260220-00001',
            'patient_id' => $patient->id,
            'analysis_date' => now()->toDateString(),
            'status' => 'final',
            'notes' => null,
        ]);

        $response = $this->get(route('analyses.print', $analysis));

        $response->assertOk();

        $html = $response->getContent();

        $this->assertTrue(
            strpos($html, 'Patient') < strpos($html, 'Age')
            && strpos($html, 'Age') < strpos($html, 'Sexe')
            && strpos($html, 'Sexe') < strpos($html, 'Telephone')
            && strpos($html, 'Telephone') < strpos($html, 'ID')
        );
    }

    public function test_lab_settings_can_store_header_layout_and_two_logos(): void
    {
        Storage::fake('public');

        $response = $this->post(route('settings.update'), [
            '_method' => 'PUT',
            'section' => 'lab',
            'name' => 'Laboratoire Central',
            'address' => 'Rue 10',
            'phone' => '70001111',
            'email' => 'lab@test.local',
            'header_note' => 'Note header',
            'footer_note' => 'Note footer',
            'header_info_position' => 'center',
            'header_logo_mode' => 'both_distinct',
            'header_logo_size_px' => 146,
            'header_logo_offset_x_left' => -8,
            'header_logo_offset_x_right' => 8,
            'logo_left' => UploadedFile::fake()->image('left.png', 320, 120),
            'logo_right' => UploadedFile::fake()->image('right.png', 320, 120),
        ]);

        $response->assertSessionHasNoErrors();

        $identity = LabSetting::getValue('lab_identity', []);

        $this->assertSame('center', $identity['header_info_position'] ?? null);
        $this->assertSame('both_distinct', $identity['header_logo_mode'] ?? null);
        $this->assertSame(146, $identity['header_logo_size_px'] ?? null);
        $this->assertSame(-8, $identity['header_logo_offset_x_left'] ?? null);
        $this->assertSame(8, $identity['header_logo_offset_x_right'] ?? null);
        $this->assertNotEmpty($identity['logo_left_path'] ?? null);
        $this->assertNotEmpty($identity['logo_right_path'] ?? null);
        Storage::disk('public')->assertExists((string) ($identity['logo_left_path'] ?? ''));
        Storage::disk('public')->assertExists((string) ($identity['logo_right_path'] ?? ''));
    }

    public function test_lab_settings_force_single_logo_mode_when_info_block_is_not_center(): void
    {
        $response = $this->put(route('settings.update'), [
            'section' => 'lab',
            'name' => 'Laboratoire Central',
            'address' => 'Rue 10',
            'phone' => '70001111',
            'email' => 'lab@test.local',
            'header_note' => 'Note header',
            'footer_note' => 'Note footer',
            'header_info_position' => 'right',
            'header_logo_mode' => 'both_distinct',
        ]);

        $response->assertSessionHasNoErrors();

        $identity = LabSetting::getValue('lab_identity', []);

        $this->assertSame('right', $identity['header_info_position'] ?? null);
        $this->assertSame('single_left', $identity['header_logo_mode'] ?? null);
    }

    public function test_lab_settings_are_rendered_from_persisted_storage_on_settings_page(): void
    {
        Storage::fake('public');

        $this->post(route('settings.update'), [
            '_method' => 'PUT',
            'section' => 'lab',
            'name' => 'Lab Persist',
            'address' => 'Rue Persist',
            'phone' => '70002222',
            'email' => 'persist@test.local',
            'header_note' => 'Persist header',
            'footer_note' => 'Persist footer',
            'header_info_position' => 'center',
            'header_logo_mode' => 'single_left',
            'header_logo_size_px' => 180,
            'header_logo_offset_x_left' => 16,
            'header_logo_offset_x_right' => 0,
            'logo_left' => UploadedFile::fake()->image('persist-left.png', 320, 120),
        ])->assertSessionHasNoErrors();

        $identity = LabSetting::getValue('lab_identity', []);
        $leftPath = (string) ($identity['logo_left_path'] ?? '');

        $this->assertNotSame('', $leftPath);
        Storage::disk('public')->assertExists($leftPath);

        $response = $this->get(route('settings.edit', ['section' => 'lab']));
        $response->assertOk();
        $response->assertSee('Lab Persist');
        $response->assertSee(Storage::disk('public')->url($leftPath), false);
        $response->assertSee('value="180"', false);
        $response->assertSee('value="16"', false);
    }

    public function test_lab_settings_can_store_typography_controls(): void
    {
        $response = $this->put(route('settings.update'), [
            'section' => 'lab',
            'name' => 'Laboratoire Typo',
            'address' => 'Rue 10',
            'phone' => '70009999',
            'email' => 'typo@test.local',
            'header_note' => 'Header',
            'footer_note' => 'Footer',
            'header_info_position' => 'center',
            'header_logo_mode' => 'single_left',
            'app_font_family' => 'robotic',
            'ui_font_size_level' => 'comfortable',
            'label_font_size_px' => 17,
            'label_font_weight' => '700',
            'label_letter_spacing_em' => 0.08,
            'label_text_transform' => 'uppercase',
            'motion_profile' => 'fluid',
        ]);

        $response->assertSessionHasNoErrors();

        $uiAppearance = LabSetting::getValue('ui_appearance', []);

        $this->assertSame('robotic', $uiAppearance['app_font_family'] ?? null);
        $this->assertSame('comfortable', $uiAppearance['ui_font_size_level'] ?? null);
        $this->assertSame(17, $uiAppearance['label_font_size_px'] ?? null);
        $this->assertSame('700', (string) ($uiAppearance['label_font_weight'] ?? ''));
        $this->assertSame(0.08, (float) ($uiAppearance['label_letter_spacing_em'] ?? 0));
        $this->assertSame('uppercase', $uiAppearance['label_text_transform'] ?? null);
        $this->assertSame('fluid', $uiAppearance['motion_profile'] ?? null);
    }

    public function test_each_settings_section_can_be_reset_independently(): void
    {
        LabSetting::putValue('lab_identity', [
            ...LabSettingsDefaults::labIdentity(),
            'name' => 'Custom Lab Name',
        ]);

        LabSetting::putValue('ui_appearance', [
            ...LabSettingsDefaults::uiAppearance(),
            'app_font_family' => 'robotic',
            'motion_profile' => 'fluid',
        ]);

        LabSetting::putValue('report_layout', [
            ...LabSettingsDefaults::reportLayout(),
            'report_font_family' => 'robotic',
            'report_title_size_px' => 30,
        ]);

        LabSetting::putValue('patient_form', [
            'identifier_required' => true,
            'fields' => [
                ['key' => 'sex', 'label' => 'Sexe', 'type' => 'text', 'active' => false],
            ],
        ]);

        $this->put(route('settings.update'), [
            'section' => 'lab',
            'action' => 'reset',
        ])->assertSessionHasNoErrors();

        $resetIdentity = LabSetting::getValue('lab_identity', []);
        $resetUi = LabSetting::getValue('ui_appearance', []);

        $this->assertSame(LabSettingsDefaults::labIdentity()['name'], $resetIdentity['name'] ?? null);
        $this->assertSame(LabSettingsDefaults::uiAppearance()['app_font_family'], $resetUi['app_font_family'] ?? null);

        $this->put(route('settings.update'), [
            'section' => 'pdf',
            'action' => 'reset',
        ])->assertSessionHasNoErrors();

        $resetLayout = LabSetting::getValue('report_layout', []);
        $this->assertSame(LabSettingsDefaults::reportLayout()['report_font_family'], $resetLayout['report_font_family'] ?? null);
        $this->assertSame(LabSettingsDefaults::reportLayout()['report_title_size_px'], $resetLayout['report_title_size_px'] ?? null);

        $this->put(route('settings.update'), [
            'section' => 'patient',
            'action' => 'reset',
        ])->assertSessionHasNoErrors();

        $resetPatientForm = PatientFieldManager::resolved(LabSetting::getValue('patient_form', []));
        $this->assertFalse((bool) ($resetPatientForm['identifier_required'] ?? true));

        $sexField = collect($resetPatientForm['fields'])->first(fn (array $field) => ($field['key'] ?? '') === 'sex');
        $this->assertTrue((bool) ($sexField['active'] ?? false));
    }

    private function createCategory(): Category
    {
        $discipline = Discipline::query()->create([
            'code' => 'disc-patient',
            'name' => 'Discipline Patient',
            'labels' => ['fr' => 'Discipline Patient'],
            'sort_order' => 1,
            'is_active' => true,
        ]);

        return Category::query()->create([
            'discipline_id' => $discipline->id,
            'code' => 'cat-patient',
            'name' => 'Categorie Patient',
            'labels' => ['fr' => 'Categorie Patient'],
            'sort_order' => 1,
            'is_active' => true,
        ]);
    }

}
