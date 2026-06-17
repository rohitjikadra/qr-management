<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BannedUserTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlanSeeder::class);
    }

    public function test_banned_user_cannot_login(): void
    {
        $user = User::factory()->create(['status' => 'banned']);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
        $response->assertSessionHasErrors([
            'email' => 'Your account has been suspended. Please contact support if you believe this is a mistake.',
        ]);
    }

    public function test_banned_user_is_redirected_from_dashboard_to_suspended_page(): void
    {
        $user = User::factory()->create(['status' => 'banned']);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertRedirect(route('account.suspended'));

        $this->assertGuest();
    }

    public function test_account_suspended_page_is_accessible_to_guests(): void
    {
        $this->get('/account-suspended')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('auth/account-suspended'));
    }

    public function test_active_user_can_still_login_and_use_dashboard(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);

        $this->get('/dashboard')->assertOk();
    }
}
