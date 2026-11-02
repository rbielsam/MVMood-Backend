<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    public function register(Request $request){

        $validated = $request->validate([
            'nickname' => 'required|string|max:255',
            'email' => 'required|string|email|ends_with:@institutmvm.cat|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'nickname.required' => 'El nombre de usuario es obligatorio',
            'nickname.max' => 'El nombre de usuario debe contener menos de 255 caracteres',
            'nickname.unique' => 'Este usuario ya existe',
            'email.required' => 'El email es obligatorio',
            'email.email' => 'El email debe ser valido',
            'email.ends_with' => 'El email debe acabar con "@institutmvm.cat"',
            'email.unique' => 'Ya hay una cuenta con este email',
            'password.required' => 'La contraseña es requerida',
            'password.min'  => 'La contraseña debe tener al menos 8 caracteres',
            'password.confirmed' => 'Las contraseñas no coinciden',
        ]);

        $user = User::create([
            'nickname' => $validated['nickname'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);
        event(new Registered($user));

        return response()->json([
            'message' => 'Usuario creado correctamente',
            'user' => [
                'id' => $user->id,
                'nickname' => $user->nickname,
                'email' => $user->email,
            ],
        ], 201);
    }
}
