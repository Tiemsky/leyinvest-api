<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\{
    RegisterStepOneRequest,
    RegisterStepTwoRequest,
    LoginRequest,
    VerifyOtpRequest,
    ForgotPasswordRequest,
    ResetPasswordRequest
};
use App\Http\Resources\AuthUserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

 /**
 * @OA\Tag(
 *     name="Authentification",
 *     description="Endpoints de gestion de l'inscription, connexion, OTP, mot de passe et profil utilisateur"
 * )
 */
class AuthController extends Controller
{

    public function __construct(
        private AuthService $authService
    ) {}

    /**
     * Étape 1 : Inscription - Nom, Prénom, Email
    */
  /**
  * @OA\Post(
  *     path="/api/v1/auth/register",
  *     summary="Étape 1 - Inscription : Nom, Prénom, Email",
  *     tags={"Authentification"},
  *     @OA\RequestBody(
  *         required=true,
  *         @OA\JsonContent(
  *             required={"nom", "prenoms", "email"},
  *             @OA\Property(property="nom", type="string", example="Doe"),
  *             @OA\Property(property="prenoms", type="string", example="John"),
  *             @OA\Property(property="email", type="string", format="email", example="john.doe@example.com")
  *         )
  *     ),
  *     @OA\Response(
  *         response=201,
  *         description="Inscription initiée avec succès",
  *         @OA\JsonContent(
  *             @OA\Property(property="success", type="boolean", example=true),
  *             @OA\Property(property="message", type="string", example="Inscription initiée. Un code de vérification a été envoyé à votre email."),
  *             @OA\Property(property="data", type="object",
  *                 @OA\Property(property="user", ref="#/components/schemas/StepOneAuthResource"),
  *                 @OA\Property(property="next_step", type="string", example="verify_otp")
  *             )
  *         )
  *     )
  * )
  */
    public function registerStepOne(RegisterStepOneRequest $request): JsonResponse
    {
        $user = $this->authService->registerStepOne($request->validated());
        return response()->json([
            'success' => true,
            'message' => 'Inscription initiée. Un code de vérification a été envoyé à votre email.',
            'data' => [
                'user' => new AuthUserResource($user),
                'next_step' => 'verify_otp',
            ],
        ], 201);
    }

    /**
     * Vérifier le code OTP pour l'inscription
     */
    /**
  * @OA\Post(
  *     path="/api/v1/auth/verify-email",
  *     summary="Vérifier le code OTP d'inscription",
  *     tags={"Authentification"},
  *     @OA\RequestBody(
  *         required=true,
  *         @OA\JsonContent(
  *             required={"email", "otp"},
  *             @OA\Property(property="email", type="string", format="email", example="john.doe@example.com"),
  *             @OA\Property(property="otp", type="string", example="123456")
  *         )
  *     ),
  *     @OA\Response(
  *         response=200,
  *         description="OTP vérifié avec succès",
  *         @OA\JsonContent(
  *             @OA\Property(property="success", type="boolean", example=true),
  *             @OA\Property(property="message", type="string", example="Email vérifié avec succès."),
  *             @OA\Property(property="data", type="object",
  *                 @OA\Property(property="user", ref="#/components/schemas/StepOneAuthResource"),
  *                 @OA\Property(property="next_step", type="string", example="registration_on_progress")
  *             )
  *         )
  *     )
  * )
   */
    public function verifyRegistrationOtp(VerifyOtpRequest $request): JsonResponse
    {
        $request->validated();
        $user = $this->authService->verifyRegistrationOtp(
            $request->input('email'),
            $request->input('otp')
        );

        return response()->json([
            'success' => true,
            'message' => 'Email vérifié avec succès. Veuillez compléter votre inscription.',
            'data' => [
                'user' => new AuthUserResource($user),
                'next_step' => 'complete_registration',
            ],
        ]);
    }

