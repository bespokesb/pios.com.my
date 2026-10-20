<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_renders_the_current_landing_design(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('PIOS BizGrowth Engine')
            ->assertSee('HOW PIOS WORKS');
    }

    public function test_v1_route_is_removed(): void
    {
        $this->get('/v1')->assertNotFound();
    }
}
