<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/consumer/register', [
            'full_name' => 'Test User',
            'email' => 'test.user@gmail.com',
            'phone_number' => '09171234567',
            'address' => 'Test Address',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect(route('consumer.verify.form', absolute: false));
        $response->assertSessionHas('consumer_verification_user_id');
        $this->assertGuest();

        $this->assertDatabaseHas('users', [
            'email' => 'test.user@gmail.com',
            'role' => 'consumer',
        ]);
    }
}
