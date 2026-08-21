<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuthController extends Controller
{
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

        $expectedUser = (string) config('tracker.login_user', 'admin');
        $expectedPassword = (string) config('tracker.login_password', '');

        if ($expectedPassword === '' || ! hash_equals($expectedUser, $credentials['username']) || ! hash_equals($expectedPassword, $credentials['password'])) {
            return back()->withErrors(['username' => 'Credenziali non valide.'])->onlyInput('username');
        }

        $request->session()->regenerate();
        $request->session()->put('env_authenticated', true);

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
