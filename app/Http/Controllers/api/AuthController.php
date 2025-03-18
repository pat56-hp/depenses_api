<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\Response;
use App\Repositories\UserRepository;
use App\Http\Requests\{ForgetRequest, LoginRequest, PasswordRequest, ProfileRequest, RegisterRequest, ResetPassword};
use App\Models\CodeVerification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use OpenApi\Annotations as OA;

class AuthController extends Controller
{
    public $userRepository;

    public function __construct(UserRepository $userResponse){
        $this->userRepository = $userResponse;
        $this->middleware('auth:api', ['except' => ['login', 'register', 'verifyEmail', 'verifyCode', 'ResetPassword']]);
    }

    /**
     * @OA\Post(
     *     path="/api/login",
     *     summary="Authentification utilisateur",
     *     tags={"Authentification"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="secret")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Connexion réussie",
     *         @OA\JsonContent(
     *             @OA\Property(property="token", type="string", example="eyJhbGciOiJIUzI1NiIsInR5c...")
     *         )
     *     ),
     *     @OA\Response(
     *         response=406,
     *         description="Email ou mot de passe incorrect",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Email ou mot de passe incorrect")
     *         )
     *     )
     * )
     */
    public function login(LoginRequest $request)
    {
        $credentials = request(['email', 'password']);

        if (!$token = auth('api')->attempt($credentials)) {
            return response()->json([
                'message' => 'Email ou mot de passe incorrect'
            ], Response::HTTP_NOT_ACCEPTABLE);
        }

        $response = $this->userRepository->responseWithToken($token);
        return response()->json($response);
    }

    /**
     * Inscription d'un utilisateur
     */
    public function register(RegisterRequest $request)
    {  
        logger()->info(json_encode($request->all()));
        $data = $request->only('name', 'email', 'password', 'type');
        try{
            $response = $this->userRepository->register($data);
            return response()->json($response);
        }catch(\Exception $e){
            logger()->error('Une erreur lors de l\'inscription  : ' . $e->getMessage());
            return response()->json(['error' => 'Une erreur s\'est produite.'], Response::HTTP_NOT_ACCEPTABLE);
        }
    }

    /**
     * Return auth informations
     */
    public function me()
    {
        return response()->json(auth('api')->user());
    }

    //Verification de l'email et génération du code
    public function verifyEmail(Request $request){
        $validator = Validator::make($request->all(), [
            'email' => 'required|email'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Oups, une erreur dans le formulaire',
                'data' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            return response()->json($this->userRepository->verifyEmail($request->email));
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], Response::HTTP_NOT_ACCEPTABLE);
        }
    }

    //Verification du envoyé par mail
    public function verifyCode(ForgetRequest $request){
        //On recupere le code de l'utilisateur et qui n'a pas dépassé 2 minutes apres la génération de celui-ci
        $code = CodeVerification::whereHas('user', fn($q) => $q->whereEmail($request->email))
            ->where(['code' => $request->code, 'status' => 0])
            ->where('created_at', '>=', now()->subMinutes(5))
            ->first();

        if (!empty($code)) {
            //On passe tous les codes de l'utilisateur en utilisés
            CodeVerification::whereHas('user', fn($q) => $q->whereEmail($request->email))->update(['status' => 1]);

            return response()->json([
                'message' => "Le code est valide"
            ]); 
        }

        return response()->json([
            'message' => "Désolé, ce code est invalide."
        ], Response::HTTP_NOT_ACCEPTABLE); 
    }

    //Valider la reinitialisation du code
    public function ResetPassword(ResetPassword $request){
        $data = $request->only('email', 'password');
        try{
            return response()->json($this->userRepository->resetPassword($data));
        }catch(\Exception $e){
            return response()->json([
                'message' => $e->getMessage()
            ], Response::HTTP_NOT_ACCEPTABLE);
        }      
    }

    /**
     * Logout user
     */
    public function logout()
    {
        auth('api')->logout();
        return response()->json(['message' => 'Déconnexion réussie']);
    }

    /**
     * @param ProfileRequest $request
     * Modification du profil d'u utilisateur
     * @return JsonResponse
     */
    public function updateProfile(ProfileRequest $request){
        $data = $request->only('name');
        try {
            $this->userRepository->updateProfile($data);
            return response()->json([
                'data' => auth('api')->user(),
                'message' => 'Profile modifié avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'data' => $e->getMessage(),
                'message' => 'Oups, une erreur s\'est produite'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update password of customer and prestataire
     * @param Request $request
     * @return JsonResponse
     */
    public function updatePassword(PasswordRequest $request){
        
        //Verification du mot de passe actuel
        if (!Hash::check($request->oldpassword, auth('api')->user()->password)){
            return response()->json([
                'message' => 'Votre mot de passe actuel est invalide'
            ], Response::HTTP_NOT_ACCEPTABLE);
        }else{
            auth('api')->user()->update([
                'password' => Hash::make($request->newpassword)
            ]);

            return response()->json([
                'message' => 'Mot de passe modifié avec succès'
            ]);
        }
    }
}
