<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Discipline;
use App\Models\LabParameter;
use App\Models\Subcategory;
use Illuminate\Database\Seeder;

class LabCatalogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $hematology = Discipline::query()->updateOrCreate(
            ['code' => 'hematology'],
            [
                'name' => 'Hematology',
                'labels' => [
                    'en' => 'Hematology',
                    'fr' => 'Hematologie',
                    'ar' => 'امراض الدم',
                ],
                'sort_order' => 10,
                'is_active' => true,
            ]
        );

        $biochemistry = Discipline::query()->updateOrCreate(
            ['code' => 'biochemistry'],
            [
                'name' => 'Biochemistry',
                'labels' => [
                    'en' => 'Biochemistry',
                    'fr' => 'Biochimie',
                    'ar' => 'الكيمياء الحيوية',
                ],
                'sort_order' => 20,
                'is_active' => true,
            ]
        );

        $nfs = Category::query()->updateOrCreate(
            ['code' => 'nfs'],
            [
                'discipline_id' => $hematology->id,
                'name' => 'Complete Blood Count',
                'labels' => [
                    'en' => 'Complete Blood Count',
                    'fr' => 'Numeration Formule Sanguine',
                    'ar' => 'تعداد الدم الكامل',
                ],
                'sort_order' => 10,
                'is_active' => true,
            ]
        );

        $glycemia = Category::query()->updateOrCreate(
            ['code' => 'glycemia'],
            [
                'discipline_id' => $biochemistry->id,
                'name' => 'Glycemia',
                'labels' => [
                    'en' => 'Glycemia',
                    'fr' => 'Glycemie',
                    'ar' => 'سكر الدم',
                ],
                'sort_order' => 20,
                'is_active' => true,
            ]
        );

        $redCell = Subcategory::query()->updateOrCreate(
            ['code' => 'rbc'],
            [
                'category_id' => $nfs->id,
                'name' => 'Red Cells',
                'labels' => [
                    'en' => 'Red Cells',
                    'fr' => 'Globules Rouges',
                    'ar' => 'خلايا الدم الحمراء',
                ],
                'sort_order' => 10,
                'is_active' => true,
            ]
        );

        $whiteCell = Subcategory::query()->updateOrCreate(
            ['code' => 'wbc'],
            [
                'category_id' => $nfs->id,
                'name' => 'White Cells',
                'labels' => [
                    'en' => 'White Cells',
                    'fr' => 'Globules Blancs',
                    'ar' => 'خلايا الدم البيضاء',
                ],
                'sort_order' => 20,
                'is_active' => true,
            ]
        );

        $parameterRows = [
            [
                'code' => 'hgb',
                'discipline_id' => $hematology->id,
                'category_id' => $nfs->id,
                'subcategory_id' => $redCell->id,
                'name' => 'Hemoglobin',
                'labels' => ['en' => 'Hemoglobin', 'fr' => 'Hemoglobine', 'ar' => 'الهيموغلوبين'],
                'unit' => 'g/dL',
                'value_type' => 'number',
                'normal_min' => 12,
                'normal_max' => 16,
                'sort_order' => 10,
            ],
            [
                'code' => 'hct',
                'discipline_id' => $hematology->id,
                'category_id' => $nfs->id,
                'subcategory_id' => $redCell->id,
                'name' => 'Hematocrit',
                'labels' => ['en' => 'Hematocrit', 'fr' => 'Hematocrite', 'ar' => 'الهيماتوكريت'],
                'unit' => '%',
                'value_type' => 'number',
                'normal_min' => 37,
                'normal_max' => 47,
                'sort_order' => 20,
            ],
            [
                'code' => 'wbc-count',
                'discipline_id' => $hematology->id,
                'category_id' => $nfs->id,
                'subcategory_id' => $whiteCell->id,
                'name' => 'Leukocytes',
                'labels' => ['en' => 'Leukocytes', 'fr' => 'Leucocytes', 'ar' => 'الكريات البيضاء'],
                'unit' => '/mm3',
                'value_type' => 'number',
                'normal_min' => 4000,
                'normal_max' => 10000,
                'sort_order' => 30,
            ],
            [
                'code' => 'glu-fasting',
                'discipline_id' => $biochemistry->id,
                'category_id' => $glycemia->id,
                'subcategory_id' => null,
                'name' => 'Fasting Glucose',
                'labels' => ['en' => 'Fasting Glucose', 'fr' => 'Glycemie a jeun', 'ar' => 'سكر الدم صائم'],
                'unit' => 'mg/dL',
                'value_type' => 'number',
                'normal_min' => 70,
                'normal_max' => 99,
                'sort_order' => 10,
            ],
        ];

        foreach ($parameterRows as $row) {
            LabParameter::query()->updateOrCreate(
                ['code' => $row['code']],
                array_merge($row, [
                    'labels' => $row['labels'],
                    'normal_text' => null,
                    'options' => null,
                    'abnormal_style' => [
                        'font_weight' => '700',
                        'text_color' => '#b91c1c',
                    ],
                    'is_visible' => true,
                    'is_active' => true,
                ])
            );
        }
    }
}
