<?php

namespace Tests\Feature;

use App\Filament\Resources\Questions\Schemas\QuestionForm;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\FileUploadConfiguration;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class QuestionMediaTypeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_an_m4a_upload_is_stored_as_audio_even_though_it_sniffs_as_video(): void
    {
        $file = $this->makeTemporaryUpload('voice-note.m4a', $this->isoBaseMediaFile('isom'));

        // Guard the regression: Livewire sniffs the bytes only, and an MPEG-4 audio
        // container written by ffmpeg or an Android recorder is indistinguishable
        // from video at the header, so the MIME type alone says "video".
        $this->assertSame('video/mp4', $file->getMimeType());
        $this->assertSame('audio', QuestionForm::resolveMediaType($file));
    }

    public function test_an_m4a_upload_with_an_apple_brand_is_also_stored_as_audio(): void
    {
        $file = $this->makeTemporaryUpload('voice-note.m4a', $this->isoBaseMediaFile('M4A '));

        $this->assertSame('audio', QuestionForm::resolveMediaType($file));
    }

    public function test_a_real_mp4_upload_is_still_stored_as_video(): void
    {
        $file = $this->makeTemporaryUpload('clip.mp4', $this->isoBaseMediaFile('isom'));

        $this->assertSame('video', QuestionForm::resolveMediaType($file));
    }

    public function test_an_image_upload_is_stored_as_image(): void
    {
        $file = $this->makeTemporaryUpload('picture.png', $this->pngBytes());

        $this->assertSame('image', QuestionForm::resolveMediaType($file));
    }

    /**
     * @return array<string, array{0: string, 1: ?string}>
     */
    public static function storedPathProvider(): array
    {
        return [
            'm4a' => ['questions/01JABCD.m4a', 'audio'],
            'mp3' => ['questions/01JABCD.mp3', 'audio'],
            'ogg' => ['questions/01JABCD.ogg', 'audio'],
            'uppercase m4a' => ['questions/01JABCD.M4A', 'audio'],
            'mp4' => ['questions/01JABCD.mp4', 'video'],
            'mov' => ['questions/01JABCD.mov', 'video'],
            'png' => ['questions/01JABCD.png', 'image'],
            'webp' => ['questions/01JABCD.webp', 'image'],
            'unknown extension, missing file' => ['questions/01JABCD.xyz', null],
        ];
    }

    /**
     * An already-saved row holds a path, not an upload, and must resolve the same way.
     */
    #[DataProvider('storedPathProvider')]
    public function test_a_stored_path_resolves_by_its_extension(string $path, ?string $expected): void
    {
        $this->assertSame($expected, QuestionForm::resolveMediaType($path));
    }

    public function test_empty_state_resolves_to_null(): void
    {
        $this->assertNull(QuestionForm::resolveMediaType(null));
        $this->assertNull(QuestionForm::resolveMediaType(''));
    }

    private function makeTemporaryUpload(string $originalName, string $contents): TemporaryUploadedFile
    {
        $storedName = TemporaryUploadedFile::generateHashNameWithOriginalNameEmbedded(
            UploadedFile::fake()->create($originalName)
        );

        FileUploadConfiguration::storage()->put(
            FileUploadConfiguration::path($storedName),
            $contents
        );

        return TemporaryUploadedFile::createFromLivewire($storedName);
    }

    /**
     * A minimal ISO base media file header: box length, "ftyp", brand, minor version.
     */
    private function isoBaseMediaFile(string $brand): string
    {
        $body = 'ftyp'.$brand."\x00\x00\x00\x00".$brand;

        return pack('N', strlen($body) + 4).$body;
    }

    private function pngBytes(): string
    {
        $image = imagecreatetruecolor(4, 4);

        ob_start();
        imagepng($image);
        $bytes = (string) ob_get_clean();

        imagedestroy($image);

        return $bytes;
    }
}
