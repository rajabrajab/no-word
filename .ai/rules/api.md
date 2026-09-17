---
paths:
  - 'app/Http/Controllers/Api/**'
---

# Api

## API messages are translation keys, never sentences
The app is Arabic first and the JSON API answers in Arabic. Never pass a written sentence to response()->sendResponse() / sendError(), and never put one in a thrown exception that reaches a controller — it will ship to the phone in English.

Pass a key from lang/en/api.php + lang/ar/api.php instead: a ResponseMessages constant (these hold keys such as 'api.not_found', not text) or __('api.game.no_alternative_question'). Both macros run whatever they receive through the translator via ResponseMessages::translate(), so a key becomes the caller's language and an unknown string is passed through untouched. Every key must exist in BOTH files — ApiLocalizationTest asserts the two files have identical key sets.

Locale is chosen by App\Http\Middleware\SetApiLocale, registered globally (not in the api group, so a 404 on an unmatched route is translated too) and acting only on `api/*`. Order: the X-Locale header, then the signed-in user's `language` column, then config('app.api_locale') which defaults to ar. `Accept-Language` is deliberately ignored — it carries the handset's OS language, so an Arabic app on an English phone would answer in English. APP_LOCALE drives the admin panel only.

Do not let an exception's own text reach the client: wrap it in a key and Log::error the exception. Validation strings come from lang/ar/validation.php, including the `attributes` map that turns `email` into البريد الإلكتروني.
