<?php

namespace App\Http\Controllers;

use App\Mail\IrinnAnnualInvoiceMail;
use App\Mail\IrinnResubmissionRequestedMail;
use App\Mail\ProfileUpdateApprovedMail;
use App\Models\Admin;
use App\Models\AdminAction;
use App\Models\Application;
use App\Models\ApplicationGstChangeHistory;
use App\Models\ApplicationGstUpdateRequest;
use App\Models\ApplicationStatusHistory;
use App\Models\GstVerification;
use App\Models\Invoice;
use App\Models\McaVerification;
use App\Models\Message;
use App\Models\PanVerification;
use App\Models\PaymentTransaction;
use App\Models\PaymentVerificationLog;
use App\Models\ProfileUpdateRequest;
use App\Models\Registration;
use App\Models\RocIecVerification;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketMessage;
use App\Models\UdyamVerification;
use App\Models\UserKycProfile;
use App\Services\IrinnAnnualInvoiceService;
use App\Support\IrinnApplicationDisplayEnricher;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use PDOException;

class AdminController extends Controller
{
    /**
     * Get current admin with roles.
     */
    protected function getCurrentAdmin()
    {
        $adminId = session('admin_id');

        return Admin::with('roles')->findOrFail($adminId);
    }

    /**
     * Check if admin has a specific role.
     */
    protected function hasRole(Admin $admin, string $roleSlug): bool
    {
        return $admin->hasRole($roleSlug);
    }

    /**
     * Display the Admin dashboard.
     */
    public function index(Request $request)
    {
        try {
            $admin = $this->getCurrentAdmin();
            $adminId = $admin->id;

            // Get selected role from query parameter or session (helpdesk, hostmaster, billing)
            $selectedRole = $request->get('role', session('admin_selected_role', null));

            // If admin has multiple roles and no role is selected, auto-select based on priority
            if ($admin->roles->count() > 1 && ! $selectedRole) {
                // Priority order: IRINN workflow roles
                $priorityOrder = [
                    'helpdesk', 'hostmaster', 'billing',
                ];
                foreach ($priorityOrder as $priorityRole) {
                    if ($admin->hasRole($priorityRole)) {
                        $selectedRole = $priorityRole;
                        break;
                    }
                }
            }

            // Validate selected role belongs to admin
            if ($selectedRole && ! $admin->hasRole($selectedRole)) {
                $selectedRole = null;
            }

            // Store selected role in session
            if ($selectedRole) {
                session(['admin_selected_role' => $selectedRole]);
            }

            // Calculate statistics (all applications visible, is_active shows live status)
            $totalUsers = Registration::count();
            $totalApplications = Application::count();

            // Calculate approved applications based on admin's role
            // Approved = applications at stages higher than current role
            $roleToUse = $selectedRole;
            if ($admin->roles->count() === 1) {
                $roleToUse = $admin->roles->first()->slug;
            }

            $approvedApplications = Application::where('application_type', 'IRINN')
                ->where('irinn_current_stage', 'billing_approved')
                ->count();

            // Approved applications with payment verification
            $approvedApplicationsWithPayment = Application::whereIn('status', ['approved', 'payment_verified'])
                ->whereHas('paymentTransactions', function ($q) {
                    $q->where('payment_status', 'success');
                })
                ->count();

            $totalMembers = Application::whereNotNull('membership_id')
                ->where('application_type', 'IRINN')
                ->count();

            $activeMembers = Application::whereNotNull('membership_id')
                ->where('application_type', 'IRINN')
                ->where('is_active', true)
                ->count();

            $disconnectedMembers = Application::whereNotNull('membership_id')
                ->where('application_type', 'IRINN')
                ->where('is_active', false)
                ->count();

            // Recent Live Members (applications with membership_id and is_active = true, ordered by most recent)
            $recentLiveMembers = Application::with('user')
                ->whereNotNull('membership_id')
                ->where('application_type', 'IRINN')
                ->where('is_active', true)
                ->orderBy('updated_at', 'desc')
                ->take(10)
                ->get();

            // Grievance Tracking
            $totalGrievances = Ticket::count();
            $openGrievances = Ticket::whereIn('status', ['open', 'assigned', 'in_progress'])->count();
            $closedGrievances = Ticket::whereIn('status', ['resolved', 'closed'])->count();

            // Pending applications based on selected role (all visible, is_active shows live status)
            $pendingApplications = 0;
            $roleToUse = $selectedRole;
            if ($admin->roles->count() === 1) {
                $roleToUse = $admin->roles->first()->slug;
            }

            $irinnPending = Application::where('application_type', 'IRINN')
                ->whereNotIn('status', ['draft', 'rejected']);

            if ($roleToUse === 'helpdesk') {
                $pendingApplications = (clone $irinnPending)->where('irinn_current_stage', 'helpdesk')->count();
            } elseif ($roleToUse === 'hostmaster') {
                $pendingApplications = (clone $irinnPending)->where('irinn_current_stage', 'hostmaster')->count();
            } elseif ($roleToUse === 'billing') {
                $pendingApplications = (clone $irinnPending)->where('irinn_current_stage', 'billing')->count();
            } elseif (in_array($roleToUse, ['processor', 'finance', 'technical'], true)) {
                $pendingApplications = Application::whereIn('status', ['pending', 'processor_review', 'processor_approved', 'finance_review', 'finance_approved'])
                    ->count();
            } else {
                $pendingApplications = (clone $irinnPending)
                    ->where(function ($q) {
                        $q->whereNull('irinn_current_stage')
                            ->orWhereNotIn('irinn_current_stage', ['billing_approved']);
                    })
                    ->count();
            }

            $recentUsers = Registration::latest()->take(10)->get();

            // Recent members (applications with membership_id, ordered by most recent)
            $recentMembers = Application::with('user')
                ->whereNotNull('membership_id')
                ->where('application_type', 'IRINN')
                ->orderBy('updated_at', 'desc')
                ->take(10)
                ->get();

            return view('admin.dashboard', compact(
                'admin',
                'totalUsers',
                'totalApplications',
                'approvedApplications',
                'roleToUse',
                'approvedApplicationsWithPayment',
                'pendingApplications',
                'selectedRole',
                'recentUsers',
                'totalMembers',
                'activeMembers',
                'disconnectedMembers',
                'recentLiveMembers',
                'recentMembers',
                'totalGrievances',
                'openGrievances',
                'closedGrievances'
            ));
        } catch (QueryException $e) {
            Log::error('Database error loading Admin dashboard: '.$e->getMessage());
            abort(503, 'Database connection error. Please try again later.');
        } catch (PDOException $e) {
            Log::error('PDO error loading Admin dashboard: '.$e->getMessage());
            abort(503, 'Database connection error. Please try again later.');
        } catch (Exception $e) {
            Log::error('Error loading Admin dashboard: '.$e->getMessage());
            abort(500, 'Unable to load dashboard. Please try again later.');
        }
    }

