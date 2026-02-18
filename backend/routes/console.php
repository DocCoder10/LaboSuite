<?php

use App\Models\Category;
use App\Models\Discipline;
use App\Models\LabParameter;
use App\Models\Subcategory;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('lms:import-legacy-analyses {path : Absolute or relative path to legacy analyses.json} {--wipe : Delete existing catalog before import}', function () {
    $pathInput = (string) $this->argument('path');
    $path = $pathInput;

    if (! str_starts_with($path, DIRECTORY_SEPARATOR)) {
        $path = base_path($path);
    }

    if (! is_file($path)) {
        $this->error("Legacy file not found: {$path}");

        return 1;
    }

    $raw = file_get_contents($path);

    if ($raw === false) {
        $this->error("Unable to read file: {$path}");

        return 1;
    }

    $data = json_decode($raw, true);

    if (! is_array($data)) {
        $this->error('Invalid JSON format: expected root array.');

        return 1;
    }

    $counters = [
        'disciplines' => 0,
        'categories' => 0,
        'parameters' => 0,
        'ignored' => 0,
    ];

    DB::transaction(function () use ($data, &$counters) {
        if ((bool) $this->option('wipe')) {
            // Keep deterministic recreation order and avoid dangling references.
            LabParameter::query()->delete();
            Subcategory::query()->delete();
            Category::query()->delete();
            Discipline::query()->delete();
        }

        $disciplineCache = [];
        $categoryCache = [];
        $disciplineOrder = 0;
        $categoryOrder = 0;
        $parameterOrder = 0;

        foreach ($data as $index => $analysis) {
            if (! is_array($analysis)) {
                $counters['ignored']++;

                continue;
            }

            $name = trim((string) ($analysis['nom'] ?? ''));

            if ($name === '') {
                $counters['ignored']++;

                continue;
            }

            $disciplineName = trim((string) ($analysis['categorie'] ?? 'Autres'));
            if ($disciplineName === '') {
                $disciplineName = 'Autres';
            }

            $disciplineKey = Str::lower($disciplineName);

            if (! isset($disciplineCache[$disciplineKey])) {
                $disciplineOrder++;

                $discipline = Discipline::query()->create([
                    'code' => lms_unique_code('discipline', $disciplineName, fn (string $code) => Discipline::query()->where('code', $code)->exists()),
                    'name' => $disciplineName,
                    'labels' => [
                        'fr' => $disciplineName,
                    ],
                    'sort_order' => $disciplineOrder * 10,
                    'is_active' => true,
                ]);

                $disciplineCache[$disciplineKey] = $discipline;
                $counters['disciplines']++;
            } else {
                $discipline = $disciplineCache[$disciplineKey];
            }

            $categoryCacheKey = $discipline->id.'|'.Str::lower($name);

            if (! isset($categoryCache[$categoryCacheKey])) {
                $categoryOrder++;

                $category = Category::query()->create([
                    'discipline_id' => $discipline->id,
                    'code' => lms_unique_code('category', $name, fn (string $code) => Category::query()->where('code', $code)->exists()),
                    'name' => $name,
                    'labels' => [
                        'fr' => $name,
                    ],
                    'sort_order' => $categoryOrder * 10,
                    'is_active' => true,
                ]);

                $categoryCache[$categoryCacheKey] = $category;
                $counters['categories']++;
            } else {
                $category = $categoryCache[$categoryCacheKey];
            }

            $children = $analysis['sous_analyses'] ?? null;

            if (is_array($children) && $children !== []) {
                foreach ($children as $child) {
                    if (! is_array($child)) {
                        $counters['ignored']++;

                        continue;
                    }

                    $parameterName = trim((string) ($child['nom'] ?? ''));
                    if ($parameterName === '') {
                        $counters['ignored']++;

                        continue;
                    }

                    $parameterOrder++;

                    lms_create_parameter_from_legacy(
                        discipline: $discipline,
                        category: $category,
                        source: $child,
                        fallbackName: $parameterName,
                        sortOrder: $parameterOrder * 10,
                        codeExists: fn (string $code) => LabParameter::query()->where('code', $code)->exists()
                    );

                    $counters['parameters']++;
                }

                continue;
            }

            $parameterOrder++;

            lms_create_parameter_from_legacy(
                discipline: $discipline,
                category: $category,
                source: $analysis,
                fallbackName: $name,
                sortOrder: $parameterOrder * 10,
                codeExists: fn (string $code) => LabParameter::query()->where('code', $code)->exists()
            );

            $counters['parameters']++;
        }
    });

    $this->info('Legacy analyses imported successfully.');
    $this->line('Disciplines created: '.$counters['disciplines']);
    $this->line('Categories created: '.$counters['categories']);
    $this->line('Parameters created: '.$counters['parameters']);
    $this->line('Rows ignored: '.$counters['ignored']);

    return 0;
})->purpose('Import legacy Python analyses.json into LMS catalog');

