# Clinic Queue — Build Runbook

Turnkey guide to resume building the Clinic Queue Management System. It captures the current
state, how to rebuild the environment on a fresh machine, the foundation contracts every domain
relies on, and the exact remaining work (four domain agents + integration).

Plan file (approved): the architecture and phases were agreed up front — Foundation builds the whole
data layer + shell; one agent per domain builds its Livewire UI + business logic + policies + tests in
its own git worktree; then an Integration phase merges, builds dashboards, seeds and runs the suite.

---

## 0. Status — COMPLETE (2026-08-27)

**The MVP is finished.** All four domains were completed and merged into `build/mvp`, the role
dashboards were built, the full suite is green (190 tests / 459 assertions), pint is clean, the
browser smoke (§7) passed, and PR #1 (`build/mvp` → `master`) is open at
https://github.com/Marujinho/clinic-queue/pull/1. Worktrees have been removed. The sections below
are kept as a historical record of the build plan and for environment-rebuild reference (§2, §3, §8).

### Historical status at the time §5 agents were relaunched — ~40% done, ~60% remaining

**DONE and committed on branch `build/mvp`:**
- Repo wired to `github.com/Marujinho/clinic-queue`; `master` (empty initial) → `build/mvp` integration branch.
- **Data layer (foundation):** enums `Role`, `AppointmentStatus`, `TicketStatus`, `TicketPriority`;
  8 migrations (users+role/active, clinics, departments, rooms, healthcare_providers, patients,
  queues, appointments, queue_tickets); models with relationships/casts; factories for all. Verified
  by `migrate:fresh` + tinker.
- **Design system:** STYLEGUIDE color tokens in `resources/css/app.css` (Tailwind v4 `@theme`),
  Instrument Sans loader removed (Switzer stack w/ system fallback); 11 base Blade components under
  `resources/views/components/` (`button, card, stat-card, status-badge, nav-item, data-table,
  avatar, icon-chip, filter-pill, modal, icon`); `components/layouts/app.blade.php` (dashboard shell)
  and `components/layouts/guest.blade.php`. Catalog (§11) has header-only per-domain sub-sections.
- **Auth & authz:** Livewire login (`pages::auth.login`, guest layout), logout, role-based **Gates**
  for the spec §6 permission matrix in `AppServiceProvider` (admin short-circuit via `Gate::before`).
- **Routing scaffold:** `routes/web.php` auto-requires `routes/domains/*.php` inside the auth group.
- **Seeders:** `FoundationSeeder` (role users + clinic/departments/rooms); `DatabaseSeeder` calls
  domain seeders only if present (no edit needed when a domain lands).
- **Tooling & tests:** PHPUnit → **Pest**; test DB is **in-memory SQLite** (`phpunit.xml`); Vite
  disabled in tests; 16 foundation tests green (auth flow + full permission matrix).
- Verified in-browser: login + dashboard shell render correctly (styleguide applied).

**Commits on `build/mvp`:** scaffold → `feat(foundation): …` → `test: in-memory SQLite`.
Current `build/mvp` HEAD had short hash `f511d7d` at the time worktrees were cut.

**REMAINING (~35–40%) — feature work. Phase 2 agents already ran once and their partial output is
preserved as WIP commits on the domain branches (all pushed):**
| Branch | State | What's left |
|--------|-------|-------------|
| `build/mvp-patient` | **DONE — 32 tests green** (index+form, deactivate, policy, seeder, routes, tests) | review only; ready to merge |
| `build/mvp-provider` | **~90% — 26 passed / 2 failed** (index+form, policy, seeder, routes, tests) | fix 2 failing tests, then merge |
| `build/mvp-appointment` | **~half** — index+form, policy, services (incl. BR-03 scaffolding), routes present | add `AppointmentSeeder`; write tests (BR-03, transitions, active guards); verify green |
| `build/mvp-queue` | **~30%** — services + components + pages only | add `QueuePolicy`+`QueueTicketPolicy`, `routes/domains/queue.php`, `QueueSeeder`, all tests; verify check-in/board/state-machine/stats per §5d |
| `build/mvp` (integration) | pending | §6: merge domains, dashboards, final seed, full suite green, PR |

