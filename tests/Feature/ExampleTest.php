<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_spa_routes_render_the_vue_shell(): void
    {
        $this->withoutVite();

        $this->get('/')->assertOk()->assertSee('id="app"', false);
        $this->get('/goals/5/edit')->assertOk()->assertSee('id="app"', false);
    }

    public function test_unknown_api_routes_return_json_not_found(): void
    {
        $this->getJson('/api/v1/unknown')->assertNotFound();
    }
}
