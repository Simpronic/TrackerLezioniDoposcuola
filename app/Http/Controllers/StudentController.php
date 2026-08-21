<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Services\StudentWorkbookExporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentController extends Controller
{
    public function index(Request $request): View
    {
        $students = Student::withCount('lessons')
            ->when($request->filled('q'), fn ($query) => $query->where(function ($query) use ($request): void {
                $query->where('nome', 'like', '%'.$request->q.'%')->orWhere('cognome', 'like', '%'.$request->q.'%');
            }))
            ->orderByDesc('attivo')->orderBy('cognome')->orderBy('nome')->paginate(20)->withQueryString();

        // L'anno del registro parte a settembre: gennaio-agosto appartengono
        // all'anno scolastico iniziato nell'anno solare precedente.
        $academicStart = now()->month >= 9 ? now()->year : now()->year - 1;
        $academicYears = range($academicStart, $academicStart - 5);

        return view('students.index', compact('students', 'academicYears', 'academicStart'));
    }

    public function create(): View
    {
        return view('students.form', ['student' => new Student]);
    }

    public function store(Request $request): RedirectResponse
    {
        Student::create($this->validated($request));

        return redirect()->route('studenti.index')->with('success', 'Studente aggiunto.');
    }

    public function edit(Student $studenti): View
    {
        return view('students.form', ['student' => $studenti]);
    }

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

    public function export(Request $request, Student $student, StudentWorkbookExporter $exporter): StreamedResponse
    {
        $data = $request->validate(['anno' => ['required', 'integer', 'min:2000', 'max:'.(now()->year + 1)]]);
        $spreadsheet = $exporter->make($student, (int) $data['anno']);
        $studentName = Str::slug($student->cognome.'_'.$student->nome, '_');
        $filename = sprintf('Lezioni_%s_%d-%d.xlsx', $studentName, $data['anno'], $data['anno'] + 1);

        // Lo streaming evita di salvare sul server una copia permanente del registro.
        return response()->streamDownload(function () use ($spreadsheet): void {
            IOFactory::createWriter($spreadsheet, 'Xlsx')->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
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
