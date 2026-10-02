<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BootstrapSystemAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_system_admin_can_be_bootstrapped_interactively(): void
    {
        $this->artisan('sdm:bootstrap-admin')
            ->expectsQuestion('Nama', 'System Admin')
            ->expectsQuestion('Email', 'admin@example.test')
            ->expectsQuestion('Password', 'VerySecret123!')
            ->expectsOutput('System administrator created.')
            ->assertSuccessful();

        $admin = User::where('email', 'admin@example.test')->firstOrFail();

        $this->assertSame(UserRole::SystemAdmin, $admin->role);
        $this->assertTrue(Hash::check('VerySecret123!', $admin->password));
    }

    public function test_second_system_admin_bootstrap_is_blocked(): void
    {
        User::create([
            'name' => 'Existing Admin',
            'email' => 'existing@example.test',
            'password' => 'VerySecret123!',
            'role' => UserRole::SystemAdmin,
            'is_active' => true,
        ]);

        $this->artisan('sdm:bootstrap-admin')
            ->expectsOutput('System administrator already exists.')
            ->assertFailed();

        $this->assertDatabaseCount('users', 1);
    }
}
