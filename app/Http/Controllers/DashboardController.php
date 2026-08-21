<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        // In assenza di filtri la dashboard mostra l'anno solare corrente.
        $request->validate(['dal' => ['nullable', 'date'], 'al' => ['nullable', 'date']]);
        $from = Carbon::parse($request->input('dal', now()->startOfYear()->toDateString()))->startOfDay();
        $to = Carbon::parse($request->input('al', now()->endOfYear()->toDateString()))->endOfDay();
        if ($from->gt($to)) {
            // Un intervallo inserito al contrario viene normalizzato senza dare errore.
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        // Carichiamo una sola volta le lezioni del periodo: i KPI successivi lavorano
        // sulla collection in memoria e condividono quindi le stesse regole di calcolo.
        $lessons = Lesson::with('student')->whereBetween('data', [$from->toDateString(), $to->toDateString()])->get();
        $completed = $lessons->where('stato', 'svolta');
        $paid = $completed->filter(fn (Lesson $lesson) => $lesson->data_pagamento !== null);
        $invoiced = $completed->where('fatturata', true);

        // Serie mensile usata dal grafico: maturato e incassato restano valori distinti.
        $monthly = $completed->groupBy(fn (Lesson $lesson) => $lesson->data->format('Y-m'))
            ->map(fn ($items, $month) => [
                'month' => $month,
                'label' => Carbon::createFromFormat('Y-m', $month)->locale('it')->translatedFormat('M Y'),
                'maturato' => round($items->sum('importo'), 2),
                'incassato' => round($items->whereNotNull('data_pagamento')->sum('importo'), 2),
            ])->sortKeys()->values();

        return view('dashboard', [
            'from' => $from, 'to' => $to, 'monthly' => $monthly,
            'maturato' => $completed->sum('importo'),
            'incassato' => $paid->sum('importo'),
            'daIncassare' => $completed->whereNull('data_pagamento')->sum('importo'),
            'daFatturare' => $completed->where('da_fatturare', true)->where('fatturata', false)->sum('importo'),
            'ore' => $completed->sum('durata_ore'),
            'numeroLezioni' => $completed->count(),
            'fatturato' => $invoiced->sum('importo'),
            'studentiAttivi' => Student::where('attivo', true)->count(),
            'prossimeLezioni' => Lesson::with('student')->where('data', '>=', today())->where('stato', 'programmata')->orderBy('data')->orderBy('ora_inizio')->limit(6)->get(),
        ]);
    }
}
