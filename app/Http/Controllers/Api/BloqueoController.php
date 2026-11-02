<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bloqueo;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class BloqueoController extends Controller
{
    public function bloquear($uuid){
        $user = Auth::user();
        if($user->id === $uuid){
            return response()->json([
                'message' => 'No puede bloquear este bloquear a si mismo'
            ], 409);
        }
        $usuarioABloquear = User::findOrFail($uuid);

        $bloqueoExistente = Bloqueo::where('bloqueador_id', $user->id)
            ->where('bloqueado_id', $usuarioABloquear->id)
            ->exists();

        if($bloqueoExistente){
            return response()->json([
                'message' => 'Este usurio ya se encuentra bloqueado'
            ], 409);
        }

        Bloqueo::create([
            'bloqueador_id' => $user->id,
            'bloqueado_id' => $usuarioABloquear->id,
        ]);
        return response()->json([
            'message' => 'El usuario se ha bloqueado correctamente'
        ], 201);
    }
    public function listaBloqueados(){
        $user = Auth::user();
        $bloqueados = Bloqueo::where('bloqueador_id', $user->id)
            ->with('bloqueado:id,nickname,foto_perfil')
            ->get()
            ->map(fn($bloqueo) => $bloqueo->bloqueado);
        return response()->json($bloqueados, 200);
    }
}
