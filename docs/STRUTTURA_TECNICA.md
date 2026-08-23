# Struttura tecnica di Tracker Lezioni Doposcuola

## 1. Scopo e stack

L’applicazione registra studenti e lezioni di doposcuola, segue fatturazione e pagamenti, calcola indicatori economici, esporta il registro annuale in Excel e può sincronizzare le lezioni programmate con Google Calendar.

- Backend: PHP 8.2+ e Laravel 12.
- Database: MySQL in produzione; SQLite in memoria durante i test.
- Interfaccia: Blade, HTML, CSS e JavaScript senza framework frontend.
- Esportazione: PhpSpreadsheet.
- Integrazione esterna: Google Calendar API tramite OAuth 2.0.

## 2. Organizzazione delle cartelle

| Percorso | Responsabilità |
| --- | --- |
| `app/Http/Controllers` | Ricezione richieste, validazione e preparazione delle viste. |
| `app/Http/Middleware` | Protezione delle pagine tramite la sessione di login. |
| `app/Models` | Modelli Eloquent `Student` e `Lesson`, relazioni e valori calcolati. |
| `app/Services` | Logica riutilizzabile: Calendar, Excel e progressivo fattura. |
| `config` | Configurazione Laravel e lettura delle variabili `.env`. |
| `database/migrations` | Definizione versionata dello schema MySQL. |
| `resources/views` | Template Blade divisi per area funzionale. |
| `resources/templates` | Modello Excel usato dall’esportazione annuale. |
| `public` | Front controller, CSS, JavaScript e risorse pubbliche. |
| `routes/web.php` | Mappa URL, controller, middleware e nomi delle rotte. |
| `storage` | Log, sessioni, cache, viste compilate e file temporanei. |
| `tests/Feature` | Test integrati di pagine, database, export e Calendar. |

`vendor` contiene le dipendenze Composer e non deve essere modificata manualmente.

## 3. Flusso di una richiesta

1. Il web server inoltra la richiesta a `public/index.php`.
2. Laravel carica configurazione, middleware e rotte.
3. `AuthenticateWithEnvironment` controlla il flag `env_authenticated` nella sessione.
4. La rotta invoca il controller corrispondente.
5. Il controller valida l’input e interroga i modelli Eloquent.
6. Il controller restituisce una vista Blade o un redirect.
7. Blade genera l’HTML; `public/css` e `public/js` gestiscono presentazione e interazioni leggere.

## 4. Modello dati

### `studenti`

Contiene anagrafica, stato, tariffa predefinita e dati del soggetto pagante. La chiave primaria è un UUID.

### `lezioni`

Contiene data, orari, argomento, stato, tariffa storicizzata, informazioni di fattura e pagamento e identificativi Google Calendar. Anche la chiave primaria è un UUID.

La relazione è:

```text
studenti (1) ──────── (N) lezioni
              studente_id
```

La chiave esterna usa `ON DELETE CASCADE` e `ON UPDATE CASCADE`. Eliminare uno studente elimina quindi anche tutte le sue lezioni.

La tariffa viene copiata dallo studente alla lezione al momento della registrazione. In questo modo una modifica futura della tariffa dello studente non altera gli importi storici.

L’importo non è memorizzato: `Lesson::getImportoAttribute()` lo calcola come durata per tariffa solo per le lezioni con stato `svolta`.

## 5. Aree applicative

### Autenticazione

`AuthController` confronta username e password con `APP_LOGIN_USER` e `APP_LOGIN_PASSWORD`. Dopo il login salva nella sessione il flag controllato dal middleware. È un’autenticazione semplice adatta a una singola utenza; per più utenti occorre migrare all’autenticazione Laravel basata sulla tabella `users`.

### Dashboard

`DashboardController` calcola maturato, incassato, da incassare, fatturato, da fatturare, ore e andamento mensile nell’intervallo richiesto.

### Studenti

`StudentController` gestisce inserimento, modifica, elenco ed esportazione Excel. `StudentWorkbookExporter` compila `resources/templates/ModelloBase.xlsx` per studente e anno scolastico.

### Lezioni

`LessonController` gestisce CRUD, normalizza i dati amministrativi e avvia facoltativamente la sincronizzazione Calendar. Una lezione pagata viene resa automaticamente fatturabile, fatturata e con stato fattura `pagata`.

### Fatturazione

`BillingController` elenca esclusivamente lezioni `svolta` con `da_fatturare = true`. Il filtro predefinito mostra quelle non ancora fatturate; sono disponibili anche fatturate e tutte.

`InvoiceNumberSuggester` legge i numeri già usati nell’anno, considera una sola volta eventuali duplicati appartenenti alla stessa fattura e propone il successivo nel formato `N/AAAA`. Il valore è un suggerimento modificabile, non un vincolo: la conferma definitiva avviene salvando la lezione.

### Google Calendar

`GoogleCalendarService` usa il refresh token per ottenere un access token temporaneo. Crea un evento quando la lezione non possiede un ID Google e aggiorna l’evento esistente negli altri casi. Un errore remoto non annulla il salvataggio locale della lezione.

## 6. Configurazione

Le variabili sensibili risiedono soltanto in `.env`; `.env.example` documenta i nomi senza contenere segreti. Le principali categorie sono:

- `APP_*`: ambiente, URL, debug e chiave di cifratura.
- `APP_LOGIN_*`: credenziali dell’unico accesso applicativo.
- `DB_*`: connessione MySQL.
- `CACHE_STORE`, `SESSION_DRIVER`, `QUEUE_CONNECTION`: infrastruttura Laravel.
- `GOOGLE_CALENDAR_*`: OAuth, calendario, fuso orario e promemoria.

In produzione usare `APP_ENV=production`, `APP_DEBUG=false`, cache e sessioni su file e coda sincrona quando l’hosting non offre processi permanenti.

## 7. Rotte principali

| Metodo e URL | Nome | Funzione |
| --- | --- | --- |
| `GET /` | `dashboard` | Dashboard e analytics. |
| `GET /login` | `login` | Modulo di accesso. |
| `GET /studenti` | `studenti.index` | Elenco studenti. |
| `GET /studenti/{id}/export-excel` | `studenti.export` | Registro annuale Excel. |
| `GET /lezioni` | `lezioni.index` | Registro completo. |
| `GET /fatturazione` | `fatturazione.index` | Lezioni fatturabili. |

Le rotte CRUD generate da `Route::resource` completano creazione, modifica ed eliminazione.

## 8. Verifica e manutenzione

Eseguire la suite automatica con:

```powershell
php artisan test
```

Prima di distribuire una modifica allo schema, creare una nuova migration e provarla su una copia del database. Su hosting senza terminale, esportare lo schema aggiornato in SQL e importarlo tramite phpMyAdmin.

I log applicativi sono in `storage/logs/laravel.log`. Attivare `APP_DEBUG=true` solo durante una diagnosi controllata e ripristinare subito `false`.

## 9. Punti di estensione

- Una tabella `fatture` separata permetterebbe di associare formalmente più lezioni alla stessa fattura e garantire l’unicità del progressivo.
- Un sistema utenti Laravel consentirebbe ruoli e accessi multipli.
- Un job asincrono renderebbe Calendar più resiliente su hosting con worker disponibili.
- Test aggiuntivi possono coprire concorrenza sul progressivo, autorizzazioni ed errori di rete.
