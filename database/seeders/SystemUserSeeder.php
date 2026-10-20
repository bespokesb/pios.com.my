<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SystemUserSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->firstOrCreate(
            ['email' => 'system@pios.com.my'],
            [
                'name' => 'PIOS System',
                'password' => Hash::make(Str::random(40)),
                'role' => UserRole::SysAdmin,
                'status' => UserStatus::Inactive,
            ],
        );
    }
}
