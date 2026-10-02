<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateFinanceUserCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_finance_user_can_be_created_interactively(): void
    {
        $this->artisan('sdm:create-finance-user')
            ->expectsQuestion('Nama', 'Finance User')
            ->expectsQuestion('Email', 'finance@example.test')
            ->expectsQuestion('Password', 'VerySecret123!')
            ->expectsOutput('Finance user created.')
            ->assertSuccessful();

        $finance = User::where('email', 'finance@example.test')->firstOrFail();

        $this->assertSame(UserRole::Finance, $finance->role);
        $this->assertTrue(Hash::check('VerySecret123!', $finance->password));
    }
}
