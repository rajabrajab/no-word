<?php

namespace App\Filament\Resources\Questions\Pages;

use App\Filament\Resources\Questions\QuestionResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewQuestion extends ViewRecord
{
    protected static string $resource = QuestionResource::class;

    public function getTitle(): string
    {
        return __('panel.question').' #'.$this->record->id;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('openQrPage')
                ->label(__('panel.qr_code_open_page'))
                ->icon('heroicon-o-qr-code')
                ->color('gray')
                ->url(fn (): string => route('question.show', $this->record))
                ->openUrlInNewTab(),

            EditAction::make(),
            DeleteAction::make(),
        ];
    }
}
