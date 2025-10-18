<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthenticationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    /*
    |--------------------------------------------------------------------------
    | Routing Configuration
    |--------------------------------------------------------------------------
    */
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )

    /*
    |--------------------------------------------------------------------------
    | Middleware Configuration
    |--------------------------------------------------------------------------
    */
    ->withMiddleware(function (Middleware $middleware): void {

        /**
         * ✅ GLOBAL MIDDLEWARE
         * Important : CORS doit être appliqué globalement pour toutes les requêtes,
         * surtout celles venant de React (http://localhost:3000 ou app.yks-ci.com)
         */
        $middleware->append(\Illuminate\Http\Middleware\HandleCors::class);

        /**
         * ✅ API MIDDLEWARE GROUP
         * EnsureFrontendRequestsAreStateful : reconnaît les requêtes SPA "stateful"
         * ForceJsonResponse : force le JSON pour toutes les réponses API
         */
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            \App\Http\Middleware\ForceJsonResponse::class,
        ]);

        /**
         * ✅ Alias customs
         */
        $middleware->alias([
            'verified' => \App\Http\Middleware\EnsureEmailIsVerified::class,
            'role' => \App\Http\Middleware\EnsureUserHasRole::class,
        ]);

        /**
         * ✅ Limitation du trafic API (throttling)
         */
        $middleware->throttleApi('api');
    })

    /*
    |--------------------------------------------------------------------------
    | Exception Handling Configuration
    |--------------------------------------------------------------------------
    */
    ->withExceptions(function (Exceptions $exceptions): void {

        $exceptions->shouldRenderJsonWhen(function (Request $request, Exception $e) {
            return $request->is('api/*') || $request->expectsJson();
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Ressource non trouvée.',
                    'code' => 404,
                    'timestamp' => now(),
                ], 404);
            }
        });

        $exceptions->render(function (MethodNotAllowedHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Méthode HTTP non autorisée pour cette route.',
                    'code' => 405,
                    'timestamp' => now(),
                ], 405);
            }
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Non authentifié. Veuillez vous connecter.',
                    'code' => 401,
                    'timestamp' => now(),
                ], 401);
            }
            return redirect()->guest(route('login'));
        });

        $exceptions->render(function (HttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'status' => 'error',
                    'message' => $e->getMessage() ?: 'Erreur HTTP détectée.',
                    'code' => $e->getStatusCode(),
                    'timestamp' => now(),
                ], $e->getStatusCode());
            }
        });

        $exceptions->render(function (Exception $e, Request $request) {
            if ($request->is('api/*')) {
                report($e);
                return response()->json([
                    'status' => 'error',
                    'message' => app()->isProduction()
                        ? 'Une erreur interne est survenue. Veuillez réessayer plus tard.'
                        : $e->getMessage(),
                    'trace' => app()->isProduction() ? null : $e->getTrace(),
                    'code' => 500,
                    'timestamp' => now(),
                ], 500);
            }
        });
    })
    ->create();
