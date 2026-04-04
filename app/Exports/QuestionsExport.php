<?php

namespace App\Exports;

use App\Models\Question;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class QuestionsExport implements FromCollection, WithColumnWidths, WithEvents, WithHeadings, WithMapping
{
    protected ?Collection $questions = null;

    public function collection(): Collection
    {
        if ($this->questions === null) {
            $this->questions = Question::query()
                ->select(['id', 'category_id', 'question', 'answer', 'hint', 'score', 'media', 'media_type'])
                ->orderBy('id')
                ->get();
        }

        return $this->questions;
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
        ];
    }

    public function map($row): array
    {
        $mediaCell = '';
        if (filled($row->media)) {
            $absolute = Storage::disk('public')->path($row->media);
            if (! $this->isEmbeddableRasterImage($row, $absolute)) {
                $mediaCell = $row->media;
            }
        }

        return [
            $row->id,
            $row->category_id,
            $row->question,
            $row->answer,
            $row->hint,
            $row->score,
            $mediaCell,
        ];
    }


    public function columnWidths(): array
    {
        return [
            'A' => 8,
            'B' => 14,
            'C' => 40,
            'D' => 30,
            'E' => 24,
            'F' => 10,
            'G' => 28,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();

                foreach ($this->collection()->values() as $index => $question) {
                    if (! filled($question->media)) {
                        continue;
                    }

                    $absolute = Storage::disk('public')->path($question->media);
                    if (! $this->isEmbeddableRasterImage($question, $absolute)) {
                        continue;
                    }

                    $resolved = realpath($absolute);
                    if ($resolved === false || ! is_readable($resolved)) {
                        continue;
                    }

                    $excelRow = $index + 2;
                    $sheet->getRowDimension($excelRow)->setRowHeight(64);

                    $drawing = new Drawing;
                    $drawing->setName('Question media');
                    $drawing->setDescription((string) $question->id);
                    $drawing->setPath($resolved);
                    $drawing->setResizeProportional(true);
                    $drawing->setWidth(120);
                    $drawing->setHeight(72);
                    $drawing->setCoordinates('G'.$excelRow);
                    $drawing->setOffsetX(2);
                    $drawing->setOffsetY(2);
                    $drawing->setWorksheet($sheet);
                }
            },
        ];
    }

    protected function isEmbeddableRasterImage(Question $question, string $absolutePath): bool
    {
        if (! is_file($absolutePath)) {
            return false;
        }

        $type = strtolower((string) $question->media_type);
        if (in_array($type, ['video', 'audio'], true)) {
            return false;
        }

        $mime = @mime_content_type($absolutePath) ?: '';

        if (in_array($mime, ['image/svg+xml', 'image/svg'], true)) {
            return false;
        }

        if (str_starts_with($mime, 'image/')) {
            return true;
        }

        if (function_exists('getimagesize')) {
            $rasterTypes = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_BMP];
            if (defined('IMAGETYPE_WEBP')) {
                $rasterTypes[] = IMAGETYPE_WEBP;
            }
            $info = @getimagesize($absolutePath);
            if (is_array($info) && isset($info[2]) && in_array($info[2], $rasterTypes, true)) {
                return true;
            }
        }

        $ext = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));

        return in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'], true);
    }
}