/**
 * @param callable(string):bool $exists
 */
if (! function_exists('lms_unique_code')) {
    /**
     * @param callable(string):bool $exists
     */
    function lms_unique_code(string $prefix, string $label, callable $exists): string
    {
        $base = Str::slug(Str::lower($label), '-');
        if ($base === '') {
            $base = 'item';
        }

        $candidate = "{$prefix}-{$base}";
        $suffix = 1;

        while ($exists($candidate)) {
            $suffix++;
            $candidate = "{$prefix}-{$base}-{$suffix}";
        }

        return $candidate;
    }
}

if (! function_exists('lms_create_parameter_from_legacy')) {
    /**
     * @param callable(string):bool $codeExists
     */
    function lms_create_parameter_from_legacy(Discipline $discipline, Category $category, array $source, string $fallbackName, int $sortOrder, callable $codeExists): void
    {
        $name = trim((string) ($source['nom'] ?? ''));
        if ($name === '') {
            $name = $fallbackName;
        }

        $unit = trim((string) ($source['unite'] ?? ''));
        if ($unit === '') {
            $unit = null;
        }

        $reference = lms_normalize_reference($source['valeurs_ref'] ?? null);
        $valueType = lms_resolve_value_type($source);

        $normalMin = null;
        $normalMax = null;
        $normalText = null;

        if ($valueType === 'number') {
            [$parsedMin, $parsedMax] = lms_parse_numeric_range($reference);

            if ($parsedMin !== null || $parsedMax !== null) {
                $normalMin = $parsedMin;
                $normalMax = $parsedMax;
            } elseif ($reference !== null) {
                $normalText = Str::limit($reference, 255, '');
            }
        } elseif ($reference !== null) {
            $normalText = Str::limit($reference, 255, '');
        }

        $options = null;
        if (isset($source['options']) && is_array($source['options'])) {
            $options = collect($source['options'])
                ->map(fn (mixed $value) => trim((string) $value))
                ->filter()
                ->unique()
                ->values()
                ->all();

            if ($options === []) {
                $options = null;
            }
        }

        LabParameter::query()->create([
            'discipline_id' => $discipline->id,
            'category_id' => $category->id,
            'subcategory_id' => null,
            'code' => lms_unique_code('param', $discipline->name.'-'.$category->name.'-'.$name, $codeExists),
            'name' => $name,
            'labels' => [
                'fr' => $name,
            ],
            'unit' => $unit,
            'value_type' => $valueType,
            'normal_min' => $normalMin,
            'normal_max' => $normalMax,
            'normal_text' => $normalText,
            'options' => $options,
            'abnormal_style' => [
                'font_weight' => '700',
                'text_color' => '#b91c1c',
            ],
            'sort_order' => $sortOrder,
            'is_visible' => true,
            'is_active' => true,
        ]);
    }
}

if (! function_exists('lms_normalize_reference')) {
    function lms_normalize_reference(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $normalized = preg_replace('/\s+/u', ' ', trim($value));

        if (! is_string($normalized) || $normalized === '' || $normalized === '-' || $normalized === '--') {
            return null;
        }

        return $normalized;
    }
}

if (! function_exists('lms_resolve_value_type')) {
    function lms_resolve_value_type(array $source): string
    {
        if (isset($source['options']) && is_array($source['options']) && $source['options'] !== []) {
            return 'list';
        }

        $type = Str::lower((string) ($source['type'] ?? ''));

        if (in_array($type, ['texte', 'text'], true)) {
            return 'text';
        }

        return 'number';
    }
}

if (! function_exists('lms_parse_numeric_range')) {
    /**
     * @return array{0: float|null, 1: float|null}
     */
    function lms_parse_numeric_range(?string $reference): array
    {
        if ($reference === null) {
            return [null, null];
        }

        $candidate = str_replace([',', '–', '—'], ['.', '-', '-'], $reference);

        if (preg_match('/^\s*(-?\d+(?:\.\d+)?)\s*-\s*(-?\d+(?:\.\d+)?)\s*$/', $candidate, $matches) !== 1) {
            return [null, null];
        }

        return [(float) $matches[1], (float) $matches[2]];
    }
}
