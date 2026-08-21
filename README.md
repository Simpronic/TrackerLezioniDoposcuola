# Lezioni in ordine

Applicazione Laravel minimale per registrare lezioni di doposcuola, studenti, pagamenti, fatture e analisi degli incassi. Riprende il modello Excel originale e il DBML fornito.

## Requisiti

- PHP 8.2+ con `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype` e `json`
- MySQL 8 o MariaDB 10.6+
- Composer 2
- Apache o Nginx con document root sulla cartella `public`

Non servono Node.js, npm, code di lavoro o servizi esterni: CSS e JavaScript sono già in `public/`.

## Installazione

```bash
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Configura `.env`:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tuo-dominio.example
APP_LOGIN_USER=admin
APP_LOGIN_PASSWORD=usa-una-password-lunga-e-unica
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=nome_database
DB_USERNAME=nome_utente
DB_PASSWORD=password_database
```

Se `APP_LOGIN_PASSWORD` resta vuota, l'accesso viene rifiutato. Il file `.env` non va pubblicato né versionato.

## Hosting condiviso o gratuito

1. Esegui Composer localmente se il provider non lo offre, poi carica anche `vendor/`.
2. Punta il dominio a `public/`. Se il provider impone `public_html`, copia lì il contenuto di `public/` e adatta i due percorsi in `index.php`.
3. Rendi scrivibili `storage/` e `bootstrap/cache/`.
4. Esegui `php artisan migrate --force` dal terminale del provider.

## Regole di calcolo

- Durata = ora fine − ora inizio.
- Importo = durata × tariffa salvata sulla lezione; solo `svolta` genera importo.
- Incassato = lezioni svolte con `data_pagamento`.
- Da incassare = lezioni svolte senza `data_pagamento`.
- La tariffa della lezione è uno snapshot: cambiare quella dello studente non modifica lo storico.
