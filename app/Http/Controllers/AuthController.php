<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuthController extends Controller
{
    /** Mostra il login oppure rimanda alla dashboard se la sessione è già autenticata. */
    public function create(Request $request): View|RedirectResponse
    {
        return $request->session()->get('env_authenticated')
            ? redirect()->route('dashboard')
            : view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string'],
        ]);

        // Le credenziali arrivano da config/tracker.php, che legge APP_LOGIN_* dal .env.
        // hash_equals evita confronti di stringhe vulnerabili ad attacchi temporali.
        $expectedUser = (string) config('tracker.login_user', 'admin');
        $expectedPassword = (string) config('tracker.login_password', '');

        if ($expectedPassword === '' || ! hash_equals($expectedUser, $credentials['username']) || ! hash_equals($expectedPassword, $credentials['password'])) {
            return back()->withErrors(['username' => 'Credenziali non valide.'])->onlyInput('username');
        }

        // Rigenerare l'ID dopo il login previene la session fixation.
        $request->session()->regenerate();
        $request->session()->put('env_authenticated', true);

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        // Invalidiamo sia la sessione sia il token CSRF associato al vecchio accesso.
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
