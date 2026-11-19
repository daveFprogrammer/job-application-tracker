# Job Application Tracker

[![tests](https://github.com/daveFprogrammer/job-application-tracker/actions/workflows/tests.yml/badge.svg)](https://github.com/daveFprogrammer/job-application-tracker/actions/workflows/tests.yml)
[![reminders](https://github.com/daveFprogrammer/job-application-tracker/actions/workflows/reminders.yml/badge.svg)](https://github.com/daveFprogrammer/job-application-tracker/actions/workflows/reminders.yml)
[![Live demo](https://img.shields.io/badge/demo-onrender.com-46e3b7?logo=render&logoColor=white)](https://job-application-tracker-wdwn.onrender.com)

**Demo live: [job-application-tracker-wdwn.onrender.com](https://job-application-tracker-wdwn.onrender.com)**: clicca **Try the demo** per entrare senza registrarti in un account condiviso con una board già popolata, oppure registrati con una tua email. Gira sul piano free di Render, quindi dopo 15 minuti di inattività va in sleep e la prima richiesta può impiegare 30-60 secondi.

Un tracker per la ricerca di lavoro: ogni candidatura è una card su una **board kanban** (Wishlist → Applied → Interviewing → Offer / Rejected) che si sposta con il **drag and drop**. Ogni candidatura ha i suoi **colloqui**, le sue **note** e i suoi **promemoria via email**, inviati in background da **code e job** di Laravel.

> Progetto portfolio pensato per una settimana di sviluppo: CRUD completo, autenticazione, drag and drop in Vue, job in coda e un deploy di produzione a costo zero.

## Funzionalità

- **Autenticazione completa** (Laravel Fortify): registrazione, verifica email, reset password, 2FA e passkey.
- **Board kanban** con drag and drop (SortableJS tramite `vue-draggable-plus`), aggiornamento ottimistico della UI e persistenza dell'ordine lato server.
- **Ricerca e filtri sulla board**: ricerca testuale su azienda, ruolo e sede, filtro per modalità di lavoro e opzione per nascondere i Rejected. I filtri vivono nella query string, quindi una vista filtrata si può salvare o condividere; mentre si filtra il drag and drop è in pausa, per non calcolare le posizioni su una colonna parziale.
- **CRUD candidature**: azienda, ruolo, link all'annuncio, sede, modalità di lavoro, RAL, data di invio, descrizione.
- **Colloqui** con tipo, data/ora, luogo o link, note e **promemoria automatico** il giorno prima (o un'ora prima se il colloquio è più vicino). Se il colloquio viene spostato, il promemoria lo segue.
- **Storico degli stati**: ogni passaggio di colonna (dal form o con il drag and drop) viene registrato, e la pagina della candidatura mostra la timeline e da quanti giorni è nello stato attuale.
- **Note** cronologiche per ogni candidatura (recap delle call, feedback, domande da fare).
- **Promemoria personalizzati** (es. "ricontatta il recruiter") inviati via email all'orario scelto.
- **Dashboard** con numeri della pipeline, tasso di risposta, prossimi colloqui e promemoria.
- **Multi-utente**: ognuno vede e modifica solo i propri dati (policy dedicata, coperta da test).
- **Account demo con un clic**: dalla landing o dal login si entra in un account condiviso con una board realistica. I dati si possono modificare liberamente e vengono ricostruiti ogni 24 ore, le email non partono mai, profilo e credenziali sono in sola lettura.

## Cosa dimostra

| Area                                | Dove guardare                                                                                                         |
| ----------------------------------- | --------------------------------------------------------------------------------------------------------------------- |
| CRUD, validazione, policy           | `app/Http/Controllers`, `app/Http/Requests`, `app/Policies`                                                           |
| Logica di dominio isolata in Action | `app/Actions/MoveJobApplication.php`, `app/Actions/SyncInterviewReminder.php`                                         |
| Drag and drop in Vue + Inertia      | `resources/js/pages/applications/Board.vue`                                                                           |
| Model observer e storico            | `app/Observers/JobApplicationObserver.php`, `app/Models/StatusChange.php`                                             |
| Code, job e scheduler               | `app/Console/Commands/DispatchDueReminders.php`, `app/Jobs/SendReminder.php`, `routes/console.php`                    |
| Idempotenza e concorrenza           | claim atomico di `queued_at` nel comando e controllo di `sent_at` nel job                                             |
| Test (PHPUnit, Postgres)            | `tests/Feature/Applications`, `tests/Feature/CronTest.php`                                                            |
| Account demo condiviso e sicuro     | `app/Actions/ResetDemoAccount.php`, `app/Http/Middleware/PreventDemoAccountChanges.php`, `tests/Feature/DemoTest.php` |
| Container e deploy                  | `Dockerfile`, `docker/`, `render.yaml`, `.github/workflows`                                                           |

## Stack

- **Backend**: PHP 8.4, Laravel 13, Fortify, PostgreSQL
- **Frontend**: Inertia.js 3, Vue 3 + TypeScript, Tailwind CSS 4, componenti shadcn-vue, Wayfinder (route tipizzate)
- **Locale**: Laravel Sail (Docker: app, PostgreSQL, Mailpit, queue worker, scheduler)
- **Produzione (free tier)**: Render (web service Docker con FrankenPHP), Neon (PostgreSQL serverless), un SMTP gratuito (es. Brevo), GitHub Actions (CI e cron)
- **Qualità**: PHPUnit, Larastan (livello 7), Pint, vue-tsc, Vite+ (lint e format)

## Architettura

```mermaid
flowchart LR
    U[Browser<br/>Vue + Inertia] -->|HTTPS| R[Render<br/>FrankenPHP + Laravel]
    R -->|SQL / TLS| N[(Neon<br/>PostgreSQL)]
    GH[GitHub Actions<br/>cron ogni 30 min] -->|POST /cron/run<br/>Bearer token| R
    R -->|SMTP| M[Brevo / SMTP]
    M --> I[Inbox utente]
```

### Il flusso dei promemoria

1. Un promemoria viene creato a mano o in automatico quando si fissa un colloquio.
2. Il comando `reminders:dispatch` cerca i promemoria scaduti e li "reclama" con un `UPDATE ... WHERE queued_at IS NULL`. Così due esecuzioni sovrapposte non mettono mai in coda lo stesso promemoria.
3. Per ogni promemoria parte un job `SendReminder` sulla coda `database`.
4. Il job invia la notifica email all'utente e imposta `sent_at`. Se il job viene ritentato dopo un invio riuscito, non manda una seconda email.

**In locale** il comando gira ogni minuto grazie al container `scheduler` (`schedule:work`) e il container `queue` processa i job.

**Su Render free** non esistono cron job né background worker, e l'istanza va in sleep dopo 15 minuti senza traffico. Per questo un workflow GitHub Actions (`.github/workflows/reminders.yml`) chiama ogni 30 minuti `POST /cron/run`, protetto da un bearer token. La richiesta sveglia l'istanza, esegue `reminders:dispatch`, svuota la coda con `queue:work --stop-when-empty` e, se sono passate più di 24 ore dall'ultimo reset, ricostruisce l'account demo (`demo:reset --if-stale`).

Ho scelto questo approccio, e non un worker sempre acceso, per restare nei limiti gratuiti di **entrambi** i servizi. Un worker che interroga il DB ogni pochi secondi terrebbe sveglio Neon (autosuspend dopo 5 minuti di inattività) e consumerebbe le ore di compute gratuite. Il compromesso è che i promemoria hanno una precisione di circa 30 minuti, più che sufficiente per un colloquio fissato al giorno dopo.

## Sviluppo locale (Laravel Sail)

Requisiti: Docker Desktop. PHP e Node servono solo per il primo `composer install`, poi si usa Sail. In alternativa, il primo passo si può fare con il container `laravelsail/php84-composer`.

```bash
git clone https://github.com/daveFprogrammer/job-application-tracker.git
cd job-application-tracker

cp .env.example .env
composer install

./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate --seed
./vendor/bin/sail npm install
./vendor/bin/sail npm run dev
```

| Servizio                  | URL                                                   |
| ------------------------- | ----------------------------------------------------- |
| App                       | http://localhost                                      |
| Mailpit (email in uscita) | http://localhost:8025                                 |
| PostgreSQL                | `localhost:5432` (utente `sail`, password `password`) |

Il seeder crea un utente di test, `test@example.com` con password `password`, con una board casuale, e l'account demo condiviso (pulsante **Try the demo**, attivo con `DEMO_ENABLED=true`). Per ricostruire la board demo: `./vendor/bin/sail artisan demo:reset`.

`sail up` avvia anche i container `queue` e `scheduler`: i promemoria scaduti arrivano in Mailpit entro un minuto.

### Test e qualità del codice

```bash
./vendor/bin/sail artisan test   # PHPUnit su PostgreSQL (database "testing")
./vendor/bin/sail composer lint  # Pint
./vendor/bin/sail composer types:check  # Larastan
npm run types:check              # vue-tsc
npm run check                    # lint + format (Vite+)
```

La CI (`.github/workflows/tests.yml`) esegue tutto questo su ogni push e pull request, con un servizio PostgreSQL, e verifica anche la build dell'immagine Docker di produzione.

## Deploy gratuito: Render + Neon

### 1. Database su Neon

1. Crea un progetto su [neon.tech](https://neon.tech) (regione `AWS Europe Central 1 (Frankfurt)`, vicina a Render Frankfurt).
2. Copia la **connection string pooled** (host con `-pooler`), ad esempio:
   `postgresql://user:password@ep-xxx-pooler.eu-central-1.aws.neon.tech/neondb?sslmode=require`

### 2. SMTP per le email

Va bene qualunque SMTP. [Brevo](https://www.brevo.com) offre 300 email al giorno gratis e permette di verificare un singolo indirizzo mittente, senza un dominio. Annota host (`smtp-relay.brevo.com`), login e chiave SMTP.

> **Porta 2525, non 587.** Da settembre 2025 i web service free di Render bloccano il traffico in uscita verso le porte SMTP 25, 465 e 587: la connessione resta appesa e la richiesta finisce in errore 500. Brevo accetta anche la porta `2525`, già impostata in `render.yaml`.

### 3. Web service su Render

1. Su [render.com](https://render.com): **New → Blueprint**, collega il repository. Render legge `render.yaml` e crea il web service Docker sul piano free.
2. Compila le variabili marcate `sync: false`:

    | Variabile                                                          | Valore                                                    |
    | ------------------------------------------------------------------ | --------------------------------------------------------- |
    | `APP_KEY`                                                          | output di `./vendor/bin/sail artisan key:generate --show` |
    | `APP_URL`                                                          | `https://<nome-servizio>.onrender.com`                    |
    | `DB_URL`                                                           | la connection string di Neon                              |
    | `MAIL_HOST`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS` | i dati SMTP                                               |

    `CRON_SECRET` viene generato da Render: copialo, serve al passo 4.

3. Al primo deploy il container esegue `migrate --force` e `optimize` (vedi `docker/entrypoint.sh`). Il piano free non ha pre-deploy command, quindi le migrazioni girano al boot (una sola istanza, quindi niente race condition).

### 4. Cron dei promemoria su GitHub Actions

Nel repository GitHub, vai in **Settings → Secrets and variables → Actions** e aggiungi:

- `APP_URL`: lo stesso URL di Render
- `CRON_SECRET`: lo stesso valore della variabile su Render

Il workflow `reminders` parte ogni 30 minuti e si può lanciare anche a mano da **Actions → reminders → Run workflow**. Senza i secret si chiude senza fare nulla.

### Limiti del free tier

- **Cold start**: dopo 15 minuti senza traffico Render sospende l'istanza, e la prima richiesta impiega fino a circa un minuto.
- **Cron best effort**: GitHub può ritardare i workflow schedulati e li disattiva dopo 60 giorni senza attività nel repository.
- **SMTP solo su porta 2525**: il piano free di Render blocca in uscita le porte 25, 465 e 587 (vedi il passo 2).
- **Precisione dei promemoria**: circa 30 minuti (vedi sopra).
- **Fuso orario unico**: le date si inseriscono nel fuso `APP_TIMEZONE` (default `Europe/Rome`).

### Evoluzione verso AWS

L'immagine Docker è già pronta per un orchestratore. Il passaggio naturale è questo: ECS (task web + task worker con `queue:work`), RDS PostgreSQL, scheduler con EventBridge o un cron container, e SES per le email. Il codice applicativo non cambia: basta rimuovere il workflow `reminders` e avviare un worker sempre acceso.

## Struttura del progetto

```text
app/
├── Actions/                 # logica di dominio (riordino board, promemoria colloqui, reset demo)
├── Console/Commands/        # reminders:dispatch, demo:reset
├── Enums/                   # ApplicationStatus, InterviewType, WorkMode
├── Http/Controllers/        # JobApplication, Interview, Note, Reminder, Dashboard, Demo, Cron
├── Http/Middleware/         # PreventDemoAccountChanges (account demo in sola lettura)
├── Jobs/SendReminder.php    # job in coda
├── Models/                  # JobApplication, Interview, Note, Reminder, StatusChange
├── Observers/               # storico degli stati delle candidature
├── Notifications/           # email del promemoria
└── Policies/                # autorizzazione per proprietario
resources/js/pages/
├── Dashboard.vue
└── applications/            # Board (kanban), Show, Create, Edit
docker/                      # Caddyfile, php.ini, entrypoint di produzione
render.yaml                  # Blueprint Render
compose.yaml                 # Laravel Sail
```

## Licenza

[MIT](LICENSE)
