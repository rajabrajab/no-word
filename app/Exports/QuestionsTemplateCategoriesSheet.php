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
        // Grouped by country, because the same category names repeat in every one of
        // them: the country is what tells two identically named rows apart.
        return Category::query()
            ->with('country:id,name')
            ->join('countries', 'countries.id', '=', 'categories.country_id')
            ->orderBy('countries.name')
            ->orderBy('categories.name')
            ->select(['categories.id', 'categories.name', 'categories.country_id'])
            ->get();
    }

    public function title(): string
    {
        return __('panel.excel_categories_sheet');
    }

    public function headings(): array
    {
        return [
            __('panel.excel_category_id'),
            __('panel.country'),
            __('panel.excel_category_name'),
        ];
    }

    /**
     * @param  Category  $row
     * @return list<mixed>
     */
    public function map($row): array
    {
        return [
            $row->id,
            $row->country?->name,
            $row->name,
        ];
    }

    /**
     * @return array<string, float|int>
     */
    public function columnWidths(): array
    {
        return [
            'A' => 14,
            'B' => 24,
            'C' => 32,
        ];
    }
}
