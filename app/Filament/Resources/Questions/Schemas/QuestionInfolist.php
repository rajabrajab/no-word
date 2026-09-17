<?php

namespace App\Filament\Resources\Questions\Schemas;

use App\Models\Question;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * The read-only view of a question, shown on its own page.
 *
 * Media is rendered by a view entry rather than a file field, so video and audio
 * get a real player instead of the upload box's file name.
 */
class QuestionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('panel.question'))
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    TextEntry::make('question')
                        ->label(__('panel.question'))
                        ->columnSpanFull(),

                    TextEntry::make('answer')
                        ->label(__('panel.answer'))
                        ->columnSpanFull(),

                    TextEntry::make('hint')
                        ->label(__('panel.hint'))
                        ->placeholder('—'),

                    TextEntry::make('score')
                        ->label(__('panel.score'))
                        ->badge(),

                    TextEntry::make('category.name')
                        ->label(__('panel.category'))
                        ->placeholder('—'),

                    TextEntry::make('category.country.name')
                        ->label(__('panel.country'))
                        ->placeholder('—'),

                    TextEntry::make('answer_time')
                        ->label(__('panel.answer_time'))
                        ->state(fn (Question $record): ?string => $record->answerTime()
                            ? $record->answerTime().' '.__('panel.seconds')
                            : null)
                        ->placeholder('—'),
                ]),

            Section::make(__('panel.media'))
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    ViewEntry::make('media')
                        ->label(__('panel.media'))
                        ->view('filament.infolists.entries.question-media')
                        ->viewData(['mediaSlot' => 'question']),

                    ViewEntry::make('answer_media')
                        ->label(__('panel.answer_media'))
                        ->view('filament.infolists.entries.question-media')
                        ->viewData(['mediaSlot' => 'answer']),

                    TextEntry::make('media_type')
                        ->label(__('panel.media_type'))
                        ->state(fn (Question $record): ?string => static::mediaTypeLabel($record->questionMediaType()))
                        ->placeholder('—'),

                    TextEntry::make('answer_media_type')
                        ->label(__('panel.answer_media_type'))
                        ->state(fn (Question $record): ?string => static::mediaTypeLabel($record->answerMediaType()))
                        ->placeholder('—'),
                ]),

            Section::make(__('panel.qr_code'))
                ->columnSpanFull()
                ->collapsed()
                ->schema([
                    ViewEntry::make('qr_code')
                        ->hiddenLabel()
                        ->view('filament.infolists.entries.question-qr-code'),
                ]),
        ]);
    }

    private static function mediaTypeLabel(?string $type): ?string
    {
        return $type === null ? null : __('panel.media_type_'.$type);
    }
}
