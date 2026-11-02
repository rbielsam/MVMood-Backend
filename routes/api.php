<?php

use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\BloqueoController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\ComentarioController;
use App\Http\Controllers\Api\LikeController;
use App\Http\Controllers\Api\PublicacionesController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Broadcast;


Broadcast::routes(['middleware' => ['auth:sanctum']]);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/email/verify/status', function (Request $request) {
        return response()->json([
            'verified' => $request->user()->hasVerifiedEmail()
        ]);
    });

});

Route::post('/register', [RegisterController::class, 'register']);

Route::post('/login', [LoginController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/home', [PublicacionesController::class, 'index']);
    Route::post('/create', [PublicacionesController::class, 'store']);
    Route::put('/publicaciones/{uuid}', [PublicacionesController::class, 'update']);
    Route::delete('/publicaciones/{uuid}', [PublicacionesController::class, 'eliminar']);
    Route::get('/publicaciones/{uuid}/likes', [PublicacionesController::class, 'likes']);
    Route::get('/publicaciones/{uuid}/comentarios', [PublicacionesController::class, 'comentarios']);
    Route::post('/publicaciones/{uuid}/comentarios', [ComentarioController::class, 'store']);
    Route::delete('/comentarios/{uuid}', [ComentarioController::class, 'eliminar']);
    Route::post('/publicaciones/{uuid}/like', [LikeController::class, 'guardarLike']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/chats', [ChatController::class, 'misChats']);
    Route::get('/chats/{chatId}/mensajes', [ChatController::class, 'mostrarMensajes']);
    Route::post('/chats/enviar', [ChatController::class, 'enviar']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/perfil', [UserController::class, 'index']);
    Route::post('/perfil', [UserController::class, 'updatePerfil']);
    Route::put('/perfil/password', [UserController::class, 'changePassword']);
    Route::get('/users', [UserController::class, 'getUsers']);
    Route::delete('/perfil/delete', [UserController::class, 'deleteUser']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/usuarios/bloqueados', [BloqueoController::class, 'listaBloqueados']);
    Route::post('/usuarios/{uuid}/bloquear', [BloqueoController::class, 'bloquear']);
});
