<?php

// ============================================================================
// bootstrap/app.php - Configuration Laravel 12 Customisée pour l'API
// ============================================================================

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {

        // PRIORITÉ DES MIDDLEWARES (ordre critique !)
        $middleware->priority([
            \App\Http\Middleware\SecureHeaders::class,
            \Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance::class,
            \Illuminate\Http\Middleware\ValidatePostSize::class,
            \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
            \Illuminate\Routing\Middleware\ThrottleRequests::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);

        //  MIDDLEWARE API - SANS EnsureFrontendRequestsAreStateful
        // (Ce middleware force l'utilisation de sessions Laravel, on ne veut PAS ça !)
        $middleware->api(prepend: [
            \App\Http\Middleware\ForceJsonResponse::class,
        ]);

        // CSRF: Désactiver pour toutes les routes API
        $middleware->validateCsrfTokens(except: [
            'api/*',
            'sanctum/csrf-cookie',
        ]);

        // THROTTLE: Rate limiting global (ajusté)
        $middleware->throttleApi('api'); // Utiliser le limiter 'api' défini dans RouteServiceProvider

        // Alias de middlewares personnalisés
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureUserHasRole::class,
            'check.token.expiration' => \App\Http\Middleware\CheckTokenExpiration::class,
        ]);
    })

     // ============================================================================
    // GESTION DES EXCEPTIONS - Format JSON pour API
    // ============================================================================

    ->withExceptions(function (Exceptions $exceptions): void {

        /**
         * Helper pour uniformiser la réponse d'erreur API
         */
        $apiResponse = function (string $message, int $code, array $extra = []) {
            return response()->json(array_merge([
                'success' => false,
                'message' => $message,
                'code' => $code,
                'timestamp' => now()->toIso8601String(), // Format ISO pour Nuxt/JS
            ], $extra), $code);
        };

        /**
         * Forcer le rendu JSON pour l'API
         */
        $exceptions->shouldRenderJsonWhen(fn (Request $request) => $request->is('api/*') || $request->expectsJson());

        /**
         * Erreur 422 - Validation (Optimisé pour Nuxt)
         */
        $exceptions->render(function (ValidationException $e, Request $request) use ($apiResponse) {
            if ($request->is('api/*')) {
                // On récupère uniquement le premier message d'erreur pour le "message" global
                $firstErrorMessage = collect($e->errors())->flatten()->first();

                return $apiResponse($firstErrorMessage ?? 'Données invalides.', 422, [
                    'errors' => $e->errors(),
                ]);
            }
        });

        /**
         * Erreur 401 - Non authentifié
         */
        $exceptions->render(function (AuthenticationException $e, Request $request) use ($apiResponse) {
            if ($request->is('api/*')) {
                return $apiResponse('Session expirée ou non autorisée. Veuillez vous reconnecter.', 401);
            }
        });

        /**
         * Erreur 404 - Ressource ou Route non trouvée
         */
        $exceptions->render(function (NotFoundHttpException $e, Request $request) use ($apiResponse) {
            if ($request->is('api/*')) {
                $isModel = $e->getPrevious() instanceof ModelNotFoundException;
                $message = $isModel ? 'La ressource demandée n\'existe pas.' : 'L\'endpoint demandé est introuvable.';

                return $apiResponse($message, 404, [
                    'requested_url' => $request->fullUrl(),
                ]);
            }
        });

        /**
         * Erreur 403 - Permission refusée
         */
        $exceptions->render(function (\Illuminate\Auth\Access\AuthorizationException $e, Request $request) use ($apiResponse) {
            if ($request->is('api/*')) {
                return $apiResponse('Vous n\'avez pas les droits nécessaires pour effectuer cette action.', 403);
            }
        });

        /**
         * Erreur 429 - Trop de requêtes
         */
        $exceptions->render(function (\Illuminate\Http\Exceptions\ThrottleRequestsException $e, Request $request) use ($apiResponse) {
            if ($request->is('api/*')) {
                return $apiResponse('Trop de tentatives. Veuillez patienter un instant.', 429, [
                    'retry_after_seconds' => $e->getHeaders()['Retry-After'] ?? 60,
                ]);
            }
        });

        /**
         * Catch-all - Erreur 500 et autres
         */
        $exceptions->render(function (Throwable $e, Request $request) use ($apiResponse) {
            if ($request->is('api/*')) {
                report($e); // Log l'erreur en interne

                $statusCode = ($e instanceof HttpException) ? $e->getStatusCode() : 500;
                $message = app()->isProduction() && $statusCode >= 500
                           ? 'Une erreur technique est survenue.'
                           : $e->getMessage();

                return $apiResponse($message, $statusCode, [
                    'debug' => app()->isProduction() ? null : [
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => collect($e->getTrace())->take(5),
                    ],
                ]);
            }
        });

    })->create();
