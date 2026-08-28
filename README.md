# Clinic Queue

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
- **Tests** — Pest suite (190 tests / 459 assertions) running on in-memory SQLite: the
  permission matrix, every business rule, state-machine paths, seeders, and dashboards.

## Getting started

Requirements: Docker + Docker Compose, PHP 8.3+ and Composer on the host (only to bootstrap
Sail), Node 20+.

```sh
git clone https://github.com/Marujinho/clinic-queue.git
cd clinic-queue

cp .env.example .env
# Ensure the DB block matches compose.yaml:
#   DB_CONNECTION=mysql  DB_HOST=mysql  DB_PORT=3306
#   DB_DATABASE=laravel  DB_USERNAME=sail  DB_PASSWORD=password
#   APP_PORT=8081

composer install
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail composer install
npm install && npm run build

./vendor/bin/sail artisan migrate:fresh --seed
```

The app runs at <http://localhost:8081> with a realistic demo dataset (patients, providers,
appointments, and queues with tickets in several states).

### Demo logins (password: `password`)

| Email | Role |
|-------|------|
| `admin@clinic.test` | Admin |
| `receptionist@clinic.test` | Receptionist |
| `provider@clinic.test` | Provider |

## Running the tests

```sh
./vendor/bin/sail artisan test          # full Pest suite (in-memory SQLite)
./vendor/bin/sail exec laravel.test ./vendor/bin/pint --test   # code style
```

## Docs

- `docs/STYLEGUIDE.md` — design tokens, component specs, and the component catalog.
- `RUNBOOK.md` — build history and environment/rebuild notes.
