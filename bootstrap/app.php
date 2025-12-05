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
         * ✅ CORS MIDDLEWARE - DOIT ÊTRE EN PREMIER
         * CRITIQUE : HandleCors doit traiter les requêtes OPTIONS (preflight)
         * AVANT tout autre middleware qui pourrait rediriger
         */
        $middleware->prepend(\Illuminate\Http\Middleware\HandleCors::class);

        /**
         * ✅ API MIDDLEWARE GROUP
         * - EnsureFrontendRequestsAreStateful : Pour Sanctum SPA authentication
         * - ForceJsonResponse : Force les réponses JSON pour l'API
         */
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            \App\Http\Middleware\ForceJsonResponse::class,
        ]);

        /**
         * ✅ MIDDLEWARE ALIASES
         */
        $middleware->alias([
            'verified' => \App\Http\Middleware\EnsureEmailIsVerified::class,
            'role' => \App\Http\Middleware\EnsureUserHasRole::class,

            // Custom middleware pour les tokens
            'check.token.expiration' => \App\Http\Middleware\CheckTokenExpiration::class,
        ]);

        /**
         * 🔥 CRITIQUE : Empêcher les redirections automatiques pour l'API
         * Sans cela, les requêtes non-authentifiées vers /api/* seront redirigées
         * vers route('login'), ce qui casse le preflight CORS
         */
        $middleware->redirectGuestsTo(function (Request $request) {
            // Si c'est une requête API, retourner JSON 401 au lieu de rediriger
            if ($request->is('api/*')) {
                abort(response()->json([
                    'status' => 'error',
                    'message' => 'Non authentifié. Veuillez vous connecter.',
                    'code' => 401,
                    'timestamp' => now(),
                ], 401));
            }

            // Pour les requêtes web, rediriger vers login
            return route('login');
        });

        /**
         * ✅ Configuration API stateful (pour Sanctum SPA)
         */
        $middleware->statefulApi();

        /**
         * ✅ Limitation du trafic API avec le rate limiter par défaut
         */
        $middleware->throttleApi();
    })

    /*
    |--------------------------------------------------------------------------
    | Exception Handling Configuration
    |--------------------------------------------------------------------------
| Utilise des réponses JSON normalisées pour l'API.
*/
    ->withExceptions(function (Exceptions $exceptions): void {

        /**
         * ✅ Forcer les réponses JSON pour toutes les requêtes API
         */
        $exceptions->shouldRenderJsonWhen(function (Request $request, Throwable $e) {
            // Rendre JSON si c'est une requête API ou si le client l'attend explicitement
            return $request->is('api/*') || $request->expectsJson();
        });

        /**
         * 🔥 CRITIQUE : Erreur 404 - Ressource non trouvée (Model Binding)
         * Capte les 404 spécifiques à la DB (e.g., /api/posts/999) pour un message précis.
         */
        $exceptions->render(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ressource non trouvée. L\'identifiant spécifié pour la ressource [' . $e->getModel() . '] n\'existe pas.',
                    'code' => 404,
                    'timestamp' => now(),
                ], 404);
            }
        });

        /**
         * 🔥 CRITIQUE : Erreur 404 - Route non trouvée (Route Inexistante)
         * Doit être placé APRÈS ModelNotFoundException, gère les autres 404.
         */
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Route non trouvée. Vérifiez l\'URL et la méthode HTTP.',
                    'requested_url' => $request->fullUrl(),
                    'code' => 404,
                    'timestamp' => now(),
                ], 404);
            }
        });

        /**
         * ✅ Erreur 405 - Méthode HTTP non autorisée
         */
        $exceptions->render(function (MethodNotAllowedHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Méthode HTTP non autorisée pour cette route.',
                    'allowed_methods' => $e->getHeaders()['Allow'] ?? 'N/A',
                    'code' => 405,
                    'timestamp' => now(),
                ], 405);
            }
        });

        /**
         * 🔥 CRITIQUE : Erreur 401 - Non authentifié
         */
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Non authentifié. Veuillez vous connecter pour accéder à cette ressource.',
                    'code' => 401,
                    'timestamp' => now(),
                ], 401);
            }

            // Pour les requêtes web, rediriger vers login
            return redirect()->guest(route('login'));
        });

        /**
         * ✅ Erreur 403 - Accès interdit (Autorisation/Permissions)
         */
        $exceptions->render(function (\Illuminate\Auth\Access\AuthorizationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès interdit. Vous n\'avez pas les permissions nécessaires.',
                    'code' => 403,
                    'timestamp' => now(),
                ], 403);
            }
        });

        /**
         * ✅ Erreur 422 - Validation échouée
         */
        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur de validation des données.',
                    'errors' => $e->errors(),
                    'code' => 422,
                    'timestamp' => now(),
                ], 422);
            }
        });

        /**
         * ✅ Erreur 429 - Trop de requêtes (Rate Limit)
         */
        $exceptions->render(function (\Illuminate\Http\Exceptions\ThrottleRequestsException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Trop de requêtes. Veuillez patienter avant de réessayer.',
                    'retry_after' => $e->getHeaders()['Retry-After'] ?? 60,
                    'code' => 429,
                    'timestamp' => now(),
                ], 429);
            }
        });

        /**
         * ✅ Erreur 500 - Erreur serveur générique
         * Ce gestionnaire capture toutes les exceptions non traitées.
         * Bonnes pratiques : masquer les détails en production.
         */
        $exceptions->render(function (Throwable $e, Request $request) {
            if ($request->is('api/*')) {
                // Loguer l'erreur pour investigation (important)
                report($e);

                $statusCode = $e instanceof HttpException ? $e->getStatusCode() : 500;

                return response()->json([
                    'success' => false,
                    'message' => app()->isProduction() && $statusCode >= 500
                        ? 'Une erreur interne est survenue. Veuillez réessayer plus tard.'
                        : $e->getMessage(),
                    // Les détails (file, line, trace) ne doivent être affichés qu'en local
                    'details' => app()->isProduction() ? null : [
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => collect($e->getTrace())->take(10)->all(),
                    ],
                    'code' => $statusCode,
                    'timestamp' => now(),
                ], $statusCode);
            }
        });
    })->create();
