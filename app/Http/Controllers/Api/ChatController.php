<?php

namespace App\Http\Controllers\Api;

use App\Events\EnviarMensaje;
use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ChatController extends Controller
{
    public function enviar(Request $request)
    {
        $request->validate([
            // 'receptor_uuid' => 'required|exists:usuarios,uuid',
            'receptor_id' => 'required|string|exists:users,id',
            'contenido' => 'required|string',
        ]);

        $emisor = auth()->user();
        // $receptor = User::where('uuid', $request->receptor_uuid)->first();
        $receptor = User::find($request->receptor_id);

        if (! $receptor) {
            return response()->json([
                'error' => 'El recepto no existe',
                'receptor_id_recibido' => $request->receptor_id,
            ], 422);
        }

        // 1. Buscar o crear el chat 1 a 1
        $chat = Chat::whereHas('usuarios', fn ($q) => $q->where('user_id', $emisor->id))
            ->whereHas('usuarios', fn ($q) => $q->where('user_id', $receptor->id))
                    // ->where('es_grupal', false)
            ->first();

        if (! $chat) {
            // $chat = Chat::create(['uuid' => Str::uuid()]);
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
        broadcast(new EnviarMensaje($mensaje, $receptor->id))->toOthers();
        \Log::info('Disparando evento en chat: '.$chat->id);

        // return response()->json($mensaje->load('emisor'), 201);
        return response()->json([
            // 'chat' => $chat->load('usuarios'),
            'chat' => $chat->load('usuarios:users.id,users.nickname,users.foto_perfil'),
            'mensaje' => $mensaje->load('emisor:id,nickname,foto_perfil'),
        ], 201);
    }

    public function mostrarMensajes($chatId)
    {
        $chat = Chat::where('id', $chatId)->whereHas('usuarios', fn ($q) => $q->where('user_id', auth()->id()))->firstOrFail();

        $mensajes = $chat->mensajes()->with('emisor:id,nickname,foto_perfil')->orderBy('created_at', 'desc')->paginate(30);

        return response()->json($mensajes);
    }

    public function misChats()
    {
        $userId = auth()->id();

        $chats = auth()->user()->chats()->with([
            'usuarios:users.id,users.nickname,users.foto_perfil',
            'ultimoMensaje',
        ])->get()->map(function ($chat) use ($userId) {
            $chat->ultimo_mensaje = $chat->ultimoMensaje;
            unset($chat->ultimoMensaje);

            $mensajesSinLeer = $chat->mensajes()
                ->where('emisor_id', '!=', $userId);

            if ($chat->pivot->last_read_at) {
                $mensajesSinLeer->where('created_at', '>', $chat->pivot->last_read_at);
            }

            $chat->unread_count = $mensajesSinLeer->count();
            unset($chat->pivot);

            return $chat;
        });

        return response()->json($chats);
    }

    public function marcarLeido($chatId)
    {
        $chat = Chat::where('id', $chatId)
            ->whereHas('usuarios', fn ($query) => $query->where('user_id', auth()->id()))
            ->firstOrFail();

        auth()->user()->chats()->updateExistingPivot($chat->id, [
            'last_read_at' => now(),
        ]);

        return response()->json([
            'message' => 'Chat marcado como leído',
            'unread_count' => 0,
        ], 200);
    }
}
