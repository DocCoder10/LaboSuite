<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Discipline;
use App\Models\LabParameter;
use App\Models\Subcategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CatalogController extends Controller
{
    private const MAX_TREE_LEVEL = 5;

    private const MAX_SUBCATEGORY_DEPTH = 2;

    public function index(): View
    {
        $disciplines = Discipline::query()
            ->with([
                'categories' => fn ($categoryQuery) => $categoryQuery
                    ->orderBy('sort_order')
                    ->orderBy('name')
                    ->with([
                        'subcategories' => fn ($subcategoryQuery) => $subcategoryQuery
                            ->whereNull('parent_subcategory_id')
                            ->orderBy('sort_order')
                            ->orderBy('name')
                            ->with([
                                'children' => fn ($childQuery) => $childQuery
                                    ->orderBy('sort_order')
                                    ->orderBy('name')
                                    ->with([
                                        'parameters' => fn ($parameterQuery) => $parameterQuery
                                            ->orderBy('sort_order')
                                            ->orderBy('name'),
                                    ]),
                                'parameters' => fn ($parameterQuery) => $parameterQuery
                                    ->orderBy('sort_order')
                                    ->orderBy('name'),
                            ]),
                        'parameters' => fn ($parameterQuery) => $parameterQuery
                            ->whereNull('subcategory_id')
                            ->orderBy('sort_order')
                            ->orderBy('name'),
                    ]),
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $categories = Category::query()
            ->with('discipline')
            ->orderBy('discipline_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $subcategories = Subcategory::query()
            ->with(['category.discipline', 'parent'])
            ->orderBy('category_id')
            ->orderBy('depth')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('catalog.index', [
            'disciplines' => $disciplines,
            'categories' => $categories,
            'subcategories' => $subcategories,
        ]);
    }

    public function storeDiscipline(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $name = trim($data['name']);
        $this->assertUniqueDisciplineName($name);

        Discipline::query()->create([
            'code' => $this->generateUniqueCode(
                'discipline',
                $name,
                fn (string $code) => Discipline::query()->where('code', $code)->exists(),
            ),
            'name' => $name,
            'labels' => $this->buildLabels($name),
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => true,
        ]);

        return back()->with('status', __('messages.catalog_saved'));
    }

    public function updateDiscipline(Request $request, Discipline $discipline): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $name = trim($data['name']);
        $this->assertUniqueDisciplineName($name, $discipline->id);

        $discipline->update([
            'name' => $name,
            'labels' => $this->buildLabels($name),
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('status', __('messages.catalog_saved'));
    }

    public function destroyDiscipline(Discipline $discipline): RedirectResponse
    {
        if ($discipline->categories()->exists() || $discipline->parameters()->exists()) {
            return back()->withErrors([
                'catalog' => __('messages.catalog_delete_has_children'),
            ]);
        }

        $discipline->delete();

        return back()->with('status', __('messages.catalog_saved'));
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'discipline_id' => ['required', 'exists:disciplines,id'],
            'name' => ['required', 'string', 'max:120'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $name = trim($data['name']);
        $disciplineId = (int) $data['discipline_id'];

        $this->assertUniqueCategoryName($disciplineId, $name);

        Category::query()->create([
            'discipline_id' => $disciplineId,
            'code' => $this->generateUniqueCode(
                'categorie',
                $name,
                fn (string $code) => Category::query()->where('code', $code)->exists(),
            ),
            'name' => $name,
            'labels' => $this->buildLabels($name),
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => true,
        ]);

        return back()->with('status', __('messages.catalog_saved'));
    }

    public function updateCategory(Request $request, Category $category): RedirectResponse
    {
        $data = $request->validate([
            'discipline_id' => ['required', 'exists:disciplines,id'],
            'name' => ['required', 'string', 'max:120'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $name = trim($data['name']);
        $disciplineId = (int) $data['discipline_id'];

        $this->assertUniqueCategoryName($disciplineId, $name, $category->id);

        $disciplineChanged = (int) $category->discipline_id !== $disciplineId;

        $category->update([
            'discipline_id' => $disciplineId,
            'name' => $name,
            'labels' => $this->buildLabels($name),
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active'),
        ]);

        if ($disciplineChanged) {
            LabParameter::query()
                ->where('category_id', $category->id)
                ->update([
                    'discipline_id' => $disciplineId,
                ]);
        }

        return back()->with('status', __('messages.catalog_saved'));
    }

    public function destroyCategory(Category $category): RedirectResponse
    {
        if ($category->allSubcategories()->exists() || $category->parameters()->exists()) {
            return back()->withErrors([
                'catalog' => __('messages.catalog_delete_has_children'),
            ]);
        }

        $category->delete();

        return back()->with('status', __('messages.catalog_saved'));
    }

    public function storeSubcategory(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'parent_subcategory_id' => ['nullable', 'integer', 'exists:subcategories,id'],
            'name' => ['required', 'string', 'max:120'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $name = trim($data['name']);
        $category = Category::query()->findOrFail((int) $data['category_id']);
        $parent = $this->resolveParentSubcategoryForCategory(
            $category,
            isset($data['parent_subcategory_id']) ? (int) $data['parent_subcategory_id'] : null
        );

        $depth = $parent ? ($parent->depth + 1) : 1;

        if ($depth > self::MAX_SUBCATEGORY_DEPTH) {
            throw ValidationException::withMessages([
                'parent_subcategory_id' => __('messages.catalog_depth_limit'),
            ]);
        }

        $this->assertNotSameAsParentName($parent?->name ?? $category->name, $name);
        $this->assertUniqueSubcategoryName($category->id, $parent?->id, $name);
        $this->convertParentToContainer($category, $parent);

        Subcategory::query()->create([
            'category_id' => $category->id,
            'parent_subcategory_id' => $parent?->id,
            'depth' => $depth,
            'code' => $this->generateUniqueCode(
                'sous-categorie',
                $name,
                fn (string $code) => Subcategory::query()->where('code', $code)->exists(),
            ),
            'name' => $name,
            'labels' => $this->buildLabels($name),
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => true,
        ]);

        return back()->with('status', __('messages.catalog_saved'));
    }

    public function updateSubcategory(Request $request, Subcategory $subcategory): RedirectResponse
    {
        $data = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'parent_subcategory_id' => ['nullable', 'integer', 'exists:subcategories,id'],
            'name' => ['required', 'string', 'max:120'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $name = trim($data['name']);
        $category = Category::query()->findOrFail((int) $data['category_id']);
        $parent = $this->resolveParentSubcategoryForCategory(
            $category,
            isset($data['parent_subcategory_id']) ? (int) $data['parent_subcategory_id'] : null,
            $subcategory
        );

        $depth = $parent ? ($parent->depth + 1) : 1;

        if ($depth > self::MAX_SUBCATEGORY_DEPTH) {
            throw ValidationException::withMessages([
                'parent_subcategory_id' => __('messages.catalog_depth_limit'),
            ]);
        }

        if ($subcategory->children()->exists() && $depth >= self::MAX_SUBCATEGORY_DEPTH) {
            throw ValidationException::withMessages([
                'parent_subcategory_id' => __('messages.catalog_depth_limit'),
            ]);
        }

        $categoryChanged = (int) $subcategory->category_id !== (int) $category->id;

        if ($categoryChanged && $subcategory->children()->exists()) {
            throw ValidationException::withMessages([
                'category_id' => __('messages.catalog_reassign_blocked_with_children'),
            ]);
        }

        $this->assertNotSameAsParentName($parent?->name ?? $category->name, $name);
        $this->assertUniqueSubcategoryName($category->id, $parent?->id, $name, $subcategory->id);
        $this->convertParentToContainer($category, $parent);

        $subcategory->update([
            'category_id' => (int) $category->id,
            'parent_subcategory_id' => $parent?->id,
            'depth' => $depth,
            'name' => $name,
            'labels' => $this->buildLabels($name),
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active'),
        ]);

        if ($categoryChanged) {
            LabParameter::query()
                ->where('subcategory_id', $subcategory->id)
                ->update([
                    'discipline_id' => $category->discipline_id,
                    'category_id' => $category->id,
                ]);
        }

        return back()->with('status', __('messages.catalog_saved'));
    }

    public function destroySubcategory(Subcategory $subcategory): RedirectResponse
    {
        if ($subcategory->children()->exists() || $subcategory->parameters()->exists()) {
            return back()->withErrors([
                'catalog' => __('messages.catalog_delete_has_children'),
            ]);
        }

        $subcategory->delete();

        return back()->with('status', __('messages.catalog_saved'));
    }

    public function storeParameter(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'subcategory_id' => ['nullable', 'integer', 'exists:subcategories,id'],
            'name' => ['required', 'string', 'max:120'],
            'unit' => ['nullable', 'string', 'max:40'],
            'value_type' => ['required', Rule::in(['number', 'text', 'list'])],
            'reference' => ['nullable', 'string', 'max:255'],
            'options_csv' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $name = trim($data['name']);
        $category = Category::query()->with('discipline')->findOrFail((int) $data['category_id']);
        $subcategory = $this->resolveSubcategoryForCategory(
            $category,
            isset($data['subcategory_id']) ? (int) $data['subcategory_id'] : null
        );

        $this->assertCanAttachParameter($category, $subcategory);
        $this->assertNotSameAsParentName($subcategory?->name ?? $category->name, $name);
        $this->assertUniqueParameterName($category->id, $subcategory?->id, $name);

        [$normalMin, $normalMax, $normalText] = $this->extractReferenceFields($data['value_type'], $data['reference'] ?? null);

        LabParameter::query()->create([
            'discipline_id' => $category->discipline_id,
            'category_id' => $category->id,
            'subcategory_id' => $subcategory?->id,
            'code' => $this->generateUniqueCode(
                'param',
                $category->name.'-'.$name,
                fn (string $code) => LabParameter::query()->where('code', $code)->exists(),
            ),
            'name' => $name,
            'labels' => $this->buildLabels($name),
            'unit' => ($data['unit'] ?? '') !== '' ? $data['unit'] : null,
            'value_type' => $data['value_type'],
            'normal_min' => $normalMin,
            'normal_max' => $normalMax,
            'normal_text' => $normalText,
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
            'category_id' => ['required', 'exists:categories,id'],
            'subcategory_id' => ['nullable', 'integer', 'exists:subcategories,id'],
            'name' => ['required', 'string', 'max:120'],
            'unit' => ['nullable', 'string', 'max:40'],
            'value_type' => ['required', Rule::in(['number', 'text', 'list'])],
            'reference' => ['nullable', 'string', 'max:255'],
            'options_csv' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_visible' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $name = trim($data['name']);
        $category = Category::query()->findOrFail((int) $data['category_id']);
        $subcategory = $this->resolveSubcategoryForCategory(
            $category,
            isset($data['subcategory_id']) ? (int) $data['subcategory_id'] : null
        );

        $this->assertCanAttachParameter($category, $subcategory);
        $this->assertNotSameAsParentName($subcategory?->name ?? $category->name, $name);
        $this->assertUniqueParameterName($category->id, $subcategory?->id, $name, $parameter->id);

        [$normalMin, $normalMax, $normalText] = $this->extractReferenceFields($data['value_type'], $data['reference'] ?? null);

        $parameter->update([
            'discipline_id' => $category->discipline_id,
            'category_id' => $category->id,
            'subcategory_id' => $subcategory?->id,
            'name' => $name,
            'labels' => $this->buildLabels($name),
            'unit' => ($data['unit'] ?? '') !== '' ? $data['unit'] : null,
            'value_type' => $data['value_type'],
            'normal_min' => $normalMin,
            'normal_max' => $normalMax,
            'normal_text' => $normalText,
            'options' => $this->parseOptions($data['options_csv'] ?? null),
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

    private function buildLabels(string $name): array
    {
        return [
            'fr' => $name,
        ];
    }

    /**
     * @param callable(string):bool $exists
     */
    private function generateUniqueCode(string $prefix, string $seed, callable $exists): string
    {
        $base = Str::slug(Str::lower($seed), '-');

        if ($base === '') {
            $base = 'item';
        }

        $candidate = "{$prefix}-{$base}";
        $index = 1;

        while ($exists($candidate)) {
            $index++;
            $candidate = "{$prefix}-{$base}-{$index}";
        }

        return $candidate;
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

    /**
     * @return array{0: float|null, 1: float|null, 2: string|null}
     */
    private function extractReferenceFields(string $valueType, ?string $reference): array
    {
        $reference = $reference !== null ? trim($reference) : null;

        if ($reference === null || $reference === '') {
            return [null, null, null];
        }

        if ($valueType === 'number') {
            $candidate = str_replace([',', '–', '—'], ['.', '-', '-'], $reference);

            if (preg_match('/^\s*(-?\d+(?:\.\d+)?)\s*-\s*(-?\d+(?:\.\d+)?)\s*$/', $candidate, $matches) === 1) {
                return [(float) $matches[1], (float) $matches[2], null];
            }
        }

        return [null, null, $reference];
    }

    private function resolveSubcategoryForCategory(Category $category, ?int $subcategoryId): ?Subcategory
    {
        if ($subcategoryId === null) {
            return null;
        }

        $subcategory = Subcategory::query()->findOrFail($subcategoryId);

        if ((int) $subcategory->category_id !== (int) $category->id) {
            throw ValidationException::withMessages([
                'subcategory_id' => __('messages.subcategory_category_error'),
            ]);
        }

        return $subcategory;
    }

    private function resolveParentSubcategoryForCategory(Category $category, ?int $parentSubcategoryId, ?Subcategory $currentSubcategory = null): ?Subcategory
    {
        if ($parentSubcategoryId === null) {
            return null;
        }

        $parent = Subcategory::query()->findOrFail($parentSubcategoryId);

        if ((int) $parent->category_id !== (int) $category->id) {
            throw ValidationException::withMessages([
                'parent_subcategory_id' => __('messages.subcategory_category_error'),
            ]);
        }

        if ($currentSubcategory && (int) $parent->id === (int) $currentSubcategory->id) {
            throw ValidationException::withMessages([
                'parent_subcategory_id' => __('messages.catalog_invalid_parent'),
            ]);
        }

        if ($currentSubcategory && $this->isSubcategoryDescendantOf((int) $parent->id, (int) $currentSubcategory->id)) {
            throw ValidationException::withMessages([
                'parent_subcategory_id' => __('messages.catalog_invalid_parent'),
            ]);
        }

        return $parent;
    }

    private function isSubcategoryDescendantOf(int $candidateParentId, int $subcategoryId): bool
    {
        $cursor = Subcategory::query()->find($candidateParentId);

        while ($cursor) {
            if ((int) $cursor->id === $subcategoryId) {
                return true;
            }

            if ($cursor->parent_subcategory_id === null) {
                return false;
            }

            $cursor = Subcategory::query()->find((int) $cursor->parent_subcategory_id);
        }

        return false;
    }

    private function assertUniqueDisciplineName(string $name, ?int $ignoreId = null): void
    {
        $query = Discipline::query();

        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }

        $exists = $this->whereNameEquals($query, $name)->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'name' => __('messages.catalog_duplicate_same_level'),
            ]);
        }
    }

    private function assertUniqueCategoryName(int $disciplineId, string $name, ?int $ignoreId = null): void
    {
        $query = Category::query()->where('discipline_id', $disciplineId);

        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }

        $exists = $this->whereNameEquals($query, $name)->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'name' => __('messages.catalog_duplicate_same_level'),
            ]);
        }
    }

    private function assertUniqueSubcategoryName(int $categoryId, ?int $parentSubcategoryId, string $name, ?int $ignoreId = null): void
    {
        $query = Subcategory::query()->where('category_id', $categoryId);

        if ($parentSubcategoryId === null) {
            $query->whereNull('parent_subcategory_id');
        } else {
            $query->where('parent_subcategory_id', $parentSubcategoryId);
        }

        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }

        $exists = $this->whereNameEquals($query, $name)->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'name' => __('messages.catalog_duplicate_same_level'),
            ]);
        }
    }

    private function assertUniqueParameterName(int $categoryId, ?int $subcategoryId, string $name, ?int $ignoreId = null): void
    {
        $query = LabParameter::query()->where('category_id', $categoryId);

        if ($subcategoryId === null) {
            $query->whereNull('subcategory_id');
        } else {
            $query->where('subcategory_id', $subcategoryId);
        }

        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }

        $exists = $this->whereNameEquals($query, $name)->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'name' => __('messages.catalog_duplicate_same_level'),
            ]);
        }
    }

    private function assertNotSameAsParentName(?string $parentName, string $childName): void
    {
        if ($parentName === null) {
            return;
        }

        $parent = mb_strtolower(trim($parentName));
        $child = mb_strtolower(trim($childName));

        if ($parent === $child) {
            throw ValidationException::withMessages([
                'name' => __('messages.catalog_same_as_parent'),
            ]);
        }
    }

    private function convertParentToContainer(Category $category, ?Subcategory $parent): void
    {
        if ($parent === null) {
            LabParameter::query()
                ->where('category_id', $category->id)
                ->whereNull('subcategory_id')
                ->where(function ($query) {
                    $query->where('is_active', true)->orWhere('is_visible', true);
                })
                ->update([
                    'is_active' => false,
                    'is_visible' => false,
                ]);

            return;
        }

        LabParameter::query()
            ->where('subcategory_id', $parent->id)
            ->where(function ($query) {
                $query->where('is_active', true)->orWhere('is_visible', true);
            })
            ->update([
                'is_active' => false,
                'is_visible' => false,
            ]);
    }

    private function assertCanAttachParameter(Category $category, ?Subcategory $subcategory): void
    {
        if ($subcategory === null) {
            $hasChildren = Subcategory::query()
                ->where('category_id', $category->id)
                ->exists();

            if ($hasChildren) {
                throw ValidationException::withMessages([
                    'subcategory_id' => __('messages.catalog_leaf_only_values'),
                ]);
            }

            $parentLevel = 2;
        } else {
            if ($subcategory->children()->exists()) {
                throw ValidationException::withMessages([
                    'subcategory_id' => __('messages.catalog_leaf_only_values'),
                ]);
            }

            $parentLevel = 2 + (int) $subcategory->depth;
        }

        $leafLevel = $parentLevel + 1;

        if ($leafLevel > self::MAX_TREE_LEVEL) {
            throw ValidationException::withMessages([
                'subcategory_id' => __('messages.catalog_depth_limit'),
            ]);
        }
    }

    private function whereNameEquals($query, string $name)
    {
        return $query->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($name))]);
    }
}
