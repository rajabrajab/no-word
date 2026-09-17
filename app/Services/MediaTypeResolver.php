<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * Works out the coarse kind of a media file — the value stored in the
 * `media_type` / `answer_media_type` columns and branched on when rendering.
 */
class MediaTypeResolver
{
    public const IMAGE = 'image';

    public const VIDEO = 'video';

    public const AUDIO = 'audio';

    /**
     * The kinds anything rendering media knows how to play or show.
     *
     * @var list<string>
     */
    public const KINDS = [self::IMAGE, self::VIDEO, self::AUDIO];

    /**
     * Coarse media type per file extension.
     *
     * Content sniffing alone misclassifies MPEG-4 audio: an .m4a written with an
     * `isom`/`mp42` brand (ffmpeg, most Android recorders) is byte-identical to a
     * video container in its header, so finfo reports `video/mp4`. The extension is
     * the only reliable signal for those, so it wins over the sniffed MIME type.
     *
     * @var array<string, string>
     */
    private const MEDIA_TYPES_BY_EXTENSION = [
        'aac' => self::AUDIO, 'aif' => self::AUDIO, 'aiff' => self::AUDIO, 'amr' => self::AUDIO,
        'caf' => self::AUDIO, 'flac' => self::AUDIO, 'm4a' => self::AUDIO, 'm4b' => self::AUDIO,
        'mp3' => self::AUDIO, 'oga' => self::AUDIO, 'ogg' => self::AUDIO, 'opus' => self::AUDIO,
        'wav' => self::AUDIO, 'weba' => self::AUDIO, 'wma' => self::AUDIO,

        '3g2' => self::VIDEO, '3gp' => self::VIDEO, 'avi' => self::VIDEO, 'flv' => self::VIDEO,
        'm4v' => self::VIDEO, 'mkv' => self::VIDEO, 'mov' => self::VIDEO, 'mp4' => self::VIDEO,
        'mpeg' => self::VIDEO, 'mpg' => self::VIDEO, 'ogv' => self::VIDEO, 'webm' => self::VIDEO,
        'wmv' => self::VIDEO,

        'avif' => self::IMAGE, 'bmp' => self::IMAGE, 'gif' => self::IMAGE, 'heic' => self::IMAGE,
        'heif' => self::IMAGE, 'ico' => self::IMAGE, 'jpeg' => self::IMAGE, 'jpg' => self::IMAGE,
        'png' => self::IMAGE, 'svg' => self::IMAGE, 'tif' => self::IMAGE, 'tiff' => self::IMAGE,
        'webp' => self::IMAGE,
    ];

    /**
     * Map a pending upload or a stored path to the coarse media type saved beside it.
     */
    public static function resolve(mixed $state): ?string
    {
        if (! $state) {
            return null;
        }

        $name = match (true) {
            $state instanceof TemporaryUploadedFile => $state->getClientOriginalName(),
            is_string($state) => $state,
            default => null,
        };

        if (($byExtension = self::fromExtension($name)) !== null) {
            return $byExtension;
        }

        // Only an unrecognised extension is worth opening the file for.
        $mime = self::mimeType($state);

        if (! is_string($mime)) {
            return null;
        }

        return match (true) {
            str_starts_with($mime, 'image/') => self::IMAGE,
            str_starts_with($mime, 'video/') => self::VIDEO,
            str_starts_with($mime, 'audio/'), $mime === 'application/ogg' => self::AUDIO,
            default => null,
        };
    }

    private static function mimeType(mixed $state): ?string
    {
        if ($state instanceof TemporaryUploadedFile) {
            return $state->getMimeType();
        }

        if (! is_string($state)) {
            return null;
        }

        $fullPath = Storage::disk('public')->path($state);

        return File::exists($fullPath) ? File::mimeType($fullPath) : null;
    }

    /**
     * The kind to render a stored file as, given whatever the row happens to say.
     *
     * The extension outranks the column: rows written before the extension-first fix
     * hold the sniffed type, so a voice note can sit in the table as `video`. Only
     * when the extension means nothing here does the stored value get a say, and
     * opening the file is the last resort.
     */
    public static function reconcile(?string $storedType, ?string $path): ?string
    {
        if (filled($path) && ($byExtension = self::fromExtension($path)) !== null) {
            return $byExtension;
        }

        $storedType = strtolower(trim((string) $storedType));

        if (in_array($storedType, self::KINDS, true)) {
            return $storedType;
        }

        return blank($path) ? null : self::resolve($path);
    }

    /**
     * The kind a filename's extension implies, without opening the file.
     */
    public static function fromExtension(?string $path): ?string
    {
        $extension = strtolower(pathinfo((string) $path, PATHINFO_EXTENSION));

        return self::MEDIA_TYPES_BY_EXTENSION[$extension] ?? null;
    }
}
