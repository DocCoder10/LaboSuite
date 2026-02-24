<?php

namespace Tests\Feature;

use App\Models\Discipline;
use App\Models\LabSetting;
use Database\Seeders\LabCatalogSeeder;
use Database\Seeders\LabSettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeedPersistenceSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_lab_settings_seeder_preserves_existing_user_values(): void
    {
        LabSetting::putValue('lab_identity', [
            'name' => 'Mon Labo Perso',
            'address' => 'Adresse custom',
            'phone' => '70000001',
            'email' => 'owner@lab.local',
            'header_note' => 'Note custom',
            'footer_note' => 'Footer custom',
            'header_info_position' => 'right',
            'header_logo_mode' => 'single_left',
            'header_logo_size_px' => 190,
            'header_logo_position_left' => 'right',
            'header_logo_position_right' => 'center',
            'logo_left_path' => 'lab-logos/custom-left.png',
            'logo_right_path' => null,
        ]);

        app(LabSettingsSeeder::class)->run();

        $identity = LabSetting::getValue('lab_identity', []);

        $this->assertSame('Mon Labo Perso', $identity['name'] ?? null);
        $this->assertSame('lab-logos/custom-left.png', $identity['logo_left_path'] ?? null);
        $this->assertSame(190, $identity['header_logo_size_px'] ?? null);
    }

    public function test_lab_catalog_seeder_skips_reseed_when_catalog_exists(): void
    {
        Discipline::query()->create([
            'code' => 'disc-custom',
            'name' => 'Discipline Custom',
            'labels' => ['fr' => 'Discipline Custom'],
            'sort_order' => 1,
            'is_active' => true,
        ]);

        app(LabCatalogSeeder::class)->run();

        $this->assertSame(1, Discipline::query()->count());
        $this->assertSame('disc-custom', Discipline::query()->first()?->code);
    }

    public function test_lab_catalog_seeder_populates_empty_catalog(): void
    {
        $this->assertSame(0, Discipline::query()->count());

        app(LabCatalogSeeder::class)->run();

        $this->assertGreaterThan(0, Discipline::query()->count());
    }
}
