<?php

namespace Tests\Feature;

use App\Filament\Resources\HelpingMethods\Pages\CreateHelpingMethod;
use App\Filament\Resources\HelpingMethods\Pages\EditHelpingMethod;
use App\Models\HelpingMethod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use League\Flysystem\UnableToWriteFile;
use Livewire\Livewire;
use Symfony\Component\Uid\Ulid;
use Tests\TestCase;

class HelpingMethodIconUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->actingAs(User::factory()->create());
    }

    public function test_an_icon_uploaded_while_creating_is_stored(): void
    {
        Livewire::test(CreateHelpingMethod::class)
            ->fillForm([
                'name' => 'Skip question',
                'icon' => UploadedFile::fake()->image('skip.png'),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $icon = HelpingMethod::query()->where('name', 'Skip question')->value('icon');

        $this->assertStringStartsWith('helping_methods/', $icon);
        Storage::disk('public')->assertExists($icon);
    }

    public function test_an_icon_uploaded_while_editing_replaces_the_old_one(): void
    {
        Storage::disk('public')->put('helping_methods/extra_time.png', 'old-png');

        $method = HelpingMethod::query()->where('key', HelpingMethod::EXTRA_TIME)->firstOrFail();
        $method->update(['icon' => 'helping_methods/extra_time.png']);

        $component = Livewire::test(EditHelpingMethod::class, ['record' => $method->getRouteKey()]);

        // Mirror the browser: the old icon is removed before the new one is uploaded.
        $component->call('callSchemaComponentMethod', 'form.icon', 'deleteUploadedFile', [
            'fileKey' => array_key_first($component->get('data.icon')),
        ]);

        $component
            ->fillForm([
                'icon' => UploadedFile::fake()->image('extra.png'),
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $icon = $method->refresh()->icon;

        $this->assertNotSame('helping_methods/extra_time.png', $icon);
        $this->assertStringStartsWith('helping_methods/', $icon);
        Storage::disk('public')->assertExists($icon);
    }

    public function test_an_icon_that_cannot_be_written_is_not_saved_as_a_broken_path(): void
    {
        Str::freezeUlids(function (Ulid $ulid): void {
            // A directory at the target filename makes the write fail, as an unwritable folder would.
            Storage::disk('public')->makeDirectory("helping_methods/{$ulid}.png");

            $this->assertThrows(
                fn () => Livewire::test(CreateHelpingMethod::class)
                    ->fillForm([
                        'name' => 'Skip question',
                        'icon' => UploadedFile::fake()->image('skip.png'),
                    ])
                    ->call('create'),
                UnableToWriteFile::class,
            );
        });

        $this->assertDatabaseMissing(HelpingMethod::class, ['name' => 'Skip question']);
    }
}
