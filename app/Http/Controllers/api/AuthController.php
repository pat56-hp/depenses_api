<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\Response;
use App\Repositories\UserRepository;
use App\Http\Requests\{LoginRequest, PasswordRequest, ProfileRequest, RegisterRequest};
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public $userRepository;

    public function __construct(UserRepository $userResponse){
        $this->userRepository = $userResponse;
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
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

        return $this->userRepository->responseWithToken($token);
    }

    /**
     * Inscription d'un utilisateur
     */
    public function register(RegisterRequest $request)
    {
        try{
            $response = $this->userRepository->register($request);
            return $response;
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
