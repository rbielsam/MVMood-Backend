<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Chat;
use App\Events\EnviarMensaje;
use Illuminate\Support\Str;

class ChatController extends Controller
{
    public function enviar(Request $request)
    {
        $request->validate([
            //'receptor_uuid' => 'required|exists:usuarios,uuid',
            'receptor_id' => 'required|string|exists:users,id',
            'contenido' => 'required|string',
        ]);

        $emisor = auth()->user();
        //$receptor = User::where('uuid', $request->receptor_uuid)->first();
        $receptor = User::find($request->receptor_id);

        if (!$receptor) {
            return response()->json([
                'error' => 'El recepto no existe',
                'receptor_id_recibido' => $request->receptor_id
            ], 422);
        }

        // 1. Buscar o crear el chat 1 a 1
        $chat = Chat::whereHas('usuarios', fn($q) => $q->where('user_id', $emisor->id))
                    ->whereHas('usuarios', fn($q) => $q->where('user_id', $receptor->id))
                    //->where('es_grupal', false)
                    ->first();

        if (!$chat) {
            //$chat = Chat::create(['uuid' => Str::uuid()]);
            $chat = Chat::create(['id' => Str::uuid(), 'nombre' => null]);
            $chat->usuarios()->attach([$emisor->id, $receptor->id]);
        }

        // 2. Crear el mensaje
        $mensaje = $chat->mensajes()->create([
            'uuid' => Str::uuid(),
            'emisor_id' => $emisor->id,
            'contenido' => $request->contenido,
        ]);

        // 3. Notificar a Pusher
        broadcast(new EnviarMensaje($mensaje))->toOthers();
        \Log::info("Disparando evento en chat: " . $chat->id);
        //return response()->json($mensaje->load('emisor'), 201);
        return response()->json([
            //'chat' => $chat->load('usuarios'),
            'chat' => $chat->load('usuarios:id,nickname,foto_perfil'),
            'mensaje' => $mensaje->load('emisor')
        ], 201);
    }

    public function mostrarMensajes($chatId) {
        $chat = Chat::where('id',$chatId)->whereHas('usuarios',fn($q) => $q->where('user_id', auth()->id()))->firstOrFail();

        $mensajes = $chat->mensajes()->with('emisor')->orderBy('created_at', 'desc')->paginate(30);

        return response()->json($mensajes);
    }

    public function misChats() {
        /*$chats = auth()->user()->chats()->with(['usuarios','mensajes' => function($q) {
            $q->latest()->first();
        }])->get();*/

        $chats = auth()->user()->chats()->with([
            'usuarios:id,nickname,foto_perfil',
            'mensajes' => function($q) {
                $q->latest()->limit(1);
            }
        ])->get()->map(function($chat) {
            $chat->ultimo_mensaje = $chat->mensajes->first();
            unset($chat->mensajes);
            return $chat;
        });

        return response()->json($chats);
    }
}
