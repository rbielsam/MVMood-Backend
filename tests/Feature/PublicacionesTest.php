<?php

namespace Tests\Feature;

use App\Models\Publicacion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PublicacionesTest extends TestCase
{
    use RefreshDatabase;

    private function crearUsuario(string $nickname): User
    {
        return User::create([
            'nickname' => $nickname,
            'email' => $nickname.'@institutmvm.cat',
            'password' => 'password123',
        ]);
    }

    public function test_usuario_normal_crea_una_publicacion(): void
    {
        $user = $this->crearUsuario('alumno');

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/create', [
            'contenido' => 'Mi primera publicación',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('message', 'Publicacion creada correctamente')
            ->assertJsonPath('publicacion.contenido', 'Mi primera publicación');

        $this->assertDatabaseHas('publicacion', [
            'user_id' => $user->id,
            'contenido' => 'Mi primera publicación',
        ]);
    }

    public function test_administrador_no_puede_crear_una_publicacion(): void
    {
        $admin = $this->crearUsuario('admin');
        $admin->rol = 'admin';
        $admin->save();

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/create', [
            'contenido' => 'Intento de publicación del administrador',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('message', 'El administrador no puede crear publicaciones');

        $this->assertDatabaseCount('publicacion', 0);
    }

    public function test_contenido_vacio_devuelve_422(): void
    {
        $user = $this->crearUsuario('alumno');

        Sanctum::actingAs($user);

        $this->postJson('/api/create', [
            'contenido' => '',
        ])->assertStatus(422)
            ->assertJsonValidationErrors('contenido');

        $this->assertDatabaseCount('publicacion', 0);
    }

    public function test_contenido_de_mas_de_500_caracteres_devuelve_422(): void
    {
        $user = $this->crearUsuario('alumno');

        Sanctum::actingAs($user);

        $this->postJson('/api/create', [
            'contenido' => str_repeat('a', 501),
        ])->assertStatus(422)
            ->assertJsonValidationErrors('contenido');

        $this->assertDatabaseCount('publicacion', 0);
    }

    public function test_home_devuelve_las_publicaciones_paginadas(): void
    {
        $user = $this->crearUsuario('alumno');

        Publicacion::create([
            'user_id' => $user->id,
            'contenido' => 'Publicación visible en el home',
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/home')
            ->assertOk()
            ->assertJsonPath('data.0.contenido', 'Publicación visible en el home');
    }
}
