<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSubscriptionsLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscriptions_page_renders_custom_admin_layout(): void
    {
        $this->seed(PlanSeeder::class);

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get('/admin/subscriptions')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/subscriptions/index')
                ->has('subscriptions')
            );
    }
}
