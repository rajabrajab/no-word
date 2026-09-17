<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * The workbook behind "Download template" on the Questions list: the blank sheet
 * to fill in, plus the category list it is filled in against.
 */
class QuestionsTemplateExport implements WithMultipleSheets
{
    /**
     * @return list<object>
     */
    public function sheets(): array
    {
        return [
            new QuestionsTemplateSheet,
            new QuestionsTemplateCategoriesSheet,
        ];
    }
}
