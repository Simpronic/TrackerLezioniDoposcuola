<?php

namespace App\Services;

use App\Models\Lesson;
use Carbon\Carbon;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GoogleCalendarService
{
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    private const API_URL = 'https://www.googleapis.com/calendar/v3';

    /** Crea l'evento oppure aggiorna quello già collegato alla lezione. */
    public function sync(Lesson $lesson): void
    {
        $this->ensureConfigured();
        $lesson->loadMissing('student');

        $calendarId = rawurlencode((string) config('services.google_calendar.calendar_id'));
        $eventId = $lesson->google_calendar_event_id;
        $endpoint = self::API_URL."/calendars/{$calendarId}/events";

        $response = $eventId
            ? $this->client()->put($endpoint.'/'.rawurlencode($eventId), $this->eventPayload($lesson))
            : $this->client()->post($endpoint, $this->eventPayload($lesson));

        $response->throw();

        // Google restituisce id e link anche dopo un aggiornamento completo.
        $lesson->forceFill([
            'google_calendar_event_id' => $response->json('id'),
            'google_calendar_event_url' => $response->json('htmlLink'),
        ])->save();
    }

    private function client(): PendingRequest
    {
        return Http::acceptJson()
            ->withToken($this->accessToken())
            ->timeout((int) config('services.google_calendar.timeout', 10));
    }

    /** Scambia il refresh token a lunga durata con un access token temporaneo. */
    private function accessToken(): string
    {
        $response = Http::asForm()->timeout((int) config('services.google_calendar.timeout', 10))->post(self::TOKEN_URL, [
            'client_id' => config('services.google_calendar.client_id'),
            'client_secret' => config('services.google_calendar.client_secret'),
            'refresh_token' => config('services.google_calendar.refresh_token'),
            'grant_type' => 'refresh_token',
        ]);

        $response->throw();
        $token = $response->json('access_token');

        if (! is_string($token) || $token === '') {
            throw new RuntimeException('Google non ha restituito un access token valido.');
        }

        return $token;
    }

    private function eventPayload(Lesson $lesson): array
    {
        $timezone = (string) config('services.google_calendar.timezone', 'Europe/Rome');
        $date = $lesson->data->format('Y-m-d');
        // I driver DB possono restituire HH:mm oppure HH:mm:ss: per Google basta
        // normalizzare sempre ai primi cinque caratteri.
        $start = Carbon::createFromFormat('Y-m-d H:i', $date.' '.substr($lesson->ora_inizio, 0, 5), $timezone);
        $end = Carbon::createFromFormat('Y-m-d H:i', $date.' '.substr($lesson->ora_fine, 0, 5), $timezone);
        $prefix = trim((string) config('services.google_calendar.event_prefix', 'Lezione doposcuola'));
        $description = array_filter([
            $lesson->argomento ? 'Argomento: '.$lesson->argomento : null,
            $lesson->note ? 'Note: '.$lesson->note : null,
            'Lezione gestita da Lezioni in ordine.',
        ]);

        return [
            'summary' => $prefix.' · '.$lesson->student->nome_completo,
            'description' => implode("\n\n", $description),
            'start' => ['dateTime' => $start->toRfc3339String(), 'timeZone' => $timezone],
            'end' => ['dateTime' => $end->toRfc3339String(), 'timeZone' => $timezone],
            'reminders' => [
                'useDefault' => false,
                'overrides' => [[
                    'method' => 'popup',
                    'minutes' => (int) config('services.google_calendar.reminder_minutes', 30),
                ]],
            ],
        ];
    }

    private function ensureConfigured(): void
    {
        if (! config('services.google_calendar.enabled')) {
            throw new RuntimeException('L’integrazione Google Calendar non è abilitata nel file .env.');
        }

        foreach (['client_id', 'client_secret', 'refresh_token', 'calendar_id'] as $key) {
            if (blank(config("services.google_calendar.{$key}"))) {
                throw new RuntimeException("Configurazione Google Calendar incompleta: manca {$key} nel file .env.");
            }
        }
    }
}
