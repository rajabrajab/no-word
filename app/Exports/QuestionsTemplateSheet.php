<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;

/**
 * The blank sheet an admin fills in to add questions across categories.
 *
 * Headings are the labels an admin reads in the panel, never the column names —
 * the importer matches on those labels, so keep the two in step.
 */
class QuestionsTemplateSheet implements FromCollection, WithColumnWidths, WithEvents, WithHeadings, WithTitle
{
    public function collection(): Collection
    {
        return collect();
    }

    public function title(): string
    {
        return __('panel.excel_questions_sheet');
    }

    public function headings(): array
    {
        return [
            __('panel.excel_category'),
            __('panel.excel_question'),
            __('panel.excel_answer'),
            __('panel.excel_hint'),
            __('panel.excel_score'),
            __('panel.bulk_excel_media_column'),
            __('panel.bulk_excel_answer_media_column'),
        ];
    }

    /**
     * @return array<string, float|int>
     */
    public function columnWidths(): array
    {
        return [
            'A' => 26,
            'B' => 42,
            'C' => 32,
            'D' => 28,
            'E' => 12,
            'F' => 30,
            'G' => 30,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $sheet->freezePane('A2');

                // Rows are sized for a pasted picture, not for a line of text.
                for ($r = 2; $r <= 200; $r++) {
                    $sheet->getRowDimension($r)->setRowHeight(80);
                }

                $hints = [
                    'A1' => __('panel.excel_category_hint'),
                    'E1' => __('panel.excel_score_hint'),
                    'F1' => __('panel.bulk_excel_media_column_hint'),
                    'G1' => __('panel.bulk_excel_answer_media_column_hint'),
                ];

                foreach ($hints as $cell => $hint) {
                    if ($hint === '') {
                        continue;
                    }

                    $comment = $sheet->getComment($cell);
                    $comment->setWidth('280pt');
                    $comment->setHeight('120pt');
                    $comment->getText()->createTextRun($hint);
                }
            },
        ];
    }
}
