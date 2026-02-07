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

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 800px;
            width: 100%;
            padding: 40px;
            text-align: center;
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
            background: #f8f9fa;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            border: 2px solid #e9ecef;
        }

        .question-title {
            font-size: 24px;
            color: #333;
            margin-bottom: 20px;
            font-weight: bold;
            line-height: 1.6;
        }

        .media-container {
            margin: 30px 0;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
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

        .media-container iframe {
            width: 100%;
            min-height: 500px;
            border: none;
        }

        .hint-section {
            background: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
        }

        .hint-label {
            font-weight: bold;
            color: #856404;
            margin-bottom: 10px;
            font-size: 18px;
        }

        .hint-text {
            color: #856404;
            font-size: 16px;
            line-height: 1.6;
        }

        .score-badge {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 10px 25px;
            border-radius: 25px;
            font-size: 18px;
            font-weight: bold;
            margin-top: 20px;
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

            @if($question->media)
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

            @if($question->hint)
                <div class="hint-section">
                    <div class="hint-label">💡 تلميح:</div>
                    <div class="hint-text">{{ $question->hint }}</div>
                </div>
            @endif

            @if($question->score)
                <div class="score-badge">
                    النقاط: {{ $question->score }}
                </div>
            @endif
        </div>
    </div>
</body>
</html>
