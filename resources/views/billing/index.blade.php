@extends('layouts.app')
@section('title', 'Fatturazione · Lezioni in ordine')
@section('content')
<section class="page-heading split"><div><p class="eyebrow">Amministrazione</p><h1>Fatturazione</h1><p>Le lezioni svolte che possono essere inserite in fattura.</p></div><a class="button primary" href="{{ route('lezioni.create') }}">＋ Registra lezione</a></section>

<section class="billing-summary" aria-label="Riepilogo fatturazione">
    <article><span>Da fatturare</span><strong>€ {{ number_format($pendingTotal, 2, ',', '.') }}</strong></article>
    <article><span>Già fatturato</span><strong>€ {{ number_format($invoicedTotal, 2, ',', '.') }}</strong></article>
    <article><span>Prossimo progressivo</span><strong>{{ $suggestedInvoiceNumber }}</strong></article>
</section>

<form class="filter-bar filters" method="get">
    <label>Studente<select name="studente_id"><option value="">Tutti</option>@foreach($students as $student)<option value="{{ $student->id }}" @selected(request('studente_id') === $student->id)>{{ $student->nome_completo }}</option>@endforeach</select></label>
    <label>Stato<select name="fatturazione"><option value="da_fatturare" @selected($billingStatus === 'da_fatturare')>Da fatturare</option><option value="fatturate" @selected($billingStatus === 'fatturate')>Fatturate</option><option value="tutte" @selected($billingStatus === 'tutte')>Tutte</option></select></label>
    <label>Dal<input type="date" name="dal" value="{{ request('dal') }}"></label>
    <label>Al<input type="date" name="al" value="{{ request('al') }}"></label>
    <button class="button secondary">Filtra</button><a class="text-link" href="{{ route('fatturazione.index') }}">Azzera</a>
</form>

<div class="table-card"><table><thead><tr><th>Data</th><th>Studente</th><th>Argomento</th><th>Ore</th><th>Importo</th><th>Fattura</th><th>Pagamento</th><th></th></tr></thead><tbody>
@forelse($lessons as $lesson)
<tr><td><strong>{{ $lesson->data->format('d/m/Y') }}</strong><small>{{ substr($lesson->ora_inizio, 0, 5) }}–{{ substr($lesson->ora_fine, 0, 5) }}</small></td><td>{{ $lesson->student->nome_completo }}</td><td class="topic">{{ $lesson->argomento ?: '—' }}</td><td>{{ number_format($lesson->durata_ore, 1, ',', '.') }} h</td><td><strong>€ {{ number_format($lesson->importo, 2, ',', '.') }}</strong></td><td>@if($lesson->fatturata)<span class="billing-tag invoiced">{{ $lesson->numero_fattura ?: 'Fatturata' }}</span>@else<span class="billing-tag billable">Da fatturare</span>@endif @if($lesson->data_fattura)<small>{{ $lesson->data_fattura->format('d/m/Y') }}</small>@endif</td><td>@if($lesson->data_pagamento)<span class="status svolta">Pagata</span><small>{{ $lesson->data_pagamento->format('d/m/Y') }}</small>@else<span class="muted">—</span>@endif</td><td class="actions"><a href="{{ route('lezioni.edit', $lesson) }}">Gestisci</a></td></tr>
@empty<tr><td colspan="8" class="empty-state">Nessuna lezione corrisponde ai filtri.</td></tr>@endforelse
</tbody></table></div>
<div class="pagination">{{ $lessons->links() }}</div>
@endsection
