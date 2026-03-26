<?php

namespace App\Http\Controllers;

use App\Models\GrievanceCategory;
use App\Models\Registration;
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

class UserGrievanceController extends Controller
{
    /**
     * Display the grievance form.
     */
    public function create(): View|RedirectResponse
    {
        $userId = session('user_id');
        $user = Registration::find($userId);

        if (! $user) {
            return redirect()->route('login.index')
                ->with('error', 'User session expired. Please login again.');
        }

        $categories = TicketAssignmentService::getCategories();
        
        // Get all categories with subcategories for JavaScript
        $categoriesWithSubcategories = \App\Models\GrievanceCategory::active()
            ->with(['activeSubcategories'])
            ->orderBy('order')
            ->get()
            ->mapWithKeys(function ($category) {
                $subcategories = $category->activeSubcategories->mapWithKeys(function ($subcategory) {
                    return [$subcategory->slug => $subcategory->name];
                })->toArray();
                return [$category->slug => $subcategories];
            })
            ->toArray();

        return view('user.grievance.create', compact('user', 'categories', 'categoriesWithSubcategories'));
    }

    /**
     * Store a new grievance ticket.
     */
    public function store(Request $request): RedirectResponse
    {
        $userId = session('user_id');
        $user = Registration::find($userId);

        if (! $user) {
            return redirect()->route('login.index')
                ->with('error', 'User session expired. Please login again.');
        }

        $validated = $request->validate([
            'category' => 'required|in:network_connectivity,billing,request,feedback_suggestion,other',
            'sub_category' => 'nullable|string|max:255',
            'subject' => 'nullable|string|max:255',
            'description' => 'required|string|min:10',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'attachments.*' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:10240', // 10MB max
        ]);

        // Validate sub_category based on category
        $subCategories = TicketAssignmentService::getSubCategories($validated['category']);
        if (! empty($subCategories) && (! isset($validated['sub_category']) || ! array_key_exists($validated['sub_category'], $subCategories))) {
            return back()->withInput()->withErrors(['sub_category' => 'Please select a valid sub-category.']);
        }

        try {
            // Generate ticket ID
            $ticketId = Ticket::generateTicketId();

            // Create ticket
            $ticket = Ticket::create([
                'ticket_id' => $ticketId,
                'user_id' => $userId,
                'category' => $validated['category'],
                'sub_category' => $validated['sub_category'] ?? null,
                'subject' => $validated['subject'] ?? null,
                'description' => $validated['description'],
                'priority' => $validated['priority'] ?? 'medium',
                'status' => 'open',
            ]);

            // Auto-assign ticket based on category and sub_category
            TicketAssignmentService::assignTicket($ticket);

            // Create initial message
            $message = TicketMessage::create([
                'ticket_id' => $ticket->id,
                'sender_type' => 'user',
                'sender_id' => $userId,
                'message' => $validated['description'],
                'is_internal' => false,
            ]);

            // Handle file attachments
            if ($request->hasFile('attachments')) {
                $storagePath = 'tickets/'.$ticketId.'/'.now()->format('YmdHis');

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

            Log::info('New grievance ticket created', [
                'ticket_id' => $ticketId,
                'user_id' => $userId,
                'category' => $validated['category'],
                'sub_category' => $validated['sub_category'] ?? null,
            ]);

            return redirect()->route('user.grievance.index')
                ->with('success', 'Your grievance has been submitted successfully. Ticket ID: '.$ticketId);
        } catch (Exception $e) {
            Log::error('Error creating grievance ticket', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withInput()
                ->with('error', 'Failed to submit grievance. Please try again.');
        }
    }

    /**
     * Display list of user's tickets.
     */
    public function index(): View|RedirectResponse
    {
        $userId = session('user_id');
        $user = Registration::find($userId);

        if (! $user) {
            return redirect()->route('login.index')
                ->with('error', 'User session expired. Please login again.');
        }

        $tickets = Ticket::where('user_id', $userId)
            ->with(['assignedAdmin', 'messages'])
            ->latest()
            ->paginate(15);

        return view('user.grievance.index', compact('user', 'tickets'));
    }

    /**
     * Display ticket details and conversation.
     */
    public function show(string $id): View|RedirectResponse
    {
        $userId = session('user_id');
        $user = Registration::find($userId);

        if (! $user) {
            return redirect()->route('login.index')
                ->with('error', 'User session expired. Please login again.');
        }

        $ticket = Ticket::where('id', (int) $id)
            ->where('user_id', $userId)
            ->with(['messages.attachments', 'attachments', 'assignedAdmin'])
            ->firstOrFail();

        return view('user.grievance.show', compact('user', 'ticket'));
    }

    /**
     * Reply to a ticket.
     */
    public function reply(Request $request, string $id): RedirectResponse
    {
        $userId = session('user_id');
        $user = Registration::find($userId);

        if (! $user) {
            return redirect()->route('login.index')
                ->with('error', 'User session expired. Please login again.');
        }

        $ticket = Ticket::where('id', (int) $id)
            ->where('user_id', $userId)
            ->firstOrFail();

        // Check if ticket is closed
        if ($ticket->status === 'closed') {
            return back()->with('error', 'This ticket is closed. You cannot reply to closed tickets.');
        }

        $validated = $request->validate([
            'message' => 'required|string|min:5',
            'attachments.*' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:10240',
        ]);

        try {
            // Create reply message
            $message = TicketMessage::create([
                'ticket_id' => $ticket->id,
                'sender_type' => 'user',
                'sender_id' => $userId,
                'message' => $validated['message'],
                'is_internal' => false,
            ]);

            // Update ticket status if it was resolved
            if ($ticket->status === 'resolved') {
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

            Log::info('User replied to ticket', [
                'ticket_id' => $ticket->ticket_id,
                'user_id' => $userId,
            ]);

            return back()->with('success', 'Your reply has been sent successfully.');
        } catch (Exception $e) {
            Log::error('Error replying to ticket', [
                'error' => $e->getMessage(),
                'ticket_id' => $ticket->id,
            ]);

            return back()->with('error', 'Failed to send reply. Please try again.');
        }
    }

    /**
     * Download ticket attachment securely.
     */
    public function downloadAttachment($attachmentId): BinaryFileResponse|RedirectResponse
    {
        try {
            $userId = session('user_id');
            $user = Registration::find($userId);

            if (! $user) {
                return redirect()->route('login.index')
                    ->with('error', 'User session expired. Please login again.');
            }

            $attachment = TicketAttachment::with('ticket')->findOrFail($attachmentId);

            // Verify user owns this ticket
            if ($attachment->ticket->user_id !== $userId) {
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
