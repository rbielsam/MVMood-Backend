<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Like;
use App\Models\Publicacion;
use Illuminate\Support\Facades\Auth;

class LikeController extends Controller
{
    public function guardarLike($uuid)
    {
        if (Auth::user()->isAdmin()){
            return response()->json([
                'message' => 'Admin no puede dar like',
            ], 403);
        }

        $publicacion = Publicacion::findOrFail($uuid);

        $likeExiste= Like::where('user_id', Auth::id())
            ->where('publicacion_id', $publicacion->id)
            ->first();

        if ( $likeExiste){
            $likeExiste->delete();
            return response()->json([
                'message' => 'Se ha borrado el like',
                'likes_count' => $publicacion->likes()->count(),
            ], 201);
        }

        Like::create([
            'user_id'   =>Auth::id(),
            'publicacion_id' => $publicacion->id,

        ]);

        return response()->json([
            'message' => 'Ha hecho like a esta publicacion',
            'likes_count' => $publicacion->likes()->count(),
        ], 201);
    }
}
