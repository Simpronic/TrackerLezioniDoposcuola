@extends('layouts.app')
@section('title', 'Panoramica · Lezioni in ordine')
@section('content')
<section class="page-heading split">
    <div><p class="eyebrow">Panoramica</p><h1>Il tuo lavoro, a colpo d’occhio.</h1><p>Lezioni, ore e incassi nel periodo selezionato.</p></div>
    <a class="button primary" href="{{ route('lezioni.create') }}">＋ Registra lezione</a>
</section>
<form class="filter-bar" method="get">
    <label>Dal<input type="date" name="dal" value="{{ $from->toDateString() }}"></label>
    <label>Al<input type="date" name="al" value="{{ $to->toDateString() }}"></label>
    <button class="button secondary">Aggiorna periodo</button>
    <a class="text-link" href="{{ route('dashboard') }}">Anno corrente</a>
</form>
<section class="kpi-grid">
    <article class="kpi accent"><span>Incassato</span><strong>€ {{ number_format($incassato, 2, ',', '.') }}</strong><small>pagamenti ricevuti</small></article>
    <article class="kpi"><span>Maturato</span><strong>€ {{ number_format($maturato, 2, ',', '.') }}</strong><small>su lezioni svolte</small></article>
    <article class="kpi warning"><span>Da incassare</span><strong>€ {{ number_format($daIncassare, 2, ',', '.') }}</strong><small>saldo ancora aperto</small></article>
    <article class="kpi"><span>Ore svolte</span><strong>{{ number_format($ore, 1, ',', '.') }}</strong><small>{{ $numeroLezioni }} lezioni · {{ $studentiAttivi }} studenti attivi</small></article>
</section>
<section class="dashboard-grid">
    <article class="panel chart-panel"><div class="panel-title"><div><p class="eyebrow">Andamento</p><h2>Incassi mensili</h2></div><span class="legend"><i></i> Incassato <i></i> Maturato</span></div>
        <div class="chart-wrap"><canvas id="revenueChart" aria-label="Grafico incassi mensili"></canvas><p id="chartEmpty" class="empty-state" hidden>Nessuna lezione svolta nel periodo.</p></div>
    </article>
    <article class="panel"><div class="panel-title"><div><p class="eyebrow">Agenda</p><h2>Prossime lezioni</h2></div><a href="{{ route('lezioni.index', ['stato' => 'programmata']) }}">Vedi tutte</a></div>
        <div class="agenda">@forelse($prossimeLezioni as $lesson)<a href="{{ route('lezioni.edit', $lesson) }}" class="agenda-item"><time><strong>{{ $lesson->data->format('d') }}</strong><span>{{ $lesson->data->locale('it')->translatedFormat('M') }}</span></time><span><strong>{{ $lesson->student->nome_completo }}</strong><small>{{ substr($lesson->ora_inizio,0,5) }}–{{ substr($lesson->ora_fine,0,5) }} · {{ $lesson->argomento ?: 'Argomento da definire' }}</small></span><span class="status {{ $lesson->stato }}">{{ str_replace('_',' ', $lesson->stato) }}</span></a>@empty<p class="empty-state">Nessuna lezione in programma.</p>@endforelse</div>
    </article>
</section>
<section class="quick-grid"><a href="{{ route('lezioni.index', ['pagamento' => 'da_pagare']) }}"><span>Pagamenti aperti</span><strong>€ {{ number_format($daIncassare, 2, ',', '.') }}</strong><small>Controlla le lezioni da saldare →</small></a><a href="{{ route('fatturazione.index') }}"><span>Da fatturare</span><strong>€ {{ number_format($daFatturare, 2, ',', '.') }}</strong><small>Gestisci dati e numeri fattura →</small></a><a href="{{ route('studenti.index') }}"><span>Rubrica</span><strong>{{ $studentiAttivi }}</strong><small>studenti attivi →</small></a></section>
@endsection
@push('scripts')<script>window.dashboardSeries = @json($monthly);</script>@endpush
