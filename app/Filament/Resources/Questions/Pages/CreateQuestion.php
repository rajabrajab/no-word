<?php

namespace App\Filament\Resources\Questions\Pages;

use App\Filament\Resources\Questions\QuestionResource;
use App\Services\QrCodeService;
use Filament\Resources\Pages\CreateRecord;

class CreateQuestion extends CreateRecord
{
    protected static string $resource = QuestionResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        $qrCodeService = app(QrCodeService::class);
        $this->record->update([
            'qr_code' => $qrCodeService->generateForQuestion($this->record)
        ]);
    }
}
