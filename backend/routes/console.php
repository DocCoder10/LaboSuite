<?php

use App\Models\Category;
use App\Models\Discipline;
use App\Models\LabSetting;
use App\Models\LabParameter;
use App\Models\Subcategory;
use App\Support\LabSettingsDefaults;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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

Artisan::command('lms:apply-installer-profile {path : Absolute or relative path to installer profile ini}', function () {
    $pathInput = trim((string) $this->argument('path'));
    if ($pathInput === '') {
        $this->error('Installer profile path is required.');

        return 1;
    }

    $isAbsolute = str_starts_with($pathInput, DIRECTORY_SEPARATOR)
        || preg_match('/^[A-Za-z]:[\/\\\\]/', $pathInput) === 1;

    $path = $isAbsolute ? $pathInput : base_path($pathInput);

    if (! is_file($path)) {
        $this->error("Installer profile not found: {$path}");

        return 1;
    }

    $profileHash = sha1_file($path) ?: '';
    $bootstrapMeta = LabSetting::getValue('installer_bootstrap', []);
    if (
        is_array($bootstrapMeta)
        && $profileHash !== ''
        && (string) ($bootstrapMeta['profile_hash'] ?? '') === $profileHash
    ) {
        $this->info('Installer profile already applied for this database.');

        return 0;
    }

    $profile = parse_ini_file($path, true, INI_SCANNER_RAW);
    if (! is_array($profile)) {
        $this->error('Invalid installer profile format.');

        return 1;
    }

    $identityInput = $profile['identity'] ?? [];
    if (! is_array($identityInput)) {
        $identityInput = [];
    }

    $defaults = LabSettingsDefaults::labIdentity();
    $existingIdentity = LabSetting::getValue('lab_identity', []);
    if (! is_array($existingIdentity)) {
        $existingIdentity = [];
    }

    $baseIdentity = [
        ...$defaults,
        ...$existingIdentity,
    ];

    $normalize = static fn (mixed $value): string => trim((string) $value);

    $name = $normalize($identityInput['name'] ?? '');
    $address = $normalize($identityInput['address'] ?? '');
    $headerServices = $normalize($identityInput['header_services'] ?? '');
    $phone = $normalize($identityInput['phone'] ?? '');
    $email = $normalize($identityInput['email'] ?? '');

    if ($name === '' || $address === '') {
        $this->error('Installer profile missing required name/address values.');

        return 1;
    }

    if ($headerServices === '') {
        $headerServices = $normalize($defaults['header_note'] ?? 'Analyses medicales - Medecine Generale - Medecine specialisee');
    }

    if ($phone === '') {
        $phone = '+223-00-00-00-00';
    }

    $logoPathInput = $normalize($identityInput['logo_path'] ?? '');
    $logoStoredPath = $normalize($baseIdentity['logo_left_path'] ?? '');
    $resolvedLogoSize = (int) ($baseIdentity['header_logo_size_px'] ?? 140);

    if ($logoPathInput !== '' && is_file($logoPathInput)) {
        $extension = strtolower(pathinfo($logoPathInput, PATHINFO_EXTENSION));
        $allowedExtensions = ['png', 'jpg', 'jpeg', 'gif', 'bmp', 'webp', 'svg', 'avif', 'tiff', 'ico'];
        if (! in_array($extension, $allowedExtensions, true)) {
            $extension = 'png';
        }

        $content = file_get_contents($logoPathInput);
        if ($content !== false) {
            $target = 'lab-logos/setup-'.substr(sha1($logoPathInput.'|'.$profileHash), 0, 20).'.'.$extension;
            Storage::disk('public')->put($target, $content);
            $logoStoredPath = $target;
        }

        $dimensions = lms_resolve_logo_dimensions($logoPathInput);
        if (is_array($dimensions) && isset($dimensions[0], $dimensions[1])) {
            $width = max(1, (int) $dimensions[0]);
            $height = max(1, (int) $dimensions[1]);
            $ratio = $width / $height;
            $resolvedLogoSize = lms_resolve_logo_size_from_ratio($ratio);
        }
    }

    $resolvedLogoSize = max(96, min(240, $resolvedLogoSize));

    $identity = [
        ...$baseIdentity,
        'name' => $name,
        'address' => $address,
        'phone' => $phone,
        'email' => $email,
        'header_note' => $headerServices,
        'header_info_position' => in_array((string) ($baseIdentity['header_info_position'] ?? 'center'), ['left', 'center', 'right'], true)
            ? (string) $baseIdentity['header_info_position']
            : 'center',
        'header_logo_mode' => in_array((string) ($baseIdentity['header_logo_mode'] ?? 'single_left'), ['single_left', 'single_right', 'both_distinct', 'both_same'], true)
            ? (string) $baseIdentity['header_logo_mode']
            : 'single_left',
        'header_logo_size_px' => $resolvedLogoSize,
        'header_logo_position_left' => in_array((string) ($baseIdentity['header_logo_position_left'] ?? 'center'), ['left', 'center', 'right'], true)
            ? (string) $baseIdentity['header_logo_position_left']
            : 'center',
        'header_logo_position_right' => in_array((string) ($baseIdentity['header_logo_position_right'] ?? 'center'), ['left', 'center', 'right'], true)
            ? (string) $baseIdentity['header_logo_position_right']
            : 'center',
        'header_vertical_align' => in_array((string) ($baseIdentity['header_vertical_align'] ?? 'center'), ['top', 'center'], true)
            ? (string) $baseIdentity['header_vertical_align']
            : 'center',
        'header_info_line_height' => round(max(1.05, min(1.80, (float) ($baseIdentity['header_info_line_height'] ?? 1.30))), 2),
        'header_info_row_gap_rem' => round(max(0, min(0.80, (float) ($baseIdentity['header_info_row_gap_rem'] ?? 0.16))), 2),
        'header_name_text_transform' => in_array((string) ($baseIdentity['header_name_text_transform'] ?? 'capitalize'), ['none', 'capitalize', 'uppercase', 'lowercase'], true)
            ? (string) $baseIdentity['header_name_text_transform']
            : 'capitalize',
        'header_meta_text_transform' => in_array((string) ($baseIdentity['header_meta_text_transform'] ?? 'capitalize'), ['none', 'capitalize', 'uppercase', 'lowercase'], true)
            ? (string) $baseIdentity['header_meta_text_transform']
            : 'capitalize',
        'logo_left_path' => $logoStoredPath !== '' ? $logoStoredPath : null,
        'logo_right_path' => null,
    ];

    LabSetting::putValue('lab_identity', $identity);
    LabSetting::putValue('installer_bootstrap', [
        'profile_hash' => $profileHash,
        'developer' => 'DocCoder10',
        'applied_at' => now()->toIso8601String(),
    ]);

    $this->info('Installer profile applied successfully.');
    $this->line('Developer: DocCoder10');

    return 0;
})->purpose('Apply setup wizard data captured by the Windows installer');

