{{--
    Renders one piece of question media by its resolved kind.

    The kind comes from Question::questionMediaType()/answerMediaType(), which falls
    back to the file itself when the stored media_type is blank or unrecognised —
    guessing from an empty column is what used to send audio into the wrong element.

    @var string $url
    @var string|null $type
    @var string $alt
--}}
<div class="media-container">
    @switch($type)
        @case('image')
            <img src="{{ $url }}" alt="{{ $alt }}">
            @break

        @case('video')
            <video controls preload="metadata" playsinline>
                <source src="{{ $url }}">
                متصفحك لا يدعم تشغيل الفيديو.
            </video>
            @break

        @case('audio')
            <audio controls preload="metadata" src="{{ $url }}">
                متصفحك لا يدعم تشغيل الصوت.
            </audio>
            @break

        @default
            <iframe src="{{ $url }}" frameborder="0"></iframe>
    @endswitch
</div>
