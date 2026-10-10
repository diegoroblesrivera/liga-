<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Throwable;

class GoogleController extends Controller
{
    // Manda al usuario a la pantalla de Google.
    public function redirect(): RedirectResponse
    {
        if (! config('services.google.client_id')) {
            return redirect()->route('login')
                ->withErrors(['email' => 'El acceso con Google aún no está configurado.']);
        }

        return Socialite::driver('google')->redirect();
    }

    // Google vuelve aquí: busca la cuenta, la vincula o la crea, e inicia sesión.
    public function callback(): RedirectResponse
    {
        try {
            $google = Socialite::driver('google')->user();
        } catch (InvalidStateException) {
            return redirect()->route('login')->withErrors(['email' => 'La sesión con Google expiró. Intenta de nuevo.']);
        } catch (Throwable $e) {
            report($e);

            return redirect()->route('login')->withErrors(['email' => 'No se pudo iniciar sesión con Google.']);
        }

        $email = strtolower($google->getEmail() ?? '');

        if ($email === '' || ! ($google->user['email_verified'] ?? false)) {
            return redirect()->route('login')->withErrors(['email' => 'Tu cuenta de Google no tiene un correo verificado.']);
        }

        // 1) Ya entró antes con Google  2) existe con ese correo (registro o invitación) → se vincula
        $user = User::where('google_id', $google->getId())->first()
            ?? User::where('email', $email)->first();

        if ($user) {
            $user->forceFill([
                'google_id' => $google->getId(),
                'avatar' => $google->getAvatar(),
                'email_verified_at' => $user->email_verified_at ?? now(),
            ])->save();
        } else {
            $user = User::forceCreate([
                'name' => $google->getName() ?: strstr($email, '@', true),
                'email' => $email,
                'google_id' => $google->getId(),
                'avatar' => $google->getAvatar(),
                'email_verified_at' => now(),
                'password' => null,
            ]);

            event(new Registered($user));
        }

        Auth::login($user, remember: true);
        session()->regenerate();

        // Vuelve a donde iba (p. ej. el link de invitación a una pichanga).
        return redirect()->intended(route('dashboard', absolute: false));
    }
}
