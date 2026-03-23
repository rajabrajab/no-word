<?php

namespace App\Exports;

use App\Models\Question;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class QuestionsExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection(): Collection
    {
        return Question::query()
            ->select(['id', 'category_id', 'question', 'answer', 'hint', 'score', 'media', 'media_type'])
            ->orderBy('id')
            ->get();
    }

    public function headings(): array
    {
        return [
            __('panel.excel_id'),
            __('panel.excel_category_id'),
            __('panel.excel_question'),
            __('panel.excel_answer'),
            __('panel.excel_hint'),
            __('panel.excel_score'),
            __('panel.excel_media'),
            __('panel.excel_media_type'),
        ];
    }

    public function map($row): array
    {
        return [
            $row->id,
            $row->category_id,
            $row->question,
            $row->answer,
            $row->hint,
            $row->score,
            $row->media,
            $row->media_type,
        ];
    }
}

