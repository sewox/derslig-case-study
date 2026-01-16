<?php

namespace Tests\Feature\Api\V1;

use App\Models\Configuration;
use App\Models\User;
use App\Models\SuspiciousActivity;
use App\Enums\WalletCurrency;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $regularUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $this->regularUser = User::factory()->create(['role' => UserRole::USER]);
        
        // Create wallet for regular user
        $this->regularUser->wallets()->create([
            'currency' => WalletCurrency::TRY,
            'balance' => 1000.00,
            'status' => 'active'
        ]);
    }

    public function test_admin_can_list_all_users()
    {
        $response = $this->actingAs($this->admin)->getJson('/api/v1/admin/users');

        $response->assertStatus(200)
                 ->assertJsonStructure(['data']);
    }

    public function test_admin_can_list_all_transactions()
    {
        $response = $this->actingAs($this->admin)->getJson('/api/v1/admin/transactions');

        $response->assertStatus(200)
                 ->assertJsonStructure(['data']);
    }

    public function test_admin_can_list_suspicious_activities()
    {
        // Create a suspicious activity
        SuspiciousActivity::create([
            'user_id' => $this->regularUser->id,
            'rule_type' => 'test_rule',
            'severity' => 'high',
            'status' => 'pending',
            'details' => ['message' => 'Test suspicious activity'],
            'risk_score' => 80
        ]);

        $response = $this->actingAs($this->admin)->getJson('/api/v1/admin/suspicious-activities');

        $response->assertStatus(200)
                 ->assertJsonCount(1, 'data');
    }

    public function test_admin_can_resolve_suspicious_activity()
    {
        $activity = SuspiciousActivity::create([
            'user_id' => $this->regularUser->id,
            'rule_type' => 'test_rule',
            'severity' => 'high',
            'status' => 'pending',
            'details' => ['message' => 'Test'],
            'risk_score' => 80
        ]);

        $response = $this->actingAs($this->admin)->postJson('/api/v1/admin/suspicious-activities/' . $activity->id . '/resolve', [
            'status' => 'resolved',
            'admin_note' => 'Checked and resolved by admin'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('suspicious_activities', [
            'id' => $activity->id,
            'status' => 'resolved',
            'admin_note' => 'Checked and resolved by admin'
        ]);
    }

    public function test_regular_user_cannot_access_admin_endpoints()
    {
        $response = $this->actingAs($this->regularUser)->getJson('/api/v1/admin/users');

        // Should return 403 Forbidden
        $response->assertStatus(403);
    }
}
