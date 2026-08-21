<!doctype html>
<html lang="it">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Accedi · My Tutor</title><link rel="stylesheet" href="{{ asset('css/app.css') }}"></head>
<body class="login-page">
<main class="login-card">
    <div class="brand brand-login"><span class="brand-mark">L</span><span>My <em>Tutor</em></span></div>
    <p class="eyebrow">Area riservata</p>
    <p class="muted">Accedi per registrare lezioni, pagamenti e fatture.</p>
    @if($errors->any())<div class="alert error">{{ $errors->first() }}</div>@endif
    <form action="{{ route('login.store') }}" method="post" class="stack">@csrf
        <label>Nome utente<input name="username" value="{{ old('username') }}" autocomplete="username" required autofocus></label>
        <label>Password<input type="password" name="password" autocomplete="current-password" required></label>
        <button class="button primary full">Entra nell’applicazione <span>→</span></button>
    </form>
</main>
</body></html>
