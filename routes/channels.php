<?php
use App\Models\Chat;
use Illuminate\Support\Facades\Broadcast;


Broadcast::routes(['middleware' => ['auth:sanctum']]);

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

//Broadcast::channel('chat.{chatUuid}', function ($user, $chatUuid) {
    // IMPORTANTE: $user es el usuario autenticado actualmente
//    return true;
//});

Broadcast::channel('chat.{chatId}', function ($user, $chatId) {
    // IMPORTANTE: $user es el usuario autenticado actualmente
    return Chat::where('id', $chatId)
               ->whereHas('usuarios', function($q) use ($user) {
                   $q->where('user_id', $user->id);
               })->exists();
});