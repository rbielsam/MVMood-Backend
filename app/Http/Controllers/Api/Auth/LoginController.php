<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\HasApiTokens;
use App\Models\User;

class LoginController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function login(Request $request){
        try {
                $request->validate(
                    [
                        'email' => [
                            'required',
                            'email',
                            'ends_with:@institutmvm.cat',
                        ],
                        'password' => ['required', 'min:8'],
                    ]
                );
        } catch (\Exception $error) {
            return response()->json([
                'message' => 'La contraseña ha de tener 8 carácteres como mínimo o falta el correo'
            ], 401);
        }


        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => 'El correo o contraseña son incorrectos',
            ], 401);
        }
        $user = Auth::user();
        if(!$user->hasVerifiedEmail()){
            Auth::logout();
            return response()->json([
                'message' =>'Verifica tu correo'
            ], 403);
        }

        if($user->banned_at){
            Auth::logout();
            return response()->json([
                'message' => 'Esta cuenta ha sido baneada'
            ], 403);
        }
        $token = User::where('email', $request->email)->first()->createToken('auth_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id'        => $user->id,
                'nickname'  => $user->nickname,
                'email'     => $user->email,
                'rol'      => $user->rol,
            ],
        ], 200);
    }
    public function logout(Request $request){
        $request->user()->currentAccessToken()->delete();
        return response()->json([
            'message' => 'Sesion cerrada',
        ], 200);
    }
}
