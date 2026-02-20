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

    public function test_numeric_reference_accepts_comma_decimal_values(): void
    {
        $discipline = $this->makeDiscipline('bio-dec');
        $category = $this->makeCategory($discipline, 'glycemie-dec');

        $response = $this->post(route('catalog.parameters.store'), [
            'category_id' => $category->id,
            'name' => 'Glycemie',
            'value_type' => 'number',
            'unit' => 'g/L',
            'reference' => '0,70 - 1,10',
            'sort_order' => 10,
        ]);

        $response->assertRedirect();

        $parameter = LabParameter::query()
            ->where('category_id', $category->id)
            ->where('name', 'Glycemie')
            ->firstOrFail();

        $this->assertSame(0.7, (float) $parameter->normal_min);
        $this->assertSame(1.1, (float) $parameter->normal_max);
        $this->assertNull($parameter->normal_text);
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
            'force_convert_parent' => 1,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('subcategories', [
            'category_id' => $category->id,
            'name' => 'Hemoglobine',
        ]);

        $this->assertDatabaseMissing('lab_parameters', [
            'id' => $parameter->id,
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
            'force_convert_parent' => 1,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('subcategories', [
            'category_id' => $category->id,
            'parent_subcategory_id' => $parentSubcategory->id,
            'name' => 'Plasmodium falciparum',
            'depth' => 2,
        ]);

        $this->assertDatabaseMissing('lab_parameters', [
            'id' => $parameter->id,
        ]);
    }

    public function test_adding_subanalysis_requires_confirmation_when_parent_has_values(): void
    {
        $discipline = $this->makeDiscipline('hema-confirm');
        $category = $this->makeCategory($discipline, 'nfs-confirm');
        $this->makeParameter(discipline: $discipline, category: $category, name: 'NFS', subcategory: null);

        $response = $this->post(route('catalog.subcategories.store'), [
            'category_id' => $category->id,
            'name' => 'Plaquettes',
        ]);

        $response->assertSessionHasErrors('parent_subcategory_id');
        $this->assertDatabaseCount('subcategories', 0);
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

    public function test_subcategory_delete_with_dependents_requires_force_flag(): void
    {
        $discipline = $this->makeDiscipline('delete-guard');
        $category = $this->makeCategory($discipline, 'delete-guard-cat');
        $parent = $this->makeSubcategory($category, 'parent', null, 1);
        $this->makeSubcategory($category, 'child', $parent, 2);
        $this->makeParameter($discipline, $category, 'Parent Param', $parent);

        $response = $this->delete(route('catalog.subcategories.destroy', $parent));

        $response->assertSessionHasErrors('catalog');
        $this->assertDatabaseHas('subcategories', ['id' => $parent->id]);
    }

    public function test_subcategory_delete_with_dependents_can_be_forced(): void
    {
        $discipline = $this->makeDiscipline('delete-force');
        $category = $this->makeCategory($discipline, 'delete-force-cat');
        $parent = $this->makeSubcategory($category, 'parent', null, 1);
        $child = $this->makeSubcategory($category, 'child', $parent, 2);
        $parentParam = $this->makeParameter($discipline, $category, 'Parent Param', $parent);
        $childParam = $this->makeParameter($discipline, $category, 'Child Param', $child);

        $response = $this->delete(route('catalog.subcategories.destroy', $parent), [
            'force' => '1',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseMissing('subcategories', ['id' => $parent->id]);
        $this->assertDatabaseMissing('subcategories', ['id' => $child->id]);
        $this->assertDatabaseMissing('lab_parameters', ['id' => $parentParam->id]);
        $this->assertDatabaseMissing('lab_parameters', ['id' => $childParam->id]);
    }

    public function test_list_parameter_accepts_matching_default_option(): void
    {
        $discipline = $this->makeDiscipline('micro');
        $category = $this->makeCategory($discipline, 'uroculture');

        $response = $this->post(route('catalog.parameters.store'), [
            'category_id' => $category->id,
            'name' => 'Culture',
            'value_type' => 'list',
            'options_csv' => 'NEGATIF, POSITIF',
            'default_option_value' => 'NEGATIF',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('lab_parameters', [
            'category_id' => $category->id,
            'subcategory_id' => null,
            'name' => 'Culture',
            'value_type' => 'list',
            'default_value' => 'NEGATIF',
        ]);
    }

    public function test_list_parameter_rejects_default_option_outside_choices(): void
    {
        $discipline = $this->makeDiscipline('micro-2');
        $category = $this->makeCategory($discipline, 'coproculture');

        $response = $this->post(route('catalog.parameters.store'), [
            'category_id' => $category->id,
            'name' => 'Culture',
            'value_type' => 'list',
            'options_csv' => 'NEGATIF, POSITIF',
            'default_option_value' => 'INCONNU',
        ]);

        $response->assertSessionHasErrors('default_option_value');
    }

    public function test_categories_can_be_reordered_inside_same_discipline(): void
    {
        $discipline = $this->makeDiscipline('reorder-cat');
        $first = $this->makeCategory($discipline, 'alpha');
        $second = $this->makeCategory($discipline, 'beta');
        $third = $this->makeCategory($discipline, 'gamma');

        $this->postJson(route('catalog.reorder'), [
            'type' => 'category',
            'discipline_id' => $discipline->id,
            'ordered_ids' => [$third->id, $first->id, $second->id],
        ])->assertOk();

        $this->assertSame(10, (int) Category::query()->findOrFail($third->id)->sort_order);
        $this->assertSame(20, (int) Category::query()->findOrFail($first->id)->sort_order);
        $this->assertSame(30, (int) Category::query()->findOrFail($second->id)->sort_order);
    }

    public function test_disciplines_can_be_reordered_globally(): void
    {
        $first = $this->makeDiscipline('reorder-disc-a');
        $second = $this->makeDiscipline('reorder-disc-b');
        $third = $this->makeDiscipline('reorder-disc-c');

        $this->postJson(route('catalog.reorder'), [
            'type' => 'discipline',
            'ordered_ids' => [$third->id, $first->id, $second->id],
        ])->assertOk();

        $this->assertSame(10, (int) Discipline::query()->findOrFail($third->id)->sort_order);
        $this->assertSame(20, (int) Discipline::query()->findOrFail($first->id)->sort_order);
        $this->assertSame(30, (int) Discipline::query()->findOrFail($second->id)->sort_order);
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
