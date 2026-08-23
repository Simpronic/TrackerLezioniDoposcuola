# Lezioni in ordine

Applicazione Laravel per registrare lezioni di doposcuola, studenti, pagamenti, fatture e statistiche sugli incassi. Permette inoltre di scaricare, per ogni studente e anno scolastico, un registro basato sul modello Excel incluso.

La descrizione di architettura, cartelle, database e flussi applicativi è disponibile in [docs/STRUTTURA_TECNICA.md](docs/STRUTTURA_TECNICA.md).

## Funzioni principali

- Gestione di studenti e lezioni.
- Stati della lezione: programmata, svolta, annullata e non svolta.
- Distinzione tra lezione non fatturabile, da fatturare e già fatturata.
- Registrazione di fattura e pagamento.
- Dashboard con ore, maturato, incassato, da incassare e da fatturare.
- Esportazione Excel per studente e anno scolastico, da settembre ad agosto.
- Creazione e aggiornamento opzionale delle lezioni programmate su Google Calendar.
- Accesso tramite username e password configurati nel file `.env`.

## Requisiti

- PHP 8.2 o successivo. In locale il progetto è configurato per PHP 8.3 di Laragon.
- Estensioni PHP: `ctype`, `curl`, `dom`, `fileinfo`, `filter`, `gd`, `iconv`, `intl`, `json`, `libxml`, `mbstring`, `openssl`, `pdo_mysql`, `simplexml`, `tokenizer`, `xml`, `xmlreader`, `xmlwriter` e `zip`.
- MySQL 8 oppure MariaDB 10.6 o successivo.
- Composer 2.
- Laragon consigliato su Windows.
- Visual Studio Code con **PHP Debug** e **PHP Intelephense** per il debug.
- Node.js/npm sono opzionali: servono soltanto per ricompilare le risorse in `resources/`. I file pronti sono già in `public/`.

Per verificare PHP e le estensioni attive:

```powershell
php -v
php --ini
php -m
composer --version
```

Se `php` non punta alla versione di Laragon, usare il percorso completo oppure selezionare la versione da **Laragon → Menu → PHP → Version**.

## Prima installazione locale con Laragon

### 1. Avviare i servizi

Aprire Laragon e premere **Start All** per avviare Apache e MySQL.

Il progetto può stare in `C:\laragon\www`, ma non è obbligatorio se viene avviato con `php artisan serve`.

### 2. Installare le dipendenze PHP

Aprire il progetto in VS Code, quindi eseguire dal terminale integrato:

```powershell
composer install
```

Il comando installa Laravel, PHPSpreadsheet e le dipendenze di sviluppo dentro `vendor/`.

Per aggiornare intenzionalmente le versioni ammesse da `composer.json` si usa invece `composer update`. Normalmente usare `composer install`, perché rispetta le versioni registrate in `composer.lock`.

### 3. Configurare l'ambiente

Creare `.env` partendo dall'esempio:

```powershell
Copy-Item .env.example .env
php artisan key:generate
```

Creare in MySQL un database vuoto chiamato, per esempio, `tracker_lezioni`. Da HeidiSQL, incluso in Laragon, collegarsi al server locale e scegliere **Crea nuovo → Database**.

Configurare almeno queste variabili in `.env`:

```dotenv
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

APP_LOGIN_USER=admin
APP_LOGIN_PASSWORD=una-password-lunga

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tracker_lezioni
DB_USERNAME=root
DB_PASSWORD=
```

`APP_LOGIN_PASSWORD` non può essere vuota. `.env` contiene segreti: non deve essere pubblicato né aggiunto a Git.

Dopo modifiche alla configurazione, pulire l'eventuale cache:

```powershell
php artisan optimize:clear
```

### 4. Creare le tabelle

```powershell
php artisan migrate
```

Per inserire anche i dati dimostrativi:

```powershell
php artisan db:seed
```

Attenzione: `php artisan migrate:fresh` elimina tutte le tabelle e i dati prima di ricrearle. Usarlo soltanto su un database di sviluppo sacrificabile.

## Avvio dell'applicazione

### Metodo consigliato da VS Code

Dal terminale integrato:

```powershell
php artisan serve --host=127.0.0.1 --port=8000
```

