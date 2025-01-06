<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\Response;
use App\Repositories\UserRepository;
use App\Http\Requests\{ForgetRequest, LoginRequest, PasswordRequest, ProfileRequest, RegisterRequest};
use App\Models\CodeVerification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public $userRepository;

    public function __construct(UserRepository $userResponse){
        $this->userRepository = $userResponse;
        $this->middleware('auth:api', ['except' => ['login', 'register', 'verifyEmail']]);
    }

    /**
     * Connexion d'un utilisateur
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
        try{
            $response = $this->userRepository->register($request);
            return response()->json($response);
        }catch(\Exception $e){
            logger()->error('Une erreur lors de l\'inscription  : ' . $e->getMessage());
            return response()->json(['error' => 'Une erreur s\'est produite.'], Response::HTTP_BAD_REQUEST);
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
            ->where(['code' => $request->code, 'status' => 1])
            ->where('created_at', '<=', now()->subMinutes(2))
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
        try {
            $this->userRepository->updateProfile($request);
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
