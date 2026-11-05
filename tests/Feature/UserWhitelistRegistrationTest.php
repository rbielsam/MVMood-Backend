<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserWhitelist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserWhitelistRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registro_con_dominio_institucional_crea_rol_user(): void
    {
        $response = $this->postJson('/api/register', [
            'nickname' => 'alumno',
            'email' => 'alumno@institutmvm.cat',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201);

        $user = User::where('email', 'alumno@institutmvm.cat')->first();
        $this->assertSame('user', $user->rol);
    }

    public function test_registro_con_email_de_whitelist_crea_rol_daw_y_marca_used_at(): void
    {
        UserWhitelist::create(['email' => 'daw@gmail.com', 'rol' => 'daw']);

        $response = $this->postJson('/api/register', [
            'nickname' => 'alumno_daw',
            'email' => 'daw@gmail.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201);

        $user = User::where('email', 'daw@gmail.com')->first();
        $this->assertSame('daw', $user->rol);
        $this->assertNull($user->email_verified_at);
        $this->assertNotNull(UserWhitelist::where('email', 'daw@gmail.com')->first()->used_at);
    }

    public function test_registro_con_email_no_autorizado_devuelve_el_mismo_error(): void
    {
        $response = $this->postJson('/api/register', [
            'nickname' => 'ajeno',
            'email' => 'ajeno@gmail.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('errors.email.0', 'El email debe acabar con "@institutmvm.cat"');
        $this->assertDatabaseMissing('users', ['email' => 'ajeno@gmail.com']);
    }

    public function test_borrar_cuenta_de_whitelist_elimina_tambien_la_excepcion(): void
    {
        UserWhitelist::create(['email' => 'daw@gmail.com', 'rol' => 'daw']);

        $user = User::create([
            'nickname' => 'alumno_daw',
            'email' => 'daw@gmail.com',
            'password' => 'password123',
        ]);
        $user->rol = 'daw';
        $user->save();

        Sanctum::actingAs($user);

        $this->deleteJson('/api/perfil/delete')->assertStatus(200);

        $this->assertNull(User::find($user->id));
        $this->assertDatabaseMissing('user_whitelist', ['email' => 'daw@gmail.com']);
    }

    public function test_borrar_cuenta_de_dominio_no_toca_la_whitelist(): void
    {
        UserWhitelist::create(['email' => 'otro@gmail.com', 'rol' => 'daw']);

        $user = User::create([
            'nickname' => 'alumno',
            'email' => 'alumno2@institutmvm.cat',
            'password' => 'password123',
        ]);

        Sanctum::actingAs($user);

        $this->deleteJson('/api/perfil/delete')->assertStatus(200);

        $this->assertNull(User::find($user->id));
        $this->assertDatabaseHas('user_whitelist', ['email' => 'otro@gmail.com']);
    }
}
