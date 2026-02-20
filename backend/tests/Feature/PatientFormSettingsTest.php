<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Discipline;
use App\Models\LabAnalysis;
use App\Models\LabSetting;
use App\Models\Patient;
use App\Support\PatientFieldManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
