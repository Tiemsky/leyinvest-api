<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *      version="1.0.0",
 *      title="LeyInvest API Documentation",
 *      description="Documentation complète de l'API LeyInvest. Cette API permet la gestion des utilisateurs, des actions suivies (follow/unfollow), ainsi que des opérations financières automatisées (take profit, stop loss).",
 *
 *      @OA\Contact(
 *          email="tiafranck@leycom.ci",
 *          name="API Support"
 *      ),
 *
 *      @OA\License(
 *          name="MIT",
 *          url="https://opensource.org/licenses/MIT"
 *      )
 * ),
 *
 * @OA\Server(
 *      url=L5_SWAGGER_CONST_HOST,
 *      description="Serveur principal de l'API LeyInvest"
 * ),
 *
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Entrez votre token Bearer obtenu via l'authentification Sanctum (ex: 'Bearer {token}')"
 * )
 */
abstract class Controller
{
    //
}