    /**
     * Display all users.
     */
    public function users(Request $request)
    {
        try {
            $query = Registration::with(['messages', 'profileUpdateRequests']);

            // Search functionality
            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('fullname', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('mobile', 'like', "%{$search}%")
                        ->orWhere('pancardno', 'like', "%{$search}%")
                        ->orWhere('registrationid', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%");
                });
            }

            // Pagination per page
            $perPage = $request->get('per_page', 20);
            $perPage = in_array($perPage, [10, 20, 50, 100]) ? $perPage : 20;

            $users = $query->latest()->paginate($perPage)->withQueryString();

            return view('admin.users.index', compact('users'));
        } catch (QueryException $e) {
            Log::error('Database error loading users: '.$e->getMessage());
            abort(503, 'Database connection error. Please try again later.');
        } catch (PDOException $e) {
            Log::error('PDO error loading users: '.$e->getMessage());
            abort(503, 'Database connection error. Please try again later.');
        } catch (Exception $e) {
            Log::error('Error loading users: '.$e->getMessage());

            return redirect()->route('admin.dashboard')
                ->with('error', 'Unable to load users. Please try again.');
        }
    }

    /**
     * Export users to Excel (CSV).
     */
    public function exportUsersToExcel(Request $request)
    {
        // Increase execution time limit for large exports
        set_time_limit(300);
        ini_set('max_execution_time', 300);

        // Prevent any output before CSV
        if (ob_get_level()) {
            ob_end_clean();
        }

        try {
            $query = Registration::query();

            // Apply search filter if present
            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('fullname', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('mobile', 'like', "%{$search}%")
                        ->orWhere('pancardno', 'like', "%{$search}%")
                        ->orWhere('registrationid', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%");
                });
            }

            $users = $query->latest()->get();

            $filename = 'registrations_export_'.date('Y-m-d_His').'.csv';

            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ];

            $callback = function () use ($users) {
                $file = fopen('php://output', 'w');

                // Add BOM for UTF-8
                fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

                // Headers
                fputcsv($file, [
                    'Registration ID',
                    'Name',
                    'Email',
                    'Mobile',
                    'PAN Card',
                    'Status',
                    'Registered Date',
                ]);

                // Data
                foreach ($users as $user) {
                    fputcsv($file, [
                        $user->registrationid,
                        $user->fullname,
                        $user->email,
                        $user->mobile,
                        $user->pancardno ?? '',
                        ucfirst($user->status),
                        $user->created_at->format('Y-m-d H:i:s'),
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        } catch (Exception $e) {
            Log::error('Error exporting users to Excel: '.$e->getMessage());

            return redirect()->route('admin.users')->with('error', 'Unable to export users. Please try again.');
        }
    }

    /**
     * Send credentials to user via email.
     */
    public function sendCredentials(Request $request, $id)
    {
        try {
            $user = Registration::findOrFail($id);

            // Generate a new secure random password
            $newPassword = $this->generateRandomPassword();

            // Update user's password
            $user->password = Hash::make($newPassword);
            $user->save();

            // Generate password update token for user to change password later
            $updateToken = Str::random(64);
            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $user->email],
                [
                    'token' => Hash::make($updateToken),
                    'created_at' => now(),
                ]
            );
            $updatePasswordUrl = route('login.update-password', ['token' => $updateToken, 'email' => $user->email]);

            // Send credentials email
            try {
                $loginUrl = route('login.index');
                $username = $user->pancardno ?? $user->email;
                // $username = $user->pancardno;

                Mail::to($user->email)->send(new \App\Mail\RegistrationSuccessMail(
                    $username,
                    $user->email,
                    $newPassword,
                    $user->registrationid,
                    $loginUrl,
                    $updatePasswordUrl
                ));

                Log::info("Credentials sent to user {$user->email} by admin");

                return back()->with('success', 'Credentials have been sent successfully to '.$user->email);
            } catch (Exception $e) {
                Log::error('Failed to send credentials email: '.$e->getMessage());

                return back()->with('error', 'Failed to send email. Please try again.');
            }
        } catch (Exception $e) {
            Log::error('Error sending credentials: '.$e->getMessage());

            return back()->with('error', 'An error occurred while sending credentials. Please try again.');
        }
    }

    /**
     * Generate a secure random password.
     */
    private function generateRandomPassword(): string
    {
        // Generate a password with at least 12 characters
        // Include uppercase, lowercase, numbers, and special characters
        $length = 12;
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $special = '!@#$%^&*';

        $password = '';
        $password .= $uppercase[rand(0, strlen($uppercase) - 1)];
        $password .= $lowercase[rand(0, strlen($lowercase) - 1)];
        $password .= $numbers[rand(0, strlen($numbers) - 1)];
        $password .= $special[rand(0, strlen($special) - 1)];

        $all = $uppercase.$lowercase.$numbers.$special;
        for ($i = strlen($password); $i < $length; $i++) {
            $password .= $all[rand(0, strlen($all) - 1)];
        }

        // Shuffle the password to randomize character positions
        return str_shuffle($password);
    }

    /**
     * Display user details with full history.
     */
    public function showUser($id, Request $request)
    {
        try {
            $admin = $this->getCurrentAdmin();
            $selectedRole = session('admin_selected_role', null);

            $user = Registration::with([
                'wallet',
                'messages',
                'profileUpdateRequests.approver',
                'profileUpdateRequests' => function ($query) {
                    $query->with('approver')->latest();
                },
                'applications' => function ($query) {
                    $query->with(['invoices' => function ($q) {
                        $q->latest('invoice_date');
                    }])
                        ->latest();
                },
            ])->findOrFail($id);

            // Check if this is a member (has applications with membership_id)
            $isMember = $user->applications->whereNotNull('membership_id')->count() > 0;
            // Check if accessed from members page
            $fromMembersPage = $request->get('from', '') === 'members';

            // Filter applications by stage based on selected role / IRINN workflow
            $stageFilter = $request->get('stage', $selectedRole);
            $applicationsQuery = $user->applications();

            // Prefer current IRINN flow (helpdesk -> hostmaster -> billing)
            if ($stageFilter && in_array($stageFilter, ['helpdesk', 'hostmaster', 'billing'], true)) {
                $applicationsQuery
                    ->where('application_type', 'IRINN')
                    ->where('status', $stageFilter);
            }

            $applications = $applicationsQuery->get();

            // Get all admin actions related to this user with pagination
            $adminActionsQuery = AdminAction::where(function ($query) use ($id, $user) {
                $query->where(function ($q) use ($id) {
                    $q->where('actionable_type', Registration::class)
                        ->where('actionable_id', $id);
                })
                    ->orWhere(function ($q) use ($user) {
                        $q->where('actionable_type', ProfileUpdateRequest::class)
                            ->whereIn('actionable_id', $user->profileUpdateRequests->pluck('id'));
                    })
                    ->orWhere(function ($q) use ($user) {
                        $q->where('actionable_type', Message::class)
                            ->whereIn('actionable_id', $user->messages->pluck('id'));
                    });
            })
                ->with(['admin', 'superAdmin']);

            $perPageActions = $request->get('per_page_actions', 10);
            $perPageActions = in_array($perPageActions, [10, 20, 50, 100]) ? $perPageActions : 10;
            $adminActions = $adminActionsQuery->latest()->paginate($perPageActions)->withQueryString();

            // Get payment transactions for this user with pagination
            $transactionsQuery = PaymentTransaction::where('user_id', $id);
            $perPageTransactions = $request->get('per_page_transactions', 10);
            $perPageTransactions = in_array($perPageTransactions, [10, 20, 50, 100]) ? $perPageTransactions : 10;
            $transactions = $transactionsQuery->latest()->paginate($perPageTransactions)->withQueryString();

            return view('admin.users.show', compact('user', 'adminActions', 'admin', 'isMember', 'fromMembersPage', 'transactions', 'applications', 'stageFilter', 'selectedRole'));
        } catch (QueryException $e) {
            Log::error('Database error loading user details: '.$e->getMessage());
            abort(503, 'Database connection error. Please try again later.');
        } catch (PDOException $e) {
            Log::error('PDO error loading user details: '.$e->getMessage());
            abort(503, 'Database connection error. Please try again later.');
        } catch (Exception $e) {
            Log::error('Error loading user details: '.$e->getMessage());

            return redirect()->route('admin.users')
                ->with('error', 'User not found.');
        }
    }

    /**
     * Update user email.
     */
    public function updateUserEmail(Request $request, $id)
    {
        try {
            $request->validate([
                'email' => 'required|email|unique:registrations,email,'.$id,
            ]);

            $user = Registration::with('applications')->findOrFail($id);
            $oldEmail = $user->email;
            $newEmail = $request->input('email');

            // Update registration table
            $user->email = $newEmail;
            $user->email_verified = false; // Reset verification status
            $user->save();

            // Update email inside all related applications' registration/representative details
            foreach ($user->applications as $application) {
                $updated = false;

                $registrationDetails = $application->registration_details ?? [];
                if (is_array($registrationDetails) && array_key_exists('email', $registrationDetails)) {
                    $registrationDetails['email'] = $newEmail;
                    $application->registration_details = $registrationDetails;
                    $updated = true;
                }

                $repDetails = $application->authorized_representative_details ?? [];
                if (is_array($repDetails) && array_key_exists('email', $repDetails)) {
                    $repDetails['email'] = $newEmail;
                    $application->authorized_representative_details = $repDetails;
                    $updated = true;
                }

                if ($updated) {
                    $application->save();
                }
            }

            // Log admin action
            $admin = $this->getCurrentAdmin();
            AdminAction::create([
                'admin_id' => $admin->id,
                'actionable_type' => Registration::class,
                'actionable_id' => $user->id,
                'action_type' => 'updated_email',
                'description' => "Updated email from {$oldEmail} to {$newEmail}",
            ]);

            return redirect()->back()->with('success', 'Email updated successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (Exception $e) {
            Log::error('Error updating user email: '.$e->getMessage());

            return redirect()->back()->with('error', 'Unable to update email. Please try again.');
        }
    }

    /**
     * Export transactions to Excel (CSV).
     */
    public function exportUserTransactions($id, Request $request)
    {
        set_time_limit(300);
        ini_set('max_execution_time', 300);

        if (ob_get_level()) {
            ob_end_clean();
        }

        try {
            $transactions = PaymentTransaction::where('user_id', $id)->latest()->get();
            $user = Registration::findOrFail($id);

            $filename = 'transactions_'.$user->registrationid.'_'.date('Y-m-d_His').'.csv';

            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ];

            $callback = function () use ($transactions) {
                $file = fopen('php://output', 'w');
                fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

                fputcsv($file, [
                    'Date/Time',
                    'Transaction ID',
                    'Payment ID',
                    'Bank Ref. No.',
                    'Payment Mode',
                    'Amount',
                    'Status',
                ]);

                foreach ($transactions as $transaction) {
                    $bankRef = null;
                    if ($transaction->payu_response && is_array($transaction->payu_response)) {
                        $bankRef = $transaction->payu_response['bank_ref_num'] ?? null;
                    }
                    if (! $bankRef && $transaction->response_message) {
                        if (preg_match('/Bank Ref:\s*([^\s-]+)/i', $transaction->response_message, $matches)) {
                            $bankRef = $matches[1];
                        }
                    }

                    $mode = null;
                    if ($transaction->payu_response && is_array($transaction->payu_response)) {
                        $mode = $transaction->payu_response['mode'] ?? null;
                    }

                    fputcsv($file, [
                        $transaction->created_at->format('Y-m-d H:i:s'),
                        $transaction->transaction_id,
                        $transaction->payment_id,
                        $bankRef ?? 'N/A',
                        $mode ?? 'N/A',
                        number_format($transaction->amount, 2),
                        strtoupper($transaction->payment_status),
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        } catch (Exception $e) {
            Log::error('Error exporting transactions: '.$e->getMessage());

            return redirect()->back()->with('error', 'Unable to export transactions. Please try again.');
        }
    }

    /**
     * Export admin actions to Excel (CSV).
     */
    public function exportUserAdminActions($id, Request $request)
    {
        set_time_limit(300);
        ini_set('max_execution_time', 300);

        if (ob_get_level()) {
            ob_end_clean();
        }

        try {
            $user = Registration::findOrFail($id);
            $adminActions = AdminAction::where(function ($query) use ($id, $user) {
                $query->where(function ($q) use ($id) {
                    $q->where('actionable_type', Registration::class)
                        ->where('actionable_id', $id);
                })
                    ->orWhere(function ($q) use ($user) {
                        $q->where('actionable_type', ProfileUpdateRequest::class)
                            ->whereIn('actionable_id', $user->profileUpdateRequests->pluck('id'));
                    })
                    ->orWhere(function ($q) use ($user) {
                        $q->where('actionable_type', Message::class)
                            ->whereIn('actionable_id', $user->messages->pluck('id'));
                    });
            })
                ->with(['admin', 'superAdmin'])
                ->latest()
                ->get();

            $filename = 'admin_actions_'.$user->registrationid.'_'.date('Y-m-d_His').'.csv';

            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ];

            $callback = function () use ($adminActions) {
                $file = fopen('php://output', 'w');
                fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

                fputcsv($file, [
                    'Date/Time',
                    'Admin',
                    'Action',
                    'Description',
                ]);

                foreach ($adminActions as $action) {
                    $adminName = 'System';
                    if ($action->superAdmin) {
                        $adminName = 'SuperAdmin: '.$action->superAdmin->name;
                    } elseif ($action->admin) {
                        $adminName = $action->admin->name;
                    }

                    fputcsv($file, [
                        $action->created_at->format('Y-m-d H:i:s'),
                        $adminName,
                        ucfirst(str_replace('_', ' ', $action->action_type)),
                        $action->description,
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        } catch (Exception $e) {
            Log::error('Error exporting admin actions: '.$e->getMessage());

            return redirect()->back()->with('error', 'Unable to export admin actions. Please try again.');
        }
    }

    /**
     * Helper method to create a message and link it to an admin action.
     */
    private function createMessageForAdmin(int $adminId, int $userId, string $subject, string $messageText): Message
    {
        $message = Message::create([
            'user_id' => $userId,
            'subject' => $subject,
            'message' => $messageText,
            'is_read' => false,
            'sent_by' => 'admin',
        ]);

        // Log action to link message to admin
        AdminAction::log(
            $adminId,
            'sent_message',
            $message,
            "Sent message: {$subject}",
            ['subject' => $subject]
        );

        return $message;
    }

    /**
     * Send message to user.
     */
    public function sendMessage(Request $request, $userId)
    {
        try {
            $validated = $request->validate([
                'subject' => 'required|string|max:255',
                'message' => 'required|string|min:10',
            ], [
                'subject.required' => 'Subject is required.',
                'message.required' => 'Message is required.',
                'message.min' => 'Message must be at least 10 characters.',
            ]);

            $user = Registration::findOrFail($userId);
            $adminId = session('admin_id');

            $this->createMessageForAdmin(
                $adminId,
                $user->id,
                $validated['subject'],
                $validated['message']
            );

            return back()->with('success', 'Message sent successfully!');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (QueryException $e) {
            Log::error('Database error sending message: '.$e->getMessage());

            return back()->with('error', 'Database connection error. Please try again later.')
                ->withInput();
        } catch (PDOException $e) {
            Log::error('PDO error sending message: '.$e->getMessage());

            return back()->with('error', 'Database connection error. Please try again later.')
                ->withInput();
        } catch (Exception $e) {
            Log::error('Error sending message: '.$e->getMessage());

            return back()->with('error', 'An error occurred while sending message. Please try again.');
        }
    }

    /**
     * Approve profile update request.
     */
    public function approveProfileUpdate($requestId)
    {
        try {
            $request = ProfileUpdateRequest::with('user')->findOrFail($requestId);
            $adminId = session('admin_id');

            $request->update([
                'status' => 'approved',
                'approved_at' => now('Asia/Kolkata'),
                'approved_by' => $adminId,
            ]);

            // Log action
            AdminAction::log(
                $adminId,
                'approved_profile_update',
                $request,
                "Approved profile update request for user: {$request->user->fullname}",
                ['user_id' => $request->user->id]
            );

            return back()->with('success', 'Profile update request approved!');
        } catch (QueryException $e) {
            Log::error('Database error approving profile update: '.$e->getMessage());

            return back()->with('error', 'Database connection error. Please try again later.');
        } catch (PDOException $e) {
            Log::error('PDO error approving profile update: '.$e->getMessage());

            return back()->with('error', 'Database connection error. Please try again later.');
        } catch (Exception $e) {
            Log::error('Error approving profile update: '.$e->getMessage());

            return back()->with('error', 'An error occurred. Please try again.');
        }
    }

    /**
     * Approve submitted profile update (apply changes to user).
     */
    public function approveSubmittedUpdate($requestId)
    {
        try {
            $updateRequest = ProfileUpdateRequest::with('user')->findOrFail($requestId);
            $adminId = session('admin_id');

            // Check if there's submitted data waiting for approval
            if (! $updateRequest->submitted_data || $updateRequest->update_approved) {
                return back()->with('error', 'This update has already been processed or has no submitted data.');
            }

            $user = $updateRequest->user;
            $submittedData = $updateRequest->submitted_data;

            // Get old email before update (to send email to new email)
            $oldEmail = $user->email;
            $newEmail = $submittedData['email'] ?? $user->email;

            // Apply the submitted changes to user
            $user->update($submittedData);

            // Mark the update as approved
            $updateRequest->update([
                'update_approved' => true,
                'update_approved_at' => now('Asia/Kolkata'),
            ]);

            // Log action
            AdminAction::log(
                $adminId,
                'approved_submitted_update',
                $updateRequest,
                "Approved and applied profile update for user: {$user->fullname}",
                ['user_id' => $user->id, 'changes' => $submittedData]
            );

            // Send message to user
            $admin = Admin::find($adminId);
            if ($admin) {
                $this->createMessageForAdmin(
                    $admin->id,
                    $user->id,
                    'Profile Update Approved',
                    'Your profile update has been approved and applied. Your profile information has been updated successfully.'
                );
            }

            // Send email to updated email address
            try {
                Mail::to($newEmail)->send(new ProfileUpdateApprovedMail($submittedData));
                Log::info("Profile update approved email sent to {$newEmail}");
            } catch (Exception $e) {
                Log::error('Failed to send profile update approved email: '.$e->getMessage());
                // Don't fail the approval if email fails
            }

            return back()->with('success', 'Profile update approved and applied successfully!');
        } catch (QueryException $e) {
            Log::error('Database error approving submitted update: '.$e->getMessage());

            return back()->with('error', 'Database connection error. Please try again later.');
        } catch (PDOException $e) {
            Log::error('PDO error approving submitted update: '.$e->getMessage());

            return back()->with('error', 'Database connection error. Please try again later.');
        } catch (Exception $e) {
            Log::error('Error approving submitted update: '.$e->getMessage());

            return back()->with('error', 'An error occurred. Please try again.');
        }
    }

    /**
     * Reject profile update request.
     */
    public function rejectProfileUpdate(Request $request, $requestId)
    {
        try {
            $validated = $request->validate([
                'admin_notes' => 'required|string|min:10',
            ], [
                'admin_notes.required' => 'Please provide a reason for rejection.',
                'admin_notes.min' => 'Please provide more details (minimum 10 characters).',
            ]);

            $updateRequest = ProfileUpdateRequest::with('user')->findOrFail($requestId);
            $adminId = session('admin_id');

            $updateRequest->update([
                'status' => 'rejected',
                'rejected_at' => now('Asia/Kolkata'),
                'admin_notes' => $validated['admin_notes'],
                'approved_by' => $adminId,
            ]);

            // Log action
            AdminAction::log(
                $adminId,
                'rejected_profile_update',
                $updateRequest,
                "Rejected profile update request for user: {$updateRequest->user->fullname}",
                ['reason' => $validated['admin_notes']]
            );

            // Send message to user about rejection
            $admin = Admin::find($adminId);
            if ($admin) {
                $this->createMessageForAdmin(
                    $admin->id,
                    $updateRequest->user->id,
                    'Profile Update Request Rejected',
                    "Your profile update request has been rejected. Reason: {$validated['admin_notes']}\n\nYou can submit a new profile update request with corrected information."
                );
            }

            return back()->with('success', 'Profile update request rejected. User has been notified.');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (QueryException $e) {
            Log::error('Database error rejecting profile update: '.$e->getMessage());

            return back()->with('error', 'Database connection error. Please try again later.')
                ->withInput();
        } catch (PDOException $e) {
            Log::error('PDO error rejecting profile update: '.$e->getMessage());

            return back()->with('error', 'Database connection error. Please try again later.')
                ->withInput();
        } catch (Exception $e) {
            Log::error('Error rejecting profile update: '.$e->getMessage());

            return back()->with('error', 'An error occurred. Please try again.');
        }
    }

    /**
     * Update user status.
     */
    public function updateUserStatus(Request $request, $userId)
    {
        try {
            $validated = $request->validate([
                'status' => 'required|in:pending,approved,rejected,active,inactive',
            ]);

            $user = Registration::findOrFail($userId);
            $oldStatus = $user->status;
            $user->update(['status' => $validated['status']]);

            // Log action
            AdminAction::log(
                session('admin_id'),
                'updated_user_status',
                $user,
                "Changed user status from {$oldStatus} to {$validated['status']}",
                ['old_status' => $oldStatus, 'new_status' => $validated['status']]
            );

            $statusMessage = $validated['status'] === 'inactive' ? 'Member deactivated successfully!' : 'User status updated successfully!';

            return back()->with('success', $statusMessage);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        } catch (QueryException $e) {
            Log::error('Database error updating user status: '.$e->getMessage());

            return back()->with('error', 'Database connection error. Please try again later.');
        } catch (PDOException $e) {
            Log::error('PDO error updating user status: '.$e->getMessage());

            return back()->with('error', 'Database connection error. Please try again later.');
        } catch (Exception $e) {
            Log::error('Error updating user status: '.$e->getMessage());

            return back()->with('error', 'An error occurred. Please try again.');
        }
    }

    /**
     * Show members list with filters (active, disconnected, all).
     */
    public function members(Request $request)
    {
        try {
            $admin = $this->getCurrentAdmin();
            $selectedRole = $request->get('role', session('admin_selected_role', null));
            if ($admin->roles->count() === 1) {
                $selectedRole = $admin->roles->first()->slug;
            } elseif ($selectedRole && ! $admin->hasRole($selectedRole)) {
                $selectedRole = null;
            }
            if ($selectedRole) {
                session(['admin_selected_role' => $selectedRole]);
            }

            $filter = $request->get('filter', 'all'); // all, live, suspended, disconnected (legacy: active -> live)
            $paymentFilter = $request->get('payment_filter'); // generated, received, pending
            $gstVerificationFilter = $request->get('gst_verification_filter'); // verified, unverified, all

            // Query applications directly — IRINN members have membership_id
            $query = Application::whereNotNull('membership_id')
                ->where('application_type', 'IRINN');

            if (in_array($filter, ['active', 'live'], true)) {
                $query->where('service_status', 'live');
            } elseif (in_array($filter, ['suspended', 'disconnected'], true)) {
                $query->where('service_status', $filter);
            }

            $showBillingPaymentSummary = $this->hasRole($admin, 'billing') && $selectedRole === 'billing';
            $showExportReports = $showBillingPaymentSummary;
            if ($showBillingPaymentSummary && $paymentFilter) {
                if ($paymentFilter === 'generated') {
                    $query->where('service_status', 'live')
                        ->whereHas('invoices');
                } elseif ($paymentFilter === 'received') {
                    $query->where('service_status', 'live')
                        ->whereHas('invoices', function ($invoiceQuery) {
                            $invoiceQuery->where('payment_status', 'paid');
                        });
                } elseif ($paymentFilter === 'pending') {
                    $query->where('service_status', 'live')
                        ->whereHas('invoices', function ($invoiceQuery) {
                            $invoiceQuery->whereIn('payment_status', ['pending', 'partial', 'overdue']);
                        });
                }
            }

            // GST verification filter
            if ($gstVerificationFilter && $gstVerificationFilter !== 'all') {
                if ($gstVerificationFilter === 'verified') {
                    $query->whereHas('gstVerification', function ($gstQuery) {
                        $gstQuery->where('is_verified', true);
                    });
                } elseif ($gstVerificationFilter === 'unverified') {
                    $query->where(function ($subQuery) {
                        $subQuery->whereDoesntHave('gstVerification')
                            ->orWhereHas('gstVerification', function ($gstQuery) {
                                $gstQuery->where('is_verified', false);
                            });
                    });
                }
            }

            // Search functionality - search in user data
            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->whereHas('user', function ($q) use ($search) {
                    $q->where('fullname', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('registrationid', 'like', "%{$search}%")
                        ->orWhere('pancardno', 'like', "%{$search}%");
                });
            }

            // Get applications with user, invoices, and GST verification
            $members = $query->with([
                'user',
                'invoices',
                'gstVerification',
            ])->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

            // Payment statistics for billing role
            $paymentStats = null;
            $currentMonthStats = null;
            if ($showBillingPaymentSummary) {
                $baseInvoiceQuery = fn ($q) => $q->whereNotNull('membership_id')
                    ->where('service_status', 'live')
                    ->where('application_type', 'IRINN');

                $totalInvoices = \App\Models\Invoice::whereHas('application', $baseInvoiceQuery)
                    ->activeForTotals()
                    ->count();

                $paidInvoices = \App\Models\Invoice::whereHas('application', $baseInvoiceQuery)
                    ->activeForTotals()
                    ->where('payment_status', 'paid')
                    ->count();

                $pendingInvoices = \App\Models\Invoice::whereHas('application', $baseInvoiceQuery)
                    ->activeForTotals()
                    ->whereIn('payment_status', ['pending', 'partial', 'overdue'])
                    ->count();

                $totalReceived = \App\Models\Invoice::whereHas('application', $baseInvoiceQuery)
                    ->activeForTotals()
                    ->where('payment_status', 'paid')
                    ->sum('total_amount');

                $totalPending = \App\Models\Invoice::whereHas('application', $baseInvoiceQuery)
                    ->activeForTotals()
                    ->whereIn('payment_status', ['pending', 'partial', 'overdue'])
                    ->sum('balance_amount');

                $paymentStats = [
                    'total_generated' => $totalInvoices,
                    'total_received' => $totalReceived,
                    'total_pending' => $totalPending,
                    'paid_count' => $paidInvoices,
                    'pending_count' => $pendingInvoices,
                ];

                // Current month invoice statistics
                $currentMonthStart = now()->startOfMonth();
                $currentMonthEnd = now()->endOfMonth();

                $currentMonthGenerated = \App\Models\Invoice::whereHas('application', $baseInvoiceQuery)
                    ->activeForTotals()
                    ->whereBetween('invoice_date', [$currentMonthStart, $currentMonthEnd])
                    ->count();

                $currentMonthReceived = \App\Models\Invoice::whereHas('application', $baseInvoiceQuery)
                    ->activeForTotals()
                    ->whereBetween('invoice_date', [$currentMonthStart, $currentMonthEnd])
                    ->where('payment_status', 'paid')
                    ->sum('total_amount');

                $currentMonthPending = \App\Models\Invoice::whereHas('application', $baseInvoiceQuery)
                    ->activeForTotals()
                    ->whereBetween('invoice_date', [$currentMonthStart, $currentMonthEnd])
                    ->whereIn('payment_status', ['pending', 'partial', 'overdue'])
                    ->sum('balance_amount');

                $currentMonthStats = [
                    'generated_count' => $currentMonthGenerated,
                    'received_amount' => $currentMonthReceived ?? 0,
                    'pending_amount' => $currentMonthPending ?? 0,
                ];
            }

            // Get all live members for GST verification modal (not paginated)
            $allLiveMembers = Application::whereNotNull('membership_id')
                ->where('application_type', 'IRINN')
                ->where('service_status', 'live')
                ->with('user')
                ->orderBy('created_at', 'desc')
                ->get();

            $zones = collect();
            $nodalOfficers = collect();

            return view('admin.members.index', compact('members', 'admin', 'filter', 'paymentStats', 'currentMonthStats', 'paymentFilter', 'showBillingPaymentSummary', 'showExportReports', 'allLiveMembers', 'gstVerificationFilter', 'zones', 'nodalOfficers'));
        } catch (Exception $e) {
            Log::error('Error loading members: '.$e->getMessage());

            return redirect()->route('admin.dashboard')
                ->with('error', 'Unable to load members. Please try again.');
        }
    }

    /**
     * Export members to Excel with invoice details.
     */
    public function exportMembersToExcel(Request $request)
    {
        // Increase execution time limit for large exports
        set_time_limit(300); // 5 minutes
        ini_set('max_execution_time', 300);

        // Prevent any output before CSV
        if (ob_get_level()) {
            ob_end_clean();
        }

        try {
            $admin = $this->getCurrentAdmin();
            $filter = $request->get('filter', 'all');
            $paymentFilter = $request->get('payment_filter');
            $search = $request->get('search');
            $userId = $request->get('user_id');
            $invoiceStatus = $request->get('invoice_status', 'all');

            // Get additional filters
            $zone = $request->get('zone');
            $nodalOfficer = $request->get('nodal_officer');
            $gstVerificationFilter = $request->get('gst_verification_filter');
            $dateFrom = $request->get('date_from');
            $dateTo = $request->get('date_to');

            // Query applications directly — IRINN members
            $query = Application::whereNotNull('membership_id')
                ->where('application_type', 'IRINN');

            // Apply filter
            if (in_array($filter, ['active', 'live'], true)) {
                $query->where('service_status', 'live');
            } elseif (in_array($filter, ['suspended', 'disconnected'], true)) {
                $query->where('service_status', $filter);
            }

            $showBillingPaymentSummary = $this->hasRole($admin, 'billing');
            if ($showBillingPaymentSummary && $paymentFilter) {
                if ($paymentFilter === 'generated') {
                    $query->where('service_status', 'live')
                        ->whereHas('invoices');
                } elseif ($paymentFilter === 'received') {
                    $query->where('service_status', 'live')
                        ->whereHas('invoices', function ($invoiceQuery) {
                            $invoiceQuery->where('payment_status', 'paid');
                        });
                } elseif ($paymentFilter === 'pending') {
                    $query->where('service_status', 'live')
                        ->whereHas('invoices', function ($invoiceQuery) {
                            $invoiceQuery->whereIn('payment_status', ['pending', 'partial', 'overdue']);
                        });
                }
            }

            // GST verification filter
            if ($gstVerificationFilter && $gstVerificationFilter !== 'all') {
                if ($gstVerificationFilter === 'verified') {
                    $query->whereHas('gstVerification', function ($gstQuery) {
                        $gstQuery->where('is_verified', true);
                    });
                } elseif ($gstVerificationFilter === 'unverified') {
                    $query->where(function ($subQuery) {
                        $subQuery->whereDoesntHave('gstVerification')
                            ->orWhereHas('gstVerification', function ($gstQuery) {
                                $gstQuery->where('is_verified', false);
                            });
                    });
                }
            }

            // Search functionality - search in user data
            if ($search) {
                $query->whereHas('user', function ($q) use ($search) {
                    $q->where('fullname', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('registrationid', 'like', "%{$search}%")
                        ->orWhere('pancardno', 'like', "%{$search}%");
                });
            }

            // Filter by user_id if provided
            if ($userId) {
                $query->whereHas('user', function ($q) use ($userId) {
                    $q->where('id', $userId)
                        ->orWhere('registrationid', 'like', "%{$userId}%");
                });
            }

            // Get applications with user and invoices
            // Always load all invoices for export (invoiceStatus filter is for display only)
            // But if payment filter is applied, we need to ensure we still get invoices
            // IMPORTANT: For exports, always load ALL invoices regardless of invoiceStatus filter
            // The invoiceStatus filter is only for UI display, not for export
            // Payment filters are already applied at application level with whereHas
            $applications = $query->with([
                'user',
                'invoices' => function ($q) {
                    // Load all invoices without any status filter for export
                    $q->orderBy('created_at', 'desc');
                },
            ])->orderBy('created_at', 'desc')->get();

            // Log query results for debugging
            Log::info('Export Query Results', [
                'applications_count' => $applications->count(),
                'filter' => $filter,
                'payment_filter' => $paymentFilter,
                'invoice_status' => $invoiceStatus,
                'search' => $search,
                'zone' => $zone,
                'nodal_officer' => $nodalOfficer,
                'gst_verification_filter' => $gstVerificationFilter,
                'user_id' => $userId,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ]);

            // Prepare export data
            $exportData = [];
            $exportData[] = [
                'REQUEST_ID',
                'ACCOUNT_NAME',
                'ORGANIZATION_NAME',
                'GSTIN',
                'STATE_DESC',
                'LEDGER_NAME',
                'VOUCHER_TYPE_NAME',
                'INVOICE_NUMBER',
                'INVOICE_DATE',
                'BILLING_START_DATE',
                'BILLING_END_DATE',
                'BASE_AMOUNT',
                'REBATE_AMOUNT',
                'TAXABLE_AMOUNT',
                'IGST_AMOUNT',
                'CGST_AMOUNT',
                'SGST_AMOUNT',
                'INVOICE_AMOUNT',
                'NARRATION',
                'Payment Status',
            ];

            $totalApplications = $applications->count();
            $processedInvoices = 0;

            foreach ($applications as $application) {
                $user = $application->user;
                if (! $user) {
                    // Skip applications without user
                    continue;
                }

                // Get invoices - ensure they're loaded (should already be loaded via eager loading)
                $invoices = $application->invoices;
                if (! $invoices) {
                    // Try to reload invoices if not loaded (load all invoices without filters)
                    $application->load(['invoices' => function ($q) {
                        $q->orderBy('created_at', 'desc');
                    }]);
                    $invoices = $application->invoices;
                }

                if (! $invoices || $invoices->isEmpty()) {
                    // Application without invoices - skip (only export invoices)
                    // Log this for debugging when filters are applied
                    if ($filter !== 'all' || $paymentFilter || $search || $userId) {
                        Log::debug('Skipping application without invoices (filtered)', [
                            'application_id' => $application->id,
                            'membership_id' => $application->membership_id,
                            'filter' => $filter,
                            'payment_filter' => $paymentFilter,
                        ]);
                    }

                    continue;
                }

                $processedInvoices += $invoices->count();

                // Get GSTIN and state from application or GST verification
                $gstin = '0';
                $stateDesc = 'N/A';
                $gstVerification = null;

                // Get GSTIN from application data
                $applicationData = is_array($application->application_data) ? $application->application_data : [];
                $applicationGstin = $applicationData['gstin'] ?? null;
                if ($applicationGstin) {
                    $gstin = strtoupper($applicationGstin);
                    // Get GST verification for this GSTIN
                    $gstVerification = GstVerification::where('user_id', $user->id)
                        ->where('gstin', $gstin)
                        ->where('is_verified', true)
                        ->latest()
                        ->first();
                }

                // If no GST verification found, try to get from application's gst_verification_id
                if (! $gstVerification && $application->gst_verification_id) {
                    $gstVerification = GstVerification::find($application->gst_verification_id);
                    if ($gstVerification && $gstVerification->gstin) {
                        $gstin = strtoupper($gstVerification->gstin);
                    }
                }

                // Get state from GST verification
                if ($gstVerification && $gstVerification->state) {
                    $stateDesc = $gstVerification->state;
                }

                // Get buyer state code from GSTIN (same logic as invoice generation)
                $buyerStateCode = $this->extractStateCodeFromGstin($gstin);

                // Get supplier state code based on seller_state_code if set, otherwise buyer state (same logic as invoice generation)
                // This uses getNixiLocationCredentials to get the correct supplier state code
                $sellerStateCode = $application->seller_state_code ?? $buyerStateCode;
                $supplierStateCode = '07'; // Default to Delhi
                if (! empty($sellerStateCode)) {
                    $nixiCredentials = $this->getNixiLocationCredentials($sellerStateCode);
                    $supplierStateCode = $nixiCredentials['supplier_state_code'] ?? '07';
                }

                $isSameState = ($supplierStateCode === $buyerStateCode);

                // Get user name (account name and organization name are same)
                $accountName = $user->fullname ?? 'N/A';
                $organizationName = $accountName;

                // Apply invoice status filter if provided
                $filteredInvoices = $invoices;
                if ($invoiceStatus !== 'all') {
                    if ($invoiceStatus === 'paid') {
                        $filteredInvoices = $invoices->where('payment_status', 'paid');
                    } elseif ($invoiceStatus === 'unpaid') {
                        $filteredInvoices = $invoices->whereIn('payment_status', ['pending', 'partial', 'overdue']);
                    }
                }

                // Apply date filters if provided
                if ($dateFrom) {
                    $filteredInvoices = $filteredInvoices->filter(function ($inv) use ($dateFrom) {
                        return $inv->invoice_date && $inv->invoice_date >= $dateFrom;
                    });
                }
                if ($dateTo) {
                    $filteredInvoices = $filteredInvoices->filter(function ($inv) use ($dateTo) {
                        return $inv->invoice_date && $inv->invoice_date <= $dateTo;
                    });
                }

                // Application with invoices - one row per invoice
                foreach ($filteredInvoices as $invoice) {
                    // Get base amount - try base_amount first, then amount field
                    $baseAmount = (float) ($invoice->base_amount ?? $invoice->amount ?? 0);

                    // Get rebate amount (carry forward amount)
                    $rebateAmount = (float) ($invoice->carry_forward_amount ?? 0);

                    // Calculate taxable amount (base amount - rebate)
                    $taxableAmount = $baseAmount - $rebateAmount;

                    // Calculate IGST/CGST/SGST based on state (same logic as invoice PDF)
                    $igstAmount = 0;
                    $cgstAmount = 0;
                    $sgstAmount = 0;

                    if ($isSameState) {
                        // Same state: CGST + SGST (9% each)
                        $cgstAmount = round(($taxableAmount * 9.0) / 100, 2);
                        $sgstAmount = round(($taxableAmount * 9.0) / 100, 2);
                    } else {
                        // Different state: IGST (18%)
                        $igstAmount = round(($taxableAmount * 18.0) / 100, 2);
                    }

                    // Get narration from line_items (particulars)
                    $narration = '';
                    $lineItems = $invoice->line_items ?? [];
                    $particulars = [];
                    foreach ($lineItems as $item) {
                        if (! is_array($item) || isset($item['is_carry_forward']) || isset($item['is_adjustment']) || $item === '_metadata') {
                            continue;
                        }
                        $description = $item['description'] ?? '';
                        if (! empty($description)) {
                            $particulars[] = $description;
                        }
                    }
                    $narration = ! empty($particulars) ? implode(', ', $particulars) : 'Period, Capacity';

                    // Format invoice date as DD-MMM-YYYY
                    $invoiceDate = 'N/A';
                    if ($invoice->invoice_date) {
                        $invoiceDate = date('d-M-Y', strtotime($invoice->invoice_date));
                    }

                    // Format billing start date as DD-MMM-YYYY
                    $billingStartDate = 'N/A';
                    if ($invoice->billing_start_date) {
                        $billingStartDate = date('d-M-Y', strtotime($invoice->billing_start_date));
                    }

                    // Format billing end date as DD-MMM-YYYY
                    $billingEndDate = 'N/A';
                    if ($invoice->billing_end_date) {
                        $billingEndDate = date('d-M-Y', strtotime($invoice->billing_end_date));
                    }

                    // Get payment status
                    $paymentStatus = strtolower($invoice->payment_status ?? 'pending');
                    $paymentStatusText = ($paymentStatus === 'paid') ? 'Paid' : 'Pending';

                    $exportData[] = [
                        $invoice->id, // REQUEST_ID (Invoice ID)
                        $accountName, // ACCOUNT_NAME
                        $organizationName, // ORGANIZATION_NAME
                        $gstin, // GSTIN
                        $stateDesc, // STATE_DESC
                        'Port charges', // LEDGER_NAME (fixed)
                        'SALES - IX', // VOUCHER_TYPE_NAME (fixed)
                        $invoice->invoice_number ?? 'N/A', // INVOICE_NUMBER
                        $invoiceDate, // INVOICE_DATE (DD-MMM-YYYY format)
                        $billingStartDate, // BILLING_START_DATE (DD-MMM-YYYY format)
                        $billingEndDate, // BILLING_END_DATE (DD-MMM-YYYY format)
                        number_format($baseAmount, 2), // BASE_AMOUNT
                        number_format($rebateAmount, 2), // REBATE_AMOUNT
                        number_format($taxableAmount, 2), // TAXABLE_AMOUNT
                        number_format($igstAmount, 2), // IGST_AMOUNT
                        number_format($cgstAmount, 2), // CGST_AMOUNT
                        number_format($sgstAmount, 2), // SGST_AMOUNT
                        number_format($invoice->total_amount ?? 0, 2), // INVOICE_AMOUNT
                        $narration, // NARRATION
                        $paymentStatusText, // Payment Status
                    ];
                }
            }

            // Log export statistics for debugging
            Log::info('Excel Export Statistics', [
                'total_applications' => $applications->count(),
                'export_rows' => count($exportData) - 1, // Subtract header row
            ]);

            // Generate CSV
            $filename = 'members_export_'.date('Y-m-d_His').'.csv';
            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                'Pragma' => 'public',
            ];

            $callback = function () use ($exportData) {
                $file = fopen('php://output', 'w');
                // Add BOM for UTF-8 Excel compatibility
                fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
                foreach ($exportData as $row) {
                    fputcsv($file, $row);
                }
                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        } catch (Exception $e) {
            Log::error('Error exporting members: '.$e->getMessage());

            // Return CSV with error message instead of HTML redirect
            $filename = 'members_export_error_'.date('Y-m-d_His').'.csv';
            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ];

            $errorData = [
                ['Error'],
                ['An error occurred while exporting members.'],
                ['Error Message: '.$e->getMessage()],
                ['Please contact support or try again later.'],
            ];

            $callback = function () use ($errorData) {
                $file = fopen('php://output', 'w');
                fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
                foreach ($errorData as $row) {
                    fputcsv($file, $row);
                }
                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        }
    }

    /**
     * Export invoice amounts for all live members.
     */

    /**
     * Export GST verification report for selected live members.
     */
    public function exportGstVerificationReport(Request $request)
    {
        // Increase execution time limit for large exports
        set_time_limit(900); // 15 minutes (GST verification takes time for 170+ verifications)
        ini_set('max_execution_time', 900);

        try {
            $admin = $this->getCurrentAdmin();

            // Get selected member IDs from request
            $selectedMemberIds = $request->input('member_ids', []);

            // Build query for live members
            $query = Registration::whereHas('applications', function ($query) {
                $query->whereNotNull('membership_id')
                    ->where('application_type', 'IRINN')
                    ->where('is_active', true);
            });

            // Filter by selected member IDs if provided
            if (! empty($selectedMemberIds) && is_array($selectedMemberIds)) {
                $query->whereIn('id', $selectedMemberIds);
            }

            $members = $query->with([
                'applications' => function ($q) {
                    $q->whereNotNull('membership_id')
                        ->where('application_type', 'IRINN')
                        ->where('is_active', true)
                        ->with('gstVerification');
                },
            ])->distinct()->orderBy('created_at', 'desc')->get();

            // Initialize IDfy service
            $idfyService = new \App\Services\IdfyVerificationService;

            // Prepare data for export
            $exportData = [];
            $exportData[] = [
                'Membership ID',
                'Registered User Name',
                'Email',
                'Mobile',
                'GST Number',
                'GST Verification Status',
                'GSTIN Status',
                'Legal Name',
                'Trade Name',
                'State',
                'Registration Date',
                'GST Type',
                'Company Status',
                'Constitution of Business',
                'Error Message',
            ];

            foreach ($members as $member) {
                $applications = $member->applications->where('application_type', 'IRINN')
                    ->whereNotNull('membership_id')
                    ->where('is_active', true);

                foreach ($applications as $application) {
                    // Get GST number from application data or gstVerification
                    $gstin = null;
                    $gstVerification = $application->gstVerification;

                    if ($gstVerification && $gstVerification->gstin) {
                        $gstin = $gstVerification->gstin;
                    } else {
                        $applicationData = $application->application_data ?? [];
                        $gstin = $applicationData['gstin'] ?? $applicationData['gst_number'] ?? null;
                    }

                    if (! $gstin) {
                        // No GST number found
                        $exportData[] = [
                            $application->membership_id ?? 'N/A',
                            $member->fullname ?? 'N/A',
                            $member->email ?? 'N/A',
                            $member->mobile ?? 'N/A',
                            'N/A',
                            'GST Number Not Found',
                            'N/A',
                            'N/A',
                            'N/A',
                            'N/A',
                            'N/A',
                            'N/A',
                            'N/A',
                            'N/A',
                            'N/A',
                        ];

                        continue;
                    }

                    // Check if GST is already verified in database with complete details
                    $gstStatus = 'Not Verified';
                    $gstinStatus = 'N/A';
                    $legalName = 'N/A';
                    $tradeName = 'N/A';
                    $state = 'N/A';
                    $registrationDate = 'N/A';
                    $gstType = 'N/A';
                    $companyStatus = 'N/A';
                    $constitutionOfBusiness = 'N/A';
                    $errorMessage = 'N/A';

                    // Check if we have existing verified GST record with complete details
                    $hasCompleteDetails = false;
                    if ($gstVerification && $gstVerification->is_verified && $gstVerification->status === 'completed') {
                        // Check if we have all required details
                        $hasCompleteDetails = ! empty($gstVerification->legal_name)
                            && ! empty($gstVerification->trade_name)
                            && ! empty($gstVerification->state);

                        if ($hasCompleteDetails) {
                            // Use existing verified data from database
                            $gstStatus = 'Verified - Exists';
                            $gstinStatus = $gstVerification->company_status ?? 'Active';
                            $legalName = $gstVerification->legal_name ?? 'N/A';
                            $tradeName = $gstVerification->trade_name ?? 'N/A';
                            $state = $gstVerification->state ?? 'N/A';
                            $registrationDate = $gstVerification->registration_date ? $gstVerification->registration_date->format('Y-m-d') : 'N/A';
                            $gstType = $gstVerification->gst_type ?? 'N/A';
                            $companyStatus = $gstVerification->company_status ?? 'Active';
                            $constitutionOfBusiness = $gstVerification->constitution_of_business ?? 'N/A';
                        } else {
                            // Details are missing - try to extract from verification_data first
                            if ($gstVerification->verification_data) {
                                $verificationData = is_string($gstVerification->verification_data)
                                    ? json_decode($gstVerification->verification_data, true)
                                    : $gstVerification->verification_data;

                                if (isset($verificationData['result']['source_output'])) {
                                    $sourceOutput = $verificationData['result']['source_output'];
                                    $gstStatus = 'Verified - Exists';
                                    $gstinStatus = $sourceOutput['gstin_status'] ?? 'Active';
                                    $legalName = $sourceOutput['legal_name'] ?? 'N/A';
                                    $tradeName = $sourceOutput['trade_name'] ?? 'N/A';

                                    $address = $sourceOutput['principal_place_of_business_fields']['principal_place_of_business_address'] ?? null;
                                    if ($address && isset($address['state_name'])) {
                                        $state = $address['state_name'];
                                    } else {
                                        $state = 'N/A';
                                    }

                                    if (isset($sourceOutput['date_of_registration'])) {
                                        try {
                                            $registrationDate = date('Y-m-d', strtotime($sourceOutput['date_of_registration']));
                                        } catch (\Exception $e) {
                                            $registrationDate = 'N/A';
                                        }
                                    }

                                    $gstType = $sourceOutput['taxpayer_type'] ?? 'N/A';
                                    $companyStatus = $sourceOutput['gstin_status'] ?? 'Active';
                                    $constitutionOfBusiness = $sourceOutput['constitution_of_business'] ?? 'N/A';

                                    // Update database with extracted data
                                    $gstVerification->update([
                                        'legal_name' => $legalName !== 'N/A' ? $legalName : null,
                                        'trade_name' => $tradeName !== 'N/A' ? $tradeName : null,
                                        'state' => $state !== 'N/A' ? $state : null,
                                        'registration_date' => $registrationDate !== 'N/A' ? $registrationDate : null,
                                        'gst_type' => $gstType !== 'N/A' ? $gstType : null,
                                        'company_status' => $companyStatus !== 'N/A' ? $companyStatus : null,
                                        'constitution_of_business' => $constitutionOfBusiness !== 'N/A' ? $constitutionOfBusiness : null,
                                    ]);

                                    $hasCompleteDetails = ($legalName !== 'N/A' && $tradeName !== 'N/A' && $state !== 'N/A');

                                    // Log extraction from verification_data
                                    Log::info("Extracted GST details from verification_data for {$gstin}: Legal={$legalName}, Trade={$tradeName}, State={$state}, Complete={$hasCompleteDetails}");
                                } else {
                                    // verification_data doesn't have source_output, need to call API
                                    $hasCompleteDetails = false;
                                }
                            } else {
                                // No verification_data, need to call API
                                $hasCompleteDetails = false;
                            }
                        }
                    }

                    // If we don't have complete details, call API to get fresh data
                    if (! $hasCompleteDetails) {
                        // Log that we're calling API
                        Log::info("Calling IDfy API for GST verification: {$gstin} (Application: {$application->id}, Member: {$member->id})");

                        // Verify GST with IDfy API (same flow as KYC form)
                        try {
                            // Initiate verification
                            $verifyResult = $idfyService->verifyGst($gstin);
                            $requestId = $verifyResult['request_id'];

                            // Create or update verification record
                            if (! $gstVerification) {
                                $gstVerification = \App\Models\GstVerification::create([
                                    'user_id' => $member->id,
                                    'gstin' => $gstin,
                                    'request_id' => $requestId,
                                    'status' => 'in_progress',
                                    'is_verified' => false,
                                ]);
                            } else {
                                $gstVerification->update([
                                    'request_id' => $requestId,
                                    'status' => 'in_progress',
                                    'is_verified' => false,
                                ]);
                            }

                            // Wait a bit for verification to process
                            sleep(3);

                            // Check status (with fewer retries to avoid timeout)
                            $maxRetries = 5;
                            $retryCount = 0;
                            $statusResult = null;

                            while ($retryCount < $maxRetries) {
                                $statusResult = $idfyService->getTaskStatus($requestId);
                                $status = $statusResult['status'] ?? 'unknown';

                                if ($status === 'completed') {
                                    $result = $statusResult['result'] ?? null;
                                    $sourceOutput = $result['source_output'] ?? null;

                                    if ($sourceOutput) {
                                        $isVerified = ($sourceOutput['status'] ?? '') === 'id_found';

                                        if ($isVerified) {
                                            $gstStatus = 'Verified - Exists';

                                            // Extract all fields exactly as KYC form does
                                            $legalName = $sourceOutput['legal_name'] ?? null;
                                            $tradeName = $sourceOutput['trade_name'] ?? null;

                                            // Extract PAN from GSTIN (first 10 characters after first 2)
                                            $pan = null;
                                            $gstinFromResponse = $sourceOutput['gstin'] ?? $gstin;
                                            if ($gstinFromResponse && strlen($gstinFromResponse) >= 10) {
                                                $pan = substr($gstinFromResponse, 2, 10);
                                            }

                                            // Extract state from address (exactly as KYC form)
                                            $state = null;
                                            $primaryAddress = null;
                                            $address = $sourceOutput['principal_place_of_business_fields']['principal_place_of_business_address'] ?? null;
                                            if ($address) {
                                                $state = $address['state_name'] ?? null;
                                                // Build primary address
                                                $addressParts = array_filter([
                                                    $address['door_number'] ?? null,
                                                    $address['building_name'] ?? null,
                                                    $address['street'] ?? null,
                                                    $address['location'] ?? null,
                                                    $address['city'] ?? null,
                                                    $address['dst'] ?? null,
                                                ]);
                                                $primaryAddress = implode(', ', $addressParts);
                                            }

                                            // Extract registration date (exactly as KYC form - using date() not Carbon)
                                            $registrationDate = null;
                                            if (isset($sourceOutput['date_of_registration'])) {
                                                $registrationDate = date('Y-m-d', strtotime($sourceOutput['date_of_registration']));
                                            }

                                            $gstType = $sourceOutput['taxpayer_type'] ?? null;
                                            $companyStatus = $sourceOutput['gstin_status'] ?? null; // Use gstin_status directly
                                            $gstinStatus = $sourceOutput['gstin_status'] ?? 'Active';
                                            $constitutionOfBusiness = $sourceOutput['constitution_of_business'] ?? null;

                                            // Update verification record with all extracted data (exactly as KYC form)
                                            $updateData = [
                                                'status' => 'completed',
                                                'is_verified' => true,
                                                'verification_data' => $result,
                                                'legal_name' => $legalName,
                                                'trade_name' => $tradeName,
                                                'state' => $state,
                                                'registration_date' => $registrationDate,
                                                'gst_type' => $gstType,
                                                'company_status' => $companyStatus,
                                                'constitution_of_business' => $constitutionOfBusiness,
                                            ];

                                            if ($pan) {
                                                $updateData['pan'] = $pan;
                                            }

                                            if ($primaryAddress) {
                                                $updateData['primary_address'] = $primaryAddress;
                                            }

                                            $gstVerification->update($updateData);

                                            // Update local variables for export (use extracted values or 'N/A')
                                            $gstinStatus = $gstinStatus ?? 'Active';
                                            $legalName = $legalName ?? 'N/A';
                                            $tradeName = $tradeName ?? 'N/A';
                                            $state = $state ?? 'N/A';
                                            $registrationDate = $registrationDate ?? 'N/A';
                                            $gstType = $gstType ?? 'N/A';
                                            $companyStatus = $companyStatus ?? 'N/A';
                                            $constitutionOfBusiness = $constitutionOfBusiness ?? 'N/A';

                                            // Log successful extraction with full API response
                                            Log::info("GST verification completed for {$gstin}", [
                                                'gstin' => $gstin,
                                                'legal_name' => $legalName,
                                                'trade_name' => $tradeName,
                                                'state' => $state,
                                                'gst_type' => $gstType,
                                                'company_status' => $companyStatus,
                                                'registration_date' => $registrationDate,
                                                'constitution_of_business' => $constitutionOfBusiness,
                                                'full_api_response' => $result, // Full API response from Idfy
                                                'source_output' => $sourceOutput, // Extracted source output
                                                'application_id' => $application->id,
                                                'member_id' => $member->id,
                                            ]);
                                        } else {
                                            $gstStatus = 'Not Verified - Does Not Exist';
                                            $errorMessage = $sourceOutput['message'] ?? 'GSTIN verification failed';

                                            $gstVerification->update([
                                                'status' => 'completed',
                                                'is_verified' => false,
                                                'verification_data' => $result,
                                                'error_message' => $errorMessage,
                                            ]);

                                            // Log failed verification with full response
                                            Log::info("GST verification failed for {$gstin}", [
                                                'gstin' => $gstin,
                                                'status' => 'not_verified',
                                                'error_message' => $errorMessage,
                                                'full_api_response' => $result,
                                                'source_output' => $sourceOutput,
                                                'application_id' => $application->id,
                                                'member_id' => $member->id,
                                            ]);
                                        }
                                    }
                                    break;
                                } elseif ($status === 'failed') {
                                    $gstStatus = 'Verification Failed';
                                    $errorMessage = 'GST verification request failed';

                                    $gstVerification->update([
                                        'status' => 'failed',
                                        'is_verified' => false,
                                        'error_message' => $errorMessage,
                                        'verification_data' => $statusResult,
                                    ]);

                                    // Log failed verification with full response
                                    Log::info("GST verification request failed for {$gstin}", [
                                        'gstin' => $gstin,
                                        'status' => 'failed',
                                        'error_message' => $errorMessage,
                                        'full_api_response' => $statusResult,
                                        'application_id' => $application->id,
                                        'member_id' => $member->id,
                                    ]);
                                    break;
                                }

                                // Wait before retrying (reduced wait time)
                                sleep(1);
                                $retryCount++;
                            }

                            if ($retryCount >= $maxRetries && $statusResult && ($statusResult['status'] ?? '') !== 'completed') {
                                $gstStatus = 'Verification In Progress';
                                $errorMessage = 'GST verification is still processing. Please check again later.';
                            }
                        } catch (Exception $e) {
                            // If verification fails, log and continue
                            Log::error('Error verifying GST for application '.$application->id.': '.$e->getMessage());
                            $gstStatus = 'Verification Error';
                            $errorMessage = $e->getMessage();
                        }
                    }

                    // Ensure all values are strings (not null) for CSV export
                    // Make sure all variables are set (convert null to 'N/A')
                    $exportRow = [
                        (string) ($application->membership_id ?? 'N/A'),
                        (string) ($member->fullname ?? 'N/A'),
                        (string) ($member->email ?? 'N/A'),
                        (string) ($member->mobile ?? 'N/A'),
                        (string) ($gstin ?? 'N/A'),
                        (string) ($gstStatus ?? 'N/A'),
                        (string) ($gstinStatus ?? 'N/A'),
                        (string) ($legalName ?? 'N/A'),
                        (string) ($tradeName ?? 'N/A'),
                        (string) ($state ?? 'N/A'),
                        (string) ($registrationDate ?? 'N/A'),
                        (string) ($gstType ?? 'N/A'),
                        (string) ($companyStatus ?? 'N/A'),
                        (string) ($constitutionOfBusiness ?? 'N/A'),
                        (string) ($errorMessage ?? 'N/A'),
                    ];

                    // Save GST details to application's kyc_details column if GST is verified
                    if ($gstVerification && $gstVerification->is_verified && $gstStatus !== 'Not Verified' && $gstStatus !== 'GST Number Not Found') {
                        try {
                            // Get billing address from GST verification
                            $billingAddressJson = null;
                            if ($gstVerification->verification_data) {
                                $verificationData = is_string($gstVerification->verification_data)
                                    ? json_decode($gstVerification->verification_data, true)
                                    : $gstVerification->verification_data;

                                // Handle both structures: with 'result' wrapper or direct 'source_output'
                                $sourceOutput = null;
                                if (isset($verificationData['result']['source_output'])) {
                                    $sourceOutput = $verificationData['result']['source_output'];
                                } elseif (isset($verificationData['source_output'])) {
                                    $sourceOutput = $verificationData['source_output'];
                                }
                                if ($sourceOutput) {
                                    $address = $sourceOutput['principal_place_of_business_fields']['principal_place_of_business_address'] ?? null;
                                    if ($address) {
                                        // Build address string
                                        $addressParts = array_filter([
                                            $address['door_number'] ?? '',
                                            $address['building_name'] ?? '',
                                            $address['street'] ?? '',
                                            $address['location'] ?? '',
                                            $address['city'] ?? '',
                                            $address['dst'] ?? '',
                                            $address['state_name'] ?? '',
                                            $address['pincode'] ?? '',
                                        ]);
                                        $addressString = implode(', ', $addressParts);

                                        // Format billing address as specified
                                        $billingAddressJson = json_encode([
                                            'source' => 'gstin',
                                            'label' => 'GSTIN - '.$gstin,
                                            'address' => $addressString,
                                        ]);
                                    }
                                }
                            }

                            // Fallback to primary_address if available
                            if (! $billingAddressJson && $gstVerification->primary_address) {
                                $billingAddressJson = json_encode([
                                    'source' => 'gstin',
                                    'label' => 'GSTIN - '.$gstin,
                                    'address' => $gstVerification->primary_address,
                                ]);
                            }

                            // Prepare kyc_details data in the specified format
                            $kycDetails = [
                                'is_msme' => false,
                                'gstin' => $gstin,
                                'gst_verified' => true,
                                'udyam_number' => null,
                                'udyam_verified' => false,
                                'cin' => null,
                                'mca_verified' => false,
                                'contact_name' => null,
                                'contact_dob' => null,
                                'contact_pan' => null,
                                'contact_email' => null,
                                'contact_mobile' => null,
                                'contact_name_pan_dob_verified' => false,
                                'contact_email_verified' => false,
                                'contact_mobile_verified' => false,
                                'billing_address' => $billingAddressJson,
                                'status' => 'completed',
                                'completed_at' => now()->format('Y-m-d H:i:s'),
                            ];

                            // Update application's kyc_details
                            $application->update([
                                'kyc_details' => $kycDetails,
                            ]);

                            Log::info('Saved GST details to application kyc_details', [
                                'application_id' => $application->id,
                                'gstin' => $gstin,
                            ]);
                        } catch (Exception $e) {
                            Log::error('Error saving GST details to application kyc_details: '.$e->getMessage(), [
                                'application_id' => $application->id,
                                'gstin' => $gstin,
                            ]);
                        }
                    }

                    // Log the row being exported for debugging (only first few to avoid log spam)
                    if (count($exportData) < 3) {
                        Log::info("Exporting GST row for {$gstin}: Legal={$exportRow[7]}, Trade={$exportRow[8]}, State={$exportRow[9]}, Type={$exportRow[11]}");
                    }

                    $exportData[] = $exportRow;
                }
            }

            // Generate CSV file (Excel compatible)
            $filename = 'gst_verification_report_'.now()->format('Y-m-d_His').'.csv';
            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                'Pragma' => 'public',
                'Expires' => '0',
            ];

            $callback = function () use ($exportData) {
                $file = fopen('php://output', 'w');
                // Add BOM for UTF-8 to ensure Excel opens it correctly
                fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

                foreach ($exportData as $row) {
                    fputcsv($file, $row);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        } catch (Exception $e) {
            Log::error('Error exporting GST verification report: '.$e->getMessage());

            return back()->with('error', 'Unable to export GST verification report. Please try again.');
        }
    }

    /**
     * Show page to update missing kyc_details for applications.
     */
    public function updateKycDetails(Request $request)
    {
        try {
            $admin = $this->getCurrentAdmin();

            // Get applications with missing or incomplete kyc_details
            $query = Application::where('application_type', 'IRINN')
                ->where(function ($q) {
                    $q->whereNull('kyc_details')
                        ->orWhereJsonDoesntContain('kyc_details', 'gstin')
                        ->orWhereJsonDoesntContain('kyc_details', 'status');
                })
                ->with(['user', 'gstVerification'])
                ->orderBy('created_at', 'desc');

            // Filter by specific application_id if provided
            if ($request->has('application_id') && $request->application_id) {
                $query->where('id', $request->application_id);
            }

            // Filter by search
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('application_id', 'like', "%{$search}%")
                        ->orWhere('membership_id', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('fullname', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->orWhere('registrationid', 'like', "%{$search}%");
                        });
                });
            }

            $applications = $query->paginate(50);

            return view('admin.applications.update-kyc-details', [
                'applications' => $applications,
                'admin' => $admin,
            ]);
        } catch (Exception $e) {
            Log::error('Error loading update kyc details page: '.$e->getMessage());

            return back()->with('error', 'Unable to load page. Please try again.');
        }
    }

    /**
     * Update kyc_details for selected applications by fetching from GST API.
     */
    public function processUpdateKycDetails(Request $request)
    {
        // Increase execution time limit
        set_time_limit(900);
        ini_set('max_execution_time', 900);

        try {
            $admin = $this->getCurrentAdmin();

            $request->validate([
                'application_ids' => 'required|array|min:1',
                'application_ids.*' => 'required|integer|exists:applications,id',
            ]);

            // Log received data for debugging
            Log::info('Processing update kyc details', [
                'application_ids' => $request->input('application_ids'),
                'count' => count($request->input('application_ids', [])),
            ]);

            $applicationIds = $request->input('application_ids');
            $applications = Application::whereIn('id', $applicationIds)
                ->with(['user', 'gstVerification'])
                ->get();

            $idfyService = new \App\Services\IdfyVerificationService;
            $updated = 0;
            $failed = 0;
            $errors = [];

            foreach ($applications as $application) {
                try {
                    // Get GSTIN from application data
                    $applicationData = $application->application_data ?? [];
                    $gstin = strtoupper($applicationData['gstin'] ?? '');

                    if (empty($gstin) || strlen($gstin) !== 15) {
                        $errors[] = "Application {$application->application_id}: GSTIN not found or invalid";
                        $failed++;

                        continue;
                    }

                    // Check if we have existing verified GST record
                    $gstVerification = $application->gstVerification;
                    $hasCompleteDetails = false;

                    if ($gstVerification && $gstVerification->is_verified && $gstVerification->status === 'completed') {
                        $verificationData = is_string($gstVerification->verification_data)
                            ? json_decode($gstVerification->verification_data, true)
                            : $gstVerification->verification_data;

                        if ($verificationData && isset($verificationData['result']['source_output'])) {
                            $hasCompleteDetails = true;
                        }
                    }

                    // If we don't have complete details, call API
                    if (! $hasCompleteDetails) {
                        Log::info("Calling IDfy API for GST verification: {$gstin} (Application: {$application->id})");

                        // Initiate verification
                        $verifyResult = $idfyService->verifyGst($gstin);
                        $requestId = $verifyResult['request_id'];

                        // Create or update verification record
                        if (! $gstVerification) {
                            $gstVerification = GstVerification::create([
                                'user_id' => $application->user_id,
                                'gstin' => $gstin,
                                'request_id' => $requestId,
                                'status' => 'in_progress',
                                'is_verified' => false,
                            ]);
                        } else {
                            $gstVerification->update([
                                'request_id' => $requestId,
                                'status' => 'in_progress',
                                'is_verified' => false,
                            ]);
                        }

                        // Wait for verification to process
                        sleep(5);

                        // Check status with more retries and longer wait times
                        // IDfy API can take 30-60 seconds to complete, so we need more retries
                        $maxRetries = 15; // Increased from 5 to 15
                        $retryCount = 0;
                        $statusResult = null;

                        while ($retryCount < $maxRetries) {
                            $statusResult = $idfyService->getTaskStatus($requestId);
                            $status = $statusResult['status'] ?? 'unknown';

                            Log::info("GST verification status check (retry {$retryCount}/{$maxRetries}) for application {$application->id}", [
                                'request_id' => $requestId,
                                'status' => $status,
                                'status_result' => $statusResult,
                            ]);

                            if ($status === 'completed') {
                                break;
                            }

                            $retryCount++;
                            if ($retryCount < $maxRetries) {
                                // Increase wait time - 3 seconds between retries (total up to 45 seconds after initial 5 seconds = 50 seconds max)
                                sleep(3);
                            }
                        }

                        if ($statusResult && ($statusResult['status'] ?? '') === 'completed') {
                            $result = $statusResult['result'] ?? null;
                            $sourceOutput = $result['source_output'] ?? null;

                            Log::info("GST verification completed for application {$application->id}", [
                                'result' => $result,
                                'source_output' => $sourceOutput,
                                'source_output_status' => $sourceOutput['status'] ?? 'missing',
                            ]);

                            if ($sourceOutput && ($sourceOutput['status'] ?? '') === 'id_found') {
                                // Extract all fields
                                $legalName = $sourceOutput['legal_name'] ?? null;
                                $tradeName = $sourceOutput['trade_name'] ?? null;
                                $pan = null;
                                $gstinFromResponse = $sourceOutput['gstin'] ?? $gstin;
                                if ($gstinFromResponse && strlen($gstinFromResponse) >= 10) {
                                    $pan = substr($gstinFromResponse, 2, 10);
                                }

                                $state = null;
                                $primaryAddress = null;
                                $address = $sourceOutput['principal_place_of_business_fields']['principal_place_of_business_address'] ?? null;
                                Log::info('Idfy GST API Address: '.json_encode($address));
                                if ($address) {
                                    $state = $address['state_name'] ?? null;
                                    $addressParts = array_filter([
                                        $address['door_number'] ?? null,
                                        $address['building_name'] ?? null,
                                        $address['street'] ?? null,
                                        $address['location'] ?? null,
                                        $address['city'] ?? null,
                                        $address['dst'] ?? null,
                                    ]);
                                    $primaryAddress = implode(', ', $addressParts);
                                }

                                $registrationDate = null;
                                if (isset($sourceOutput['date_of_registration'])) {
                                    $registrationDate = date('Y-m-d', strtotime($sourceOutput['date_of_registration']));
                                }

                                $gstType = $sourceOutput['taxpayer_type'] ?? null;
                                $companyStatus = $sourceOutput['gstin_status'] ?? null;
                                $constitutionOfBusiness = $sourceOutput['constitution_of_business'] ?? null;

                                // Update verification record
                                $updateData = [
                                    'status' => 'completed',
                                    'is_verified' => true,
                                    'verification_data' => $result,
                                    'legal_name' => $legalName,
                                    'trade_name' => $tradeName,
                                    'state' => $state,
                                    'registration_date' => $registrationDate,
                                    'gst_type' => $gstType,
                                    'company_status' => $companyStatus,
                                    'constitution_of_business' => $constitutionOfBusiness,
                                ];

                                if ($pan) {
                                    $updateData['pan'] = $pan;
                                }

                                if ($primaryAddress) {
                                    $updateData['primary_address'] = $primaryAddress;
                                }

                                $gstVerification->update($updateData);
                                $hasCompleteDetails = true;
                            } else {
                                Log::warning("GST verification completed but status is not 'id_found' for application {$application->id}", [
                                    'gstin' => $gstin,
                                    'source_output_status' => $sourceOutput['status'] ?? 'missing',
                                    'source_output' => $sourceOutput,
                                ]);
                            }
                        } else {
                            Log::warning("GST verification did not complete for application {$application->id}", [
                                'gstin' => $gstin,
                                'request_id' => $requestId,
                                'final_status' => $statusResult['status'] ?? 'null',
                                'status_result' => $statusResult,
                            ]);
                        }
                    }

                    // If we have complete details, update kyc_details
                    if ($hasCompleteDetails && $gstVerification) {
                        // Get billing address from GST verification
                        $billingAddressJson = null;
                        if ($gstVerification->verification_data) {
                            $verificationData = is_string($gstVerification->verification_data)
                                ? json_decode($gstVerification->verification_data, true)
                                : $gstVerification->verification_data;

                            // Handle both structures: with 'result' wrapper or direct 'source_output'
                            $sourceOutput = null;
                            if (isset($verificationData['result']['source_output'])) {
                                $sourceOutput = $verificationData['result']['source_output'];
                            } elseif (isset($verificationData['source_output'])) {
                                $sourceOutput = $verificationData['source_output'];
                            }

                            if ($sourceOutput) {
                                $address = $sourceOutput['principal_place_of_business_fields']['principal_place_of_business_address'] ?? null;
                                if ($address) {
                                    // Build address parts, but ensure pincode is always last if present
                                    $addressParts = array_filter([
                                        $address['door_number'] ?? '',
                                        $address['building_name'] ?? '',
                                        $address['street'] ?? '',
                                        $address['location'] ?? '',
                                        $address['city'] ?? '',
                                        $address['dst'] ?? '',
                                        $address['state_name'] ?? '',
                                    ]);

                                    $pincode = $address['pincode'] ?? '';
                                    if (! empty($pincode)) {
                                        $addressParts[] = $pincode; // Always add pincode at end if present
                                    }
                                    $addressString = implode(', ', $addressParts);

                                    $billingAddressJson = json_encode([
                                        'source' => 'gstin',
                                        'label' => 'GSTIN - '.$gstin,
                                        'address' => $addressString,
                                    ]);

                                    Log::info('API Data: '.($billingAddressJson));
                                }
                            }
                        }

                        // Enhanced logging to debug why billingAddressJson is null even when API returns value
                        Log::info('Idfy GST API - GSTIN: '.$gstin);
                        Log::info('Idfy GST API - GSTVerification ID: '.($gstVerification->id ?? 'null'));
                        Log::info('Idfy GST API - GSTVerification verification_data: '.json_encode($gstVerification->verification_data));
                        Log::info('Idfy GST API - Billing Address (decoded): '.print_r(isset($address) ? $address : null, true));
                        Log::info('Idfy GST API - Billing Address JSON (pre-encode): '.print_r($billingAddressJson, true));
                        Log::info('Idfy GST API - Billing Address (final, as will be saved): '.json_encode($billingAddressJson));

                        // Fallback to primary_address if available and billingAddressJson is still null
                        if (! $billingAddressJson && $gstVerification->primary_address) {
                            $billingAddressJson = json_encode([
                                'source' => 'gstin',
                                'label' => 'GSTIN - '.$gstin,
                                'address' => $gstVerification->primary_address,
                            ]);
                            Log::info('Idfy GST API - Using fallback primary_address for billing address');
                        }

                        // Prepare kyc_details data in the exact format specified
                        $kycDetails = [
                            'is_msme' => false,
                            'gstin' => $gstin,
                            'gst_verified' => true,
                            'udyam_number' => null,
                            'udyam_verified' => false,
                            'cin' => null,
                            'mca_verified' => false,
                            'contact_name' => null,
                            'contact_dob' => null,
                            'contact_pan' => null,
                            'contact_email' => null,
                            'contact_mobile' => null,
                            'contact_name_pan_dob_verified' => false,
                            'contact_email_verified' => false,
                            'contact_mobile_verified' => false,
                            // If $billingAddressJson is already json_encoded above, do NOT double-encode it
                            'billing_address' => $billingAddressJson,
                            'status' => 'completed',
                            'completed_at' => now()->format('Y-m-d H:i:s'),
                        ];

                        // Update application's kyc_details
                        $application->update([
                            'kyc_details' => $kycDetails,
                        ]);

                        // Update gst_verification_id if not set
                        if (! $application->gst_verification_id) {
                            $application->update([
                                'gst_verification_id' => $gstVerification->id,
                            ]);
                        }

                        // Ensure gst_verifications record exists and is up-to-date (acts as lookup table)
                        // Use updateOrCreate to ensure the record exists for lookup purposes, even if user_id changes
                        try {
                            GstVerification::updateOrCreate(
                                [
                                    'gstin' => $gstin,
                                ],
                                [
                                    'user_id' => $application->user_id,
                                    'request_id' => $gstVerification->request_id,
                                    'status' => $gstVerification->status,
                                    'is_verified' => $gstVerification->is_verified,
                                    'verification_data' => $gstVerification->verification_data,
                                    'legal_name' => $gstVerification->legal_name,
                                    'trade_name' => $gstVerification->trade_name,
                                    'pan' => $gstVerification->pan,
                                    'state' => $gstVerification->state,
                                    'registration_date' => $gstVerification->registration_date,
                                    'gst_type' => $gstVerification->gst_type,
                                    'company_status' => $gstVerification->company_status,
                                    'constitution_of_business' => $gstVerification->constitution_of_business,
                                    'primary_address' => $gstVerification->primary_address,
                                    'error_message' => $gstVerification->error_message,
                                ]
                            );

                            Log::info('Updated gst_verifications lookup record', [
                                'gstin' => $gstin,
                                'gst_verification_id' => $gstVerification->id,
                                'application_id' => $application->id,
                            ]);
                        } catch (Exception $e) {
                            Log::error('Error updating gst_verifications lookup: '.$e->getMessage(), [
                                'gstin' => $gstin,
                                'application_id' => $application->id,
                            ]);
                        }

                        $updated++;
                        Log::info('Updated kyc_details for application', [
                            'application_id' => $application->id,
                            'gstin' => $gstin,
                        ]);
                    } else {
                        $errorMsg = "Application {$application->application_id}: GST verification failed or incomplete";
                        Log::warning($errorMsg, [
                            'application_id' => $application->id,
                            'hasCompleteDetails' => $hasCompleteDetails,
                            'gstVerification_exists' => isset($gstVerification),
                            'gstVerification_is_verified' => $gstVerification->is_verified ?? null,
                            'gstVerification_status' => $gstVerification->status ?? null,
                        ]);
                        $errors[] = $errorMsg;
                        $failed++;
                    }
                } catch (Exception $e) {
                    Log::error('Error updating kyc_details for application '.$application->id.': '.$e->getMessage(), [
                        'trace' => $e->getTraceAsString(),
                    ]);
                    $errors[] = "Application {$application->application_id}: ".$e->getMessage();
                    $failed++;
                }
            }

            $message = "Updated {$updated} application(s) successfully.";
            if ($failed > 0) {
                $message .= " {$failed} application(s) failed.";
            }

            return redirect()->route('admin.applications.update-kyc-details')
                ->with('success', $message)
                ->with('errors', $errors);
        } catch (Exception $e) {
            Log::error('Error processing update kyc details: '.$e->getMessage());

            return back()->with('error', 'Unable to update kyc details. Please try again.');
        }
    }

    /**
     * Backward compatible endpoint (LIVE / NOT LIVE).
     * Now maps to service_status = live (is_active=true) or disconnected (is_active=false).
     */
    public function toggleMemberStatus(Request $request, $applicationId)
    {
        $application = Application::whereNotNull('membership_id')->findOrFail($applicationId);
        $newStatus = ! $application->is_active;

        $request->merge([
            'service_status' => $newStatus ? 'live' : 'disconnected',
            'activation_date' => $newStatus ? now('Asia/Kolkata')->format('Y-m-d') : null,
            'disconnection_date' => ! $newStatus ? now('Asia/Kolkata')->format('Y-m-d') : null,
        ]);

        return $this->updateServiceStatus($request, $applicationId);
    }

    /**
     * Update member service status: live | suspended | disconnected
     */
    public function updateServiceStatus(Request $request, $applicationId)
    {
        try {
            $adminId = session('admin_id');
            $superAdminId = session('superadmin_id');

            if (! $adminId && ! $superAdminId) {
                return back()->with('error', 'Unauthorized access.');
            }

            $validated = $request->validate([
                'service_status' => 'required|in:live,suspended,disconnected',
                'activation_date' => 'nullable|date',
                'suspension_date' => 'nullable|date',
                'disconnection_date' => 'nullable|date',
                'notes' => 'nullable|string|max:2000',
            ]);

            $application = Application::whereNotNull('membership_id')->findOrFail($applicationId);

            $oldStatus = $application->service_status ?? ($application->is_active ? 'live' : 'disconnected');
            $newStatus = $validated['service_status'];

            $now = now('Asia/Kolkata');
            $effectiveFrom = null;

            if ($newStatus === 'live') {
                $effectiveFrom = $validated['activation_date'] ? \Carbon\Carbon::parse($validated['activation_date']) : $now;

                // Enforce flow: if coming back from DISCONNECTED, final invoice up to disconnection date must be generated first.
                if ($oldStatus === 'disconnected') {
                    $stopDate = null;

                    if ($application->disconnected_at) {
                        $stopDate = \Carbon\Carbon::parse($application->disconnected_at)->startOfDay();
                    } else {
                        $lastDisconnected = \App\Models\ApplicationServiceStatusHistory::query()
                            ->where('application_id', $application->id)
                            ->where('status', 'disconnected')
                            ->whereNotNull('effective_from')
                            ->latest('effective_from')
                            ->first();

                        if ($lastDisconnected && $lastDisconnected->effective_from) {
                            $stopDate = \Carbon\Carbon::parse($lastDisconnected->effective_from)->startOfDay();
                        }
                    }

                    if ($stopDate) {
                        $hasFinalInvoice = Invoice::query()
                            ->where('application_id', $application->id)
                            ->where('status', '!=', 'cancelled')
                            ->whereNotNull('billing_start_date')
                            ->whereNotNull('billing_end_date')
                            ->whereDate('billing_start_date', '<=', $stopDate->format('Y-m-d'))
                            ->whereDate('billing_end_date', '>=', $stopDate->format('Y-m-d'))
                            ->exists();

                        if (! $hasFinalInvoice) {
                            return back()->with('error', 'Please generate the final invoice up to the disconnection date before reactivating this member.');
                        }
                    }
                }

                // Important:
                // - If coming back from SUSPENDED, we should NOT shift billing start; we only exclude suspended days.
                // - If coming back from DISCONNECTED, billing should restart from activation date.
                $billingResumeDate = $oldStatus === 'disconnected' ? $effectiveFrom->format('Y-m-d') : null;

                // Determine billing anchor date (1st of month for future billing)
                // If activation is on the 1st, anchor is the same date; otherwise, it's the 1st of the next month
                $billingAnchorDate = $effectiveFrom->day === 1
                    ? $effectiveFrom->copy()->startOfDay()
                    : $effectiveFrom->copy()->addMonth()->startOfMonth();

                $application->update([
                    'service_status' => 'live',
                    'is_active' => true,
                    'billing_resume_date' => $billingResumeDate,
                    'suspended_from' => null,
                    'disconnected_at' => null,
                    'deactivated_at' => null,
                    'deactivated_by' => null,
                    'billing_anchor_date' => $billingAnchorDate, // Set the anchor date
                ]);
            } elseif ($newStatus === 'suspended') {
                $effectiveFrom = $validated['suspension_date'] ? \Carbon\Carbon::parse($validated['suspension_date']) : $now;

                $application->update([
                    'service_status' => 'suspended',
                    'is_active' => false,
                    'suspended_from' => $effectiveFrom->format('Y-m-d'),
                    'billing_resume_date' => null,
                    'disconnected_at' => null,
                    'deactivated_at' => $now,
                    'deactivated_by' => ($adminId ?? $superAdminId),
                ]);
            } else {
                $effectiveFrom = $validated['disconnection_date'] ? \Carbon\Carbon::parse($validated['disconnection_date']) : $now;

                $application->update([
                    'service_status' => 'disconnected',
                    'is_active' => false,
                    'disconnected_at' => $effectiveFrom->format('Y-m-d'),
                    'billing_resume_date' => null,
                    'suspended_from' => null,
                    'deactivated_at' => $now,
                    'deactivated_by' => ($adminId ?? $superAdminId),
                ]);
            }

            \App\Models\ApplicationServiceStatusHistory::create([
                'application_id' => $application->id,
                'status' => $newStatus,
                'effective_from' => $effectiveFrom ? $effectiveFrom->format('Y-m-d') : null,
                'effective_to' => null,
                'changed_by_type' => $adminId ? 'admin' : 'superadmin',
                'changed_by_id' => $adminId ?? $superAdminId,
                'notes' => $validated['notes'] ?? null,
            ]);

            // Log action in AdminAction as well (existing audit trail)
            $actionType = match ($newStatus) {
                'live' => 'member_marked_live',
                'suspended' => 'member_marked_suspended',
                'disconnected' => 'member_marked_disconnected',
            };

            $message = match ($newStatus) {
                'live' => 'Member marked as LIVE successfully!',
                'suspended' => 'Member marked as SUSPENDED successfully!',
                'disconnected' => 'Member marked as DISCONNECTED successfully!',
            };

            if ($adminId) {
                AdminAction::log(
                    $adminId,
                    $actionType,
                    $application,
                    $message." Application {$application->application_id}",
                    [
                        'old_status' => $oldStatus,
                        'new_status' => $newStatus,
                        'membership_id' => $application->membership_id,
                        'effective_from' => $effectiveFrom ? $effectiveFrom->format('Y-m-d') : null,
                    ]
                );
            } else {
                AdminAction::logSuperAdmin(
                    $superAdminId,
                    $actionType,
                    $application,
                    $message." Application {$application->application_id}",
                    [
                        'old_status' => $oldStatus,
                        'new_status' => $newStatus,
                        'membership_id' => $application->membership_id,
                        'effective_from' => $effectiveFrom ? $effectiveFrom->format('Y-m-d') : null,
                    ]
                );
            }

            return back()->with('success', $message);
        } catch (Exception $e) {
            Log::error('Error updating service status: '.$e->getMessage());

            return back()->with('error', 'An error occurred. Please try again.');
        }
    }

    public function resetServiceTimeline(Request $request, $applicationId)
    {
        try {
            $adminId = session('admin_id');
            $superAdminId = session('superadmin_id');

            if (! $adminId && ! $superAdminId) {
                return back()->with('error', 'Unauthorized access.');
            }

            $validated = $request->validate([
                'reset_fields' => 'nullable|boolean',
                'reset_reactivation' => 'nullable|boolean',
            ]);

            $application = Application::whereNotNull('membership_id')->findOrFail($applicationId);

            DB::beginTransaction();

            \App\Models\ApplicationServiceStatusHistory::where('application_id', $application->id)->delete();

            if ($request->boolean('reset_reactivation') || (($validated['reset_reactivation'] ?? false) === true)) {
                // Clear reactivation workflow so the same application can be tested again end-to-end
                \App\Models\ApplicationReactivationRequest::where('application_id', $application->id)->delete();

                $testInvoices = Invoice::query()
                    ->where('application_id', $application->id)
                    ->where('status', '!=', 'cancelled')
                    ->where(function ($q) {
                        $q->where('invoice_purpose', 'reactivation')
                            ->orWhere('billing_period', 'like', 'FINAL-%');
                    })
                    ->get();

                foreach ($testInvoices as $inv) {
                    // Remove PDF
                    if ($inv->pdf_path && Storage::disk('public')->exists($inv->pdf_path)) {
                        Storage::disk('public')->delete($inv->pdf_path);
                    }

                    // Delete related records
                    try {
                        $inv->paymentAllocations()->delete();
                    } catch (\Throwable $e) {
                        // ignore if relation not loaded/available
                    }

                    PaymentTransaction::where('application_id', $inv->application_id)
                        ->where('product_info', 'like', '%'.$inv->invoice_number.'%')
                        ->delete();

                    PaymentVerificationLog::where('application_id', $inv->application_id)
                        ->where('billing_period', $inv->billing_period)
                        ->delete();

                    $inv->delete();
                }
            }

            if ($request->boolean('reset_fields') || (($validated['reset_fields'] ?? false) === true)) {
                $application->update([
                    'service_status' => 'live',
                    'is_active' => true,
                    'billing_resume_date' => null,
                    'suspended_from' => null,
                    'disconnected_at' => null,
                    'deactivated_at' => null,
                    'deactivated_by' => null,
                ]);
            }

            DB::commit();

            return back()->with('success', 'Service timeline cleared successfully.');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error resetting service timeline: '.$e->getMessage());

            return back()->with('error', 'Unable to reset service timeline.');
        }
    }

    /**
     * Delete user and all related data.
     */
    public function deleteUser($userId)
    {
        try {
            $user = Registration::findOrFail($userId);
            $userName = $user->fullname;
            $userRegistrationId = $user->registrationid;
            $adminId = session('admin_id');

            DB::beginTransaction();

            // Delete Application Status History
            $applicationIds = Application::where('user_id', $userId)->pluck('id');
            ApplicationStatusHistory::whereIn('application_id', $applicationIds)->delete();

            // Delete Applications
            $applications = Application::where('user_id', $userId)->get();
            foreach ($applications as $application) {
                // Delete application storage files if any
                $applicationPath = storage_path("app/public/applications/{$application->application_id}");
                if (File::exists($applicationPath)) {
                    File::deleteDirectory($applicationPath);
                }
            }
            Application::where('user_id', $userId)->delete();

            // Delete User KYC Profiles
            UserKycProfile::where('user_id', $userId)->delete();

            // Delete Payment Transactions
            PaymentTransaction::where('user_id', $userId)->delete();

            // Delete Messages
            Message::where('user_id', $userId)->delete();

            // Delete Profile Update Requests
            ProfileUpdateRequest::where('user_id', $userId)->delete();

            // Delete Verifications
            PanVerification::where('user_id', $userId)->delete();
            GstVerification::where('user_id', $userId)->delete();
            UdyamVerification::where('user_id', $userId)->delete();
            McaVerification::where('user_id', $userId)->delete();
            RocIecVerification::where('user_id', $userId)->delete();

            // Delete Tickets and related data
            $tickets = Ticket::where('user_id', $userId)->get();
            foreach ($tickets as $ticket) {
                // Delete ticket attachments
                TicketAttachment::where('ticket_id', $ticket->id)->delete();
                // Delete ticket messages
                TicketMessage::where('ticket_id', $ticket->id)->delete();
            }
            Ticket::where('user_id', $userId)->delete();

            // Delete Admin Actions related to this user
            AdminAction::where('actionable_type', Registration::class)
                ->where('actionable_id', $userId)
                ->delete();

            // Delete password reset tokens
            DB::table('password_reset_tokens')
                ->where('email', $user->email)
                ->delete();

            // Delete user sessions (if using database sessions)
            if (config('session.driver') === 'database') {
                DB::table('sessions')
                    ->where('user_id', $userId)
                    ->delete();
            }

            // Log action before deleting the user
            AdminAction::logAdminActivity(
                $adminId,
                'deleted_user',
                "Deleted user: {$userName} (Registration ID: {$userRegistrationId})",
                ['deleted_user_id' => $userId, 'deleted_user_name' => $userName, 'deleted_registration_id' => $userRegistrationId]
            );

            // Delete the user
            $user->delete();

            DB::commit();

            return redirect()->route('admin.users')
                ->with('success', "User '{$userName}' and all related data have been deleted successfully.");
        } catch (QueryException $e) {
            DB::rollBack();
            Log::error('Database error deleting user: '.$e->getMessage());

            return back()->with('error', 'Database connection error. Please try again later.');
        } catch (PDOException $e) {
            DB::rollBack();
            Log::error('PDO error deleting user: '.$e->getMessage());

            return back()->with('error', 'Database connection error. Please try again later.');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error deleting user: '.$e->getMessage());

            return back()->with('error', 'An error occurred while deleting the user. Please try again.');
        }
    }

    /**
     * List applications in the admin panel (IRINN workflow).
     */
    public function applications(Request $request)
    {
        try {
            $admin = $this->getCurrentAdmin();

            // Get selected role from query parameter or session (IRINN roles: helpdesk, hostmaster, billing)
            $selectedRole = $request->get('role', session('admin_selected_role', null));

            // If admin has multiple roles and no role is selected, auto-select based on IRINN priority
            if ($admin->roles->count() > 1 && ! $selectedRole) {
                $priorityOrder = ['helpdesk', 'hostmaster', 'billing'];
                foreach ($priorityOrder as $priorityRole) {
                    if ($admin->hasRole($priorityRole)) {
                        $selectedRole = $priorityRole;
                        break;
                    }
                }
            }

            // Validate selected role belongs to admin
            if ($selectedRole && ! $admin->hasRole($selectedRole)) {
                $selectedRole = null;
            }

            // Store selected role in session
            if ($selectedRole) {
                session(['admin_selected_role' => $selectedRole]);
            }

            // Get filters from request
            $statusFilter = $request->get('status');
            $roleFilter = $request->get('role_filter'); // Filter by assigned role
            $registrationFilter = $request->get('registration_filter'); // Filter by registration date
            $isActiveFilter = $request->get('is_active'); // Filter by live status
            $paymentStatusFilter = $request->get('payment_status'); // Filter by payment status
            $approvedFilter = $request->get('approved'); // Filter by approved applications
            $perPage = (int) $request->get('per_page', 20); // Pagination size
            $perPage = in_array($perPage, [10, 20, 50, 100], true) ? $perPage : 20;

            // Determine which role to use for filtering
            $roleToUse = $selectedRole;
            if ($admin->roles->count() === 1) {
                // Single role - use that role directly
                $roleToUse = $admin->roles->first()->slug;
            }

            // Build list of workflow roles actually assigned to this admin for the filter dropdown (IRINN roles)
            $workflowRoleSlugs = ['helpdesk', 'hostmaster', 'billing'];
            $assignedWorkflowRoles = $admin->roles
                ->whereIn('slug', $workflowRoleSlugs)
                ->map(function ($role) {
                    return [
                        'slug' => $role->slug,
                        'name' => $role->name ?? ucfirst(str_replace('_', ' ', $role->slug)),
                    ];
                })
                ->values();

            // Show all IRINN applications but filter based on role for default view
            $query = Application::with([
                'user',
                'processor', 'finance', 'technical', // Legacy
                'ixProcessor', 'ixLegal', 'ixHead', 'ceo', 'nodalOfficer', 'ixTechTeam', 'ixAccount', // New
                'statusHistory',
            ])->where('application_type', 'IRINN');

            // Filter by approved applications (applications beyond current role)
            if ($approvedFilter) {
                // For IRINN simplified workflow, consider billing stage as "approved"
                $approvedStatusMap = [
                    'helpdesk' => ['hostmaster', 'billing', 'billing_approved'],
                    'hostmaster' => ['billing', 'billing_approved'],
                    'billing' => ['billing_approved'],
                ];

                if ($roleToUse && isset($approvedStatusMap[$roleToUse])) {
                    $query->whereIn('status', $approvedStatusMap[$roleToUse]);
                } else {
                    // If no role or role not in map, show all applications at final stage
                    $query->whereIn('status', ['billing_approved']);
                }
            }

            // Filter by assigned role (binded to status)
            if ($roleFilter && ! $approvedFilter) {
                // IRINN simplified workflow: status equals stage
                $roleStatusMap = [
                    'helpdesk' => ['helpdesk', 'submitted'],
                    'hostmaster' => ['hostmaster'],
                    'billing' => ['billing'],
                ];

                if (isset($roleStatusMap[$roleFilter])) {
                    $query->whereIn('status', $roleStatusMap[$roleFilter]);
                }
            }

            // Filter by registration date (binded to submitted_at)
            if ($registrationFilter === 'today') {
                $query->whereDate('submitted_at', today('Asia/Kolkata'));
            } elseif ($registrationFilter === 'this_week') {
                $query->whereBetween('submitted_at', [
                    now('Asia/Kolkata')->startOfWeek(),
                    now('Asia/Kolkata')->endOfWeek(),
                ]);
            } elseif ($registrationFilter === 'this_month') {
                $query->whereMonth('submitted_at', now('Asia/Kolkata')->month)
                    ->whereYear('submitted_at', now('Asia/Kolkata')->year);
            } elseif ($registrationFilter === 'this_year') {
                $query->whereYear('submitted_at', now('Asia/Kolkata')->year);
            }

            // Filter by live status (is_active)
            if ($isActiveFilter === '1') {
                $query->where('is_active', true);
            } elseif ($isActiveFilter === '0') {
                $query->where('is_active', false);
            }

            // Filter by payment status (check invoices)
            if ($paymentStatusFilter) {
                $query->whereHas('invoices', function ($q) use ($paymentStatusFilter) {
                    $q->where('payment_status', $paymentStatusFilter);
                });
            }

            // Search functionality
            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('application_id', 'like', "%{$search}%")
                        ->orWhere('membership_id', 'like', "%{$search}%")
                        ->orWhere('customer_id', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('fullname', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->orWhere('registrationid', 'like', "%{$search}%")
                                ->orWhere('mobile', 'like', "%{$search}%");
                        })
                        ->orWhere('status', 'like', "%{$search}%");
                });
            }

            $applications = $query->orderBy('submitted_at', 'desc')->paginate($perPage)->withQueryString();

            return view('admin.applications.index', [
                'applications' => $applications,
                'admin' => $admin,
                'selectedRole' => $selectedRole,
                'assignedWorkflowRoles' => $assignedWorkflowRoles,
            ]);
        } catch (QueryException $e) {
            Log::error('Database error loading applications: '.$e->getMessage());
            abort(503, 'Database connection error. Please try again later.');
        } catch (PDOException $e) {
            Log::error('PDO error loading applications: '.$e->getMessage());
            abort(503, 'Database connection error. Please try again later.');
        } catch (Exception $e) {
            Log::error('Error loading applications: '.$e->getMessage());

            return redirect()->route('admin.dashboard')
                ->with('error', 'Unable to load applications. Please try again.');
        }
    }

    /**
     * Export filtered applications to Excel (CSV).
     */
    public function exportApplicationsToExcel(Request $request)
    {
        set_time_limit(300);
        ini_set('max_execution_time', 300);

        if (ob_get_level()) {
            ob_end_clean();
        }

        try {
            $admin = $this->getCurrentAdmin();
            $selectedRole = $request->get('role', session('admin_selected_role', null));

            $roleFilter = $request->get('role_filter');
            $registrationFilter = $request->get('registration_filter');

            $query = Application::with(['user'])
                ->where('application_type', 'IRINN');

            if ($roleFilter) {
                $roleStatusMap = [
                    'helpdesk' => ['helpdesk', 'submitted'],
                    'hostmaster' => ['hostmaster'],
                    'billing' => ['billing'],
                ];

                if (isset($roleStatusMap[$roleFilter])) {
                    $query->whereIn('status', $roleStatusMap[$roleFilter]);
                }
            } elseif ($selectedRole && in_array($selectedRole, ['helpdesk', 'hostmaster', 'billing'], true)) {
                $roleStatusMap = [
                    'helpdesk' => ['helpdesk', 'submitted'],
                    'hostmaster' => ['hostmaster'],
                    'billing' => ['billing'],
                ];
                if (isset($roleStatusMap[$selectedRole])) {
                    $query->whereIn('status', $roleStatusMap[$selectedRole]);
                }
            }

            if ($registrationFilter === 'today') {
                $query->whereDate('submitted_at', today('Asia/Kolkata'));
            } elseif ($registrationFilter === 'this_week') {
                $query->whereBetween('submitted_at', [
                    now('Asia/Kolkata')->startOfWeek(),
                    now('Asia/Kolkata')->endOfWeek(),
                ]);
            } elseif ($registrationFilter === 'this_month') {
                $query->whereMonth('submitted_at', now('Asia/Kolkata')->month)
                    ->whereYear('submitted_at', now('Asia/Kolkata')->year);
            } elseif ($registrationFilter === 'this_year') {
                $query->whereYear('submitted_at', now('Asia/Kolkata')->year);
            }

            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('application_id', 'like', "%{$search}%")
                        ->orWhere('membership_id', 'like', "%{$search}%")
                        ->orWhere('customer_id', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('fullname', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->orWhere('registrationid', 'like', "%{$search}%")
                                ->orWhere('mobile', 'like', "%{$search}%");
                        });
                });
            }

            $applications = $query->orderBy('submitted_at', 'desc')->get();

            $filename = 'applications_export_'.date('Y-m-d_His').'.csv';

            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ];

            $callback = function () use ($applications) {
                $file = fopen('php://output', 'w');
                fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

                fputcsv($file, [
                    'Application ID',
                    'Applicant Name',
                    'Applicant Email',
                    'Status',
                    'Submitted At',
                ]);

                foreach ($applications as $application) {
                    fputcsv($file, [
                        $application->application_id,
                        optional($application->user)->fullname,
                        optional($application->user)->email,
                        $application->status_display,
                        optional($application->submitted_at)->format('Y-m-d H:i:s'),
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        } catch (Exception $e) {
            Log::error('Error exporting applications: '.$e->getMessage());

            return redirect()->back()->with('error', 'Unable to export applications. Please try again.');
        }
    }

    /**
     * Show application details.
     */
    public function showApplication(Request $request, $id)
    {
        try {
            $admin = $this->getCurrentAdmin();
            $application = Application::with(['user', 'processor', 'finance', 'technical', 'statusHistory', 'serviceStatusHistories', 'paymentVerificationLogs', 'invoices', 'gstChangeHistory', 'pendingGstUpdateRequest'])
                ->findOrFail($id);

            if ($application->application_type !== 'IRINN') {
                return redirect()->route('admin.applications')
                    ->with('error', 'Only IRINN applications are supported in this portal.');
            }

            // Get selected role from query parameter or session (IRINN roles: helpdesk, hostmaster, billing)
            $selectedRole = $request->get('role', session('admin_selected_role', null));

            // If admin has multiple roles and no role is selected, auto-select based on IRINN priority
            if ($admin->roles->count() > 1 && ! $selectedRole) {
                $priorityOrder = ['helpdesk', 'hostmaster', 'billing'];
                foreach ($priorityOrder as $priorityRole) {
                    if ($admin->hasRole($priorityRole)) {
                        $selectedRole = $priorityRole;
                        break;
                    }
                }
            }

            // Validate selected role belongs to admin
            if ($selectedRole && ! $admin->hasRole($selectedRole)) {
                $selectedRole = null;
            }

            // Store selected role in session
            if ($selectedRole) {
                session(['admin_selected_role' => $selectedRole]);
            }

            $canGenerateInvoice = false;
            $invoiceGenerationMessage = null;
            $currentBillingPeriod = null;
            $currentInvoice = null;

            // Mark application as read by this admin for the selected role (if any and already submitted)
            if ($application->submitted_at && $selectedRole) {
                $admin->readApplications()->syncWithoutDetaching([
                    $application->id => [
                        'read_at' => now('Asia/Kolkata'),
                        'role' => $selectedRole,
                    ],
                ]);
            }

            // Get seller GST options for the view
            $sellerGstOptions = $this->getNixiSellerGstOptions();

            $canManageInvoices = $this->hasRole($admin, 'billing');

            $irinnAnnualInvoices = Invoice::query()
                ->where('application_id', $application->id)
                ->where('invoice_purpose', IrinnAnnualInvoiceService::INVOICE_PURPOSE)
                ->orderByDesc('id')
                ->limit(12)
                ->get();

            return view('admin.applications.show-irinn', compact(
                'application',
                'admin',
                'selectedRole',
                'canManageInvoices',
                'canGenerateInvoice',
                'invoiceGenerationMessage',
                'currentBillingPeriod',
                'currentInvoice',
                'sellerGstOptions',
                'irinnAnnualInvoices'
            ));
        } catch (QueryException $e) {
            Log::error('Database error loading application: '.$e->getMessage());
            abort(503, 'Database connection error. Please try again later.');
        } catch (PDOException $e) {
            Log::error('PDO error loading application: '.$e->getMessage());
            abort(503, 'Database connection error. Please try again later.');
        } catch (Exception $e) {
            Log::error('Error loading application: '.$e->getMessage());

            return redirect()->route('admin.applications')
                ->with('error', 'Application not found.');
        }
    }

    /**
     * IRINN: Change application stage (helpdesk -> hostmaster -> billing -> billing_approved).
     */
    public function irinnChangeStage(Request $request, $id)
    {
        try {
            $admin = $this->getCurrentAdmin();
            $application = Application::with('user')->findOrFail($id);

            if ($application->application_type !== 'IRINN') {
                return back()->with('error', 'This action is only available for IRINN applications.');
            }

            $request->validate([
                'target_stage' => 'required|string|in:hostmaster,billing,billing_approved',
            ]);

            $targetStage = $request->input('target_stage');

            // Role-based permission: which role can move to which stage
            $role = session('admin_selected_role');
            if (! $role || ! in_array($role, ['helpdesk', 'hostmaster', 'billing'], true)) {
                return back()->with('error', 'You do not have permission to change IRINN stages.');
            }

            // Allowed transitions:
            // helpdesk admin: helpdesk|submitted -> hostmaster
            // hostmaster admin: hostmaster -> billing
            // billing admin: billing -> billing_approved
            $current = $application->status ?? 'helpdesk';
            if ($current === 'submitted') {
                $current = 'helpdesk';
            }
            $allowed = false;

            if ($role === 'helpdesk' && $current === 'helpdesk' && $targetStage === 'hostmaster') {
                $allowed = true;
            } elseif ($role === 'hostmaster' && $current === 'hostmaster' && $targetStage === 'billing') {
                $allowed = true;
            } elseif ($role === 'billing' && $current === 'billing' && $targetStage === 'billing_approved') {
                $allowed = true;
            }

            if (! $allowed) {
                return back()->with('error', 'Invalid stage transition for your role.');
            }

            $oldStatus = $application->status ?? 'helpdesk';
            $application->update([
                'status' => $targetStage,
                'irinn_current_stage' => $targetStage,
            ]);

            // Status history log
            ApplicationStatusHistory::log(
                $application->id,
                $oldStatus,
                $targetStage,
                'admin',
                $admin->id,
                "IRINN stage changed from {$oldStatus} to {$targetStage} by {$role} admin"
            );

            // Admin action log
            AdminAction::log(
                $admin->id,
                'irinn_change_stage',
                $application,
                "Changed IRINN application {$application->application_id} stage from {$oldStatus} to {$targetStage}",
                ['user_id' => $application->user_id, 'old_status' => $oldStatus, 'new_status' => $targetStage]
            );

            return back()->with('success', "Application moved to {$targetStage} stage.");
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (QueryException $e) {
            Log::error('Database error changing IRINN stage: '.$e->getMessage());

            return back()->with('error', 'Database connection error. Please try again later.');
        } catch (PDOException $e) {
            Log::error('PDO error changing IRINN stage: '.$e->getMessage());

            return back()->with('error', 'Database connection error. Please try again later.');
        } catch (Exception $e) {
            Log::error('Error changing IRINN stage: '.$e->getMessage());

            return back()->with('error', 'An error occurred. Please try again.');
        }
    }

    /**
     * IRINN: Request resubmission from user (helpdesk or hostmaster).
     */
    public function irinnRequestResubmission(Request $request, $id)
    {
        try {
            $admin = $this->getCurrentAdmin();
            $application = Application::with('user')->findOrFail($id);

            if ($application->application_type !== 'IRINN') {
                return back()->with('error', 'This action is only available for IRINN applications.');
            }

            $request->validate([
                'resubmission_reason' => 'required|string|max:2000',
            ]);

            $role = session('admin_selected_role');
            if (! $role || ! in_array($role, ['helpdesk', 'hostmaster'], true)) {
                return back()->with('error', 'Only Helpdesk or Hostmaster can request resubmission.');
            }

            $oldStatus = $application->status ?? 'helpdesk';

            if ($role === 'helpdesk') {
                $allowedForHelpdesk = ['submitted', 'helpdesk', 'pending'];
                if (! in_array($oldStatus, $allowedForHelpdesk, true)) {
                    return back()->with('error', 'Helpdesk can request resubmission only while the application is submitted, with Helpdesk, or pending.');
                }
            } elseif ($role === 'hostmaster') {
                if ($oldStatus !== 'hostmaster') {
                    return back()->with('error', 'Hostmaster can request resubmission only while the application is at the Hostmaster stage.');
                }
            }

            // Mark application as resubmission requested, but keep original stage in application_data for reference
            $data = $application->application_data ?? [];
            $data['irinn_previous_stage'] = $oldStatus;
            $data['irinn_resubmission_requested_at'] = now('Asia/Kolkata')->toDateTimeString();
            $data['irinn_resubmission_requested_by'] = $role;
            $data['irinn_resubmission_reason'] = $request->input('resubmission_reason');

            $application->update([
                'status' => 'resubmission_requested',
                'application_data' => $data,
            ]);

            // Status history
            ApplicationStatusHistory::log(
                $application->id,
                $oldStatus,
                'resubmission_requested',
                'admin',
                $admin->id,
                "IRINN resubmission requested by {$role} admin"
            );

            // Admin action log
            AdminAction::log(
                $admin->id,
                'irinn_request_resubmission',
                $application,
                "Requested resubmission for IRINN application {$application->application_id}",
                [
                    'user_id' => $application->user_id,
                    'old_status' => $oldStatus,
                    'reason' => $request->input('resubmission_reason'),
                ]
            );

            // Notify user (in-app message)
            $this->createMessageForAdmin(
                $admin->id,
                $application->user_id,
                'IRINN Application Resubmission Requested',
                "Your IRINN application {$application->application_id} requires resubmission:\n\n".$request->input('resubmission_reason')
            );

            $applicant = $application->user;
            if ($applicant && filter_var($applicant->email, FILTER_VALIDATE_EMAIL)) {
                try {
                    Mail::to($applicant->email)->send(
                        new IrinnResubmissionRequestedMail($application->fresh(['user']), (string) $request->input('resubmission_reason'))
                    );
                } catch (Exception $e) {
                    Log::warning('IRINN resubmission email failed: '.$e->getMessage(), [
                        'application_id' => $application->id,
                    ]);
                }
            }

            return back()->with('success', 'Resubmission requested from user.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (QueryException $e) {
            Log::error('Database error requesting IRINN resubmission: '.$e->getMessage());

            return back()->with('error', 'Database connection error. Please try again later.');
        } catch (PDOException $e) {
            Log::error('PDO error requesting IRINN resubmission: '.$e->getMessage());

            return back()->with('error', 'Database connection error. Please try again later.');
        } catch (Exception $e) {
            Log::error('Error requesting IRINN resubmission: '.$e->getMessage());

            return back()->with('error', 'An error occurred. Please try again.');
        }
    }

    /**
     * IRINN Billing: update discount % on application (applies to all future annual invoices).
     */
    /**
     * IRINN Hostmaster: confirm resource allocation and effective date (used for annual billing PDF / prerequisites).
     */
    public function irinnHostmasterAllocateResources(Request $request, $id): RedirectResponse
    {
        try {
            $admin = $this->getCurrentAdmin();
            $application = Application::query()->findOrFail($id);

            if ($application->application_type !== 'IRINN') {
                return back()->with('error', 'This action is only for IRINN applications.');
            }

            if (session('admin_selected_role') !== 'hostmaster') {
                return back()->with('error', 'Only the hostmaster role can confirm resource allocation.');
            }

            if (($application->status ?? '') !== 'billing_approved') {
                return back()->with('error', 'Resource allocation is available only after billing approval.');
            }

            $allocated = $request->boolean('irinn_resources_allocated');

            $validated = $request->validate([
                'billing_anchor_date' => ['nullable', 'date', Rule::requiredIf($allocated)],
            ]);

            $application->update([
                'irinn_resources_allocated' => $allocated,
                'billing_anchor_date' => $allocated ? $validated['billing_anchor_date'] : null,
            ]);

            AdminAction::log(
                $admin->id,
                'irinn_hostmaster_allocate',
                $application,
                ($allocated ? 'Confirmed IRINN resource allocation' : 'Cleared IRINN resource allocation')
                    .($application->billing_anchor_date ? ' (effective '.$application->billing_anchor_date->format('Y-m-d').')' : ''),
                ['user_id' => $application->user_id]
            );

            if ($allocated && $application->billing_anchor_date) {
                ApplicationStatusHistory::log(
                    $application->id,
                    (string) $application->status,
                    (string) $application->status,
                    'admin',
                    $admin->id,
                    'IRINN resources allocated (effective date: '.$application->billing_anchor_date->format('d M Y').').'
                );
            }

            return back()->with('success', $allocated ? 'Resource allocation saved.' : 'Resource allocation cleared.');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (Exception $e) {
            Log::error('irinnHostmasterAllocateResources: '.$e->getMessage());

            return back()->with('error', 'Unable to save allocation.');
        }
    }

    public function irinnBillingUpdateDiscount(Request $request, $id): RedirectResponse
    {
        try {
            $admin = $this->getCurrentAdmin();
            $application = Application::query()->findOrFail($id);

            if ($application->application_type !== 'IRINN') {
                return back()->with('error', 'This action is only for IRINN applications.');
            }

            $role = session('admin_selected_role');
            if ($role !== 'billing') {
                return back()->with('error', 'Only billing role can update the billing discount.');
            }

            $validated = $request->validate([
                'irinn_billing_discount_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            ]);

            $application->update([
                'irinn_billing_discount_percent' => $validated['irinn_billing_discount_percent'],
            ]);

            AdminAction::log(
                $admin->id,
                'irinn_billing_discount',
                $application,
                "Updated IRINN billing discount to {$validated['irinn_billing_discount_percent']}% for {$application->application_id}",
                ['user_id' => $application->user_id]
            );

            return back()->with('success', 'Billing discount percentage saved. It will apply to future annual invoices.');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (Exception $e) {
            Log::error('irinnBillingUpdateDiscount: '.$e->getMessage());

            return back()->with('error', 'Unable to save discount.');
        }
    }

    /**
     * IRINN Billing: generate annual resource invoice (PDF stored on disk; e-invoice API only if billing GST provided).
     */
    public function irinnBillingGenerateAnnualInvoice(Request $request, $id): RedirectResponse
    {
        try {
            $admin = $this->getCurrentAdmin();
            $application = Application::with('user')->findOrFail($id);

            if ($application->application_type !== 'IRINN') {
                return back()->with('error', 'This action is only for IRINN applications.');
            }

            $role = session('admin_selected_role');
            if ($role !== 'billing') {
                return back()->with('error', 'Only billing role can generate IRINN annual invoices.');
            }

            $status = $application->status ?? '';
            if ($status !== 'billing_approved') {
                return back()->with('error', 'Annual invoices can be generated only after billing has approved this application (status must be billing approved).');
            }

            $validated = $request->validate([
                'annual_base_amount' => ['required', 'numeric', 'min:0.01'],
            ]);

            $invoice = app(IrinnAnnualInvoiceService::class)->generate($application, $validated, $admin->id);

            ApplicationStatusHistory::log(
                $application->id,
                $status,
                $status,
                'admin',
                $admin->id,
                'IRINN annual invoice generated: '.$invoice->invoice_number
            );

            $user = $application->user;
            if ($user && filled($user->email) && $invoice->pdf_path) {
                try {
                    Mail::to($user->email)->send(new IrinnAnnualInvoiceMail(
                        (string) $user->fullname,
                        (string) $application->application_id,
                        (string) $invoice->invoice_number,
                        (float) $invoice->total_amount,
                        (string) ($invoice->billing_period ?? ''),
                        (string) $invoice->pdf_path
                    ));
                    $invoice->update(['sent_at' => now('Asia/Kolkata')]);
                } catch (\Throwable $e) {
                    Log::warning('IRINN annual invoice email failed: '.$e->getMessage(), [
                        'invoice_id' => $invoice->id,
                    ]);
                }
            }

            return back()->with('success', 'Annual invoice '.$invoice->invoice_number.' generated, PDF saved, and the user was notified by email when mail could be sent.');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\InvalidArgumentException|\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        } catch (Exception $e) {
            Log::error('irinnBillingGenerateAnnualInvoice: '.$e->getMessage());

            return back()->with('error', 'Unable to generate invoice. Please try again.');
        }
    }

    /**
     * IRINN Billing: preview annual invoice (seller/buyer GST, amounts, FY) before generating.
     */
    public function irinnBillingPreviewAnnualInvoice(Request $request, $id)
    {
        try {
            $admin = $this->getCurrentAdmin();
            $application = Application::with('user')->findOrFail($id);

            if ($application->application_type !== 'IRINN') {
                abort(404);
            }

            if (session('admin_selected_role') !== 'billing') {
                return redirect()->route('admin.applications.show', $id)
                    ->with('error', 'Only the billing role can preview annual invoices.');
            }

            if (($application->status ?? '') !== 'billing_approved') {
                return redirect()->route('admin.applications.show', $id)
                    ->with('error', 'Preview is available only after billing approval.');
            }

            $validated = $request->validate([
                'annual_base_amount' => ['required', 'numeric', 'min:0.01'],
            ]);

            $preview = app(IrinnAnnualInvoiceService::class)->preview($application, (float) $validated['annual_base_amount']);

            return view('admin.applications.irinn-annual-invoice-preview', compact('application', 'admin', 'preview'));
        } catch (ValidationException $e) {
            return redirect()->route('admin.applications.show', $id)
                ->withErrors($e->errors())
                ->with('error', 'Enter a valid annual base amount to preview.');
        } catch (\InvalidArgumentException $e) {
            return redirect()->route('admin.applications.show', $id)
                ->with('error', $e->getMessage());
        } catch (Exception $e) {
            Log::error('irinnBillingPreviewAnnualInvoice: '.$e->getMessage());

            return redirect()->route('admin.applications.show', $id)
                ->with('error', 'Unable to load preview.');
        }
    }

    /**
     * IRINN Billing: mark an annual invoice paid manually (reference, notes, optional proof upload).
     */
    public function irinnBillingMarkAnnualInvoicePaid(Request $request, $applicationId, $invoiceId): RedirectResponse
    {
        try {
            $admin = $this->getCurrentAdmin();

            if (session('admin_selected_role') !== 'billing') {
                return back()->with('error', 'Only billing can mark invoices paid.');
            }

            $application = Application::query()->findOrFail($applicationId);

            $invoice = Invoice::query()
                ->where('id', $invoiceId)
                ->where('application_id', $application->id)
                ->where('invoice_purpose', IrinnAnnualInvoiceService::INVOICE_PURPOSE)
                ->firstOrFail();

            if ($invoice->payment_status === 'paid' && (float) ($invoice->balance_amount ?? 0) <= 0.009) {
                return back()->with('error', 'This invoice is already fully paid.');
            }

            $validated = $request->validate([
                'manual_payment_id' => ['required', 'string', 'max:255'],
                'manual_payment_notes' => ['nullable', 'string', 'max:2000'],
                'billing_payment_proof' => ['nullable', 'file', 'mimes:pdf,jpeg,jpg,png', 'max:10240'],
            ]);

            $proofPath = $invoice->billing_payment_proof_path;
            if ($request->hasFile('billing_payment_proof')) {
                $proofPath = $request->file('billing_payment_proof')->store('invoices/billing-proofs', 'public');
            }

            $invoice->update([
                'manual_payment_id' => $validated['manual_payment_id'],
                'manual_payment_notes' => $validated['manual_payment_notes'] ?? null,
                'billing_payment_proof_path' => $proofPath,
                'paid_amount' => $invoice->total_amount,
                'balance_amount' => 0,
                'payment_status' => 'paid',
                'status' => 'paid',
                'paid_at' => now('Asia/Kolkata'),
                'paid_by' => $admin->id,
            ]);

            AdminAction::log(
                $admin->id,
                'irinn_invoice_mark_paid',
                $application,
                'Marked IRINN annual invoice '.$invoice->invoice_number.' as paid (manual reference: '.$validated['manual_payment_id'].')',
                ['invoice_id' => $invoice->id]
            );

            return back()->with('success', 'Invoice '.$invoice->invoice_number.' marked as paid.');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (Exception $e) {
            Log::error('irinnBillingMarkAnnualInvoicePaid: '.$e->getMessage());

            return back()->with('error', 'Unable to mark invoice paid.');
        }
    }

    /**
     * Billing admin: download user-uploaded TDS certificate for an invoice.
     */
    public function downloadInvoiceTdsCertificate($invoiceId)
    {
        try {
            if (session('admin_selected_role') !== 'billing') {
                abort(403, 'Only billing can download this document.');
            }

            $invoice = Invoice::with('application')->findOrFail($invoiceId);

            if (($invoice->application?->application_type ?? '') !== 'IRINN') {
                abort(404);
            }

            if (! $invoice->tds_certificate_path) {
                return redirect()->back()->with('error', 'No TDS certificate on file for this invoice.');
            }

            $path = public_path($invoice->tds_certificate_path);
            if (! is_file($path)) {
                return redirect()->back()->with('error', 'TDS certificate file is missing on disk.');
            }

            return response()->download($path, 'tds_'.$invoice->invoice_number.'.pdf');
        } catch (Exception $e) {
            Log::error('downloadInvoiceTdsCertificate: '.$e->getMessage());

            return redirect()->back()->with('error', 'Unable to download TDS certificate.');
        }
    }

    /**
     * Billing admin: download payment proof uploaded when marking an invoice paid.
     */
    public function downloadInvoiceBillingPaymentProof($invoiceId)
    {
        try {
            if (session('admin_selected_role') !== 'billing') {
                abort(403, 'Only billing can download this document.');
            }

            $invoice = Invoice::with('application')->findOrFail($invoiceId);

            if (($invoice->application?->application_type ?? '') !== 'IRINN') {
                abort(404);
            }

            if (! $invoice->billing_payment_proof_path || ! Storage::disk('public')->exists($invoice->billing_payment_proof_path)) {
                return redirect()->back()->with('error', 'No billing payment proof on file.');
            }

            $full = Storage::disk('public')->path($invoice->billing_payment_proof_path);

            return response()->download($full, 'payment_proof_'.$invoice->invoice_number.'.'.pathinfo($full, PATHINFO_EXTENSION));
        } catch (Exception $e) {
            Log::error('downloadInvoiceBillingPaymentProof: '.$e->getMessage());

            return redirect()->back()->with('error', 'Unable to download payment proof.');
        }
    }

    /**
     * Show comprehensive application details (similar to user side).
     */
    public function showApplicationComprehensive($id)
    {
        try {
            $admin = $this->getCurrentAdmin();
            $application = Application::with([
                'user',
                'statusHistory' => fn ($q) => $q->orderBy('created_at'),
                'gstChangeHistory',
                'planChangeRequests',
            ])
                ->findOrFail($id);

            // Load pending GST update request separately
            $application->load(['gstUpdateRequests' => function ($query) {
                $query->where('status', 'pending')->latest();
            }]);

            // Load plan change requests with relationships
            $pendingPlanChange = $application->planChangeRequests()
                ->where('status', 'pending')
                ->latest()
                ->first();

            $approvedPlanChange = $application->planChangeRequests()
                ->where('status', 'approved')
                ->where('effective_from', '>', now())
                ->latest()
                ->first();

            // Mark application as read by this admin for all their roles (this view is generic)
            if ($application->submitted_at) {
                $roles = $admin->roles()->pluck('slug')->toArray();

                if (! empty($roles)) {
                    $pivotData = [];
                    foreach ($roles as $roleSlug) {
                        $pivotData[$application->id] = [
                            'read_at' => now('Asia/Kolkata'),
                            'role' => $roleSlug,
                        ];
                    }

                    $admin->readApplications()->syncWithoutDetaching($pivotData);
                }
            }

            if ($application->application_type === 'IRINN') {
                IrinnApplicationDisplayEnricher::enrich($application);
                $application->statusHistory->each(fn ($row) => $row->setRelation('application', $application));

                $pendingGstUpdateRequest = ApplicationGstUpdateRequest::where('application_id', $application->id)
                    ->where('status', 'pending')
                    ->latest()
                    ->first();

                return view('admin.applications.show-comprehensive-irinn-parity', compact(
                    'application',
                    'admin',
                    'pendingPlanChange',
                    'approvedPlanChange',
                    'pendingGstUpdateRequest'
                ));
            }

            return view('admin.applications.show-comprehensive', compact('application', 'admin', 'pendingPlanChange', 'approvedPlanChange'));
        } catch (Exception $e) {
            Log::error('Error loading comprehensive application details: '.$e->getMessage());

            return redirect()->route('admin.applications')
                ->with('error', 'Unable to load application details. Please try again.');
        }
    }

    /**
     * Serve application document securely.
     */
    public function serveDocument($id, Request $request)
    {
        try {
            $documentKey = $request->input('doc');
            $documentIndex = $request->input('index');

            if (! $documentKey) {
                abort(400, 'Document key is required.');
            }

            $application = Application::findOrFail($id);
            if ($application->application_type !== 'IRINN') {
                abort(404, 'Application not found.');
            }

            $applicationData = $application->application_data ?? [];
            $filePath = null;

            if (Application::isIrinnStoredPathDocumentKey($documentKey)) {
                $filePath = $application->getAttribute($documentKey);
            } elseif (isset($applicationData['part3'][$documentKey])) {
                $filePath = $applicationData['part3'][$documentKey];
            } elseif (isset($applicationData['part4'][$documentKey])) {
                $filePath = $applicationData['part4'][$documentKey];
            }

            // Support multi-file document arrays (e.g. bandwidth_invoice_file)
            if (is_array($filePath)) {
                if ($documentIndex !== null && isset($filePath[(int) $documentIndex])) {
                    $filePath = $filePath[(int) $documentIndex];
                } else {
                    $filePath = $filePath[0] ?? null;
                }
            }

            if (! $filePath) {
                abort(404, 'Document not found.');
            }

            if (! Storage::disk('public')->exists($filePath)) {
                abort(404, 'File not found on server.');
            }

            $fullPath = Storage::disk('public')->path($filePath);
            $fileName = basename($filePath);

            return response()->file($fullPath, [
                'Content-Type' => Storage::disk('public')->mimeType($filePath),
                'Content-Disposition' => 'inline; filename="'.$fileName.'"',
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            abort(404, 'Application not found.');
        } catch (Exception $e) {
            Log::error('Error serving document: '.$e->getMessage());
            abort(500, 'Unable to serve document.');
        }
    }

    /**
     * Processor: Approve application to Finance.
     */
    public function approveToFinance(Request $request, $id)
    {
        try {
            $admin = $this->getCurrentAdmin();

            if (! $this->hasRole($admin, 'processor')) {
                return back()->with('error', 'You do not have permission to perform this action.');
            }

            $application = Application::with('user')->findOrFail($id);

            if (! $application->isVisibleToProcessor()) {
                return back()->with('error', 'This application is not available for processing.');
            }

            $oldStatus = $application->status;
            $application->update([
                'status' => 'processor_approved',
                'current_processor_id' => $admin->id,
            ]);

            // Log status change
            ApplicationStatusHistory::log(
                $application->id,
                $oldStatus,
                'processor_approved',
                'admin',
                $admin->id,
                'Application approved by Processor and forwarded to Finance'
            );

            // Log admin action
            AdminAction::log(
                $admin->id,
                'approved_application',
                $application,
                "Approved application {$application->application_id} to Finance",
                ['user_id' => $application->user_id]
            );

            // Send message to user
            $this->createMessageForAdmin(
                $admin->id,
                $application->user_id,
                'Application Status Update',
                "Your application {$application->application_id} has been approved by Processor and forwarded to Finance for review."
            );

            return back()->with('success', 'Application approved and forwarded to Finance!');
        } catch (QueryException $e) {
            Log::error('Database error approving application: '.$e->getMessage());

            return back()->with('error', 'Database connection error. Please try again later.');
        } catch (PDOException $e) {
            Log::error('PDO error approving application: '.$e->getMessage());

            return back()->with('error', 'Database connection error. Please try again later.');
        } catch (Exception $e) {
            Log::error('Error approving application: '.$e->getMessage());

            return back()->with('error', 'An error occurred. Please try again.');
        }
    }

    /**
     * Finance: Approve application to Technical.
     */
    public function approveToTechnical(Request $request, $id)
    {
        try {
            $admin = $this->getCurrentAdmin();

            if (! $this->hasRole($admin, 'finance')) {
                return back()->with('error', 'You do not have permission to perform this action.');
            }

            $application = Application::with('user')->findOrFail($id);

            if (! $application->isVisibleToFinance()) {
                return back()->with('error', 'This application is not available for Finance review.');
            }

            $oldStatus = $application->status;
            $application->update([
                'status' => 'finance_approved',
                'current_finance_id' => $admin->id,
            ]);

            // Log status change
            ApplicationStatusHistory::log(
                $application->id,
                $oldStatus,
                'finance_approved',
                'admin',
                $admin->id,
                'Application approved by Finance and forwarded to Technical'
            );

            // Log admin action
            AdminAction::log(
                $admin->id,
                'approved_application',
                $application,
                "Approved application {$application->application_id} to Technical",
                ['user_id' => $application->user_id]
            );

            // Send message to user
            $this->createMessageForAdmin(
                $admin->id,
                $application->user_id,
                'Application Status Update',
                "Your application {$application->application_id} has been approved by Finance and forwarded to Technical for final review."
            );

            return back()->with('success', 'Application approved and forwarded to Technical!');
        } catch (QueryException $e) {
            Log::error('Database error approving application: '.$e->getMessage());

            return back()->with('error', 'Database connection error. Please try again later.');
        } catch (PDOException $e) {
            Log::error('PDO error approving application: '.$e->getMessage());

            return back()->with('error', 'Database connection error. Please try again later.');
        } catch (Exception $e) {
            Log::error('Error approving application: '.$e->getMessage());

            return back()->with('error', 'An error occurred. Please try again.');
        }
    }

    /**
     * Finance: Send application back to Processor.
     */
    public function sendBackToProcessor(Request $request, $id)
    {
        try {
            $admin = $this->getCurrentAdmin();

            if (! $this->hasRole($admin, 'finance')) {
                return back()->with('error', 'You do not have permission to perform this action.');
            }

            $validated = $request->validate([
                'rejection_reason' => 'required|string|min:10',
            ], [
                'rejection_reason.required' => 'Please provide a reason for rejection.',
                'rejection_reason.min' => 'Please provide more details (minimum 10 characters).',
            ]);

            $application = Application::with('user')->findOrFail($id);

            if (! $application->isVisibleToFinance()) {
                return back()->with('error', 'This application is not available for Finance review.');
            }

            $oldStatus = $application->status;
            $application->update([
                'status' => 'processor_review',
                'rejection_reason' => $validated['rejection_reason'],
                'current_finance_id' => $admin->id,
            ]);

            // Log status change
            ApplicationStatusHistory::log(
                $application->id,
                $oldStatus,
                'processor_review',
                'admin',
                $admin->id,
                $validated['rejection_reason']
            );

            // Log admin action
            AdminAction::log(
                $admin->id,
                'rejected_application',
                $application,
                "Sent application {$application->application_id} back to Processor",
                ['reason' => $validated['rejection_reason']]
            );

            // Send message to user
            $this->createMessageForAdmin(
                $admin->id,
                $application->user_id,
                'Application Status Update',
                "Your application {$application->application_id} has been sent back to Processor for review. Reason: {$validated['rejection_reason']}"
            );

            return back()->with('success', 'Application sent back to Processor!');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (QueryException $e) {
            Log::error('Database error sending application back: '.$e->getMessage());

            return back()->with('error', 'Database connection error. Please try again later.')
                ->withInput();
        } catch (PDOException $e) {
            Log::error('PDO error sending application back: '.$e->getMessage());

            return back()->with('error', 'Database connection error. Please try again later.')
                ->withInput();
        } catch (Exception $e) {
            Log::error('Error sending application back: '.$e->getMessage());

            return back()->with('error', 'An error occurred. Please try again.');
        }
    }

    /**
     * Technical: Approve application (final approval).
     */
    public function approveApplication(Request $request, $id)
    {
        try {
            $admin = $this->getCurrentAdmin();

            if (! $this->hasRole($admin, 'technical')) {
                return back()->with('error', 'You do not have permission to perform this action.');
            }

            $application = Application::with('user')->findOrFail($id);

            if (! $application->isVisibleToTechnical()) {
                return back()->with('error', 'This application is not available for Technical review.');
            }

            $oldStatus = $application->status;
            $application->update([
                'status' => 'approved',
                'approved_at' => now('Asia/Kolkata'),
                'current_technical_id' => $admin->id,
            ]);

            // Log status change
            ApplicationStatusHistory::log(
                $application->id,
                $oldStatus,
                'approved',
                'admin',
                $admin->id,
                'Application approved by Technical'
            );

            // Log admin action
            AdminAction::log(
                $admin->id,
                'approved_application',
                $application,
                "Approved application {$application->application_id}",
                ['user_id' => $application->user_id]
            );

            // Send message to user
            $this->createMessageForAdmin(
                $admin->id,
                $application->user_id,
                'Application Approved!',
                "Congratulations! Your application {$application->application_id} has been approved by Technical. You can now view it in your Applications tab."
            );

            return back()->with('success', 'Application approved successfully!');
        } catch (QueryException $e) {
            Log::error('Database error approving application: '.$e->getMessage());

            return back()->with('error', 'Database connection error. Please try again later.');
        } catch (PDOException $e) {
            Log::error('PDO error approving application: '.$e->getMessage());

            return back()->with('error', 'Database connection error. Please try again later.');
        } catch (Exception $e) {
            Log::error('Error approving application: '.$e->getMessage());

            return back()->with('error', 'An error occurred. Please try again.');
        }
    }

    /**
     * Technical: Send application back to Finance.
     */
    public function sendBackToFinance(Request $request, $id)
    {
        try {
            $admin = $this->getCurrentAdmin();

            if (! $this->hasRole($admin, 'technical')) {
                return back()->with('error', 'You do not have permission to perform this action.');
            }

            $validated = $request->validate([
                'rejection_reason' => 'required|string|min:10',
            ], [
                'rejection_reason.required' => 'Please provide a reason for rejection.',
                'rejection_reason.min' => 'Please provide more details (minimum 10 characters).',
            ]);

            $application = Application::with('user')->findOrFail($id);

            if (! $application->isVisibleToTechnical()) {
                return back()->with('error', 'This application is not available for Technical review.');
            }

            $oldStatus = $application->status;
            $application->update([
                'status' => 'finance_review',
                'rejection_reason' => $validated['rejection_reason'],
                'current_technical_id' => $admin->id,
            ]);

            // Log status change
            ApplicationStatusHistory::log(
                $application->id,
                $oldStatus,
                'finance_review',
                'admin',
                $admin->id,
                $validated['rejection_reason']
            );

            // Log admin action
            AdminAction::log(
                $admin->id,
                'rejected_application',
                $application,
                "Sent application {$application->application_id} back to Finance",
                ['reason' => $validated['rejection_reason']]
            );

            // Send message to user
            $this->createMessageForAdmin(
                $admin->id,
                $application->user_id,
                'Application Status Update',
                "Your application {$application->application_id} has been sent back to Finance for review. Reason: {$validated['rejection_reason']}"
            );

            return back()->with('success', 'Application sent back to Finance!');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (QueryException $e) {
            Log::error('Database error sending application back: '.$e->getMessage());

            return back()->with('error', 'Database connection error. Please try again later.')
                ->withInput();
        } catch (PDOException $e) {
            Log::error('PDO error sending application back: '.$e->getMessage());

            return back()->with('error', 'Database connection error. Please try again later.')
                ->withInput();
        } catch (Exception $e) {
            Log::error('Error sending application back: '.$e->getMessage());

            return back()->with('error', 'An error occurred. Please try again.');
        }
    }

    /**
     * Display all profile update requests and messages for admin.
     */
    public function requestsAndMessages(Request $request)
    {
        try {
            $admin = $this->getCurrentAdmin();

            // Get all pending profile update requests with user info
            $requestsQuery = ProfileUpdateRequest::with(['user'])
                ->where('status', 'pending');

            // Search for requests
            if ($request->filled('requests_search')) {
                $search = $request->input('requests_search');
                $requestsQuery->where(function ($q) use ($search) {
                    $q->whereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('fullname', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('registrationid', 'like', "%{$search}%");
                    });
                });
            }

            $profileUpdateRequests = $requestsQuery->latest()->paginate(20, ['*'], 'requests_page')->withQueryString();

            // Get all recent messages sent to users
            $messagesQuery = Message::with(['user']);

            // Search for messages
            if ($request->filled('messages_search')) {
                $search = $request->input('messages_search');
                $messagesQuery->where(function ($q) use ($search) {
                    $q->where('subject', 'like', "%{$search}%")
                        ->orWhere('message', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('fullname', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            }

            $messages = $messagesQuery->latest()->paginate(20, ['*'], 'messages_page')->withQueryString();

            return view('admin.requests-messages', compact('admin', 'profileUpdateRequests', 'messages'));
        } catch (QueryException $e) {
            Log::error('Database error loading requests and messages: '.$e->getMessage());
            abort(503, 'Database connection error. Please try again later.');
        } catch (PDOException $e) {
            Log::error('PDO error loading requests and messages: '.$e->getMessage());
            abort(503, 'Database connection error. Please try again later.');
        } catch (Exception $e) {
            Log::error('Error loading requests and messages: '.$e->getMessage());

            return redirect()->route('admin.dashboard')
                ->with('error', 'Unable to load requests and messages. Please try again.');
        }
    }

    /**
     * Display admin messages inbox.
     */
    public function messages(Request $request)
    {
        try {
            $admin = $this->getCurrentAdmin();

            // Get message IDs sent by this admin
            $adminMessageIds = AdminAction::where('admin_id', $admin->id)
                ->where('action_type', 'sent_message')
                ->where('actionable_type', Message::class)
                ->pluck('actionable_id')
                ->toArray();

            // Filter messages: show messages sent by this admin, user replies, OR system messages (notifications)
            $query = Message::with(['user']);

            if (count($adminMessageIds) > 0) {
                $query->where(function ($q) use ($adminMessageIds) {
                    $q->whereIn('id', $adminMessageIds)
                        ->orWhere('sent_by', 'system'); // Show all system messages (notifications) to all admins
                });
            } else {
                // If no messages linked to this admin, still show system messages
                $query->where('sent_by', 'system');
            }

            // For admin view, we check admin_read status, not is_read
            // is_read is for user's read status

            // Search functionality
            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('subject', 'like', "%{$search}%")
                        ->orWhere('message', 'like', "%{$search}%")
                        ->orWhere('user_reply', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('fullname', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->orWhere('registrationid', 'like', "%{$search}%");
                        });
                });
            }

            $messages = $query->latest()->paginate(20)->withQueryString();

            return view('admin.messages.index', compact('admin', 'messages'));
        } catch (QueryException $e) {
            Log::error('Database error loading admin messages: '.$e->getMessage());
            abort(503, 'Database connection error. Please try again later.');
        } catch (PDOException $e) {
            Log::error('PDO error loading admin messages: '.$e->getMessage());
            abort(503, 'Database connection error. Please try again later.');
        } catch (Exception $e) {
            Log::error('Error loading admin messages: '.$e->getMessage());

            return redirect()->route('admin.dashboard')
                ->with('error', 'Unable to load messages. Please try again.');
        }
    }

    /**
     * Display message details.
     */
    public function showMessage($id)
    {
        try {
            $admin = $this->getCurrentAdmin();

            $message = Message::with(['user'])->findOrFail($id);

            // Verify that this message was sent by the current admin
            $adminAction = AdminAction::where('admin_id', $admin->id)
                ->where('action_type', 'sent_message')
                ->where('actionable_type', Message::class)
                ->where('actionable_id', $message->id)
                ->first();

            if (! $adminAction) {
                return redirect()->route('admin.messages')
                    ->with('error', 'You do not have permission to view this message.');
            }

            // Mark message as read by admin when admin views it
            if (! $message->admin_read) {
                $message->markAsReadByAdmin();
            }

            return view('admin.messages.show', compact('message', 'admin', 'adminAction'));
        } catch (Exception $e) {
            Log::error('Error loading message details: '.$e->getMessage());

            return redirect()->route('admin.messages')
                ->with('error', 'Unable to load message details.');
        }
    }

    /**
     * Display all profile update requests.
     */
    public function profileUpdateRequests(Request $request)
    {
        try {
            $admin = $this->getCurrentAdmin();

            $query = ProfileUpdateRequest::with(['user', 'approver']);

            // Search functionality
            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('status', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('fullname', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->orWhere('registrationid', 'like', "%{$search}%")
                                ->orWhere('mobile', 'like', "%{$search}%");
                        });
                });
            }

            $requests = $query->latest()->paginate(20)->withQueryString();

            return view('admin.profile-update-requests.index', compact('admin', 'requests'));
        } catch (QueryException $e) {
            Log::error('Database error loading profile update requests: '.$e->getMessage());
            abort(503, 'Database connection error. Please try again later.');
        } catch (PDOException $e) {
            Log::error('PDO error loading profile update requests: '.$e->getMessage());
            abort(503, 'Database connection error. Please try again later.');
        } catch (Exception $e) {
            Log::error('Error loading profile update requests: '.$e->getMessage());

            return redirect()->route('admin.dashboard')
                ->with('error', 'Unable to load profile update requests. Please try again.');
        }
    }

    /**
     * Show profile update request details.
     */
    public function showProfileUpdateRequest($id)
    {
        try {
            $admin = $this->getCurrentAdmin();

            $request = ProfileUpdateRequest::with(['user', 'approver'])->findOrFail($id);

            return view('admin.profile-update-requests.show', compact('admin', 'request'));
        } catch (Exception $e) {
            Log::error('Error loading profile update request details: '.$e->getMessage());

            return redirect()->route('admin.profile-update-requests')
                ->with('error', 'Unable to load profile update request details.');
        }
    }

    /**
     * List GST update requests.
     */
    public function gstUpdateRequests(Request $request)
    {
        try {
            $admin = $this->getCurrentAdmin();

            $query = ApplicationGstUpdateRequest::with(['user', 'application', 'reviewedBy', 'gstVerification']);

            // Filter by status
            if ($request->filled('status')) {
                $query->where('status', $request->input('status'));
            }

            // Search functionality
            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('old_gstin', 'like', "%{$search}%")
                        ->orWhere('new_gstin', 'like', "%{$search}%")
                        ->orWhere('old_company_name', 'like', "%{$search}%")
                        ->orWhere('new_company_name', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('fullname', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->orWhere('registrationid', 'like', "%{$search}%");
                        })
                        ->orWhereHas('application', function ($appQuery) use ($search) {
                            $appQuery->where('application_id', 'like', "%{$search}%")
                                ->orWhere('membership_id', 'like', "%{$search}%");
                        });
                });
            }

            $requests = $query->latest()->paginate(20)->withQueryString();

            return view('admin.gst-update-requests.index', compact('admin', 'requests'));
        } catch (QueryException $e) {
            Log::error('Database error loading GST update requests: '.$e->getMessage());
            abort(503, 'Database connection error. Please try again later.');
        } catch (PDOException $e) {
            Log::error('PDO error loading GST update requests: '.$e->getMessage());
            abort(503, 'Database connection error. Please try again later.');
        } catch (Exception $e) {
            Log::error('Error loading GST update requests: '.$e->getMessage());

            return redirect()->route('admin.dashboard')
                ->with('error', 'Unable to load GST update requests. Please try again.');
        }
    }

    /**
     * Show GST update request details.
     */
    public function showGstUpdateRequest($id)
    {
        try {
            $admin = $this->getCurrentAdmin();

            $request = ApplicationGstUpdateRequest::with(['user', 'application', 'reviewedBy', 'gstVerification'])
                ->findOrFail($id);

            return view('admin.gst-update-requests.show', compact('admin', 'request'));
        } catch (Exception $e) {
            Log::error('Error loading GST update request details: '.$e->getMessage());

            return redirect()->route('admin.gst-update-requests')
                ->with('error', 'Unable to load GST update request details.');
        }
    }

    /**
     * Approve GST update request.
     */
    public function approveGstUpdateRequest(Request $request, $id)
    {
        try {
            $admin = $this->getCurrentAdmin();
            $adminId = $admin->id;

            $gstRequest = ApplicationGstUpdateRequest::with(['application', 'user'])->findOrFail($id);

            if ($gstRequest->status !== 'pending') {
                return back()->with('error', 'This request has already been processed.');
            }

            // Update application with new GST details
            $application = $gstRequest->application;
            $newKycDetails = $gstRequest->new_kyc_details ?? [];

            // Update GST verification ID if available
            if ($gstRequest->gst_verification_id) {
                $application->gst_verification_id = $gstRequest->gst_verification_id;

                // Ensure billing address is updated from new GST verification primary address
                $gstVerification = GstVerification::find($gstRequest->gst_verification_id);
                if ($gstVerification && $gstVerification->primary_address) {
                    $newKycDetails['billing_address'] = $gstVerification->primary_address;
                    $newKycDetails['billing_pincode'] = $gstVerification->pincode;
                }
            }

            // Remove primary_address from kyc_details if it exists (we only use billing_address)
            if (isset($newKycDetails['primary_address'])) {
                unset($newKycDetails['primary_address']);
            }

            if ($application->application_type === 'IRINN') {
                // IRINN portal: GST details live on application columns (no user KYC profile dependency).
                $gstVerification = null;
                if ($gstRequest->gst_verification_id) {
                    $gstVerification = GstVerification::find($gstRequest->gst_verification_id);
                }

                $application->irinn_has_gst_number = true;
                $application->irinn_billing_gstin = $gstRequest->new_gstin;
                $application->irinn_billing_legal_name = $newKycDetails['legal_name'] ?? null;
                $application->irinn_billing_pan = $gstVerification?->pan ?? null;
                $application->irinn_billing_address = $newKycDetails['billing_address'] ?? null;
                $application->irinn_billing_postcode = $newKycDetails['billing_pincode'] ?? null;
                $application->save();
            } else {
                // Replace all old GST data with new GST data in kyc_details
                $application->kyc_details = $newKycDetails;

                // Update application_data GSTIN - replace old with new
                $applicationData = is_array($application->application_data) ? $application->application_data : [];
                $applicationData['gstin'] = $gstRequest->new_gstin;
                $application->application_data = $applicationData;

                $application->save();
            }

            // Log GST change history
            ApplicationGstChangeHistory::log(
                $application->id,
                $gstRequest->user_id,
                $gstRequest->old_gstin,
                $gstRequest->new_gstin,
                $gstRequest->old_kyc_details,
                $newKycDetails,
                'admin',
                $adminId,
                'GST update approved by admin'
            );

            // Add to user-facing activity log (status history)
            \App\Models\ApplicationStatusHistory::log(
                $application->id,
                (string) ($application->status ?? ''),
                (string) ($application->status ?? ''),
                'admin',
                $adminId,
                "GST update approved: {$gstRequest->old_gstin} -> {$gstRequest->new_gstin}"
            );

            // Update request status
            $gstRequest->update([
                'status' => 'approved',
                'reviewed_by' => $adminId,
                'reviewed_at' => now('Asia/Kolkata'),
                'admin_notes' => $request->input('admin_notes'),
            ]);

            // Log admin action
            AdminAction::log(
                $adminId,
                'approved_gst_update',
                $gstRequest,
                "Approved GST update request for application: {$application->application_id}",
                ['application_id' => $application->id, 'user_id' => $gstRequest->user_id]
            );

            // Send message to user
            $this->createMessageForAdmin(
                $adminId,
                $gstRequest->user_id,
                'GST Update Approved',
                "Your GST update request for application {$application->application_id} has been approved."
            );

            return back()->with('success', 'GST update request approved successfully!');
        } catch (QueryException $e) {
            Log::error('Database error approving GST update: '.$e->getMessage());

            return back()->with('error', 'Database connection error. Please try again later.');
        } catch (PDOException $e) {
            Log::error('PDO error approving GST update: '.$e->getMessage());

            return back()->with('error', 'Database connection error. Please try again later.');
        } catch (Exception $e) {
            Log::error('Error approving GST update: '.$e->getMessage());

            return back()->with('error', 'An error occurred. Please try again.');
        }
    }

    /**
     * Reject GST update request.
     */
    public function rejectGstUpdateRequest(Request $request, $id)
    {
        try {
            $admin = $this->getCurrentAdmin();
            $adminId = $admin->id;

            $validated = $request->validate([
                'admin_notes' => 'required|string|min:10',
            ], [
                'admin_notes.required' => 'Please provide a reason for rejection.',
                'admin_notes.min' => 'Please provide more details (minimum 10 characters).',
            ]);

            $gstRequest = ApplicationGstUpdateRequest::with(['application', 'user'])->findOrFail($id);

            if ($gstRequest->status !== 'pending') {
                return back()->with('error', 'This request has already been processed.');
            }

            // Update request status
            $gstRequest->update([
                'status' => 'rejected',
                'reviewed_by' => $adminId,
                'reviewed_at' => now('Asia/Kolkata'),
                'admin_notes' => $validated['admin_notes'],
            ]);

            // Add to user-facing activity log (status history)
            \App\Models\ApplicationStatusHistory::log(
                $gstRequest->application->id,
                (string) ($gstRequest->application->status ?? ''),
                (string) ($gstRequest->application->status ?? ''),
                'admin',
                $adminId,
                "GST update rejected: {$gstRequest->old_gstin} -> {$gstRequest->new_gstin}"
            );

            // Log admin action
            AdminAction::log(
                $adminId,
                'rejected_gst_update',
                $gstRequest,
                "Rejected GST update request for application: {$gstRequest->application->application_id}",
                ['application_id' => $gstRequest->application->id, 'user_id' => $gstRequest->user_id]
            );

            // Send message to user
            $this->createMessageForAdmin(
                $adminId,
                $gstRequest->user_id,
                'GST Update Rejected',
                "Your GST update request for application {$gstRequest->application->application_id} has been rejected. Reason: {$validated['admin_notes']}"
            );

            return back()->with('success', 'GST update request rejected.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (QueryException $e) {
            Log::error('Database error rejecting GST update: '.$e->getMessage());

            return back()->with('error', 'Database connection error. Please try again later.');
        } catch (PDOException $e) {
            Log::error('PDO error rejecting GST update: '.$e->getMessage());

            return back()->with('error', 'Database connection error. Please try again later.');
        } catch (Exception $e) {
            Log::error('Error rejecting GST update: '.$e->getMessage());

            return back()->with('error', 'An error occurred. Please try again.');
        }
    }

    /**
     * Show update application form.
     */
    public function editApplication($id)
    {
        try {
            $application = Application::with(['user'])->findOrFail($id);
            $applicationData = $application->application_data ?? [];
            $documents = $applicationData['documents'] ?? [];

            return view('admin.applications.edit', compact('application', 'applicationData', 'documents'));
        } catch (Exception $e) {
            Log::error('Error loading application edit page: '.$e->getMessage());

            return redirect()->route('admin.applications')
                ->with('error', 'Application not found.');
        }
    }

    /**
     * Update application documents and details.
     */
    public function updateApplication(Request $request, $id)
    {
        try {
            $application = Application::findOrFail($id);
            $applicationData = $application->application_data ?? [];
            $existingDocuments = $applicationData['documents'] ?? [];

            // Validate document uploads
            $request->validate([
                'agreement_file' => 'nullable|file|mimes:pdf|max:10240',
                'license_isp_file' => 'nullable|file|mimes:pdf|max:10240',
                'license_vno_file' => 'nullable|file|mimes:pdf|max:10240',
                'cdn_declaration_file' => 'nullable|file|mimes:pdf|max:10240',
                'general_declaration_file' => 'nullable|file|mimes:pdf|max:10240',
                'whois_details_file' => 'nullable|file|mimes:pdf|max:10240',
                'pan_document_file' => 'nullable|file|mimes:pdf|max:10240',
                'gstin_document_file' => 'nullable|file|mimes:pdf|max:10240',
                'msme_document_file' => 'nullable|file|mimes:pdf|max:10240',
                'incorporation_document_file' => 'nullable|file|mimes:pdf|max:10240',
                'authorized_rep_document_file' => 'nullable|file|mimes:pdf|max:10240',
                // Allow updating application details
                'representative_name' => 'nullable|string|max:255',
                'representative_email' => 'nullable|email|max:255',
                'representative_mobile' => 'nullable|string|size:10|regex:/^[0-9]{10}$/',
                'gstin' => 'nullable|string|max:15',
                'port_capacity' => 'nullable|string|max:50',
                'billing_plan' => 'nullable|string|in:arc,mrc,quarterly',
                'ip_prefix_count' => 'nullable|integer|min:1|max:500',
                'asn_number' => 'nullable|string|max:50',
                'pre_peering_connectivity' => 'nullable|string|in:yes,no',
                'router_height_u' => 'nullable|string|max:50',
                'router_make_model' => 'nullable|string|max:255',
                'router_serial_number' => 'nullable|string|max:255',
                // Live application details (only for active applications)
                'service_activation_date' => 'nullable|date',
                'billing_cycle' => 'nullable|in:monthly,quarterly,annual',
                'assigned_port_capacity' => 'nullable|string|max:50',
                'assigned_ip' => 'nullable|string|max:255',
                'customer_id' => 'nullable|string|max:255',
                'membership_id' => 'nullable|string|max:255',
                'assigned_port_number' => 'nullable|string|max:255',
            ]);

            $documentFields = [
                'agreement_file',
                'license_isp_file',
                'license_vno_file',
                'cdn_declaration_file',
                'general_declaration_file',
                'whois_details_file',
                'pan_document_file',
                'gstin_document_file',
                'msme_document_file',
                'incorporation_document_file',
                'authorized_rep_document_file',
            ];

            $updatedDocuments = $existingDocuments;
            $storagePrefix = 'applications/'.$application->user_id.'/irin/'.now()->format('YmdHis');

            // Handle file uploads
            foreach ($documentFields as $field) {
                if ($request->hasFile($field)) {
                    $updatedDocuments[$field] = $request->file($field)
                        ->store($storagePrefix, 'public');
                }
            }

            // Update application data
            $updatedData = $applicationData;

            // Update documents
            $updatedData['documents'] = $updatedDocuments;

            // Update application details if provided
            if ($request->filled('representative_name')) {
                if (! isset($updatedData['representative'])) {
                    $updatedData['representative'] = [];
                }
                $updatedData['representative']['name'] = $request->input('representative_name');
            }

            if ($request->filled('representative_email')) {
                if (! isset($updatedData['representative'])) {
                    $updatedData['representative'] = [];
                }
                $updatedData['representative']['email'] = $request->input('representative_email');
            }

            if ($request->filled('representative_mobile')) {
                if (! isset($updatedData['representative'])) {
                    $updatedData['representative'] = [];
                }
                $updatedData['representative']['mobile'] = $request->input('representative_mobile');
            }

            // Handle GSTIN changes and verification
            $gstinChanged = false;
            $newGstin = null;
            $gstVerificationSuccess = false;
            $gstVerificationMessage = null;

            if ($request->filled('gstin')) {
                $newGstin = strtoupper(preg_replace('/[^A-Z0-9]/', '', $request->input('gstin')));
                $existingGstin = $applicationData['gstin'] ?? null;

                // Normalize both for comparison
                $normalizedNew = $newGstin;
                $normalizedExisting = $existingGstin ? strtoupper(preg_replace('/[^A-Z0-9]/', '', $existingGstin)) : null;

                // Check if GSTIN has changed - only verify if it's different
                if ($normalizedNew !== $normalizedExisting && strlen($normalizedNew) === 15) {
                    $gstinChanged = true;
                    $updatedData['gstin'] = $newGstin;

                    // Check if verification was already completed via button click
                    $gstVerificationId = $request->input('gst_verification_id');
                    $gstVerified = $request->input('gst_verified') === '1';

                    if ($gstVerificationId && $gstVerified) {
                        // Verification was already completed via button - just link it
                        $gstVerification = \App\Models\GstVerification::find($gstVerificationId);
                        if ($gstVerification && $gstVerification->user_id === $application->user_id && $gstVerification->is_verified) {
                            // Update application_data with verified GSTIN
                            $updatedData['gstin'] = $gstVerification->gstin;

                            $updateData['gst_verification_id'] = $gstVerification->id;
                            // kyc_details should already be updated via the button verification
                            $gstVerificationSuccess = true;
                            $gstVerificationMessage = 'GSTIN verified successfully.';
                        }
                    } else {
                        // Verification not done via button - initiate on form submit (fallback)
                        // Note: This should rarely happen now that we have the verify button
                        try {
                            $idfyService = new \App\Services\IdfyVerificationService;
                            $verifyResult = $idfyService->verifyGst($newGstin);
                            $requestId = $verifyResult['request_id'];

                            // Find or create GST verification record
                            $gstVerification = \App\Models\GstVerification::where('user_id', $application->user_id)
                                ->where('gstin', $newGstin)
                                ->first();

                            if (! $gstVerification) {
                                $gstVerification = \App\Models\GstVerification::create([
                                    'user_id' => $application->user_id,
                                    'gstin' => $newGstin,
                                    'request_id' => $requestId,
                                    'status' => 'in_progress',
                                    'is_verified' => false,
                                ]);
                            } else {
                                $gstVerification->update([
                                    'request_id' => $requestId,
                                    'status' => 'in_progress',
                                    'is_verified' => false,
                                    'error_message' => null,
                                ]);
                            }

                            // Update application with verification ID
                            $updateData['gst_verification_id'] = $gstVerification->id;

                            // Wait a bit for verification to process
                            sleep(3);

                            // Poll for verification status
                            $maxRetries = 5;
                            $retryCount = 0;
                            $statusResult = null;

                            while ($retryCount < $maxRetries) {
                                $statusResult = $idfyService->getTaskStatus($requestId);
                                $status = $statusResult['status'] ?? 'unknown';

                                if ($status === 'completed') {
                                    $result = $statusResult['result'] ?? null;
                                    $sourceOutput = $result['source_output'] ?? null;

                                    if ($sourceOutput) {
                                        $isVerified = ($sourceOutput['status'] ?? '') === 'id_found';

                                        if ($isVerified) {
                                            // Extract verification data
                                            $legalName = $sourceOutput['legal_name'] ?? null;
                                            $tradeName = $sourceOutput['trade_name'] ?? null;

                                            // Extract PAN from GSTIN
                                            $pan = null;
                                            $gstinFromResponse = $sourceOutput['gstin'] ?? $newGstin;
                                            if ($gstinFromResponse && strlen($gstinFromResponse) >= 10) {
                                                $pan = substr($gstinFromResponse, 2, 10);
                                            }

                                            // Extract state and address
                                            $state = null;
                                            $primaryAddress = null;
                                            $address = $sourceOutput['principal_place_of_business_fields']['principal_place_of_business_address'] ?? null;
                                            if ($address) {
                                                $state = $address['state_name'] ?? null;
                                                $pincode = $address['pincode'] ?? null;
                                                $addressParts = array_filter([
                                                    $address['door_number'] ?? null,
                                                    $address['building_name'] ?? null,
                                                    $address['street'] ?? null,
                                                    $address['location'] ?? null,
                                                    $address['city'] ?? null,
                                                    $address['dst'] ?? null,
                                                ]);
                                                $primaryAddress = implode(', ', $addressParts);

                                                // Append pincode to address if available
                                                if ($pincode && ! preg_match('/\b'.preg_quote($pincode, '/').'\b/', $primaryAddress)) {
                                                    $primaryAddress = trim($primaryAddress).' - '.$pincode;
                                                }
                                            }

                                            // Extract registration date
                                            $registrationDate = null;
                                            if (isset($sourceOutput['date_of_registration'])) {
                                                $registrationDate = date('Y-m-d', strtotime($sourceOutput['date_of_registration']));
                                            }

                                            $gstType = $sourceOutput['taxpayer_type'] ?? null;
                                            $companyStatus = $sourceOutput['gstin_status'] ?? 'Active';
                                            $constitutionOfBusiness = $sourceOutput['constitution_of_business'] ?? null;

                                            // Update verification record
                                            $gstVerification->update([
                                                'status' => 'completed',
                                                'is_verified' => true,
                                                'verification_data' => $result,
                                                'legal_name' => $legalName,
                                                'trade_name' => $tradeName,
                                                'pan' => $pan,
                                                'state' => $state,
                                                'registration_date' => $registrationDate,
                                                'gst_type' => $gstType,
                                                'company_status' => $companyStatus,
                                                'primary_address' => $primaryAddress,
                                                'constitution_of_business' => $constitutionOfBusiness,
                                            ]);

                                            // Update kyc_details in applications table
                                            $kycDetails = $application->kyc_details ?? [];
                                            $kycDetails['gstin'] = $newGstin;
                                            $kycDetails['gst_verified'] = true;
                                            $kycDetails['gst_verification_id'] = $gstVerification->id;
                                            $kycDetails['gst_legal_name'] = $legalName;
                                            $kycDetails['gst_trade_name'] = $tradeName;
                                            $kycDetails['gst_pan'] = $pan;
                                            $kycDetails['gst_state'] = $state;
                                            $kycDetails['gst_registration_date'] = $registrationDate;
                                            $kycDetails['gst_type'] = $gstType;
                                            $kycDetails['gst_company_status'] = $companyStatus;
                                            $kycDetails['gst_primary_address'] = $primaryAddress;
                                            $kycDetails['gst_verified_at'] = now('Asia/Kolkata')->toDateTimeString();

                                            // Update application_data with verified GSTIN
                                            $updatedData['gstin'] = $newGstin;

                                            $updateData['application_data'] = $updatedData;
                                            $updateData['kyc_details'] = $kycDetails;

                                            $gstVerificationSuccess = true;
                                            $gstVerificationMessage = 'GSTIN verified successfully. Please complete the KYC process.';

                                            Log::info('GST verification completed for application', [
                                                'application_id' => $application->id,
                                                'gstin' => $newGstin,
                                                'gst_verification_id' => $gstVerification->id,
                                            ]);
                                        } else {
                                            $errorMessage = $sourceOutput['message'] ?? 'GSTIN verification failed';

                                            $gstVerification->update([
                                                'status' => 'completed',
                                                'is_verified' => false,
                                                'verification_data' => $result,
                                                'error_message' => $errorMessage,
                                            ]);

                                            $gstVerificationMessage = 'GSTIN verification failed: '.$errorMessage;

                                            Log::warning('GST verification failed for application', [
                                                'application_id' => $application->id,
                                                'gstin' => $newGstin,
                                                'error' => $errorMessage,
                                            ]);
                                        }
                                    }
                                    break;
                                } elseif ($status === 'failed') {
                                    $errorMessage = 'GST verification request failed';

                                    $gstVerification->update([
                                        'status' => 'failed',
                                        'is_verified' => false,
                                        'error_message' => $errorMessage,
                                        'verification_data' => $statusResult,
                                    ]);

                                    $gstVerificationMessage = 'GST verification request failed. Please try again.';
                                    break;
                                }

                                sleep(1);
                                $retryCount++;
                            }

                            if ($retryCount >= $maxRetries && (! $statusResult || ($statusResult['status'] ?? '') !== 'completed')) {
                                $gstVerificationMessage = 'GST verification is in progress. Please check again later.';
                            }
                        } catch (\Exception $e) {
                            Log::error('Error verifying GST for application: '.$e->getMessage(), [
                                'application_id' => $application->id,
                                'gstin' => $newGstin,
                                'error' => $e->getMessage(),
                            ]);

                            $gstVerificationMessage = 'Error initiating GST verification: '.$e->getMessage();
                        }
                    } // End of else block for verification not done via button
                } else {
                    // GSTIN not changed - just update it without verification
                    $updatedData['gstin'] = $newGstin;
                }
            }

            if ($request->filled('port_capacity')) {
                if (! isset($updatedData['port_selection'])) {
                    $updatedData['port_selection'] = [];
                }
                $updatedData['port_selection']['capacity'] = $request->input('port_capacity');
            }

            if ($request->filled('billing_plan')) {
                if (! isset($updatedData['port_selection'])) {
                    $updatedData['port_selection'] = [];
                }
                $updatedData['port_selection']['billing_plan'] = $request->input('billing_plan');
            }

            if ($request->filled('ip_prefix_count')) {
                if (! isset($updatedData['ip_prefix'])) {
                    $updatedData['ip_prefix'] = [];
                }
                $updatedData['ip_prefix']['count'] = $request->input('ip_prefix_count');
            }

            // Update peering details
            if ($request->filled('asn_number') || $request->filled('pre_peering_connectivity')) {
                if (! isset($updatedData['peering'])) {
                    $updatedData['peering'] = [];
                }
                if ($request->filled('asn_number')) {
                    $updatedData['peering']['asn_number'] = $request->input('asn_number');
                }
                if ($request->filled('pre_peering_connectivity')) {
                    $updatedData['peering']['pre_nixi_connectivity'] = $request->input('pre_peering_connectivity');
                }
            }

            // Update router details
            if ($request->filled('router_height_u') || $request->filled('router_make_model') || $request->filled('router_serial_number')) {
                if (! isset($updatedData['router_details'])) {
                    $updatedData['router_details'] = [];
                }
                if ($request->filled('router_height_u')) {
                    $updatedData['router_details']['height_u'] = $request->input('router_height_u');
                }
                if ($request->filled('router_make_model')) {
                    $updatedData['router_details']['make_model'] = $request->input('router_make_model');
                }
                if ($request->filled('router_serial_number')) {
                    $updatedData['router_details']['serial_number'] = $request->input('router_serial_number');
                }
            }

            // Prepare update data
            $updateData = [
                'application_data' => $updatedData,
            ];

            // Update live application details if application is active
            if ($application->is_active && $application->application_type === 'IRINN') {
                if ($request->filled('service_activation_date')) {
                    $updateData['service_activation_date'] = $request->input('service_activation_date');
                }
                if ($request->filled('billing_cycle')) {
                    $updateData['billing_cycle'] = $request->input('billing_cycle');
                }
                if ($request->filled('assigned_port_capacity')) {
                    $updateData['assigned_port_capacity'] = $request->input('assigned_port_capacity');
                }
                if ($request->filled('assigned_ip')) {
                    $updateData['assigned_ip'] = $request->input('assigned_ip');
                }
                if ($request->filled('customer_id')) {
                    $updateData['customer_id'] = $request->input('customer_id');
                }
                if ($request->filled('membership_id')) {
                    $updateData['membership_id'] = $request->input('membership_id');
                }
                if ($request->filled('assigned_port_number')) {
                    $updateData['assigned_port_number'] = $request->input('assigned_port_number');
                }
            }

            // Update application
            $application->update($updateData);

            // Prepare success message
            $successMessage = 'Application updated successfully!';
            if ($gstinChanged && $gstVerificationMessage) {
                if ($gstVerificationSuccess) {
                    $successMessage .= ' '.$gstVerificationMessage;
                } else {
                    $successMessage .= ' Note: '.$gstVerificationMessage;
                }
            }

            return redirect()->route('admin.applications.show', $id)
                ->with('success', $successMessage);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (Exception $e) {
            Log::error('Error updating application: '.$e->getMessage());

            return back()->with('error', 'Unable to update application. Please try again.');
        }
    }

    /**
     * Comprehensive update method - allows updating any field without validation.
     * This method handles user-friendly form inputs and converts them to JSON format.
     */
    public function updateApplicationComprehensive(Request $request, $id)
    {
        try {
            $admin = $this->getCurrentAdmin();
            $application = Application::findOrFail($id);

            // Get existing data
            $applicationData = $application->application_data ?? [];
            $kycDetails = $application->kyc_details ?? [];
            $registrationDetails = $application->registration_details ?? [];
            $authorizedRepresentativeDetails = $application->authorized_representative_details ?? [];
            $updateData = [];

            // Regular columns that can be updated directly
            $regularColumns = [
                'application_id', 'pan_card_no', 'status', 'current_stage',
                'submitted_at', 'approved_at', 'membership_id', 'customer_id',
                'assigned_ip', 'assigned_port_number', 'assigned_port_capacity',
                'service_activation_date', 'billing_cycle', 'is_active',
                'resubmission_query', 'rejection_reason',
            ];

            // Process regular columns
            foreach ($regularColumns as $column) {
                if ($request->has($column)) {
                    $value = $request->input($column);
                    if ($value !== null && $value !== '') {
                        // Handle date/datetime fields
                        if (in_array($column, ['submitted_at', 'approved_at', 'service_activation_date'])) {
                            $updateData[$column] = $value ? \Carbon\Carbon::parse($value) : null;
                        } elseif ($column === 'is_active') {
                            $updateData[$column] = (bool) $value;
                        } else {
                            $updateData[$column] = $value;
                        }
                    }
                }
            }

            // Process registration_details (array format)
            if ($request->has('registration_details') && is_array($request->input('registration_details'))) {
                $registrationDetails = array_merge($registrationDetails, $request->input('registration_details'));
            }

            // Process kyc_details (array format)
            if ($request->has('kyc_details') && is_array($request->input('kyc_details'))) {
                $kycInput = $request->input('kyc_details');
                foreach ($kycInput as $key => $value) {
                    // Handle boolean values
                    if ($value === '1' || $value === '0') {
                        $kycDetails[$key] = (bool) $value;
                    } elseif ($key === 'billing_address') {
                        // Billing address should always be a string, not JSON
                        $kycDetails[$key] = $value !== '' ? (string) $value : null;
                    } elseif ($key === 'billing_pincode') {
                        // Billing pincode should be a string
                        $kycDetails[$key] = $value !== '' ? (string) $value : null;
                    } elseif (is_array($value)) {
                        // Nested arrays (for other fields)
                        $kycDetails[$key] = array_merge($kycDetails[$key] ?? [], $value);
                    } else {
                        // Regular string values
                        $kycDetails[$key] = $value !== '' ? $value : null;
                    }
                }
            }

            // Process authorized_representative_details (array format)
            if ($request->has('authorized_representative_details') && is_array($request->input('authorized_representative_details'))) {
                $authorizedRepresentativeDetails = array_merge($authorizedRepresentativeDetails, $request->input('authorized_representative_details'));
            }

            // Process application_data (nested array format)
            if ($request->has('application_data') && is_array($request->input('application_data'))) {
                $appDataInput = $request->input('application_data');

                // Merge location data
                if (isset($appDataInput['location']) && is_array($appDataInput['location'])) {
                    $applicationData['location'] = array_merge($applicationData['location'] ?? [], $appDataInput['location']);
                }

                // Merge port_selection data
                if (isset($appDataInput['port_selection']) && is_array($appDataInput['port_selection'])) {
                    $applicationData['port_selection'] = array_merge($applicationData['port_selection'] ?? [], $appDataInput['port_selection']);
                }

                // Merge ip_prefix data
                if (isset($appDataInput['ip_prefix']) && is_array($appDataInput['ip_prefix'])) {
                    $applicationData['ip_prefix'] = array_merge($applicationData['ip_prefix'] ?? [], $appDataInput['ip_prefix']);
                }

                // Merge peering data
                if (isset($appDataInput['peering']) && is_array($appDataInput['peering'])) {
                    $applicationData['peering'] = array_merge($applicationData['peering'] ?? [], $appDataInput['peering']);
                }

                // Merge router_details data
                if (isset($appDataInput['router_details']) && is_array($appDataInput['router_details'])) {
                    $applicationData['router_details'] = array_merge($applicationData['router_details'] ?? [], $appDataInput['router_details']);
                }

                // Handle simple fields
                if (isset($appDataInput['member_type'])) {
                    $applicationData['member_type'] = $appDataInput['member_type'];
                }
                if (isset($appDataInput['gstin'])) {
                    $applicationData['gstin'] = $appDataInput['gstin'];
                }

                // Merge payment data
                if (isset($appDataInput['payment']) && is_array($appDataInput['payment'])) {
                    $applicationData['payment'] = array_merge($applicationData['payment'] ?? [], $appDataInput['payment']);
                    // Convert date strings to proper format
                    foreach (['paid_at', 'declaration_confirmed_at'] as $dateField) {
                        if (isset($applicationData['payment'][$dateField]) && $applicationData['payment'][$dateField]) {
                            try {
                                $applicationData['payment'][$dateField] = \Carbon\Carbon::parse($applicationData['payment'][$dateField])->format('Y-m-d H:i:s');
                            } catch (\Exception $e) {
                                // Keep original value if parsing fails
                            }
                        }
                    }
                }
            }

            // Handle document uploads
            if ($request->hasFile('documents')) {
                $documents = $applicationData['documents'] ?? [];
                $uploadedDocs = $request->file('documents');

                foreach ($uploadedDocs as $key => $file) {
                    if ($file && $file->isValid()) {
                        // Generate storage path
                        $storagePath = 'applications/'.$application->id.'/ix/'.date('YmdHis');
                        $fileName = 'NIXI-IX-'.$application->user_id.'-'.$key.'.'.$file->getClientOriginalExtension();

                        // Store file
                        $storedPath = $file->storeAs($storagePath, $fileName, 'public');
                        $documents[$key] = $storedPath;
                    }
                }

                $applicationData['documents'] = $documents;
            }

            // Update JSON columns
            $updateData['application_data'] = $applicationData;
            $updateData['kyc_details'] = $kycDetails;
            $updateData['registration_details'] = $registrationDetails;
            $updateData['authorized_representative_details'] = $authorizedRepresentativeDetails;

            // Update the application
            $application->update($updateData);

            // Log admin action
            AdminAction::log(
                $admin->id,
                'updated_application',
                $application,
                "Comprehensively updated application {$application->application_id}",
                ['user_id' => $application->user_id]
            );

            return redirect()->route('admin.applications.show-comprehensive', $id)
                ->with('success', 'Application updated successfully!');
        } catch (Exception $e) {
            Log::error('Error comprehensively updating application: '.$e->getMessage(), [
                'application_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Unable to update application: '.$e->getMessage())->withInput();
        }
    }

    /**
     * Verify GST for application (AJAX endpoint).
     */
    public function verifyGstForApplication(Request $request, $id): JsonResponse
    {
        try {
            $application = Application::findOrFail($id);
            $admin = $this->getCurrentAdmin();

            $request->validate([
                'gstin' => 'required|string|size:15|regex:/^[0-9A-Z]{15}$/',
            ]);

            $gstin = strtoupper($request->input('gstin'));

            $service = new \App\Services\IdfyVerificationService;
            $result = $service->verifyGst($gstin);

            // Find or create verification record for this user
            $verification = \App\Models\GstVerification::where('user_id', $application->user_id)
                ->where('gstin', $gstin)
                ->first();

            if (! $verification) {
                $verification = \App\Models\GstVerification::create([
                    'user_id' => $application->user_id,
                    'gstin' => $gstin,
                    'request_id' => $result['request_id'],
                    'status' => 'in_progress',
                    'is_verified' => false,
                ]);
            } else {
                $verification->update([
                    'request_id' => $result['request_id'],
                    'status' => 'in_progress',
                    'is_verified' => false,
                    'error_message' => null,
                ]);
            }

            Log::info('Admin initiated GST verification for application', [
                'application_id' => $application->id,
                'admin_id' => $admin->id,
                'gstin' => $gstin,
                'verification_id' => $verification->id,
            ]);

            return response()->json([
                'success' => true,
                'request_id' => $result['request_id'],
                'verification_id' => $verification->id,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: '.implode(', ', $e->errors()['gstin'] ?? ['Invalid GSTIN format']),
            ], 422);
        } catch (Exception $e) {
            Log::error('Error verifying GST for application: '.$e->getMessage(), [
                'application_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to initiate GST verification: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check GST verification status (AJAX endpoint).
     */
    public function checkGstVerificationStatus(Request $request, $id): JsonResponse
    {
        try {
            $application = Application::findOrFail($id);

            $request->validate([
                'verification_id' => 'required|integer|exists:gst_verifications,id',
            ]);

            $verification = \App\Models\GstVerification::findOrFail($request->input('verification_id'));

            // Verify this verification belongs to the application's user
            if ($verification->user_id !== $application->user_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Verification does not belong to this application.',
                ], 403);
            }

            // If already verified, return immediately
            if ($verification->is_verified && $verification->status === 'completed') {
                // Update application kyc_details if not already done
                $kycDetails = $application->kyc_details ?? [];
                if (! isset($kycDetails['gst_verified']) || ! $kycDetails['gst_verified']) {
                    $kycDetails['gstin'] = $verification->gstin;
                    $kycDetails['gst_verified'] = true;
                    $kycDetails['gst_verification_id'] = $verification->id;
                    $kycDetails['gst_legal_name'] = $verification->legal_name;
                    $kycDetails['gst_trade_name'] = $verification->trade_name;
                    $kycDetails['gst_pan'] = $verification->pan;
                    $kycDetails['gst_state'] = $verification->state;
                    $kycDetails['gst_registration_date'] = $verification->registration_date?->format('Y-m-d');
                    $kycDetails['gst_type'] = $verification->gst_type;
                    $kycDetails['gst_company_status'] = $verification->company_status;
                    $kycDetails['gst_verified_at'] = now('Asia/Kolkata')->toDateTimeString();

                    // Update application_data with verified GSTIN
                    $applicationData = $application->application_data ?? [];
                    $applicationData['gstin'] = $verification->gstin;

                    $application->update([
                        'application_data' => $applicationData,
                        'kyc_details' => $kycDetails,
                        'gst_verification_id' => $verification->id,
                    ]);
                }

                return response()->json([
                    'success' => true,
                    'status' => 'completed',
                    'is_verified' => true,
                    'message' => 'GSTIN verified successfully. Please complete the KYC process.',
                ]);
            }

            // Check status with Idfy API
            $service = new \App\Services\IdfyVerificationService;
            $statusResult = $service->getTaskStatus($verification->request_id);

            if ($statusResult['status'] === 'completed') {
                $result = $statusResult['result'];
                $sourceOutput = $result['source_output'] ?? null;

                if ($sourceOutput) {
                    $isVerified = ($sourceOutput['status'] ?? '') === 'id_found';

                    if ($isVerified) {
                        // Extract and update verification data
                        $updateData = [
                            'status' => 'completed',
                            'is_verified' => true,
                            'verification_data' => $result,
                            'legal_name' => $sourceOutput['legal_name'] ?? null,
                            'trade_name' => $sourceOutput['trade_name'] ?? null,
                        ];

                        // Extract PAN from GSTIN
                        $gstinFromResponse = $sourceOutput['gstin'] ?? $verification->gstin;
                        if ($gstinFromResponse && strlen($gstinFromResponse) >= 10) {
                            $updateData['pan'] = substr($gstinFromResponse, 2, 10);
                        }

                        // Extract state and address
                        $address = $sourceOutput['principal_place_of_business_fields']['principal_place_of_business_address'] ?? null;
                        if ($address) {
                            $updateData['state'] = $address['state_name'] ?? null;
                            $addressParts = array_filter([
                                $address['door_number'] ?? null,
                                $address['building_name'] ?? null,
                                $address['street'] ?? null,
                                $address['location'] ?? null,
                                $address['city'] ?? null,
                                $address['dst'] ?? null,
                            ]);
                            $updateData['primary_address'] = implode(', ', $addressParts);
                        }

                        if (isset($sourceOutput['date_of_registration'])) {
                            $updateData['registration_date'] = date('Y-m-d', strtotime($sourceOutput['date_of_registration']));
                        }

                        $updateData['gst_type'] = $sourceOutput['taxpayer_type'] ?? null;
                        $updateData['company_status'] = $sourceOutput['gstin_status'] ?? null;
                        $updateData['constitution_of_business'] = $sourceOutput['constitution_of_business'] ?? null;

                        $verification->update($updateData);

                        // Update application kyc_details
                        $kycDetails = $application->kyc_details ?? [];
                        $kycDetails['gstin'] = $verification->gstin;
                        $kycDetails['gst_verified'] = true;
                        $kycDetails['gst_verification_id'] = $verification->id;
                        $kycDetails['gst_legal_name'] = $verification->legal_name;
                        $kycDetails['gst_trade_name'] = $verification->trade_name;
                        $kycDetails['gst_pan'] = $verification->pan;
                        $kycDetails['gst_state'] = $verification->state;
                        $kycDetails['gst_registration_date'] = $verification->registration_date?->format('Y-m-d');
                        $kycDetails['gst_type'] = $verification->gst_type;
                        $kycDetails['gst_company_status'] = $verification->company_status;
                        $kycDetails['gst_verified_at'] = now('Asia/Kolkata')->toDateTimeString();

                        // Update application_data with verified GSTIN
                        $applicationData = $application->application_data ?? [];
                        $applicationData['gstin'] = $verification->gstin;

                        $application->update([
                            'application_data' => $applicationData,
                            'kyc_details' => $kycDetails,
                            'gst_verification_id' => $verification->id,
                        ]);

                        Log::info('GST verification completed for application', [
                            'application_id' => $application->id,
                            'gstin' => $verification->gstin,
                            'verification_id' => $verification->id,
                        ]);

                        return response()->json([
                            'success' => true,
                            'status' => 'completed',
                            'is_verified' => true,
                            'message' => 'GSTIN verified successfully. Please complete the KYC process.',
                        ]);
                    } else {
                        $errorMessage = $sourceOutput['message'] ?? 'GSTIN verification failed';

                        $verification->update([
                            'status' => 'completed',
                            'is_verified' => false,
                            'verification_data' => $result,
                            'error_message' => $errorMessage,
                        ]);

                        return response()->json([
                            'success' => false,
                            'status' => 'completed',
                            'is_verified' => false,
                            'message' => $errorMessage,
                        ]);
                    }
                }
            } elseif ($statusResult['status'] === 'failed') {
                $verification->update([
                    'status' => 'failed',
                    'is_verified' => false,
                    'error_message' => 'GST verification request failed',
                ]);

                return response()->json([
                    'success' => false,
                    'status' => 'failed',
                    'is_verified' => false,
                    'message' => 'GST verification request failed',
                ]);
            }

            return response()->json([
                'success' => true,
                'status' => $statusResult['status'] ?? 'in_progress',
                'is_verified' => false,
                'message' => 'Verification in progress. Please wait...',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: '.implode(', ', $e->errors()['verification_id'] ?? ['Invalid verification ID']),
            ], 422);
        } catch (Exception $e) {
            Log::error('Error checking GST verification status: '.$e->getMessage(), [
                'application_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while checking verification status.',
            ], 500);
        }
    }

    /**
     * Complete KYC for application (AJAX endpoint).
     */
    public function completeKycForApplication(Request $request, $id): JsonResponse
    {
        try {
            $application = Application::findOrFail($id);
            $admin = $this->getCurrentAdmin();

            // Verify GST verification exists and is verified
            if (! $application->gst_verification_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'GST verification is required before completing KYC.',
                ], 400);
            }

            $gstVerification = \App\Models\GstVerification::find($application->gst_verification_id);
            if (! $gstVerification || ! $gstVerification->is_verified) {
                return response()->json([
                    'success' => false,
                    'message' => 'GST verification must be completed before finishing KYC.',
                ], 400);
            }

            // Get current kyc_details and application_data
            $kycDetails = $application->kyc_details ?? [];
            $applicationData = $application->application_data ?? [];

            // Update GST verification details - include all fields
            $kycDetails['gstin'] = $gstVerification->gstin;
            $kycDetails['gst_verified'] = true;
            $kycDetails['gst_verification_id'] = $gstVerification->id;
            $kycDetails['gst_legal_name'] = $gstVerification->legal_name;
            $kycDetails['gst_trade_name'] = $gstVerification->trade_name;
            $kycDetails['gst_pan'] = $gstVerification->pan;
            $kycDetails['gst_state'] = $gstVerification->state;
            $kycDetails['gst_registration_date'] = $gstVerification->registration_date?->format('Y-m-d');
            $kycDetails['gst_type'] = $gstVerification->gst_type;
            $kycDetails['gst_company_status'] = $gstVerification->company_status;
            // Include GST primary address with pincode if available
            $gstPrimaryAddress = $gstVerification->primary_address;
            if ($gstPrimaryAddress) {
                // Try to extract or append pincode from verification data if available
                $verificationData = $gstVerification->verification_data ?? [];
                // Handle both formats: source_output directly or nested under result
                $sourceOutput = $verificationData['source_output'] ?? $verificationData['result']['source_output'] ?? [];
                $addressFields = $sourceOutput['principal_place_of_business_fields']['principal_place_of_business_address'] ?? [];
                $gstPincode = $addressFields['pincode'] ?? null;

                if ($gstPincode && ! preg_match('/\b'.preg_quote($gstPincode, '/').'\b/', $gstPrimaryAddress)) {
                    $gstPrimaryAddress = trim($gstPrimaryAddress).' - '.$gstPincode;
                }
            }
            $kycDetails['gst_primary_address'] = $gstPrimaryAddress;
            $kycDetails['gst_constitution_of_business'] = $gstVerification->constitution_of_business;
            $kycDetails['gst_verified_at'] = now('Asia/Kolkata')->toDateTimeString();

            // Include full GST verification data if available
            if ($gstVerification->verification_data) {
                $kycDetails['gst_verification_data'] = $gstVerification->verification_data;
            }

            // Include UDYAM verification details if available
            if ($application->udyam_verification_id) {
                $udyamVerification = \App\Models\UdyamVerification::find($application->udyam_verification_id);
                if ($udyamVerification) {
                    $kycDetails['udyam_number'] = $udyamVerification->udyam_number;
                    $kycDetails['udyam_verified'] = $udyamVerification->is_verified;
                    $kycDetails['udyam_verification_id'] = $udyamVerification->id;
                    $kycDetails['udyam_enterprise_name'] = $udyamVerification->enterprise_name ?? null;
                    $kycDetails['udyam_enterprise_type'] = $udyamVerification->enterprise_type ?? null;
                    $kycDetails['udyam_registration_date'] = $udyamVerification->registration_date?->format('Y-m-d');
                    if ($udyamVerification->verification_data) {
                        $kycDetails['udyam_verification_data'] = $udyamVerification->verification_data;
                    }
                }
            }

            // Include MCA verification details if available
            if ($application->mca_verification_id) {
                $mcaVerification = \App\Models\McaVerification::find($application->mca_verification_id);
                if ($mcaVerification) {
                    $kycDetails['cin'] = $mcaVerification->cin;
                    $kycDetails['mca_verified'] = $mcaVerification->is_verified;
                    $kycDetails['mca_verification_id'] = $mcaVerification->id;
                    $kycDetails['mca_company_name'] = $mcaVerification->company_name ?? null;
                    $kycDetails['mca_registration_date'] = $mcaVerification->registration_date?->format('Y-m-d');
                    $kycDetails['mca_company_status'] = $mcaVerification->company_status ?? null;
                    if ($mcaVerification->verification_data) {
                        $kycDetails['mca_verification_data'] = $mcaVerification->verification_data;
                    }
                }
            }

            // Include ROC IEC verification details if available
            if ($application->roc_iec_verification_id) {
                $rocIecVerification = \App\Models\RocIecVerification::find($application->roc_iec_verification_id);
                if ($rocIecVerification) {
                    $kycDetails['roc_iec_number'] = $rocIecVerification->import_export_code;
                    $kycDetails['roc_iec_verified'] = $rocIecVerification->is_verified;
                    $kycDetails['roc_iec_verification_id'] = $rocIecVerification->id;
                    if ($rocIecVerification->verification_data) {
                        $kycDetails['roc_iec_verification_data'] = $rocIecVerification->verification_data;
                    }
                }
            }

            // Include representative/contact details from application_data
            $representative = $applicationData['representative'] ?? [];
            if (! empty($representative)) {
                $kycDetails['contact_name'] = $representative['name'] ?? null;
                $kycDetails['contact_email'] = $representative['email'] ?? null;
                $kycDetails['contact_mobile'] = $representative['mobile'] ?? null;
                $kycDetails['contact_pan'] = $representative['pan'] ?? null;
                $kycDetails['contact_dob'] = $representative['dob'] ?? null;
            }

            // Include authorized representative details if available
            $authorizedRepresentative = $application->authorized_representative_details ?? [];
            if (! empty($authorizedRepresentative)) {
                $kycDetails['authorized_representative_name'] = $authorizedRepresentative['name'] ?? null;
                $kycDetails['authorized_representative_email'] = $authorizedRepresentative['email'] ?? null;
                $kycDetails['authorized_representative_mobile'] = $authorizedRepresentative['mobile'] ?? null;
                $kycDetails['authorized_representative_pan'] = $authorizedRepresentative['pan'] ?? null;
                $kycDetails['authorized_representative_dob'] = $authorizedRepresentative['dob'] ?? null;
            }

            // Include billing address - ALWAYS use GST verified primary address when available (most accurate)
            $billingAddress = null;
            $pincode = null;

            // Extract pincode and address from GST verification data (this is the verified, accurate address)
            if ($gstVerification) {
                // Use GST primary address first (this is from the verified GST data)
                if ($gstVerification->primary_address) {
                    $billingAddress = $gstVerification->primary_address;
                }

                // Extract pincode from GST verification data
                if ($gstVerification->verification_data) {
                    $verificationData = $gstVerification->verification_data;
                    $sourceOutput = $verificationData['source_output'] ?? $verificationData['result']['source_output'] ?? [];
                    $addressFields = $sourceOutput['principal_place_of_business_fields']['principal_place_of_business_address'] ?? [];
                    $pincode = $addressFields['pincode'] ?? null;
                }
            }

            // Only use UserKycProfile or application_data if GST verified address is not available
            if (! $billingAddress) {
                // Try to get from UserKycProfile
                $userKycProfile = \App\Models\UserKycProfile::where('user_id', $application->user_id)
                    ->where('status', 'completed')
                    ->latest()
                    ->first();

                if ($userKycProfile && $userKycProfile->billing_address) {
                    $billingAddress = $userKycProfile->billing_address;
                } elseif (isset($applicationData['billing_address'])) {
                    // Fallback to application_data
                    $billingAddress = $applicationData['billing_address'];
                } else {
                    // Try from location in application_data
                    $location = $applicationData['location'] ?? [];
                    $billingAddress = $location['address'] ?? $location['billing_address'] ?? null;
                }
            }

            // Parse billing_address if it's a JSON string
            if ($billingAddress && is_string($billingAddress)) {
                $decoded = json_decode($billingAddress, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    // Extract the actual address from JSON - try 'address' field first, then 'label'
                    if (isset($decoded['address']) && ! empty($decoded['address'])) {
                        $billingAddress = $decoded['address'];
                    } elseif (isset($decoded['label']) && ! empty($decoded['label'])) {
                        $billingAddress = $decoded['label'];
                    } else {
                        // Fallback: use original if address field not found
                        $billingAddress = $billingAddress;
                    }
                }
            }

            // If pincode not found yet, try to extract from billing address itself
            if (! $pincode && $billingAddress) {
                // Try to extract 6-digit pincode from address
                if (preg_match('/\b(\d{6})\b/', $billingAddress, $matches)) {
                    $pincode = $matches[1];
                }
            }

            // If still no pincode, try to get from GST primary address
            if (! $pincode && $gstVerification && $gstVerification->primary_address) {
                if (preg_match('/\b(\d{6})\b/', $gstVerification->primary_address, $matches)) {
                    $pincode = $matches[1];
                }
            }

            // Append pincode to address if not already included and pincode exists
            if ($billingAddress && $pincode) {
                // Check if pincode is already in the address
                if (! preg_match('/\b'.preg_quote($pincode, '/').'\b/', $billingAddress)) {
                    $billingAddress = trim($billingAddress).' - '.$pincode;
                }
            }

            // Store billing address with pincode in kyc_details
            // Ensure we always have an address - use GST primary address if billing address is empty
            if (! $billingAddress && $gstVerification && $gstVerification->primary_address) {
                $billingAddress = $gstVerification->primary_address;
                // Extract pincode from GST verification data if not already extracted
                if (! $pincode && $gstVerification->verification_data) {
                    $verificationData = $gstVerification->verification_data;
                    $sourceOutput = $verificationData['source_output'] ?? $verificationData['result']['source_output'] ?? [];
                    $addressFields = $sourceOutput['principal_place_of_business_fields']['principal_place_of_business_address'] ?? [];
                    $pincode = $addressFields['pincode'] ?? null;
                }
                // If still no pincode, try to extract from GST primary address
                if (! $pincode && preg_match('/\b(\d{6})\b/', $billingAddress, $matches)) {
                    $pincode = $matches[1];
                }
            }

            if ($billingAddress) {
                $kycDetails['billing_address'] = $billingAddress;
                $kycDetails['billing_pincode'] = $pincode;

                // Also extract city and state if available from location or GST data
                $location = $applicationData['location'] ?? [];
                if (! empty($location)) {
                    $kycDetails['billing_city'] = $location['city'] ?? null;
                    $kycDetails['billing_state'] = $location['state'] ?? null;
                }

                // If city/state not found, try from GST verification data
                if (empty($kycDetails['billing_city']) && $gstVerification && $gstVerification->verification_data) {
                    $verificationData = $gstVerification->verification_data;
                    $sourceOutput = $verificationData['source_output'] ?? $verificationData['result']['source_output'] ?? [];
                    $addressFields = $sourceOutput['principal_place_of_business_fields']['principal_place_of_business_address'] ?? [];
                    if (empty($kycDetails['billing_city'])) {
                        $kycDetails['billing_city'] = $addressFields['city'] ?? $addressFields['dst'] ?? null;
                    }
                    if (empty($kycDetails['billing_state'])) {
                        $kycDetails['billing_state'] = $addressFields['state_name'] ?? $gstVerification->state ?? null;
                    }
                }
            }

            // Include user information
            $user = $application->user;
            if ($user) {
                $kycDetails['user_name'] = $user->fullname;
                $kycDetails['user_email'] = $user->email;
                $kycDetails['user_mobile'] = $user->mobile;
            }

            // Mark KYC as completed with metadata
            $kycDetails['status'] = 'completed';
            $kycDetails['completed_at'] = now('Asia/Kolkata')->toDateTimeString();
            $kycDetails['completed_by'] = 'admin';
            $kycDetails['completed_by_admin_id'] = $admin->id;
            $kycDetails['completed_by_admin_name'] = $admin->name ?? $admin->email;

            // Update application_data with verified GSTIN
            $applicationData['gstin'] = $gstVerification->gstin;

            // Update application
            $application->update([
                'application_data' => $applicationData,
                'kyc_details' => $kycDetails,
            ]);

            Log::info('KYC completed for application by admin', [
                'application_id' => $application->id,
                'admin_id' => $admin->id,
                'gst_verification_id' => $gstVerification->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'KYC completed successfully!',
            ]);
        } catch (\Exception $e) {
            Log::error('Error completing KYC for application: '.$e->getMessage(), [
                'application_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to complete KYC: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download invoice PDF or credit note PDF.
     * Query parameter 'type' can be 'invoice' or 'credit_note' to specify which PDF to download.
     * For cancelled invoices, pdf_path points to the -C.pdf file.
     * If type is not specified and credit note exists, defaults to credit note for backward compatibility.
     */
    public function downloadInvoice($invoiceId, Request $request)
    {
        try {
            $admin = $this->getCurrentAdmin();

            $invoice = Invoice::with(['application'])
                ->whereHas('application', function ($q) {
                    $q->where('application_type', 'IRINN');
                })
                ->findOrFail($invoiceId);

            $downloadType = $request->query('type', 'auto'); // 'invoice', 'credit_note', or 'auto'

            // If credit note exists and type is 'credit_note' or 'auto', serve credit note
            if (($downloadType === 'credit_note' || $downloadType === 'auto') && $invoice->hasCreditNote()) {
                // Ensure credit note PDF exists (generate if missing)
                $this->ensureCreditNotePdfExists($invoice->application, $invoice);

                if ($invoice->credit_note_pdf_path && Storage::disk('public')->exists($invoice->credit_note_pdf_path)) {
                    $filePath = Storage::disk('public')->path($invoice->credit_note_pdf_path);
                    // Credit note filename: invoice_number + "C.pdf" (e.g., NIXIEX2526-2292C.pdf)
                    $safeFilename = str_replace(['/', '\\'], '-', $invoice->invoice_number).'C.pdf';

                    return response()->download($filePath, $safeFilename);
                }

                return back()->with('error', 'Credit note PDF not found and could not be generated.');
            }

            // Serve invoice PDF (original or cancelled version)
            if ($downloadType === 'invoice' || ($downloadType === 'auto' && ! $invoice->hasCreditNote())) {
                $this->ensureInvoicePdfExists($invoice->application, $invoice);

                $safeFilename = str_replace(['/', '\\'], '-', $invoice->invoice_number).'_invoice.pdf';
                if ($invoice->pdf_path && Storage::disk('public')->exists($invoice->pdf_path)) {
                    $filePath = Storage::disk('public')->path($invoice->pdf_path);

                    return response()->download($filePath, $safeFilename);
                }

                return back()->with('error', 'Invoice PDF not found. It may not have been generated yet.');
            }

            return back()->with('error', 'Invalid download type specified.');
        } catch (Exception $e) {
            Log::error('Error downloading invoice PDF: '.$e->getMessage());

            return back()->with('error', 'Unable to download invoice PDF.');
        }
    }

    /**
     * Ensure invoice PDF exists on storage (IRINN only).
     */
    public function ensureInvoicePdfExists(Application $application, Invoice $invoice): void
    {
        if ($application->application_type !== 'IRINN') {
            Log::warning('ensureInvoicePdfExists skipped: non-IRINN application', ['application_id' => $application->id]);

            return;
        }

        if ($invoice->pdf_path && Storage::disk('public')->exists($invoice->pdf_path)) {
            return;
        }

        $purpose = (string) ($invoice->invoice_purpose ?? '');

        if ($purpose === IrinnAnnualInvoiceService::INVOICE_PURPOSE) {
            app(IrinnAnnualInvoiceService::class)->generateAndStorePdf($application, $invoice);

            return;
        }

        $user = $application->user;
        $pdf = Pdf::loadView('invoices.reactivation-pdf', [
            'invoice' => $invoice,
            'user' => $user,
        ])->setPaper('a4', 'portrait')
            ->setOption('margin-top', 8)
            ->setOption('margin-bottom', 8)
            ->setOption('margin-left', 8)
            ->setOption('margin-right', 8)
            ->setOption('enable-local-file-access', true);

        $suffix = $purpose === 'reactivation' ? '_reactivation' : '_invoice';
        $safeName = str_replace(['/', '\\'], '-', $invoice->invoice_number).$suffix.'.pdf';
        $path = 'applications/'.$application->user_id.'/irin/'.$safeName;
        Storage::disk('public')->put($path, $pdf->output());
        $invoice->update(['pdf_path' => $path]);
    }

    /**
     * Ensure credit note PDF exists when the invoice is marked as having a credit note (IRINN only).
     */
    public function ensureCreditNotePdfExists(Application $application, Invoice $invoice): void
    {
        if ($application->application_type !== 'IRINN') {
            return;
        }

        if (! $invoice->hasCreditNote()) {
            return;
        }

        if ($invoice->credit_note_pdf_path && Storage::disk('public')->exists($invoice->credit_note_pdf_path)) {
            return;
        }

        $user = $application->user;
        $pdf = Pdf::loadView('invoices.credit-note-pdf', [
            'invoice' => $invoice,
            'user' => $user,
        ])->setPaper('a4', 'portrait')
            ->setOption('margin-top', 8)
            ->setOption('margin-bottom', 8)
            ->setOption('margin-left', 8)
            ->setOption('margin-right', 8)
            ->setOption('enable-local-file-access', true);

        $safeName = str_replace(['/', '\\'], '-', $invoice->invoice_number).'C.pdf';
        $path = 'applications/'.$application->user_id.'/irin/credit-notes/'.$safeName;
        Storage::disk('public')->put($path, $pdf->output());
        $invoice->update(['credit_note_pdf_path' => $path]);
    }

    /**
     * Create a PayU-payable reactivation invoice for an IRINN application (billing admin workflow).
     */
    public function createReactivationInvoiceForApplication(Application $application, Admin $admin, float $feeAmount): Invoice
    {
        if ($application->application_type !== 'IRINN') {
            throw new \InvalidArgumentException('Only IRINN applications support reactivation invoices.');
        }

        $tz = 'Asia/Kolkata';
        $invoiceDate = now($tz)->startOfDay();
        $dueDate = $invoiceDate->copy()->addDays(14);
        $prefix = 'NXNIR-REACT-';
        $last = Invoice::where('invoice_number', 'like', $prefix.'%')->orderByDesc('id')->value('invoice_number');
        $seq = 1;
        if ($last && preg_match('/'.preg_quote($prefix, '/').'(\d+)$/', (string) $last, $m)) {
            $seq = (int) $m[1] + 1;
        }
        $invoiceNumber = $prefix.str_pad((string) $seq, 5, '0', STR_PAD_LEFT);

        $base = round($feeAmount, 2);
        $gstAmount = round($base * 0.18, 2);
        $total = round($base + $gstAmount, 2);

        $lineItems = [
            ['description' => 'IRINN service reactivation charges', 'amount' => $base],
        ];

        $invoice = Invoice::create([
            'application_id' => $application->id,
            'invoice_number' => $invoiceNumber,
            'invoice_date' => $invoiceDate->toDateString(),
            'due_date' => $dueDate->toDateString(),
            'billing_period' => 'reactivation-'.$invoiceDate->format('Y-m'),
            'invoice_purpose' => 'reactivation',
            'line_items' => $lineItems,
            'amount' => $base,
            'gst_amount' => $gstAmount,
            'total_amount' => $total,
            'paid_amount' => 0,
            'balance_amount' => $total,
            'payment_status' => 'pending',
            'status' => 'pending',
            'currency' => 'INR',
            'generated_by' => $admin->id,
        ]);

        $invoice->refresh();
        $this->ensureInvoicePdfExists($application, $invoice);

        return $invoice->fresh();
    }

    /**
     * Display list of all invoices with filters.
     */
    public function invoices(Request $request)
    {
        try {
            $admin = $this->getCurrentAdmin();

            $query = Invoice::with(['application.user', 'generatedBy'])
                ->whereHas('application', function ($q) {
                    $q->where('application_type', 'IRINN');
                });

            // Search functionality
            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('invoice_number', 'like', "%{$search}%")
                        ->orWhereHas('application', function ($appQuery) use ($search) {
                            $appQuery->where('application_id', 'like', "%{$search}%")
                                ->orWhere('membership_id', 'like', "%{$search}%")
                                ->orWhere('customer_id', 'like', "%{$search}%")
                                ->orWhereHas('user', function ($userQuery) use ($search) {
                                    $userQuery->where('fullname', 'like', "%{$search}%")
                                        ->orWhere('email', 'like', "%{$search}%")
                                        ->orWhere('registrationid', 'like', "%{$search}%");
                                });
                        });
                });
            }

            // Filter by invoice number
            if ($request->filled('invoice_number')) {
                $query->where('invoice_number', 'like', "%{$request->input('invoice_number')}%");
            }

            // Filter by membership ID
            if ($request->filled('membership_id')) {
                $query->whereHas('application', function ($q) use ($request) {
                    $q->where('membership_id', 'like', "%{$request->input('membership_id')}%");
                });
            }

            // Filter by payment status
            if ($request->filled('payment_status')) {
                $query->where('payment_status', $request->input('payment_status'));
            }

            // Filter by invoice status
            if ($request->filled('status')) {
                $query->where('status', $request->input('status'));
            }

            if ($request->filled('application_record_id')) {
                $query->where('application_id', (int) $request->input('application_record_id'));
            }

            // Filter by date range
            if ($request->filled('date_from')) {
                $query->whereDate('invoice_date', '>=', $request->input('date_from'));
            }
            if ($request->filled('date_to')) {
                $query->whereDate('invoice_date', '<=', $request->input('date_to'));
            }

            // Filter by timeline
            if ($request->filled('timeline')) {
                $timeline = $request->input('timeline');
                $now = now('Asia/Kolkata');

                switch ($timeline) {
                    case 'today':
                        $query->whereDate('invoice_date', $now->toDateString());
                        break;
                    case 'this_week':
                        $query->whereBetween('invoice_date', [
                            $now->startOfWeek()->toDateString(),
                            $now->endOfWeek()->toDateString(),
                        ]);
                        break;
                    case 'this_month':
                        $query->whereYear('invoice_date', $now->year)
                            ->whereMonth('invoice_date', $now->month);
                        break;
                    case 'this_year':
                        $query->whereYear('invoice_date', $now->year);
                        break;
                    case 'last_month':
                        $query->whereYear('invoice_date', $now->copy()->subMonth()->year)
                            ->whereMonth('invoice_date', $now->copy()->subMonth()->month);
                        break;
                    case 'last_year':
                        $query->whereYear('invoice_date', $now->copy()->subYear()->year);
                        break;
                }
            }

            $invoices = $query->latest('invoice_date')->paginate(20)->withQueryString();

            // Get selected role for checking permissions
            $selectedRole = session('admin_selected_role', null);

            return view('admin.invoices.index', compact('invoices', 'admin', 'selectedRole'));
        } catch (Exception $e) {
            Log::error('Error loading invoices: '.$e->getMessage());

            return redirect()->route('admin.dashboard')
                ->with('error', 'Unable to load invoices.');
        }
    }

    /**
     * Get NIXI location credentials based on buyer state code
     *
     * @param  string  $buyerStateCode  Buyer's state code (from GSTIN)
     * @return array NIXI location credentials
     */
    private function getNixiLocationCredentials(string $buyerStateCode): array
    {
        // Map of state codes to NIXI locations with their credentials
        // Format: state_code => [gstin, location, pincode, state_code, api_identifier, address]
        $nixiLocations = [
            '07' => [ // Delhi
                'gstin' => '07AABCN9308A1ZT',
                'location' => 'Delhi',
                'pincode' => '110001',
                'state_code' => '07',
                'api_identifier' => '07AAACW3775F006',
                'address' => 'B-901, 9th Floor Tower B, World Trade Centre Nauroji Nagar',
            ],
            '09' => [ // Noida, Uttar Pradesh
                'gstin' => '09AABCN9308A1ZP',
                'location' => 'Noida',
                'pincode' => '201301',
                'state_code' => '09',
                'api_identifier' => 'nixi_noida_API_1ZP',
                'address' => 'H-223, Sector-63, Noida, Gautam Buddha Nagar',
            ],
            '19' => [ // Kolkata, West Bengal
                'gstin' => '19AABCN9308A1ZO',
                'location' => 'Kolkata',
                'pincode' => '700091',
                'state_code' => '19',
                'api_identifier' => 'nixi_kolka_API_1ZO',
                'address' => '2nd floor, Webel STPI II Building, Block-DN, Plot-53, Sector-V, Salt lake',
            ],
            '24' => [ // Ahmedabad, Gujarat
                'gstin' => '24AABCN9308A1ZX',
                'location' => 'Ahmedabad',
                'pincode' => '380054',
                'state_code' => '24',
                'api_identifier' => 'nixi_ahmed_API_1ZX',
                'address' => '301, GNFC Info Tower, Near Grand Bhagwati, S G Highway, Bodakdev',
            ],
            '27' => [ // Mumbai (Vashi), Maharashtra
                'gstin' => '27AABCN9308A1ZR',
                'location' => 'Navi Mumbai',
                'pincode' => '400703',
                'state_code' => '27',
                'api_identifier' => 'nixi_mumba_API_1ZR',
                'address' => 'Tower No.2, 5th Floor, International Infotech park, Vashi (Near Railway Station)',
            ],
            '29' => [ // Bangalore, Karnataka
                'gstin' => '29AABCN9308A1ZN',
                'location' => 'Bangalore',
                'pincode' => '560100',
                'state_code' => '29',
                'api_identifier' => 'nixi_banga_API_1ZN',
                'address' => '3rd Floor, Plot No. 76/77, Cyber Park, Electronic City, Phase-1, Dodda Thogur Village',
            ],
            '33' => [ // Chennai, Tamil Nadu
                'gstin' => '33AABCN9308A1ZY',
                'location' => 'Chennai',
                'pincode' => '600113',
                'state_code' => '33',
                'api_identifier' => 'nixi_chenn_API_1ZY',
                'address' => 'II Floor, Tidel Park, No. 4, Canal Bank Road, Taramani',
            ],
            '36' => [ // Hyderabad, Telangana
                'gstin' => '36AABCN9308A1ZS',
                'location' => 'Hyderabad',
                'pincode' => '500081',
                'state_code' => '36',
                'api_identifier' => 'nixi_hyder_API_1ZS',
                'address' => '16, Software Units Layout, Hi-Tech City, Madhapur',
            ],
            '05' => [ // Dehradun, Uttarakhand
                'gstin' => '05AABCN9308A1ZX',
                'location' => 'Dehradun',
                'pincode' => '248013',
                'state_code' => '05',
                'api_identifier' => 'nixi_dehra_API_1ZX',
                'address' => 'Plot No. IT-01, STPI, IT Park IIE Sahastradhara Road',
            ],
        ];

        // Common credentials (defaults in config/services.php → env; override in .env)
        $commonCdKey = (string) config('services.einvoice.cd_key');
        $commonEfUsername = (string) config('services.einvoice.ef_username');
        $commonEfPassword = (string) config('services.einvoice.ef_password');
        $commonEinvPassword = (string) config('services.einvoice.password');
        $globalEinvUsername = config('services.einvoice.username');

        // Match buyer state code with NIXI location
        // If match found, return that location's credentials
        if (! empty($buyerStateCode) && isset($nixiLocations[$buyerStateCode])) {
            $location = $nixiLocations[$buyerStateCode];

            // Get location-specific credentials from env with fallbacks
            // Format: EINVOICE_{LOCATION}_{FIELD} or EINVOICE_{FIELD}
            $locationKey = strtolower(str_replace(' ', '_', $location['location']));

            return [
                // Use common CD_KEY for all locations (can be overridden per location if needed)
                'cd_key' => env("EINVOICE_{$locationKey}_CD_KEY") ?: $commonCdKey,
                'einv_user_name' => env("EINVOICE_{$locationKey}_USERNAME")
                    ?: (filled($globalEinvUsername) ? (string) $globalEinvUsername : null)
                    ?: $location['api_identifier'],
                'einv_password' => env("EINVOICE_{$locationKey}_PASSWORD") ?: $commonEinvPassword,
                'ef_user_name' => env("EINVOICE_{$locationKey}_EF_USERNAME") ?: $commonEfUsername,
                'ef_password' => env("EINVOICE_{$locationKey}_EF_PASSWORD") ?: $commonEfPassword,
                'supplier_gstin' => $location['gstin'],
                'supplier_location' => $location['location'],
                'supplier_pincode' => $location['pincode'],
                'supplier_state_code' => $location['state_code'],
                'supplier_address' => $location['address'],
                'api_identifier' => $location['api_identifier'],
            ];
        }

        // Default to Delhi if no match
        $delhiLocation = $nixiLocations['07'];
        $locationKey = 'delhi';

        return [
            'cd_key' => env("EINVOICE_{$locationKey}_CD_KEY") ?: $commonCdKey,
            'einv_user_name' => env("EINVOICE_{$locationKey}_USERNAME")
                ?: (filled($globalEinvUsername) ? (string) $globalEinvUsername : null)
                ?: $delhiLocation['api_identifier'],
            'einv_password' => env("EINVOICE_{$locationKey}_PASSWORD") ?: $commonEinvPassword,
            'ef_user_name' => env("EINVOICE_{$locationKey}_EF_USERNAME") ?: $commonEfUsername,
            'ef_password' => env("EINVOICE_{$locationKey}_EF_PASSWORD") ?: $commonEfPassword,
            'supplier_gstin' => $delhiLocation['gstin'],
            'supplier_location' => $delhiLocation['location'],
            'supplier_pincode' => $delhiLocation['pincode'],
            'supplier_state_code' => $delhiLocation['state_code'],
            'supplier_address' => $delhiLocation['address'],
            'api_identifier' => $delhiLocation['api_identifier'],
        ];
    }

    /**
     * Get available NIXI seller GST state code options
     */
    private function getNixiSellerGstOptions(): array
    {
        // Map of state codes to location names (same as getNixiLocationCredentials)
        return [
            '07' => 'Delhi',
            '09' => 'Noida, Uttar Pradesh',
            '19' => 'Kolkata, West Bengal',
            '24' => 'Ahmedabad, Gujarat',
            '27' => 'Navi Mumbai, Maharashtra',
            '29' => 'Bangalore, Karnataka',
            '33' => 'Chennai, Tamil Nadu',
            '36' => 'Hyderabad, Telangana',
            '05' => 'Dehradun, Uttarakhand',
        ];
    }

    /**
     * Update seller GST state code for an application
     */
    public function updateSellerGstForApplication(Request $request, int $id): RedirectResponse
    {
        $application = Application::findOrFail($id);

        $allowed = array_keys($this->getNixiSellerGstOptions());
        // Ensure all allowed codes are strings for comparison
        $allowed = array_map('strval', $allowed);

        // Get the input value
        $sellerStateCode = $request->input('seller_state_code');

        // Convert empty/null to null
        if ($sellerStateCode === null || $sellerStateCode === '') {
            $sellerStateCode = null;
        } else {
            // Convert to string and trim
            $sellerStateCode = trim((string) $sellerStateCode);

            // Pad single digit codes with leading zero if needed (e.g., '7' -> '07')
            if (strlen($sellerStateCode) === 1 && is_numeric($sellerStateCode)) {
                $sellerStateCode = '0'.$sellerStateCode;
            }

            // Validate state code if provided
            if (! in_array($sellerStateCode, $allowed, true)) {
                Log::warning('Invalid seller GST state code', [
                    'provided' => $sellerStateCode,
                    'provided_type' => gettype($sellerStateCode),
                    'allowed' => $allowed,
                ]);

                return back()->with('error', 'Invalid seller GST state code selected: '.$sellerStateCode);
            }
        }

        $application->update([
            'seller_state_code' => $sellerStateCode,
        ]);

        return back()->with('success', 'Seller GST assignment updated.');
    }

    /**
     * Merge duplicate-IRN payload (e.g. ErrorCode 2150 / DUPIRN) into top-level Irn, AckNo, AckDate.
     *
     * @param  array<string, mixed>  $responseObj
     * @return array<string, mixed>
     */
    private function normalizeEinvoiceApiResponse(array $responseObj): array
    {
        $irn = $responseObj['Irn'] ?? '';
        $errorCode = (string) ($responseObj['ErrorCode'] ?? '');
        if ($errorCode === '2150' && empty($irn) && isset($responseObj['InfoDtls']) && is_array($responseObj['InfoDtls'])) {
            foreach ($responseObj['InfoDtls'] as $infoDetail) {
                if (($infoDetail['InfCd'] ?? '') === 'DUPIRN' && isset($infoDetail['Desc']) && is_array($infoDetail['Desc'])) {
                    $desc = $infoDetail['Desc'];
                    $responseObj['Irn'] = $desc['Irn'] ?? '';
                    $responseObj['AckNo'] = $desc['AckNo'] ?? '';
                    $responseObj['AckDate'] = $desc['AckDt'] ?? '';
                    break;
                }
            }
        }

        return $responseObj;
    }

    /**
     * Public entry point for e-invoice API (used by IRINN billing flows).
     */
    public function callEinvoiceApiForInvoice(Application $application, Invoice $invoice): ?array
    {
        return $this->callEinvoiceApi($application, $invoice, false);
    }

    /**
     * Call e-invoice API to generate signed invoice or credit note.
     *
     * @param  bool  $isCreditNote  If true, sends CRN (credit note) with same invoice number
     */
    private function callEinvoiceApi(Application $application, Invoice $invoice, bool $isCreditNote = false): ?array
    {
        try {
            // Get API URL and credentials from config/env
            // API URL - must be exactly: http://einvSandbox.webtel.in/v1.03/GenIRN
            // Note: Case-sensitive (capital S in Sandbox) and GenIRN (not GenIRN2)
            $apiUrl = config('services.einvoice.url');

            // Validate URL format
            // if (strpos($apiUrl, 'GenIRN2') !== false) {
            //     Log::error('E-invoice API URL is incorrect - should be GenIRN not GenIRN2', [
            //         'current_url' => $apiUrl,
            //         'correct_url' => 'http://einvSandbox.webtel.in/v1.03/GenIRN',
            //     ]);
            //     // Override with correct URL
            //     $apiUrl = 'http://einvSandbox.webtel.in/v1.03/GenIRN';
            // }

            // Get buyer details from kyc_details column first to determine which NIXI location to use
            $kycDetails = $application->kyc_details ?? [];
            $buyerGstin = $kycDetails['gstin'] ?? $application->gstin ?? '';
            $buyerStateCode = $this->extractStateCodeFromGstin($buyerGstin);

            // Seller state code: admin-assigned, or auto (buyer state if we have NIXI for same state, else Delhi via getNixiLocationCredentials)
            $sellerStateCode = $application->seller_state_code ?? $buyerStateCode;

            // Get NIXI location credentials based on seller state code
            // If state matches, use that location's credentials; otherwise default to Delhi
            $nixiCredentials = $this->getNixiLocationCredentials($sellerStateCode);

            $cdKey = $nixiCredentials['cd_key'];
            $einvUserName = $nixiCredentials['einv_user_name'];
            $einvPassword = $nixiCredentials['einv_password'];
            $efUserName = $nixiCredentials['ef_user_name'];
            $efPassword = $nixiCredentials['ef_password'];

            // Log credentials being used (for debugging - passwords are hidden in actual API call logs)
            $isLive = strpos($apiUrl, 'einvlive') !== false;
            $locationKey = strtolower(str_replace(' ', '_', $nixiCredentials['supplier_location']));
            $locationSpecificCdKey = env("EINVOICE_{$locationKey}_CD_KEY");
            $envGlobalCdKey = env('EINVOICE_CD_KEY');
            $configCdKey = config('services.einvoice.cd_key');

            // Determine CD_KEY source for logging
            $cdKeySource = 'services.einvoice.cd_key (config default)';
            if (filled($locationSpecificCdKey)) {
                $cdKeySource = "EINVOICE_{$locationKey}_CD_KEY (location-specific .env)";
            } elseif (filled($envGlobalCdKey)) {
                $cdKeySource = 'EINVOICE_CD_KEY (.env)';
            } elseif (filled($configCdKey)) {
                $cdKeySource = 'services.einvoice.cd_key (config)';
            }

            // Supplier (NIXI) details from matched location (set before log that uses supplierStateCode)
            $supplierGstin = $nixiCredentials['supplier_gstin'];
            $supplierLocation = $nixiCredentials['supplier_location'];
            $supplierPinCode = $nixiCredentials['supplier_pincode'];
            $supplierStateCode = $nixiCredentials['supplier_state_code'];
            $supplierAddress = $nixiCredentials['supplier_address'];

            // Calculate isSameState for logging (will be recalculated later with updated buyerStateCode)
            $tempIsSameState = ($supplierStateCode === $buyerStateCode);

            Log::info('E-invoice API credentials', [
                'api_url' => $apiUrl,
                'environment' => $isLive ? 'LIVE' : 'SANDBOX',
                'cd_key' => $cdKey,
                'cd_key_source' => $cdKeySource,
                'einv_username' => $einvUserName,
                'supplier_gstin' => $nixiCredentials['supplier_gstin'],
                'supplier_location' => $nixiCredentials['supplier_location'],
                'supplier_state_code' => $supplierStateCode,
                'buyer_state_code' => $buyerStateCode,
                'seller_state_code' => $sellerStateCode ?? 'auto',
                'is_same_state' => $tempIsSameState,
            ]);

            // Note: CD_KEY is common for all locations
            // If you get "CDKey Not Match" error, set the correct common CD_KEY in .env:
            // EINVOICE_CD_KEY=<correct_common_cd_key>
            // Or override for specific location if needed:
            // EINVOICE_BANGALORE_CD_KEY=<location_specific_cd_key>

            if (empty($apiUrl)) {
                Log::warning('E-invoice API URL not configured');

                return [
                    'Status' => '0',
                    'ErrorCode' => 'CONFIG',
                    'ErrorMessage' => 'E-invoice API URL is not configured. Set EINVOICE_API_URL in .env or config/services.php.',
                ];
            }

            // Get buyer details from kyc_details column
            $kycDetails = $application->kyc_details ?? [];
            $buyerGstin = $kycDetails['gstin'] ?? $application->gstin ?? '';
            // Recalculate buyer state code (in case buyer GSTIN was retrieved again)
            $buyerStateCode = $this->extractStateCodeFromGstin($buyerGstin);

            // Get billing address from kyc_details - handle JSON format
            $billingAddressRaw = $kycDetails['billing_address'] ?? $application->billing_address ?? '';
            $billingAddress = $this->extractAddressFromKycData($billingAddressRaw);
            $buyerLocation = $this->extractPlaceFromAddress($billingAddress);
            $buyerPinCode = $this->extractPincodeFromAddress($billingAddress);

            // Get state name from state code for Addr2
            $buyerStateName = $this->getStateNameFromCode($buyerStateCode) ?? '';

            // Split address into Addr1 (address without city/state, max 100 chars) and Addr2 (city, state)
            $addressParts = $this->splitAddressForEinvoice($billingAddress, $buyerLocation, $buyerStateName);
            $buyerAddr1 = $addressParts['addr1']; // Address without city/state, max 100 chars
            $buyerAddr2 = $addressParts['addr2']; // City and state

            // Get company name from kyc_details or GST verification or user
            $buyerName = $kycDetails['contact_name'] ?? $application->user->fullname ?? '';

            // Get GST verification to check status and registration date
            $gstVerification = \App\Models\GstVerification::where('user_id', $application->user_id)
                ->where('gstin', $buyerGstin)
                ->where('is_verified', true)
                ->latest()
                ->first();

            // If not found by GSTIN, try to get latest verified one
            if (! $gstVerification) {
                $gstVerification = \App\Models\GstVerification::where('user_id', $application->user_id)
                    ->where('is_verified', true)
                    ->latest()
                    ->first();
            }

            // Validate GSTIN status and registration date before calling API
            if ($gstVerification) {
                // Check if GSTIN is cancelled
                $companyStatus = strtolower(trim($gstVerification->company_status ?? ''));

                // Try to get gstin_status from verification_data if not in model
                $gstinStatus = '';
                if ($gstVerification->verification_data && is_array($gstVerification->verification_data)) {
                    $verificationData = $gstVerification->verification_data;
                    $sourceOutput = $verificationData['result']['source_output'] ?? [];
                    $gstinStatus = strtolower(trim($sourceOutput['gstin_status'] ?? ''));
                }

                // Check if GSTIN is cancelled (check both company_status and gstin_status fields)
                $isCancelled = in_array($companyStatus, ['cancelled', 'canceled', 'cancelled/suspended'])
                    || in_array($gstinStatus, ['cancelled', 'canceled', 'cancelled/suspended']);

                if ($isCancelled) {
                    Log::error('E-invoice API validation failed: GSTIN is cancelled', [
                        'invoice_id' => $invoice->id ?? null,
                        'invoice_number' => $invoice->invoice_number ?? null,
                        'gstin' => $buyerGstin,
                        'company_status' => $gstVerification->company_status,
                        'gstin_status' => $gstVerification->gstin_status,
                    ]);

                    throw new \Exception("GSTIN {$buyerGstin} is cancelled. Cannot generate invoice for a cancelled GSTIN.");
                }

                // Invoice date must be on or after GST registration date (cannot invoice before registration)
                if ($gstVerification->registration_date && $invoice->invoice_date) {
                    $registrationDate = \Carbon\Carbon::parse($gstVerification->registration_date)->startOfDay();
                    $invoiceDate = \Carbon\Carbon::parse($invoice->invoice_date)->startOfDay();

                    if ($invoiceDate->lt($registrationDate)) {
                        Log::error('E-invoice API validation failed: Invoice date is before GST registration date', [
                            'invoice_id' => $invoice->id ?? null,
                            'invoice_number' => $invoice->invoice_number ?? null,
                            'gstin' => $buyerGstin,
                            'invoice_date' => $invoiceDate->format('d/m/Y'),
                            'registration_date' => $registrationDate->format('d/m/Y'),
                        ]);

                        throw new \Exception("Invoice date ({$invoiceDate->format('d/m/Y')}) cannot be before GST registration date ({$registrationDate->format('d/m/Y')}). Please use a date on or after the registration date.");
                    }
                }

                // Get legal/trade name from GST verification
                if (empty($buyerName) || $buyerName === $application->user->fullname) {
                    $buyerName = $gstVerification->legal_name ?? $gstVerification->trade_name ?? $buyerName;
                }
            }

            // Get contact details from kyc_details
            $buyerEmail = $kycDetails['contact_email'] ?? $application->user->email ?? '';
            $buyerMobile = $kycDetails['contact_mobile'] ?? $application->user->mobile ?? '';

            // Calculate GST amounts: use effective rate from invoice (amount + gst_amount) when available, else 18%
            // Same state: CGST + SGST (half rate each); different state: IGST (full rate)
            $taxableAmount = (float) $invoice->amount;
            $invoiceGstAmount = (float) ($invoice->gst_amount ?? 0);
            $gstRate = ($taxableAmount > 0 && $invoiceGstAmount >= 0)
                ? round($invoiceGstAmount / $taxableAmount * 100, 2)
                : 18.0;
            if ($gstRate <= 0 || $gstRate > 100) {
                $gstRate = 18.0;
            }

            $normalizedSupplierStateCode = trim((string) $supplierStateCode);
            $normalizedBuyerStateCode = trim((string) $buyerStateCode);
            $isSameState = ($normalizedSupplierStateCode === $normalizedBuyerStateCode && $normalizedSupplierStateCode !== '' && $normalizedBuyerStateCode !== '');

            $cgst = 0.00;
            $sgst = 0.00;
            $igst = 0.00;

            if ($isSameState) {
                // Same state: CGST + SGST (half rate each)
                $halfRate = $gstRate / 2;
                $cgst = ($taxableAmount * $halfRate) / 100;
                $sgst = ($taxableAmount * $halfRate) / 100;
            } else {
                // Different state: IGST (full rate)
                $igst = ($taxableAmount * $gstRate) / 100;
            }

            $finalAmount = $taxableAmount + $cgst + $sgst + $igst;

            // First, calculate total invoice values from all items
            $lineItems = $invoice->line_items ?? [];
            $totalAssVal = 0.00;
            $totalCgstVal = 0.00;
            $totalSgstVal = 0.00;
            $totalIgstVal = 0.00;

            // Calculate totals first
            foreach ($lineItems as $key => $item) {
                if (is_string($key) && str_starts_with($key, '_')) {
                    continue;
                }
                if (! is_array($item) || isset($item['is_carry_forward']) || isset($item['is_adjustment']) || $item === '_metadata') {
                    continue;
                }

                $itemAmount = abs((float) ($item['amount'] ?? 0));
                $itemAssAmt = $itemAmount;

                if ($isSameState) {
                    $halfRate = $gstRate / 2;
                    $itemCgst = round(($itemAssAmt * $halfRate) / 100, 2);
                    $itemSgst = round(($itemAssAmt * $halfRate) / 100, 2);
                    $itemIgst = 0.00;
                } else {
                    $itemCgst = 0.00;
                    $itemSgst = 0.00;
                    $itemIgst = round(($itemAssAmt * $gstRate) / 100, 2);
                }

                $totalAssVal += $itemAssAmt;
                $totalCgstVal += $itemCgst;
                $totalSgstVal += $itemSgst;
                $totalIgstVal += $itemIgst;
            }

            // If no items, use invoice totals
            if ($totalAssVal == 0) {
                $totalAssVal = round($taxableAmount, 2);
                $totalCgstVal = round($cgst, 2);
                $totalSgstVal = round($sgst, 2);
                $totalIgstVal = round($igst, 2);
            }

            // Round all totals to 2 decimal places (API requirement)
            $totalAssVal = round($totalAssVal, 2);
            $totalCgstVal = round($totalCgstVal, 2);
            $totalSgstVal = round($totalSgstVal, 2);
            $totalIgstVal = round($totalIgstVal, 2);
            $totalInvVal = round($totalAssVal + $totalCgstVal + $totalSgstVal + $totalIgstVal, 2);

            // Now create data array - each item becomes a separate entry
            $dataArray = [];
            $itemSlNo = 1;

            // Process each line item
            foreach ($lineItems as $key => $item) {
                if (is_string($key) && str_starts_with($key, '_')) {
                    continue;
                }
                if (! is_array($item) || isset($item['is_carry_forward']) || isset($item['is_adjustment']) || $item === '_metadata') {
                    continue;
                }

                $itemAmount = abs((float) ($item['amount'] ?? 0));
                $itemQuantity = (float) ($item['quantity'] ?? 1);
                $itemUnitPrice = abs((float) ($item['rate'] ?? $itemAmount));
                $itemDescription = $item['description'] ?? 'IX Port Service';

                // Calculate GST for this item
                $itemAssAmt = $itemAmount;
                $itemGstRate = $gstRate;

                $itemCgst = 0.00;
                $itemSgst = 0.00;
                $itemIgst = 0.00;

                if ($isSameState) {
                    $halfRate = $gstRate / 2;
                    $itemCgst = round(($itemAssAmt * $halfRate) / 100, 2);
                    $itemSgst = round(($itemAssAmt * $halfRate) / 100, 2);
                } else {
                    $itemIgst = round(($itemAssAmt * $gstRate) / 100, 2);
                }

                $itemTotItemVal = round($itemAssAmt + $itemCgst + $itemSgst + $itemIgst, 2);

                // Create data entry for this item
                // Note: GSTIN field must match EINVUSERNAME (registered API user GSTIN), not supplier GSTIN
                $docTyp = $isCreditNote ? 'CRN' : 'INV';
                $dataEntry = [
                    'GSTIN' => $supplierGstin, // Must match EINVUSERNAME (registered API user)
                    'Version' => '1.03',
                    'Tran_TaxSch' => 'GST',
                    'Tran_SupTyp' => 'B2B',
                    'Tran_RegRev' => 'N',
                    'Tran_Typ' => $docTyp,
                    'Doc_Typ' => $docTyp,
                    'Doc_No' => $invoice->invoice_number,
                    'Doc_Dt' => $invoice->invoice_date->format('d/m/Y'),

                    // Supplier (BillFrom) details - use actual supplier GSTIN
                    'BillFrom_Gstin' => $supplierGstin,
                    'BillFrom_LglNm' => 'National Internet Exchange of India',
                    'BillFrom_Addr1' => $supplierAddress,
                    'BillFrom_Addr2' => '',
                    'BillFrom_Loc' => $supplierLocation,
                    'BillFrom_Pin' => $supplierPinCode,
                    'BillFrom_Stcd' => $supplierStateCode,
                    'BillFrom_Ph' => '',
                    'BillFrom_Em' => 'ixbilling@nixi.in',

                    // Buyer (BillTo) details from kyc_details
                    'BillTo_Gstin' => $buyerGstin,
                    'BillTo_LglNm' => $buyerName,
                    'BillTo_TrdNm' => $buyerName,
                    'BillTo_Pos' => $buyerStateCode,
                    'BillTo_Addr1' => $buyerAddr1,
                    'BillTo_Addr2' => $buyerAddr2,
                    'BillTo_Loc' => $buyerLocation,
                    'BillTo_Pin' => $buyerPinCode,
                    'BillTo_Stcd' => $buyerStateCode,
                    'BillTo_Ph' => $buyerMobile,
                    'BillTo_Em' => $buyerEmail,

                    // Item details
                    'Item_SlNo' => (string) $itemSlNo,
                    'Item_PrdDesc' => $itemDescription,
                    'Item_IsServc' => 'Y',
                    'Item_HsnCd' => '998319',
                    'Item_Qty' => (string) $itemQuantity,
                    'Item_Unit' => 'NOS',
                    'Item_UnitPrice' => round($itemUnitPrice, 2),
                    'Item_TotAmt' => round($itemAmount, 2),
                    'Item_AssAmt' => round($itemAssAmt, 2),
                    'Item_GstRt' => $itemGstRate,
                    'Item_IgstAmt' => round($itemIgst, 2),
                    'Item_CgstAmt' => round($itemCgst, 2),
                    'Item_SgstAmt' => round($itemSgst, 2),
                    'Item_TotItemVal' => round($itemTotItemVal, 2),

                    // Invoice totals (same for all items - total invoice values)
                    'Val_AssVal' => round($totalAssVal, 2),
                    'Val_CgstVal' => round($totalCgstVal, 2),
                    'Val_SgstVal' => round($totalSgstVal, 2),
                    'Val_IgstVal' => round($totalIgstVal, 2),
                    'Val_TotInvVal' => round($totalInvVal, 2),

                    // Webtel credentials
                    // CDKEY should be a string (API expects string format)
                    'CDKEY' => (string) $cdKey,
                    'EFUSERNAME' => $efUserName,
                    'EFPASSWORD' => $efPassword,
                    'EINVUSERNAME' => $einvUserName,
                    'EINVPASSWORD' => $einvPassword,
                ];

                $dataArray[] = $dataEntry;
                $itemSlNo++;
            }

            // If no items found, create a default entry
            if (empty($dataArray)) {
                $finalAmount = round($taxableAmount + $cgst + $sgst + $igst, 2);

                $docTypDefault = $isCreditNote ? 'CRN' : 'INV';
                $dataArray[] = [
                    'GSTIN' => $einvUserName, // Must match EINVUSERNAME (registered API user)
                    'Version' => '1.03',
                    'Tran_TaxSch' => 'GST',
                    'Tran_SupTyp' => 'B2B',
                    'Tran_RegRev' => 'N',
                    'Tran_Typ' => $docTypDefault,
                    'Doc_Typ' => $docTypDefault,
                    'Doc_No' => $invoice->invoice_number,
                    'Doc_Dt' => $invoice->invoice_date->format('d/m/Y'),

                    'BillFrom_Gstin' => $supplierGstin,
                    'BillFrom_LglNm' => 'National Internet Exchange of India',
                    'BillFrom_Addr1' => $supplierAddress,
                    'BillFrom_Addr2' => '',
                    'BillFrom_Loc' => $supplierLocation,
                    'BillFrom_Pin' => $supplierPinCode,
                    'BillFrom_Stcd' => $supplierStateCode,
                    'BillFrom_Ph' => '',
                    'BillFrom_Em' => 'ixbilling@nixi.in',

                    'BillTo_Gstin' => $buyerGstin,
                    'BillTo_LglNm' => $buyerName,
                    'BillTo_TrdNm' => $buyerName,
                    'BillTo_Pos' => $buyerStateCode,
                    'BillTo_Addr1' => $buyerAddr1,
                    'BillTo_Addr2' => $buyerAddr2,
                    'BillTo_Loc' => $buyerLocation,
                    'BillTo_Pin' => $buyerPinCode,
                    'BillTo_Stcd' => $buyerStateCode,
                    'BillTo_Ph' => $buyerMobile,
                    'BillTo_Em' => $buyerEmail,

                    'Item_SlNo' => '1',
                    'Item_PrdDesc' => 'IX Port Service',
                    'Item_IsServc' => 'Y',
                    'Item_HsnCd' => '998319',
                    'Item_Qty' => '1',
                    'Item_Unit' => 'NOS',
                    'Item_UnitPrice' => round($taxableAmount, 2),
                    'Item_TotAmt' => round($taxableAmount, 2),
                    'Item_AssAmt' => round($taxableAmount, 2),
                    'Item_GstRt' => $gstRate,
                    'Item_IgstAmt' => round($igst, 2),
                    'Item_CgstAmt' => round($cgst, 2),
                    'Item_SgstAmt' => round($sgst, 2),
                    'Item_TotItemVal' => round($finalAmount, 2),

                    'Val_AssVal' => round($taxableAmount, 2),
                    'Val_CgstVal' => round($cgst, 2),
                    'Val_SgstVal' => round($sgst, 2),
                    'Val_IgstVal' => round($igst, 2),
                    'Val_TotInvVal' => round($finalAmount, 2),

                    // CDKEY should be a string (API expects string format)
                    'CDKEY' => (string) $cdKey,
                    'EFUSERNAME' => $efUserName,
                    'EFPASSWORD' => $efPassword,
                    'EINVUSERNAME' => $einvUserName,
                    'EINVPASSWORD' => $einvPassword,
                ];
            }

            // Wrap in Push_Data_List structure
            $invoiceData = [
                'Push_Data_List' => [
                    'Data' => $dataArray,
                ],
            ];

            // Log the request payload for debugging (without passwords)
            $logData = $invoiceData;
            if (isset($logData['Push_Data_List']['Data'])) {
                foreach ($logData['Push_Data_List']['Data'] as &$dataEntry) {
                    if (isset($dataEntry['EINVPASSWORD'])) {
                        $dataEntry['EINVPASSWORD'] = '***HIDDEN***';
                    }
                    if (isset($dataEntry['EFPASSWORD'])) {
                        $dataEntry['EFPASSWORD'] = '***HIDDEN***';
                    }
                }
            }

            Log::info('Calling e-invoice API', [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'api_url' => $apiUrl,
                'payload' => $logData,
                'data_count' => count($dataArray),
            ]);

            // Make API call - ensure proper JSON encoding
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])
                ->timeout(60)
                ->asJson()
                ->post($apiUrl, $invoiceData);

            $statusCode = $response->status();
            $responseBody = $response->body();
            $responseData = json_decode($responseBody, true);

            Log::info('E-invoice API response', [
                'invoice_id' => $invoice->id,
                'status_code' => $statusCode,
                'response_body_raw' => $responseBody,
                'response_parsed' => $responseData,
            ]);

            // API returns an array with response objects
            // Check if response is successful (200 status) and has data
            if ($statusCode === 200 && is_array($responseData) && ! empty($responseData)) {
                // Get the first response object (usually only one)
                $responseObj = $responseData[0] ?? null;

                if ($responseObj && is_array($responseObj)) {
                    $responseObj = $this->normalizeEinvoiceApiResponse($responseObj);
                    // Check if Status indicates success (Status: "1" means success)
                    $status = $responseObj['Status'] ?? $responseObj['status'] ?? '';
                    if ($status === '1' || $status === 1) {
                        Log::info('E-invoice API call successful', [
                            'invoice_id' => $invoice->id,
                            'irn' => $responseObj['Irn'] ?? null,
                            'ack_no' => $responseObj['AckNo'] ?? null,
                        ]);

                        return $responseObj;
                    } else {
                        // Status is not "1", but we still got a response - log it
                        Log::warning('E-invoice API returned non-success status', [
                            'invoice_id' => $invoice->id,
                            'status' => $status,
                            'error_code' => $responseObj['ErrorCode'] ?? null,
                            'error_message' => $responseObj['ErrorMessage'] ?? null,
                        ]);

                        // Still return the response so it can be stored
                        return $responseObj;
                    }
                }

                // If response is not in expected format, return the whole response
                return $responseData;
            } else {
                $errorMsg = trim($responseBody) !== ''
                    ? 'HTTP '.$statusCode.': '.substr($responseBody, 0, 500)
                    : 'HTTP '.$statusCode.' - empty response from e-invoice API';
                Log::error('E-invoice API call failed', [
                    'invoice_id' => $invoice->id,
                    'status_code' => $statusCode,
                    'response' => $responseBody,
                ]);

                return [
                    'Status' => '0',
                    'ErrorCode' => 'HTTP_'.$statusCode,
                    'ErrorMessage' => $errorMsg,
                ];
            }
        } catch (\Exception $e) {
            Log::error('Error calling e-invoice API: '.$e->getMessage(), [
                'invoice_id' => $invoice->id ?? null,
                'invoice_number' => $invoice->invoice_number ?? null,
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'Status' => '0',
                'ErrorCode' => 'EXCEPTION',
                'ErrorMessage' => $e->getMessage(),
            ];
        }
    }

    /**
     * Extract place/city from address string
     */
    private function extractPlaceFromAddress(string $address): string
    {
        if (empty($address)) {
            return '';
        }

        // Try to extract city/place from address
        // Common patterns: "City, State" or "City State" or just "City"
        $parts = explode(',', $address);
        if (count($parts) >= 2) {
            // Usually city is before the last comma
            return trim($parts[count($parts) - 2]);
        }

        return '';
    }

    /**
     * Extract pincode from address string
     */
    private function extractPincodeFromAddress(string $address): string
    {
        if (empty($address)) {
            return '';
        }

        // Extract 6-digit pincode
        if (preg_match('/\b(\d{6})\b/', $address, $matches)) {
            return $matches[1];
        }

        return '';
    }

    /**
     * Extract state code from GSTIN (first 2 digits)
     */
    private function extractStateCodeFromGstin(string $gstin): string
    {
        if (empty($gstin) || strlen($gstin) < 2) {
            return '';
        }

        // GSTIN first 2 digits represent state code
        return substr($gstin, 0, 2);
    }

    /**
     * Get state name from state code
     */
    private function getStateNameFromCode(string $stateCode): ?string
    {
        $stateMap = [
            '05' => 'Uttarakhand',
            '07' => 'Delhi',
            '09' => 'Uttar Pradesh',
            '19' => 'West Bengal',
            '24' => 'Gujarat',
            '27' => 'Maharashtra',
            '29' => 'Karnataka',
            '33' => 'Tamil Nadu',
            '36' => 'Telangana',
        ];

        return $stateMap[$stateCode] ?? null;
    }

    /**
     * Get state code from state name
     */
    private function getStateCodeFromName(string $stateName): ?string
    {
        $stateMap = [
            'Uttarakhand' => '05',
            'Delhi' => '07',
            'New Delhi' => '07',
            'Uttar Pradesh' => '09',
            'West Bengal' => '19',
            'Gujarat' => '24',
            'Maharashtra' => '27',
            'Karnataka' => '29',
            'Tamil Nadu' => '33',
            'Telangana' => '36',
        ];

        // Try exact match first
        if (isset($stateMap[$stateName])) {
            return $stateMap[$stateName];
        }

        // Try case-insensitive match
        $stateNameLower = strtolower(trim($stateName));
        foreach ($stateMap as $name => $code) {
            if (strtolower($name) === $stateNameLower) {
                return $code;
            }
        }

        return null;
    }

    /**
     * Extract address string from KYC data (handles JSON format)
     *
     * @param  mixed  $addressData
     */
    private function extractAddressFromKycData($addressData): string
    {
        if (empty($addressData)) {
            return '';
        }

        // If it's already a string, check if it's JSON
        if (is_string($addressData)) {
            // Try to decode if it looks like JSON
            $trimmed = trim($addressData);
            if (strpos($trimmed, '{') === 0 || strpos($trimmed, '[') === 0) {
                $decoded = json_decode($addressData, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    // Extract address from JSON structure
                    if (isset($decoded['address'])) {
                        return $decoded['address'];
                    }
                    // If it's a simple array, try to get the address field
                    if (isset($decoded[0]) && is_array($decoded[0]) && isset($decoded[0]['address'])) {
                        return $decoded[0]['address'];
                    }
                }
            }

            // If not JSON or decode failed, return as is
            return $addressData;
        }

        // If it's an array, extract address
        if (is_array($addressData)) {
            if (isset($addressData['address'])) {
                return $addressData['address'];
            }
            // If it's a simple array with address as value
            if (isset($addressData[0]) && is_string($addressData[0])) {
                return $addressData[0];
            }
        }

        return (string) $addressData;
    }

    /**
     * Split address for e-invoice API format
     * Addr1: Address without city and state, max 100 characters
     * Addr2: City and state
     *
     * @return array ['addr1' => string, 'addr2' => string]
     */
    private function splitAddressForEinvoice(string $fullAddress, string $city = '', string $stateName = ''): array
    {
        if (empty($fullAddress)) {
            return ['addr1' => ' ', 'addr2' => ' '];
        }

        $address = trim($fullAddress);

        // Remove pincode (6 digits) from address
        $address = preg_replace('/\b\d{6}\b/', '', $address);

        // Split by commas to identify parts
        $parts = array_map('trim', explode(',', $address));
        $parts = array_filter($parts, function ($part) {
            return ! empty(trim($part));
        });

        // Common state names to identify and remove
        $stateNames = [
            'Andhra Pradesh', 'Arunachal Pradesh', 'Assam', 'Bihar', 'Chhattisgarh',
            'Goa', 'Gujarat', 'Haryana', 'Himachal Pradesh', 'Jharkhand', 'Karnataka',
            'Kerala', 'Madhya Pradesh', 'Maharashtra', 'Manipur', 'Meghalaya', 'Mizoram',
            'Nagaland', 'Odisha', 'Punjab', 'Rajasthan', 'Sikkim', 'Tamil Nadu',
            'Telangana', 'Tripura', 'Uttar Pradesh', 'Uttarakhand', 'West Bengal',
            'Delhi', 'Jammu and Kashmir', 'Ladakh', 'Puducherry', 'Chandigarh',
        ];

        $addr1Parts = [];
        $cityFound = false;
        $stateFound = false;

        // Process address parts from start to end
        foreach ($parts as $part) {
            $partLower = strtolower($part);
            $isCity = false;
            $isState = false;

            // Check if this part is the city
            if (! empty($city) && stripos($part, $city) !== false) {
                $isCity = true;
                $cityFound = true;
            }

            // Check if this part is a state
            foreach ($stateNames as $state) {
                if (stripos($part, $state) !== false) {
                    $isState = true;
                    $stateFound = true;
                    break;
                }
            }

            // If not city or state, add to Addr1
            if (! $isCity && ! $isState) {
                $addr1Parts[] = $part;
            }
        }

        // Build Addr1 (address without city/state)
        $addr1 = implode(', ', $addr1Parts);
        $addr1 = trim($addr1);

        // Remove any remaining city/state mentions
        foreach ($stateNames as $state) {
            $addr1 = preg_replace('/\b'.preg_quote($state, '/').'\b/i', '', $addr1);
        }
        if (! empty($city)) {
            $addr1 = preg_replace('/\b'.preg_quote($city, '/').'\b/i', '', $addr1);
        }

        $addr1 = preg_replace('/\s+/', ' ', $addr1); // Clean up multiple spaces
        $addr1 = trim($addr1, ', '); // Remove leading/trailing commas

        // Truncate to 100 characters max
        if (strlen($addr1) > 100) {
            $addr1 = substr($addr1, 0, 100);
            // Try to truncate at word boundary
            $lastSpace = strrpos($addr1, ' ');
            if ($lastSpace !== false && $lastSpace > 80) {
                $addr1 = substr($addr1, 0, $lastSpace);
            }
        }

        // Ensure Addr1 has minimum length of 1
        if (empty($addr1)) {
            $addr1 = ' '; // Use space if empty (API requirement)
        }

        // Build Addr2 (city and state)
        $addr2Parts = [];
        if (! empty($city)) {
            $addr2Parts[] = $city;
        }

        // Use provided state name, or try to find state from address
        if (empty($stateName)) {
            foreach ($parts as $part) {
                foreach ($stateNames as $state) {
                    if (stripos($part, $state) !== false) {
                        $stateName = $state;
                        break 2;
                    }
                }
            }
        }

        if (! empty($stateName) && ! in_array($stateName, $addr2Parts)) {
            $addr2Parts[] = $stateName;
        }

        $addr2 = implode(', ', $addr2Parts);
        $addr2 = trim($addr2);

        // Ensure Addr2 has minimum length of 1
        if (empty($addr2)) {
            $addr2 = ' '; // Use space if empty (API requirement)
        }

        return [
            'addr1' => $addr1,
            'addr2' => $addr2,
        ];
    }

    /**
     * Show bulk notification form.
     */
    public function bulkNotification(Request $request)
    {
        try {
            $admin = $this->getCurrentAdmin();

            $zones = collect();
            $locations = collect();

            $users = Registration::with('applications')
                ->whereHas('applications', function ($query) {
                    $query->where('application_type', 'IRINN')
                        ->whereNotNull('membership_id');
                })
                ->orderBy('fullname')
                ->get(['id', 'fullname', 'email', 'registrationid']);

            return view('admin.bulk-notification.index', compact('admin', 'zones', 'locations', 'users'));
        } catch (Exception $e) {
            Log::error('Error loading bulk notification form: '.$e->getMessage());

            return redirect()->route('admin.dashboard')
                ->with('error', 'Error loading bulk notification form. Please try again.');
        }
    }

    /**
     * Send bulk notifications.
     */
    public function sendBulkNotification(Request $request)
    {
        try {
            $request->validate([
                'subject' => 'required|string|max:255',
                'message' => 'required|string',
                'filter_type' => 'required|in:all,payment_status,user_wise,application_status',
                'payment_status' => 'nullable|in:paid,pending,overdue|required_if:filter_type,payment_status',
                'user_ids' => 'nullable|array|required_if:filter_type,user_wise',
                'user_ids.*' => 'integer|exists:registrations,id',
                'application_status' => 'nullable|in:live,not_live|required_if:filter_type,application_status',
            ]);

            $admin = $this->getCurrentAdmin();
            $subject = $request->input('subject');
            $message = $request->input('message');
            $filterType = $request->input('filter_type');

            // Get users based on filter
            $users = $this->getFilteredUsers($request, $filterType);

            if ($users->isEmpty()) {
                return redirect()->route('admin.bulk-notification')
                    ->with('error', 'No users found matching the selected criteria.')
                    ->withInput();
            }

            $sentCount = 0;
            $failedCount = 0;

            foreach ($users as $user) {
                try {
                    // Create message
                    $messageRecord = Message::create([
                        'user_id' => $user->id,
                        'subject' => $subject,
                        'message' => $message,
                        'is_read' => false,
                        'admin_read' => false,
                        'sent_by' => 'admin',
                    ]);

                    // Log admin action
                    AdminAction::create([
                        'admin_id' => $admin->id,
                        'action_type' => 'sent_message',
                        'actionable_type' => Message::class,
                        'actionable_id' => $messageRecord->id,
                        'description' => "Sent bulk notification to user {$user->fullname} (ID: {$user->id})",
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                    ]);

                    $sentCount++;
                } catch (Exception $e) {
                    Log::error("Error sending notification to user {$user->id}: ".$e->getMessage());
                    $failedCount++;
                }
            }

            $message = "Bulk notification sent successfully! {$sentCount} notification(s) sent.";
            if ($failedCount > 0) {
                $message .= " {$failedCount} notification(s) failed.";
            }

            return redirect()->route('admin.bulk-notification')
                ->with('success', $message);

        } catch (ValidationException $e) {
            return redirect()->route('admin.bulk-notification')
                ->withErrors($e->errors())
                ->withInput();
        } catch (Exception $e) {
            Log::error('Error sending bulk notification: '.$e->getMessage());

            return redirect()->route('admin.bulk-notification')
                ->with('error', 'Error sending bulk notification. Please try again.')
                ->withInput();
        }
    }

    /**
     * Get filtered users based on criteria.
     */
    private function getFilteredUsers(Request $request, string $filterType)
    {
        $query = Registration::query();

        switch ($filterType) {
            case 'all':
                $query->whereHas('applications', function ($q) {
                    $q->where('application_type', 'IRINN')
                        ->whereNotNull('membership_id');
                });
                break;

            case 'payment_status':
                $paymentStatus = $request->input('payment_status');
                $query->whereHas('applications', function ($q) use ($paymentStatus) {
                    $q->where('application_type', 'IRINN')
                        ->whereNotNull('membership_id')
                        ->whereHas('invoices', function ($invQuery) use ($paymentStatus) {
                            if ($paymentStatus === 'paid') {
                                $invQuery->where('payment_status', 'paid');
                            } elseif ($paymentStatus === 'pending') {
                                $invQuery->whereIn('payment_status', ['pending', 'partial']);
                            } elseif ($paymentStatus === 'overdue') {
                                $invQuery->where('payment_status', 'overdue');
                            }
                        });
                });
                break;

            case 'user_wise':
                $userIds = $request->input('user_ids', []);
                $query->whereIn('id', $userIds);
                break;

            case 'application_status':
                $appStatus = $request->input('application_status');
                $query->whereHas('applications', function ($q) use ($appStatus) {
                    $q->where('application_type', 'IRINN')
                        ->whereNotNull('membership_id');
                    if ($appStatus === 'live') {
                        $q->where('is_active', true);
                    } else {
                        $q->where('is_active', false);
                    }
                });
                break;
        }

        return $query->distinct()->get();
    }
}
