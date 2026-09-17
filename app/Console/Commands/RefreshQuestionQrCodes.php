<?php

namespace App\Console\Commands;

use App\Models\Question;
use App\Services\QrCodeService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class RefreshQuestionQrCodes extends Command
{
    protected $signature = 'questions:refresh-qr-codes
                            {--new-tokens : Issue a fresh token for every question, invalidating codes already printed}
                            {--missing-only : Only draw codes for questions that have none}';

    protected $description = 'Redraw the question QR codes so they point at the token URL instead of the id URL';

    public function handle(QrCodeService $qrCodeService): int
    {
        $rotate = (bool) $this->option('new-tokens');

        if ($rotate && ! $this->confirm('Issuing new tokens breaks every QR code already printed. Continue?', false)) {
            $this->warn('Nothing changed.');

            return self::SUCCESS;
        }

        $query = $this->questionsToDraw();

        $total = $query->clone()->count();

        if ($total === 0) {
            $this->info('No questions to draw.');

            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($total);
        $drawn = 0;
        $orphansRemoved = 0;

        $query->chunkById(200, function ($questions) use ($qrCodeService, $rotate, $bar, &$drawn): void {
            foreach ($questions as $question) {
                if ($rotate) {
                    $question->forceFill(['qr_token' => Question::newQrToken()])->save();
                }

                $question->update([
                    'qr_code' => $qrCodeService->generateForQuestion($question),
                ]);

                $drawn++;
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);

        $orphansRemoved = $this->removeOrphanedCodes();

        $this->info("Drew {$drawn} QR code(s).");

        if ($orphansRemoved > 0) {
            $this->info("Deleted {$orphansRemoved} leftover code file(s) that no question points at.");
        }

        return self::SUCCESS;
    }

    /**
     * Every live question, plus the deleted ones that already have a code. A deleted
     * question keeps its file, so skipping it would leave an id-URL code behind for a
     * restore to bring back — but it is not given a code it never had.
     */
    private function questionsToDraw(): Builder
    {
        if ($this->option('missing-only')) {
            return Question::query()->where(
                fn (Builder $query) => $query->whereNull('qr_code')->orWhere('qr_code', ''),
            );
        }

        return Question::withTrashed()->where(
            fn (Builder $query) => $query->whereNull('deleted_at')->orWhere(
                fn (Builder $query) => $query->whereNotNull('qr_code')->where('qr_code', '<>', ''),
            ),
        );
    }

    /**
     * Delete code files nothing references — notably the old `question-{id}.svg`
     * files, which encode the id URL this change exists to retire.
     */
    private function removeOrphanedCodes(): int
    {
        $disk = Storage::disk('public');
        $referenced = Question::query()->withTrashed()->pluck('qr_code')->filter()->flip();

        $removed = 0;

        foreach ($disk->files('qr-codes') as $file) {
            if (! $referenced->has($file)) {
                $disk->delete($file);
                $removed++;
            }
        }

        return $removed;
    }
}
