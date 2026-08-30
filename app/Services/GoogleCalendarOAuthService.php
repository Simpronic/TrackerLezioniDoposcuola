<?php

namespace App\Services;

use App\Models\GoogleCalendarConnection;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GoogleCalendarOAuthService
{
    private const AUTHORIZE_URL = 'https://accounts.google.com/o/oauth2/v2/auth';

    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    public function authorizationUrl(string $state, string $redirectUri): string
    {
        $this->ensureClientConfigured();

        return self::AUTHORIZE_URL.'?'.http_build_query([
            'client_id' => config('services.google_calendar.client_id'),
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'https://www.googleapis.com/auth/calendar.events',
            'access_type' => 'offline',
            // Forza Google a restituire un nuovo refresh token anche se l'account
            // aveva già autorizzato l'app in precedenza.
            'prompt' => 'consent',
            'include_granted_scopes' => 'true',
            'state' => $state,
        ], '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * Scambia il codice monouso con i token e conserva il refresh token cifrato.
     *
     * @throws RequestException
     */
    public function connect(string $code, string $redirectUri): GoogleCalendarConnection
    {
        $this->ensureClientConfigured();

        $response = Http::asForm()
            ->timeout((int) config('services.google_calendar.timeout', 10))
            ->post(self::TOKEN_URL, [
                'client_id' => config('services.google_calendar.client_id'),
                'client_secret' => config('services.google_calendar.client_secret'),
                'code' => $code,
                'grant_type' => 'authorization_code',
                'redirect_uri' => $redirectUri,
            ]);

        $response->throw();
        $refreshToken = $response->json('refresh_token');

        if (! is_string($refreshToken) || $refreshToken === '') {
            throw new RuntimeException('Google non ha restituito un refresh token. Ripeti il consenso selezionando nuovamente l’account.');
        }

        return GoogleCalendarConnection::query()->updateOrCreate(
            ['id' => 1],
            ['refresh_token' => $refreshToken, 'connected_at' => now()],
        );
    }

    private function ensureClientConfigured(): void
    {
        foreach (['client_id', 'client_secret'] as $key) {
            if (blank(config("services.google_calendar.{$key}"))) {
                throw new RuntimeException("Configurazione Google Calendar incompleta: manca {$key} nel file .env.");
            }
        }
    }
}
