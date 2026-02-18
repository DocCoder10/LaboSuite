<?php

namespace Tests\Feature;

use App\Models\Category;
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
}
