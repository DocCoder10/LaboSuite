<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Discipline;
use App\Models\LabParameter;
use App\Models\Subcategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function index(): View
    {
        return view('catalog.index', [
            'disciplines' => Discipline::query()->orderBy('sort_order')->get(),
            'categories' => Category::query()->with('discipline')->orderBy('sort_order')->get(),
            'subcategories' => Subcategory::query()->with('category')->orderBy('sort_order')->get(),
            'parameters' => LabParameter::query()
                ->with(['discipline', 'category', 'subcategory'])
                ->orderBy('sort_order')
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

        $options = null;
        if (! empty($data['options_csv'])) {
            $options = collect(explode(',', $data['options_csv']))
                ->map(fn (string $value) => trim($value))
                ->filter()
                ->values()
                ->all();
        }

        LabParameter::query()->create([
            'discipline_id' => (int) $data['discipline_id'],
            'category_id' => (int) $data['category_id'],
            'subcategory_id' => $data['subcategory_id'] ?? null,
            'code' => $data['code'],
            'name' => $data['name'],
            'labels' => [
                'en' => $data['name'],
                'fr' => $data['label_fr'] ?? $data['name'],
                'ar' => $data['label_ar'] ?? $data['name'],
            ],
            'unit' => $data['unit'] ?? null,
            'value_type' => $data['value_type'],
            'normal_min' => $data['normal_min'] ?? null,
            'normal_max' => $data['normal_max'] ?? null,
            'normal_text' => $data['normal_text'] ?? null,
            'options' => $options,
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

    public function destroyParameter(LabParameter $parameter): RedirectResponse
    {
        $parameter->delete();

        return back()->with('status', __('messages.catalog_saved'));
    }
}
