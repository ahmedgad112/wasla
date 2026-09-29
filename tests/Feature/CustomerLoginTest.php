<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerLoginTest extends TestCase
{
    use RefreshDatabase;

    private function customer(): User
    {
        return User::factory()->create([
            'email' => 'keep-me@example.com',
            'password' => 'password',
            'role' => 'CUSTOMER',
            'is_active' => true,
        ]);
    }

    public function test_remember_me_sets_a_long_lived_session_cookie(): void
    {
        $this->customer();

        $response = $this->post('/login', [
            'email' => 'keep-me@example.com',
            'password' => 'password',
            'remember' => true,
        ]);

        $response->assertRedirect(route('customer.dashboard'));
        $this->assertAuthenticated();

        $sessionCookie = collect($response->headers->getCookies())
            ->first(fn ($cookie) => $cookie->getName() === config('session.cookie'));

        $this->assertNotNull($sessionCookie);
        $this->assertGreaterThan(
            now()->addDays(20)->getTimestamp(),
            $sessionCookie->getExpiresTime(),
        );
        $this->assertNotEmpty(User::query()->where('email', 'keep-me@example.com')->value('remember_token'));
    }

    public function test_login_without_remember_does_not_extend_session_for_a_month(): void
    {
        $this->customer();

        $response = $this->post('/login', [
            'email' => 'keep-me@example.com',
            'password' => 'password',
            'remember' => false,
        ]);

        $response->assertRedirect(route('customer.dashboard'));
        $this->assertAuthenticated();

        $sessionCookie = collect($response->headers->getCookies())
            ->first(fn ($cookie) => $cookie->getName() === config('session.cookie'));

        $this->assertNotNull($sessionCookie);
        $this->assertLessThan(
            now()->addDays(2)->getTimestamp(),
            $sessionCookie->getExpiresTime(),
        );
    }
}
