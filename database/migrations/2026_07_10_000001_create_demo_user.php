<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        User::updateOrCreate(
            ['email' => 'demo@chapel.test'],
            [
                'name' => 'demo',
                'role' => 'admin',
                'password' => Hash::make('demo'),
            ],
        );
    }

    public function down(): void
    {
        User::where('email', 'demo@chapel.test')->delete();
    }
};
