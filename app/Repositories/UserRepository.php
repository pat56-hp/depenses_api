<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserRepository {

    private $user;

    public function __construct(User $user){
        $this->user = $user;
    }

    //Inscription
    public function register($data){
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
    public function updateProfile($data){
        auth('api')->user()->update([
            'name' => $data['name'],
            'adresse' => $data['adresse'],
        ]);
    }


    //Renvoie des infos de l'user connecté
    public function responseWithToken($token){
        return [
            'access_token' => $token,
            'token_type' => 'bearer',
            'user' => auth('api')->user(),
            'expires_in' => auth('api')->factory()->getTTL() * 60
        ];
    }

}