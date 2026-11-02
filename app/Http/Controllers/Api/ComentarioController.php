<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Publicacion;
use Illuminate\Http\Request;
use App\Models\Comentario;
use Illuminate\Support\Facades\Auth;

class ComentarioController extends Controller
{

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, $uuid)
    {
        if (Auth::user()->isAdmin()) {
            return response()->json([
                'message' => 'El admin no puede crear un comentario',
            ], 403);
        }

        $publicacion = Publicacion::findOrFail($uuid);

        $request->validate([
            'contenido' => 'required|string|max:500',
        ], [
            'contenido.required' => 'El contenido es obligatorio',
            'contenido.max' => 'El contenido no puede superar los 500 caracteres',
        ]);

        $comentario = Comentario::create([
            'publicacion_id' => $publicacion->id,
            'user_id' => Auth::id(),
            'contenido' => $request->contenido,
        ]);

        return response()->json([
            'message' => 'El comentario se ha creado correctamente',
            'comentario' => $comentario->load('user:id,nickname,foto_perfil'),
        ], 201);
    }

    public function eliminar($uuid)
    {
        $comentario = Comentario::findOrFail($uuid);

        if($comentario->user_id !== Auth::id()) {
            return response()->json([
                'message' => 'no puedes eliminar comentario no tuyo',
            ], 403);
        }

        $comentario->delete();
        return response()->json([
            'message' => 'El comentario se ha eliminado correctamente',
        ], 200);
    }


}