**Resume plan:** finish `provider` (2 tests), then `appointment`, then `queue` — either by relaunching a
focused agent per branch (prompts in §5) pointed at the existing WIP, or by hand. Each domain branch
already has a worktree recreated via §2b. Then do §6 integration. Re-running an agent from scratch is
also fine but would redo the WIP; prefer finishing what's there (esp. patient/provider).

---

## 1. Repository & branch layout

- Remote: `origin = https://github.com/Marujinho/clinic-queue` (auth: `gh` as `Marujinho`).
- `master` — tracks `origin/master` (essentially empty; final PR target).
- `build/mvp` — integration branch, all foundation work. **Push this before switching machines:**
  `git push -u origin build/mvp`.
- `build/mvp-{patient,provider,appointment,queue}` — empty domain branches off `build/mvp`. These are
  local scaffolding for the worktrees and need **not** be pushed; recreate them on the new machine.
- Final delivery: open a PR `build/mvp → master`.

---

## 2. Environment setup on a fresh machine

Prereqs: Docker + Docker Compose, PHP 8.3+ & Composer (host, to bootstrap Sail), Node 20+, `gh`.

```sh
# 1. Get the code
git clone https://github.com/Marujinho/clinic-queue.git
cd clinic-queue
git checkout build/mvp

# 2. Env file (NOT in git). Copy the example and set an app key.
cp .env.example .env
# Ensure DB block matches compose.yaml (MySQL/Sail):
#   DB_CONNECTION=mysql  DB_HOST=mysql  DB_PORT=3306
#   DB_DATABASE=laravel  DB_USERNAME=sail  DB_PASSWORD=password
#   APP_PORT=8081   (host port for the app)

# 3. Install host deps enough to run Sail, then bring up containers
composer install
./vendor/bin/sail up -d           # starts laravel.test (PHP 8.5) + mysql 8.4
./vendor/bin/sail artisan key:generate
./vendor/bin/sail composer install
npm install && npm run build      # builds Tailwind/Vite assets (needed for browser, not for tests)

# 4. Create the DB schema + seed foundation (dev DB is MySQL)
./vendor/bin/sail artisan migrate:fresh --seed
```

Seeded logins (password `password`): `admin@clinic.test`, `receptionist@clinic.test`,
`provider@clinic.test`. App runs at `http://localhost:8081`.

### 2b. Recreate the domain worktrees

Worktrees live under `.worktrees/` (git-ignored) so they sit inside Sail's mounted volume
(`/var/www/html/.worktrees/<domain>` in the container). Each needs its **own real `vendor`** — a
symlinked vendor breaks PSR-4 (its autoloader resolves paths to the main repo, so tests run the wrong
files). `.env` is symlinked so tests get `APP_KEY`.

```sh
ROOT="$(pwd)"
for d in patient provider appointment queue; do
  git worktree add ".worktrees/$d" -b "build/mvp-$d" build/mvp
  ln -s ../../.env ".worktrees/$d/.env"
  # real vendor per worktree (warm cache ~10s each):
  ./vendor/bin/sail exec -T laravel.test bash -lc "cd /var/www/html/.worktrees/$d && composer install --no-interaction --no-progress"
done
git worktree list
```

Sanity check one worktree runs the suite:
```sh
./vendor/bin/sail exec -T laravel.test bash -lc "cd /var/www/html/.worktrees/patient && php artisan test tests/Feature/Foundation --compact"
# expect: 16 passed
```

---

## 3. How to run things (important gotchas)

- **All PHP/artisan/composer/pint/test commands run inside the container.** Portable form:
  `./vendor/bin/sail exec -T laravel.test bash -lc "cd /var/www/html/.worktrees/<domain> && <cmd>"`.
  (In this session the container was named `clinic-queue-laravel.test-1`; `sail exec laravel.test`
  is machine-independent.) Host PHP lacks the sqlite/other extensions — do not run bare `php` on host.
- **Tests use in-memory SQLite** (`phpunit.xml`) → fast and fully isolated, so parallel worktrees
  never collide. Run a domain's tests: `… && php artisan test`.
