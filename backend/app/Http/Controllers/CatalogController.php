<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Discipline;
use App\Models\LabParameter;
use App\Models\Subcategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function index(): View
    {
        return view('catalog.index', [
            'disciplines' => Discipline::query()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
            'categories' => Category::query()
                ->with('discipline')
                ->orderBy('discipline_id')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
            'subcategories' => Subcategory::query()
                ->with(['category.discipline'])
                ->orderBy('category_id')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
            'parameters' => LabParameter::query()
                ->with(['discipline', 'category', 'subcategory'])
                ->orderBy('discipline_id')
                ->orderBy('category_id')
                ->orderByRaw('CASE WHEN subcategory_id IS NULL THEN 0 ELSE 1 END')
                ->orderBy('subcategory_id')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->paginate(20),
        ]);
    }

    public function storeDiscipline(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:60', 'unique:disciplines,code'],
            'name' => ['required', 'string', 'max:120'],
            'label_fr' => ['nullable', 'string', 'max:120'],
            'label_ar' => ['nullable', 'string', 'max:120'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        Discipline::query()->create([
            'code' => $data['code'],
            'name' => $data['name'],
            'labels' => [
                'en' => $data['name'],
                'fr' => $data['label_fr'] ?? $data['name'],
                'ar' => $data['label_ar'] ?? $data['name'],
            ],
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => true,
        ]);

        return back()->with('status', __('messages.catalog_saved'));
    }

    public function updateDiscipline(Request $request, Discipline $discipline): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:60', Rule::unique('disciplines', 'code')->ignore($discipline->id)],
            'name' => ['required', 'string', 'max:120'],
            'label_fr' => ['nullable', 'string', 'max:120'],
            'label_ar' => ['nullable', 'string', 'max:120'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $discipline->update([
            'code' => $data['code'],
            'name' => $data['name'],
            'labels' => $this->buildLabels($data),
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('status', __('messages.catalog_saved'));
    }

    public function destroyDiscipline(Discipline $discipline): RedirectResponse
    {
        $discipline->delete();

        return back()->with('status', __('messages.catalog_saved'));
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'discipline_id' => ['required', 'exists:disciplines,id'],
            'code' => ['required', 'string', 'max:60', 'unique:categories,code'],
            'name' => ['required', 'string', 'max:120'],
            'label_fr' => ['nullable', 'string', 'max:120'],
            'label_ar' => ['nullable', 'string', 'max:120'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        Category::query()->create([
            'discipline_id' => (int) $data['discipline_id'],
            'code' => $data['code'],
            'name' => $data['name'],
            'labels' => $this->buildLabels($data),
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => true,
        ]);

        return back()->with('status', __('messages.catalog_saved'));
    }

    public function updateCategory(Request $request, Category $category): RedirectResponse
    {
        $data = $request->validate([
            'discipline_id' => ['required', 'exists:disciplines,id'],
            'code' => ['required', 'string', 'max:60', Rule::unique('categories', 'code')->ignore($category->id)],
            'name' => ['required', 'string', 'max:120'],
            'label_fr' => ['nullable', 'string', 'max:120'],
            'label_ar' => ['nullable', 'string', 'max:120'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $category->update([
            'discipline_id' => (int) $data['discipline_id'],
            'code' => $data['code'],
            'name' => $data['name'],
            'labels' => $this->buildLabels($data),
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('status', __('messages.catalog_saved'));
    }

    public function destroyCategory(Category $category): RedirectResponse
    {
        $category->delete();

        return back()->with('status', __('messages.catalog_saved'));
    }

    public function storeSubcategory(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'code' => ['required', 'string', 'max:60', 'unique:subcategories,code'],
            'name' => ['required', 'string', 'max:120'],
            'label_fr' => ['nullable', 'string', 'max:120'],
            'label_ar' => ['nullable', 'string', 'max:120'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        Subcategory::query()->create([
            'category_id' => (int) $data['category_id'],
            'code' => $data['code'],
            'name' => $data['name'],
            'labels' => $this->buildLabels($data),
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => true,
        ]);

        return back()->with('status', __('messages.catalog_saved'));
    }

    public function updateSubcategory(Request $request, Subcategory $subcategory): RedirectResponse
    {
        $data = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'code' => ['required', 'string', 'max:60', Rule::unique('subcategories', 'code')->ignore($subcategory->id)],
            'name' => ['required', 'string', 'max:120'],
            'label_fr' => ['nullable', 'string', 'max:120'],
            'label_ar' => ['nullable', 'string', 'max:120'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $subcategory->update([
            'category_id' => (int) $data['category_id'],
            'code' => $data['code'],
            'name' => $data['name'],
            'labels' => $this->buildLabels($data),
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('status', __('messages.catalog_saved'));
    }

    public function destroySubcategory(Subcategory $subcategory): RedirectResponse
    {
        $subcategory->delete();

        return back()->with('status', __('messages.catalog_saved'));
    }

    public function storeParameter(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'discipline_id' => ['required', 'exists:disciplines,id'],
            'category_id' => ['required', 'exists:categories,id'],
            'subcategory_id' => ['nullable', 'exists:subcategories,id'],
            'code' => ['required', 'string', 'max:80', 'unique:lab_parameters,code'],
            'name' => ['required', 'string', 'max:120'],
            'label_fr' => ['nullable', 'string', 'max:120'],
            'label_ar' => ['nullable', 'string', 'max:120'],
            'unit' => ['nullable', 'string', 'max:40'],
            'value_type' => ['required', 'in:number,text,list'],
            'normal_min' => ['nullable', 'numeric'],
            'normal_max' => ['nullable', 'numeric'],
            'normal_text' => ['nullable', 'string', 'max:120'],
            'options_csv' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        if ($hierarchyErrors = $this->validateParameterHierarchy($data)) {
            return back()->withErrors($hierarchyErrors)->withInput();
        }

        LabParameter::query()->create([
            'discipline_id' => (int) $data['discipline_id'],
            'category_id' => (int) $data['category_id'],
            'subcategory_id' => $data['subcategory_id'] ?? null,
            'code' => $data['code'],
            'name' => $data['name'],
            'labels' => $this->buildLabels($data),
            'unit' => $data['unit'] ?? null,
            'value_type' => $data['value_type'],
            'normal_min' => $data['normal_min'] ?? null,
            'normal_max' => $data['normal_max'] ?? null,
            'normal_text' => $data['normal_text'] ?? null,
            'options' => $this->parseOptions($data['options_csv'] ?? null),
            'abnormal_style' => [
                'font_weight' => '700',
                'text_color' => '#b91c1c',
            ],
            'sort_order' => $data['sort_order'] ?? 0,
            'is_visible' => true,
            'is_active' => true,
        ]);

        return back()->with('status', __('messages.catalog_saved'));
    }

    public function updateParameter(Request $request, LabParameter $parameter): RedirectResponse
    {
        $data = $request->validate([
            'discipline_id' => ['required', 'exists:disciplines,id'],
            'category_id' => ['required', 'exists:categories,id'],
            'subcategory_id' => ['nullable', 'exists:subcategories,id'],
            'code' => ['required', 'string', 'max:80', Rule::unique('lab_parameters', 'code')->ignore($parameter->id)],
            'name' => ['required', 'string', 'max:120'],
            'label_fr' => ['nullable', 'string', 'max:120'],
            'label_ar' => ['nullable', 'string', 'max:120'],
            'unit' => ['nullable', 'string', 'max:40'],
            'value_type' => ['required', 'in:number,text,list'],
            'normal_min' => ['nullable', 'numeric'],
            'normal_max' => ['nullable', 'numeric'],
            'normal_text' => ['nullable', 'string', 'max:120'],
            'options_csv' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_visible' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ($hierarchyErrors = $this->validateParameterHierarchy($data)) {
            return back()->withErrors($hierarchyErrors)->withInput();
        }

        $parameter->update([
            'discipline_id' => (int) $data['discipline_id'],
            'category_id' => (int) $data['category_id'],
            'subcategory_id' => $data['subcategory_id'] ?? null,
            'code' => $data['code'],
            'name' => $data['name'],
            'labels' => $this->buildLabels($data),
            'unit' => $data['unit'] ?? null,
            'value_type' => $data['value_type'],
            'normal_min' => $data['normal_min'] ?? null,
            'normal_max' => $data['normal_max'] ?? null,
            'normal_text' => $data['normal_text'] ?? null,
            'options' => $this->parseOptions($data['options_csv'] ?? null),
            'abnormal_style' => is_array($parameter->abnormal_style) && $parameter->abnormal_style !== []
                ? $parameter->abnormal_style
                : [
                    'font_weight' => '700',
                    'text_color' => '#b91c1c',
                ],
            'sort_order' => $data['sort_order'] ?? 0,
            'is_visible' => $request->boolean('is_visible'),
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('status', __('messages.catalog_saved'));
    }

    public function destroyParameter(LabParameter $parameter): RedirectResponse
    {
        $parameter->delete();

        return back()->with('status', __('messages.catalog_saved'));
    }

    private function buildLabels(array $data): array
    {
        return [
            'en' => $data['name'],
            'fr' => $data['label_fr'] ?? $data['name'],
            'ar' => $data['label_ar'] ?? $data['name'],
        ];
    }

    private function parseOptions(?string $optionsCsv): ?array
    {
        if ($optionsCsv === null) {
            return null;
        }

        $options = collect(explode(',', $optionsCsv))
            ->map(fn (string $value) => trim($value))
            ->filter()
            ->unique()
            ->values()
            ->all();

        return $options === [] ? null : $options;
    }

    private function validateParameterHierarchy(array $data): ?array
    {
        $category = Category::query()->find((int) $data['category_id']);

        if (! $category || (int) $category->discipline_id !== (int) $data['discipline_id']) {
            return [
                'category_id' => __('messages.category_discipline_error'),
            ];
        }

        if (! empty($data['subcategory_id'])) {
            $subcategory = Subcategory::query()->find((int) $data['subcategory_id']);

            if (! $subcategory || (int) $subcategory->category_id !== (int) $category->id) {
                return [
                    'subcategory_id' => __('messages.subcategory_category_error'),
                ];
            }
        }

        return null;
    }
}
