<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CustomerRegisterTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, string>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'أحمد محمد',
            'email' => 'ahmed-register@example.com',
            'phone' => '01012345678',
            'password' => 'Strong1@',
            'password_confirmation' => 'Strong1@',
        ], $overrides);
    }

    public function test_rejects_password_shorter_than_eight_characters(): void
    {
        $response = $this->from('/register')->post('/register', $this->validPayload([
            'password' => 'Ab1@x',
            'password_confirmation' => 'Ab1@x',
        ]));

        $response->assertRedirect('/register');
        $response->assertSessionHasErrors('password');
        $this->assertDatabaseMissing('users', ['email' => 'ahmed-register@example.com']);
    }

    public function test_rejects_password_without_uppercase_letter(): void
    {
        $response = $this->from('/register')->post('/register', $this->validPayload([
            'password' => 'strong1@',
            'password_confirmation' => 'strong1@',
        ]));

        $response->assertRedirect('/register');
        $response->assertSessionHasErrors('password');
    }

    public function test_rejects_password_without_a_number(): void
    {
        $response = $this->from('/register')->post('/register', $this->validPayload([
            'password' => 'Strong@@',
            'password_confirmation' => 'Strong@@',
        ]));

        $response->assertRedirect('/register');
        $response->assertSessionHasErrors('password');
    }

    public function test_rejects_password_without_a_symbol(): void
    {
        $response = $this->from('/register')->post('/register', $this->validPayload([
            'password' => 'Strong12',
            'password_confirmation' => 'Strong12',
        ]));

        $response->assertRedirect('/register');
        $response->assertSessionHasErrors('password');
    }

    public function test_rejects_mismatched_password_confirmation(): void
    {
        $response = $this->from('/register')->post('/register', $this->validPayload([
            'password_confirmation' => 'Strong2@',
        ]));

        $response->assertRedirect('/register');
        $response->assertSessionHasErrors('password');
    }

    public function test_creates_customer_account_when_password_meets_rules(): void
    {
        Role::findOrCreate('CUSTOMER', 'web');

        $response = $this->post('/register', $this->validPayload());

        $response->assertRedirect(route('customer.dashboard'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'ahmed-register@example.com',
            'role' => 'CUSTOMER',
        ]);

        $user = User::query()->where('email', 'ahmed-register@example.com')->first();
        $this->assertNotNull($user);
        $this->assertDatabaseHas('customers', ['user_id' => $user->id]);
    }
}
