<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use App\Models\SuspiciousActivity;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $this->user = User::factory()->create(['role' => UserRole::USER]);
    }

    public function test_admin_can_list_users()
    {
        User::factory()->count(5)->create();

        $response = $this->actingAs($this->admin)->getJson('/api/v1/admin/users');

        $response->assertStatus(200)
                 ->assertJsonStructure(['data']);
    }

    public function test_admin_can_list_transactions()
    {
        $response = $this->actingAs($this->admin)->getJson('/api/v1/admin/transactions');

        $response->assertStatus(200)
                 ->assertJsonStructure(['data']);
    }

    public function test_admin_can_list_suspicious_activities()
    {
        SuspiciousActivity::create([
            'user_id' => $this->user->id,
            'rule_type' => 'test_rule',
            'severity' => 'high',
            'status' => 'pending',
            'risk_score' => 80
        ]);

        $response = $this->actingAs($this->admin)->getJson('/api/v1/admin/suspicious-activities');

        $response->assertStatus(200)
                 ->assertJsonCount(1, 'data');
    }

    public function test_admin_can_resolve_suspicious_activity()
    {
        $activity = SuspiciousActivity::create([
            'user_id' => $this->user->id,
            'rule_type' => 'test_rule',
            'severity' => 'high',
            'status' => 'pending',
            'risk_score' => 80
        ]);

        $response = $this->actingAs($this->admin)->postJson(
            '/api/v1/admin/suspicious-activities/' . $activity->id . '/resolve',
            [
                'status' => 'resolved',
                'admin_note' => 'Reviewed and resolved'
            ]
        );

        $response->assertStatus(200);
        
        $this->assertDatabaseHas('suspicious_activities', [
            'id' => $activity->id,
            'status' => 'resolved'
        ]);
    }

    public function test_admin_can_mark_as_false_positive()
    {
        $activity = SuspiciousActivity::create([
            'user_id' => $this->user->id,
            'rule_type' => 'test_rule',
            'severity' => 'high',
            'status' => 'pending',
            'risk_score' => 80
        ]);

        $response = $this->actingAs($this->admin)->postJson(
            '/api/v1/admin/suspicious-activities/' . $activity->id . '/resolve',
            [
                'status' => 'false_positive',
                'admin_note' => 'False alarm'
            ]
        );

        $response->assertStatus(200);
        
        $this->assertDatabaseHas('suspicious_activities', [
            'id' => $activity->id,
            'status' => 'false_positive'
        ]);
    }

    public function test_regular_user_cannot_access_admin_users()
    {
        $response = $this->actingAs($this->user)->getJson('/api/v1/admin/users');

        $response->assertStatus(403);
    }

    public function test_regular_user_cannot_access_admin_transactions()
    {
        $response = $this->actingAs($this->user)->getJson('/api/v1/admin/transactions');

        $response->assertStatus(403);
    }

    public function test_regular_user_cannot_resolve_suspicious_activity()
    {
        $activity = SuspiciousActivity::create([
            'user_id' => $this->user->id,
            'rule_type' => 'test_rule',
            'severity' => 'high',
            'status' => 'pending',
            'risk_score' => 80
        ]);

        $response = $this->actingAs($this->user)->postJson(
            '/api/v1/admin/suspicious-activities/' . $activity->id . '/resolve',
            ['status' => 'resolved']
        );

        $response->assertStatus(403);
    }

    public function test_unauthenticated_cannot_access_admin()
    {
        $response = $this->getJson('/api/v1/admin/users');

        $response->assertStatus(401);
    }
}
