<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Exercise;

class ExerciseSeeder extends Seeder
{
  public function run(): void
  {
    $items = [
      ['name' => 'Bench Press', 'muscle_group' => 'Chest'],
      ['name' => 'Incline Dumbbell Press', 'muscle_group' => 'Chest'],
      ['name' => 'Chest Flyes', 'muscle_group' => 'Chest'],
      ['name' => 'Deadlift', 'muscle_group' => 'Back'],
      ['name' => 'Pull-ups', 'muscle_group' => 'Back'],
      ['name' => 'Barbell Rows', 'muscle_group' => 'Back'],
      ['name' => 'Squats', 'muscle_group' => 'Legs'],
      ['name' => 'Leg Press', 'muscle_group' => 'Legs'],
      ['name' => 'Lunges', 'muscle_group' => 'Glutes'],
      ['name' => 'Dumbbell Curls', 'muscle_group' => 'Biceps'],
      ['name' => 'Triceps Pushdown', 'muscle_group' => 'Triceps'],
    ];

    foreach ($items as $it) {
      Exercise::firstOrCreate(['name' => $it['name']], $it);
    }
  }
}