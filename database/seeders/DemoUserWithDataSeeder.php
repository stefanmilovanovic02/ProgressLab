<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;

use App\Models\User;
use App\Models\NutritionEntry;

class DemoUserWithDataSeeder extends Seeder
{
    public function run(): void
    {
        // ✅ Login credentials for your demo user:
        $email = 'demo@gymtracker.test';
        $password = 'Demo12345!'; // you will use this to login

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Demo User',
                'full_name' => 'Demo User',
                'username' => 'demo_user',
                'password' => Hash::make($password),
                // add these only if your users table has them:
                'gender' => 'male',
                'location' => 'Belgrade, Serbia',
                'date_of_birth' => '2000-01-01',
                'remember_token' => Str::random(10),
            ]
        );

        // Seed last 120 days (change to 365 if you want)
        $days = 365;

        for ($i = 0; $i < $days; $i++) {
            $date = Carbon::today()->subDays($i)->toDateString();

            NutritionEntry::updateOrCreate(
                ['user_id' => $user->id, 'entry_date' => $date],
                [
                    'calories' => rand(1700, 3200),
                    'protein_g' => rand(90, 220),
                    'carbs_g' => rand(120, 420),
                    'fat_g' => rand(40, 120),
                    'creatine_g' => rand(0, 5),
                    'water_ml' => rand(1500, 4500),
                ]
            );
        }
    }
}