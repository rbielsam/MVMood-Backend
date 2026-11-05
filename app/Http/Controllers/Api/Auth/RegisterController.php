<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserWhitelist;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class RegisterController extends Controller
{
    public function register(Request $request){

        $validated = $request->validate([
            'nickname' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'nickname.required' => 'El nombre de usuario es obligatorio',
            'nickname.max' => 'El nombre de usuario debe contener menos de 255 caracteres',
            'nickname.unique' => 'Este usuario ya existe',
            'email.required' => 'El email es obligatorio',
            'email.email' => 'El email debe ser valido',
            'email.unique' => 'Ya hay una cuenta con este email',
            'password.required' => 'La contraseña es requerida',
            'password.min'  => 'La contraseña debe tener al menos 8 caracteres',
            'password.confirmed' => 'Las contraseñas no coinciden',
        ]);

        $dominioPermitido = str_ends_with($validated['email'], '@institutmvm.cat');

        $entradaWhitelist = $dominioPermitido
            ? null
            : UserWhitelist::where('email', $validated['email'])->first();

        if (!$dominioPermitido && !$entradaWhitelist) {
            throw ValidationException::withMessages([
                'email' => 'El email debe acabar con "@institutmvm.cat"',
            ]);
        }

        $user = User::create([
            'nickname' => $validated['nickname'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $user->rol = $dominioPermitido ? 'user' : $entradaWhitelist->rol;
        $user->save();

        if ($entradaWhitelist) {
            $entradaWhitelist->used_at = now();
            $entradaWhitelist->save();
        }

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
