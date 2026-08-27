# Clinic Queue

A clinic queue management system: patients, healthcare providers, appointments, and live
walk-in queues with priority ordering, built with **Laravel 13**, **Livewire 4**
(single-file components), **Tailwind CSS v4**, and **Laravel Sail** (Docker).

## Features

- **Patients** — searchable registry (name / medical record number), activate/deactivate,
  unique MRN (BR-01).
- **Providers** — admin-managed registry with specialty and unique license number.
- **Appointments** — scheduling with active-only patient/provider guards, ±30-minute
  per-provider conflict detection (BR-03), and a strict status state machine
  (scheduled → confirmed → in progress → completed; cancel / no-show from early states).
- **Queues** — configurable queues (priority-enabled per queue), patient check-in with
  per-queue daily ticket numbering (`A001`…), one active ticket per patient (BR-05),
  live queue board (`wire:poll`) calling patients by priority then arrival (BR-04),
  and a ticket state machine (waiting → called → in service → completed, BR-06).
- **Role dashboards** — reception overview (today's appointments + queue stats + per-queue
  table) and provider console (current patient with Start / Complete / Cancel).
- **Authorization** — Admin / Receptionist / Provider roles enforced via Gates and
  policies across every screen and action (spec §6 permission matrix).

## Requirements

- Docker + Docker Compose
- PHP 8.3+ and Composer on the host (only to bootstrap Sail)
- Node 20+

## Setup

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

The app runs at <http://localhost:8081>.

### Seeded logins (password: `password`)

| Email | Role |
|-------|------|
| `admin@clinic.test` | Admin |
| `receptionist@clinic.test` | Receptionist |
| `provider@clinic.test` | Provider |

## Tests

The suite uses in-memory SQLite (no MySQL needed) via Pest:

```sh
./vendor/bin/sail artisan test
```

Code style is enforced with Pint:

```sh
./vendor/bin/sail exec laravel.test ./vendor/bin/pint --test
```

## Docs

- `docs/STYLEGUIDE.md` — design tokens, component specs, and the component catalog.
- `RUNBOOK.md` — build history and environment/rebuild notes.
