<?php

namespace Database\Seeders;

use App\Models\HelpingMethod;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class HelpingMethodSeeder extends Seeder
{
    public function run(): void
    {
        $sourceDir = public_path('helping_methods');
        $targetDir = 'helping_methods';

        if (! Storage::disk('public')->exists($targetDir)) {
            Storage::disk('public')->makeDirectory($targetDir);
        }

        $helpingMethods = HelpingMethod::defaults();

        foreach ($helpingMethods as $method) {
            $attributes = [
                'name' => $method['name'],
                'description' => $method['description'],
                'sort_order' => $method['sort_order'],
                'deleted_at' => null,
            ];

            $iconPath = $this->resolveIcon($sourceDir, $targetDir, $method['icon_file']);

            // Leave an admin-uploaded icon alone when the seeder ships none.
            if ($iconPath !== null) {
                $attributes['icon'] = $iconPath;
            }

            HelpingMethod::withTrashed()->updateOrCreate(['key' => $method['key']], $attributes);

            $this->command->info("Seeded helping method: {$method['name']} with icon: ".($iconPath ?? 'none'));
        }

        // Anything outside the current set is retired rather than deleted, so the
        // team_helping_methods rows that reference it still resolve to a name.
        $retired = HelpingMethod::query()
            ->whereNotIn('key', array_column($helpingMethods, 'key'))
            ->get();

        foreach ($retired as $method) {
            $method->delete();
            $this->command->info("Retired helping method: {$method->name}");
        }

        $this->command->info('Helping methods seeding completed!');
    }

    /**
     * Copy a bundled icon onto the public disk and return its stored path.
     */
    private function resolveIcon(string $sourceDir, string $targetDir, ?string $iconFile): ?string
    {
        if ($iconFile === null || ! File::exists($sourceDir.'/'.$iconFile)) {
            return null;
        }

        $targetPath = $targetDir.'/'.$iconFile;

        if (Storage::disk('public')->exists($targetPath)) {
            $this->command->info("Icon {$iconFile} already exists in storage, skipping copy.");
        } else {
            Storage::disk('public')->put($targetPath, File::get($sourceDir.'/'.$iconFile));
            $this->command->info("Copied icon {$iconFile} to storage.");
        }

        return $targetPath;
    }
}
