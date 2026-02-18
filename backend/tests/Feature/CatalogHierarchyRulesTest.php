<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Discipline;
use App\Models\LabParameter;
use App\Models\Subcategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogHierarchyRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_analysis_can_exist_without_subanalyses_and_keep_direct_value(): void
    {
        $discipline = $this->makeDiscipline('biochimie');
        $category = $this->makeCategory($discipline, 'creatininemie');

        $response = $this->post(route('catalog.parameters.store'), [
            'category_id' => $category->id,
            'name' => 'Creatininemie',
            'value_type' => 'number',
            'unit' => 'umol/L',
            'reference' => '60 - 115',
            'sort_order' => 10,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('lab_parameters', [
            'category_id' => $category->id,
            'subcategory_id' => null,
            'name' => 'Creatininemie',
            'is_active' => 1,
            'is_visible' => 1,
        ]);
    }

    public function test_adding_subanalysis_converts_parent_analysis_to_container(): void
    {
        $discipline = $this->makeDiscipline('hematologie');
        $category = $this->makeCategory($discipline, 'nfs');

        $parameter = $this->makeParameter(
            discipline: $discipline,
            category: $category,
            name: 'NFS',
            subcategory: null
        );

        $response = $this->post(route('catalog.subcategories.store'), [
            'category_id' => $category->id,
            'name' => 'Hemoglobine',
            'sort_order' => 10,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('subcategories', [
            'category_id' => $category->id,
            'name' => 'Hemoglobine',
        ]);

        $this->assertDatabaseHas('lab_parameters', [
            'id' => $parameter->id,
            'is_active' => 0,
            'is_visible' => 0,
        ]);
    }

    public function test_adding_child_under_subanalysis_converts_it_to_container(): void
    {
        $discipline = $this->makeDiscipline('parasitologie');
        $category = $this->makeCategory($discipline, 'goutte-epaisse');
        $parentSubcategory = $this->makeSubcategory($category, 'parasites-observes', null, 1);

        $parameter = $this->makeParameter(
            discipline: $discipline,
            category: $category,
            name: 'Parasites observes',
            subcategory: $parentSubcategory
        );

        $response = $this->post(route('catalog.subcategories.store'), [
            'category_id' => $category->id,
            'parent_subcategory_id' => $parentSubcategory->id,
            'name' => 'Plasmodium falciparum',
            'sort_order' => 10,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('subcategories', [
            'category_id' => $category->id,
            'parent_subcategory_id' => $parentSubcategory->id,
            'name' => 'Plasmodium falciparum',
            'depth' => 2,
        ]);

        $this->assertDatabaseHas('lab_parameters', [
            'id' => $parameter->id,
            'is_active' => 0,
            'is_visible' => 0,
        ]);
    }

    public function test_analysis_can_receive_direct_value_again_after_all_children_removed(): void
    {
        $discipline = $this->makeDiscipline('biochimie-2');
        $category = $this->makeCategory($discipline, 'uricemie');
        $subcategory = $this->makeSubcategory($category, 'temp-sub', null, 1);

        $deleteResponse = $this->delete(route('catalog.subcategories.destroy', $subcategory));
        $deleteResponse->assertRedirect();

        $response = $this->post(route('catalog.parameters.store'), [
            'category_id' => $category->id,
            'name' => 'Uricemie',
            'value_type' => 'number',
            'unit' => 'mg/L',
            'reference' => '20 - 60',
            'sort_order' => 10,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('lab_parameters', [
            'category_id' => $category->id,
            'subcategory_id' => null,
            'name' => 'Uricemie',
            'is_active' => 1,
            'is_visible' => 1,
        ]);
    }

    private function makeDiscipline(string $seed): Discipline
    {
        return Discipline::query()->create([
            'code' => 'disc-'.$seed,
            'name' => 'Disc '.$seed,
            'labels' => ['fr' => 'Disc '.$seed],
            'sort_order' => 10,
            'is_active' => true,
        ]);
    }

    private function makeCategory(Discipline $discipline, string $seed): Category
    {
        return Category::query()->create([
            'discipline_id' => $discipline->id,
            'code' => 'cat-'.$seed,
            'name' => 'Cat '.$seed,
            'labels' => ['fr' => 'Cat '.$seed],
            'sort_order' => 10,
            'is_active' => true,
        ]);
    }

    private function makeSubcategory(Category $category, string $seed, ?Subcategory $parent, int $depth): Subcategory
    {
        return Subcategory::query()->create([
            'category_id' => $category->id,
            'parent_subcategory_id' => $parent?->id,
            'depth' => $depth,
            'code' => 'sub-'.$seed.'-'.$depth,
            'name' => 'Sub '.$seed,
            'labels' => ['fr' => 'Sub '.$seed],
            'sort_order' => 10,
            'is_active' => true,
        ]);
    }

    private function makeParameter(Discipline $discipline, Category $category, string $name, ?Subcategory $subcategory): LabParameter
    {
        return LabParameter::query()->create([
            'discipline_id' => $discipline->id,
            'category_id' => $category->id,
            'subcategory_id' => $subcategory?->id,
            'code' => 'param-'.strtolower(str_replace(' ', '-', $name)).'-'.($subcategory?->id ?? 'none'),
            'name' => $name,
            'labels' => ['fr' => $name],
            'unit' => 'u',
            'value_type' => 'number',
            'normal_min' => 1,
            'normal_max' => 2,
            'sort_order' => 10,
            'is_visible' => true,
            'is_active' => true,
        ]);
    }
}
