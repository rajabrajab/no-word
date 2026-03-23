<?php

namespace App\Filament\Resources\Questions\Pages;

use App\Filament\Resources\Questions\QuestionResource;
use App\Exports\QuestionsExport;
use App\Exports\QuestionsTemplateExport;
use App\Imports\QuestionsImport;
use Filament\Actions\CreateAction;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;

class ListQuestions extends ListRecords
{
    protected static string $resource = QuestionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('downloadQuestionsTemplate')
                ->label(__('panel.download_template') ?: 'Download template')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(fn () => Excel::download(new QuestionsTemplateExport(), 'questions_template.xlsx')),

            Action::make('exportQuestions')
                ->label(__('panel.export') ?: 'Export')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(fn () => Excel::download(new QuestionsExport(), 'questions.xlsx')),

            Action::make('importQuestions')
                ->label(__('panel.import') ?: 'Import')
                ->icon('heroicon-o-arrow-up-tray')
                ->form([
                    FileUpload::make('file')
                        ->label(__('panel.excel_file') ?: 'Excel file')
                        ->required()
                        ->disk('local')
                        ->directory('imports')
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-excel',
                            'text/csv',
                        ]),
                ])
                ->action(function (array $data): void {
                    $path = $data['file'] ?? null;
                    if (! $path) {
                        Notification::make()
                            ->title('No file uploaded')
                            ->danger()
                            ->send();
                        return;
                    }

                    $import = new QuestionsImport();
                    Excel::import($import, storage_path('app/' . $path));

                    if (! empty($import->errors)) {
                        $firstErrors = collect($import->errors)
                            ->take(5)
                            ->map(fn ($e) => 'Row ' . $e['row'] . ': ' . implode(' | ', $e['errors']))
                            ->implode("\n");

                        Notification::make()
                            ->title("Imported {$import->created} questions. Skipped {$import->skipped}.")
                            ->body("Some rows failed validation:\n" . $firstErrors)
                            ->warning()
                            ->send();
                        return;
                    }

                    Notification::make()
                        ->title("Imported {$import->created} questions successfully.")
                        ->success()
                        ->send();
                }),

            CreateAction::make(),
        ];
    }
}