Aprire [http://127.0.0.1:8000](http://127.0.0.1:8000). Per arrestare il server premere `Ctrl+C` nel terminale.

In alternativa, in VS Code eseguire il task **Laravel: serve veloce**. Questo avvio disabilita Xdebug ed è consigliato per l'uso normale e per l'esportazione Excel. Usare invece `F5` quando servono realmente i breakpoint: PHPSpreadsheet è sensibilmente più lento mentre Xdebug analizza il codice delle dipendenze.

### Metodo Laragon/Apache

Se il progetto è dentro `C:\laragon\www`, Laragon crea normalmente un virtual host automatico, per esempio `http://trackerlezionidoposcuola.test`. Riavviare Laragon dopo aver aggiunto o spostato il progetto.

La document root del sito deve essere `public/`. Con `artisan serve` questa configurazione è gestita automaticamente.

## Debug con VS Code e Xdebug

L'estensione **PHP Debug** di VS Code riceve le connessioni di debug, ma non installa Xdebug dentro PHP. Xdebug deve essere caricato dalla stessa versione PHP che avvia Laravel.

### 1. Verificare Xdebug

```powershell
php --ini
php -v
php -i | Select-String "xdebug.mode|xdebug.start_with_request|xdebug.client_port"
```

`php -v` deve mostrare `with Xdebug`. `php --ini` indica il `php.ini` realmente utilizzato; nell'installazione Laragon corrente è:

```text
C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.ini
```

Il percorso cambia cambiando versione PHP. Non modificare un altro `php.ini` senza prima controllare `php --ini`.

Impostazioni essenziali:

```ini
zend_extension="C:\percorso\corretto\php_xdebug.dll"
xdebug.mode=debug,develop
xdebug.start_with_request=yes
xdebug.client_host=127.0.0.1
xdebug.client_port=9003
```

La DLL deve trovarsi direttamente nella cartella `ext` della versione PHP attiva. Dopo modifiche al `php.ini`, riavviare Apache/Laragon e i terminali aperti.

### 2. Avviare una sessione di debug

Il repository contiene già `.vscode/launch.json`, `.vscode/tasks.json` e le impostazioni del percorso PHP.

1. Aprire in VS Code la cartella principale del progetto, quella che contiene `artisan`.
2. Impostare un breakpoint cliccando a sinistra del numero di riga in un file PHP.
3. Aprire **Esegui e debug** con `Ctrl+Shift+D`.
4. Selezionare **Laravel: debug completo**.
5. Premere `F5`: VS Code avvia `artisan serve` e ascolta Xdebug sulla porta 9003.
6. Visitare `http://127.0.0.1:8000` ed eseguire l'azione interessata.

Se Laravel è già servito da Apache/Laragon, scegliere **Xdebug: ascolta Laragon/Apache**, premere `F5` e usare il dominio `.test` nel browser.

Per eseguire tutti i test sotto debugger, scegliere **PHPUnit: tutti i test**.

### 3. Se il breakpoint non viene raggiunto

Controllare nell'ordine:

1. `php -v` mostra Xdebug e non contiene `Failed loading`.
2. VS Code sta ascoltando sulla porta 9003.
3. `xdebug.client_port` vale 9003.
4. Il server usa lo stesso eseguibile PHP configurato in `.vscode/settings.json`.
5. Il breakpoint è su una riga realmente eseguita dalla richiesta.
6. Dopo modifiche a `.env` è stato eseguito `php artisan optimize:clear`.
7. Per Apache, Laragon è stato riavviato dopo le modifiche al `php.ini`.

I log Laravel sono in `storage/logs/laravel.log`. Durante lo sviluppo `APP_DEBUG=true` mostra anche l'eccezione dettagliata; in produzione deve essere sempre `false`.

## Collegamento a Google Calendar

L'applicazione usa OAuth 2.0 con accesso offline. Il refresh token conservato nel `.env` viene scambiato automaticamente con access token temporanei, quindi non è necessario rifare il login Google a ogni lezione.

### 1. Preparare Google Cloud

1. Aprire la [Google Cloud Console](https://console.cloud.google.com/) e creare o selezionare un progetto.
2. In **API e servizi → Libreria**, abilitare **Google Calendar API**.
3. Configurare la schermata consenso OAuth e aggiungere il proprio account Google come utente di test.
4. In **Credenziali**, creare un client OAuth 2.0 di tipo **Applicazione web**.
5. Aggiungere come URI di reindirizzamento autorizzato `https://developers.google.com/oauthplayground`.

Se l'app OAuth esterna resta nello stato **Testing**, Google può far scadere il refresh token dopo sette giorni. Per un utilizzo continuativo occorre portare la schermata consenso in produzione, rispettando le indicazioni mostrate dalla console.

### 2. Ottenere il refresh token

1. Aprire [OAuth 2.0 Playground](https://developers.google.com/oauthplayground/).
2. Aprire le impostazioni tramite l'icona dell'ingranaggio.
3. Attivare **Use your own OAuth credentials** e inserire client ID e client secret creati prima.
4. Richiedere lo scope `https://www.googleapis.com/auth/calendar.events`.
5. Autorizzare l'accesso con l'account che possiede il calendario.
6. Scambiare il codice di autorizzazione e copiare il valore `refresh_token`.

Il flusso è descritto anche nella [documentazione OAuth per applicazioni web](https://developers.google.com/identity/protocols/oauth2/web-server). L'app crea gli eventi tramite il metodo ufficiale [`events.insert`](https://developers.google.com/workspace/calendar/api/v3/reference/events/insert) e aggiorna quelli collegati tramite [`events.update`](https://developers.google.com/workspace/calendar/api/v3/reference/events/update).

### 3. Configurare `.env`

```dotenv
GOOGLE_CALENDAR_ENABLED=true
GOOGLE_CALENDAR_CLIENT_ID=client-id.apps.googleusercontent.com
GOOGLE_CALENDAR_CLIENT_SECRET=client-secret
GOOGLE_CALENDAR_REFRESH_TOKEN=refresh-token
GOOGLE_CALENDAR_ID=primary
GOOGLE_CALENDAR_TIMEZONE=Europe/Rome
GOOGLE_CALENDAR_EVENT_PREFIX="Lezione doposcuola"
GOOGLE_CALENDAR_REMINDER_MINUTES=30
GOOGLE_CALENDAR_TIMEOUT=10
```

`GOOGLE_CALENDAR_ID=primary` usa il calendario principale dell'account che ha autorizzato l'app. Per un calendario secondario, copiare il relativo ID dalle impostazioni di Google Calendar.

Dopo aver modificato `.env`:

```powershell
php artisan optimize:clear
```

Nel form di una lezione con stato **Programmata** sarà disponibile il flag **Aggiungi questa lezione a Google Calendar**. Dopo il primo invio l'ID Google viene salvato nel database: modificando la lezione si aggiorna lo stesso evento, senza crearne uno nuovo.

Se Google non è raggiungibile o la configurazione non è valida, la lezione viene comunque salvata e l'applicazione mostra un avviso. Il dettaglio tecnico è registrato in `storage/logs/laravel.log`.

## Test e controlli di qualità

Eseguire tutti i test:

```powershell
php artisan test
```

Eseguire i test senza il rallentamento di Xdebug:

```powershell
php -d xdebug.mode=off vendor/bin/phpunit --testdox
```

Formattare il codice PHP e controllare le migrazioni:

```powershell
vendor/bin/pint
php artisan migrate:status
```

## Dipendenze frontend opzionali

Il sito usa già `public/css/app.css`, `public/css/features.css` e `public/js/app.js`, quindi npm non è necessario per il normale avvio.

Se si modificano i sorgenti dentro `resources/css` o `resources/js`:

```powershell
npm install
npm run dev
```

`npm run dev` resta attivo durante lo sviluppo. Per una build finale:

```powershell
npm run build
```

## Struttura del progetto

- `app/Http/Controllers/`: richieste HTTP, validazione e coordinamento.
- `app/Http/Middleware/`: protezione delle pagine tramite sessione.
- `app/Models/`: modelli Eloquent, relazioni e calcoli.
- `app/Services/StudentWorkbookExporter.php`: compilazione del registro Excel.
- `database/migrations/`: struttura e aggiornamenti del database.
- `resources/views/`: interfaccia Blade.
- `resources/templates/ModelloBase.xlsx`: modello Excel originale, non sovrascritto durante l'export.
- `public/`: CSS, JavaScript e punto di ingresso esposti dal server.
- `routes/web.php`: URL e middleware dell'applicazione.
- `storage/logs/laravel.log`: log per errori e debug.

## Regole di calcolo

- Durata = ora finale meno ora iniziale.
- Importo = durata per tariffa memorizzata sulla lezione; soltanto una lezione `svolta` genera importo.
- Incassato = lezioni svolte con `data_pagamento`.
- Da incassare = lezioni svolte senza `data_pagamento`.
- La tariffa della lezione è uno snapshot: cambiare la tariffa dello studente non modifica lo storico.
- `da_fatturare` indica se la lezione entra nel conteggio; `fatturata` indica che la fattura è stata prodotta.
- Una lezione pagata viene considerata automaticamente fatturabile e fatturata.
- Il registro Excel copre un anno scolastico da settembre ad agosto e usa la tariffa storica di ogni lezione.

## Installazione in produzione o hosting condiviso

Preparare localmente le dipendenze ottimizzate:

```powershell
composer install --no-dev --optimize-autoloader
```

Sul server:

1. Caricare il progetto e, se Composer non è disponibile, anche `vendor/`.
2. Puntare la document root a `public/`.
3. Rendere scrivibili `storage/` e `bootstrap/cache/`.
4. Creare `.env` con `APP_ENV=production`, `APP_DEBUG=false`, URL e credenziali reali.
5. Generare `APP_KEY` con `php artisan key:generate` se non è presente.
6. Eseguire:

```bash
php artisan migrate --force
php artisan optimize
```

Se il provider impone `public_html`, è preferibile configurare il dominio perché punti a `public/`. Copiare il contenuto di `public/` e modificare `index.php` va fatto soltanto quando il pannello hosting non permette di cambiare la document root.
