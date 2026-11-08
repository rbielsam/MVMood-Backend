<?php

namespace Tests\Feature;

use App\Events\EnviarMensaje;
use App\Models\Chat;
use App\Models\Mensaje;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatTest extends TestCase
{
    use RefreshDatabase;

    private function crearUsuario(string $nickname): User
    {
        return User::create([
            'nickname' => $nickname,
            'email' => $nickname.'@institutmvm.cat',
            'password' => 'password',
        ]);
    }

    public function test_enviar_crea_chat_y_mensaje(): void
    {
        $emisor = $this->crearUsuario('emisor');
        $receptor = $this->crearUsuario('receptor');

        $response = $this->actingAs($emisor, 'sanctum')->postJson('/api/chats/enviar', [
            'receptor_id' => $receptor->id,
            'contenido' => 'Hola',
        ]);

        $response->assertCreated()
            ->assertJsonPath('mensaje.contenido', 'Hola')
            ->assertJsonPath('mensaje.emisor.nickname', 'emisor')
            ->assertJsonMissingPath('mensaje.emisor.email');

        $response->assertJsonFragment(['id' => $emisor->id]);
        $response->assertJsonFragment(['id' => $receptor->id]);

        $this->assertDatabaseCount('chats', 1);
        $this->assertDatabaseCount('mensajes', 1);
    }

    public function test_mis_chats_devuelve_usuarios_y_ultimo_mensaje(): void
    {
        $emisor = $this->crearUsuario('emisor');
        $receptor = $this->crearUsuario('receptor');

        $this->actingAs($emisor, 'sanctum')->postJson('/api/chats/enviar', [
            'receptor_id' => $receptor->id,
            'contenido' => 'Primer mensaje',
        ])->assertCreated();

        $response = $this->actingAs($emisor, 'sanctum')->getJson('/api/chats');

        $response->assertOk()
            ->assertJsonPath('0.ultimo_mensaje.contenido', 'Primer mensaje')
            ->assertJsonStructure([
                [
                    'id',
                    'nombre',
                    'usuarios' => [['id', 'nickname', 'foto_perfil']],
                    'ultimo_mensaje' => ['uuid', 'contenido'],
                ],
            ]);

        $this->assertCount(2, $response->json('0.usuarios'));
    }

    public function test_mostrar_mensajes_no_expone_datos_privados_del_emisor(): void
    {
        $emisor = $this->crearUsuario('emisor');
        $receptor = $this->crearUsuario('receptor');

        $this->actingAs($emisor, 'sanctum')->postJson('/api/chats/enviar', [
            'receptor_id' => $receptor->id,
            'contenido' => 'Mensaje privado',
        ])->assertCreated();

        $chat = Chat::firstOrFail();

        $response = $this->actingAs($emisor, 'sanctum')
            ->getJson("/api/chats/{$chat->id}/mensajes");

        $response->assertOk()
            ->assertJsonPath('data.0.contenido', 'Mensaje privado')
            ->assertJsonPath('data.0.emisor.nickname', 'emisor')
            ->assertJsonMissingPath('data.0.emisor.email')
            ->assertJsonMissingPath('data.0.emisor.rol');
    }

    public function test_enviar_sin_contenido_devuelve_422(): void
    {
        $emisor = $this->crearUsuario('emisor');
        $receptor = $this->crearUsuario('receptor');

        $response = $this->actingAs($emisor, 'sanctum')->postJson('/api/chats/enviar', [
            'receptor_id' => $receptor->id,
            'contenido' => '',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('contenido');

        $this->assertDatabaseCount('chats', 0);
        $this->assertDatabaseCount('mensajes', 0);
    }

    public function test_no_se_puede_leer_un_chat_ajeno(): void
    {
        $emisor = $this->crearUsuario('emisor');
        $receptor = $this->crearUsuario('receptor');
        $intruso = $this->crearUsuario('intruso');

        $this->actingAs($emisor, 'sanctum')->postJson('/api/chats/enviar', [
            'receptor_id' => $receptor->id,
            'contenido' => 'Privado',
        ])->assertCreated();

        $chat = Chat::firstOrFail();

        $this->actingAs($intruso, 'sanctum')
            ->getJson("/api/chats/{$chat->id}/mensajes")
            ->assertNotFound();
    }

    public function test_mis_chats_devuelve_los_mensajes_sin_leer(): void
    {
        $emisor = $this->crearUsuario('emisor');
        $receptor = $this->crearUsuario('receptor');

        $this->actingAs($emisor, 'sanctum')->postJson('/api/chats/enviar', [
            'receptor_id' => $receptor->id,
            'contenido' => 'Mensaje pendiente',
        ])->assertCreated();

        $this->actingAs($receptor, 'sanctum')
            ->getJson('/api/chats')
            ->assertOk()
            ->assertJsonPath('0.unread_count', 1);

        $this->actingAs($emisor, 'sanctum')
            ->getJson('/api/chats')
            ->assertOk()
            ->assertJsonPath('0.unread_count', 0);
    }

    public function test_marcar_leido_elimina_los_mensajes_sin_leer(): void
    {
        $emisor = $this->crearUsuario('emisor');
        $receptor = $this->crearUsuario('receptor');

        $this->actingAs($emisor, 'sanctum')->postJson('/api/chats/enviar', [
            'receptor_id' => $receptor->id,
            'contenido' => 'Mensaje pendiente',
        ])->assertCreated();

        $chat = Chat::firstOrFail();

        $this->actingAs($receptor, 'sanctum')
            ->postJson("/api/chats/{$chat->id}/leer")
            ->assertOk()
            ->assertJsonPath('unread_count', 0);

        $this->actingAs($receptor, 'sanctum')
            ->getJson('/api/chats')
            ->assertOk()
            ->assertJsonPath('0.unread_count', 0);
    }

    public function test_no_se_puede_marcar_como_leido_un_chat_ajeno(): void
    {
        $emisor = $this->crearUsuario('emisor');
        $receptor = $this->crearUsuario('receptor');
        $intruso = $this->crearUsuario('intruso');

        $this->actingAs($emisor, 'sanctum')->postJson('/api/chats/enviar', [
            'receptor_id' => $receptor->id,
            'contenido' => 'Privado',
        ])->assertCreated();

        $chat = Chat::firstOrFail();

        $this->actingAs($intruso, 'sanctum')
            ->postJson("/api/chats/{$chat->id}/leer")
            ->assertNotFound();
    }

    public function test_el_evento_de_mensaje_se_difunde_al_canal_del_receptor(): void
    {
        $emisor = $this->crearUsuario('emisor');
        $receptor = $this->crearUsuario('receptor');

        $this->actingAs($emisor, 'sanctum')->postJson('/api/chats/enviar', [
            'receptor_id' => $receptor->id,
            'contenido' => 'Hola',
        ])->assertCreated();

        $mensaje = Mensaje::firstOrFail();
        $evento = new EnviarMensaje($mensaje, $receptor->id);

        $canales = array_map(fn ($canal) => (string) $canal, $evento->broadcastOn());

        $this->assertContains('private-chat.'.$mensaje->chat_id, $canales);
        $this->assertContains('private-user.'.$receptor->id, $canales);
    }
}