- **Do NOT** run `migrate:fresh`/seeders against the live MySQL from inside a worktree, and don't start
  a second dev server — the dev MySQL DB is shared by all worktrees. Validate via `php artisan test`.
- **Livewire 4 single-file components** live at `resources/views/pages/<name>/⚡<file>.blade.php`
  (full pages, ref `pages::<dir>.<file>`) and `resources/views/components/<dir>/⚡<file>.blade.php`
  (children, tag `<livewire:<dir>.<file> />`). The ⚡ emoji is part of the filename. Generate with
  `php artisan make:livewire pages::patient.index` etc. Full-page routes use `Route::livewire()`.
- **Models use Laravel 13 attributes** `#[Fillable([...])]` + `casts()` method (see `app/Models/User.php`).
- Full-page components auto-wrap in `components.layouts.app`; be explicit with `#[Layout('components.layouts.app')]`.

---

## 4. Foundation API the domains build on (do not recreate)

**Models (`App\Models`)**
- `Patient` — first_name,last_name,date_of_birth[date],email,phone,medical_record_number,active[bool];
  `->full_name` accessor; `scopeActive`; `appointments()`, `queueTickets()`.
- `HealthcareProvider` — name,specialty,license_number,active[bool]; `scopeActive`; `appointments()`.
- `Appointment` — patient_id,healthcare_provider_id,scheduled_at[datetime],status[AppointmentStatus],
  reason,notes; `patient()`, `provider()` (FK healthcare_provider_id), `queueTicket()`.
- `Queue` — name,description,priority_enabled[bool],active[bool],department_id; `queueTickets()`,
  `department()`, `waitingCount()`; `scopeActive`.
- `QueueTicket` — queue_id,patient_id,appointment_id,ticket_number,priority[TicketPriority],
  status[TicketStatus],checked_in_at,called_at,service_started_at,completed_at; `queue()`,`patient()`,`appointment()`.
- `Clinic`,`Department`,`Room`; `User` — role[Role],active; `isAdmin()/isReceptionist()/isProvider()/hasRole()`.

**Enums (`App\Enums`)**
- `Role{Admin,Receptionist,Provider}` + `label()`.
- `AppointmentStatus{Scheduled,Confirmed,InProgress,Completed,Cancelled,NoShow}` + `label()` + `badge()`.
- `TicketStatus{Waiting,Called,InService,Completed,Cancelled}` + `label()` + `badge()` + `isActive()` (Waiting/Called/InService).
- `TicketPriority` (int) `{Normal=0,High=1,Urgent=2}` + `label()` + `badge()`. `badge()` returns success|warning|danger|info|neutral.

**Factories** — all models; states: `Patient::factory()->inactive()`, `HealthcareProvider::factory()->inactive()`,
`Appointment::factory()->confirmed()/inProgress()/completed()/cancelled()/noShow()`,
`Queue::factory()->inactive()`, `QueueTicket::factory()->highPriority()/urgent()/called()/inService()/completed()/cancelled()`,
`User::factory()->admin()/receptionist()/provider()`.

**Gates** (admin passes all via `Gate::before`) — guard with `$this->authorize('<gate>')`:
`manage-patients`(admin+recep), `view-patients`(all), `manage-providers`(admin), `manage-queues`(admin),
`manage-users`(admin), `check-in`(admin+recep), `call-patient`(admin+prov), `start-service`(admin+prov),
`complete-service`(admin+prov), `cancel-ticket`(admin+recep+prov), `manage-appointments`(admin+recep),
`view-appointments`(all). Policies are auto-discovered (`App\Policies\<Model>Policy`).

**Base UI components** (reuse, do not restyle): `<x-button variant=primary|secondary|ghost size=md|sm>`,
`<x-card title?>`(+`<x-slot:actions>`), `<x-stat-card label value delta? deltaType? :hero>`,
`<x-status-badge variant=success|warning|danger|info|neutral label>`, `<x-avatar name size>`,
`<x-data-table>`(+`<x-slot:head>`; rows `<tr class="border-b border-border hover:bg-hover-surface">`,
cells `<td class="py-3 text-ink">`), `<x-modal title?>`(+`<x-slot:footer>`; parent shows via `@if`),
`<x-icon name>` (dashboard,patients,provider,calendar,queue,board,search,bell,plus,chevron-down,check,
x-mark,logout,clock,filter), `<x-filter-pill label icon?>`, `<x-icon-chip>`. Tokens are Tailwind classes
(`bg-primary,text-ink,text-muted,bg-surface,border-border,bg-hover-surface,text-success,bg-success-tint,
text-danger,bg-danger-tint,…`) — never ad-hoc hex or `[#…]`. See `docs/STYLEGUIDE.md`.

