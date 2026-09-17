@php
    /** @var \App\Models\Question $record */
    $record = $getRecord();
    $isAnswer = ($mediaSlot ?? 'question') === 'answer';

    $path = $isAnswer ? $record->answer_media : $record->media;
    $type = $isAnswer ? $record->answerMediaType() : $record->questionMediaType();
    $url = $isAnswer ? $record->answerMediaUrl() : $record->questionMediaUrl();
    $exists = $isAnswer ? $record->hasAnswerMedia() : $record->hasQuestionMedia();
@endphp

<div class="nw-media">
    @if (blank($path))
        <p class="nw-media__note">{{ __('panel.media_none') }}</p>
    @elseif (! $exists)
        <p class="nw-media__note nw-media__note--warning">{{ __('panel.media_file_missing') }}</p>
        <p class="nw-media__path">{{ $path }}</p>
    @else
        @switch($type)
            @case('image')
                <a href="{{ $url }}" target="_blank" rel="noopener">
                    <img src="{{ $url }}" alt="{{ $record->question }}" class="nw-media__image">
                </a>
                @break

            @case('video')
                <video class="nw-media__video" controls preload="metadata" playsinline>
                    <source src="{{ $url }}">
                    {{ __('panel.media_unsupported_player') }}
                </video>
                @break

            @case('audio')
                <audio class="nw-media__audio" controls preload="metadata" src="{{ $url }}">
                    {{ __('panel.media_unsupported_player') }}
                </audio>
                @break

            @default
                {{-- PDFs, documents, anything with no player: hand over the file itself. --}}
                <a href="{{ $url }}" target="_blank" rel="noopener" class="nw-media__link">
                    {{ __('panel.media_open_file') }}
                </a>
        @endswitch

        <p class="nw-media__path">{{ $path }}</p>
    @endif
</div>

@once
    <style>
        .nw-media {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            align-items: flex-start;
            max-width: 32rem;
        }

        .nw-media__image,
        .nw-media__video {
            max-width: 100%;
            max-height: 20rem;
            border-radius: 0.5rem;
            display: block;
        }

        .nw-media__audio {
            width: 100%;
            min-width: 16rem;
        }

        .nw-media__link {
            text-decoration: underline;
            font-weight: 600;
        }

        .nw-media__note {
            font-style: italic;
            opacity: 0.7;
        }

        .nw-media__note--warning {
            color: #b45309;
            font-style: normal;
            font-weight: 600;
        }

        .nw-media__path {
            font-size: 0.75rem;
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            opacity: 0.6;
            word-break: break-all;
        }
    </style>
@endonce
