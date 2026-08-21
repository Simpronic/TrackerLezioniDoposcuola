<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\Student;
use App\Services\GoogleCalendarService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class LessonController extends Controller
{
    public function index(Request $request): View
    {
        // Ogni filtro è opzionale; withQueryString conserva i filtri nella paginazione.
        $lessons = Lesson::with('student')
            ->when($request->filled('studente_id'), fn ($q) => $q->where('studente_id', $request->studente_id))
            ->when($request->filled('stato'), fn ($q) => $q->where('stato', $request->stato))
            ->when($request->filled('pagamento'), function ($q) use ($request): void {
                $request->pagamento === 'pagata' ? $q->whereNotNull('data_pagamento') : $q->whereNull('data_pagamento');
            })
            ->when($request->filled('dal'), fn ($q) => $q->whereDate('data', '>=', $request->dal))
            ->when($request->filled('al'), fn ($q) => $q->whereDate('data', '<=', $request->al))
            ->orderByDesc('data')->orderByDesc('ora_inizio')->paginate(25)->withQueryString();

        return view('lessons.index', ['lessons' => $lessons, 'students' => Student::orderBy('cognome')->get()]);
    }

    public function create(Request $request): View
    {
        $lesson = new Lesson(['data' => today(), 'stato' => 'svolta', 'studente_id' => $request->studente_id]);

        return $this->form($lesson);
    }

    public function store(Request $request, GoogleCalendarService $calendar): RedirectResponse
    {
        $lesson = Lesson::create($this->validated($request));
        $calendarError = $this->syncCalendarIfRequested($request, $lesson, $calendar);

        $response = redirect()->route('lezioni.index')->with('success', 'Lezione registrata.');

        return $calendarError ? $response->with('error', $calendarError) : $response;
    }

    public function edit(Lesson $lezioni): View
    {
        return $this->form($lezioni);
    }

    public function update(Request $request, Lesson $lezioni, GoogleCalendarService $calendar): RedirectResponse
    {
        $lezioni->update($this->validated($request));
        $calendarError = $this->syncCalendarIfRequested($request, $lezioni, $calendar);

        $response = redirect()->route('lezioni.index')->with('success', 'Lezione aggiornata.');

        return $calendarError ? $response->with('error', $calendarError) : $response;
    }

    public function destroy(Lesson $lezioni): RedirectResponse
    {
        $lezioni->delete();

        return back()->with('success', 'Lezione eliminata.');
    }

    private function form(Lesson $lesson): View
    {
        return view('lessons.form', ['lesson' => $lesson, 'students' => Student::orderByDesc('attivo')->orderBy('cognome')->get()]);
    }

    private function validated(Request $request): array
    {
        // Questo metodo è condiviso da creazione e modifica per mantenere identiche
        // validazione e regole di normalizzazione dei dati di fatturazione.
        $data = $request->validate([
            'studente_id' => ['required', 'uuid', Rule::exists('studenti', 'id')],
            'data' => ['required', 'date'], 'ora_inizio' => ['required', 'date_format:H:i'],
            'ora_fine' => ['required', 'date_format:H:i', 'after:ora_inizio'],
            'argomento' => ['nullable', 'string', 'max:2000'],
            'stato' => ['required', Rule::in(['programmata', 'svolta', 'annullata', 'non_svolta'])],
            'tariffa_oraria_applicata' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'numero_fattura' => ['nullable', 'string', 'max:50'], 'data_fattura' => ['nullable', 'date'],
            'stato_fattura' => ['nullable', Rule::in(['emessa', 'inviata', 'pagata', 'scaduta', 'annullata'])],
            'data_pagamento' => ['nullable', 'date'], 'note' => ['nullable', 'string', 'max:4000'],
        ]);
        $student = Student::findOrFail($data['studente_id']);

        // La tariffa viene copiata sulla lezione: future modifiche allo studente non
        // alterano gli importi storici già registrati.
        $data['tariffa_oraria_applicata'] = $data['tariffa_oraria_applicata'] ?? $student->tariffa_oraria;
        $data['da_fatturare'] = $request->boolean('da_fatturare');
        $data['fatturata'] = $request->boolean('fatturata');
        if (! $data['fatturata']) {
            // Evita metadati di fattura incoerenti quando il relativo flag è spento.
            $data['numero_fattura'] = $data['data_fattura'] = $data['stato_fattura'] = null;
        }
        if ($data['fatturata'] || $data['data_pagamento']) {
            // Una lezione già fatturata o pagata deve necessariamente essere fatturabile.
            $data['da_fatturare'] = true;
        }
        if ($data['data_pagamento']) {
            // Il pagamento rappresenta lo stato finale del flusso di fatturazione.
            $data['fatturata'] = true;
            $data['stato_fattura'] = 'pagata';
        }

        return $data;
    }

    /** Sincronizza solo su richiesta e senza perdere la lezione se Google non risponde. */
    private function syncCalendarIfRequested(Request $request, Lesson $lesson, GoogleCalendarService $calendar): ?string
    {
        if (! $request->boolean('add_to_google_calendar') || $lesson->stato !== 'programmata') {
            return null;
        }

        try {
            $calendar->sync($lesson);
        } catch (Throwable $exception) {
            report($exception);

            return 'La lezione è stata salvata, ma non è stato possibile sincronizzarla con Google Calendar. Controlla configurazione e log.';
        }

        return null;
    }
}
