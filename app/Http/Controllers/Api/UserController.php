<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    public function index() {
        $user = Auth::user();

        return response()->json([
            'id'    => $user->id,
            'nickname' => $user->nickname,
            'email'    => $user->email,
            'foto_perfil' => $user->foto_perfil?Storage::url($user->foto_perfil):null,
            'rol'     => $user->rol
        ], 200);
    }
    /*public function editarPerfil()
    {
        $user = Auth::user();
        return view('perfil.editar', ['user' => $user]);
    }*/

    public function updatePerfil(Request $request){

        $user = Auth::user();

        $request->validate([
            'nickname' => 'sometimes|required|string|max:255',
            'foto_perfil' => 'sometimes|image|mimes:jpeg,png,jpg,gif,svg|max:3062',
        ], [
            'nickname.required' => 'El nombre de usuario es requerido',
            'nickname.max' => 'El nombre de usuario debe contener menos de 255 caracteres',
            'foto_perfil.image' => 'El archivo debe ser una imagen',
            'foto_perfil.mimes' => 'La imagen debe ser jpeg, png, jpg o gif',
            'foto_perfil.max' => 'La imagen no puede superar los 3MB',
        ]);
        if($request->has('nickname')){
            $user->nickname = $request->nickname;
        }
        if($request->hasFile('foto_perfil')){
            if($user->foto_perfil){
                Storage::disk('public')->delete($user->foto_perfil);
            }

            $ruta = $request->file('foto_perfil')->store('fotos_perfil','public');
            $user->foto_perfil = $ruta;
        }
        $user->save();
        return response()->json([
            'message' => 'Perfil actualizado correctamente',
            'user' => [
                'id'    => $user->id,
                'nickname' => $user->nickname,
                'foto_perfil' => $user->foto_perfil?Storage::url($user->foto_perfil):null,
                ],
        ],200);
    }
    public function changePassword(Request $request){
        $user = Auth::user();
        $validated = $request->validate([
            'password_antigua' => 'required|string',
            'password_nueva' => 'required|string|min:8|confirmed',
        ], [
            'password_antigua.required'  => 'La contraseña actual es incorrecta',
            'password_nueva.required'    => 'La nueva contraseña es obligatoria',
            'password_nueva.min'        => 'La nueva contraseña debe tener minimo 8 characteres',
            'password_nueva.confirmed'  => 'Las contraseñas no coinciden',
        ]);

        if(!Hash::check($validated['password_antigua'], $user->password)){
            return response()->json([
                'message' => 'La contraseña actual es incorrecta',
            ], 403);
        }
        $user->password = Hash::make($validated['password_nueva']);
        $user->save();
        $user->tokens()->delete();
        return response()->json([
            'message' => 'Se ha actualizado la contraseña correctamente. Vuelve a iniciar sesión'
        ], 200);
    }

    public function getUsers() {
        return User::select('id', 'nickname', 'foto_perfil')->where('id', '!=', auth()->id())->get();
    }

    public function deleteUser() {
        $user = Auth::user();

        $user->chats()->delete();
        $user->comentarios()->delete();
        $user->likes()->delete();
        $user->publicaciones()->delete();
        $user->tokens()->delete();
        $user->delete();

        return response()->json([
            'message' => 'Cuenta eliminada'
        ], 200);
    }
 }
