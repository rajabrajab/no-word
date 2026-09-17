@php
    /** @var \App\Models\Question $record */
    $record = $getRecord();
    $exists = filled($record->qr_code) && \Illuminate\Support\Facades\Storage::disk('public')->exists($record->qr_code);
@endphp

<div class="nw-media">
    @if (! $exists)
        <p class="nw-media__note">{{ __('panel.media_none') }}</p>
    @else
        <a href="{{ asset('storage/'.$record->qr_code) }}" target="_blank" rel="noopener">
            <img src="{{ asset('storage/'.$record->qr_code) }}" alt="{{ __('panel.qr_code') }}" class="nw-media__qr">
        </a>

        <a href="{{ route('question.show', $record) }}" target="_blank" rel="noopener" class="nw-media__link">
            {{ __('panel.qr_code_open_page') }}
        </a>
    @endif
</div>

@once
    <style>
        .nw-media__qr {
            width: 12rem;
            height: auto;
            background: #fff;
            padding: 0.5rem;
            border-radius: 0.5rem;
            display: block;
        }
    </style>
@endonce
