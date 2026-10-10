<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Support\Run;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Datos para la portería del recinto: RUN, patente y acompañantes.
     */
    public function updateAcceso(Request $request): RedirectResponse
    {
        // Las filas de acompañantes que quedaron totalmente vacías se ignoran.
        $request->merge([
            'acompanantes' => collect($request->input('acompanantes', []))
                ->filter(fn ($a) => filled($a['nombre'] ?? null) || filled($a['run'] ?? null))
                ->values()
                ->all(),
        ]);

        $runValido = function (string $attribute, mixed $value, \Closure $fail) {
            if (Run::normalizar($value) === null) {
                $fail('El :attribute no es válido. Revisa el número y el dígito verificador.');
            }
        };

        $data = $request->validateWithBag('datosAcceso', [
            'run' => ['nullable', 'string', 'max:15', $runValido],
            'patente' => ['nullable', 'string', 'max:12', function (string $attribute, mixed $value, \Closure $fail) {
                if (! preg_match('/^[A-Z0-9]{5,8}$/', (string) Run::normalizarPatente($value))) {
                    $fail('La patente debe tener entre 5 y 8 letras o números (ej. BBCL12).');
                }
            }],
            'acompanantes' => ['array', 'max:10'],
            'acompanantes.*.nombre' => ['required', 'string', 'max:120'],
            'acompanantes.*.run' => ['required', 'string', 'max:15', $runValido],
        ], [], [
            'run' => 'RUN',
            'acompanantes.*.nombre' => 'nombre del acompañante',
            'acompanantes.*.run' => 'RUN del acompañante',
        ]);

        $user = $request->user();

        DB::transaction(function () use ($user, $data) {
            $user->update([
                'run' => Run::normalizar($data['run'] ?? null),
                'patente' => Run::normalizarPatente($data['patente'] ?? null),
            ]);

            $user->acompanantes()->delete();

            foreach ($data['acompanantes'] ?? [] as $acompanante) {
                $user->acompanantes()->create([
                    'nombre' => trim($acompanante['nombre']),
                    'run' => Run::normalizar($acompanante['run']),
                ]);
            }
        });

        return Redirect::route('profile.edit')->with('status', 'acceso-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        // Quien entró solo con Google no tiene contraseña que confirmar.
        if ($request->user()->password !== null) {
            $request->validateWithBag('userDeletion', [
                'password' => ['required', 'current_password'],
            ]);
        }

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
