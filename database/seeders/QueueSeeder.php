<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Department;
use App\Models\Patient;
use App\Models\Queue;
use App\Services\Queue\TicketNumberGenerator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * Seeds the clinic's queues plus a realistic set of checked-in tickets:
 * several patients Waiting (varied priorities), one Called and one InService.
 *
 * Works standalone (it creates the active patients it needs when there are not
 * enough) and after the other domain seeders (it reuses existing active
 * patients, respecting BR-05 — one active ticket per patient).
 */
class QueueSeeder extends Seeder
{
    public function run(): void
    {
        $queues = $this->seedQueues();

        $this->seedTickets($queues);
    }

    /**
     * @return Collection<string, Queue>
     */
    private function seedQueues(): Collection
    {
        $definitions = [
            ['name' => 'General Consultation', 'description' => 'Walk-ins and general practice visits.', 'priority_enabled' => true, 'department' => 'General Practice'],
            ['name' => 'Cardiology', 'description' => 'Cardiology consultations and follow-ups.', 'priority_enabled' => false, 'department' => 'Cardiology'],
            ['name' => 'Pediatrics', 'description' => 'Children and adolescent care.', 'priority_enabled' => false, 'department' => 'Pediatrics'],
            ['name' => 'Emergency', 'description' => 'Urgent and emergency triage.', 'priority_enabled' => true, 'department' => 'Emergency'],
        ];

        return collect($definitions)->mapWithKeys(function (array $definition): array {
            $department = Department::query()->where('name', $definition['department'])->first();

            $queue = Queue::query()->updateOrCreate(
                ['name' => $definition['name']],
                [
                    'description' => $definition['description'],
                    'priority_enabled' => $definition['priority_enabled'],
                    'active' => true,
                    'department_id' => $department?->id,
                ],
            );

            return [$queue->name => $queue];
        });
    }

    /**
     * @param  Collection<string, Queue>  $queues
     */
    private function seedTickets(Collection $queues): void
    {
        $patients = $this->availablePatients(9);
        $generator = app(TicketNumberGenerator::class);

        // [queue, priority, status, minutes since check-in]
        $plan = [
            ['General Consultation', TicketPriority::Normal, TicketStatus::Waiting, 40],
            ['General Consultation', TicketPriority::High, TicketStatus::Waiting, 25],
            ['General Consultation', TicketPriority::Normal, TicketStatus::Waiting, 10],
            ['General Consultation', TicketPriority::Normal, TicketStatus::Called, 55],
            ['Cardiology', TicketPriority::Normal, TicketStatus::Waiting, 30],
            ['Cardiology', TicketPriority::Normal, TicketStatus::InService, 65],
            ['Pediatrics', TicketPriority::Normal, TicketStatus::Waiting, 15],
            ['Emergency', TicketPriority::Urgent, TicketStatus::Waiting, 5],
            ['Emergency', TicketPriority::High, TicketStatus::Waiting, 20],
        ];

        foreach ($plan as $index => [$queueName, $priority, $status, $minutesAgo]) {
            $queue = $queues[$queueName];
            $patient = $patients[$index];
            $checkedInAt = now()->subMinutes($minutesAgo);

            $patient->queueTickets()->create([
                'queue_id' => $queue->id,
                'ticket_number' => $generator->next($queue),
                'priority' => $priority,
                'status' => $status,
                'checked_in_at' => $checkedInAt,
                'called_at' => $status === TicketStatus::Waiting ? null : $checkedInAt->copy()->addMinutes(12),
                'service_started_at' => $status === TicketStatus::InService ? $checkedInAt->copy()->addMinutes(18) : null,
                'completed_at' => null,
            ]);
        }
    }

    /**
     * Active patients without an active ticket (BR-05), topped up via the
     * factory when the database does not hold enough yet.
     *
     * @return Collection<int, Patient>
     */
    private function availablePatients(int $count): Collection
    {
        $available = Patient::query()
            ->active()
            ->whereDoesntHave('queueTickets', function ($query): void {
                $query->whereIn('status', [TicketStatus::Waiting, TicketStatus::Called, TicketStatus::InService]);
            })
            ->limit($count)
            ->get();

        $missing = $count - $available->count();

        if ($missing > 0) {
            $available = $available->concat(Patient::factory()->count($missing)->create());
        }

        return $available->values();
    }
}
