<?php

namespace Database\Seeders;

use App\Models\PlayerAvatar;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class PlayerAvatarSeeder extends Seeder
{
    public function run(): void
    {
        $sourceDir = public_path('avatars');
        $targetDir = 'player_avatars';

        if (!Storage::disk('public')->exists($targetDir)) {
            Storage::disk('public')->makeDirectory($targetDir);
        }

        if (!File::exists($sourceDir)) {
            $this->command->warn("Source directory {$sourceDir} does not exist.");
            return;
        }

        $avatarFiles = File::files($sourceDir);

        if (empty($avatarFiles)) {
            $this->command->warn("No avatar files found in {$sourceDir}.");
            return;
        }

        foreach ($avatarFiles as $file) {
            $filename = $file->getFilename();
            $targetPath = $targetDir . '/' . $filename;

            if (Storage::disk('public')->exists($targetPath)) {
                $this->command->info("File {$filename} already exists in storage, skipping copy.");
            } else {
                $fileContents = File::get($file->getPathname());
                Storage::disk('public')->put($targetPath, $fileContents);
                $this->command->info("Copied {$filename} to storage.");
            }

            PlayerAvatar::updateOrCreate(
                ['avatar_path' => $targetPath],
                ['avatar_path' => $targetPath]
            );

            $this->command->info("Seeded avatar: {$targetPath}");
        }

        $this->command->info("Player avatars seeding completed!");
    }
}

