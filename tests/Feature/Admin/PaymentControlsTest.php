<?php

namespace Tests\Feature\Admin;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentControlsTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_access_payment_controls(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($superAdmin)
            ->get('/admin/billing/payment-controls')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/billing/payment-controls')
                ->where('payments_enabled', true)
                ->where('billing_mode', 'manual_renewal')
            );
    }

    public function test_admin_cannot_access_payment_controls(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/admin/billing/payment-controls')->assertForbidden();
    }

    public function test_super_admin_can_close_payments_with_custom_message(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($superAdmin)
            ->put('/admin/billing/payment-controls', [
                'payments_enabled' => false,
                'payments_disabled_message' => 'Maintenance in progress.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('0', Setting::get('payments_enabled'));
        $this->assertSame('Maintenance in progress.', Setting::get('payments_disabled_message'));
    }

    public function test_subscribe_blocked_when_payments_closed(): void
    {
        $this->seed(\Database\Seeders\PlanSeeder::class);

        Setting::set('payments_enabled', '0');
        Setting::set('payments_disabled_message', 'Payments are closed for now.');

        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/billing/subscribe', ['plan' => 'pro_monthly'])
            ->assertSessionHasErrors('plan');
    }

    public function test_pricing_page_shows_disabled_message_when_payments_closed(): void
    {
        $this->seed(\Database\Seeders\PlanSeeder::class);

        Setting::set('payments_enabled', '0');
        Setting::set('payments_disabled_message', 'Come back tomorrow.');

        $this->get('/pricing')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('payments_enabled', false)
                ->where('payments_disabled_message', 'Come back tomorrow.')
            );
    }
}
