<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as GoogleUser;
use Mockery;
use Tests\TestCase;

// Requiere MySQL (las migraciones usan vistas, triggers y ALTER ... ENUM).
class GoogleLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function simularGoogle(string $email, string $id = 'g-123', bool $verificado = true, string $avatar = 'https://example.com/a.jpg'): void
    {
        $google = (new GoogleUser)->setRaw(['email_verified' => $verificado])->map([
            'id' => $id,
            'name' => 'Juan Pérez',
            'email' => $email,
            'avatar' => $avatar,
        ]);

        $provider = Mockery::mock(\Laravel\Socialite\Contracts\Provider::class);
        $provider->shouldReceive('user')->andReturn($google);
        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
    }

    public function test_login_y_registro_muestran_ambas_opciones(): void
    {
        $this->get('/login')->assertOk()->assertSee('Continuar con Google')->assertSee('Regístrate');
        $this->get('/register')->assertOk()->assertSee('Registrarme con Google')->assertSee('Crear cuenta');
    }

    public function test_crea_cuenta_nueva_sin_contrasena(): void
    {
        $this->simularGoogle('Juan@Gmail.com');

        $this->get('/auth/google/callback')->assertRedirect('/dashboard');

        $user = User::where('email', 'juan@gmail.com')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertSame('g-123', $user->google_id);
        $this->assertNull($user->password);
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_vincula_una_cuenta_existente_con_el_mismo_correo(): void
    {
        $existente = User::factory()->create(['email' => 'juan@gmail.com']);
        $this->simularGoogle('juan@gmail.com');

        $this->get('/auth/google/callback');

        $this->assertAuthenticatedAs($existente);
        $this->assertSame('g-123', $existente->fresh()->google_id);
        $this->assertNotNull($existente->fresh()->password); // conserva su contraseña
        $this->assertSame(1, User::count());
    }

    // Caso real (14-10-2026): Google entregó una URL de foto de 1.130 caracteres y el
    // login daba error 500, tanto al crear la cuenta como al vincular una existente.
    public function test_acepta_url_de_avatar_muy_larga_al_crear_y_al_vincular(): void
    {
        $avatarLargo = 'https://lh3.googleusercontent.com/a-/'.str_repeat('AbC123xYz_', 110);
        $this->assertGreaterThan(1100, strlen($avatarLargo));

        $this->simularGoogle('nuevo@gmail.com', 'g-largo-1', avatar: $avatarLargo);
        $this->get('/auth/google/callback')->assertRedirect('/dashboard');
        $this->assertSame($avatarLargo, User::where('email', 'nuevo@gmail.com')->value('avatar'));

        auth()->logout();
        $existente = User::factory()->create(['email' => 'existente@gmail.com']);
        $this->simularGoogle('existente@gmail.com', 'g-largo-2', avatar: $avatarLargo);
        $this->get('/auth/google/callback')->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($existente);
        $this->assertSame('g-largo-2', $existente->fresh()->google_id);
    }

    public function test_rechaza_correo_no_verificado(): void
    {
        $this->simularGoogle('juan@gmail.com', verificado: false);

        $this->get('/auth/google/callback')->assertRedirect(route('login'))->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_vuelve_a_la_invitacion_de_pichanga(): void
    {
        $this->get('/pichangas/unirse/abc')->assertRedirect(route('login'));
        $this->simularGoogle('juan@gmail.com');

        $this->get('/auth/google/callback')->assertRedirect(url('/pichangas/unirse/abc'));
    }

    public function test_usuario_de_google_crea_su_primera_contrasena_y_entra_con_correo(): void
    {
        $user = User::factory()->create(['password' => null, 'google_id' => 'g-1']);

        $this->actingAs($user)->get('/profile')->assertOk()->assertSee('aún no tienes contraseña');
        $this->actingAs($user)->put('/password', [
            'password' => 'clave-segura-123',
            'password_confirmation' => 'clave-segura-123',
        ])->assertSessionHasNoErrors();

        auth()->logout();
        $this->post('/login', ['email' => $user->email, 'password' => 'clave-segura-123']);
        $this->assertAuthenticatedAs($user);
    }

    public function test_usuario_sin_contrasena_no_puede_entrar_con_contrasena_vacia(): void
    {
        $user = User::factory()->create(['password' => null]);

        $this->post('/login', ['email' => $user->email, 'password' => 'cualquiera']);
        $this->assertGuest();
    }
}
