<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class ForgotPasswordTest extends TestCase
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

    public function test_solicitar_enlace_con_correo_existente_envia_notificacion(): void
    {
        Notification::fake();

        $user = $this->crearUsuario('alumno');

        $response = $this->postJson('/api/forgot-password', ['email' => $user->email]);

        $response->assertOk()
            ->assertJsonPath('message', 'Si el correo está registrado, recibirás un enlace para restablecer la contraseña.');

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $mail = $notification->toMail($user);

            return str_contains($mail->actionUrl, '/reset-password?token=')
                && str_contains($mail->actionUrl, urlencode($user->email));
        });
    }

    public function test_solicitar_enlace_con_correo_inexistente_responde_igual_sin_enviar(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/forgot-password', ['email' => 'noexiste@institutmvm.cat']);

        $response->assertOk()
            ->assertJsonPath('message', 'Si el correo está registrado, recibirás un enlace para restablecer la contraseña.');

        Notification::assertNothingSent();
    }

    public function test_solicitar_enlace_con_correo_invalido_devuelve_422(): void
    {
        $response = $this->postJson('/api/forgot-password', ['email' => 'no-es-un-email']);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_restablecer_con_token_valido_cambia_la_contrasena_y_revoca_tokens(): void
    {
        $user = $this->crearUsuario('alumno');
        $user->createToken('sesion-activa');
        $token = Password::createToken($user);

        $response = $this->postJson('/api/reset-password', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'nuevaPassword123',
            'password_confirmation' => 'nuevaPassword123',
        ]);

        $response->assertOk();

        $this->assertTrue(Hash::check('nuevaPassword123', $user->fresh()->password));
        $this->assertDatabaseCount('password_reset_tokens', 0);
        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_restablecer_con_token_invalido_devuelve_422(): void
    {
        $user = $this->crearUsuario('alumno');

        $response = $this->postJson('/api/reset-password', [
            'email' => $user->email,
            'token' => 'token-invalido',
            'password' => 'nuevaPassword123',
            'password_confirmation' => 'nuevaPassword123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('token');
    }

    public function test_restablecer_con_contrasena_corta_o_sin_confirmar_devuelve_422(): void
    {
        $user = $this->crearUsuario('alumno');
        $token = Password::createToken($user);

        $this->postJson('/api/reset-password', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'corta',
            'password_confirmation' => 'corta',
        ])->assertStatus(422)->assertJsonValidationErrors('password');

        $this->postJson('/api/reset-password', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'nuevaPassword123',
            'password_confirmation' => 'otraPassword123',
        ])->assertStatus(422)->assertJsonValidationErrors('password');
    }

    public function test_cuentas_baneadas_o_no_verificadas_reciben_la_respuesta_generica(): void
    {
        Notification::fake();

        $noVerificada = $this->crearUsuario('sin_verificar');

        $baneada = $this->crearUsuario('baneada');
        $baneada->banned_at = now();
        $baneada->save();

        $mensaje = 'Si el correo está registrado, recibirás un enlace para restablecer la contraseña.';

        $this->postJson('/api/forgot-password', ['email' => $noVerificada->email])
            ->assertOk()
            ->assertJsonPath('message', $mensaje);

        $this->postJson('/api/forgot-password', ['email' => $baneada->email])
            ->assertOk()
            ->assertJsonPath('message', $mensaje);
    }

    public function test_solicitudes_excesivas_devuelven_429(): void
    {
        Notification::fake();

        $user = $this->crearUsuario('alumno');

        for ($intento = 0; $intento < 6; $intento++) {
            $this->postJson('/api/forgot-password', ['email' => $user->email])->assertOk();
        }

        $this->postJson('/api/forgot-password', ['email' => $user->email])
            ->assertStatus(429);
    }
}
