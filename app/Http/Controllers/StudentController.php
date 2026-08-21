<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function index(Request $request): View
    {
        $students = Student::withCount('lessons')
            ->when($request->filled('q'), fn ($query) => $query->where(function ($query) use ($request): void {
                $query->where('nome', 'like', '%'.$request->q.'%')->orWhere('cognome', 'like', '%'.$request->q.'%');
            }))
            ->orderByDesc('attivo')->orderBy('cognome')->orderBy('nome')->paginate(20)->withQueryString();

        return view('students.index', compact('students'));
    }

    public function create(): View { return view('students.form', ['student' => new Student]); }

    public function store(Request $request): RedirectResponse
    {
        Student::create($this->validated($request));
        return redirect()->route('studenti.index')->with('success', 'Studente aggiunto.');
    }

    public function edit(Student $studenti): View { return view('students.form', ['student' => $studenti]); }

    public function update(Request $request, Student $studenti): RedirectResponse
    {
        $studenti->update($this->validated($request));
        return redirect()->route('studenti.index')->with('success', 'Studente aggiornato.');
    }

    public function destroy(Student $studenti): RedirectResponse
    {
        if ($studenti->lessons()->exists()) {
            return back()->with('error', 'Non puoi eliminare uno studente con lezioni registrate. Puoi renderlo non attivo.');
        }
        $studenti->delete();
        return back()->with('success', 'Studente eliminato.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'nome' => ['required', 'string', 'max:100'],
            'cognome' => ['required', 'string', 'max:100'],
            'anno_ingresso' => ['required', 'integer', 'min:2000', 'max:'.(now()->year + 1)],
            'tariffa_oraria' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'pagante_nome' => ['nullable', 'string', 'max:100'],
            'pagante_cognome' => ['nullable', 'string', 'max:100'],
            'pagante_codice_fiscale' => ['nullable', 'string', 'size:16', Rule::unique('studenti')->ignore($request->route('studenti'))],
            'pagante_indirizzo' => ['nullable', 'string', 'max:255'],
        ]);
        $data['attivo'] = $request->boolean('attivo');
        $data['pagante_codice_fiscale'] = $data['pagante_codice_fiscale'] ? strtoupper($data['pagante_codice_fiscale']) : null;
        return $data;
    }
}
