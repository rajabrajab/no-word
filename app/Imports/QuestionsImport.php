<?php

namespace App\Imports;

use App\Models\Category;
use App\Models\Question;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;

class QuestionsImport implements ToCollection
{
    public int $created = 0;
    public int $skipped = 0;

    public array $errors = [];

    public function collection(Collection $rows): void
    {
        if ($rows->isEmpty()) {
            return;
        }

        // Row 1 is header (either English keys or translated labels)
        $header = $rows->first();
        $rows = $rows->slice(1)->values();

        $expectedOrder = [
            'category_id',
            'question',
            'answer',
            'hint',
            'score',
            'media',
            'media_type',
        ];

        $englishHeaders = [
            'category_id',
            'question',
            'answer',
            'hint',
            'score',
            'media',
            'media_type',
        ];

        $translatedHeaders = [
            __('panel.excel_category_id'),
            __('panel.excel_question'),
            __('panel.excel_answer'),
            __('panel.excel_hint'),
            __('panel.excel_score'),
            __('panel.excel_media'),
            __('panel.excel_media_type'),
        ];

        $headerValues = [];
        foreach ($header as $cell) {
            $headerValues[] = is_string($cell) ? trim($cell) : $cell;
        }

        $isEnglish = array_values(array_slice($headerValues, 0, 7)) === $englishHeaders;
        $isTranslated = array_values(array_slice($headerValues, 0, 7)) === $translatedHeaders;

        if (! $isEnglish && ! $isTranslated) {
            $this->errors[] = [
                'row' => 1,
                'errors' => ['Invalid header row. Please use the downloaded template.'],
            ];
            return;
        }

        foreach ($rows as $index => $row) {
            // Excel row number: +2 because we removed header row
            $excelRowNumber = $index + 2;

            $cells = $row->values()->all();

            $data = [];
            foreach ($expectedOrder as $i => $field) {
                $data[$field] = $cells[$i] ?? null;
            }

            // Skip fully empty rows
            $nonEmpty = array_filter($data, fn ($v) => $v !== null && $v !== '');
            if (empty($nonEmpty)) {
                $this->skipped++;
                continue;
            }

            $validator = Validator::make($data, [
                'category_id' => ['required', 'integer', 'exists:categories,id'],
                'question' => ['required', 'string'],
                'answer' => ['required', 'string'],
                'hint' => ['nullable', 'string'],
                'score' => ['nullable', 'integer'],
                'media' => ['nullable', 'string'],
                'media_type' => ['nullable', 'string'],
            ]);

            if ($validator->fails()) {
                $this->errors[] = [
                    'row' => $excelRowNumber,
                    'errors' => $validator->errors()->all(),
                ];
                $this->skipped++;
                continue;
            }

            // Category already validated by exists rule; still guard for safety
            if (! Category::query()->whereKey($data['category_id'])->exists()) {
                $this->errors[] = [
                    'row' => $excelRowNumber,
                    'errors' => ["Invalid category_id: {$data['category_id']}"],
                ];
                $this->skipped++;
                continue;
            }

            Question::query()->create($validator->validated());
            $this->created++;
        }
    }
}

