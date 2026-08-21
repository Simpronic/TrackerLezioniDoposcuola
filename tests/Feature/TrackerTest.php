<?php

namespace Tests\Feature;

use App\Models\Lesson;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrackerTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_requires_login(): void
    {
        $this->get('/')->assertRedirect('/login');
        $this->get('/login')->assertOk()->assertSee('Area riservata');
    }

    public function test_environment_credentials_allow_access(): void
    {
        config(['tracker.login_user' => 'admin', 'tracker.login_password' => 'secret']);

        $this->post('/login', ['username' => 'admin', 'password' => 'secret'])
            ->assertRedirect('/');
        $this->get('/')->assertOk()->assertSee('Il tuo lavoro');
    }

    public function test_lesson_amount_uses_duration_and_snapshot_rate(): void
    {
        $student = Student::create([
            'nome' => 'Ada', 'cognome' => 'Rossi', 'anno_ingresso' => 2026,
            'attivo' => true, 'tariffa_oraria' => 20,
        ]);
        $lesson = Lesson::create([
            'studente_id' => $student->id, 'data' => '2026-08-21',
            'ora_inizio' => '15:00', 'ora_fine' => '16:30', 'stato' => 'svolta',
            'tariffa_oraria_applicata' => 20, 'fatturata' => false,
        ]);

        $this->assertSame(1.5, $lesson->durata_ore);
        $this->assertSame(30.0, $lesson->importo);
    }
}