**Test helper:** `actingAsRole(App\Enums\Role::X)` creates + logs in a user of that role (`tests/Pest.php`).

---

## 5. Phase 2 — four domain agents (one per worktree, run in parallel)

Launch each as a subagent whose working files are its worktree. **Strict file ownership — a domain
agent may create/edit ONLY:** `resources/views/pages/<domain>/**`, `resources/views/components/<domain>/**`,
`app/Policies/<Model>Policy.php`, `app/Services/<Domain>/**`, `routes/domains/<domain>.php`,
`database/seeders/<Domain>Seeder.php`, `tests/{Feature,Unit}/<Domain>/**`, and only its own `### <Domain>`
sub-table in `docs/STYLEGUIDE.md` §11. **Forbidden:** models, enums, migrations, factories,
`resources/css/**`, layouts, base components, `routes/web.php`, `app/Providers/**`, `config/**`,
`tests/Pest.php`, `phpunit.xml`, other domains' files. Each agent finishes by: `pint app resources
routes tests database` → `php artisan test` (all green) → register components in its catalog sub-table →
commit in its worktree (`git add -A && git commit`, message trailer `Co-Authored-By: Claude Opus 4.8
(1M context) <noreply@anthropic.com>`).

Command prefix each agent uses (replace `<domain>`):
`./vendor/bin/sail exec -T laravel.test bash -lc "cd /var/www/html/.worktrees/<domain> && <cmd>"`.

### 5a. Patient (`build/mvp-patient`)
- `pages::patient.index`: list/search — search (`wire:model.live.debounce.300ms`) over name + MRN,
  status filter (All/Active/Inactive), `<x-data-table>` (Name w/ avatar, MRN, Phone, DOB, Status badge,
  Actions Edit + Deactivate/Activate), paginate 10, "New Patient" button. Manage actions only for
  `manage-patients`; providers see read-only.
- `patient.form` child (modal): create/edit; fields first_name,last_name,date_of_birth,email,phone,
  medical_record_number,active. Validation: names min2/max100; **DOB before_or_equal today (not future)**;
  email nullable email; phone required; **MRN unique ignoring self (BR-01)**. `authorize('manage-patients')`;
  dispatch `patient-saved`.
- Deactivate/activate toggle. `PatientPolicy` (view→view-patients, manage→manage-patients).
- `routes/domains/patient.php`: `Route::livewire('/patients','pages::patient.index')->name('patients.index')->middleware('can:view-patients');`
- `PatientSeeder`: ~18 patients, 3–4 inactive.
- Tests: create ok; **BR-01** dup MRN rejected; future DOB rejected; name length; edit keeps own MRN;
  search by name & MRN; deactivate/reactivate; authz (recep/admin save, provider forbidden, provider can view).

### 5b. Provider (`build/mvp-provider`)
- `pages::provider.index`: list/search (name/specialty), status filter, `<x-data-table>` (Name, Specialty,
  License, Status, Actions Edit + Activate/Deactivate), "New Provider".
- `provider.form` child (modal): name(min2/max100),specialty(req),license_number(**unique ignore self**),active.
  `authorize('manage-providers')`; dispatch `provider-saved`.
- `HealthcareProviderPolicy` → manage-providers for all abilities.
- `routes/domains/provider.php`: `Route::livewire('/providers','pages::provider.index')->name('providers.index')->middleware('can:manage-providers');`
- `ProviderSeeder`: ~8 providers across specialties, 1–2 inactive.
- Tests: create ok; license uniqueness (ignore self); name validation; activate/deactivate; search;
  authz — **admin** GET /providers 200 & can save; **receptionist/provider** GET /providers **403** & save forbidden.

### 5c. Appointment (`build/mvp-appointment`)
- `pages::appointment.index`: filters (status, provider, optional date); `<x-data-table>` (Patient w/avatar,
  Provider, Scheduled at, Status badge from `->badge()/->label()`, Reason, Actions). Row actions contextual
  to status: Confirm, Start, Complete, Cancel, No-show. "New Appointment".
- `appointment.form` child: patient_id (ACTIVE patients only), healthcare_provider_id (ACTIVE providers only),
  scheduled_at (datetime-local, after_or_equal now), reason, notes; status defaults Scheduled. Reject inactive
  patient/provider. `authorize('manage-appointments')`; dispatch `appointment-saved`.
- **BR-03** in `app/Services/Appointment/AppointmentScheduler.php`:
  `hasConflict(providerId, scheduledAt, ?ignoreId): bool` with `SLOT_MINUTES=30` — conflict if another
  non-cancelled, non-no_show appt for that provider is within ±30 min. Fail validation on save.
- Transition rules (service or component): scheduled→confirmed→in_progress→completed;
  scheduled/confirmed→cancelled; scheduled/confirmed→no_show; reject anything else.
- `AppointmentPolicy` (view→view-appointments, manage→manage-appointments).
- `routes/domains/appointment.php`: `Route::livewire('/appointments','pages::appointment.index')->name('appointments.index')->middleware('can:view-appointments');`
- `AppointmentSeeder`: ~20 appts across statuses/times, no BR-03 conflicts.
- Tests: create ok; reject inactive patient; reject inactive provider; **BR-03** overlap rejected /
  non-overlap ok / different provider ok / cancelled doesn't block (unit + feature); each valid transition +
  ≥1 invalid transition; authz (recep/admin manage, provider view-only 200, manage forbidden).

### 5d. Queue core (`build/mvp-queue`) — largest
- `pages::queue.index`: queue config list (Name, Description, Priority-enabled badge, Status, Waiting count,
  Actions Edit + Activate/Deactivate), "New Queue"; guard `manage-queues`. `queue.form` child:
  name,description,priority_enabled,active,department_id(select).
- `pages::queue.check-in` (CheckInPatient): select ACTIVE patient (searchable), ACTIVE queue, optional
  appointment (that patient's scheduled/confirmed), priority (only if queue.priority_enabled else Normal).
  `authorize('check-in')`. **BR-02** inactive patient rejected. **BR-05** patient with any active ticket
  (waiting/called/in_service) rejected. Create ticket status=Waiting, checked_in_at=now(), ticket_number via
  `app/Services/Queue/TicketNumberGenerator` — sequential **per queue per day**, `A001`,`A002`… (reset on
  new day / other queue).
- `pages::queue.board` (QueueBoard) with `wire:poll.5s`: per active queue → NOW SERVING (called/in_service
  ticket) + WAITING list ordered **priority DESC then checked_in_at ASC (BR-04)**; buttons Call Next
  (`call-patient`), Start (`start-service`, **BR-07** provider/admin only), Complete (`complete-service`),
  Cancel (`cancel-ticket`, only while waiting/called).
- `app/Services/Queue/TicketStateMachine`: `call/start/complete/cancel` validate current status, set
  timestamps, throw on illegal moves (**BR-06**); `callNext(Queue)` picks next per BR-04.
- `queue.stats` child (QueueStats, accepts optional `?Queue`): Waiting/Called/InService/Completed(today) +
  average wait (avg called_at−checked_in_at). Reused by Phase-3 dashboards.
- Policies `QueuePolicy`(manage-queues), `QueueTicketPolicy`(create→check-in, call→call-patient,
  start→start-service, complete→complete-service, cancel→cancel-ticket).
- `routes/domains/queue.php`:
  `/queues`→`pages::queue.index` name `queues.index` `can:manage-queues`;
  `/check-in`→`pages::queue.check-in` name `queue.check-in` `can:check-in`;
  `/queue-board`→`pages::queue.board` name `queue.board` (any authed user).
- `QueueSeeder`: 3–4 queues (General [priority], Cardiology, Pediatrics, Emergency [priority]) + check in
  several active patients with varied priorities/states (several Waiting, one Called, one InService).
- Tests: ticket-number sequencing per queue/day (incl. yesterday doesn't bump today); check-in creates
  Waiting; **BR-02**; **BR-05**; **BR-04** call-next ordering (Urgent before Normal; earliest within a
  priority); state machine happy paths + **BR-06** illegal transitions throw; **BR-07**/authz (recep can't
  start, provider can; call denied for recep; manage-queues admin-only 200/403); QueueStats counts.

> The four agent prompts issued in-session were more detailed versions of the above; this section is a
> faithful, self-contained restatement — sufficient to relaunch each agent.

---

## 6. Phase 3 — integration & ship (on `build/mvp`)

1. **Merge** domain branches into `build/mvp` in order patient → provider → appointment → queue.
   Conflicts should be near-zero (only the STYLEGUIDE §11 per-domain sub-tables touch a shared file;
   they auto-merge). After each merge run `sail artisan test`.
2. **Dashboards** (cross-domain, build here):
   - `pages::dashboard.reception`: today's appointments, waiting patients, in-service, completed,
     cancelled + per-queue waiting table (reuse `<x-stat-card>` hero + `queue.stats`).
   - `pages::dashboard.provider`: current called/in-service patient + Start/Complete/Cancel (delegating
     to `TicketStateMachine`).
   - Route `/` → role-appropriate dashboard; the placeholder `pages::dashboard.index` may be replaced.
3. **Seeding:** confirm `DatabaseSeeder` order (Foundation → Patient → Provider → Appointment → Queue)
   yields a realistic demo dataset; `sail artisan migrate:fresh --seed`.
4. **Quality gate:** `sail artisan test` (full Pest green), `npm run build`, `./vendor/bin/pint --test`.
5. **Docs:** update `README.md` (setup + seeded logins) and STYLEGUIDE §9 status.
6. **Ship:** `git push origin build/mvp` and open PR to `master` via `gh pr create`.
7. **Cleanup:** `git worktree remove .worktrees/<domain>` for each once merged.

---

## 7. Verification checklist (end-to-end)

- `sail up -d` → `sail artisan migrate:fresh --seed` loads cleanly.
- `sail artisan test` — full suite green (BR-01…BR-08, transitions, call-next ordering, per-role authz).
- `npm run build` succeeds; `pint --test` clean.
- Browser smoke at `http://localhost:8081`: login as receptionist → check a patient into a queue →
  login as provider → Queue Board → Call Next (verify priority + arrival order) → Start → Complete;
  dashboards' counts update and the board live-refreshes (`wire:poll`); role gates block disallowed actions.

