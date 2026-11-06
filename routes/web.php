<?php

use App\Events\EnviarMensaje;
use App\Models\Mensaje;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/email/verify/{id}/{hash}', function (Request $request, $id, $hash) {

    $user = User::findOrFail($id);

    if ($user->hasVerifiedEmail()) {
        return redirect(config('app.frontend_url').'/?verified=already');
    }

    $user->markEmailAsVerified();

    return redirect(config('app.frontend_url').'/?verified=1');

})->middleware('signed')->name('verification.verify');

// Route::get('/test-pusher', function () {
//    $mensaje = Mensaje::first();
//    event(new EnviarMensaje($mensaje));
//    return "Evento enviado correctamente";
// });
