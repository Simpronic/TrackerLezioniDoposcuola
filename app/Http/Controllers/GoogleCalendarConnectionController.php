<?php

namespace App\Http\Controllers;

use App\Models\GoogleCalendarConnection;
use App\Services\CalendarConnectionStatus;
use App\Services\GoogleCalendarOAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class GoogleCalendarConnectionController extends Controller
{
    public function index(CalendarConnectionStatus $status): View
    {
        return view('settings.google-calendar', [
            'connection' => GoogleCalendarConnection::query()->find(1),
            'notificationsEnabled' => $status->notificationsEnabled(),
            'connectionStatus' => $status->check(),
            'hasLegacyToken' => filled(config('services.google_calendar.refresh_token')),
            'redirectUri' => route('google-calendar.callback'),
            'configured' => filled(config('services.google_calendar.client_id'))
                && filled(config('services.google_calendar.client_secret')),
        ]);
    }

    public function notifications(Request $request): RedirectResponse
    {
        $data = $request->validate(['notifications_enabled' => ['required', 'boolean']]);
        DB::table('calendar_preferences')->updateOrInsert(['id' => 1], [
            'notifications_enabled' => (bool) $data['notifications_enabled'],
        ]);

        return redirect()->route('google-calendar.index')->with('success', 'Preferenza notifiche salvata.');
    }

    public function redirect(Request $request, GoogleCalendarOAuthService $oauth): RedirectResponse
    {
        $state = Str::random(64);
        $request->session()->put('google_calendar_oauth_state', $state);

        try {
            return redirect()->away($oauth->authorizationUrl($state, route('google-calendar.callback')));
        } catch (Throwable $exception) {
            report($exception);

            return redirect()->route('google-calendar.index')->with('error', $exception->getMessage());
        }
    }

    public function callback(Request $request, GoogleCalendarOAuthService $oauth): RedirectResponse
    {
        $expectedState = (string) $request->session()->pull('google_calendar_oauth_state', '');
        $receivedState = (string) $request->query('state', '');

        if ($expectedState === '' || $receivedState === '' || ! hash_equals($expectedState, $receivedState)) {
            return redirect()->route('google-calendar.index')->with('error', 'Verifica OAuth non valida o scaduta. Avvia nuovamente il collegamento.');
        }

        if ($request->filled('error')) {
            return redirect()->route('google-calendar.index')->with('error', 'Autorizzazione Google annullata o rifiutata.');
        }

        $code = (string) $request->query('code', '');
        if ($code === '') {
            return redirect()->route('google-calendar.index')->with('error', 'Google non ha restituito il codice di autorizzazione.');
        }

        try {
            $oauth->connect($code, route('google-calendar.callback'));
        } catch (Throwable $exception) {
            report($exception);

            return redirect()->route('google-calendar.index')->with('error', 'Collegamento non riuscito. Controlla configurazione e log.');
        }

        return redirect()->route('google-calendar.index')->with('success', 'Google Calendar è stato collegato correttamente.');
    }
}
