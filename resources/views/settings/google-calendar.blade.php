@extends('layouts.app')
@section('title', 'Google Calendar · Lezioni in ordine')
@section('content')
<section class="page-heading"><p class="eyebrow">Impostazioni</p><h1>Google Calendar</h1><p>Gestisci l’autorizzazione usata per creare e aggiornare gli eventi delle lezioni.</p></section>

<section class="calendar-settings-card">
    <div class="calendar-connection-status">
        <span @class(['connection-dot', 'connected' => $connectionStatus === 'connected'])></span>
        <div><strong>{{ ['connected' => 'Account collegato', 'disconnected' => 'Collegamento richiesto', 'unknown' => 'Verifica temporaneamente non disponibile'][$connectionStatus] }}</strong><small>@if($connection)Ultimo collegamento: {{ $connection->connected_at?->format('d/m/Y H:i') }}@elseif($hasLegacyToken)È in uso il refresh token configurato nel file .env.@else Non è presente un token utilizzabile.@endif</small></div>
    </div>

    @if($configured)
    <form method="post" action="{{ route('google-calendar.redirect') }}">@csrf<button class="button primary">{{ $connection || $hasLegacyToken ? 'Ricollega Google Calendar' : 'Collega Google Calendar' }}</button></form>
    @else
    <div class="alert error">Inserisci prima client ID e client secret di Google Calendar nel file <code>.env</code>.</div>
    @endif

    <div class="calendar-setup-note">
        <h2>Notifiche nell’app</h2>
        <form method="post" action="{{ route('google-calendar.notifications') }}">
            @csrf
            <input type="hidden" name="notifications_enabled" value="0">
            <label class="calendar-notification-toggle"><input type="checkbox" role="switch" name="notifications_enabled" value="1" @checked($notificationsEnabled)> Avvisami quando Google Calendar non è connesso</label>
            <p>Il controllo viene aggiornato al massimo ogni 15 minuti durante l’utilizzo dell’app. Disattivare l’avviso non scollega l’account e non disabilita la sincronizzazione.</p>
            <button class="button secondary">Salva preferenza</button>
        </form>
    </div>
    <div class="calendar-setup-note">
        <h2>URI di reindirizzamento</h2>
        <p>Aggiungi esattamente questo indirizzo tra gli URI autorizzati del client OAuth nella Google Cloud Console:</p>
        <code>{{ $redirectUri }}</code>
    </div>
</section>

<section class="calendar-help panel">
    <h2>Come funziona</h2>
    <p>L’access token, che dura poco, viene rigenerato automaticamente a ogni sincronizzazione. Se Google invalida il refresh token dopo il periodo di test, torna in questa pagina e usa <strong>Ricollega Google Calendar</strong>: verrai inviato alla schermata di consenso e il nuovo token sarà salvato cifrato nel database.</p>
</section>
@endsection
