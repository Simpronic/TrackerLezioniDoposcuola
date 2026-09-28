<?php

namespace App\Services;

use App\Models\GoogleCalendarConnection;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class CalendarConnectionStatus
{
    public function notificationsEnabled(): bool
    {
        // La preferenza esiste anche prima del primo collegamento OAuth.
        return (bool) (DB::table('calendar_preferences')->where('id', 1)->value('notifications_enabled') ?? true);
    }

    /** Restituisce connected, disconnected oppure unknown per problemi temporanei. */
    public function check(): string
    {
        foreach (['enabled', 'client_id', 'client_secret', 'calendar_id'] as $key) {
            if (! config("services.google_calendar.{$key}")) {
                return 'disconnected';
            }
        }

        try {
            $token = GoogleCalendarConnection::find(1)?->refresh_token ?: config('services.google_calendar.refresh_token');
        } catch (DecryptException) {
            return 'disconnected';
        }

        if (blank($token)) {
            return 'disconnected';
        }

        // La chiave cambia con token e credenziali; non contiene segreti in chiaro.
        $key = 'calendar-status:'.hash('sha256', json_encode([
            $token, config('services.google_calendar.client_id'), config('services.google_calendar.client_secret'),
        ]));

        return Cache::remember($key, now()->addMinutes(15), function () use ($token): string {
            try {
                $response = Http::asForm()->timeout(3)->connectTimeout(3)->post('https://oauth2.googleapis.com/token', [
                    'client_id' => config('services.google_calendar.client_id'),
                    'client_secret' => config('services.google_calendar.client_secret'),
                    'refresh_token' => $token,
                    'grant_type' => 'refresh_token',
                ]);
            } catch (ConnectionException) {
                // Una rete indisponibile non dimostra che il consenso sia scaduto.
                return 'unknown';
            }

            if ($response->successful() && filled($response->json('access_token'))) {
                return 'connected';
            }

            return in_array($response->json('error'), ['invalid_grant', 'invalid_client', 'unauthorized_client'], true)
                ? 'disconnected' : 'unknown';
        });
    }
}