---

## 8. Gotchas learned (save yourself the debugging)

- **Worktree `vendor` must be real, not a symlink** — a symlinked vendor's autoloader resolves PSR-4 to
  the main repo, so tests load the wrong classes (`Call to undefined method …::get()` / wrong namespace).
  Run `composer install` inside each worktree.
- **Test DB is SQLite in-memory** (`phpunit.xml`): `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`. Keeps
  parallel worktrees isolated; no MySQL needed for tests.
- **`@vite` breaks full-page render tests** without a build manifest → `tests/Pest.php` calls
  `$this->withoutVite()` in a Feature `beforeEach`.
- **Livewire component filenames contain `⚡`** and pages are referenced `pages::dir.file`; routes use
  `Route::livewire()` (a real Livewire 4 macro).
- **`.env` and `.worktrees/` are git-ignored**; symlink `.env` into each worktree so tests get `APP_KEY`.
- Container name this session: `clinic-queue-laravel.test-1`; prefer the portable `sail exec laravel.test`.
- **`tests/Unit` must exist** or `php artisan test` aborts with "Test directory not found" (git drops the
  empty dir on checkout). `build/mvp` now ships `tests/Unit/.gitkeep`; the domain WIP branches predate it,
  so on those branches either run `php artisan test tests/Feature` or `mkdir -p tests/Unit` first. The
  fix merges in with `build/mvp` during integration.
- **Domain branches are WIP and were force-created before finishing** — check `php artisan test tests/Feature`
  per branch to see current state (patient 32 green; provider 26/2; appointment & queue tests not written yet).
