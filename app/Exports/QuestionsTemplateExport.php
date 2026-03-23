<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class QuestionsTemplateExport implements FromCollection, WithHeadings
{
    public function collection(): Collection
    {
        return collect();
    }

    public function headings(): array
    {
        return [
            __('panel.excel_category_id'),
            __('panel.excel_question'),
            __('panel.excel_answer'),
            __('panel.excel_hint'),
            __('panel.excel_score'),
            __('panel.excel_media'),
            __('panel.excel_media_type'),
        ];
    }
}

