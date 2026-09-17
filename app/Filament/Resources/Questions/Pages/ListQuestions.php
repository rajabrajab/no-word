<?php

namespace App\Filament\Resources\Questions\Pages;

use App\Exports\QuestionsExport;
use App\Exports\QuestionsTemplateExport;
use App\Filament\Resources\Questions\QuestionResource;
use App\Services\QrCodeService;
use App\Services\QuestionsExcelImporter;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ListQuestions extends ListRecords
{
    protected static string $resource = QuestionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportQuestions')
                ->label(__('panel.export_questions_to_excel'))
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(fn () => Excel::download(new QuestionsExport, 'questions.xlsx')),

            Action::make('downloadQuestionsTemplate')
                ->label(__('panel.download_questions_template'))
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->action(fn () => Excel::download(new QuestionsTemplateExport, 'questions-template.xlsx')),

            Action::make('importQuestions')
                ->label(__('panel.import_questions_from_excel'))
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->modalDescription(__('panel.import_questions_description'))
                ->form([
                    FileUpload::make('file')
                        ->label(__('panel.excel_file'))
                        ->required()
                        ->disk('local')
                        ->directory('imports')
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-excel',
                        ]),
                ])
                ->action(fn (array $data) => $this->importQuestions($data)),

            CreateAction::make(),
        ];
    }

    /**
     * @param  array{file?: string|list<string>|null}  $data
     */
    protected function importQuestions(array $data): void
    {
        $path = $data['file'] ?? null;

        if (! $path) {
            Notification::make()
                ->title(__('panel.bulk_excel_no_file'))
                ->danger()
                ->send();

            return;
        }

        $absolutePath = Storage::disk('local')->path(is_array($path) ? ($path[0] ?? '') : $path);

        if (! is_file($absolutePath)) {
            Notification::make()
                ->title(__('panel.bulk_excel_file_missing'))
                ->danger()
                ->send();

            return;
        }

        $importer = new QuestionsExcelImporter(app(QrCodeService::class));
        $importer->import($absolutePath);

        if (! empty($importer->errors)) {
            $body = collect($importer->errors)
                ->take(5)
                ->map(fn (array $error): string => __('panel.bulk_excel_row').' '.$error['row'].': '.implode(' | ', $error['errors']))
                ->implode("\n");

            $notification = Notification::make()->body($body);

            if ($importer->created > 0) {
                $notification
                    ->title(__('panel.bulk_excel_import_partial', [
                        'created' => $importer->created,
                        'skipped' => $importer->skipped,
                    ]))
                    ->warning();
            } else {
                $notification
                    ->title(__('panel.bulk_excel_import_failed'))
                    ->danger();
            }

            $notification->send();

            return;
        }

        Notification::make()
            ->title(__('panel.bulk_excel_import_done', ['count' => $importer->created]))
            ->success()
            ->send();
    }
}
