<?php

namespace App\Console\Commands;

use App\Models\Application;
use App\Models\Message;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendPaymentReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payments:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send payment due reminders to users (15 days after activation, 8 days before due, 1 day before due)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $now = now('Asia/Kolkata');
            
            // Get all LIVE applications with service activation date
            $applications = Application::where('application_type', 'IX')
                ->where('is_active', true)
                ->whereNotNull('service_activation_date')
                ->whereNotNull('membership_id')
                ->with('user')
                ->get();

            $remindersSent = 0;

            foreach ($applications as $application) {
                $activationDate = \Carbon\Carbon::parse($application->service_activation_date);
                $dueDate = $activationDate->copy()->addMonth();
                $daysSinceActivation = $activationDate->diffInDays($now);
                $daysUntilDue = $now->diffInDays($dueDate, false); // Negative if past due

                // 1st reminder: 15 days after service activation
                if ($daysSinceActivation === 15) {
                    $this->sendReminder($application, 1, $dueDate);
                    $remindersSent++;
                }
                
                // 2nd reminder: 8 days before due date
                if ($daysUntilDue === 8) {
                    $this->sendReminder($application, 2, $dueDate);
                    $remindersSent++;
                }
                
                // 3rd reminder: 1 day before due date
                if ($daysUntilDue === 1) {
                    $this->sendReminder($application, 3, $dueDate);
                    $remindersSent++;
                }
            }

            $this->info("Payment reminders sent: {$remindersSent}");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            Log::error('Error sending payment reminders: '.$e->getMessage());
            $this->error('Error sending payment reminders: '.$e->getMessage());

            return Command::FAILURE;
        }
    }

    /**
     * Send payment reminder to user.
     */
    private function sendReminder(Application $application, int $reminderNumber, \Carbon\Carbon $dueDate): void
    {
        try {
            $user = $application->user;
            if (!$user) {
                return;
            }

            $messages = [
                1 => "This is your first payment reminder. Your payment is due on {$dueDate->format('d M Y')}. Please ensure payment is completed to avoid service interruption.",
                2 => "This is your second payment reminder. Your payment is due in 8 days ({$dueDate->format('d M Y')}). Please complete the payment at your earliest convenience.",
                3 => "This is your final payment reminder. Your payment is due tomorrow ({$dueDate->format('d M Y')}). Please complete the payment immediately to avoid service interruption.",
            ];

            $subject = "Payment Reminder #{$reminderNumber} - Application {$application->application_id}";
            $message = $messages[$reminderNumber] ?? "Your payment is due on {$dueDate->format('d M Y')}.";

            // Send message
            Message::create([
                'user_id' => $user->id,
                'subject' => $subject,
                'message' => $message,
                'is_read' => false,
                'sent_by' => 'system',
            ]);

            // Send email (you can create a dedicated mail class if needed)
            // For now, we'll just log it
            Log::info("Payment reminder #{$reminderNumber} sent to user {$user->id} for application {$application->application_id}");

        } catch (\Exception $e) {
            Log::error("Error sending payment reminder #{$reminderNumber} for application {$application->id}: ".$e->getMessage());
        }
    }
}
