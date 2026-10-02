<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationRbacTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_user_can_login(): void
    {
        $user = User::create([
            'name' => 'HR Admin',
            'email' => 'hr@example.test',
            'password' => 'Secret123!',
            'role' => UserRole::HrAdmin,
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'email' => 'hr@example.test',
            'password' => 'Secret123!',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_inactive_user_cannot_login(): void
    {
        User::create([
            'name' => 'Inactive',
            'email' => 'inactive@example.test',
            'password' => 'Secret123!',
            'role' => UserRole::HrAdmin,
            'is_active' => false,
        ]);

        $this->post('/login', [
            'email' => 'inactive@example.test',
            'password' => 'Secret123!',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_employee_role_cannot_access_hr_master_data(): void
    {
        $employee = User::create([
            'name' => 'Employee',
            'email' => 'employee@example.test',
            'password' => 'Secret123!',
            'role' => UserRole::Employee,
            'is_active' => true,
        ]);

        $this->actingAs($employee)->get('/hr/departments')->assertForbidden();
    }

    public function test_hr_admin_can_access_hr_master_data(): void
    {
        $hr = User::create([
            'name' => 'HR Admin',
            'email' => 'hr@example.test',
            'password' => 'Secret123!',
            'role' => UserRole::HrAdmin,
            'is_active' => true,
        ]);

        $this->actingAs($hr)->get('/hr/departments')->assertOk();
    }
}
