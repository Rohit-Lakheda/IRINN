<?php

namespace App\Console\Commands;

use App\Models\Admin;
use App\Models\Message;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\TicketEscalationSetting;
use App\Models\TicketMessage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class EscalateTickets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tickets:escalate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically escalate unresolved tickets based on Super Admin escalation settings';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $now = now('Asia/Kolkata');
            $escalatedCount = 0;

            $setting = TicketEscalationSetting::current();
            if (! $setting->is_enabled) {
                $this->info('Ticket escalation is disabled (Super Admin settings).');

                return Command::SUCCESS;
            }

            $ixHeadAfterHours = max(0, (int) ($setting->ix_head_after_hours ?? 6));
            $ceoAfterHours = max($ixHeadAfterHours, (int) ($setting->ceo_after_hours ?? 24));
            $level1RoleSlug = (string) ($setting->level_1_role_slug ?? 'ix_head');
            $level2RoleSlug = (string) ($setting->level_2_role_slug ?? 'ceo');

            // Get unresolved tickets (not resolved or closed)
            // Only escalate tickets that are open, assigned, or in_progress
            $unresolvedTickets = Ticket::whereIn('status', ['open', 'assigned', 'in_progress'])
                ->where('escalation_level', '!=', 'ceo') // Don't escalate if already at CEO
                ->get();

            foreach ($unresolvedTickets as $ticket) {
                // Check escalation based on assigned_at (when assigned to admin role)
                // If not assigned yet, use created_at as fallback
                $startTime = $ticket->assigned_at ?? $ticket->created_at;
                $hoursSinceAssignment = $startTime->diffInHours($now);

                $shouldEscalateToIxHead = false;
                $shouldEscalateToCeo = false;

                // Check if should escalate to IX Head (6 hours after assignment, not already escalated)
                if ($hoursSinceAssignment >= $ixHeadAfterHours && $ticket->escalation_level === 'none') {
                    $shouldEscalateToIxHead = true;
                }

                // Check if should escalate to CEO (24 hours after assignment)
                if ($hoursSinceAssignment >= $ceoAfterHours) {
                    if ($ticket->escalation_level === 'ix_head') {
                        $shouldEscalateToCeo = true;
                    } elseif ($ticket->escalation_level === 'none') {
                        // If 24+ hours and not escalated yet, escalate directly to CEO
                        $shouldEscalateToCeo = true;
                    }
                }

                if ($shouldEscalateToIxHead) {
                    $this->escalateToRole($ticket, $level1RoleSlug, $ixHeadAfterHours, 'ix_head');
                    $escalatedCount++;
                } elseif ($shouldEscalateToCeo) {
                    $this->escalateToRole($ticket, $level2RoleSlug, $ceoAfterHours, 'ceo');
                    $escalatedCount++;
                }
            }

            $this->info("Tickets escalated: {$escalatedCount}");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            Log::error('Error escalating tickets: '.$e->getMessage());
            $this->error('Error escalating tickets: '.$e->getMessage());

            return Command::FAILURE;
        }
    }

    /**
     * Escalate ticket to IX Head.
     */
    private function escalateToRole(Ticket $ticket, string $roleSlug, int $afterHours, string $level): void
    {
        try {
            $role = Role::where('slug', $roleSlug)->first();
            if (! $role) {
                Log::warning("Role '{$roleSlug}' not found. Cannot escalate ticket {$ticket->ticket_id}");

                return;
            }

            $targetAdmin = Admin::whereHas('roles', function ($query) use ($role) {
                $query->where('roles.id', $role->id);
            })
                ->where('is_active', true)
                ->first();

            if (! $targetAdmin) {
                Log::warning("No active admin found for role '{$roleSlug}'. Cannot escalate ticket {$ticket->ticket_id}");

                return;
            }

            // Update ticket - set priority to high and mark as escalated
            // Keep original assignment so original admin can still see it as escalated
            $ticket->update([
                'escalation_level' => $level,
                'escalated_to' => $targetAdmin->id,
                'escalated_at' => now('Asia/Kolkata'),
                'escalation_notes' => "Automatically escalated after {$afterHours} hours without resolution (from assignment).",
                'priority' => $level === 'ceo' ? 'urgent' : ($ticket->priority === 'urgent' ? 'urgent' : 'high'),
                // Keep assigned_to and assigned_role so original admin can still see it
            ]);

            // Send message to user
            Message::create([
                'user_id' => $ticket->user_id,
                'subject' => 'Grievance Escalated - Ticket '.$ticket->ticket_id,
                'message' => "Your grievance ticket {$ticket->ticket_id} has been escalated to {$role->name} for priority resolution as it was not resolved within {$afterHours} hours.",
                'is_read' => false,
                'sent_by' => 'system',
            ]);

            // Create ticket message for escalation (use admin type with null ID for system messages)
            TicketMessage::create([
                'ticket_id' => $ticket->id,
                'sender_type' => 'admin',
                'sender_id' => null, // System message
                'message' => "🔴 ESCALATED: Ticket automatically escalated to {$role->name} ({$targetAdmin->name}) after {$afterHours} hours without resolution.",
                'is_internal' => false,
            ]);

            Log::info("Ticket {$ticket->ticket_id} escalated to {$roleSlug} ({$targetAdmin->name})");

        } catch (\Exception $e) {
            Log::error("Error escalating ticket {$ticket->id} to {$roleSlug}: ".$e->getMessage());
        }
    }
}
