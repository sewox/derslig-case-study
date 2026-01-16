<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\PreventDuplicateRequests;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PreventDuplicateRequestsTest extends TestCase
{
    use RefreshDatabase;

    protected $middleware;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new PreventDuplicateRequests();
        $this->user = User::factory()->create();
    }

    public function test_allows_first_request()
    {
        $request = Request::create('/api/v1/transactions/transfer', 'POST', [
            'amount' => 100,
            'currency' => 'TRY',
            'target_user_email' => 'test@example.com'
        ]);
        $request->setUserResolver(fn() => $this->user);

        $response = $this->middleware->handle($request, fn($req) => response()->json(['success' => true]));

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_blocks_duplicate_request_within_window()
    {
        $requestData = [
            'amount' => 100,
            'currency' => 'TRY',
            'target_user_email' => 'test@example.com'
        ];

        $request1 = Request::create('/api/v1/transactions/transfer', 'POST', $requestData);
        $request1->setUserResolver(fn() => $this->user);

        // First request passes
        $response1 = $this->middleware->handle($request1, fn($req) => response()->json(['success' => true]));
        $this->assertEquals(200, $response1->getStatusCode());

        // Second identical request should be blocked
        $request2 = Request::create('/api/v1/transactions/transfer', 'POST', $requestData);
        $request2->setUserResolver(fn() => $this->user);

        $response2 = $this->middleware->handle($request2, fn($req) => response()->json(['success' => true]));
        $this->assertEquals(429, $response2->getStatusCode());
    }

    public function test_allows_different_users_same_request()
    {
        // Clear cache first
        Cache::flush();

        $user2 = User::factory()->create();

        $request1 = Request::create('/api/v1/transactions/transfer', 'POST', [
            'amount' => 100,
            'currency' => 'TRY',
            'target_user_email' => 'user1@example.com'
        ]);
        $request1->setUserResolver(fn() => $this->user);

        // Same request but different user - should pass
        $request2 = Request::create('/api/v1/transactions/transfer', 'POST', [
            'amount' => 100,
            'currency' => 'TRY',
            'target_user_email' => 'user1@example.com'
        ]);
        $request2->setUserResolver(fn() => $user2);

        // First should pass
        $response1 = $this->middleware->handle($request1, fn($req) => response()->json(['success' => true]));
        $this->assertEquals(200, $response1->getStatusCode());

        // Second from different user should also pass
        $response2 = $this->middleware->handle($request2, fn($req) => response()->json(['success' => true]));
        $this->assertEquals(200, $response2->getStatusCode());
    }
}
