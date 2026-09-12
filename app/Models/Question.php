<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Question extends BaseModel
{
    use SoftDeletes;

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
     * A stored path whose file has since been deleted would render as a broken
     * image, so treat a missing file the same as no file at all.
     */
    private function storedMediaExists(?string $path): bool
    {
        return filled($path) && Storage::disk('public')->exists($path);
    }
}