    /**
     * Étape 2 : Compléter l'inscription - Mot de passe, Pays, etc.
     */
    /**
  * @OA\Post(
  *     path="/api/v1/auth/complete-profile",
  *     summary="Étape 2 - Compléter l'inscription",
  *     tags={"Authentification"},
  *     @OA\RequestBody(
  *         required=true,
  *         @OA\JsonContent(
  *             required={"email", "password", "country_id", "password_confirmation"},
  *             @OA\Property(property="email", type="string", format="email", example="john.doe@example.com"),
  *             @OA\Property(property="password", type="string", format="password", example="password123"),
  *             @OA\Property(property="country_id", type="int",  example="1"),
 *              @OA\Property(property="age", type="integer", nullable=true, example=30),
 *              @OA\Property(property="genre", type="string", nullable=true, example="Masculin"),
 *              @OA\Property(property="situation_professionnelle", type="string", nullable=true, example="Salarié"),
 *              @OA\Property(property="numero", type="string", nullable=true, example="+2250707070707"),
 *              @OA\Property(property="whatsapp", type="string", nullable=true, example="+2250707070707"),
  *         )
  *     ),
  *     @OA\Response(
  *         response=201,
  *         description="Inscription complétée avec succès",
  *         @OA\JsonContent(
  *             @OA\Property(property="success", type="boolean", example=true),
  *             @OA\Property(property="message", type="string", example="Inscription complétée avec succès."),
  *             @OA\Property(property="data", type="object",
  *                 @OA\Property(property="user", ref="#/components/schemas/AuthUserResource")
  *             )
  *         )
  *     )
  * )
  *
  **/
    public function registerStepTwo(RegisterStepTwoRequest $request): JsonResponse
    {
        $user = $this->authService->registerStepTwo($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Inscription complétée avec succès. Vous pouvez maintenant vous connecter.',
            'data' => [
                'user' => new AuthUserResource($user),
            ],
        ], 201);
    }

    /**
     * Connexion utilisateur
     */
     /**
  * @OA\Post(
  *     path="/api/v1/auth/login",
  *     summary="Connexion utilisateur",
  *     tags={"Authentification"},
  *     @OA\RequestBody(
  *         required=true,
  *         @OA\JsonContent(ref="#/components/schemas/LoginRequest")
  *     ),
  *     @OA\Response(
  *         response=200,
  *         description="Connexion réussie",
  *         @OA\JsonContent(
  *             @OA\Property(property="success", type="boolean", example=true),
  *             @OA\Property(property="message", type="string", example="Connexion réussie."),
  *             @OA\Property(property="data", type="object",
  *                 @OA\Property(property="user", ref="#/components/schemas/AuthUserResource"),
  *                 @OA\Property(property="token", type="string", example="1|eyJhbGciOi...")
  *             )
  *         )
  *     ),
  *     @OA\Response(response=401, description="Identifiants invalides")
  * )
  *
  **/
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login(
            $request->validated(),
            $request->input('device_name', 'api')
        );

        return response()->json([
            'success' => true,
            'message' => 'Connexion réussie.',
            'data' => [
                'user' => new AuthUserResource($result['user']),
                'token' => $result['token'],
            ],
        ]);
    }

    /**
     * Renvoyer le code OTP
     */
    /**
 * @OA\Post(
 *     path="/api/v1/auth/resend-code",
 *     tags={"Authentification"},
 *     summary="Renvoie le code OTP pour vérification",
 *     description="Renvoyer un code OTP pour un utilisateur non encore vérifié ou inscrit",
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(ref="#/components/schemas/ResendOtpRequest")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="OTP renvoyé avec succès",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string", example="Le code OTP a été renvoyé sur votre email.")
 *         )
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Erreur de validation (compte déjà vérifié ou email inexistant)",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(
 *                 property="errors",
 *                 type="object",
 *                 @OA\Property(
 *                     property="email",
 *                     type="array",
 *                     @OA\Items(type="string", example="Ce compte est déjà vérifié et complété.")
 *                 )
 *             )
 *         )
 *     )
 * )
 */
    public function resendOtp(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
        ]);

        $this->authService->resendOtp($request->input('email'));

        return response()->json([
            'success' => true,
            'message' => 'Un nouveau code de vérification a été envoyé à votre email.',
        ]);
    }

    /**
     * Demander la réinitialisation du mot de passe
     */
     /**
  * @OA\Post(
  *     path="/api/v1/auth/forgot-password",
  *     summary="Demander la réinitialisation du mot de passe",
  *     tags={"Authentification"},
  *     @OA\RequestBody(
  *         required=true,
  *         @OA\JsonContent(
  *             required={"email"},
  *             @OA\Property(property="email", type="string", format="email", example="john.doe@example.com")
  *         )
  *     ),
  *     @OA\Response(
  *         response=200,
  *         description="Email de réinitialisation envoyé"
  *     )
  * )
  *
  **/
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $this->authService->forgotPassword($request->input('email'));

        return response()->json([
            'success' => true,
            'message' => 'Un code de réinitialisation a été envoyé à votre email.',
        ]);
    }


    /**
  * @OA\Post(
  *     path="/api/v1/auth/reset-password",
  *     summary="Réinitialiser le mot de passe via OTP",
  *     tags={"Authentification"},
  *     @OA\RequestBody(
  *         required=true,
  *         @OA\JsonContent(
  *             required={"email", "otp", "password"},
  *             @OA\Property(property="email", type="string", example="john.doe@example.com"),
  *             @OA\Property(property="otp", type="string", example="123456"),
  *             @OA\Property(property="password", type="string", format="password", example="newpassword123")
  *         )
  *     ),
  *     @OA\Response(
  *         response=200,
  *         description="Mot de passe réinitialisé avec succès",
  *         @OA\JsonContent(
  *             @OA\Property(property="success", type="boolean", example=true),
  *             @OA\Property(property="message", type="string", example="Mot de passe réinitialisé avec succès."),
  *             @OA\Property(property="data", type="object",
  *                 @OA\Property(property="user", ref="#/components/schemas/AuthUserResource")
  *             )
  *         )
  *     )
  * )
  **/
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $user = $this->authService->resetPassword(
            $request->input('email'),
            $request->input('otp'),
            $request->input('password')
        );

        return response()->json([
            'success' => true,
            'message' => 'Mot de passe réinitialisé avec succès.',
            'data' => [
                'user' => new AuthUserResource($user),
            ],
        ]);
    }

    /**
     * Déconnexion
     */

         /**
  * @OA\Post(
  *     path="/api/v1/auth/logout",
  *     summary="Déconnexion de l'utilisateur courant",
  *     security={{"sanctum": {}}},
  *     tags={"Authentification"},
  *     @OA\Response(response=200, description="Déconnexion réussie")
  * )
  **/
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return response()->json([
            'success' => true,
            'message' => 'Déconnexion réussie.',
        ]);
    }

    /**
     * Déconnexion de tous les appareils
     */
    /**
  * @OA\Post(
  *     path="/api/v1/auth/logout-all",
  *     summary="Déconnexion de tous les appareils",
  *     security={{"sanctum": {}}},
  *     tags={"Authentification"},
  *     @OA\Response(response=200, description="Tous les appareils déconnectés avec succès")
  * )
  *
    **/
    public function logoutAll(Request $request): JsonResponse
    {
        $this->authService->logoutAll($request->user());

        return response()->json([
            'success' => true,
            'message' => 'Déconnexion de tous les appareils réussie.',
        ]);
    }

    /**
     * Obtenir l'utilisateur authentifié
     */
       /**
  * @OA\Get(
  *     path="/api/v1/auth/user",
  *     summary="Obtenir les informations de l'utilisateur connecté",
  *     security={{"sanctum": {}}},
  *     tags={"Authentification"},
  *     @OA\Response(
  *         response=200,
  *         description="Utilisateur connecté",
  *         @OA\JsonContent(
  *             @OA\Property(property="success", type="boolean", example=true),
  *             @OA\Property(property="data", type="object",
  *                 @OA\Property(property="user", ref="#/components/schemas/AuthUserResource")
  *             )
  *         )
  *     )
  * )
  */
    public function user(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'user' => new AuthUserResource($request->user()),
            ],
        ]);
    }

    /**
     * Changer le mot de passe utilisateur
     */
     /**
 * @OA\Post(
 *     path="/api/v1/auth/change-password",
 *     tags={"Authentification"},
 *     summary="Changer le mot de passe",
 *     description="L'utilisateur peut changer son mot de passe en fournissant l'ancien et le nouveau",
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(ref="#/components/schemas/ChangePasswordRequest")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Mot de passe modifié avec succès",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string", example="Le mot de passe a été modifié avec succès.")
 *         )
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Erreur de validation",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(
 *                 property="errors",
 *                 type="object",
 *                 @OA\Property(
 *                     property="current_password",
 *                     type="array",
 *                     @OA\Items(type="string", example="Le mot de passe actuel est incorrect.")
 *                 ),
 *                 @OA\Property(
 *                     property="new_password",
 *                     type="array",
 *                     @OA\Items(type="string", example="Le nouveau mot de passe doit être différent de l'ancien.")
 *                 )
 *             )
 *         )
 *     )
 * )
 */
    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $this->authService->changePassword(
            $request->user(),
            $request->input('current_password'),
            $request->input('new_password')
        );

        return response()->json([
            'success' => true,
            'message' => 'Mot de passe changé avec succès.',
        ]);
    }

    /**
     * Mettre à jour le profil utilisateur
     */


