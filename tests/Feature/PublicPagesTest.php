<?php

namespace Tests\Feature;

use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlanSeeder::class);
    }

    public function test_home_page_loads_with_plans(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('welcome')
                ->has('plans', 3)
            );
    }

    public function test_legal_pages_load(): void
    {
        $this->get('/terms')->assertOk()->assertInertia(fn ($p) => $p->component('legal/terms'));
        $this->get('/privacy')->assertOk()->assertInertia(fn ($p) => $p->component('legal/privacy'));
        $this->get('/refund-policy')->assertOk()->assertInertia(fn ($p) => $p->component('legal/refund'));
    }

    public function test_robots_txt_is_available(): void
    {
        $this->get('/robots.txt')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertSee('Disallow: /admin')
            ->assertSee('Sitemap:');
    }

    public function test_sitemap_xml_is_available(): void
    {
        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml')
            ->assertSee('urlset', false);
    }

    public function test_custom_404_page_renders(): void
    {
        $this->get('/this-page-does-not-exist')
            ->assertNotFound()
            ->assertSee('404')
            ->assertSee('Page not found');
    }
}
