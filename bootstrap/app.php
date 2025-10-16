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
    | Déclare les fichiers de routes web, api et console.
    | La route de santé '/up' permet au load balancer de vérifier l’état du serveur.
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
    | Enregistre et configure les middlewares globaux, groupes et alias.
    | Bonnes pratiques : API stateful pour SPA, alias "verified", "role" et "json".
    */
    ->withMiddleware(function (Middleware $middleware): void {

        // Middleware global pour les requêtes API
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            \App\Http\Middleware\ForceJsonResponse::class, // Force JSON responses
        ]);

        // Alias custom pour plus de lisibilité
        $middleware->alias([
            'verified' => \App\Http\Middleware\EnsureEmailIsVerified::class,
            'role' => \App\Http\Middleware\EnsureUserHasRole::class,
        ]);
    })

    /*
    |--------------------------------------------------------------------------
    | Exception Handling Configuration
    |--------------------------------------------------------------------------
    | Centralisation des erreurs et personnalisation des réponses JSON.
    | Objectif : ne jamais retourner de HTML à une requête API.
    */
    ->withExceptions(function (Exceptions $exceptions): void {

        // 🔸 Toutes les requêtes API doivent retourner du JSON
        $exceptions->shouldRenderJsonWhen(function (Request $request, Exception $e) {
            return $request->is('api/*') || $request->expectsJson();
        });

        // 🔸 404 Not Found
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

        // 🔸 405 Method Not Allowed
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

        // 🔸 401 Unauthorized / AuthenticationException
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Non authentifié. Veuillez vous connecter pour accéder à cette ressource.',
                    'code' => 401,
                    'timestamp' => now(),
                ], 401);
            }
            return redirect()->guest(route('login'));
        });

        // 🔸 HTTP Exception personnalisée (403, 500, etc.)
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

        // 🔸 Gestion générique des exceptions non capturées
        $exceptions->render(function (Exception $e, Request $request) {
            if ($request->is('api/*')) {
                report($e); // journalisation
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
