<?php

namespace Tests\Feature;

use App\Models\Lesson;
use App\Models\Student;
use App\Services\GoogleCalendarService;
use App\Services\InvoiceNumberSuggester;
use App\Services\StudentWorkbookExporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PhpOffice\PhpSpreadsheet\IOFactory;
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

    public function test_student_workbook_is_filled_from_the_template(): void
    {
        $student = Student::create([
            'nome' => 'Ada', 'cognome' => 'Rossi', 'anno_ingresso' => 2026,
            'attivo' => true, 'tariffa_oraria' => 25,
        ]);
        Lesson::create([
            'studente_id' => $student->id, 'data' => '2026-09-12',
            'ora_inizio' => '15:00', 'ora_fine' => '16:30', 'argomento' => 'Equazioni',
            'stato' => 'svolta', 'tariffa_oraria_applicata' => 25,
            'da_fatturare' => true, 'fatturata' => false,
        ]);

        $workbook = app(StudentWorkbookExporter::class)->make($student, 2026);
        $sheet = $workbook->getSheetByName('Settembre');

        $this->assertSame('Equazioni', $sheet->getCell('E3')->getValue());
        $this->assertSame('No', $sheet->getCell('F3')->getValue());
        $this->assertSame('=(C3-B3)*24', $sheet->getCell('D3')->getValue());
        $this->assertSame(25.0, $sheet->getCell('G3')->getValue());
        $this->assertTrue($sheet->getColumnDimension('G')->getVisible() === false);

        $path = storage_path('framework/testing/registro-studente.xlsx');
        IOFactory::createWriter($workbook, 'Xlsx')->save($path);
        $this->assertFileExists($path);

        $savedWorkbook = IOFactory::load($path);
        $this->assertSame('Equazioni', $savedWorkbook->getSheetByName('Settembre')->getCell('E3')->getValue());
        $savedWorkbook->disconnectWorksheets();
        unlink($path);
        $workbook->disconnectWorksheets();
    }

    public function test_scheduled_lesson_can_be_created_in_google_calendar(): void
    {
        config(['services.google_calendar' => [
            'enabled' => true,
            'client_id' => 'client-id',
            'client_secret' => 'client-secret',
            'refresh_token' => 'refresh-token',
            'calendar_id' => 'primary',
            'timezone' => 'Europe/Rome',
            'event_prefix' => 'Lezione doposcuola',
            'reminder_minutes' => 30,
            'timeout' => 10,
        ]]);

        Http::fake([
            'oauth2.googleapis.com/token' => Http::response(['access_token' => 'temporary-token']),
            'www.googleapis.com/calendar/v3/*' => Http::response([
                'id' => 'google-event-123',
                'htmlLink' => 'https://calendar.google.com/event?eid=123',
            ]),
        ]);

        $student = Student::create([
            'nome' => 'Ada', 'cognome' => 'Rossi', 'anno_ingresso' => 2026,
            'attivo' => true, 'tariffa_oraria' => 25,
        ]);
        $lesson = Lesson::create([
            'studente_id' => $student->id, 'data' => '2026-09-12',
            'ora_inizio' => '15:00', 'ora_fine' => '16:30', 'argomento' => 'Equazioni',
            'stato' => 'programmata', 'tariffa_oraria_applicata' => 25,
            'da_fatturare' => true, 'fatturata' => false,
        ]);

        app(GoogleCalendarService::class)->sync($lesson);

        $this->assertSame('google-event-123', $lesson->fresh()->google_calendar_event_id);
        Http::assertSent(fn ($request) => $request->url() === 'https://www.googleapis.com/calendar/v3/calendars/primary/events'
            && $request['summary'] === 'Lezione doposcuola · Ada Rossi'
            && $request['start']['timeZone'] === 'Europe/Rome');

        // Una seconda sincronizzazione deve aggiornare l'ID esistente via PUT.
        $lesson->refresh()->update(['argomento' => 'Equazioni di secondo grado']);
        app(GoogleCalendarService::class)->sync($lesson);

        Http::assertSent(fn ($request) => $request->method() === 'PUT'
            && $request->url() === 'https://www.googleapis.com/calendar/v3/calendars/primary/events/google-event-123');
        Http::assertSentCount(4);
    }

    public function test_invoice_number_is_suggested_from_the_yearly_sequence(): void
    {
        $student = Student::create([
            'nome' => 'Ada', 'cognome' => 'Rossi', 'anno_ingresso' => 2026,
            'attivo' => true, 'tariffa_oraria' => 25,
        ]);

        foreach (['3/2026', '8/2026', '8/2026'] as $index => $invoiceNumber) {
            Lesson::create([
                'studente_id' => $student->id,
                'data' => '2026-09-'.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
                'ora_inizio' => '15:00', 'ora_fine' => '16:00', 'stato' => 'svolta',
                'tariffa_oraria_applicata' => 25, 'da_fatturare' => true, 'fatturata' => true,
                'numero_fattura' => $invoiceNumber, 'data_fattura' => '2026-09-10',
            ]);
        }

        $this->assertSame('9/2026', app(InvoiceNumberSuggester::class)->next(2026));
        $this->assertSame('1/2027', app(InvoiceNumberSuggester::class)->next(2027));
    }

    public function test_billing_page_lists_only_completed_billable_lessons_by_default(): void
    {
        $student = Student::create([
            'nome' => 'Ada', 'cognome' => 'Rossi', 'anno_ingresso' => 2026,
            'attivo' => true, 'tariffa_oraria' => 25,
        ]);

        foreach ([
            ['argomento' => 'Da fatturare', 'stato' => 'svolta', 'da_fatturare' => true, 'fatturata' => false],
            ['argomento' => 'Gratuita', 'stato' => 'svolta', 'da_fatturare' => false, 'fatturata' => false],
            ['argomento' => 'Programmata', 'stato' => 'programmata', 'da_fatturare' => true, 'fatturata' => false],
            ['argomento' => 'Già fatturata', 'stato' => 'svolta', 'da_fatturare' => true, 'fatturata' => true],
        ] as $index => $values) {
            Lesson::create($values + [
                'studente_id' => $student->id,
                'data' => '2026-10-'.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
                'ora_inizio' => '15:00', 'ora_fine' => '16:00',
                'tariffa_oraria_applicata' => 25,
            ]);
        }

        $this->withSession(['env_authenticated' => true])
            ->get('/fatturazione')
            ->assertOk()
            ->assertSee('Da fatturare')
            ->assertDontSee('Gratuita')
            ->assertDontSee('Programmata')
            ->assertDontSee('Già fatturata');

        $this->withSession(['env_authenticated' => true])
            ->get('/fatturazione?fatturazione=fatturate')
            ->assertOk()
            ->assertSee('Già fatturata')
            ->assertDontSee('Gratuita');
    }
}
