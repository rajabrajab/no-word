<?php

namespace App\Filament\Resources\Questions\Pages;

use App\Filament\Resources\Questions\QuestionResource;
use App\Services\QrCodeService;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditQuestion extends EditRecord
{
    protected static string $resource = QuestionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['generate_qr_code'] = !empty($this->record->qr_code);
        
        // Load country_id from the question's category
        if ($this->record->category && $this->record->category->country_id) {
            $data['country_id'] = $this->record->category->country_id;
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset($data['generate_qr_code']);
        unset($data['country_id']); // Country is only used for filtering categories

        return $data;
    }

    protected function afterSave(): void
    {

        $formData = $this->form->getRawState();

        if (isset($formData['generate_qr_code']) && $formData['generate_qr_code']) {
            $qrCodeService = app(QrCodeService::class);
            $this->record->update([
                'qr_code' => $qrCodeService->generateForQuestion($this->record)
            ]);
        } elseif (isset($formData['generate_qr_code']) && !$formData['generate_qr_code']) {
            if ($this->record->qr_code) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($this->record->qr_code);
            }
            $this->record->update([
                'qr_code' => null
            ]);
        }
    }
}
