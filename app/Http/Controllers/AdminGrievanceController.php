<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketMessage;
use App\Services\TicketAssignmentService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AdminGrievanceController extends Controller
{
    /**
     * Display list of tickets assigned to the admin.
     */
    public function index(Request $request): View|RedirectResponse
    {
        $adminId = session('admin_id');
        $admin = Admin::with('roles')->find($adminId);

        if (! $admin) {
            return redirect()->route('admin.login')
                ->with('error', 'Admin session expired. Please login again.');
        }

        // Get current admin's role (from session or first role)
        $selectedRole = session('admin_selected_role', null);
        if (! $selectedRole && $admin->roles->count() > 0) {
            $selectedRole = $admin->roles->first()->slug;
        }

        // Filter by assigned_role matching current admin's role
        $query = Ticket::with(['user', 'messages']);

        // Only filter by role if we have a valid role
        if ($selectedRole) {
            $query->where('assigned_role', $selectedRole);
        } else {
            // If no role, return empty result
            $query->whereRaw('1 = 0');
        }

        // Filter by priority (only if value is provided and not empty)
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        // Filter by escalated status (only if value is provided and not empty)
        if ($request->filled('escalated')) {
            if ($request->escalated === 'yes') {
                $query->where('escalation_level', '!=', 'none');
            } elseif ($request->escalated === 'no') {
                $query->where('escalation_level', 'none');
            }
        }

        // Filter by status (only if value is provided and not empty)
        if ($request->filled('status')) {
            if ($request->status === 'open') {
                $query->whereIn('status', ['open', 'assigned', 'in_progress']);
            } elseif ($request->status === 'resolved') {
                $query->whereIn('status', ['resolved', 'closed']);
            } else {
                $query->where('status', $request->status);
            }
        }

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('ticket_id', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%")
                    ->orWhere('type', 'like', "%{$search}%")
                    ->orWhere('priority', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('fullname', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('registrationid', 'like', "%{$search}%");
                    });
            });
        }

        $tickets = $query->latest()->paginate(15)->withQueryString();

        return view('admin.grievance.index', compact('admin', 'tickets', 'selectedRole'));
    }

    /**
     * Display ticket details.
     */
    public function show(string $id): View|RedirectResponse
    {
        $adminId = session('admin_id');
        $admin = Admin::with('roles')->find($adminId);

        if (! $admin) {
            return redirect()->route('admin.login')
                ->with('error', 'Admin session expired. Please login again.');
        }

        // Get current admin's role (from session or first role)
        $selectedRole = session('admin_selected_role', null);
        if (! $selectedRole && $admin->roles->count() > 0) {
            $selectedRole = $admin->roles->first()->slug;
        }

        $ticket = Ticket::where('id', (int) $id)
            ->where('assigned_role', $selectedRole)
            ->with(['user', 'messages.attachments', 'attachments', 'assignedBy', 'forwardedBy'])
            ->firstOrFail();

        // Get forwardable roles for this ticket
        $forwardableRoles = TicketAssignmentService::getForwardableRoles($ticket, $admin);

        return view('admin.grievance.show', compact('admin', 'ticket', 'forwardableRoles'));
    }

    /**
     * Reply to a ticket.
     */
    public function reply(Request $request, string $id): RedirectResponse
    {
        $adminId = session('admin_id');
        $admin = Admin::find($adminId);

        if (! $admin) {
            return redirect()->route('admin.login')
                ->with('error', 'Admin session expired. Please login again.');
        }

        // Get current admin's role
        $selectedRole = session('admin_selected_role', null);
        if (! $selectedRole && $admin->roles->count() > 0) {
            $selectedRole = $admin->roles->first()->slug;
        }

        $ticket = Ticket::where('id', (int) $id)
            ->where('assigned_role', $selectedRole)
            ->firstOrFail();

        $validated = $request->validate([
            'message' => 'required|string|min:5',
            'is_internal' => 'nullable|boolean',
            'attachments.*' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:10240',
        ]);

        try {
            // Create reply message
            $message = TicketMessage::create([
                'ticket_id' => $ticket->id,
                'sender_type' => 'admin',
                'sender_id' => $adminId,
                'message' => $validated['message'],
                'is_internal' => $validated['is_internal'] ?? false,
            ]);

            // Update ticket status
            if ($ticket->status === 'assigned') {
                $ticket->update(['status' => 'in_progress']);
            }

            // Handle file attachments
            if ($request->hasFile('attachments')) {
                $storagePath = 'tickets/'.$ticket->ticket_id.'/'.now()->format('YmdHis');

                foreach ($request->file('attachments') as $file) {
                    $filePath = $file->store($storagePath, 'public');

                    TicketAttachment::create([
                        'ticket_id' => $ticket->id,
                        'ticket_message_id' => $message->id,
                        'file_name' => $file->getClientOriginalName(),
                        'file_path' => $filePath,
                        'file_type' => $file->getMimeType(),
                        'file_size' => $file->getSize(),
                    ]);
                }
            }

            Log::info('Admin replied to ticket', [
                'ticket_id' => $ticket->ticket_id,
                'admin_id' => $adminId,
            ]);

            return back()->with('success', 'Reply sent successfully.');
        } catch (Exception $e) {
            Log::error('Error replying to ticket', [
                'error' => $e->getMessage(),
                'ticket_id' => $ticket->id,
            ]);

            return back()->with('error', 'Failed to send reply. Please try again.');
        }
    }

    /**
     * Resolve a ticket.
     */
    public function resolve(Request $request, string $id): RedirectResponse
    {
        $adminId = session('admin_id');
        $admin = Admin::find($adminId);

        if (! $admin) {
            return redirect()->route('admin.login')
                ->with('error', 'Admin session expired. Please login again.');
        }

        // Get current admin's role
        $selectedRole = session('admin_selected_role', null);
        if (! $selectedRole && $admin->roles->count() > 0) {
            $selectedRole = $admin->roles->first()->slug;
        }

        $ticket = Ticket::where('id', (int) $id)
            ->where('assigned_role', $selectedRole)
            ->firstOrFail();

        $validated = $request->validate([
            'resolution_notes' => 'nullable|string|max:1000',
        ]);

        try {
            $ticket->update([
                'status' => 'resolved',
                'resolved_at' => now(),
                'resolution_notes' => $validated['resolution_notes'] ?? null,
            ]);

            Log::info('Admin resolved ticket', [
                'ticket_id' => $ticket->ticket_id,
                'admin_id' => $adminId,
            ]);

            return back()->with('success', 'Ticket marked as resolved.');
        } catch (Exception $e) {
            Log::error('Error resolving ticket', [
                'error' => $e->getMessage(),
                'ticket_id' => $ticket->id,
            ]);

            return back()->with('error', 'Failed to resolve ticket. Please try again.');
        }
    }

    /**
     * Close a ticket.
     */
    public function close(Request $request, string $id): RedirectResponse
    {
        $adminId = session('admin_id');
        $admin = Admin::find($adminId);

        if (! $admin) {
            return redirect()->route('admin.login')
                ->with('error', 'Admin session expired. Please login again.');
        }

        // Get current admin's role
        $selectedRole = session('admin_selected_role', null);
        if (! $selectedRole && $admin->roles->count() > 0) {
            $selectedRole = $admin->roles->first()->slug;
        }

        $ticket = Ticket::where('id', (int) $id)
            ->where('assigned_role', $selectedRole)
            ->firstOrFail();

        $validated = $request->validate([
            'resolution_notes' => 'nullable|string|max:1000',
        ]);

        try {
            $ticket->update([
                'status' => 'closed',
                'closed_at' => now(),
                'closed_by' => $adminId,
                'resolution_notes' => $validated['resolution_notes'] ?? $ticket->resolution_notes,
            ]);

            Log::info('Admin closed ticket', [
                'ticket_id' => $ticket->ticket_id,
                'admin_id' => $adminId,
            ]);

            return redirect()->route('admin.grievance.index')
                ->with('success', 'Ticket closed successfully.');
        } catch (Exception $e) {
            Log::error('Error closing ticket', [
                'error' => $e->getMessage(),
                'ticket_id' => $ticket->id,
            ]);

            return back()->with('error', 'Failed to close ticket. Please try again.');
        }
    }

    /**
     * Forward a ticket to another admin with a specific role.
     */
    public function forward(Request $request, string $id): RedirectResponse
    {
        $adminId = session('admin_id');
        $admin = Admin::find($adminId);

        if (! $admin) {
            return redirect()->route('admin.login')
                ->with('error', 'Admin session expired. Please login again.');
        }

        // Get current admin's role
        $selectedRole = session('admin_selected_role', null);
        if (! $selectedRole && $admin->roles->count() > 0) {
            $selectedRole = $admin->roles->first()->slug;
        }

        $ticket = Ticket::where('id', (int) $id)
            ->where('assigned_role', $selectedRole)
            ->firstOrFail();

        $validated = $request->validate([
            'target_role' => 'required|string',
            'forwarding_notes' => 'nullable|string|max:1000',
        ]);

        // Check if admin can forward to this role
        if (! TicketAssignmentService::canForwardTo($ticket, $admin, $validated['target_role'])) {
            return back()->with('error', 'You do not have permission to forward this ticket to the selected role.');
        }

        try {
            $success = TicketAssignmentService::forwardTicket(
                $ticket,
                $admin,
                $validated['target_role'],
                $validated['forwarding_notes'] ?? null
            );

            if (! $success) {
                return back()->with('error', 'Failed to forward ticket. Please try again.');
            }

            // Create internal message about forwarding
            TicketMessage::create([
                'ticket_id' => $ticket->id,
                'sender_type' => 'admin',
                'sender_id' => $adminId,
                'message' => 'Ticket forwarded to '.$validated['target_role'].($validated['forwarding_notes'] ? "\n\nNotes: ".$validated['forwarding_notes'] : ''),
                'is_internal' => true,
            ]);

            Log::info('Admin forwarded ticket', [
                'ticket_id' => $ticket->ticket_id,
                'admin_id' => $adminId,
                'target_role' => $validated['target_role'],
            ]);

            return back()->with('success', 'Ticket forwarded successfully.');
        } catch (Exception $e) {
            Log::error('Error forwarding ticket', [
                'error' => $e->getMessage(),
                'ticket_id' => $ticket->id,
            ]);

            return back()->with('error', 'Failed to forward ticket. Please try again.');
        }
    }

    /**
     * Download ticket attachment securely.
     */
    public function downloadAttachment($attachmentId): BinaryFileResponse|RedirectResponse
    {
        try {
            $adminId = session('admin_id');
            $admin = Admin::find($adminId);

            if (! $admin) {
                return redirect()->route('admin.login')
                    ->with('error', 'Admin session expired. Please login again.');
            }

            // Get current admin's role
            $selectedRole = session('admin_selected_role', null);
            if (! $selectedRole && $admin->roles->count() > 0) {
                $selectedRole = $admin->roles->first()->slug;
            }

            $attachment = TicketAttachment::with('ticket')->findOrFail($attachmentId);

            // Verify admin has access to this ticket
            if ($selectedRole && $attachment->ticket->assigned_role !== $selectedRole) {
                abort(403, 'You do not have permission to access this attachment.');
            }

            // Check if file exists
            if (! Storage::disk('public')->exists($attachment->file_path)) {
                abort(404, 'File not found.');
            }

            $filePath = Storage::disk('public')->path($attachment->file_path);

            return response()->download($filePath, $attachment->file_name);
        } catch (Exception $e) {
            Log::error('Error downloading attachment: '.$e->getMessage());

            return back()->with('error', 'Unable to download attachment.');
        }
    }
}
