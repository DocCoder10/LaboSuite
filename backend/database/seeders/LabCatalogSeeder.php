<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Discipline;
use App\Models\LabParameter;
use App\Models\Subcategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LabCatalogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            // Reset default catalog content before inserting the new baseline.
            LabParameter::query()->delete();
            Subcategory::query()->delete();
            Category::query()->delete();
            Discipline::query()->delete();

            $disciplineByName = [];
            $disciplineSortOrder = 10;
            $categorySortByDiscipline = [];

            $disciplineCodes = [];
            $categoryCodes = [];
            $subcategoryCodes = [];
            $parameterCodes = [];

            foreach ($this->catalogDefinition() as $entry) {
                $disciplineName = $this->asText($entry['categorie'] ?? null);
                $categoryName = $this->asText($entry['nom'] ?? null);

                if ($disciplineName === null || $categoryName === null) {
                    continue;
                }

                if (! isset($disciplineByName[$disciplineName])) {
                    $disciplineCode = $this->uniqueCode($disciplineCodes, 'disc', $disciplineName);

                    $disciplineByName[$disciplineName] = Discipline::query()->create([
                        'code' => $disciplineCode,
                        'name' => $disciplineName,
                        'labels' => $this->labels($disciplineName),
                        'sort_order' => $disciplineSortOrder,
                        'is_active' => true,
                    ]);

                    $categorySortByDiscipline[(int) $disciplineByName[$disciplineName]->id] = 10;
                    $disciplineSortOrder += 10;
                }

                $discipline = $disciplineByName[$disciplineName];
                $disciplineId = (int) $discipline->id;

                $categoryCode = $this->uniqueCode($categoryCodes, 'cat', $disciplineName.'-'.$categoryName);
                $categorySortOrder = $categorySortByDiscipline[$disciplineId] ?? 10;

                $category = Category::query()->create([
                    'discipline_id' => $disciplineId,
                    'code' => $categoryCode,
                    'name' => $categoryName,
                    'labels' => $this->labels($categoryName),
                    'sort_order' => $categorySortOrder,
                    'is_active' => true,
                ]);

                $categorySortByDiscipline[$disciplineId] = $categorySortOrder + 10;

                $tests = $this->resolveTests($entry);
                $useSubcategories = count($tests) > 1;
                $subcategorySortOrder = 10;
                $parameterSortOrder = 10;

                foreach ($tests as $test) {
                    $testName = $this->asText($test['nom'] ?? null);
                    if ($testName === null) {
                        continue;
                    }

                    $subcategory = null;

                    if ($useSubcategories) {
                        $subcategoryCode = $this->uniqueCode($subcategoryCodes, 'sub', $disciplineName.'-'.$categoryName.'-'.$testName);

                        $subcategory = Subcategory::query()->create([
                            'category_id' => (int) $category->id,
                            'parent_subcategory_id' => null,
                            'depth' => 1,
                            'code' => $subcategoryCode,
                            'name' => $testName,
                            'labels' => $this->labels($testName),
                            'sort_order' => $subcategorySortOrder,
                            'is_active' => true,
                        ]);

                        $subcategorySortOrder += 10;
                    }

                    $valueType = $this->resolveValueType($test);
                    $options = $this->resolveOptions($test, $valueType);
                    $defaultValue = $this->resolveDefaultValue($test, $valueType, $options);
                    [$normalMin, $normalMax, $normalText] = $this->resolveReferenceFields($valueType, $test['valeurs_ref'] ?? null);

                    $parameterCode = $this->uniqueCode($parameterCodes, 'param', $disciplineName.'-'.$categoryName.'-'.$testName);

                    LabParameter::query()->create([
                        'discipline_id' => $disciplineId,
                        'category_id' => (int) $category->id,
                        'subcategory_id' => $subcategory?->id,
                        'code' => $parameterCode,
                        'name' => $testName,
                        'labels' => $this->labels($testName),
                        'unit' => $this->asText($test['unite'] ?? null),
                        'value_type' => $valueType,
                        'normal_min' => $normalMin,
                        'normal_max' => $normalMax,
                        'normal_text' => $normalText,
                        'options' => $options,
                        'default_value' => $defaultValue,
                        'abnormal_style' => [
                            'font_weight' => '700',
                            'text_color' => '#b91c1c',
                        ],
                        'sort_order' => $parameterSortOrder,
                        'is_visible' => true,
                        'is_active' => true,
                    ]);

                    $parameterSortOrder += 10;
                }
            }
        });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function catalogDefinition(): array
    {
        return [
            [
                'nom' => 'Goutte Epaisse',
                'unite' => 'Trophozoites/ul de sang',
                'valeurs_ref' => '00.00',
                'categorie' => 'Parasitologie',
                'type' => 'numerique',
            ],
            [
                'nom' => 'Taux de Hb',
                'unite' => 'g/dL',
                'valeurs_ref' => "Homme : 13.0 - 17.0\nFemme : 12.0 - 15.0\nEnfant/Ne : 14.0 - 20.0",
                'categorie' => 'Hematologie',
                'type' => 'numerique',
            ],
            [
                'nom' => 'Groupage/Rhesus',
                'unite' => '  ',
                'valeurs_ref' => ' ',
                'categorie' => 'Hematologie',
                'options' => ['A+', 'B+', 'AB+', 'O+', 'A-', 'B-', 'AB-', 'O-'],
            ],
            [
                'nom' => 'AgHBS',
                'unite' => '',
                'valeurs_ref' => ' ',
                'categorie' => 'Serologie',
                'options' => ['NEGATIF', 'POSITIF'],
            ],
            [
                'nom' => 'NFS',
                'categorie' => 'Hematologie',
                'sous_analyses' => [
                    ['nom' => 'Leu', 'unite' => '10^3/mm3', 'valeurs_ref' => "Homme : 4.6 - 6.2\nFemme :  4.2 - 5.4", 'type' => 'numerique'],
                    ['nom' => 'GR', 'unite' => '10^6/mm3', 'valeurs_ref' => "Homme : 4.6 - 6.2\nFemme :  4.2 - 5.4", 'type' => 'numerique'],
                    ['nom' => 'HB', 'unite' => 'g/dL', 'valeurs_ref' => "Homme : 13.0 - 18.0\nFemme : 12.0 - 16.0\nEnfant/Ne : 12.0 - 14.5", 'type' => 'numerique'],
                    ['nom' => 'HT', 'unite' => '%', 'valeurs_ref' => "Homme : 40 - 55\nFemme : 37 - 47", 'type' => 'numerique'],
                    ['nom' => 'VGM', 'unite' => 'fl', 'valeurs_ref' => '80 - 100', 'type' => 'numerique'],
                    ['nom' => 'TCMH', 'unite' => 'pg', 'valeurs_ref' => '27 - 31', 'type' => 'numerique'],
                    ['nom' => 'CCMH', 'unite' => 'g/dl', 'valeurs_ref' => '32 - 36', 'type' => 'numerique'],
                    ['nom' => 'Neutrophiles', 'unite' => '%', 'valeurs_ref' => '4.0 - 10.0', 'type' => 'numerique'],
                    ['nom' => 'Lymphocytes', 'unite' => '%', 'valeurs_ref' => '10 - 40', 'type' => 'numerique'],
                    ['nom' => 'Monocytes', 'unite' => '%', 'valeurs_ref' => '1.0 - 10.0', 'type' => 'numerique'],
                    ['nom' => 'Eosinophiles', 'unite' => '%', 'valeurs_ref' => '0,1 - 5.0', 'type' => 'numerique'],
                    ['nom' => 'Basophiles', 'unite' => '%', 'valeurs_ref' => '0 - 0,5', 'type' => 'numerique', 'default' => '0'],
                    ['nom' => 'Plaquettes', 'unite' => '10^3/mm3', 'valeurs_ref' => '150 - 450', 'type' => 'numerique'],
                ],
            ],
            [
                'nom' => 'TOXO',
                'categorie' => 'Serologie',
                'sous_analyses' => [
                    ['nom' => 'IGG', 'unite' => '10^3/mm3', 'valeurs_ref' => ' ', 'options' => ['NEGATIF', 'POSITIF']],
                    ['nom' => 'IGM', 'unite' => '10^6/mm3', 'valeurs_ref' => ' ', 'options' => ['NEGATIF', 'POSITIF']],
                ],
            ],
            [
                'nom' => 'Glycemie a jeun',
                'unite' => 'mg/dL',
                'valeurs_ref' => '70 - 110',
                'categorie' => 'Biochimie',
                'type' => 'numerique',
            ],
            [
                'nom' => 'Glycemie Aleatoire',
                'unite' => 'mg/dL',
                'valeurs_ref' => '90 - 150',
                'categorie' => 'Biochimie',
                'type' => 'numerique',
            ],
            [
                'nom' => 'Creatininemie',
                'unite' => 'umol/L',
                'valeurs_ref' => '60 - 130',
                'categorie' => 'Biochimie',
                'type' => 'numerique',
            ],
            [
                'nom' => 'Bilan hepatique',
                'categorie' => 'Biochimie',
                'sous_analyses' => [
                    ['nom' => 'ASAT', 'unite' => 'UI/L', 'valeurs_ref' => '10-40', 'type' => 'numerique'],
                    ['nom' => 'ALAT', 'unite' => 'UI/L', 'valeurs_ref' => '10-50', 'type' => 'numerique'],
                ],
            ],
            [
                'nom' => 'BW',
                'unite' => '--',
                'valeurs_ref' => 'N/O',
                'categorie' => 'Serologie',
                'options' => ['Positive', 'Negative'],
                'sous_analyses' => [
                    ['nom' => 'TO', 'unite' => '', 'valeurs_ref' => '', 'options' => ['Negatif', 'Positif']],
                    ['nom' => 'TH', 'unite' => '', 'valeurs_ref' => '', 'options' => ['Negatif', 'Positif']],
                ],
            ],
            [
                'nom' => 'CRP',
                'unite' => ' ',
                'valeurs_ref' => ' ',
                'categorie' => 'Serologie',
                'options' => ['Positive', 'Negative'],
            ],
            [
                'nom' => 'SRV',
                'unite' => ' ',
                'valeurs_ref' => ' ',
                'categorie' => 'Serologie',
                'options' => ['Positive', 'Negative'],
            ],
            [
                'nom' => 'ECBU',
                'categorie' => 'Bacteriologie',
                'sous_analyses' => [
                    ['nom' => 'Aspect', 'unite' => '', 'valeurs_ref' => '', 'options' => ['Clair', 'Trouble', 'Fonce']],
                    ['nom' => 'Couleur', 'unite' => '', 'valeurs_ref' => '', 'options' => ['Jaune pale', 'Jaune fonce', 'Rosee', 'Brunatre']],
                    ['nom' => 'Culot urinaire', 'unite' => '', 'valeurs_ref' => ''],
                    ['nom' => 'Leucocytes', 'unite' => '/mm3', 'valeurs_ref' => '< 10 000', 'type' => 'numerique'],
                    ['nom' => 'Hematies', 'unite' => '/mm3', 'valeurs_ref' => '< 5 000', 'type' => 'numerique'],
                    ['nom' => 'Cellules epitheliales', 'unite' => '', 'valeurs_ref' => 'Rare'],
                    ['nom' => 'Cristaux', 'unite' => '', 'valeurs_ref' => 'Absents', 'default' => 'Absents'],
                    ['nom' => 'Cylindres', 'unite' => '', 'valeurs_ref' => 'Absents', 'default' => 'Absents'],
                    ['nom' => 'Bacteries', 'unite' => '', 'valeurs_ref' => 'Absentes', 'default' => 'Absentes'],
                    ['nom' => 'Parasites', 'unite' => '', 'valeurs_ref' => 'Absents', 'default' => 'Absents'],
                    ['nom' => 'Levures', 'unite' => '', 'valeurs_ref' => 'Absentes', 'default' => 'Absentes'],
                    [
                        'nom' => 'Culture',
                        'unite' => '',
                        'valeurs_ref' => '',
                        'options' => ['Sterile', 'Escherichia coli', 'Klebsiella spp.', 'Enterococcus spp.', 'Pseudomonas aeruginosa', 'Staphylococcus aureus'],
                    ],
                    ['nom' => 'Nbre de germes isoles', 'unite' => 'UFC/mL', 'valeurs_ref' => '< 10^4 UFC/mL (non significatif)', 'type' => 'numerique'],
                ],
            ],
            [
                'nom' => 'Coloration de Gram',
                'categorie' => 'Bacteriologie',
                'sous_analyses' => [
                    ['nom' => 'Resultat', 'unite' => '', 'valeurs_ref' => '', 'options' => ['Bacilles Gram negatif', 'Cocci Gram positif', 'Aucun germe observe']],
                    ['nom' => 'Interpretation', 'unite' => '', 'valeurs_ref' => ''],
                ],
            ],
            [
                'nom' => 'Antibiogramme',
                'categorie' => 'Bacteriologie',
                'sous_analyses' => [
                    ['nom' => 'Antibiotiques testes', 'unite' => '', 'valeurs_ref' => '', 'options' => ['Amoxicilline', 'Ciprofloxacine', 'Ceftriaxone', 'Nitrofurantoine', 'Gentamicine', 'Imipeneme']],
                    ['nom' => 'Resistance / Sensibilite', 'unite' => '', 'valeurs_ref' => '', 'options' => ['Sensible', 'Resistant', 'Intermediaire']],
                    ['nom' => 'Commentaires ATB', 'unite' => '', 'valeurs_ref' => ''],
                ],
            ],
            [
                'nom' => 'studytest',
                'unite' => '',
                'valeurs_ref' => '',
                'categorie' => 'Serologie',
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $entry
     * @return array<int, array<string, mixed>>
     */
    private function resolveTests(array $entry): array
    {
        $children = is_array($entry['sous_analyses'] ?? null) ? $entry['sous_analyses'] : [];

        if ($children === []) {
            return [$this->normalizeTest($entry)];
        }

        $tests = [];

        if ($this->hasTopLevelTest($entry)) {
            $tests[] = $this->normalizeTest($entry);
        }

        foreach ($children as $child) {
            if (! is_array($child)) {
                continue;
            }

            $tests[] = $this->normalizeTest($child);
        }

        return $tests;
    }

    /**
     * @param  array<string, mixed>  $entry
     */
    private function hasTopLevelTest(array $entry): bool
    {
        if (is_array($entry['options'] ?? null) && $entry['options'] !== []) {
            return true;
        }

        if ($this->asText($entry['type'] ?? null) !== null) {
            return true;
        }

        if ($this->asText($entry['unite'] ?? null) !== null) {
            return true;
        }

        if ($this->asText($entry['valeurs_ref'] ?? null) !== null) {
            return true;
        }

        return $this->asText($entry['default'] ?? null) !== null;
    }

    /**
     * @param  array<string, mixed>  $raw
     * @return array<string, mixed>
     */
    private function normalizeTest(array $raw): array
    {
        return [
            'nom' => $raw['nom'] ?? null,
            'unite' => $raw['unite'] ?? null,
            'valeurs_ref' => $raw['valeurs_ref'] ?? null,
            'type' => $raw['type'] ?? null,
            'options' => $raw['options'] ?? null,
            'default' => $raw['default'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $test
     */
    private function resolveValueType(array $test): string
    {
        $options = is_array($test['options'] ?? null)
            ? array_values(array_filter(array_map(fn (mixed $value) => $this->asText($value), $test['options']), fn (?string $value) => $value !== null))
            : [];

        if ($options !== []) {
            return 'list';
        }

        $type = Str::lower((string) ($test['type'] ?? ''));

        if (in_array($type, ['numerique', 'numérique', 'number'], true)) {
            return 'number';
        }

        return 'text';
    }

    /**
     * @param  array<string, mixed>  $test
     * @return array<int, string>|null
     */
    private function resolveOptions(array $test, string $valueType): ?array
    {
        if ($valueType !== 'list') {
            return null;
        }

        $options = is_array($test['options'] ?? null)
            ? array_values(array_filter(array_map(fn (mixed $value) => $this->asText($value), $test['options']), fn (?string $value) => $value !== null))
            : [];

        return $options !== [] ? $options : null;
    }

    /**
     * @param  array<string, mixed>  $test
     * @param  array<int, string>|null  $options
     */
    private function resolveDefaultValue(array $test, string $valueType, ?array $options): ?string
    {
        $defaultValue = $this->asText($test['default'] ?? null);

        if ($defaultValue === null) {
            return null;
        }

        if ($valueType === 'list' && is_array($options) && ! in_array($defaultValue, $options, true)) {
            return null;
        }

        return $defaultValue;
    }

    /**
     * @return array{0:float|null,1:float|null,2:string|null}
     */
    private function resolveReferenceFields(string $valueType, mixed $rawReference): array
    {
        $reference = $this->asText($rawReference);

        if ($reference === null) {
            return [null, null, null];
        }

        if ($valueType === 'number') {
            $normalized = str_replace(['−', '–', '—'], '-', $reference);

            if (preg_match('/^\s*(-?\d+(?:[\.,]\d+)?)\s*-\s*(-?\d+(?:[\.,]\d+)?)\s*$/u', $normalized, $matches) === 1) {
                $min = (float) str_replace(',', '.', $matches[1]);
                $max = (float) str_replace(',', '.', $matches[2]);

                return [$min, $max, null];
            }
        }

        return [null, null, $reference];
    }

    /**
     * @param  array<int, string>  $usedCodes
     */
    private function uniqueCode(array &$usedCodes, string $prefix, string $seed): string
    {
        $base = Str::slug($seed, '-');
        $base = $base !== '' ? $base : 'item';

        $candidate = $prefix.'-'.$base;
        $suffix = 1;

        while (in_array($candidate, $usedCodes, true)) {
            $suffix += 1;
            $candidate = $prefix.'-'.$base.'-'.$suffix;
        }

        $usedCodes[] = $candidate;

        return $candidate;
    }

    /**
     * @return array{fr:string,en:string,ar:string}
     */
    private function labels(string $name): array
    {
        return [
            'fr' => $name,
            'en' => $name,
            'ar' => $name,
        ];
    }

    private function asText(mixed $value): ?string
    {
        $text = trim((string) ($value ?? ''));

        return $text === '' ? null : $text;
    }
}
