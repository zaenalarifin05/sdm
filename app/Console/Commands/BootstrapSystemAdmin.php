<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class BootstrapSystemAdmin extends Command
{
    protected $signature = 'sdm:bootstrap-admin';
    protected $description = 'Create the first system administrator interactively';

    public function handle(): int
    {
        if (User::query()->where('role', UserRole::SystemAdmin->value)->exists()) {
            $this->error('System administrator already exists.');

            return self::FAILURE;
        }

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
            'role' => UserRole::SystemAdmin,
            'is_active' => true,
        ]);

        $this->info('System administrator created.');

        return self::SUCCESS;
    }
}
