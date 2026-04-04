<?php

namespace App\Filament\Resources\Questions\Pages;

use App\Exports\QuestionsExport;
use App\Filament\Resources\Questions\QuestionResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
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
                ->action(fn () => Excel::download(new QuestionsExport(), 'questions.xlsx')),

            CreateAction::make(),
        ];
    }
}
