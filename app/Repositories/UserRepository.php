<?php

namespace App\Repositories;

use App\Http\Resources\UserResource;
use App\Models\CodeVerification;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Hash;

class UserRepository {

    private $user;

    public function __construct(User $user){
        $this->user = $user;
    }

    //Inscription
    public function register(Array $data){
        //Creation du user
        $this->user->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'statut' => 1, // Actif
            'image' => '/images/user.png',
        ]);

        //Recuperation des credentials pour creation du token
        $credentials = ['email' => $data['email'], 'password' => $data['password']];
        $token = auth('api')->attempt($credentials);
        return $this->responseWithToken($token);
    }

    //Modification des informations du profil d'un utilisateur
    public function updateProfile(Array $data){
        auth('api')->user()->update([
            'name' => $data['name'],
            'adresse' => $data['adresse'],
        ]);
    }

    //Verification de l'email de l'utilisateur
    public function verifyEmail(String $email){
        if ($user = $this->user->where('email', $email)->first()) {
            //Creation du code de verification et envoie du code par mail à l'utilisateur
            $code = CodeVerification::generate();
            $codeGenerate = $user->code()->create([
                'code' => $code,
                'status' => 0,
            ]);

            //Met à jour les autres codes en utilisés
            $user->code()
                ->where('id', '!=', $codeGenerate->id)
                ->update(['status' => 1]);

            return [
                'message' => 'Utilisateur trouvé et code généré.'
            ];
        }

        throw new Exception('Désolé, cette adresse email ne correspond à aucun utilisateur !');
    }

    //Reinitialiser le mot de passe
    public function resetPassword($data){
        if($user = $this->user->whereEmail($data['email'])->first()){
            $user->update([
                'password' => Hash::make($data['password'])
            ]);

            return [
                'message' => 'Votre mot de passe a été réinitialisé avec succès'
            ];
        }

        throw new Exception('Impossible de trouver l\'utilisateur de cette adresse email');
    }


    //Renvoie des infos de l'user connecté
    public function responseWithToken(String $token){
        return [
            'access_token' => $token,
            'token_type' => 'bearer',
            'user' => auth('api')->user(),
            'expires_in' => auth('api')->factory()->getTTL() * 60
        ];
    }

}