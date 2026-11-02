<?php

namespace App\Http\Controllers\Api;

use App\Models\Bloqueo;
use App\Http\Controllers\Controller;
use App\Models\Publicacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PublicacionesController extends Controller
{
    public function index(){
        $user = Auth::user();

        $bloqueados = $user->bloqueados()->pluck('bloqueado_id')
            ->merge(Bloqueo::where('bloqueado_id', $user->id)->pluck('bloqueador_id'));

        $publicaciones = Publicacion::with(['user:id,nickname,foto_perfil'])
            ->withCount('comentarios')
            ->withCount('likes')
            ->whereNotIn('user_id', $bloqueados)
            ->orderBy('created_at','desc')
            ->paginate(6);

        $publicaciones->getCollection()->transform(function ($publicacion) use ($user) {
            $publicacion->user_has_liked = $publicacion->likes()
                ->where('user_id', $user->id)
                ->exists();
            return $publicacion;
        });
        return response()->json($publicaciones, 200);
    }

    /*public function crear(){
        return view('publicaciones.crear');
    }*/

    public function store(Request $request){
        $user = Auth::user();
        if (Auth::user()->isAdmin()){
            return response()->json([
                'message' => 'El administrador no pede crear publicaciones',
            ], 403);
        }
        $request->validate([
            'contenido' => ['required', 'max:500'],
            'imagen' => ['nullable', 'image']
        ], [
                'contenido.required' => 'Es necesario ingresar un contenido',
                'contenido.max' => 'El contenido no debe superar 500 caracteres',
            ]
        );

        $imagenUrl = null;
        if ($request->hasFile('imagen')) {
            // Obtenemos la fecha actual
            $fecha = now()->format('Ymd_His');

            // Obtenemos user_id
            $userId = Auth::id();

            $extension = $request->file('imagen')->getClientOriginalExtension();

            $imageName = "{$fecha}_user{$userId}.{$extension}";

            $imagenUrl = $request->file('imagen')->storeAs('publicaciones', $imageName, 'public');
        }

        $publicacion = Publicacion::create([
            'user_id'   => Auth::id(),
            'contenido' => $request->input('contenido'),
            'imagen' => $imagenUrl,
            //'imagen'     => null,
        ]);

        return response()->json([
            'message' => 'Publicacion creada correctamente',
            'publicacion' => $publicacion->load(['user:id,nickname,foto_perfil']),
        ],201);
    }

    public function eliminar($uuid)
    {
        $publicacion = Publicacion::findOrFail($uuid);
        $user = Auth::user();
        if ($publicacion->user_id !== $user->id && !$user->isAdmin()){
            return response()->json([
                'message' => 'No puedes eliminar esta publicacion',
            ], 403);
        }
        $publicacion->delete();
        return response()->json([
            'message' => 'Publicacion eliminada correctamente',
        ], 200);
    }

    /*public function editar($id){
        $publicacion = Publicacion::findOrFail($id);
        // falta implementar que no puede modificar la publicacion si ya han pasado 15min
        return view('publicaciones.editar', ['publicacion' => $publicacion]);
    }*/


    public function update(Request $request, $uuid)
    {
        $publicacion = Publicacion::findOrFail($uuid);

        if ($publicacion->user_id !== Auth::id()){
            return response()->json([
                'message' => 'No puedes editar esta publicacion',
            ], 403);
        }

        if($publicacion->created_at->diffInMinutes(now())>15){
            return response()->json([
                'message' => 'Ya han pasado 15 minutos',
            ], 403);
        }

        $request->validate([
            'contenido' => ['required', 'max:500'],
            'imagen' => ['nullable', 'image']
        ], [
            'contenido.required' => 'El contenido es obligatorio',
            'contenido.max' => 'El contenido no debe superar 500 caracteres',
        ]);

        if ($request->hasFile('imagen')) {
            if ($publicacion->imagen && \Storage::disk('public')->exists($publicacion->imagen)) {
                \Storage::disk('public')->delete($publicacion->imagen);
            }

            $fecha = now()->format('Ymd_His');
            $userId = Auth::id();
            $extension = $request->file('imagen')->getClientOriginalExtension();
            $imageName = "{$fecha}_user{$userId}.{$extension}";

            $imagenUrl = $request->file('imagen')->storeAs('publicaciones', $imageName, 'public');

            $publicacion->imagen = $imagenUrl;
            $publicacion->save();

        }

        $publicacion->update([
            'contenido' => $request->input('contenido'),
        ]);

        return response()->json([
            'message' => 'Publicacion actualizada correctamente',
        ], 200);

    }

    public function likes($uuid){
        $publicacion = Publicacion::findOrFail($uuid);
        $likes = $publicacion->likes()->with('user:id,nickname,foto_perfil')->get()
            ->map(fn($like) => $like->user);
        return response()->json($likes, 200);
    }

    public function comentarios($uuid){
        $publicacion = Publicacion::findOrFail($uuid);
        $comentarios = $publicacion->comentarios()
            ->with('user:id,nickname,foto_perfil')
            ->orderBy('created_at','desc')
            ->get();
        return response()->json($comentarios, 200);
    }

}

