<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\api\AuthController;
use App\Http\Controllers\api\HistoriqueController;
use App\Http\Controllers\api\ProfileController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group(['prefix' => 'v1'], function (){
    /**
     * @Route of Authentication
     * @Controller AuthController
     */
    Route::controller(AuthController::class)->group(function (){
        Route::post('/login', 'login');
        Route::post('/register', 'register');
        Route::post('/refresh', 'refresh');
        Route::post('/me', 'me');
        Route::post('/verify/email', 'verifyEmail');
        Route::post('/verify/code', 'verifyCode');
        Route::post('/resetPassword', 'ResetPassword');

        Route::post('/profile/update', 'updateProfile');
        Route::post('/profile/update/password', 'updatePassword');
    });

    /**
     * @Route of Historique
     * @Controller HistoriqueController
     */
    Route::controller(HistoriqueController::class)->group(function (){
        Route::get('/historiques', 'index');
        Route::post('/historiques', 'storeOrUpdate');
        Route::delete('/historiques/delete/{id}', 'destroy');
    });
});

