<?php

namespace App\Exports;

use App\Models\Category;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * A read-only companion sheet so an admin can look up what to type in the
 * template's category column without leaving Excel.
 */
class QuestionsTemplateCategoriesSheet implements FromCollection, WithColumnWidths, WithHeadings, WithMapping, WithTitle
{
    public function collection(): Collection
    {
        return Category::query()
            ->with('country:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'country_id']);
    }

    public function title(): string
    {
        return __('panel.excel_categories_sheet');
    }

    public function headings(): array
    {
        return [
            __('panel.excel_category_name'),
            __('panel.excel_category_id'),
            __('panel.country'),
        ];
    }

    /**
     * @param  Category  $row
     * @return list<mixed>
     */
    public function map($row): array
    {
        return [
            $row->name,
            $row->id,
            $row->country?->name,
        ];
    }

    /**
     * @return array<string, float|int>
     */
    public function columnWidths(): array
    {
        return [
            'A' => 32,
            'B' => 14,
            'C' => 24,
        ];
    }
}
