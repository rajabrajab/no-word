<?php

namespace App\Models;

use App\Services\MediaTypeResolver;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Question extends BaseModel
{
    use SoftDeletes;

    /**
     * The public question page is addressed by an unguessable token, never by the
     * id: the QR codes are printed and handed out, so an id in the URL would let
     * anyone holding one card walk the ids and read every question and answer.
     */
    protected static function booted(): void
    {
        static::creating(function (self $question): void {
            if (blank($question->qr_token)) {
                $question->qr_token = static::newQrToken();
            }
        });
    }

    public static function newQrToken(): string
    {
        return bin2hex(random_bytes(16));
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function games()
    {
        return $this->belongsToMany(Game::class, 'game_questions');
    }

    /**
     * Seconds allowed to answer this question, taken from its category's
     * timer for the question's score tier.
     */
    public function answerTime(): ?int
    {
        return $this->category?->answerTimeForScore($this->score);
    }

    /**
     * Whether the question's own picture is still on disk and safe to render.
     */
    public function hasQuestionMedia(): bool
    {
        return $this->storedMediaExists($this->media);
    }

    /**
     * Whether the answer's picture is still on disk and safe to render.
     */
    public function hasAnswerMedia(): bool
    {
        return $this->storedMediaExists($this->answer_media);
    }

    /**
     * The kind of the question's media, worked out from the file when the stored
     * type is blank or unrecognised — rows predating the column, or written before
     * the type was derived, would otherwise reach the wrong player.
     */
    public function questionMediaType(): ?string
    {
        return MediaTypeResolver::reconcile($this->media_type, $this->media);
    }

    /**
     * The kind of the answer's media. See {@see questionMediaType()}.
     */
    public function answerMediaType(): ?string
    {
        return MediaTypeResolver::reconcile($this->answer_media_type, $this->answer_media);
    }

    /**
     * Public URL of the question's media, or null when it has none.
     */
    public function questionMediaUrl(): ?string
    {
        return filled($this->media) ? asset('storage/'.$this->media) : null;
    }

    /**
     * Public URL of the answer's media, or null when it has none.
     */
    public function answerMediaUrl(): ?string
    {
        return filled($this->answer_media) ? asset('storage/'.$this->answer_media) : null;
    }

    /**
     * A stored path whose file has since been deleted would render as a broken
     * image, so treat a missing file the same as no file at all.
     */
    private function storedMediaExists(?string $path): bool
    {
        return filled($path) && Storage::disk('public')->exists($path);
    }
}
