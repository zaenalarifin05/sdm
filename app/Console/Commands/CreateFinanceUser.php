<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateFinanceUser extends Command
{
    protected $signature = 'sdm:create-finance-user';
    protected $description = 'Create a Finance user interactively';

    public function handle(): int
    {
        $data = [
            'name' => trim((string) $this->ask('Nama')),
            'email' => trim((string) $this->ask('Email')),
            'password' => (string) $this->secret('Password'),
        ];

        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:12'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => UserRole::Finance,
            'is_active' => true,
        ]);

        $this->info('Finance user created.');

        return self::SUCCESS;
    }
}
