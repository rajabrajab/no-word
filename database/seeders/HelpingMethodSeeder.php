<?php

namespace Database\Seeders;

use App\Models\HelpingMethod;
use Illuminate\Database\Seeder;

class HelpingMethodSeeder extends Seeder
{
    public function run(): void
    {
        $helpingMethods = [
            [
                'name' => 'Hint',
                'description' => 'Get a helpful hint to guide you towards the answer without revealing it completely.',
                'icon' => null,
            ],
            [
                'name' => 'Skip Question',
                'description' => 'Skip the current question and move to the next one without losing points.',
                'icon' => null,
            ],
            [
                'name' => '50/50',
                'description' => 'Remove two incorrect options, leaving you with a 50% chance of selecting the correct answer.',
                'icon' => null,
            ],
        ];

        foreach ($helpingMethods as $method) {
            HelpingMethod::updateOrCreate(
                ['name' => $method['name']],
                $method
            );
        }
    }
}

