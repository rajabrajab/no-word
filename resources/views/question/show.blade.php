<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>السؤال</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        [hidden] {
            display: none !important;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #09182c 0%, #1e2f46 100%);
            min-height: 100vh;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .container {
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
            max-width: 800px;
            width: 100%;
            padding: 40px;
            text-align: center;
        }

        @media (prefers-color-scheme: dark) {
            body {
                background: linear-gradient(135deg, #09182c 0%, #1e2f46 100%);
            }

            .container {
                background: #ffffff;
                color: #1a2b3c;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
                border: 2px solid rgba(251, 203, 28, 0.3);
            }
        }

        .logo {
            max-width: 200px;
            width: 100%;
            height: auto;
            margin-bottom: 30px;
            display: block;
            margin-left: auto;
            margin-right: auto;
        }

        .question-card {
            background: #f5f7fa;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            border: 2px solid #e1e8ed;
            position: relative;
        }

        .question-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #fbcb1c 0%, #ec2c2e 100%);
            border-radius: 15px 15px 0 0;
        }

        .question-title {
            font-size: 24px;
            color: #1a2b3c;
            margin-bottom: 20px;
            font-weight: bold;
            line-height: 1.6;
        }

        @media (prefers-color-scheme: dark) {
            .question-card {
                background: #f5f7fa;
                border-color: rgba(251, 203, 28, 0.5);
                border-width: 2px;
            }

            .question-card::before {
                background: linear-gradient(90deg, #fbcb1c 0%, #ec2c2e 100%);
                height: 5px;
                box-shadow: 0 2px 10px rgba(251, 203, 28, 0.4);
            }

            .question-title {
                color: #1a2b3c;
            }
        }

        .media-container {
            margin: 30px 0;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            background: #ffffff;
        }

        @media (prefers-color-scheme: dark) {
            .media-container {
                background: #ffffff;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
                border: 1px solid rgba(251, 203, 28, 0.3);
            }
        }

        .media-container img {
            width: 100%;
            height: auto;
            display: block;
            max-height: 500px;
            object-fit: contain;
        }

        .media-container video {
            width: 100%;
            height: auto;
            display: block;
            max-height: 500px;
        }

        .media-container audio {
            width: 100%;
            display: block;
            padding: 15px;
        }

        .media-container iframe {
            width: 100%;
            min-height: 500px;
            border: none;
        }

        .score-badge {
            display: inline-block;
            background: linear-gradient(135deg, #09182c 0%, #1e2f46 100%);
            color: #ffffff;
            padding: 10px 25px;
            border-radius: 25px;
            font-size: 18px;
            font-weight: bold;
            margin-top: 20px;
            box-shadow: 0 4px 15px rgba(9, 24, 44, 0.3);
        }

        @media (prefers-color-scheme: dark) {
            .score-badge {
                background: linear-gradient(135deg, #fbcb1c 0%, #ec2c2e 100%);
                color: #1e2f46;
                box-shadow: 0 4px 20px rgba(251, 203, 28, 0.5), 0 0 10px rgba(236, 44, 46, 0.3);
                font-weight: bold;
            }
        }

        .reveal-button {
            display: inline-block;
            width: 100%;
            max-width: 320px;
            padding: 16px 30px;
            border: none;
            border-radius: 30px;
            background: #fbcb1c;
            color: #09182c;
            font-family: inherit;
            font-size: 20px;
            font-weight: bold;
            cursor: pointer;
            box-shadow: 0 6px 18px rgba(251, 203, 28, 0.45);
            transition: background 0.15s ease, box-shadow 0.15s ease, transform 0.15s ease;
            -webkit-tap-highlight-color: transparent;
        }

        .reveal-button:hover {
            background: #ffd740;
        }

        .reveal-button:active {
            transform: translateY(2px);
            box-shadow: 0 3px 10px rgba(251, 203, 28, 0.4);
        }

        .reveal-button:focus-visible {
            outline: 3px solid #ec2c2e;
            outline-offset: 3px;
        }

        .answer-card {
            margin-top: 25px;
            background: #fffdf3;
            border: 2px solid #e1e8ed;
            border-inline-start: 6px solid #fbcb1c;
            border-radius: 15px;
            padding: 25px;
            text-align: start;
            animation: answer-reveal 0.25s ease-out;
        }

        @keyframes answer-reveal {
            from {
                opacity: 0;
                transform: translateY(-8px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .answer-card {
                animation: none;
            }
        }

        .answer-label {
            font-size: 15px;
            font-weight: bold;
            color: #ec2c2e;
            margin-bottom: 10px;
        }

        .answer-text {
            font-size: 22px;
            font-weight: bold;
            color: #1a2b3c;
            line-height: 1.6;
        }

        .answer-card .media-container {
            margin-bottom: 0;
        }

        @media (prefers-color-scheme: dark) {
            .answer-card {
                background: #fffdf3;
                border-color: rgba(251, 203, 28, 0.5);
                border-inline-start-color: #fbcb1c;
            }

            .answer-text {
                color: #1a2b3c;
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 20px;
                margin: 10px;
            }

            .question-title {
                font-size: 20px;
            }

            .question-card {
                padding: 20px;
            }

            .logo {
                max-width: 150px;
            }

            .media-container {
                margin: 20px 0;
            }

            .media-container img,
            .media-container video {
                max-height: 300px;
            }

            .media-container iframe {
                min-height: 300px;
            }

            .reveal-button {
                font-size: 18px;
                padding: 14px 24px;
            }

            .answer-card {
                padding: 20px;
            }

            .answer-text {
                font-size: 19px;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 15px;
            }

            .question-title {
                font-size: 18px;
            }

            .question-card {
                padding: 15px;
            }

            .logo {
                max-width: 120px;
            }

            .answer-card {
                padding: 15px;
            }

            .answer-text {
                font-size: 18px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        @if(file_exists(public_path('images/logo.png')))
            <img src="{{ asset('images/logo.png') }}" alt="Logo" class="logo">
        @endif

        <div class="question-card">
            <h1 class="question-title">{{ $question->question }}</h1>

            @if($question->hasQuestionMedia())
                <div class="media-container">
                    @if($question->media_type === 'image')
                        <img src="{{ asset('storage/' . $question->media) }}" alt="Question Media">
                    @elseif($question->media_type === 'video')
                        <video controls>
                            <source src="{{ asset('storage/' . $question->media) }}" type="video/mp4">
                            متصفحك لا يدعم تشغيل الفيديو.
                        </video>
                    @else
                        <iframe src="{{ asset('storage/' . $question->media) }}" frameborder="0"></iframe>
                    @endif
                </div>
            @endif


            @if($question->score)
                <div class="score-badge">
                    النقاط: {{ $question->score }}
                </div>
            @endif
        </div>

        <button type="button" class="reveal-button" id="revealAnswer" aria-expanded="false" aria-controls="answerBlock">
            إظهار الإجابة
        </button>

        <div class="answer-card" id="answerBlock" hidden>
            <div class="answer-label">الإجابة</div>
            <div class="answer-text">{{ $question->answer }}</div>

            @if($question->hasAnswerMedia())
                <div class="media-container">
                    @if($question->answer_media_type === 'video')
                        <video controls>
                            <source src="{{ asset('storage/' . $question->answer_media) }}" type="video/mp4">
                            متصفحك لا يدعم تشغيل الفيديو.
                        </video>
                    @elseif($question->answer_media_type === 'audio')
                        <audio controls src="{{ asset('storage/' . $question->answer_media) }}">
                            متصفحك لا يدعم تشغيل الصوت.
                        </audio>
                    @else
                        <img src="{{ asset('storage/' . $question->answer_media) }}" alt="Answer Media">
                    @endif
                </div>
            @endif
        </div>
    </div>

    <script>
        (function () {
            var button = document.getElementById('revealAnswer');
            var block = document.getElementById('answerBlock');

            if (!button || !block) {
                return;
            }

            button.addEventListener('click', function () {
                var willShow = block.hidden;

                block.hidden = !willShow;
                button.setAttribute('aria-expanded', willShow ? 'true' : 'false');
                button.textContent = willShow ? 'إخفاء الإجابة' : 'إظهار الإجابة';
            });
        })();
    </script>
</body>
</html>
