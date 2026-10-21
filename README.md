# Clinic Queue

[![CI](https://github.com/Marujinho/clinic-queue/actions/workflows/ci.yml/badge.svg)](https://github.com/Marujinho/clinic-queue/actions/workflows/ci.yml)

Clinic Queue is a queue and appointment management system for walk-in clinics. It covers the
full front-desk workflow: registering patients, scheduling appointments with conflict
detection, checking patients into service queues, and calling them up on a live queue board —
with every action gated by role-based permissions.

Built with **Laravel 13**, **Livewire 4** (single-file components), **Tailwind CSS v4**, and
**Laravel Sail** (Docker).

## How it works

A typical day in the system:

1. A **receptionist** registers a patient (or finds them by name / medical record number) and
   optionally schedules an appointment with a healthcare provider.
2. When the patient arrives, the receptionist **checks them in** to a queue (General,
   Cardiology, Pediatrics, Emergency, …). The system issues a sequential daily ticket per
   queue — `A001`, `A002`, … — and, on priority-enabled queues, a Normal / High / Urgent level.
3. The **queue board** shows every active queue live: who is being served now and who is
   waiting, ordered by priority first and arrival time second. It refreshes automatically.
4. A **provider** presses **Call Next**, then **Start** when the patient enters the room, and
   **Complete** when done. Each ticket moves through a strict lifecycle with timestamps at
   every step, which feeds the average-wait statistics.
5. Role-specific **dashboards** keep everyone oriented: reception sees today's appointments
   and per-queue waiting counts; providers see their current called/in-service patient with
   one-click actions.

## Modules

| Module | What it does |
|--------|--------------|
| **Patients** | Registry with live search (name / MRN), activate–deactivate instead of delete, unique medical record number enforced. |
| **Providers** | Admin-managed registry of healthcare providers with specialty and unique license number. |
| **Appointments** | Scheduling against active patients/providers only, per-provider conflict detection (±30-minute slots), and a status state machine: scheduled → confirmed → in progress → completed, with cancel / no-show allowed only from early states. |
| **Queues** | Queue configuration, patient check-in, ticket numbering, live board, and per-queue statistics (waiting / called / in service / completed today / average wait). |
| **Dashboards** | Reception overview and provider console, chosen automatically by the logged-in user's role. |

## Business rules

The core invariants are enforced in services and covered by tests:

- **BR-01** — Medical record numbers are unique (ignoring the patient's own record on edit).
- **BR-02** — Inactive patients cannot be checked in.
- **BR-03** — A provider cannot have two live appointments within 30 minutes of each other
  (cancelled and no-show appointments don't block the slot).
- **BR-04** — *Call Next* picks the highest-priority waiting ticket; ties break by arrival time.
- **BR-05** — A patient can hold only one active ticket (waiting / called / in service) at a time.
- **BR-06** — Ticket transitions are strict: waiting → called → in service → completed;
  cancellation only while waiting or called. Illegal moves are rejected.
- **BR-07** — Actions are role-gated: receptionists check in and cancel; providers call,
  start, and complete service; admins can do everything.

## Roles & permissions

Three roles — **Admin**, **Receptionist**, **Provider** — enforced through Laravel Gates and
policies (admin passes everything via `Gate::before`). The UI hides what a role cannot do, and
routes/actions reject it server-side regardless.

| Capability | Admin | Receptionist | Provider |
|------------|:-----:|:------------:|:--------:|
| Manage patients | ✅ | ✅ | view only |
| Manage providers / queues | ✅ | — | — |
| Manage appointments | ✅ | ✅ | view only |
| Check in / cancel ticket | ✅ | ✅ | cancel only |
| Call next / start / complete service | ✅ | — | ✅ |

## Architecture notes

- **Livewire 4 single-file components** — pages under `resources/views/pages/`, reusable
  children under `resources/views/components/`, routed with `Route::livewire()`. Each domain
  owns its own route file in `routes/domains/`, auto-loaded inside the auth group.
- **Domain services** hold the business logic: `AppointmentScheduler` (BR-03),
  `AppointmentTransitioner`, `TicketNumberGenerator`, and `TicketStateMachine` (BR-04/06) —
  components never mutate statuses directly.
- **Design system** — tokens and component specs live in `docs/STYLEGUIDE.md`; the UI is built
  from 13 base Blade components (buttons, cards, stat cards, badges, tables, modals, …) using
  Tailwind theme tokens only, no ad-hoc colors.
- **Tests** — Pest suite (207 tests / 512 assertions) running on in-memory SQLite: the
  permission matrix, every business rule, state-machine paths, seeders, dashboards, login
  throttling and account deactivation. CI runs Pint + Pest on PHP 8.4 and 8.5 for every push.
- **Security** — session-based auth with login rate limiting (5 attempts/min per e-mail + IP),
  deactivated accounts are rejected at login and signed out mid-session, every mutating Livewire
  action re-checks its gate server-side, record identifiers are `#[Locked]` against client
  tampering, and ticket transitions are atomic compare-and-set updates with per-queue row locks
  so concurrent check-ins / "Call Next" clicks cannot duplicate numbers or call the same patient.

## Design decisions

- **Queues are shared, not owned.** A ticket belongs to a queue, not to a provider: any provider
  on duty can call the next patient from any active queue, which is how a walk-in clinic's
  front line operates. Linking users to a specific `HealthcareProvider` (and restricting
  call/start/complete to the ticket's owner) is a deliberate non-goal of this MVP.
- **Soft deactivation everywhere.** Patients, providers, queues and users are never deleted,
  only flagged inactive, so historical tickets and appointments keep their references.
- **Business rules live in services, not components.** Livewire components validate input and
  call `AppointmentScheduler` / `TicketStateMachine`; they never flip a status themselves.

## Getting started

### With Docker (Laravel Sail, MySQL) — recommended

Requirements: Docker + Docker Compose, PHP 8.4+ and Composer on the host (to bootstrap Sail),
Node 22+.

```sh
git clone https://github.com/Marujinho/clinic-queue.git
cd clinic-queue

cp .env.example .env          # already configured for Sail (MySQL, app on :8081, DB on :3307)
composer install
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate:fresh --seed
npm install && npm run build
```

The app runs at <http://localhost:8081> with a realistic demo dataset (patients, providers,
appointments, and queues with tickets in several states).

### Without Docker (SQLite)

Requirements: PHP 8.4+ with the `pdo_sqlite` extension, Composer, Node 22+.

```sh
git clone https://github.com/Marujinho/clinic-queue.git
cd clinic-queue

cp .env.example .env
# In .env: comment out the MySQL block and uncomment  DB_CONNECTION=sqlite
composer setup                 # install, key:generate, migrate (creates database/database.sqlite), npm build
php artisan db:seed
php artisan serve              # http://localhost:8000
```

### Demo logins (password: `password`)

| Email | Role |
|-------|------|
| `admin@clinic.test` | Admin |
| `receptionist@clinic.test` | Receptionist |
| `provider@clinic.test` | Provider |

Demo accounts are only seeded outside `production`.

## Running the tests

The suite uses an in-memory SQLite database and needs no `.env` — a fresh clone can run it
right after `composer install`.

```sh
php artisan test                               # or: ./vendor/bin/sail artisan test
vendor/bin/pint --test                         # code style
```

## Docs

- `docs/STYLEGUIDE.md` — design tokens, component specs, and the component catalog.
- `RUNBOOK.md` — build history and environment/rebuild notes.
