<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\Student;
use App\Services\InvoiceNumberSuggester;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BillingController extends Controller
{
    public function __invoke(Request $request, InvoiceNumberSuggester $suggester): View
    {
        $filters = $request->validate([
            'studente_id' => ['nullable', 'uuid', Rule::exists('studenti', 'id')],
            'fatturazione' => ['nullable', Rule::in(['da_fatturare', 'fatturate', 'tutte'])],
            'dal' => ['nullable', 'date'],
            'al' => ['nullable', 'date'],
        ]);
        $billingStatus = $filters['fatturazione'] ?? 'da_fatturare';

        // Una lezione è fatturabile quando è stata svolta ed è marcata come tale.
        $baseQuery = Lesson::with('student')
            ->where('stato', 'svolta')
            ->where('da_fatturare', true)
            ->when($filters['studente_id'] ?? null, fn ($query, $studentId) => $query->where('studente_id', $studentId))
            ->when($filters['dal'] ?? null, fn ($query, $from) => $query->whereDate('data', '>=', $from))
            ->when($filters['al'] ?? null, fn ($query, $to) => $query->whereDate('data', '<=', $to));

        // I riepiloghi non dipendono dal filtro di stato e rendono visibile il
        // carico complessivo anche quando la tabella mostra solo le righe aperte.
        $pendingTotal = (clone $baseQuery)->where('fatturata', false)->get()->sum('importo');
        $invoicedTotal = (clone $baseQuery)->where('fatturata', true)->get()->sum('importo');

        $lessons = $baseQuery
            ->when($billingStatus === 'da_fatturare', fn ($query) => $query->where('fatturata', false))
            ->when($billingStatus === 'fatturate', fn ($query) => $query->where('fatturata', true))
            ->orderByDesc('data')
            ->orderByDesc('ora_inizio')
            ->paginate(25)
            ->withQueryString();

        return view('billing.index', [
            'lessons' => $lessons,
            'students' => Student::orderBy('cognome')->orderBy('nome')->get(),
            'billingStatus' => $billingStatus,
            'pendingTotal' => $pendingTotal,
            'invoicedTotal' => $invoicedTotal,
            'suggestedInvoiceNumber' => $suggester->next(now()->year),
        ]);
    }
}