if (! function_exists('lms_resolve_logo_dimensions')) {
    /**
     * @return array{0: int, 1: int}|null
     */
    function lms_resolve_logo_dimensions(string $path): ?array
    {
        $dimensions = @getimagesize($path);
        if (is_array($dimensions) && isset($dimensions[0], $dimensions[1])) {
            return [
                max(1, (int) $dimensions[0]),
                max(1, (int) $dimensions[1]),
            ];
        }

        if (strtolower(pathinfo($path, PATHINFO_EXTENSION)) !== 'svg') {
            return null;
        }

        $content = file_get_contents($path);
        if (! is_string($content) || $content === '') {
            return null;
        }

        if (preg_match('/viewBox\s*=\s*["\']\s*[-+]?\d*\.?\d+(?:[eE][-+]?\d+)?\s+[-+]?\d*\.?\d+(?:[eE][-+]?\d+)?\s+([-+]?\d*\.?\d+(?:[eE][-+]?\d+)?)\s+([-+]?\d*\.?\d+(?:[eE][-+]?\d+)?)\s*["\']/i', $content, $matches) === 1) {
            $width = (int) round((float) $matches[1]);
            $height = (int) round((float) $matches[2]);

            if ($width > 0 && $height > 0) {
                return [$width, $height];
            }
        }

        $extractSvgDimension = static function (string $attribute) use ($content): ?float {
            $pattern = '/(?:^|[\s<])'.preg_quote($attribute, '/').'\s*=\s*["\']\s*([-+]?\d*\.?\d+)(?:px)?\s*["\']/i';
            if (preg_match($pattern, $content, $matches) !== 1) {
                return null;
            }

            return (float) $matches[1];
        };

        $width = $extractSvgDimension('width');
        $height = $extractSvgDimension('height');

        if ($width === null || $height === null) {
            return null;
        }

        $resolvedWidth = (int) round($width);
        $resolvedHeight = (int) round($height);

        if ($resolvedWidth <= 0 || $resolvedHeight <= 0) {
            return null;
        }

        return [$resolvedWidth, $resolvedHeight];
    }
}

if (! function_exists('lms_resolve_logo_size_from_ratio')) {
    function lms_resolve_logo_size_from_ratio(float $ratio): int
    {
        $safeRatio = max(0.70, min(4.20, $ratio));

        if ($safeRatio >= 3.10) {
            return 182;
        }

        if ($safeRatio >= 2.60) {
            return 174;
        }

        if ($safeRatio >= 2.10) {
            return 164;
        }

        if ($safeRatio >= 1.60) {
            return 154;
        }

        if ($safeRatio >= 1.20) {
            return 146;
        }

        return 136;
    }
}

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
