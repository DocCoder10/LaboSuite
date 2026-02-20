<?php

namespace App\Http\Controllers;

use App\Models\AnalysisResult;
use App\Models\Category;
use App\Models\Discipline;
use App\Models\LabAnalysis;
use App\Models\LabParameter;
use App\Models\LabSetting;
use App\Models\Patient;
use App\Models\Subcategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AnalysisController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->string('search'));
        $period = (string) $request->string('period', 'all');
        $sort = (string) $request->string('sort', 'date');
        $direction = strtolower((string) $request->string('direction', 'desc')) === 'asc' ? 'asc' : 'desc';
        $perPage = (int) $request->integer('per_page', 15);

        $allowedPeriods = ['all', 'today', '7_days', '30_days'];
        $allowedPerPage = [15, 20];

        if (! in_array($period, $allowedPeriods, true)) {
            $period = 'all';
        }

        if (! in_array($perPage, $allowedPerPage, true)) {
            $perPage = 15;
        }

        if ($sort !== 'date') {
            $sort = 'date';
        }

        $analysesQuery = LabAnalysis::query()
            ->with('patient');

        if ($search !== '') {
            $searchLike = '%'.$search.'%';

            $analysesQuery->where(function ($query) use ($searchLike) {
                $query
                    ->where('analysis_number', 'like', $searchLike)
                    ->orWhereHas('patient', function ($patientQuery) use ($searchLike) {
                        $patientQuery
                            ->where('identifier', 'like', $searchLike)
                            ->orWhere('first_name', 'like', $searchLike)
                            ->orWhere('last_name', 'like', $searchLike)
                            ->orWhereRaw("CONCAT(last_name, ' ', first_name) LIKE ?", [$searchLike])
                            ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", [$searchLike]);
                    });
            });
        }

        if ($period === 'today') {
            $analysesQuery->whereDate('updated_at', Carbon::today());
        }

        if ($period === '7_days') {
            $analysesQuery->where('updated_at', '>=', Carbon::today()->subDays(6)->startOfDay());
        }

        if ($period === '30_days') {
            $analysesQuery->where('updated_at', '>=', Carbon::today()->subDays(29)->startOfDay());
        }

        $sortExpression = 'CASE WHEN updated_at > created_at THEN updated_at ELSE analysis_date END';

        $analyses = $analysesQuery
            ->orderByRaw($sortExpression.' '.$direction)
            ->orderBy('id', $direction)
            ->paginate($perPage)
            ->withQueryString();

        return view('analyses.index', [
            'search' => $search,
            'period' => $period,
            'sort' => $sort,
            'direction' => $direction,
            'perPage' => $perPage,
            'listQuery' => $this->extractListQuery($request, $search, $period, $sort, $direction, $perPage),
            'analyses' => $analyses,
        ]);
    }

    public function create(Request $request): View
    {
        $draft = $request->session()->get('analysis_draft', []);

        return view('analyses.create', [
            'analysisDate' => $draft['analysis_date'] ?? now()->toDateString(),
            'disciplines' => $this->loadActiveDisciplines(),
            'draft' => $draft,
        ]);
    }

    public function storeSelection(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->selectionRules());

        $validated['selected_categories'] = collect($validated['selected_categories'])
            ->map(fn (mixed $value) => (int) $value)
            ->unique()
            ->values()
            ->all();

        $request->session()->put('analysis_draft', [
            'patient' => [
                'identifier' => $validated['patient']['identifier'],
                'first_name' => $validated['patient']['first_name'],
                'last_name' => $validated['patient']['last_name'],
                'sex' => $validated['patient']['sex'],
                'age' => $validated['patient']['age'] ?? null,
                'phone' => $validated['patient']['phone'] ?? null,
            ],
            'analysis_date' => $validated['analysis_date'],
            'selected_categories' => $validated['selected_categories'],
        ]);

        return redirect()->route('analyses.results');
    }

    public function results(Request $request): RedirectResponse|View
    {
        $draft = $request->session()->get('analysis_draft');

        if (! is_array($draft) || empty($draft['selected_categories'])) {
            return redirect()
                ->route('analyses.create')
                ->withErrors(['selected_categories' => __('messages.selection_required')]);
        }

        $entryData = $this->buildEntryViewData($draft);

        if (
            empty($entryData['groups'])
            || $entryData['resolvedCategoryCount'] !== $entryData['requestedCategoryCount']
        ) {
            $request->session()->forget('analysis_draft');

            return redirect()
                ->route('analyses.create')
                ->withErrors(['selected_categories' => __('messages.selection_outdated')]);
        }

        return view('analyses.results', [
            'draft' => $draft,
            ...$entryData,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $draft = $request->session()->get('analysis_draft');

        if (is_array($draft) && ! empty($draft['selected_categories'])) {
            $validatedResults = $request->validate([
                'results' => ['required', 'array'],
                'notes' => ['nullable', 'string'],
            ]);

            $validated = [
                'patient' => $draft['patient'],
                'analysis_date' => $draft['analysis_date'],
                'selected_categories' => collect($draft['selected_categories'])
                    ->map(fn (mixed $value) => (int) $value)
                    ->unique()
                    ->values()
                    ->all(),
                'results' => $validatedResults['results'] ?? [],
                'notes' => $validatedResults['notes'] ?? null,
            ];
        } else {
            $validated = $request->validate([
                ...$this->selectionRules(),
                'results' => ['required', 'array'],
                'notes' => ['nullable', 'string'],
            ]);

            $validated['selected_categories'] = collect($validated['selected_categories'])
                ->map(fn (mixed $value) => (int) $value)
                ->unique()
                ->values()
                ->all();
        }

        $resolvedCategoryIds = Category::query()
            ->whereIn('id', $validated['selected_categories'])
            ->pluck('id')
            ->map(fn (int $value) => (int) $value)
            ->values()
            ->all();

        if (count($resolvedCategoryIds) !== count($validated['selected_categories'])) {
            $request->session()->forget('analysis_draft');

            return redirect()
                ->route('analyses.create')
                ->withErrors(['selected_categories' => __('messages.selection_outdated')]);
        }

        $validated['selected_categories'] = $resolvedCategoryIds;

        $parameters = LabParameter::query()
            ->whereIn('category_id', $validated['selected_categories'])
            ->where('is_active', true)
            ->where('is_visible', true)
            ->orderBy('sort_order')
            ->get();

        $results = $validated['results'] ?? [];

        $missingParameter = $parameters->first(function (LabParameter $parameter) use ($results) {
            $rawValue = trim((string) ($results[$parameter->id] ?? ''));

            return $rawValue === '';
        });

        if ($missingParameter) {
            return back()
                ->withErrors([
                    'results' => __('messages.result_required_parameter', [
                        'parameter' => $missingParameter->label(app()->getLocale()),
                    ]),
                ])
                ->withInput();
        }

        $invalidListParameter = $parameters->first(function (LabParameter $parameter) use ($results) {
            if ($parameter->value_type !== 'list') {
                return false;
            }

            $options = is_array($parameter->options) ? $parameter->options : [];

            if ($options === []) {
                return false;
            }

            $rawValue = trim((string) ($results[$parameter->id] ?? ''));

            return ! in_array($rawValue, $options, true);
        });

        if ($invalidListParameter) {
            return back()
                ->withErrors([
                    'results' => __('messages.result_invalid_option', [
                        'parameter' => $invalidListParameter->label(app()->getLocale()),
                    ]),
                ])
                ->withInput();
        }

        $analysis = DB::transaction(function () use ($validated, $parameters, $results) {
            $patient = Patient::query()->updateOrCreate(
                ['identifier' => $validated['patient']['identifier']],
                [
                    'first_name' => $validated['patient']['first_name'],
                    'last_name' => $validated['patient']['last_name'],
                    'sex' => $validated['patient']['sex'],
                    'age' => $validated['patient']['age'] ?? null,
                    'phone' => $validated['patient']['phone'] ?? null,
                ]
            );

            $analysis = LabAnalysis::query()->create([
                'analysis_number' => 'PENDING',
                'patient_id' => $patient->id,
                'analysis_date' => $validated['analysis_date'],
                'status' => 'final',
                'notes' => $validated['notes'] ?? null,
            ]);

            $analysis->analysis_number = sprintf(
                'LMS-%s-%05d',
                Carbon::parse($validated['analysis_date'])->format('Ymd'),
                $analysis->id
            );
            $analysis->save();

            $analysis->categories()->sync($validated['selected_categories']);

            foreach ($parameters as $parameter) {
                $rawValue = trim((string) ($results[$parameter->id] ?? ''));

                $numericValue = $parameter->value_type === 'number'
                    ? $this->parseNumericValue($rawValue)
                    : null;

                AnalysisResult::query()->create([
                    'analysis_id' => $analysis->id,
                    'lab_parameter_id' => $parameter->id,
                    'result_value' => $rawValue,
                    'result_numeric' => $numericValue,
                    'is_abnormal' => $this->isAbnormal($parameter, $rawValue),
                ]);
            }

            return $analysis;
        });

        $request->session()->forget('analysis_draft');

        return redirect()
            ->route('analyses.show', $analysis)
            ->with('status', __('messages.analysis_saved'));
    }

    public function show(LabAnalysis $analysis): View
    {
        $analysis->load([
            'patient',
            'results.parameter.discipline',
            'results.parameter.category',
            'results.parameter.subcategory',
            'results.parameter.subcategory.parent',
            'categories.discipline',
        ]);

        return view('analyses.show', [
            ...$this->buildReportViewData($analysis),
            'listQuery' => $this->extractListQuery(request()),
        ]);
    }

    public function print(LabAnalysis $analysis): View
    {
        $analysis->load([
            'patient',
            'results.parameter.discipline',
            'results.parameter.category',
            'results.parameter.subcategory',
            'results.parameter.subcategory.parent',
            'categories.discipline',
        ]);

        return view('analyses.print', $this->buildReportViewData($analysis));
    }

    public function edit(Request $request, LabAnalysis $analysis): View
    {
        $analysis->load([
            'patient',
            'results.parameter.discipline',
            'results.parameter.category',
            'results.parameter.subcategory',
        ]);

        return view('analyses.edit', [
            ...$this->buildEditViewData($analysis),
            'listQuery' => $this->extractListQuery($request),
        ]);
    }

    public function update(Request $request, LabAnalysis $analysis): RedirectResponse
    {
        $analysis->load([
            'results.parameter',
        ]);

        $validated = $request->validate([
            'results' => ['required', 'array'],
            'notes' => ['nullable', 'string'],
        ]);

        $results = $validated['results'] ?? [];
        $resultModels = $analysis->results->keyBy('lab_parameter_id');

        $parameters = $analysis->results
            ->pluck('parameter')
            ->filter(fn (mixed $parameter) => $parameter instanceof LabParameter)
            ->keyBy('id');

        $missingParameter = $parameters->first(function (LabParameter $parameter) use ($results) {
            $rawValue = trim((string) ($results[$parameter->id] ?? ''));

            return $rawValue === '';
        });

        if ($missingParameter) {
            return back()
                ->withErrors([
                    'results' => __('messages.result_required_parameter', [
                        'parameter' => $missingParameter->label(app()->getLocale()),
                    ]),
                ])
                ->withInput();
        }

        $invalidListParameter = $parameters->first(function (LabParameter $parameter) use ($results) {
            if ($parameter->value_type !== 'list') {
                return false;
            }

            $options = is_array($parameter->options) ? $parameter->options : [];

            if ($options === []) {
                return false;
            }

            $rawValue = trim((string) ($results[$parameter->id] ?? ''));

            return ! in_array($rawValue, $options, true);
        });

        if ($invalidListParameter) {
            return back()
                ->withErrors([
                    'results' => __('messages.result_invalid_option', [
                        'parameter' => $invalidListParameter->label(app()->getLocale()),
                    ]),
                ])
                ->withInput();
        }

        DB::transaction(function () use ($analysis, $validated, $results, $resultModels, $parameters) {
            $analysis->notes = $validated['notes'] ?? null;
            $analysis->save();

            foreach ($parameters as $parameterId => $parameter) {
                $analysisResult = $resultModels->get((int) $parameterId);

                if (! $analysisResult) {
                    continue;
                }

                $rawValue = trim((string) ($results[$parameterId] ?? ''));
                $numericValue = $parameter->value_type === 'number'
                    ? $this->parseNumericValue($rawValue)
                    : null;

                $analysisResult->update([
                    'result_value' => $rawValue,
                    'result_numeric' => $numericValue,
                    'is_abnormal' => $this->isAbnormal($parameter, $rawValue),
                ]);
            }

            $analysis->touch();
        });

        return redirect()
            ->route('analyses.show', [
                'analysis' => $analysis,
                ...$this->extractListQuery($request),
            ])
            ->with('status', __('messages.analysis_updated'));
    }

    public function destroy(Request $request, LabAnalysis $analysis): RedirectResponse
    {
        $listQuery = $this->extractListQuery($request);

        $analysis->delete();

        return redirect()
            ->route('analyses.index', $listQuery)
            ->with('status', __('messages.analysis_deleted'));
    }

    private function loadActiveDisciplines(): Collection
    {
        return Discipline::query()
            ->where('is_active', true)
            ->with([
                'categories' => fn ($query) => $query
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->orderBy('id'),
            ])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    private function selectionRules(): array
    {
        return [
            'patient.identifier' => ['required', 'string', 'max:80'],
            'patient.first_name' => ['required', 'string', 'max:120'],
            'patient.last_name' => ['required', 'string', 'max:120'],
            'patient.sex' => ['required', 'in:male,female,other'],
            'patient.age' => ['nullable', 'integer', 'min:0', 'max:130'],
            'patient.phone' => ['nullable', 'string', 'max:40'],
            'analysis_date' => ['required', 'date'],
            'selected_categories' => ['required', 'array', 'min:1'],
            'selected_categories.*' => ['integer', 'exists:categories,id'],
        ];
    }

    private function buildEntryViewData(array $draft): array
    {
        $locale = app()->getLocale();
        $selectedCategoryIds = collect($draft['selected_categories'] ?? [])
            ->map(fn (mixed $value) => (int) $value)
            ->unique()
            ->values()
            ->all();

        $categories = Category::query()
            ->whereIn('id', $selectedCategoryIds)
            ->where('is_active', true)
            ->with([
                'discipline',
                'subcategories' => fn ($query) => $query
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->orderBy('id'),
                'parameters' => fn ($query) => $query
                    ->where('is_active', true)
                    ->where('is_visible', true)
                    ->with('subcategory')
                    ->orderBy('sort_order')
                    ->orderBy('id'),
            ])
            ->orderBy('discipline_id')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $groups = [];
        $parameterCount = 0;

        foreach ($categories as $category) {
            if (! $category->discipline) {
                continue;
            }

            $discipline = $category->discipline;
            $disciplineId = (string) $discipline->id;
            $categoryId = (string) $category->id;

            if (! isset($groups[$disciplineId])) {
                $groups[$disciplineId] = [
                    'id' => $discipline->id,
                    'label' => $discipline->label($locale),
                    'sort_order' => $discipline->sort_order,
                    'categories' => [],
                ];
            }

            $subcategoryGroups = [];

            foreach ($category->parameters as $parameter) {
                $subcategoryId = $parameter->subcategory_id ? (string) $parameter->subcategory_id : 'none';

                if (! isset($subcategoryGroups[$subcategoryId])) {
                    $subcategoryGroups[$subcategoryId] = [
                        'id' => $parameter->subcategory_id,
                        'label' => $parameter->subcategory
                            ? $parameter->subcategory->label($locale)
                            : null,
                        'sort_order' => $parameter->subcategory?->sort_order ?? 0,
                        'rows' => [],
                    ];
                }

                $subcategoryGroups[$subcategoryId]['rows'][] = [
                    'id' => $parameter->id,
                    'label' => $parameter->label($locale),
                    'value_type' => $parameter->value_type,
                    'options' => is_array($parameter->options) ? $parameter->options : [],
                    'default_value' => $parameter->default_value,
                    'unit' => $parameter->unit,
                    'reference' => $parameter->referenceRange(),
                ];

                $parameterCount += 1;
            }

            $groups[$disciplineId]['categories'][$categoryId] = [
                'id' => $category->id,
                'label' => $category->label($locale),
                'sort_order' => $category->sort_order,
                'subcategories' => collect($subcategoryGroups)
                    ->sortBy('sort_order')
                    ->values()
                    ->all(),
            ];
        }

        $groups = collect($groups)
            ->sortBy('sort_order')
            ->map(function (array $discipline) {
                $discipline['categories'] = collect($discipline['categories'])
                    ->sortBy('sort_order')
                    ->values()
                    ->all();

                return $discipline;
            })
            ->values()
            ->all();

        return [
            'groups' => $groups,
            'parameterCount' => $parameterCount,
            'requestedCategoryCount' => count($selectedCategoryIds),
            'resolvedCategoryCount' => $categories->count(),
        ];
    }

    private function buildReportViewData(LabAnalysis $analysis): array
    {
        $locale = app()->getLocale();
        $layout = LabSetting::getValue('report_layout', []);
        $identity = LabSetting::getValue('lab_identity', []);
        $showUnitColumn = (bool) ($layout['show_unit_column'] ?? false);

        $groups = [];

        foreach ($analysis->results as $result) {
            $parameter = $result->parameter;

            if (! $parameter) {
                continue;
            }

            $discipline = $parameter->discipline;
            $category = $parameter->category;
            $subcategory = $parameter->subcategory;

            if (! $discipline || ! $category) {
                continue;
            }

            $disciplineId = (string) $discipline->id;
            $categoryId = (string) $category->id;
            $subcategoryId = $subcategory ? (string) $subcategory->id : 'none';

            if (! isset($groups[$disciplineId])) {
                $groups[$disciplineId] = [
                    'id' => $discipline->id,
                    'label' => $discipline->label($locale),
                    'sort_order' => $discipline->sort_order,
                    'categories' => [],
                ];
            }

            if (! isset($groups[$disciplineId]['categories'][$categoryId])) {
                $groups[$disciplineId]['categories'][$categoryId] = [
                    'id' => $category->id,
                    'label' => $category->label($locale),
                    'sort_order' => $category->sort_order,
                    'subcategories' => [],
                ];
            }

            if (! isset($groups[$disciplineId]['categories'][$categoryId]['subcategories'][$subcategoryId])) {
                $groups[$disciplineId]['categories'][$categoryId]['subcategories'][$subcategoryId] = [
                    'id' => $subcategory?->id,
                    'label' => $subcategory?->label($locale),
                    'lineage' => $this->resolveSubcategoryLineage($subcategory, $locale),
                    'sort_order' => $subcategory?->sort_order ?? 0,
                    'rows' => [],
                ];
            }

            $resultValue = trim((string) $result->result_value);

            if (! $showUnitColumn && $parameter->unit) {
                $resultValue = trim($resultValue.' '.$parameter->unit);
            }

            $abnormalFlag = $this->resolveAbnormalFlag($parameter, $result);

            $groups[$disciplineId]['categories'][$categoryId]['subcategories'][$subcategoryId]['rows'][] = [
                'parameter' => $parameter->label($locale),
                'result' => $resultValue,
                'reference' => $parameter->referenceRange(),
                'unit' => $showUnitColumn ? ($parameter->unit ?: '-') : null,
                'is_abnormal' => $result->is_abnormal,
                'abnormal_flag' => $abnormalFlag,
                'sort_order' => $parameter->sort_order,
            ];
        }

        $groupedResults = collect($groups)
            ->sortBy('sort_order')
            ->map(function (array $discipline) {
                $discipline['categories'] = collect($discipline['categories'])
                    ->sortBy('sort_order')
                    ->map(function (array $category) {
                        $category['subcategories'] = collect($category['subcategories'])
                            ->sortBy('sort_order')
                            ->map(function (array $subcategory) {
                                $subcategory['rows'] = collect($subcategory['rows'])
                                    ->sortBy('sort_order')
                                    ->values()
                                    ->all();

                                return $subcategory;
                            })
                            ->values()
                            ->all();

                        return $category;
                    })
                    ->values()
                    ->all();

                return $discipline;
            })
            ->values()
            ->all();

        return [
            'analysis' => $analysis,
            'groupedResults' => $groupedResults,
            'layout' => $layout,
            'identity' => $identity,
        ];
    }

    private function buildEditViewData(LabAnalysis $analysis): array
    {
        $locale = app()->getLocale();
        $groups = [];

        foreach ($analysis->results as $result) {
            $parameter = $result->parameter;

            if (! $parameter) {
                continue;
            }

            $discipline = $parameter->discipline;
            $category = $parameter->category;
            $subcategory = $parameter->subcategory;

            if (! $discipline || ! $category) {
                continue;
            }

            $disciplineId = (string) $discipline->id;
            $categoryId = (string) $category->id;
            $subcategoryId = $subcategory ? (string) $subcategory->id : 'none';

            if (! isset($groups[$disciplineId])) {
                $groups[$disciplineId] = [
                    'id' => $discipline->id,
                    'label' => $discipline->label($locale),
                    'sort_order' => $discipline->sort_order,
                    'categories' => [],
                ];
            }

            if (! isset($groups[$disciplineId]['categories'][$categoryId])) {
                $groups[$disciplineId]['categories'][$categoryId] = [
                    'id' => $category->id,
                    'label' => $category->label($locale),
                    'sort_order' => $category->sort_order,
                    'subcategories' => [],
                ];
            }

            if (! isset($groups[$disciplineId]['categories'][$categoryId]['subcategories'][$subcategoryId])) {
                $groups[$disciplineId]['categories'][$categoryId]['subcategories'][$subcategoryId] = [
                    'id' => $subcategory?->id,
                    'label' => $subcategory?->label($locale),
                    'sort_order' => $subcategory?->sort_order ?? 0,
                    'rows' => [],
                ];
            }

            $groups[$disciplineId]['categories'][$categoryId]['subcategories'][$subcategoryId]['rows'][] = [
                'id' => $parameter->id,
                'label' => $parameter->label($locale),
                'value_type' => $parameter->value_type,
                'options' => is_array($parameter->options) ? $parameter->options : [],
                'unit' => $parameter->unit,
                'reference' => $parameter->referenceRange(),
                'current_value' => $result->result_value,
                'sort_order' => $parameter->sort_order,
            ];
        }

        $groups = collect($groups)
            ->sortBy('sort_order')
            ->map(function (array $discipline) {
                $discipline['categories'] = collect($discipline['categories'])
                    ->sortBy('sort_order')
                    ->map(function (array $category) {
                        $category['subcategories'] = collect($category['subcategories'])
                            ->sortBy('sort_order')
                            ->map(function (array $subcategory) {
                                $subcategory['rows'] = collect($subcategory['rows'])
                                    ->sortBy('sort_order')
                                    ->values()
                                    ->all();

                                return $subcategory;
                            })
                            ->values()
                            ->all();

                        return $category;
                    })
                    ->values()
                    ->all();

                return $discipline;
            })
            ->values()
            ->all();

        return [
            'analysis' => $analysis,
            'groups' => $groups,
        ];
    }

    private function isAbnormal(LabParameter $parameter, string $rawValue): bool
    {
        if ($parameter->value_type === 'number') {
            $value = $this->parseNumericValue($rawValue);

            if ($value === null) {
                return false;
            }

            if ($parameter->normal_min !== null && $value < (float) $parameter->normal_min) {
                return true;
            }

            if ($parameter->normal_max !== null && $value > (float) $parameter->normal_max) {
                return true;
            }

            return false;
        }

        if ($parameter->normal_text) {
            return mb_strtolower(trim($rawValue)) !== mb_strtolower(trim($parameter->normal_text));
        }

        return false;
    }

    /**
     * @return array<int, array{id:int, label:string, depth:int, sort_order:int}>
     */
    private function resolveSubcategoryLineage(?Subcategory $subcategory, string $locale): array
    {
        if (! $subcategory) {
            return [];
        }

        $lineage = [];
        $cursor = $subcategory;

        while ($cursor) {
            $lineage[] = [
                'id' => (int) $cursor->id,
                'label' => $cursor->label($locale),
                'depth' => (int) $cursor->depth,
                'sort_order' => (int) $cursor->sort_order,
            ];

            if (! $cursor->relationLoaded('parent')) {
                $cursor->load('parent');
            }

            $cursor = $cursor->parent;
        }

        return array_reverse($lineage);
    }

    private function resolveAbnormalFlag(LabParameter $parameter, AnalysisResult $result): ?string
    {
        if ($parameter->value_type !== 'number') {
            return null;
        }

        $numericValue = $result->result_numeric !== null
            ? (float) $result->result_numeric
            : $this->parseNumericValue($result->result_value);

        if ($numericValue === null) {
            return null;
        }

        if ($parameter->normal_min !== null && $numericValue < (float) $parameter->normal_min) {
            return 'L';
        }

        if ($parameter->normal_max !== null && $numericValue > (float) $parameter->normal_max) {
            return 'H';
        }

        return null;
    }

    private function parseNumericValue(?string $rawValue): ?float
    {
        $value = trim((string) $rawValue);

        if ($value === '') {
            return null;
        }

        $normalized = str_replace([',', ' '], ['.', ''], $value);

        if (preg_match('/^-?\d+(?:\.\d+)?$/', $normalized) !== 1) {
            return null;
        }

        return (float) $normalized;
    }

    private function extractListQuery(
        Request $request,
        ?string $search = null,
        ?string $period = null,
        ?string $sort = null,
        ?string $direction = null,
        ?int $perPage = null
    ): array {
        $search = $search ?? trim((string) $request->string('search'));
        $period = $period ?? (string) $request->string('period', 'all');
        $sort = $sort ?? (string) $request->string('sort', 'date');
        $direction = $direction ?? strtolower((string) $request->string('direction', 'desc'));
        $perPage = $perPage ?? (int) $request->integer('per_page', 15);
        $page = $request->integer('page');

        if (! in_array($period, ['all', 'today', '7_days', '30_days'], true)) {
            $period = 'all';
        }

        if ($sort !== 'date') {
            $sort = 'date';
        }

        if (! in_array($direction, ['asc', 'desc'], true)) {
            $direction = 'desc';
        }

        if (! in_array($perPage, [15, 20], true)) {
            $perPage = 15;
        }

        $query = [
            'search' => $search !== '' ? $search : null,
            'period' => $period !== 'all' ? $period : null,
            'sort' => $sort !== 'date' ? $sort : null,
            'direction' => $direction !== 'desc' ? $direction : null,
            'per_page' => $perPage !== 15 ? $perPage : null,
            'page' => $page > 1 ? $page : null,
        ];

        return array_filter($query, fn (mixed $value) => $value !== null);
    }
}
