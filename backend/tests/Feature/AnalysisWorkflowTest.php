<?php

namespace Tests\Feature;

use App\Models\AnalysisResult;
use App\Models\Category;
use App\Models\Discipline;
use App\Models\LabParameter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalysisWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_selection_step_redirects_to_results_screen(): void
    {
        $this->seed();
        $category = Category::query()->firstOrFail();

        $response = $this->post(route('analyses.selection.store'), [
            'patient' => [
                'identifier' => 'P-0001',
                'first_name' => 'John',
                'last_name' => 'Doe',
                'sex' => 'male',
                'age' => 30,
                'phone' => '555-1010',
            ],
            'analysis_date' => now()->toDateString(),
            'selected_categories' => [$category->id],
        ]);

        $response
            ->assertRedirect(route('analyses.results'))
            ->assertSessionHas('analysis_draft');

        $this->get(route('analyses.results'))->assertOk();
    }

    public function test_results_screen_requires_existing_selection_draft(): void
    {
        $this->get(route('analyses.results'))
            ->assertRedirect(route('analyses.create'));
    }

    public function test_numeric_result_with_comma_decimal_is_used_for_high_low_detection(): void
    {
        $discipline = Discipline::query()->create([
            'code' => 'disc-dec',
            'name' => 'Disc Decimal',
            'labels' => ['fr' => 'Disc Decimal'],
            'sort_order' => 10,
            'is_active' => true,
        ]);

        $category = Category::query()->create([
            'discipline_id' => $discipline->id,
            'code' => 'cat-dec',
            'name' => 'Analyse Decimal',
            'labels' => ['fr' => 'Analyse Decimal'],
            'sort_order' => 10,
            'is_active' => true,
        ]);

        $parameter = LabParameter::query()->create([
            'discipline_id' => $discipline->id,
            'category_id' => $category->id,
            'subcategory_id' => null,
            'code' => 'param-dec',
            'name' => 'Glycemie',
            'labels' => ['fr' => 'Glycemie'],
            'value_type' => 'number',
            'normal_min' => 0.70,
            'normal_max' => 1.10,
            'unit' => 'g/L',
            'sort_order' => 10,
            'is_visible' => true,
            'is_active' => true,
        ]);

        $response = $this
            ->withSession([
                'analysis_draft' => [
                    'patient' => [
                        'identifier' => 'P-DEC-1',
                        'first_name' => 'Jean',
                        'last_name' => 'Test',
                        'sex' => 'male',
                        'age' => 30,
                        'phone' => '70000000',
                    ],
                    'analysis_date' => now()->toDateString(),
                    'selected_categories' => [$category->id],
                ],
            ])
            ->post(route('analyses.store'), [
                'results' => [
                    $parameter->id => '1,35',
                ],
                'notes' => '',
            ]);

        $response->assertRedirect();

        $result = AnalysisResult::query()
            ->where('lab_parameter_id', $parameter->id)
            ->firstOrFail();

        $this->assertSame(1.35, (float) $result->result_numeric);
        $this->assertTrue((bool) $result->is_abnormal);
    }
}
