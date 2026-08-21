<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Lezioni in ordine')</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<header class="site-header">
    <a class="brand" href="{{ route('dashboard') }}"><span class="brand-mark">L</span><span>Lezioni <em>in ordine</em></span></a>
    <button class="nav-toggle" type="button" aria-label="Apri menu" aria-expanded="false">☰</button>
    <nav class="nav-links" aria-label="Navigazione principale">
        <a @class(['active' => request()->routeIs('dashboard')]) href="{{ route('dashboard') }}">Panoramica</a>
        <a @class(['active' => request()->routeIs('lezioni.*')]) href="{{ route('lezioni.index') }}">Lezioni</a>
        <a @class(['active' => request()->routeIs('studenti.*')]) href="{{ route('studenti.index') }}">Studenti</a>
        <form action="{{ route('logout') }}" method="post">@csrf<button class="link-button">Esci</button></form>
    </nav>
</header>
<main class="page-shell">
    @if(session('success'))<div class="alert success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert error">{{ session('error') }}</div>@endif
    @if($errors->any())<div class="alert error"><strong>Controlla i dati inseriti.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    @yield('content')
</main>
<script src="{{ asset('js/app.js') }}" defer></script>
@stack('scripts')
</body>
</html>
