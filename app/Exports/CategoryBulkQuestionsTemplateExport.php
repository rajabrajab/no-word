<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;

class CategoryBulkQuestionsTemplateExport implements FromCollection, WithColumnWidths, WithEvents, WithHeadings
{
    public function collection(): Collection
    {
        return collect();
    }

    public function headings(): array
    {
        return [
            __('panel.excel_question'),
            __('panel.excel_answer'),
            __('panel.excel_hint'),
            __('panel.excel_score'),
            __('panel.bulk_excel_media_column'),
        ];
    }

    /**
     * @return array<string, float|int>
     */
    public function columnWidths(): array
    {
        return [
            'A' => 42,
            'B' => 32,
            'C' => 28,
            'D' => 12,
            'E' => 30,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $sheet->freezePane('A2');

                for ($r = 2; $r <= 200; $r++) {
                    $sheet->getRowDimension($r)->setRowHeight(80);
                }

                $hint = __('panel.bulk_excel_media_column_hint');
                if ($hint !== '') {
                    $comment = $sheet->getComment('E1');
                    $comment->setWidth('280pt');
                    $comment->setHeight('120pt');
                    $comment->getText()->createTextRun($hint);
                }
            },
        ];
    }
}
