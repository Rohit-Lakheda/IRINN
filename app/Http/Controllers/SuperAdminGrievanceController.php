<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\Role;
use App\Models\SuperAdmin;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SuperAdminGrievanceController extends Controller
{
    /**
     * Display list of all tickets.
     */
    public function index(Request $request): View|RedirectResponse
    {
        $superAdminId = session('superadmin_id');
        $superAdmin = SuperAdmin::find($superAdminId);

        if (! $superAdmin) {
            return redirect()->route('superadmin.login')
                ->with('error', 'SuperAdmin session expired. Please login again.');
        }

        $query = Ticket::with(['user', 'assignedAdmin', 'messages']);

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
                    })
                    ->orWhereHas('assignedAdmin', function ($adminQuery) use ($search) {
                        $adminQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        // Filter by status
        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        // Filter by type
        if ($request->has('type') && $request->type !== '') {
            $query->where('type', $request->type);
        }

        // Filter by assigned
        if ($request->has('assigned') && $request->assigned !== '') {
            if ($request->assigned === 'unassigned') {
                $query->whereNull('assigned_to');
            } else {
                $query->whereNotNull('assigned_to');
            }
        }

        $tickets = $query->latest()->paginate(20)->withQueryString();

        // Get all admins for assignment dropdown
        $admins = Admin::with('roles')->get();

        // Get admin roles for filtering
        $roles = Role::all();

        return view('superadmin.grievance.index', compact('superAdmin', 'tickets', 'admins', 'roles'));
    }

    /**
     * Display ticket details.
     */
    public function show(string $id): View|RedirectResponse
    {
        $superAdminId = session('superadmin_id');
        $superAdmin = SuperAdmin::find($superAdminId);

        if (! $superAdmin) {
            return redirect()->route('superadmin.login')
                ->with('error', 'SuperAdmin session expired. Please login again.');
        }

        $ticket = Ticket::with(['user', 'messages.attachments', 'attachments', 'assignedAdmin', 'assignedBy', 'closedByAdmin'])
            ->findOrFail((int) $id);

        // Get admins grouped by role for assignment
        $admins = Admin::with('roles')->get();
        $roles = Role::all();

        return view('superadmin.grievance.show', compact('superAdmin', 'ticket', 'admins', 'roles'));
    }

    /**
     * Assign ticket to an admin.
     */
    public function assign(Request $request, string $id): RedirectResponse
    {
        $superAdminId = session('superadmin_id');
        $superAdmin = SuperAdmin::find($superAdminId);

        if (! $superAdmin) {
            return redirect()->route('superadmin.login')
                ->with('error', 'SuperAdmin session expired. Please login again.');
        }

        $ticket = Ticket::findOrFail((int) $id);

        $validated = $request->validate([
            'assigned_to' => 'required|exists:admins,id',
            'priority' => 'nullable|in:low,medium,high,urgent',
        ]);

        try {
            $ticket->update([
                'assigned_to' => $validated['assigned_to'],
                'assigned_by' => $superAdminId,
                'assigned_at' => now(),
                'status' => 'assigned',
                'priority' => $validated['priority'] ?? $ticket->priority,
            ]);

            // Add internal note about assignment
            \App\Models\TicketMessage::create([
                'ticket_id' => $ticket->id,
                'sender_type' => 'superadmin',
                'sender_id' => $superAdminId,
                'message' => 'Ticket assigned to admin by SuperAdmin.',
                'is_internal' => true,
            ]);

            Log::info('SuperAdmin assigned ticket', [
                'ticket_id' => $ticket->ticket_id,
                'assigned_to' => $validated['assigned_to'],
                'superadmin_id' => $superAdminId,
            ]);

            return back()->with('success', 'Ticket assigned successfully.');
        } catch (Exception $e) {
            Log::error('Error assigning ticket', [
                'error' => $e->getMessage(),
                'ticket_id' => $ticket->id,
            ]);

            return back()->with('error', 'Failed to assign ticket. Please try again.');
        }
    }

    /**
     * Unassign ticket from admin.
     */
    public function unassign(string $id): RedirectResponse
    {
        $superAdminId = session('superadmin_id');
        $superAdmin = SuperAdmin::find($superAdminId);

        if (! $superAdmin) {
            return redirect()->route('superadmin.login')
                ->with('error', 'SuperAdmin session expired. Please login again.');
        }

        $ticket = Ticket::findOrFail((int) $id);

        try {
            $ticket->update([
                'assigned_to' => null,
                'assigned_by' => null,
                'assigned_at' => null,
                'status' => 'open',
            ]);

            // Add internal note about unassignment
            \App\Models\TicketMessage::create([
                'ticket_id' => $ticket->id,
                'sender_type' => 'superadmin',
                'sender_id' => $superAdminId,
                'message' => 'Ticket unassigned by SuperAdmin.',
                'is_internal' => true,
            ]);

            Log::info('SuperAdmin unassigned ticket', [
                'ticket_id' => $ticket->ticket_id,
                'superadmin_id' => $superAdminId,
            ]);

            return back()->with('success', 'Ticket unassigned successfully.');
        } catch (Exception $e) {
            Log::error('Error unassigning ticket', [
                'error' => $e->getMessage(),
                'ticket_id' => $ticket->id,
            ]);

            return back()->with('error', 'Failed to unassign ticket. Please try again.');
        }
    }

    /**
     * Get admins by role for assignment.
     */
    public function getAdminsByRole(Request $request)
    {
        $roleSlug = $request->input('role');

        if (! $roleSlug) {
            return response()->json(['admins' => []]);
        }

        $role = Role::where('slug', $roleSlug)->first();

        if (! $role) {
            return response()->json(['admins' => []]);
        }

        $admins = Admin::whereHas('roles', function ($query) use ($role) {
            $query->where('roles.id', $role->id);
        })->get(['id', 'name', 'email']);

        return response()->json(['admins' => $admins]);
    }

    /**
     * Download ticket attachment securely.
     */
    public function downloadAttachment($attachmentId): BinaryFileResponse|RedirectResponse
    {
        try {
            $superAdminId = session('superadmin_id');
            $superAdmin = SuperAdmin::find($superAdminId);

            if (! $superAdmin) {
                return redirect()->route('superadmin.login')
                    ->with('error', 'SuperAdmin session expired. Please login again.');
            }

            $attachment = TicketAttachment::with('ticket')->findOrFail($attachmentId);

            // SuperAdmin can access all attachments
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
