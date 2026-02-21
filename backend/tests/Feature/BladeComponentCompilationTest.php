<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BladeComponentCompilationTest extends TestCase
{
    use RefreshDatabase;

    public function test_critical_pages_do_not_render_uncompiled_ui_component_tags(): void
    {
        $responses = [
            $this->get(route('analyses.create')),
            $this->get(route('analyses.index')),
            $this->get(route('catalog.index')),
            $this->get(route('settings.edit', ['section' => 'lab'])),
        ];

        foreach ($responses as $response) {
            $response->assertOk();
            $response->assertDontSee('<x-ui.', false);
            $response->assertDontSee('</x-ui.', false);
        }
    }
}
