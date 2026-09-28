<?php

namespace Tests\Feature;

use App\Services\CalendarConnectionStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CalendarNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_preference_is_persistent_and_requires_login(): void
    {
        config(['services.google_calendar.enabled' => false]);
        $this->post(route('google-calendar.notifications'), ['notifications_enabled' => 0])->assertRedirect('/login');
        $this->withSession(['env_authenticated' => true])->get('/')->assertSee('Google Calendar non è connesso.');
        $this->post(route('google-calendar.notifications'), ['notifications_enabled' => 0])->assertRedirect();
        $this->assertDatabaseHas('calendar_preferences', ['id' => 1, 'notifications_enabled' => 0]);
        $this->get('/')->assertDontSee('Google Calendar non è connesso.');
        $this->post(route('google-calendar.notifications'), ['notifications_enabled' => 1])->assertRedirect();
        $this->get('/')->assertSee('Google Calendar non è connesso.');
    }

    public function test_token_validation_is_cached_and_distinguishes_temporary_errors(): void
    {
        config(['services.google_calendar' => [
            'enabled' => true, 'client_id' => 'id', 'client_secret' => 'secret',
            'calendar_id' => 'primary', 'refresh_token' => 'legacy-token',
        ]]);
        Cache::flush();
        Http::fake(['oauth2.googleapis.com/token' => Http::sequence()
            ->push(['error' => 'invalid_grant'], 400)
            ->push(['error' => 'server_error'], 503)
            ->push(['access_token' => 'valid-token'])]);
        $status = app(CalendarConnectionStatus::class);
        $this->assertSame('disconnected', $status->check());
        $this->assertSame('disconnected', $status->check());
        Http::assertSentCount(1);
        $this->travel(16)->minutes();
        $this->assertSame('unknown', $status->check());
        $this->travel(16)->minutes();
        $this->assertSame('connected', $status->check());
        Http::assertSentCount(3);
        $this->travelBack();
    }

    public function test_disabled_notifications_do_not_probe_google_on_other_pages(): void
    {
        config(['services.google_calendar' => [
            'enabled' => true, 'client_id' => 'id', 'client_secret' => 'secret',
            'calendar_id' => 'primary', 'refresh_token' => 'legacy-token',
        ]]);
        Http::fake();
        $this->withSession(['env_authenticated' => true])->post(route('google-calendar.notifications'), ['notifications_enabled' => 0]);
        $this->get('/')->assertOk()->assertDontSee('Google Calendar non è connesso.');
        Http::assertNothingSent();
    }
}