public function updateProfile(Request $request): JsonResponse{
    $request->validate([
        'nom' => ['sometimes', 'string', 'max:255'],
        'prenoms' => ['sometimes', 'string', 'max:255'],
        'country_id' => ['sometimes', 'integer', 'max:100'],
        'numero' => ['sometimes', 'string', 'max:20'],
        'whatsaap' => ['sometimes', 'string', 'max:20'],
        'age' => ['sometimes', 'integer', 'max:100'],
        'situation_professionnelle' => ['sometimes', 'string', 'max:100'],
        'avatar' => ['sometimes', 'image', 'max:2048'], // Max 2MB
    ]);

    $user = $request->user();
    // Update Profile
    $this->authService->updateProfile($user, $request->only(['nom', 'prenomss', 'phone', 'country']));

    // Update Avatar
    if ($request->hasFile('avatar')) {
        $this->authService->updateAvatar($user, $request->file('avatar'));
    }

    return response()->json([
        'success' => true,
        'message' => 'Profil mis à jour avec succès.',
        'data' => [
            'user' => new AuthUserResource($user->fresh()),
        ],
    ]);
}
/**  * Mettre à jour ou supprimer l'avatar de l'utilisateur
  */public function manageAvatar(Request $request): void {
    $user = $request->user();
    // Update Avatar
    if ($request->hasFile('avatar')) {
        $this->authService->updateAvatar($user, $request->file('avatar'));
        return;
    }
}
}
