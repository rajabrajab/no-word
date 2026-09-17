<?php

use App\Constants\ResponseMessages;
use App\Http\Middleware\SetApiLocale;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Global rather than api-group: a request that matches no route never
        // reaches group middleware, and its "not found" has to be translated too.
        $middleware->prepend(SetApiLocale::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (ValidationException $e, $request) {
            if ($request->expectsJson()) {
                $errors = $e->errors();
                $firstErrorMessage = collect($errors)->flatten()->first();

                return response()->sendError(422, $firstErrorMessage, $e->errors());
            }
        });

        $exceptions->render(function (NotFoundHttpException $e, $request) {
            if ($request->expectsJson()) {

                return response()->sendError(404, ResponseMessages::NOT_FOUND);
            }
        });

        $exceptions->render(function (AuthenticationException $e, $request) {
            if ($request->expectsJson()) {
                return response()->sendError(440, ResponseMessages::SESSION_EXPIRED);
            }
        });

        $exceptions->render(function (Throwable $e, $request) {
            if ($request->expectsJson()) {
                $message = ResponseMessages::GENERAL_FAILURE;

                if (config('app.debug')) {
                    $message = $e->getMessage();
                }

                return response()->sendError(500, $message);
            }
        });
    })->create();
