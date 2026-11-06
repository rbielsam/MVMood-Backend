<?php

namespace Tests\Feature;

use App\Models\Chat;
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
}
