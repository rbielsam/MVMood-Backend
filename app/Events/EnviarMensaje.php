<?php

namespace App\Events;

use App\Models\Mensaje;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EnviarMensaje implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $mensaje;

    public string $receptorId;

    /**
     * Create a new event instance.
     */
    public function __construct(Mensaje $mensaje, string $receptorId)
    {
        $this->mensaje = $mensaje->load('emisor:id,nickname,foto_perfil');
        $this->receptorId = $receptorId;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chat.'.$this->mensaje->chat->id),
            new PrivateChannel('user.'.$this->receptorId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'nuevo-mensaje';
    }
}
