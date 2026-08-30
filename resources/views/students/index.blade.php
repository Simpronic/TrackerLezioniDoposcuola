@extends('layouts.app')
@section('title', 'Studenti · Lezioni in ordine')
@section('content')
<section class="page-heading split">
    <div><p class="eyebrow">Rubrica</p><h1>Studenti</h1><p>Tariffe e informazioni del pagante sempre aggiornate.</p></div>
    <a class="button primary" href="{{ route('studenti.create') }}">＋ Nuovo studente</a>
</section>
<form class="filter-bar" method="get"><label class="grow">Cerca<input type="search" name="q" value="{{ request('q') }}" placeholder="Nome o cognome"></label><button class="button secondary">Cerca</button></form>
<div class="table-card"><table><thead><tr><th>Studente</th><th>Ingresso</th><th>Tariffa</th><th>Lezioni</th><th>Stato</th><th>Registro Excel</th><th></th></tr></thead><tbody>
@forelse($students as $student)
<tr>
    <td><strong>{{ $student->nome_completo }}</strong><small>{{ $student->pagante_codice_fiscale ?: 'Dati pagante non completi' }}</small></td>
    <td>{{ $student->anno_ingresso }}</td>
    <td>€ {{ number_format($student->tariffa_oraria, 2, ',', '.') }}/h</td>
    <td>{{ $student->lessons_count }}</td>
    <td><span class="status {{ $student->attivo ? 'svolta' : 'annullata' }}">{{ $student->attivo ? 'attivo' : 'non attivo' }}</span></td>
    <td><form class="export-form" method="get" action="{{ route('studenti.export', $student) }}"><select name="anno" aria-label="Anno scolastico">@foreach($academicYears as $year)<option value="{{ $year }}" @selected($year === $academicStart)>{{ $year }}/{{ $year + 1 }}</option>@endforeach</select><button class="button secondary" title="Scarica Excel">Scarica .xlsx</button></form></td>
    <td class="actions"><button type="button" class="action-button" data-open-dialog="student-stats-{{ $student->id }}">Statistiche</button><a href="{{ route('lezioni.create', ['studente_id' => $student->id]) }}" title="Aggiungi lezione">＋</a><a href="{{ route('studenti.edit', $student) }}">Modifica</a></td>
</tr>
@empty
<tr><td colspan="7" class="empty-state">Nessuno studente trovato.</td></tr>
@endforelse
</tbody></table></div>
{{ $students->links('pagination::simple-default') }}

@foreach($students as $student)
<dialog class="student-stats-dialog" id="student-stats-{{ $student->id }}" aria-labelledby="student-stats-title-{{ $student->id }}">
    <div class="dialog-heading"><div><p class="eyebrow">Storico completo</p><h2 id="student-stats-title-{{ $student->id }}">{{ $student->nome_completo }}</h2></div><button type="button" class="dialog-close" data-close-dialog aria-label="Chiudi">×</button></div>
    <div class="student-stats-grid">
        <article><span>Lezioni svolte</span><strong>{{ $student->statistics['completed'] }}</strong></article>
        <article><span>Lezioni annullate</span><strong>{{ $student->statistics['cancelled'] }}</strong></article>
        <article><span>Totale pagato</span><strong>€ {{ number_format($student->statistics['paid_total'], 2, ',', '.') }}</strong><small>Lezioni saldate</small></article>
        <article @class(['has-debt' => $student->statistics['debt_count'] > 0])><span>Debiti</span><strong>{{ $student->statistics['debt_count'] > 0 ? 'Sì' : 'No' }}</strong><small>@if($student->statistics['debt_count'] > 0){{ $student->statistics['debt_count'] }} {{ $student->statistics['debt_count'] === 1 ? 'lezione' : 'lezioni' }} · € {{ number_format($student->statistics['debt_total'], 2, ',', '.') }}@else Nessuna lezione da saldare @endif</small></article>
    </div>
    <div class="dialog-actions"><a class="button secondary" href="{{ route('lezioni.index', ['studente_id' => $student->id]) }}">Vedi lezioni</a><button type="button" class="button primary" data-close-dialog>Chiudi</button></div>
</dialog>
@endforeach
@endsection
