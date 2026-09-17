<?php

namespace App\Providers;

use App\Constants\ResponseMessages;
use App\Http\Helpers\HttpCodes;
use Illuminate\Routing\ResponseFactory;
use Illuminate\Support\ServiceProvider;

class ResponseServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * Both macros run the message through the translator, so callers pass a
     * translation key from lang/*\/api.php and the client is answered in its own
     * language. A plain sentence with no matching key is passed through unchanged.
     */
    public function boot(ResponseFactory $factory)
    {
        $factory->macro('sendResponse', function ($data = false, $message = '', array $replace = []) use ($factory) {

            $format = [
                'state' => true,
                'code' => HttpCodes::OK,
                'message' => ResponseMessages::translate($message, $replace),
            ];

            $format['data'] = $data ?? [];

            $format = ResponseServiceProvider::withDebug($format);

            return $factory->make($format);
        });

        $factory->macro('sendError', function ($code, $message = '', $data = [], array $replace = []) use ($factory) {

            $false = [
                'state' => false,
                'code' => $code,
                'message' => ResponseMessages::translate($message, $replace),
            ];

            if ($data) {
                $false['errors'] = $data;
            }

            $false = ResponseServiceProvider::withDebug($false);

            return $factory->make($false, $code);
        });
    }

    /**
     * Attach the debug payload when the app is configured to send one.
     *
     * Read from config, not env: env() returns null once config:cache has run.
     * The debug_request() helper is not defined in this application, so the call
     * is guarded rather than left to fatal the moment the flag is switched on.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public static function withDebug(array $payload): array
    {
        if (config('app.full_system_debug') && function_exists('debug_request')) {
            $payload['debug'] = debug_request();
        }

        return $payload;
    }
}
