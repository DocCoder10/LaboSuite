<?php

namespace App\Http\Controllers;

use App\Models\AnalysisResult;
use App\Models\Category;
use App\Models\Discipline;
use App\Models\LabAnalysis;
use App\Models\LabParameter;
use App\Models\LabSetting;
use App\Models\Patient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AnalysisController extends Controller
{
    public function index(): View
    {
        $analyses = LabAnalysis::query()
            ->with('patient')
            ->latest('analysis_date')
            ->latest('id')
            ->paginate(12);

        return view('analyses.index', [
            'analyses' => $analyses,
        ]);
    }

    public function create(): View
    {
        $disciplines = Discipline::query()
            ->where('is_active', true)
            ->with([
                'categories' => fn ($query) => $query
                    ->where('is_active', true)
                    ->with([
                        'subcategories' => fn ($subQuery) => $subQuery->where('is_active', true),
                        'parameters' => fn ($paramQuery) => $paramQuery
                            ->where('is_active', true)
                            ->where('is_visible', true)
                            ->with('subcategory'),
                    ]),
            ])
            ->orderBy('sort_order')
            ->get();

        return view('analyses.create', [
            'analysisDate' => now()->toDateString(),
            'disciplines' => $disciplines,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'patient.identifier' => ['required', 'string', 'max:80'],
            'patient.first_name' => ['required', 'string', 'max:120'],
            'patient.last_name' => ['required', 'string', 'max:120'],
            'patient.sex' => ['required', 'in:male,female,other'],
            'patient.age' => ['nullable', 'integer', 'min:0', 'max:130'],
            'patient.phone' => ['nullable', 'string', 'max:40'],
            'analysis_date' => ['required', 'date'],
            'selected_categories' => ['required', 'array', 'min:1'],
            'selected_categories.*' => ['integer', 'exists:categories,id'],
            'results' => ['nullable', 'array'],
            'notes' => ['nullable', 'string'],
        ]);

        $analysis = DB::transaction(function () use ($validated) {
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

            $parameters = LabParameter::query()
                ->whereIn('category_id', $validated['selected_categories'])
                ->where('is_active', true)
                ->where('is_visible', true)
                ->get();

            $results = $validated['results'] ?? [];

            foreach ($parameters as $parameter) {
                $rawValue = trim((string) ($results[$parameter->id] ?? ''));

                if ($rawValue === '') {
                    continue;
                }

                $numericValue = null;
                if ($parameter->value_type === 'number' && is_numeric($rawValue)) {
                    $numericValue = (float) $rawValue;
                }

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
            'categories.discipline',
        ]);

        return view('analyses.show', $this->buildReportViewData($analysis));
    }

    public function print(LabAnalysis $analysis): View
    {
        $analysis->load([
            'patient',
            'results.parameter.discipline',
            'results.parameter.category',
            'results.parameter.subcategory',
            'categories.discipline',
        ]);

        return view('analyses.print', $this->buildReportViewData($analysis));
    }

    private function buildReportViewData(LabAnalysis $analysis): array
    {
        $locale = app()->getLocale();
        $layout = LabSetting::getValue('report_layout', []);
        $identity = LabSetting::getValue('lab_identity', []);

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

            $resultValue = $result->result_value;
            $showUnitColumn = (bool) ($layout['show_unit_column'] ?? false);

            if (! $showUnitColumn && $parameter->unit) {
                $resultValue = trim($resultValue.' '.$parameter->unit);
            }

            $groups[$disciplineId]['categories'][$categoryId]['subcategories'][$subcategoryId]['rows'][] = [
                'parameter' => $parameter->label($locale),
                'result' => $resultValue,
                'reference' => $parameter->referenceRange(),
                'unit' => $showUnitColumn ? ($parameter->unit ?: '-') : null,
                'is_abnormal' => $result->is_abnormal,
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

    private function isAbnormal(LabParameter $parameter, string $rawValue): bool
    {
        if ($parameter->value_type === 'number' && is_numeric($rawValue)) {
            $value = (float) $rawValue;

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
}
