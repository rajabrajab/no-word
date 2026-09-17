<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Answer every API request in Arabic unless the caller asks for something else.
 *
 * Runs globally rather than in the `api` group so that a request matching no
 * route — which never reaches group middleware — still gets its "not found"
 * in Arabic. Requests outside the API are left alone: the admin panel has its
 * own language switcher and APP_LOCALE to follow.
 *
 * `Accept-Language` is deliberately not consulted. It carries the phone's OS
 * language, so an Arabic app installed on an English handset would answer in
 * English. Only a deliberate choice counts: the `X-Locale` header, or the
 * language stored on the signed-in user.
 */
class SetApiLocale
{
    /**
     * @var list<string>
     */
    public const SUPPORTED = ['ar', 'en'];

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('api/*')) {
            App::setLocale($this->resolveLocale($request));
        }

        return $next($request);
    }

    private function resolveLocale(Request $request): string
    {
        $candidates = [
            $request->header('X-Locale'),
            $request->user()?->language,
            config('app.api_locale'),
        ];

        foreach ($candidates as $candidate) {
            $locale = $this->normalise($candidate);

            if ($locale !== null) {
                return $locale;
            }
        }

        return self::SUPPORTED[0];
    }

    /**
     * Reduce a header or a stored preference to a locale this app can answer in;
     * `ar-SA` and `AR` both mean Arabic.
     */
    private function normalise(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        $locale = strtolower(str_replace('_', '-', trim($value)));
        $locale = explode('-', $locale)[0];

        return in_array($locale, self::SUPPORTED, true) ? $locale : null;
    }
}
