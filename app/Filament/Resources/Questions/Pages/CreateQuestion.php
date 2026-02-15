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

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        unset($data['generate_qr_code']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $formData = $this->form->getRawState();

        if (isset($formData['generate_qr_code']) && $formData['generate_qr_code']) {
            $qrCodeService = app(QrCodeService::class);
            $this->record->update([
                'qr_code' => $qrCodeService->generateForQuestion($this->record)
            ]);
        }
    }
}
