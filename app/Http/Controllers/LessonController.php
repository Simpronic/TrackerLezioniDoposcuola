<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LessonController extends Controller
{
    public function index(Request $request): View
    {
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

    public function store(Request $request): RedirectResponse
    {
        Lesson::create($this->validated($request));
        return redirect()->route('lezioni.index')->with('success', 'Lezione registrata.');
    }

    public function edit(Lesson $lezioni): View { return $this->form($lezioni); }

    public function update(Request $request, Lesson $lezioni): RedirectResponse
    {
        $lezioni->update($this->validated($request));
        return redirect()->route('lezioni.index')->with('success', 'Lezione aggiornata.');
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
        $data['tariffa_oraria_applicata'] = $data['tariffa_oraria_applicata'] ?? $student->tariffa_oraria;
        $data['fatturata'] = $request->boolean('fatturata');
        if (! $data['fatturata']) { $data['numero_fattura'] = $data['data_fattura'] = $data['stato_fattura'] = null; }
        if ($data['data_pagamento']) { $data['fatturata'] = true; $data['stato_fattura'] = 'pagata'; }
        return $data;
    }
}
