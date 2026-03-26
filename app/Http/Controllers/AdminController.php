<?php

namespace App\Http\Controllers;

use App\Mail\IxApplicationInvoiceMail;
use App\Mail\IxInvoiceCancellationMail;
use App\Mail\IxInvoiceCreditNoteMail;
use App\Mail\ProfileUpdateApprovedMail;
use App\Models\Admin;
use App\Models\AdminAction;
use App\Models\Application;
use App\Models\ApplicationGstChangeHistory;
use App\Models\ApplicationGstUpdateRequest;
use App\Models\ApplicationStatusHistory;
use App\Models\GstVerification;
use App\Models\Invoice;
use App\Models\IxApplicationPricing;
use App\Models\IxInvoiceCronLog;
use App\Models\IxLocation;
use App\Models\IxPortPricing;
use App\Models\McaVerification;
use App\Models\Message;
use App\Models\PanVerification;
use App\Models\PaymentAllocation;
use App\Models\PaymentTransaction;
use App\Models\PaymentVerificationLog;
use App\Models\PlanChangeRequest;
use App\Models\ProfileUpdateRequest;
use App\Models\Registration;
use App\Models\RocIecVerification;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketMessage;
use App\Models\UdyamVerification;
use App\Models\UserKycProfile;
use App\Services\IxMembershipInvoiceService;
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

            $approvedApplications = 0;
            if ($roleToUse === 'ix_processor') {
                // Approved = at IX Legal, IX Head, CEO, Nodal Officer, IX Tech Team, IX Account, or Completed
                $approvedApplications = Application::where('application_type', 'IX')
                    ->whereIn('status', [
                        'processor_forwarded_legal', // IX Legal
                        'legal_forwarded_head', 'ceo_sent_back_head', // IX Head
                        'head_forwarded_ceo', // CEO
                        'ceo_approved', 'port_hold', 'port_not_feasible', 'customer_denied', // Nodal Officer
                        'port_assigned', // IX Tech Team
                        'ip_assigned', 'invoice_pending', // IX Account
                        'payment_verified', 'approved', // Completed
                    ])
                    ->count();
            } elseif ($roleToUse === 'ix_legal') {
                // Approved = at IX Head, CEO, Nodal Officer, IX Tech Team, IX Account, or Completed
                $approvedApplications = Application::where('application_type', 'IX')
                    ->whereIn('status', [
                        'legal_forwarded_head', 'ceo_sent_back_head', // IX Head
                        'head_forwarded_ceo', // CEO
                        'ceo_approved', 'port_hold', 'port_not_feasible', 'customer_denied', // Nodal Officer
                        'port_assigned', // IX Tech Team
                        'ip_assigned', 'invoice_pending', // IX Account
                        'payment_verified', 'approved', // Completed
                    ])
                    ->count();
            } elseif ($roleToUse === 'ix_head') {
                // Approved = at CEO, Nodal Officer, IX Tech Team, IX Account, or Completed
                $approvedApplications = Application::where('application_type', 'IX')
                    ->whereIn('status', [
                        'head_forwarded_ceo', // CEO
                        'ceo_approved', 'port_hold', 'port_not_feasible', 'customer_denied', // Nodal Officer
                        'port_assigned', // IX Tech Team
                        'ip_assigned', 'invoice_pending', // IX Account
                        'payment_verified', 'approved', // Completed
                    ])
                    ->count();
            } elseif ($roleToUse === 'ceo') {
                // Approved = at Nodal Officer, IX Tech Team, IX Account, or Completed
                $approvedApplications = Application::where('application_type', 'IX')
                    ->whereIn('status', [
                        'ceo_approved', 'port_hold', 'port_not_feasible', 'customer_denied', // Nodal Officer
                        'port_assigned', // IX Tech Team
                        'ip_assigned', 'invoice_pending', // IX Account
                        'payment_verified', 'approved', // Completed
                    ])
                    ->count();
            } elseif ($roleToUse === 'nodal_officer') {
                // Approved = at IX Tech Team, IX Account, or Completed
                $approvedApplications = Application::where('application_type', 'IX')
                    ->whereIn('status', [
                        'port_assigned', // IX Tech Team
                        'ip_assigned', 'invoice_pending', // IX Account
                        'payment_verified', 'approved', // Completed
                    ])
                    ->count();
            } elseif ($roleToUse === 'ix_tech_team') {
                // Approved = at IX Account or Completed
                $approvedApplications = Application::where('application_type', 'IX')
                    ->whereIn('status', [
                        'ip_assigned', 'invoice_pending', // IX Account
                        'payment_verified', 'approved', // Completed
                    ])
                    ->count();
            } elseif ($roleToUse === 'ix_account') {
                // Approved = Completed only
                $approvedApplications = Application::where('application_type', 'IX')
                    ->whereIn('status', [
                        'payment_verified', 'approved', // Completed
                    ])
                    ->count();
            } elseif ($roleToUse === 'processor') {
                // Legacy - Approved = at Finance, Technical, or Completed
                $approvedApplications = Application::whereIn('status', [
                    'processor_approved', 'finance_review', // Finance
                    'finance_approved', // Technical
                    'approved', 'payment_verified', // Completed
                ])->count();
            } elseif ($roleToUse === 'finance') {
                // Legacy - Approved = at Technical or Completed
                $approvedApplications = Application::whereIn('status', [
                    'finance_approved', // Technical
                    'approved', 'payment_verified', // Completed
                ])->count();
            } elseif ($roleToUse === 'technical') {
                // Legacy - Approved = Completed only
                $approvedApplications = Application::whereIn('status', [
                    'approved', 'payment_verified', // Completed
                ])->count();
            } else {
                // If no role selected, show all completed applications
                $approvedApplications = Application::whereIn('status', ['approved', 'payment_verified'])->count();
            }

            // Approved applications with payment verification
            $approvedApplicationsWithPayment = Application::whereIn('status', ['approved', 'payment_verified'])
                ->whereHas('paymentTransactions', function ($q) {
                    $q->where('payment_status', 'success');
                })
                ->count();

            // Member Statistics (Applications with membership_id)
            // Total members: All applications with membership_id and application_type = 'IX'
            $totalMembers = Application::whereNotNull('membership_id')
                ->where('application_type', 'IX')
                ->count();

            // Live members: Have membership_id AND is_active = true
            $activeMembers = Application::whereNotNull('membership_id')
                ->where('application_type', 'IX')
                ->where('is_active', true)
                ->count();

            // Not live members: Have membership_id but is_active = false
            $disconnectedMembers = Application::whereNotNull('membership_id')
                ->where('application_type', 'IX')
                ->where('is_active', false)
                ->count();

            // Recent Live Members (applications with membership_id and is_active = true, ordered by most recent)
            $recentLiveMembers = Application::with('user')
                ->whereNotNull('membership_id')
                ->where('is_active', true)
                ->orderBy('updated_at', 'desc')
                ->take(10)
                ->get();

            // IX Points Statistics
            $totalIxPoints = IxLocation::where('is_active', true)->count();
            $edgeIxPoints = IxLocation::where('is_active', true)->where('node_type', 'edge')->count();
            $metroIxPoints = IxLocation::where('is_active', true)->where('node_type', 'metro')->count();

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

            // New IX workflow roles
            if ($roleToUse === 'ix_processor') {
                $pendingApplications = Application::where('application_type', 'IX')
                    ->whereIn('status', ['submitted', 'resubmitted', 'processor_resubmission', 'legal_sent_back', 'head_sent_back'])
                    ->count();
            } elseif ($roleToUse === 'ix_legal') {
                $pendingApplications = Application::where('application_type', 'IX')
                    ->where('status', 'processor_forwarded_legal')
                    ->count();
            } elseif ($roleToUse === 'ix_head') {
                $pendingApplications = Application::where('application_type', 'IX')
                    ->where('status', 'legal_forwarded_head')
                    ->count();
            } elseif ($roleToUse === 'ceo') {
                $pendingApplications = Application::where('application_type', 'IX')
                    ->where('status', 'head_forwarded_ceo')
                    ->count();
            } elseif ($roleToUse === 'nodal_officer') {
                $pendingApplications = Application::where('application_type', 'IX')
                    ->where('status', 'ceo_approved')
                    ->count();
            } elseif ($roleToUse === 'ix_tech_team') {
                $pendingApplications = Application::where('application_type', 'IX')
                    ->where('status', 'port_assigned')
                    ->count();
            } elseif ($roleToUse === 'ix_account') {
                $pendingApplications = Application::where('application_type', 'IX')
                    ->whereIn('status', ['ip_assigned', 'invoice_pending'])
                    ->count();
            } elseif ($roleToUse === 'processor') {
                // Legacy
                $pendingApplications = Application::whereIn('status', ['pending', 'processor_review'])
                    ->count();
            } elseif ($roleToUse === 'finance') {
                // Legacy
                $pendingApplications = Application::whereIn('status', ['processor_approved', 'finance_review'])
                    ->count();
            } elseif ($roleToUse === 'technical') {
                // Legacy
                $pendingApplications = Application::where('status', 'finance_approved')
                    ->count();
            } else {
                // If no role selected, show all pending IX applications
                $pendingApplications = Application::where('application_type', 'IX')
                    ->whereNotIn('status', ['approved', 'rejected', 'ceo_rejected', 'payment_verified'])
                    ->count();
            }

            $recentUsers = Registration::latest()->take(10)->get();

            // Recent members (applications with membership_id, ordered by most recent)
            $recentMembers = Application::with('user')
                ->whereNotNull('membership_id')
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
                'closedGrievances',
                'totalIxPoints',
                'edgeIxPoints',
                'metroIxPoints'
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
            } elseif ($stageFilter && in_array($stageFilter, ['ix_processor', 'ix_head', 'ix_account'], true)) {
                // Legacy IX mapping kept for backward compatibility
                $stageStatusMap = [
                    'ix_processor' => ['submitted', 'resubmitted', 'processor_resubmission', 'legal_sent_back', 'head_sent_back', 'pending', 'processor_review'],
                    'ix_head'      => ['legal_forwarded_head', 'ceo_sent_back_head'],
                    'ix_account'   => ['ip_assigned', 'invoice_pending'],
                ];

                if (isset($stageStatusMap[$stageFilter])) {
                    $applicationsQuery->whereIn('status', $stageStatusMap[$stageFilter]);
                }
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
                'email' => 'required|email|unique:registrations,email,' . $id,
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
            Log::error('Error updating user email: ' . $e->getMessage());
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

            $filename = 'transactions_' . $user->registrationid . '_' . date('Y-m-d_His') . '.csv';

            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];

            $callback = function () use ($transactions) {
                $file = fopen('php://output', 'w');
                fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

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
                    if (!$bankRef && $transaction->response_message) {
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
            Log::error('Error exporting transactions: ' . $e->getMessage());
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

            $filename = 'admin_actions_' . $user->registrationid . '_' . date('Y-m-d_His') . '.csv';

            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];

            $callback = function () use ($adminActions) {
                $file = fopen('php://output', 'w');
                fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

                fputcsv($file, [
                    'Date/Time',
                    'Admin',
                    'Action',
                    'Description',
                ]);

                foreach ($adminActions as $action) {
                    $adminName = 'System';
                    if ($action->superAdmin) {
                        $adminName = 'SuperAdmin: ' . $action->superAdmin->name;
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
            Log::error('Error exporting admin actions: ' . $e->getMessage());
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

            // Query applications directly - members are applications with membership_id
            $query = Application::whereNotNull('membership_id')
                ->where('application_type', 'IX');

            if (in_array($filter, ['active', 'live'], true)) {
                $query->where('service_status', 'live');
            } elseif (in_array($filter, ['suspended', 'disconnected'], true)) {
                $query->where('service_status', $filter);
            }

            // Payment filter for IX Account admins
            $isIxAccount = $this->hasRole($admin, 'ix_account');
            $showExportReports = $isIxAccount && $selectedRole === 'ix_account';
            if ($isIxAccount && $paymentFilter) {
                if ($paymentFilter === 'generated') {
                    // Applications with generated invoices
                    $query->where('service_status', 'live')
                        ->whereHas('invoices');
                } elseif ($paymentFilter === 'received') {
                    // Applications with paid invoices
                    $query->where('service_status', 'live')
                        ->whereHas('invoices', function ($invoiceQuery) {
                            $invoiceQuery->where('payment_status', 'paid');
                        });
                } elseif ($paymentFilter === 'pending') {
                    // Applications with pending/partial/overdue invoices
                    $query->where('service_status', 'live')
                        ->whereHas('invoices', function ($invoiceQuery) {
                            $invoiceQuery->whereIn('payment_status', ['pending', 'partial', 'overdue']);
                        });
                }
            }

            // GST verification filter
            if ($gstVerificationFilter && $gstVerificationFilter !== 'all') {
                if ($gstVerificationFilter === 'verified') {
                    // Applications with verified GST
                    $query->whereHas('gstVerification', function ($gstQuery) {
                        $gstQuery->where('is_verified', true);
                    });
                } elseif ($gstVerificationFilter === 'unverified') {
                    // Applications without verified GST or with unverified GST
                    $query->where(function ($subQuery) {
                        $subQuery->whereDoesntHave('gstVerification')
                            ->orWhereHas('gstVerification', function ($gstQuery) {
                                $gstQuery->where('is_verified', false);
                            });
                    });
                }
            }

            // Zone filter - filter by location zone
            $zone = $request->get('zone');
            if ($zone) {
                // Get location IDs that match the zone
                $locationIds = \App\Models\IxLocation::where('zone', $zone)
                    ->pluck('id')
                    ->toArray();

                if (! empty($locationIds)) {
                    $query->where(function ($q) use ($zone, $locationIds) {
                        // Check if zone matches in JSON
                        $q->whereRaw("JSON_EXTRACT(application_data, '$.location.zone') = ?", [json_encode($zone)])
                            ->orWhereRaw("JSON_EXTRACT(application_data, '$.location.zone') LIKE ?", ["%{$zone}%"]);

                        // Check if location ID matches (location ID is stored as integer in JSON)
                        $locationIdConditions = [];
                        foreach ($locationIds as $locationId) {
                            $locationIdConditions[] = "JSON_EXTRACT(application_data, '$.location.id') = {$locationId}";
                        }
                        if (! empty($locationIdConditions)) {
                            $q->orWhereRaw('('.implode(' OR ', $locationIdConditions).')');
                        }
                    });
                }
            }

            // Nodal officer filter - filter by location nodal officer
            $nodalOfficer = $request->get('nodal_officer');
            if ($nodalOfficer) {
                // Get location IDs that match the nodal officer
                $locationIds = \App\Models\IxLocation::where('nodal_officer', $nodalOfficer)
                    ->pluck('id')
                    ->toArray();

                if (! empty($locationIds)) {
                    $query->where(function ($q) use ($nodalOfficer, $locationIds) {
                        // Check if nodal_officer matches in JSON
                        $q->whereRaw("JSON_EXTRACT(application_data, '$.location.nodal_officer') = ?", [json_encode($nodalOfficer)])
                            ->orWhereRaw("JSON_EXTRACT(application_data, '$.location.nodal_officer') LIKE ?", ["%{$nodalOfficer}%"]);

                        // Check if location ID matches (location ID is stored as integer in JSON)
                        $locationIdConditions = [];
                        foreach ($locationIds as $locationId) {
                            $locationIdConditions[] = "JSON_EXTRACT(application_data, '$.location.id') = {$locationId}";
                        }
                        if (! empty($locationIdConditions)) {
                            $q->orWhereRaw('('.implode(' OR ', $locationIdConditions).')');
                        }
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

            // Payment statistics for IX Account admins
            $paymentStats = null;
            $currentMonthStats = null;
            if ($isIxAccount) {
                $liveApplications = Application::whereNotNull('membership_id')
                    ->where('service_status', 'live')
                    ->where('application_type', 'IX')
                    ->get();

                $baseInvoiceQuery = fn ($q) => $q->whereNotNull('membership_id')
                    ->where('service_status', 'live')
                    ->where('application_type', 'IX');

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
                ->where('application_type', 'IX')
                ->where('service_status', 'live')
                ->with('user')
                ->orderBy('created_at', 'desc')
                ->get();

            // Get zones and nodal officers for filters
            $zones = \App\Models\IxLocation::whereNotNull('zone')
                ->distinct()
                ->orderBy('zone')
                ->pluck('zone')
                ->filter()
                ->values();

            $nodalOfficers = \App\Models\IxLocation::whereNotNull('nodal_officer')
                ->distinct()
                ->orderBy('nodal_officer')
                ->pluck('nodal_officer')
                ->filter()
                ->values();

            return view('admin.members.index', compact('members', 'admin', 'filter', 'paymentStats', 'currentMonthStats', 'paymentFilter', 'isIxAccount', 'showExportReports', 'allLiveMembers', 'gstVerificationFilter', 'zones', 'nodalOfficers'));
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

            // Query applications directly - members are applications with membership_id
            $query = Application::whereNotNull('membership_id')
                ->where('application_type', 'IX');

            // Apply filter
            if (in_array($filter, ['active', 'live'], true)) {
                $query->where('service_status', 'live');
            } elseif (in_array($filter, ['suspended', 'disconnected'], true)) {
                $query->where('service_status', $filter);
            }

            // Payment filter for IX Account admins
            $isIxAccount = $this->hasRole($admin, 'ix_account');
            if ($isIxAccount && $paymentFilter) {
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

            // Zone filter - filter by location zone
            if ($zone) {
                // Get location IDs that match the zone
                $locationIds = \App\Models\IxLocation::where('zone', $zone)
                    ->pluck('id')
                    ->toArray();

                if (! empty($locationIds)) {
                    $query->where(function ($q) use ($zone, $locationIds) {
                        // Check if zone matches in JSON
                        $q->whereRaw("JSON_EXTRACT(application_data, '$.location.zone') = ?", [json_encode($zone)])
                            ->orWhereRaw("JSON_EXTRACT(application_data, '$.location.zone') LIKE ?", ["%{$zone}%"]);

                        // Check if location ID matches (location ID is stored as integer in JSON)
                        $locationIdConditions = [];
                        foreach ($locationIds as $locationId) {
                            $locationIdConditions[] = "JSON_EXTRACT(application_data, '$.location.id') = {$locationId}";
                        }
                        if (! empty($locationIdConditions)) {
                            $q->orWhereRaw('('.implode(' OR ', $locationIdConditions).')');
                        }
                    });
                }
            }

            // Nodal officer filter - filter by location nodal officer
            if ($nodalOfficer) {
                // Get location IDs that match the nodal officer
                $locationIds = \App\Models\IxLocation::where('nodal_officer', $nodalOfficer)
                    ->pluck('id')
                    ->toArray();

                if (! empty($locationIds)) {
                    $query->where(function ($q) use ($nodalOfficer, $locationIds) {
                        // Check if nodal_officer matches in JSON
                        $q->whereRaw("JSON_EXTRACT(application_data, '$.location.nodal_officer') = ?", [json_encode($nodalOfficer)])
                            ->orWhereRaw("JSON_EXTRACT(application_data, '$.location.nodal_officer') LIKE ?", ["%{$nodalOfficer}%"]);

                        // Check if location ID matches (location ID is stored as integer in JSON)
                        $locationIdConditions = [];
                        foreach ($locationIds as $locationId) {
                            $locationIdConditions[] = "JSON_EXTRACT(application_data, '$.location.id') = {$locationId}";
                        }
                        if (! empty($locationIdConditions)) {
                            $q->orWhereRaw('('.implode(' OR ', $locationIdConditions).')');
                        }
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
    public function exportInvoiceAmounts(Request $request)
    {
        // Check permissions first (before any output)
        $admin = $this->getCurrentAdmin();
        $selectedRole = session('admin_selected_role');

        // Only IX Account admins can export invoice amounts
        if ($selectedRole !== 'ix_account') {
            abort(403, 'You do not have permission to perform this action.');
        }

        // Increase execution time limit for large exports
        set_time_limit(300); // 5 minutes
        ini_set('max_execution_time', 300);

        // Prevent any output before CSV
        if (ob_get_level()) {
            ob_end_clean();
        }

        try {
            // Use the exact same query logic as the cron job (GenerateMonthlyIxInvoices)
            $query = Application::query()
                ->where('application_type', 'IX')
                ->where('is_active', true)
                ->whereNotNull('service_activation_date')
                ->whereNotNull('billing_cycle')
                ->where(function ($q) {
                    $q->whereNull('service_status')
                        ->orWhere('service_status', 'live');
                })
                ->with('user');

            $applications = $query->get();

            $exportData = [];
            $exportData[] = [
                'Membership ID',
                'Registered User Name',
                'Node',
                'Port Capacity',
                'Billing Cycle',
                'Billing Period',
                'Billing Start Date',
                'Billing End Date',
                'Base Amount',
                'IGST Amount',
                'CGST Amount',
                'SGST Amount',
                'Carry Forward Amount',
                'Carry Forward From Invoices',
                'Total Amount',
                'Status',
            ];

            foreach ($applications as $application) {
                try {
                    // Step 1: Get current billing period (same as cron - line 121)
                    $billingPeriod = $this->getCurrentBillingPeriod($application);
                    if (! $billingPeriod) {
                        continue; // Skip if no billing period can be determined
                    }

                    // Step 2: Check if invoice already exists for this period
                    // Allow regenerate if cancelled or has credit note (skip only if active invoice without credit note exists)
                    $existingInvoice = Invoice::query()
                        ->where('application_id', $application->id)
                        ->where('billing_period', $billingPeriod)
                        ->where('status', '!=', 'cancelled')
                        ->whereNull('credit_note_pdf_path')
                        ->first();

                    if ($existingInvoice) {
                        continue; // Skip if active invoice exists (without credit note)
                    }

                    // If we reach here, either no invoice exists OR invoice exists with credit note - allow regeneration

                    // Step 3: Check if invoice can be generated for this period (same as cron - line 161)
                    $allowed = $this->canGenerateInvoiceForPeriod($application, $billingPeriod);
                    if (! $allowed) {
                        continue; // Skip if not eligible to generate
                    }

                    // Extract node name from application data
                    $nodeName = 'N/A';
                    if ($application->application_data) {
                        $appData = $application->application_data;
                        $locationInfo = $appData['location'] ?? null;
                        if ($locationInfo && isset($locationInfo['name'])) {
                            $nodeName = $locationInfo['name'];
                        }
                    }

                    // Step 4: Calculate invoice details (same as cron - line 179)
                    $invoiceData = $this->calculateInvoiceDetails($application);
                    if (is_array($invoiceData) && isset($invoiceData['error'])) {
                        continue; // Skip applications with errors
                    }

                    $baseAmount = $invoiceData['amount'] ?? 0;
                    $carryForwardAmount = $invoiceData['carry_forward_amount'] ?? 0;
                    $totalAmount = $invoiceData['final_total_amount'] ?? 0;
                    $carryForwardInvoices = $invoiceData['carry_forward_invoices'] ?? [];

                    // IGST/CGST/SGST by buyer vs seller state code (same logic as invoice generation)
                    $kycDetails = $application->kyc_details ?? [];
                    $buyerGstin = $kycDetails['gstin'] ?? $application->gstin ?? '';
                    $buyerStateCode = $this->extractStateCodeFromGstin($buyerGstin);
                    $sellerStateCode = $application->seller_state_code ?? $buyerStateCode;
                    $nixiCredentials = $this->getNixiLocationCredentials($sellerStateCode);
                    $supplierStateCode = $nixiCredentials['supplier_state_code'] ?? '07';
                    $normalizedBuyer = trim((string) $buyerStateCode);
                    $normalizedSupplier = trim((string) $supplierStateCode);
                    $isSameState = ($normalizedBuyer === $normalizedSupplier && $normalizedBuyer !== '' && $normalizedSupplier !== '');

                    if ($isSameState) {
                        $igstAmount = 0;
                        $cgstAmount = round(($baseAmount * 9) / 100, 2);
                        $sgstAmount = round(($baseAmount * 9) / 100, 2);
                    } else {
                        $igstAmount = round(($baseAmount * 18) / 100, 2);
                        $cgstAmount = 0;
                        $sgstAmount = 0;
                    }

                    $status = 'Ready to Generate';
                    $totalAmountDisplay = number_format($totalAmount, 2);

                    if ($totalAmount <= 0) {
                        $status = 'No charges for this period';
                        $totalAmountDisplay = 'No charges for this period';
                    }

                    // Format carry forward invoice numbers
                    $carryForwardFromInvoices = 'N/A';
                    if (! empty($carryForwardInvoices) && $carryForwardAmount > 0) {
                        $invoiceNumbers = array_map(function ($inv) {
                            return $inv['invoice_number'];
                        }, $carryForwardInvoices);
                        $carryForwardFromInvoices = implode(', ', $invoiceNumbers);
                    }

                    $billingStartDateStr = $invoiceData['billing_start_date'] ?? 'N/A';
                    $billingEndDateStr = $invoiceData['billing_end_date'] ?? 'N/A';

                    $portCapacityDisplay = $application->getEffectivePortCapacity() ?? 'N/A';

                    $exportData[] = [
                        $application->membership_id ?? 'N/A',
                        $application->user->fullname ?? 'N/A',
                        $nodeName,
                        $portCapacityDisplay,
                        strtoupper($application->billing_cycle ?? 'N/A'),
                        $billingPeriod,
                        $billingStartDateStr,
                        $billingEndDateStr,
                        number_format($baseAmount, 2),
                        number_format($igstAmount, 2),
                        number_format($cgstAmount, 2),
                        number_format($sgstAmount, 2),
                        number_format($carryForwardAmount, 2),
                        $carryForwardFromInvoices,
                        $totalAmountDisplay,
                        $status,
                    ];
                } catch (Exception $e) {
                    Log::error("Error calculating invoice for application {$application->id}: ".$e->getMessage());

                    // Skip applications with errors instead of adding error rows
                    continue;
                }
            }

            // Generate CSV
            $filename = 'invoice_amounts_export_'.date('Y-m-d_His').'.csv';
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
            Log::error('Error exporting invoice amounts: '.$e->getMessage());

            // Return CSV with error message instead of HTML redirect
            $filename = 'invoice_amounts_export_error_'.date('Y-m-d_His').'.csv';
            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ];

            $errorData = [
                ['Error'],
                ['An error occurred while exporting invoice amounts.'],
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
     * Display cron job report page (IX invoice cron logs) with filters.
     */
    public function cronReport(Request $request)
    {
        $admin = $this->getCurrentAdmin();

        $query = IxInvoiceCronLog::query()
            ->with(['application:id,application_id,membership_id', 'application.user:id,fullname,email'])
            ->orderByDesc('started_at');

        if ($request->filled('run_id')) {
            $query->where('run_id', $request->input('run_id'));
        }
        if ($request->filled('date_from')) {
            $query->whereDate('started_at', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('started_at', '<=', $request->input('date_to'));
        }
        if ($request->filled('status') && $request->input('status') !== '') {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('application_code')) {
            $query->where('application_code', 'like', '%'.$request->input('application_code').'%');
        }

        $logs = $query->paginate(50)->withQueryString();

        return view('admin.cron-report.index', [
            'logs' => $logs,
            'filters' => $request->only(['run_id', 'date_from', 'date_to', 'status', 'application_code']),
        ]);
    }

    /**
     * Export cron job report to Excel (CSV) with same filters.
     */
    public function exportCronReport(Request $request)
    {
        $admin = $this->getCurrentAdmin();

        $query = IxInvoiceCronLog::query()
            ->with(['application:id,application_id,membership_id', 'application.user:id,fullname,email'])
            ->orderByDesc('started_at');

        if ($request->filled('run_id')) {
            $query->where('run_id', $request->input('run_id'));
        }
        if ($request->filled('date_from')) {
            $query->whereDate('started_at', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('started_at', '<=', $request->input('date_to'));
        }
        if ($request->filled('status') && $request->input('status') !== '') {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('application_code')) {
            $query->where('application_code', 'like', '%'.$request->input('application_code').'%');
        }

        $logs = $query->get();

        $exportData = [];
        $exportData[] = [
            'Run ID',
            'Started At',
            'Finished At',
            'Application Code',
            'Membership ID',
            'Member Name',
            'Billing Period',
            'Billing Start',
            'Billing End',
            'Invoice Number',
            'Status',
            'Skip Reason',
            'Error Message',
            'Dry Run',
            'PDF Generated',
            'Mail Sent',
            'E-invoice Attempted',
            'E-invoice IRN',
            'E-invoice Status',
            'E-invoice Error Code',
            'E-invoice Error Message',
        ];

        foreach ($logs as $log) {
            $exportData[] = [
                $log->run_id,
                $log->started_at?->format('Y-m-d H:i:s'),
                $log->finished_at?->format('Y-m-d H:i:s'),
                $log->application_code ?? '',
                $log->application?->membership_id ?? '',
                $log->application?->user?->fullname ?? '',
                $log->billing_period ?? '',
                $log->billing_start_date?->format('Y-m-d'),
                $log->billing_end_date?->format('Y-m-d'),
                $log->invoice_number ?? '',
                $log->status ?? '',
                $log->skip_reason ?? '',
                $log->error_message ?? '',
                $log->is_dry_run ? 'Yes' : 'No',
                $log->pdf_generated ? 'Yes' : 'No',
                $log->mail_sent ? 'Yes' : 'No',
                $log->einvoice_attempted ? 'Yes' : 'No',
                $log->einvoice_irn ?? '',
                $log->einvoice_status ?? '',
                $log->einvoice_error_code ?? '',
                $log->einvoice_error_message ?? '',
            ];
        }

        $filename = 'cron_report_'.date('Y-m-d_His').'.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Pragma' => 'public',
        ];

        if (ob_get_level()) {
            ob_end_clean();
        }

        $callback = function () use ($exportData) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            foreach ($exportData as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

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
                    ->where('application_type', 'IX')
                    ->where('is_active', true);
            });

            // Filter by selected member IDs if provided
            if (! empty($selectedMemberIds) && is_array($selectedMemberIds)) {
                $query->whereIn('id', $selectedMemberIds);
            }

            $members = $query->with([
                'applications' => function ($q) {
                    $q->whereNotNull('membership_id')
                        ->where('application_type', 'IX')
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
                $applications = $member->applications->where('application_type', 'IX')
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
            $query = Application::where('application_type', 'IX')
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
     * Display IX points listing.
     */
    public function ixPoints(Request $request)
    {
        try {
            $query = IxLocation::where('is_active', true);

            // Search functionality
            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('state', 'like', "%{$search}%")
                        ->orWhere('city', 'like', "%{$search}%")
                        ->orWhere('nodal_officer', 'like', "%{$search}%")
                        ->orWhere('zone', 'like', "%{$search}%")
                        ->orWhere('switch_details', 'like', "%{$search}%");
                });
            }

            // Filter by node type
            if ($request->filled('node_type') && in_array($request->node_type, ['edge', 'metro'])) {
                $query->where('node_type', $request->node_type);
            }

            // Filter by state
            if ($request->filled('state')) {
                $query->where('state', $request->state);
            }

            // Filter by zone
            if ($request->filled('zone')) {
                $query->where('zone', $request->zone);
            }

            $locations = $query->orderBy('node_type')
                ->orderBy('state')
                ->orderBy('name')
                ->paginate(20)
                ->withQueryString();

            // Get application counts for each location
            $locationStats = [];
            foreach ($locations as $location) {
                // Count applications for this location using JSON path
                $applications = Application::where('application_type', 'IX')
                    ->whereRaw('JSON_EXTRACT(application_data, "$.location.id") = ?', [$location->id])
                    ->get();

                // Pending applications: applications that are not approved, rejected, payment verified, or at ix_account stage
                $pendingApplications = $applications->filter(function ($app) {
                    return ! in_array($app->status, ['approved', 'rejected', 'ceo_rejected', 'payment_verified', 'ip_assigned', 'invoice_pending']);
                });

                // Live members: applications with membership_id and is_active = true for this location
                $liveMembers = $applications->filter(function ($app) {
                    return $app->membership_id && $app->is_active;
                });

                // Not live members: applications with membership_id but is_active = false for this location
                $notLiveMembers = $applications->filter(function ($app) {
                    return $app->membership_id && ! $app->is_active;
                });

                $locationStats[$location->id] = [
                    'total_applications' => $applications->count(),
                    'live_applications' => $applications->filter(function ($app) {
                        return $app->is_active && $app->assigned_ip && $app->assigned_port_number;
                    })->count(),
                    'approved_applications' => $applications->whereIn('status', ['approved', 'payment_verified'])->count(),
                    'pending_applications' => $pendingApplications->count(),
                    'live_members' => $liveMembers->count(),
                    'not_live_members' => $notLiveMembers->count(),
                ];
            }

            // Get unique states and zones for filters
            $states = IxLocation::where('is_active', true)
                ->distinct()
                ->orderBy('state')
                ->pluck('state')
                ->filter()
                ->values();

            $zones = IxLocation::where('is_active', true)
                ->distinct()
                ->orderBy('zone')
                ->pluck('zone')
                ->filter()
                ->values();

            return view('admin.ix-points.index', compact('locations', 'locationStats', 'states', 'zones'));
        } catch (Exception $e) {
            Log::error('Error loading IX points: '.$e->getMessage());

            return redirect()->route('admin.dashboard')
                ->with('error', 'Unable to load IX points right now.');
        }
    }

    /**
     * Display IX point details.
     */
    public function showIxPoint(Request $request, $id)
    {
        try {
            $location = IxLocation::where('is_active', true)->findOrFail($id);

            // Get application counts for this location
            $applications = Application::where('application_type', 'IX')
                ->whereRaw('JSON_EXTRACT(application_data, "$.location.id") = ?', [$location->id])
                ->get();

            // Pending applications: applications that are not approved, rejected, payment verified, or at ix_account stage
            $pendingApplications = $applications->filter(function ($app) {
                return ! in_array($app->status, ['approved', 'rejected', 'ceo_rejected', 'payment_verified', 'ip_assigned', 'invoice_pending']);
            });

            // Live members: applications with membership_id and is_active = true for this location
            $liveMembers = $applications->filter(function ($app) {
                return $app->membership_id && $app->is_active;
            });

            // Not live members: applications with membership_id but is_active = false for this location
            $notLiveMembers = $applications->filter(function ($app) {
                return $app->membership_id && ! $app->is_active;
            });

            $locationStats = [
                'pending_applications' => $pendingApplications->count(),
                'live_members' => $liveMembers->count(),
                'not_live_members' => $notLiveMembers->count(),
            ];

            return view('admin.ix-points.show', compact('location', 'locationStats'));
        } catch (Exception $e) {
            Log::error('Error loading IX point details: '.$e->getMessage());

            return redirect()->route('admin.ix-points')
                ->with('error', 'Unable to load IX point details right now.');
        }
    }

    /**
     * Get applications for a specific IX location.
     */
    public function ixPointApplications(Request $request, $locationId)
    {
        try {
            $location = IxLocation::where('is_active', true)->findOrFail($locationId);

            $query = Application::with(['user'])
                ->where('application_type', 'IX')
                ->whereRaw('JSON_EXTRACT(application_data, "$.location.id") = ?', [$location->id]);

            // Filter by live status
            $liveFilter = $request->get('live');
            if ($liveFilter === 'true') {
                $query->where('is_active', true)
                    ->whereNotNull('assigned_ip')
                    ->whereNotNull('assigned_port_number');
            } elseif ($liveFilter === 'false') {
                $query->where(function ($q) {
                    $q->where('is_active', false)
                        ->orWhereNull('assigned_ip')
                        ->orWhereNull('assigned_port_number');
                });
            }

            // Search
            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('application_id', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('fullname', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            }

            $applications = $query->orderBy('created_at', 'desc')
                ->paginate(20)
                ->withQueryString();

            return view('admin.ix-points.applications', compact('location', 'applications'));
        } catch (Exception $e) {
            Log::error('Error loading IX point applications: '.$e->getMessage());

            return redirect()->route('admin.ix-points')
                ->with('error', 'Unable to load applications right now.');
        }
    }

    /**
     * Get members for a specific IX location.
     */
    public function ixPointMembers(Request $request, $locationId)
    {
        try {
            $location = IxLocation::where('is_active', true)->findOrFail($locationId);

            $query = Registration::whereHas('applications', function ($q) use ($location) {
                $q->where('application_type', 'IX')
                    ->whereRaw('JSON_EXTRACT(application_data, "$.location.id") = ?', [$location->id])
                    ->whereNotNull('membership_id');
            });

            // Filter by live status
            $liveFilter = $request->get('live');
            if ($liveFilter === 'true') {
                $query->whereHas('applications', function ($q) use ($location) {
                    $q->where('application_type', 'IX')
                        ->whereRaw('JSON_EXTRACT(application_data, "$.location.id") = ?', [$location->id])
                        ->whereNotNull('membership_id')
                        ->where('is_active', true);
                });
            } elseif ($liveFilter === 'false') {
                $query->whereHas('applications', function ($q) use ($location) {
                    $q->where('application_type', 'IX')
                        ->whereRaw('JSON_EXTRACT(application_data, "$.location.id") = ?', [$location->id])
                        ->whereNotNull('membership_id')
                        ->where('is_active', false);
                });
            }

            // Search
            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('fullname', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('registrationid', 'like', "%{$search}%");
                });
            }

            $members = $query->with(['applications' => function ($q) use ($location) {
                $q->where('application_type', 'IX')
                    ->whereRaw('JSON_EXTRACT(application_data, "$.location.id") = ?', [$location->id])
                    ->whereNotNull('membership_id')
                    ->latest();
            }])->distinct()->orderBy('created_at', 'desc')
                ->paginate(20)
                ->withQueryString();

            return view('admin.ix-points.members', compact('location', 'members'));
        } catch (Exception $e) {
            Log::error('Error loading IX point members: '.$e->getMessage());

            return redirect()->route('admin.ix-points')
                ->with('error', 'Unable to load members right now.');
        }
    }

    /**
     * Display applications based on admin role.
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
                    'helpdesk' => ['hostmaster', 'billing'],
                    'hostmaster' => ['billing'],
                    'billing' => ['billing'],
                ];

                if ($roleToUse && isset($approvedStatusMap[$roleToUse])) {
                    $query->whereIn('status', $approvedStatusMap[$roleToUse]);
                } else {
                    // If no role or role not in map, show all applications at final stage
                    $query->whereIn('status', ['billing']);
                }
            }

            // Filter by assigned role (binded to status)
            if ($roleFilter && ! $approvedFilter) {
                // IRINN simplified workflow: status equals stage
                $roleStatusMap = [
                    'helpdesk' => ['helpdesk'],
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
                    'helpdesk' => ['helpdesk'],
                    'hostmaster' => ['hostmaster'],
                    'billing' => ['billing'],
                ];

                if (isset($roleStatusMap[$roleFilter])) {
                    $query->whereIn('status', $roleStatusMap[$roleFilter]);
                }
            } elseif ($selectedRole && in_array($selectedRole, ['helpdesk', 'hostmaster', 'billing'], true)) {
                $roleStatusMap = [
                    'helpdesk' => ['helpdesk'],
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

            $filename = 'applications_export_' . date('Y-m-d_His') . '.csv';

            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];

            $callback = function () use ($applications) {
                $file = fopen('php://output', 'w');
                fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

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
            Log::error('Error exporting applications: ' . $e->getMessage());
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

            // Auto-mark backend registered applications as paid
            if ($selectedRole === 'ix_account' && $application->is_active && $application->isVisibleToIxAccount()) {
                // Check if this is a backend registered application (payment_mode = 'test' or 'backend_entry')
                $backendPayment = \App\Models\PaymentTransaction::where('application_id', $application->id)
                    ->whereIn('payment_mode', ['test', 'backend_entry'])
                    ->where('payment_status', 'success')
                    ->first();

                if ($backendPayment && ! $application->service_activation_date) {
                    // This is a backend registered application without service activation date
                    // Auto-mark initial payment as verified if not already verified
                    $hasInitialVerification = $application->paymentVerificationLogs()
                        ->where('verification_type', 'initial')
                        ->exists();

                    if (! $hasInitialVerification) {
                        \App\Models\PaymentVerificationLog::create([
                            'application_id' => $application->id,
                            'verified_by' => null, // System auto-verification
                            'verification_type' => 'initial',
                            'billing_period' => null,
                            'amount' => $backendPayment->amount,
                            'currency' => 'INR',
                            'payment_method' => 'backend_entry',
                            'payment_id' => $backendPayment->transaction_id,
                            'notes' => 'Auto-verified: Payment completed via backend data entry',
                            'verified_at' => now('Asia/Kolkata'),
                        ]);
                    }
                }
            }

            // Check if invoice can be generated for IX Account
            $canGenerateInvoice = false;
            $invoiceGenerationMessage = null;
            $currentBillingPeriod = null;
            $currentInvoice = null;

            if ($selectedRole === 'ix_account' && $application->is_active && $application->isVisibleToIxAccount()) {
                if ($application->service_activation_date && $application->billing_cycle) {
                    // Application is live - check if invoice can be generated
                    $currentBillingPeriod = $this->getCurrentBillingPeriod($application);
                    if ($currentBillingPeriod) {
                        // Check if invoice already exists for this period (allow regenerate if cancelled or credit note generated)
                        $existingInvoice = \App\Models\Invoice::where('application_id', $application->id)
                            ->where('billing_period', $currentBillingPeriod)
                            ->where('status', '!=', 'cancelled')
                            ->whereNull('credit_note_pdf_path')
                            ->first();

                        if ($existingInvoice) {
                            $invoiceGenerationMessage = "Invoice for this billing period already exists: {$existingInvoice->invoice_number}";
                            $currentInvoice = $existingInvoice;
                            // Don't allow generation if active invoice exists
                        } else {
                            // Check if invoice with credit note exists for current period - allow regeneration
                            $invoiceWithCreditNote = \App\Models\Invoice::where('application_id', $application->id)
                                ->where('billing_period', $currentBillingPeriod)
                                ->where('status', '!=', 'cancelled')
                                ->whereNotNull('credit_note_pdf_path')
                                ->first();

                            if ($invoiceWithCreditNote) {
                                // Allow regeneration - invoice with credit note exists for current period
                                $invoiceGenerationMessage = 'Credit note exists for this period. You can generate a new invoice.';
                                $currentInvoice = $invoiceWithCreditNote;
                                $canGenerateInvoice = true; // Allow generation when credit note exists
                            } else {
                                // Check if ANY invoice with credit note exists for this application (regardless of period)
                                // This handles cases where credit note was generated for a past period
                                $anyCreditNoteInvoice = \App\Models\Invoice::where('application_id', $application->id)
                                    ->where('status', '!=', 'cancelled')
                                    ->whereNotNull('credit_note_pdf_path')
                                    ->exists();

                                if ($anyCreditNoteInvoice) {
                                    // Allow generation if any credit note exists (admin can choose period)
                                    $canGenerateInvoice = true;
                                    $invoiceGenerationMessage = 'Credit note exists for this application. You can generate a new invoice.';
                                } else {
                                    // No invoice exists - check if we can generate invoice one month in advance
                                    $canGenerateInvoice = $this->canGenerateInvoiceForPeriod($application, $currentBillingPeriod);
                                    if (! $canGenerateInvoice) {
                                        $invoiceGenerationMessage = 'Invoice can only be generated one month before the billing period starts.';
                                    }
                                }
                            }
                        }
                    }
                } else {
                    // Application not yet live - cannot generate invoice
                    $invoiceGenerationMessage = 'Invoice can only be generated for LIVE applications.';
                }
            }

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

            $canManageInvoices = $this->hasRole($admin, 'ix_account');

            // Choose view based on application type (IRINN uses simplified IRINN layout)
            $viewName = $application->application_type === 'IRINN'
                ? 'admin.applications.show-irinn'
                : 'admin.applications.show';

            // Admin can view all applications, but can only take actions on applications for their selected role
            return view($viewName, compact(
                'application',
                'admin',
                'selectedRole',
                'canManageInvoices',
                'canGenerateInvoice',
                'invoiceGenerationMessage',
                'currentBillingPeriod',
                'currentInvoice',
                'sellerGstOptions'
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
     * IRINN: Change application stage (helpdesk -> hostmaster -> billing).
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
                'target_stage' => 'required|string|in:helpdesk,hostmaster,billing',
            ]);

            $targetStage = $request->input('target_stage');

            // Role-based permission: which role can move to which stage
            $role = session('admin_selected_role');
            if (! $role || ! in_array($role, ['helpdesk', 'hostmaster', 'billing'], true)) {
                return back()->with('error', 'You do not have permission to change IRINN stages.');
            }

            // Allowed transitions:
            // helpdesk admin: helpdesk -> hostmaster
            // hostmaster admin: hostmaster -> billing
            $current = $application->status ?? 'helpdesk';
            $allowed = false;

            if ($role === 'helpdesk' && $current === 'helpdesk' && $targetStage === 'hostmaster') {
                $allowed = true;
            } elseif ($role === 'hostmaster' && $current === 'hostmaster' && $targetStage === 'billing') {
                $allowed = true;
            }

            if (! $allowed) {
                return back()->with('error', 'Invalid stage transition for your role.');
            }

            $oldStatus = $current;
            $application->update([
                'status' => $targetStage,
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

            // Notify user
            $this->createMessageForAdmin(
                $admin->id,
                $application->user_id,
                'IRINN Application Resubmission Requested',
                "Your IRINN application {$application->application_id} requires resubmission:\n\n".$request->input('resubmission_reason')
            );

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
     * Show comprehensive application details (similar to user side).
     */
    public function showApplicationComprehensive($id)
    {
        try {
            $admin = $this->getCurrentAdmin();
            $application = Application::with(['user', 'statusHistory', 'gstChangeHistory', 'planChangeRequests'])
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
            $applicationData = $application->application_data ?? [];
            $filePath = null;

            // IRINN documents are stored in part3 / part4
            if ($application->application_type === 'IRINN') {
                if (isset($applicationData['part3'][$documentKey])) {
                    $filePath = $applicationData['part3'][$documentKey];
                } elseif (isset($applicationData['part4'][$documentKey])) {
                    $filePath = $applicationData['part4'][$documentKey];
                }
            } else {
                // IX documents are stored in documents / pdfs
                $documents = $applicationData['documents'] ?? [];
                $pdfs = $applicationData['pdfs'] ?? [];
                if (isset($pdfs[$documentKey])) {
                    $filePath = $pdfs[$documentKey];
                } elseif (isset($documents[$documentKey])) {
                    $filePath = $documents[$documentKey];
                }
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
                // For application_pdf, try to generate if missing
                if ($documentKey === 'application_pdf' && $application->application_type === 'IX') {
                    app(IxApplicationController::class)->ensureApplicationPdfExists($application);
                    $application->refresh();
                    $applicationData = $application->application_data ?? [];
                    $pdfs = $applicationData['pdfs'] ?? [];
                    $filePath = $pdfs['application_pdf'] ?? null;
                }
                if (! $filePath) {
                    abort(404, 'Document not found.');
                }
            }

            if (! Storage::disk('public')->exists($filePath)) {
                // For application_pdf, try to generate if file missing
                if ($documentKey === 'application_pdf' && $application->application_type === 'IX') {
                    app(IxApplicationController::class)->ensureApplicationPdfExists($application);
                    $application->refresh();
                    $applicationData = $application->application_data ?? [];
                    $pdfs = $applicationData['pdfs'] ?? [];
                    $filePath = $pdfs['application_pdf'] ?? null;
                }
                if (! $filePath || ! Storage::disk('public')->exists($filePath)) {
                    abort(404, 'File not found on server.');
                }
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

            // Replace all old GST data with new GST data in kyc_details
            $application->kyc_details = $newKycDetails;

            // Update application_data GSTIN - replace old with new
            $applicationData = is_array($application->application_data) ? $application->application_data : [];
            $applicationData['gstin'] = $gstRequest->new_gstin;
            $application->application_data = $applicationData;

            $application->save();

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

    // ============================================
    // NEW IX WORKFLOW METHODS
    // ============================================

    /**
     * IX Processor: Forward application to Legal.
     */
    public function ixProcessorForwardToLegal(Request $request, $id)
    {
        try {
            $admin = $this->getCurrentAdmin();

            if (! $this->hasRole($admin, 'ix_processor')) {
                return back()->with('error', 'You do not have permission to perform this action.');
            }

            $application = Application::with('user')->where('application_type', 'IX')->findOrFail($id);

            if (! $application->isVisibleToIxProcessor()) {
                return back()->with('error', 'This application is not available for processing.');
            }

            $oldStatus = $application->status;
            $application->update([
                'status' => 'processor_forwarded_legal',
                'current_ix_processor_id' => $admin->id,
            ]);

            ApplicationStatusHistory::log(
                $application->id,
                $oldStatus,
                'processor_forwarded_legal',
                'admin',
                $admin->id,
                'Application forwarded to IX Legal by Processor'
            );

            AdminAction::log(
                $admin->id,
                'forwarded_application',
                $application,
                "Forwarded application {$application->application_id} to IX Legal",
                ['user_id' => $application->user_id]
            );

            // Send email to applicant
            try {
                Mail::to($application->user->email)->send(
                    new \App\Mail\IxApplicationForwardedMail(
                        $application,
                        'IX Legal',
                        'IX Application Processor'
                    )
                );
            } catch (Exception $e) {
                Log::error('Error sending IX application forwarded email: '.$e->getMessage());
            }

            $this->createMessageForAdmin(
                $admin->id,
                $application->user_id,
                'Application Forwarded to Legal',
                "Your application {$application->application_id} has been forwarded to IX Legal for verification of agreement."
            );

            return back()->with('success', 'Application forwarded to IX Legal!');
        } catch (Exception $e) {
            Log::error('Error forwarding application to legal: '.$e->getMessage());

            return back()->with('error', 'An error occurred. Please try again.');
        }
    }

    /**
     * IX Processor: Request resubmission from user.
     */
    public function ixProcessorRequestResubmission(Request $request, $id)
    {
        try {
            $admin = $this->getCurrentAdmin();

            if (! $this->hasRole($admin, 'ix_processor')) {
                return back()->with('error', 'You do not have permission to perform this action.');
            }

            $validated = $request->validate([
                'resubmission_query' => 'required|string|min:10',
            ], [
                'resubmission_query.required' => 'Please provide a query or reason for resubmission.',
                'resubmission_query.min' => 'Please provide more details (minimum 10 characters).',
            ]);

            $application = Application::with('user')->where('application_type', 'IX')->findOrFail($id);

            if (! $application->isVisibleToIxProcessor()) {
                return back()->with('error', 'This application is not available for processing.');
            }

            $oldStatus = $application->status;
            $application->update([
                'status' => 'processor_resubmission',
                'resubmission_query' => $validated['resubmission_query'],
                'current_ix_processor_id' => $admin->id,
            ]);

            ApplicationStatusHistory::log(
                $application->id,
                $oldStatus,
                'processor_resubmission',
                'admin',
                $admin->id,
                $validated['resubmission_query']
            );

            AdminAction::log(
                $admin->id,
                'requested_resubmission',
                $application,
                "Requested resubmission for application {$application->application_id}",
                ['query' => $validated['resubmission_query']]
            );

            // Send resubmission email with query
            try {
                Mail::to($application->user->email)->send(
                    new \App\Mail\IxApplicationResubmissionMail(
                        $application,
                        $validated['resubmission_query']
                    )
                );
            } catch (Exception $e) {
                Log::error('Error sending IX application resubmission email: '.$e->getMessage());
            }

            $this->createMessageForAdmin(
                $admin->id,
                $application->user_id,
                'Application Resubmission Required',
                "Your application {$application->application_id} requires resubmission. Query: {$validated['resubmission_query']}"
            );

            return back()->with('success', 'Resubmission requested!');
        } catch (Exception $e) {
            Log::error('Error requesting resubmission: '.$e->getMessage());

            return back()->with('error', 'An error occurred. Please try again.');
        }
    }

    /**
     * IX Legal: Forward to IX Head.
     */
    public function ixLegalForwardToHead(Request $request, $id)
    {
        try {
            $admin = $this->getCurrentAdmin();

            if (! $this->hasRole($admin, 'ix_legal')) {
                return back()->with('error', 'You do not have permission to perform this action.');
            }

            $application = Application::with('user')->where('application_type', 'IX')->findOrFail($id);

            if (! $application->isVisibleToIxLegal()) {
                return back()->with('error', 'This application is not available for Legal review.');
            }

            $oldStatus = $application->status;
            $application->update([
                'status' => 'legal_forwarded_head',
                'current_ix_legal_id' => $admin->id,
            ]);

            ApplicationStatusHistory::log(
                $application->id,
                $oldStatus,
                'legal_forwarded_head',
                'admin',
                $admin->id,
                'Application forwarded to IX Head by Legal'
            );

            AdminAction::log(
                $admin->id,
                'forwarded_application',
                $application,
                "Forwarded application {$application->application_id} to IX Head",
                ['user_id' => $application->user_id]
            );

            // Send email to applicant
            try {
                Mail::to($application->user->email)->send(
                    new \App\Mail\IxApplicationForwardedMail(
                        $application,
                        'IX Head',
                        'IX Legal'
                    )
                );
            } catch (Exception $e) {
                Log::error('Error sending IX application forwarded email: '.$e->getMessage());
            }

            $this->createMessageForAdmin(
                $admin->id,
                $application->user_id,
                'Application Forwarded to IX Head',
                "Your application {$application->application_id} has been verified by Legal and forwarded to IX Head for review."
            );

            return back()->with('success', 'Application forwarded to IX Head!');
        } catch (Exception $e) {
            Log::error('Error forwarding application to head: '.$e->getMessage());

            return back()->with('error', 'An error occurred. Please try again.');
        }
    }

    /**
     * IX Legal: Send back to Processor.
     */
    public function ixLegalSendBackToProcessor(Request $request, $id)
    {
        try {
            $admin = $this->getCurrentAdmin();

            if (! $this->hasRole($admin, 'ix_legal')) {
                return back()->with('error', 'You do not have permission to perform this action.');
            }

            $validated = $request->validate([
                'rejection_reason' => 'required|string|min:10',
            ]);

            $application = Application::with('user')->where('application_type', 'IX')->findOrFail($id);

            if (! $application->isVisibleToIxLegal()) {
                return back()->with('error', 'This application is not available for Legal review.');
            }

            $oldStatus = $application->status;
            $application->update([
                'status' => 'legal_sent_back',
                'rejection_reason' => $validated['rejection_reason'],
                'current_ix_legal_id' => $admin->id,
            ]);

            ApplicationStatusHistory::log(
                $application->id,
                $oldStatus,
                'legal_sent_back',
                'admin',
                $admin->id,
                $validated['rejection_reason']
            );

            $this->createMessageForAdmin(
                $admin->id,
                $application->user_id,
                'Application Sent Back to Processor',
                "Your application {$application->application_id} has been sent back to Processor. Reason: {$validated['rejection_reason']}"
            );

            return back()->with('success', 'Application sent back to Processor!');
        } catch (Exception $e) {
            Log::error('Error sending application back: '.$e->getMessage());

            return back()->with('error', 'An error occurred. Please try again.');
        }
    }

    /**
     * IX Head: Forward to CEO.
     */
    public function ixHeadForwardToCeo(Request $request, $id)
    {
        try {
            $admin = $this->getCurrentAdmin();

            if (! $this->hasRole($admin, 'ix_head')) {
                return back()->with('error', 'You do not have permission to perform this action.');
            }

            $application = Application::with('user')->where('application_type', 'IX')->findOrFail($id);

            if (! $application->isVisibleToIxHead()) {
                return back()->with('error', 'This application is not available for IX Head review.');
            }

            $oldStatus = $application->status;
            $application->update([
                'status' => 'head_forwarded_ceo',
                'current_ix_head_id' => $admin->id,
            ]);

            ApplicationStatusHistory::log(
                $application->id,
                $oldStatus,
                'head_forwarded_ceo',
                'admin',
                $admin->id,
                'Application forwarded to CEO by IX Head'
            );

            // Send email to applicant
            try {
                Mail::to($application->user->email)->send(
                    new \App\Mail\IxApplicationForwardedMail(
                        $application,
                        'CEO',
                        'IX Head'
                    )
                );
            } catch (Exception $e) {
                Log::error('Error sending IX application forwarded email: '.$e->getMessage());
            }

            $this->createMessageForAdmin(
                $admin->id,
                $application->user_id,
                'Application Forwarded to CEO',
                "Your application {$application->application_id} has been reviewed by IX Head and forwarded to CEO for final approval."
            );

            return back()->with('success', 'Application forwarded to CEO!');
        } catch (Exception $e) {
            Log::error('Error forwarding application to CEO: '.$e->getMessage());

            return back()->with('error', 'An error occurred. Please try again.');
        }
    }

    /**
     * IX Head: Send back to Processor.
     */
    public function ixHeadSendBackToProcessor(Request $request, $id)
    {
        try {
            $admin = $this->getCurrentAdmin();

            if (! $this->hasRole($admin, 'ix_head')) {
                return back()->with('error', 'You do not have permission to perform this action.');
            }

            $validated = $request->validate([
                'rejection_reason' => 'required|string|min:10',
            ]);

            $application = Application::with('user')->where('application_type', 'IX')->findOrFail($id);

            if (! $application->isVisibleToIxHead()) {
                return back()->with('error', 'This application is not available for IX Head review.');
            }

            $oldStatus = $application->status;
            $application->update([
                'status' => 'head_sent_back',
                'rejection_reason' => $validated['rejection_reason'],
                'current_ix_head_id' => $admin->id,
            ]);

            ApplicationStatusHistory::log(
                $application->id,
                $oldStatus,
                'head_sent_back',
                'admin',
                $admin->id,
                $validated['rejection_reason']
            );

            $this->createMessageForAdmin(
                $admin->id,
                $application->user_id,
                'Application Sent Back to Processor',
                "Your application {$application->application_id} has been sent back to Processor. Reason: {$validated['rejection_reason']}"
            );

            return back()->with('success', 'Application sent back to Processor!');
        } catch (Exception $e) {
            Log::error('Error sending application back: '.$e->getMessage());

            return back()->with('error', 'An error occurred. Please try again.');
        }
    }

    /**
     * CEO: Approve and forward to Nodal Officer.
     */
    public function ceoApprove(Request $request, $id)
    {
        try {
            $admin = $this->getCurrentAdmin();

            if (! $this->hasRole($admin, 'ceo')) {
                return back()->with('error', 'You do not have permission to perform this action.');
            }

            $application = Application::with('user')->where('application_type', 'IX')->findOrFail($id);

            if (! $application->isVisibleToCeo()) {
                return back()->with('error', 'This application is not available for CEO review.');
            }

            $oldStatus = $application->status;
            $application->update([
                'status' => 'ceo_approved',
                'current_ceo_id' => $admin->id,
            ]);

            ApplicationStatusHistory::log(
                $application->id,
                $oldStatus,
                'ceo_approved',
                'admin',
                $admin->id,
                'Application approved by CEO and forwarded to Nodal Officer'
            );

            // Send approval email to applicant
            try {
                Mail::to($application->user->email)->send(
                    new \App\Mail\IxApplicationApprovedMail(
                        $application,
                        'CEO'
                    )
                );
            } catch (Exception $e) {
                Log::error('Error sending IX application approved email: '.$e->getMessage());
            }

            $this->createMessageForAdmin(
                $admin->id,
                $application->user_id,
                'Application Approved by CEO',
                "Congratulations! Your application {$application->application_id} has been approved by CEO and forwarded to Nodal Officer for port assignment."
            );

            return back()->with('success', 'Application approved and forwarded to Nodal Officer!');
        } catch (Exception $e) {
            Log::error('Error approving application: '.$e->getMessage());

            return back()->with('error', 'An error occurred. Please try again.');
        }
    }

    /**
     * CEO: Reject application.
     */
    public function ceoReject(Request $request, $id)
    {
        try {
            $admin = $this->getCurrentAdmin();

            if (! $this->hasRole($admin, 'ceo')) {
                return back()->with('error', 'You do not have permission to perform this action.');
            }

            $validated = $request->validate([
                'rejection_reason' => 'required|string|min:10',
            ]);

            $application = Application::with('user')->where('application_type', 'IX')->findOrFail($id);

            if (! $application->isVisibleToCeo()) {
                return back()->with('error', 'This application is not available for CEO review.');
            }

            $oldStatus = $application->status;
            $application->update([
                'status' => 'ceo_rejected',
                'rejection_reason' => $validated['rejection_reason'],
                'current_ceo_id' => $admin->id,
            ]);

            ApplicationStatusHistory::log(
                $application->id,
                $oldStatus,
                'ceo_rejected',
                'admin',
                $admin->id,
                $validated['rejection_reason']
            );

            // Send rejection email to applicant
            try {
                Mail::to($application->user->email)->send(
                    new \App\Mail\IxApplicationRejectedMail(
                        $application,
                        'CEO',
                        $validated['rejection_reason']
                    )
                );
            } catch (Exception $e) {
                Log::error('Error sending IX application rejected email: '.$e->getMessage());
            }

            $this->createMessageForAdmin(
                $admin->id,
                $application->user_id,
                'Application Rejected',
                "Your application {$application->application_id} has been rejected by CEO. Reason: {$validated['rejection_reason']}"
            );

            return back()->with('success', 'Application rejected!');
        } catch (Exception $e) {
            Log::error('Error rejecting application: '.$e->getMessage());

            return back()->with('error', 'An error occurred. Please try again.');
        }
    }

    /**
     * CEO: Send back to IX Head.
     */
    public function ceoSendBackToHead(Request $request, $id)
    {
        try {
            $admin = $this->getCurrentAdmin();

            if (! $this->hasRole($admin, 'ceo')) {
                return back()->with('error', 'You do not have permission to perform this action.');
            }

            $validated = $request->validate([
                'send_back_reason' => 'nullable|string|max:1000',
            ]);

            $application = Application::with('user')->where('application_type', 'IX')->findOrFail($id);

            if (! $application->isVisibleToCeo()) {
                return back()->with('error', 'This application is not available for CEO review.');
            }

            // Get the IX Head who forwarded it (from status history or current_ix_head_id)
            $ixHeadId = $application->current_ix_head_id;
            if (! $ixHeadId) {
                // Try to get from status history
                $headForwardHistory = ApplicationStatusHistory::where('application_id', $application->id)
                    ->where('status_to', 'head_forwarded_ceo')
                    ->where('changed_by_type', 'admin')
                    ->latest()
                    ->first();
                $ixHeadId = $headForwardHistory ? $headForwardHistory->changed_by_id : null;
            }

            $oldStatus = $application->status;
            $application->update([
                'status' => 'ceo_sent_back_head',
                'current_ceo_id' => $admin->id,
                'current_ix_head_id' => $ixHeadId,
            ]);

            $notes = $validated['send_back_reason'] ?? 'Application sent back to IX Head by CEO for review';

            ApplicationStatusHistory::log(
                $application->id,
                $oldStatus,
                'ceo_sent_back_head',
                'admin',
                $admin->id,
                $notes
            );

            $this->createMessageForAdmin(
                $admin->id,
                $application->user_id,
                'Application Sent Back to IX Head',
                "Your application {$application->application_id} has been sent back to IX Head by CEO for further review."
            );

            return back()->with('success', 'Application sent back to IX Head!');
        } catch (Exception $e) {
            Log::error('Error sending application back to IX Head: '.$e->getMessage());

            return back()->with('error', 'An error occurred. Please try again.');
        }
    }

    /**
     * Nodal Officer: Assign Port and forward to Tech Team.
     */
    public function nodalOfficerAssignPort(Request $request, $id)
    {
        try {
            $admin = $this->getCurrentAdmin();

            if (! $this->hasRole($admin, 'nodal_officer')) {
                return back()->with('error', 'You do not have permission to perform this action.');
            }

            $validated = $request->validate([
                'assigned_port_capacity' => 'required|string',
                'assigned_port_number' => 'nullable|string',
            ]);

            $application = Application::with('user')->where('application_type', 'IX')->findOrFail($id);

            if (! $application->isVisibleToNodalOfficer()) {
                return back()->with('error', 'This application is not available for Nodal Officer review.');
            }

            $oldStatus = $application->status;
            $application->update([
                'status' => 'port_assigned',
                'assigned_port_capacity' => $validated['assigned_port_capacity'],
                'assigned_port_number' => $validated['assigned_port_number'] ?? null,
                'current_nodal_officer_id' => $admin->id,
            ]);

            ApplicationStatusHistory::log(
                $application->id,
                $oldStatus,
                'port_assigned',
                'admin',
                $admin->id,
                "Port assigned: {$validated['assigned_port_capacity']}"
            );

            $this->createMessageForAdmin(
                $admin->id,
                $application->user_id,
                'IP Assigned - Service Activated',
                "IP has been assigned for your application {$application->application_id}. Your service is now LIVE. Service Activation Date: {$validated['service_activation_date']}. IP Address: {$validated['assigned_ip']}, Customer ID: {$validated['customer_id']}, Membership ID: {$validated['membership_id']}"
            );

            return back()->with('success', 'Port assigned and forwarded to Tech Team!');
        } catch (Exception $e) {
            Log::error('Error assigning port: '.$e->getMessage());

            return back()->with('error', 'An error occurred. Please try again.');
        }
    }

    /**
     * Nodal Officer: Hold application.
     */
    public function nodalOfficerHold(Request $request, $id)
    {
        try {
            $admin = $this->getCurrentAdmin();

            if (! $this->hasRole($admin, 'nodal_officer')) {
                return back()->with('error', 'You do not have permission to perform this action.');
            }

            $validated = $request->validate([
                'rejection_reason' => 'required|string|min:10',
            ]);

            $application = Application::with('user')->where('application_type', 'IX')->findOrFail($id);

            if (! $application->isVisibleToNodalOfficer()) {
                return back()->with('error', 'This application is not available for Nodal Officer review.');
            }

            $oldStatus = $application->status;
            $application->update([
                'status' => 'port_hold',
                'rejection_reason' => $validated['rejection_reason'],
                'current_nodal_officer_id' => $admin->id,
            ]);

            ApplicationStatusHistory::log(
                $application->id,
                $oldStatus,
                'port_hold',
                'admin',
                $admin->id,
                $validated['rejection_reason']
            );

            $this->createMessageForAdmin(
                $admin->id,
                $application->user_id,
                'Application On Hold',
                "Your application {$application->application_id} has been put on hold. Reason: {$validated['rejection_reason']}"
            );

            return back()->with('success', 'Application put on hold!');
        } catch (Exception $e) {
            Log::error('Error holding application: '.$e->getMessage());

            return back()->with('error', 'An error occurred. Please try again.');
        }
    }

    /**
     * Nodal Officer: Mark as Not Feasible.
     */
    public function nodalOfficerNotFeasible(Request $request, $id)
    {
        try {
            $admin = $this->getCurrentAdmin();

            if (! $this->hasRole($admin, 'nodal_officer')) {
                return back()->with('error', 'You do not have permission to perform this action.');
            }

            $validated = $request->validate([
                'rejection_reason' => 'required|string|min:10',
            ]);

            $application = Application::with('user')->where('application_type', 'IX')->findOrFail($id);

            if (! $application->isVisibleToNodalOfficer()) {
                return back()->with('error', 'This application is not available for Nodal Officer review.');
            }

            $oldStatus = $application->status;
            $application->update([
                'status' => 'port_not_feasible',
                'rejection_reason' => $validated['rejection_reason'],
                'current_nodal_officer_id' => $admin->id,
            ]);

            ApplicationStatusHistory::log(
                $application->id,
                $oldStatus,
                'port_not_feasible',
                'admin',
                $admin->id,
                $validated['rejection_reason']
            );

            $this->createMessageForAdmin(
                $admin->id,
                $application->user_id,
                'Application Not Feasible',
                "Your application {$application->application_id} has been marked as not feasible. Reason: {$validated['rejection_reason']}"
            );

            return back()->with('success', 'Application marked as not feasible!');
        } catch (Exception $e) {
            Log::error('Error marking application as not feasible: '.$e->getMessage());

            return back()->with('error', 'An error occurred. Please try again.');
        }
    }

    /**
     * Nodal Officer: Customer Denied.
     */
    public function nodalOfficerCustomerDenied(Request $request, $id)
    {
        try {
            $admin = $this->getCurrentAdmin();

            if (! $this->hasRole($admin, 'nodal_officer')) {
                return back()->with('error', 'You do not have permission to perform this action.');
            }

            $application = Application::with('user')->where('application_type', 'IX')->findOrFail($id);

            if (! $application->isVisibleToNodalOfficer()) {
                return back()->with('error', 'This application is not available for Nodal Officer review.');
            }

            $oldStatus = $application->status;
            $application->update([
                'status' => 'customer_denied',
                'current_nodal_officer_id' => $admin->id,
            ]);

            ApplicationStatusHistory::log(
                $application->id,
                $oldStatus,
                'customer_denied',
                'admin',
                $admin->id,
                'Customer denied the port assignment'
            );

            $this->createMessageForAdmin(
                $admin->id,
                $application->user_id,
                'Application Status Update',
                "Your application {$application->application_id} has been marked as customer denied."
            );

            return back()->with('success', 'Application marked as customer denied!');
        } catch (Exception $e) {
            Log::error('Error marking application as customer denied: '.$e->getMessage());

            return back()->with('error', 'An error occurred. Please try again.');
        }
    }

    /**
     * Nodal Officer: Forward back to Processor.
     */
    public function nodalOfficerForwardToProcessor(Request $request, $id)
    {
        try {
            $admin = $this->getCurrentAdmin();

            if (! $this->hasRole($admin, 'nodal_officer')) {
                return back()->with('error', 'You do not have permission to perform this action.');
            }

            $validated = $request->validate([
                'rejection_reason' => 'required|string|min:10',
            ]);

            $application = Application::with('user')->where('application_type', 'IX')->findOrFail($id);

            if (! $application->isVisibleToNodalOfficer()) {
                return back()->with('error', 'This application is not available for Nodal Officer review.');
            }

            $oldStatus = $application->status;
            $application->update([
                'status' => 'processor_resubmission',
                'rejection_reason' => $validated['rejection_reason'],
                'current_nodal_officer_id' => $admin->id,
            ]);

            ApplicationStatusHistory::log(
                $application->id,
                $oldStatus,
                'processor_resubmission',
                'admin',
                $admin->id,
                $validated['rejection_reason']
            );

            $this->createMessageForAdmin(
                $admin->id,
                $application->user_id,
                'Application Sent Back to Processor',
                "Your application {$application->application_id} has been sent back to Processor. Reason: {$validated['rejection_reason']}"
            );

            return back()->with('success', 'Application sent back to Processor!');
        } catch (Exception $e) {
            Log::error('Error forwarding application to processor: '.$e->getMessage());

            return back()->with('error', 'An error occurred. Please try again.');
        }
    }

    /**
     * IX Tech Team: Assign IP and make live.
     */
    public function ixTechTeamAssignIp(Request $request, $id)
    {
        try {
            $admin = $this->getCurrentAdmin();

            if (! $this->hasRole($admin, 'ix_tech_team')) {
                return back()->with('error', 'You do not have permission to perform this action.');
            }

            $validated = $request->validate([
                'assigned_ip' => 'required|string',
                'customer_id' => 'required|string',
                'membership_id' => 'required|string',
                'service_activation_date' => 'required|date',
            ]);

            $application = Application::with('user')->where('application_type', 'IX')->findOrFail($id);

            if (! $application->isVisibleToIxTechTeam()) {
                return back()->with('error', 'This application is not available for Tech Team review.');
            }

            // Get billing cycle from application data
            $applicationData = $application->application_data ?? [];
            $billingCycle = $applicationData['billing']['plan'] ?? 'monthly'; // monthly, quarterly, annual

            $oldStatus = $application->status;
            $activationDate = \Carbon\Carbon::parse($validated['service_activation_date']);

            // Determine billing anchor date (1st of month for future billing)
            // If activation is on the 1st, anchor is the same date; otherwise, it's the 1st of the next month
            $billingAnchorDate = $activationDate->day === 1
                ? $activationDate->copy()->startOfDay()
                : $activationDate->copy()->addMonth()->startOfMonth();

            $application->update([
                'status' => 'ip_assigned',
                'assigned_ip' => $validated['assigned_ip'],
                'customer_id' => $validated['customer_id'],
                'membership_id' => $validated['membership_id'],
                'service_activation_date' => $validated['service_activation_date'],
                'billing_cycle' => $billingCycle,
                'service_status' => 'live',
                'billing_resume_date' => null,
                'suspended_from' => null,
                'disconnected_at' => null,
                'billing_anchor_date' => $billingAnchorDate, // Set the anchor date
                'is_active' => true, // Make it live
                'current_ix_tech_team_id' => $admin->id,
                'current_ix_account_id' => null, // Reset so IX Account can see it
            ]);

            ApplicationStatusHistory::log(
                $application->id,
                $oldStatus,
                'ip_assigned',
                'admin',
                $admin->id,
                "IP assigned: {$validated['assigned_ip']}, Customer ID: {$validated['customer_id']}, Membership ID: {$validated['membership_id']}, Service Activation: {$validated['service_activation_date']}"
            );

            // Generate membership and IX invoice (TODO: Implement invoice generation)
            // Send email with details
            try {
                Mail::to($application->user->email)->send(
                    new \App\Mail\IxApplicationIpAssignedMail(
                        $application,
                        $validated['assigned_ip'],
                        $validated['customer_id'],
                        $validated['membership_id'],
                        $validated['service_activation_date']
                    )
                );
            } catch (Exception $e) {
                Log::error('Error sending IX application IP assigned email: '.$e->getMessage());
            }

            $this->createMessageForAdmin(
                $admin->id,
                $application->user_id,
                'IP Assigned - Application Live',
                "Your application {$application->application_id} is now live! IP: {$validated['assigned_ip']}, Customer ID: {$validated['customer_id']}, Membership ID: {$validated['membership_id']}"
            );

            return back()->with('success', 'IP assigned and application is now live!');
        } catch (Exception $e) {
            Log::error('Error assigning IP: '.$e->getMessage());

            return back()->with('error', 'An error occurred. Please try again.');
        }
    }

    /**
     * IX Account: Show invoice generation form with prefilled details.
     */
    public function ixAccountShowInvoiceForm(Request $request, $id)
    {
        try {
            $admin = $this->getCurrentAdmin();

            if (! $this->hasRole($admin, 'ix_account')) {
                return back()->with('error', 'You do not have permission to perform this action.');
            }

            $application = Application::with('user')->where('application_type', 'IX')->findOrFail($id);

            if (($application->service_status ?? 'live') !== 'live') {
                return back()->with('error', 'Invoice can only be generated for LIVE applications.');
            }

            if (! $request->boolean('pending_invoice') && ! $application->isVisibleToIxAccount()) {
                return back()->with('error', 'This application is not available for Account review.');
            }

            // Check if invoice can be generated for current period
            if ($application->service_activation_date && $application->billing_cycle) {
                $currentBillingPeriod = $this->getCurrentBillingPeriod($application);
                if ($currentBillingPeriod) {
                    // Check if invoice already exists for this period (allow regenerate if credit note exists)
                    $existingInvoice = \App\Models\Invoice::where('application_id', $application->id)
                        ->where('billing_period', $currentBillingPeriod)
                        ->where('status', '!=', 'cancelled')
                        ->whereNull('credit_note_pdf_path')
                        ->first();

                    if ($existingInvoice) {
                        return back()->with('error', "Invoice for billing period '{$currentBillingPeriod}' already exists (Invoice: {$existingInvoice->invoice_number}).");
                    }

                    // Check if invoice with credit note exists for current period - if so, allow regeneration without advance window check
                    $invoiceWithCreditNote = \App\Models\Invoice::where('application_id', $application->id)
                        ->where('billing_period', $currentBillingPeriod)
                        ->where('status', '!=', 'cancelled')
                        ->whereNotNull('credit_note_pdf_path')
                        ->exists();

                    // Also check if ANY credit note exists for this application (regardless of period)
                    $anyCreditNoteInvoice = \App\Models\Invoice::where('application_id', $application->id)
                        ->where('status', '!=', 'cancelled')
                        ->whereNotNull('credit_note_pdf_path')
                        ->exists();

                    // If credit note exists (for current period or any period), skip advance window check and allow generation
                    if (! $invoiceWithCreditNote && ! $anyCreditNoteInvoice) {
                        // Only check advance window if no credit note exists
                        if (! $this->canGenerateInvoiceForPeriod($application, $currentBillingPeriod)) {
                            return redirect()->route('admin.applications.show', $application->id)
                                ->with('error', 'Invoice can only be generated one month before the billing period starts.');
                        }
                    }
                }
            }

            // Calculate invoice details (same logic as generate, but don't create invoice)
            $invoiceData = $this->calculateInvoiceDetails($application);

            if (isset($invoiceData['error'])) {
                return back()->with('error', $invoiceData['error']);
            }

            return view('admin.invoices.create', compact('application', 'admin', 'invoiceData'));
        } catch (Exception $e) {
            Log::error('Error loading invoice form: '.$e->getMessage());

            return back()->with('error', 'Unable to load invoice form.');
        }
    }

    /**
     * IX Account: Generate one-time pending invoice for suspended/disconnected applications.
     */
    public function ixAccountShowPendingInvoiceForm($id)
    {
        try {
            $admin = $this->getCurrentAdmin();

            if (! $this->hasRole($admin, 'ix_account')) {
                return back()->with('error', 'You do not have permission to perform this action.');
            }

            $application = Application::with('user')->where('application_type', 'IX')->findOrFail($id);
            $serviceStatus = $application->service_status ?? 'live';

            // We allow generating a pending/final invoice even after reactivation (service_status=live),
            // by using the most recent DISCONNECTED event from the service status history.
            $stopDate = null;
            $isPendingFor = null; // 'suspended'|'disconnected'

            if ($serviceStatus === 'suspended') {
                if (! $application->suspended_from) {
                    return back()->with('error', 'Suspension date is missing.');
                }
                $stopDate = \Carbon\Carbon::parse($application->suspended_from)->subDay();
                $isPendingFor = 'suspended';
            } elseif ($serviceStatus === 'disconnected') {
                if (! $application->disconnected_at) {
                    return back()->with('error', 'Disconnection date is missing.');
                }
                $stopDate = \Carbon\Carbon::parse($application->disconnected_at);
                $isPendingFor = 'disconnected';
            } else {
                // Currently LIVE: try to find the most recent DISCONNECTED history to generate the missing final invoice.
                $lastDisconnected = \App\Models\ApplicationServiceStatusHistory::query()
                    ->where('application_id', $application->id)
                    ->where('status', 'disconnected')
                    ->whereNotNull('effective_from')
                    ->latest('effective_from')
                    ->first();

                if (! $lastDisconnected) {
                    return back()->with('error', 'No disconnected/suspended period found to generate a pending invoice.');
                }

                $stopDate = \Carbon\Carbon::parse($lastDisconnected->effective_from);
                $isPendingFor = 'disconnected';
            }

            // If billing already covered up to stop date, do not generate again
            $hasCoveredStopDate = Invoice::where('application_id', $application->id)
                ->where('status', '!=', 'cancelled')
                ->whereNotNull('billing_start_date')
                ->whereNotNull('billing_end_date')
                ->whereDate('billing_start_date', '<=', $stopDate->format('Y-m-d'))
                ->whereDate('billing_end_date', '>=', $stopDate->format('Y-m-d'))
                ->exists();

            if ($hasCoveredStopDate) {
                return back()->with('info', 'No pending invoice required. Billing is already up to date for the selected stop date.');
            }

            $invoiceData = $this->calculateInvoiceDetails($application, [
                'override_billing_end_date' => $stopDate->format('Y-m-d'),
                'billing_period' => 'FINAL-'.$stopDate->format('Ymd').'-'.$application->id,
            ]);

            if (isset($invoiceData['error'])) {
                return back()->with('error', $invoiceData['error']);
            }

            $invoiceData['pending_invoice'] = true;
            $invoiceData['pending_for'] = $isPendingFor;

            return view('admin.invoices.create', compact('application', 'admin', 'invoiceData'));
        } catch (Exception $e) {
            Log::error('Error loading pending invoice form: '.$e->getMessage());

            return back()->with('error', 'Unable to load pending invoice form.');
        }
    }

    /**
     * Calculate invoice details (extracted for reuse in form and generation).
     */
    private function calculateInvoiceDetails(Application $application, array $options = []): array
    {
        try {
            $applicationData = $application->application_data ?? [];
            $billingPlanRaw = $application->billing_cycle ?? ($applicationData['port_selection']['billing_plan'] ?? 'monthly');
            $billingPlan = strtolower(trim($billingPlanRaw));
            if (in_array($billingPlan, ['arc', 'annual'])) {
                $billingPlan = 'annual';
            } elseif (in_array($billingPlan, ['mrc', 'monthly'])) {
                $billingPlan = 'monthly';
            } elseif ($billingPlan === 'quarterly') {
                $billingPlan = 'quarterly';
            } else {
                $billingPlan = 'monthly';
            }

            $overrideEndDate = ! empty($options['override_billing_end_date'])
                ? \Carbon\Carbon::parse($options['override_billing_end_date'])->startOfDay()
                : null;

            $overrideStartDate = ! empty($options['override_billing_start_date'])
                ? \Carbon\Carbon::parse($options['override_billing_start_date'])->startOfDay()
                : null;

            // Get the invoice with the latest billing period end (any status except cancelled) to determine next billing cycle.
            // Use billing_end_date, not invoice_date, so that if you have 2025-12 and 2026-01 invoices (created in any order),
            // the next period is after 2026-01 (1 Feb–28 Feb), not after 2025-12 (which would wrongly suggest 2026-01 again).
            // When override_billing_end_date is set (pending/final invoice), use the latest invoice ending on/before that date.
            $latestInvoiceQuery = Invoice::where('application_id', $application->id)
                ->where('status', '!=', 'cancelled')
                ->whereNotNull('billing_end_date');

            if ($overrideEndDate) {
                $latestInvoiceQuery->whereDate('billing_end_date', '<=', $overrideEndDate->format('Y-m-d'));
            }

            $latestInvoice = $latestInvoiceQuery->orderByDesc('billing_end_date')->first();

            // Also get last paid invoice for carry-forward calculations (bounded for pending/final invoices)
            $lastPaidInvoiceQuery = Invoice::where('application_id', $application->id)
                ->where('status', 'paid');

            if ($overrideEndDate) {
                $lastPaidInvoiceQuery->whereDate('invoice_date', '<=', $overrideEndDate->format('Y-m-d'));
            }

            $lastPaidInvoice = $lastPaidInvoiceQuery->latest('invoice_date')->first();

            $billingStartDate = null;
            if ($latestInvoice && $latestInvoice->billing_end_date) {
                // Use the billing_end_date of the latest invoice as the start for the next cycle
                // Parse as date-only to avoid timezone issues
                $endDateStr = is_string($latestInvoice->billing_end_date) ? $latestInvoice->billing_end_date : $latestInvoice->billing_end_date->format('Y-m-d');
                if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $endDateStr, $match)) {
                    $billingStartDate = \Carbon\Carbon::createFromFormat('Y-m-d', $match[1])->addDay();
                } else {
                    $billingStartDate = \Carbon\Carbon::parse($endDateStr)->addDay();
                }
            } elseif ($latestInvoice && $latestInvoice->due_date) {
                // Fallback to due_date if billing_end_date is not available
                $dueDateStr = is_string($latestInvoice->due_date) ? $latestInvoice->due_date : $latestInvoice->due_date->format('Y-m-d');
                if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $dueDateStr, $match)) {
                    $billingStartDate = \Carbon\Carbon::createFromFormat('Y-m-d', $match[1])->addDay();
                } else {
                    $billingStartDate = \Carbon\Carbon::parse($dueDateStr)->addDay();
                }
            } elseif ($application->service_activation_date) {
                $activationDateStr = is_string($application->service_activation_date) ? $application->service_activation_date : $application->service_activation_date->format('Y-m-d');
                if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $activationDateStr, $match)) {
                    $billingStartDate = \Carbon\Carbon::createFromFormat('Y-m-d', $match[1]);
                } else {
                    $billingStartDate = \Carbon\Carbon::parse($activationDateStr);
                }
            } else {
                $billingStartDate = now('Asia/Kolkata');
            }

            // Allow overriding billing start date (used for controlled backfills / special cases).
            if ($overrideStartDate) {
                $billingStartDate = $overrideStartDate->copy();
            }

            // If billing was resumed after a DISCONNECTION, restart billing from billing_resume_date (if it is later).
            // NOTE: We must NOT shift billing start for SUSPENDED → LIVE. Suspended days are excluded from proration instead.
            if (! empty($application->billing_resume_date)) {
                $resumeDate = \Carbon\Carbon::parse($application->billing_resume_date)->startOfDay();

                $hasDisconnectedBeforeResume = \App\Models\ApplicationServiceStatusHistory::query()
                    ->where('application_id', $application->id)
                    ->where('status', 'disconnected')
                    ->whereNotNull('effective_from')
                    ->whereDate('effective_from', '<', $resumeDate->format('Y-m-d'))
                    ->exists();

                if ($hasDisconnectedBeforeResume && $resumeDate->gt($billingStartDate)) {
                    $billingStartDate = $resumeDate;
                }
            }

            // Use billing_anchor_date if available (for aligning all billing cycles to 1st of month)
            // This ensures that after the first invoice (which may be prorated), all future cycles start from the 1st
            if ($application->billing_anchor_date && ! $overrideStartDate && ! $overrideEndDate) {
                $anchorDate = \Carbon\Carbon::parse($application->billing_anchor_date)->startOfDay();
                // Only use anchor if we don't have a previous invoice (first invoice scenario)
                // OR if billing_start_date is before the anchor (should align to anchor)
                if (! $latestInvoice || $billingStartDate->lt($anchorDate)) {
                    $billingStartDate = $anchorDate->copy();
                }
            }

            // Check if there's an approved billing cycle change that should take effect for this next invoice
            // (i.e., effective_from is before or equal to billingStartDate)
            $billingCycleChange = PlanChangeRequest::where('application_id', $application->id)
                ->where('status', 'approved')
                ->whereNotNull('effective_from')
                ->whereNotNull('new_billing_plan')
                ->where('effective_from', '<=', $billingStartDate)
                ->orderBy('effective_from', 'desc')
                ->first();

            // Use new billing cycle if change is effective, otherwise use current
            if ($billingCycleChange && $billingCycleChange->new_billing_plan) {
                $billingPlan = strtolower(trim($billingCycleChange->new_billing_plan));
                Log::info("Using new billing cycle '{$billingPlan}' for next invoice (effective from {$billingCycleChange->effective_from})");
            }

            switch ($billingPlan) {
                case 'annual':
                case 'arc':
                    // Annual: end date is one day before the same date next year
                    // Example: 21-01-2024 to 20-01-2025
                    $billingEndDate = $billingStartDate->copy()->addYear()->subDay();
                    $billingPeriod = $billingStartDate->format('Y');
                    break;
                case 'quarterly':
                    // Quarterly: end date is one day before the same date 3 months later
                    // Example: 21-01-2024 to 20-04-2024
                    $billingEndDate = $billingStartDate->copy()->addMonths(3)->subDay();
                    $quarter = ceil($billingStartDate->month / 3);
                    $billingPeriod = $billingStartDate->format('Y').'-Q'.$quarter;
                    break;
                case 'monthly':
                case 'mrc':
                default:
                    // Monthly: end date is one day before the same day next month
                    // Example: 21-12-2025 should end on 20-01-2026, not 21-01-2026
                    $billingEndDate = $billingStartDate->copy()->addMonth()->subDay();
                    $billingPeriod = $billingEndDate->format('Y-m');
                    break;
            }

            // Allow overriding billing period label without forcing an end-date override.
            if (! empty($options['override_billing_period'])) {
                $billingPeriod = (string) $options['override_billing_period'];
            }

            // Allow overriding end date for one-time pending/final invoices
            if (! empty($options['override_billing_end_date'])) {
                $billingEndDate = \Carbon\Carbon::parse($options['override_billing_end_date']);
                $billingPeriod = $options['billing_period']
                    ?? ('FINAL-'.$billingStartDate->format('Ymd').'-'.$billingEndDate->format('Ymd'));
            }

            // Safety fallback: if start is after (overridden) end, recalculate start based on the last invoice ending before end.
            if ($overrideEndDate && $billingEndDate->lt($billingStartDate)) {
                $fallbackInvoice = Invoice::where('application_id', $application->id)
                    ->where('status', '!=', 'cancelled')
                    ->whereNotNull('billing_end_date')
                    ->whereDate('billing_end_date', '<=', $billingEndDate->format('Y-m-d'))
                    ->latest('invoice_date')
                    ->first();

                if ($fallbackInvoice && $fallbackInvoice->billing_end_date) {
                    $endDateStr = is_string($fallbackInvoice->billing_end_date) ? $fallbackInvoice->billing_end_date : $fallbackInvoice->billing_end_date->format('Y-m-d');
                    if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $endDateStr, $match)) {
                        $billingStartDate = \Carbon\Carbon::createFromFormat('Y-m-d', $match[1])->addDay();
                    } else {
                        $billingStartDate = \Carbon\Carbon::parse($endDateStr)->addDay();
                    }
                }
            }

            if ($billingEndDate->lt($billingStartDate)) {
                return ['error' => 'Billing end date cannot be before billing start date.'];
            }

            // Set due date based on invoice generation date and billing cycle:
            // - Monthly (MRC): 15 days after invoice date
            // - Quarterly: 21 days after invoice date
            // - Annual (ARC): 30 days after invoice date
            $invoiceDate = now('Asia/Kolkata')->startOfDay();
            $normalizedPlan = strtolower(trim($billingPlan ?? 'monthly'));

            if (in_array($normalizedPlan, ['annual', 'arc'], true)) {
                $dueDate = $invoiceDate->copy()->addDays(30);
            } elseif ($normalizedPlan === 'quarterly') {
                $dueDate = $invoiceDate->copy()->addDays(21);
            } else {
                // Default / monthly (MRC)
                $dueDate = $invoiceDate->copy()->addDays(15);
            }

            // Check if active invoice exists (without credit note) - prevent duplicate
            $existingInvoice = Invoice::where('application_id', $application->id)
                ->where('billing_period', $billingPeriod)
                ->where('status', '!=', 'cancelled')
                ->whereNull('credit_note_pdf_path')
                ->first();

            if ($existingInvoice) {
                return ['error' => "An invoice for billing period '{$billingPeriod}' already exists (Invoice: {$existingInvoice->invoice_number})."];
            }

            // If invoice with credit note exists, allow regeneration (no error - continue)

            $location = null;
            if (isset($applicationData['location']['id'])) {
                $location = IxLocation::find($applicationData['location']['id']);
            }
            if (! $location) {
                return ['error' => 'Unable to calculate port charges. Location is missing.'];
            }

            $getPortAmount = function ($capacity, $plan) use ($location) {
                if (! $capacity) {
                    return null;
                }
                $normalizedCapacity = trim($capacity);
                $normalizedCapacity = preg_replace('/\s+/', '', $normalizedCapacity);
                if (stripos($normalizedCapacity, 'Gbps') !== false) {
                    $normalizedCapacity = str_ireplace(['Gbps', 'gbps', 'GBPS'], 'Gig', $normalizedCapacity);
                }
                if (! preg_match('/(Gig|M)$/i', $normalizedCapacity)) {
                    if (preg_match('/^\d+$/', $normalizedCapacity)) {
                        $normalizedCapacity .= 'Gig';
                    }
                }
                $pricing = IxPortPricing::active()
                    ->where('node_type', $location->node_type)
                    ->where('port_capacity', $normalizedCapacity)
                    ->first();
                if (! $pricing) {
                    $variations = [trim($capacity), str_replace(' ', '', trim($capacity)), preg_replace('/\s+/', '', trim($capacity)), str_replace(['Gbps', 'gbps', 'GBPS'], 'Gig', str_replace(' ', '', trim($capacity)))];
                    foreach (array_unique($variations) as $variation) {
                        if (empty($variation)) {
                            continue;
                        }
                        $pricing = IxPortPricing::active()
                            ->where('node_type', $location->node_type)
                            ->where('port_capacity', $variation)
                            ->first();
                        if ($pricing) {
                            break;
                        }
                    }
                }
                if (! $pricing) {
                    return null;
                }

                return $pricing->getAmountForPlan($plan);
            };

            $approvedPlanChanges = PlanChangeRequest::where('application_id', $application->id)
                ->where('status', 'approved')
                ->whereNotNull('effective_from')
                ->orderBy('effective_from', 'asc')
                ->get();

            // Determine the capacity at the START of the billing period
            $basePlan = $billingPlan;

            // Find the first change that occurs DURING this billing period
            $firstChangeInPeriod = $approvedPlanChanges
                ->filter(function ($change) use ($billingStartDate, $billingEndDate) {
                    return $change->effective_from && $change->effective_from->gte($billingStartDate) && $change->effective_from->lt($billingEndDate);
                })
                ->sortBy('effective_from')
                ->first();

            if ($firstChangeInPeriod && $firstChangeInPeriod->isCapacityChange()) {
                // If there's a capacity change during this period, the capacity at START is the CURRENT capacity from that change
                $baseCapacity = $firstChangeInPeriod->current_port_capacity;
                Log::info("Capacity change during billing period: Starting with {$baseCapacity} (will change to {$firstChangeInPeriod->new_port_capacity} on {$firstChangeInPeriod->effective_from->format('Y-m-d')})");
            } else {
                // No capacity change during this period, find the most recent change before this period
                $lastChangeBeforePeriod = $approvedPlanChanges
                    ->filter(function ($change) use ($billingStartDate) {
                        return $change->effective_from && $change->effective_from->lt($billingStartDate);
                    })
                    ->sortByDesc('effective_from')
                    ->first();

                if ($lastChangeBeforePeriod && $lastChangeBeforePeriod->new_port_capacity) {
                    // Use the capacity from the last change before this period
                    $baseCapacity = $lastChangeBeforePeriod->new_port_capacity;
                    if ($lastChangeBeforePeriod->new_billing_plan) {
                        $basePlan = strtolower($lastChangeBeforePeriod->new_billing_plan);
                    }
                } else {
                    // No changes, use initial capacity from application
                    $baseCapacity = $applicationData['port_selection']['capacity'] ?? $application->assigned_port_capacity ?? null;
                }
            }

            if (! $baseCapacity) {
                return ['error' => 'Port capacity is not set for this application. Please assign port capacity first.'];
            }

            $basePlanNormalized = strtolower(trim($basePlan));
            if (in_array($basePlanNormalized, ['arc', 'annual'])) {
                $basePlanNormalized = 'arc';
            } elseif (in_array($basePlanNormalized, ['mrc', 'monthly'])) {
                $basePlanNormalized = 'mrc';
            } elseif ($basePlanNormalized === 'quarterly') {
                $basePlanNormalized = 'quarterly';
            } else {
                $basePlanNormalized = 'mrc';
            }

            // Get changes that occur DURING this billing period (between start and end, excluding end date)
            // Changes on the end date should apply to the NEXT billing period, not this one
            $futureChanges = $approvedPlanChanges->filter(function ($c) use ($billingStartDate, $billingEndDate) {
                return $c->effective_from && $c->effective_from->gte($billingStartDate) && $c->effective_from->lt($billingEndDate);
            })->sortBy('effective_from')->values();

            // Log all changes for debugging
            Log::info('=== INVOICE CALCULATION DEBUG ===');
            Log::info("Billing period: {$billingStartDate->format('Y-m-d')} to {$billingEndDate->format('Y-m-d')}");
            Log::info("Base capacity determined: {$baseCapacity}");
            Log::info('Application assigned_port_capacity: '.($application->assigned_port_capacity ?? 'null'));
            Log::info('Application application_data capacity: '.($applicationData['port_selection']['capacity'] ?? 'null'));
            Log::info('Total approved plan changes: '.$approvedPlanChanges->count());
            Log::info('Changes in this billing period: '.$futureChanges->count());

            foreach ($approvedPlanChanges as $change) {
                $effDate = $change->effective_from ? $change->effective_from->format('Y-m-d') : 'null';
                $inPeriod = $change->effective_from && $change->effective_from->gte($billingStartDate) && $change->effective_from->lt($billingEndDate) ? 'YES' : 'NO';
                Log::info("  Plan Change ID {$change->id}: {$change->current_port_capacity} → {$change->new_port_capacity}, effective: {$effDate}, in period: {$inPeriod}, isCapacityChange: ".($change->isCapacityChange() ? 'YES' : 'NO'));
            }

            foreach ($futureChanges as $idx => $change) {
                Log::info("  Change #{$idx} in period: {$change->current_port_capacity} → {$change->new_port_capacity} effective from {$change->effective_from->format('Y-m-d')} (ID: {$change->id}, isCapacityChange: ".($change->isCapacityChange() ? 'YES' : 'NO').')');
            }
            Log::info('=== END DEBUG ===');

            // Determine if this is the first service invoice for the application (or first after reactivation)
            $isFirstServiceInvoice = Invoice::where('application_id', $application->id)
                ->where('invoice_purpose', 'service')
                ->where('status', '!=', 'cancelled')
                ->doesntExist();

            $isFirstInvoiceAfterReactivation = false;
            if (! $isFirstServiceInvoice && $application->billing_resume_date) {
                $lastDisconnectedHistory = \App\Models\ApplicationServiceStatusHistory::where('application_id', $application->id)
                    ->where('status', 'disconnected')
                    ->whereNotNull('effective_from')
                    ->latest('effective_from')
                    ->first();

                if ($lastDisconnectedHistory && \Carbon\Carbon::parse($application->billing_resume_date)->gte(\Carbon\Carbon::parse($lastDisconnectedHistory->effective_from))) {
                    // This is the first invoice after a disconnection and reactivation
                    $isFirstInvoiceAfterReactivation = true;
                }
            }

            $segmentStart = $billingStartDate->copy();
            $currentCapacity = $baseCapacity;
            $currentPlan = $basePlanNormalized;
            $segments = [];
            $prorationTotal = 0.0;

            // Build suspension intervals from service status history so we can:
            // - Exclude suspended days from billing (proration deduction)
            // - Add 0-amount invoice line items for suspended periods (for audit/clarity)
            // Interval semantics: [suspended_from, next_status_effective_from) (end is exclusive).
            $statusHistory = \App\Models\ApplicationServiceStatusHistory::query()
                ->where('application_id', $application->id)
                ->whereNotNull('effective_from')
                ->orderBy('effective_from')
                ->orderBy('id')
                ->get(['status', 'effective_from']);

            $suspensionIntervals = [];
            $openSuspensionStart = null;
            foreach ($statusHistory as $entry) {
                $status = $entry->status;
                $effectiveFrom = $entry->effective_from ? \Carbon\Carbon::parse($entry->effective_from)->startOfDay() : null;

                if (! $effectiveFrom) {
                    continue;
                }

                if ($status === 'suspended') {
                    $openSuspensionStart = $effectiveFrom;

                    continue;
                }

                if ($openSuspensionStart) {
                    // Any status change (live/disconnected) ends the suspension.
                    $suspensionIntervals[] = [
                        'from' => $openSuspensionStart->copy(),
                        'to' => $effectiveFrom->copy(), // exclusive
                    ];
                    $openSuspensionStart = null;
                }
            }

            // If we have an open-ended suspension (bad/old data or still suspended),
            // treat it as suspended until the end of the current billed period.
            if ($openSuspensionStart) {
                $suspensionIntervals[] = [
                    'from' => $openSuspensionStart->copy(),
                    'to' => $billingEndDate->copy()->addDay()->startOfDay(), // exclusive
                ];
            }

            // Add explicit 0-amount line items for suspended periods overlapping this billing window.
            // This is purely informational and should not affect totals.
            $suspensionLineItems = [];
            foreach ($suspensionIntervals as $interval) {
                $from = $interval['from']->copy()->startOfDay();
                $toExclusive = $interval['to']->copy()->startOfDay();

                $windowStart = $billingStartDate->copy()->startOfDay();
                $windowEndExclusive = $billingEndDate->copy()->addDay()->startOfDay();

                $overlapStart = $windowStart->greaterThan($from) ? $windowStart : $from;
                $overlapEnd = $windowEndExclusive->lessThan($toExclusive) ? $windowEndExclusive : $toExclusive;

                if ($overlapEnd->gt($overlapStart)) {
                    $days = $overlapStart->diffInDays($overlapEnd);
                    $endInclusive = $overlapEnd->copy()->subDay();

                    $suspensionLineItems[] = [
                        'start' => $overlapStart->format('Y-m-d'),
                        'end' => $endInclusive->format('Y-m-d'),
                        'description' => 'Service Suspended - Period: '.$overlapStart->format('d/m/Y').' to '.$endInclusive->format('d/m/Y'),
                        'quantity' => 0,
                        'rate' => 0,
                        'amount' => 0,
                        'show_period' => true,
                        'days' => $days,
                        'is_suspension' => true,
                    ];
                }
            }

            $getBillingCycleDays = function ($plan) {
                $plan = strtolower(trim($plan));

                return match ($plan) {
                    'annual', 'arc' => 365,
                    'quarterly' => 90,
                    'monthly', 'mrc' => 30,
                    default => 30,
                };
            };

            $getBillableRanges = function (\Carbon\Carbon $start, \Carbon\Carbon $end) use ($suspensionIntervals): array {
                // Segment is inclusive [start, end].
                // Suspension intervals are stored as [from, to) where "to" is exclusive.
                $segmentStart = $start->copy()->startOfDay();
                $segmentEndExclusive = $end->copy()->addDay()->startOfDay();

                $overlaps = [];
                foreach ($suspensionIntervals as $interval) {
                    $from = $interval['from']->copy()->startOfDay();
                    $to = $interval['to']->copy()->startOfDay();

                    $overlapStart = $segmentStart->greaterThan($from) ? $segmentStart : $from;
                    $overlapEnd = $segmentEndExclusive->lessThan($to) ? $segmentEndExclusive : $to;

                    if ($overlapEnd->gt($overlapStart)) {
                        $overlaps[] = ['from' => $overlapStart, 'to' => $overlapEnd]; // to is exclusive
                    }
                }

                if (empty($overlaps)) {
                    return [
                        ['from' => $segmentStart, 'to' => $segmentEndExclusive],
                    ];
                }

                usort($overlaps, function ($a, $b) {
                    return strcmp($a['from']->format('Y-m-d'), $b['from']->format('Y-m-d'));
                });

                // Merge overlapping overlaps
                $merged = [];
                foreach ($overlaps as $ov) {
                    if (empty($merged)) {
                        $merged[] = $ov;

                        continue;
                    }

                    $last = $merged[count($merged) - 1];
                    if ($ov['from']->lte($last['to'])) {
                        if ($ov['to']->gt($last['to'])) {
                            $merged[count($merged) - 1]['to'] = $ov['to'];
                        }
                    } else {
                        $merged[] = $ov;
                    }
                }

                $billables = [];
                $cursor = $segmentStart->copy();
                foreach ($merged as $ov) {
                    if ($ov['from']->gt($cursor)) {
                        $billables[] = ['from' => $cursor->copy(), 'to' => $ov['from']->copy()];
                    }
                    $cursor = $ov['to']->copy();
                }

                if ($segmentEndExclusive->gt($cursor)) {
                    $billables[] = ['from' => $cursor->copy(), 'to' => $segmentEndExclusive->copy()];
                }

                return $billables;
            };

            $addSegment = function ($start, $end, $capacity, $plan) use ($getPortAmount, &$segments, &$prorationTotal, $getBillingCycleDays, $getBillableRanges) {
                if ($end->lte($start)) {
                    return;
                }
                $fullAmount = $getPortAmount($capacity, $plan);
                if ($fullAmount === null || $fullAmount <= 0) {
                    return;
                }
                $billingCycleDays = $getBillingCycleDays($plan);
                $billableRanges = $getBillableRanges($start, $end);
                foreach ($billableRanges as $range) {
                    $rangeStart = $range['from']->copy()->startOfDay();
                    $rangeEndInclusive = $range['to']->copy()->subDay()->startOfDay();

                    if ($rangeEndInclusive->lt($rangeStart)) {
                        continue;
                    }

                    $rangeDays = $rangeStart->diffInDays($rangeEndInclusive) + 1;
                    if ($rangeDays <= 0) {
                        continue;
                    }

                    $planLower = strtolower($plan);
                    $isOriginalRange = $rangeStart->eq($start->copy()->startOfDay()) && $rangeEndInclusive->eq($end->copy()->startOfDay());

                    if ($isOriginalRange && in_array($planLower, ['monthly', 'mrc']) && $rangeDays >= 28 && $rangeDays <= 31) {
                        $prorated = $fullAmount;
                    } else {
                        $prorated = round(($fullAmount * $rangeDays) / $billingCycleDays, 2);
                    }

                    $prorationTotal += $prorated;

                    $planLabel = match (strtolower($plan)) {
                        'annual', 'arc' => 'Annual (ARC)',
                        'quarterly' => 'Quarterly',
                        'monthly', 'mrc' => 'Monthly (MRC)',
                        default => ucfirst($plan),
                    };

                    $startFormatted = $rangeStart->format('d/m/Y');
                    $endFormatted = $rangeEndInclusive->format('d/m/Y');

                    // Always show period so invoice reads like a timeline for the user
                    $description = "IX Service - {$capacity} Port Capacity ({$planLabel}) - Period: {$startFormatted} to {$endFormatted}";

                    $segments[] = [
                        'start' => $rangeStart->format('Y-m-d'),
                        'end' => $rangeEndInclusive->format('Y-m-d'),
                        'capacity' => $capacity,
                        'plan' => $plan,
                        'plan_label' => $planLabel,
                        'days' => $rangeDays,
                        'billing_cycle_days' => $billingCycleDays,
                        'amount_full' => $fullAmount,
                        'amount_prorated' => $prorated,
                        'description' => $description,
                        'quantity' => 1,
                        'rate' => $fullAmount,
                        'amount' => $prorated,
                        'show_period' => true,
                    ];
                }
            };

            // First invoice proration logic: If activation is mid-month, bill for remaining days, then align to 1st
            // This must be done AFTER addSegment is defined so it can use the function
            if (($isFirstServiceInvoice || $isFirstInvoiceAfterReactivation) && $application->billing_anchor_date) {
                $actualServiceStartDate = $application->billing_resume_date
                    ? \Carbon\Carbon::parse($application->billing_resume_date)->startOfDay()
                    : \Carbon\Carbon::parse($application->service_activation_date)->startOfDay();

                $anchorDate = \Carbon\Carbon::parse($application->billing_anchor_date)->startOfDay();

                // If activation date is not the 1st of the month, and not the anchor date,
                // generate a prorated segment for the remaining days of the activation month.
                if ($actualServiceStartDate->lt($anchorDate)) {
                    // Proration end date should be the day before anchor date (inclusive)
                    // So if anchor is Feb 1, proration ends on Jan 31
                    $prorationEndDate = $anchorDate->copy()->subDay();

                    if ($actualServiceStartDate->lte($prorationEndDate)) {
                        // Use addSegment to properly handle suspended periods within proration
                        // getBillableRanges expects inclusive end date and adds a day internally, so pass prorationEndDate directly (inclusive)
                        $addSegment($actualServiceStartDate, $prorationEndDate->copy()->startOfDay(), $currentCapacity, $currentPlan);
                    }

                    // Shift the main billing start date to the anchor date for the main billing cycle
                    $billingStartDate = $anchorDate->copy();
                    $segmentStart = $billingStartDate->copy();
                }
            }

            // Process changes in chronological order
            foreach ($futureChanges as $index => $change) {
                $changeDate = $change->effective_from->copy();

                // Only process if change date is strictly within the billing period
                // (after segment start and strictly before billing end)
                if ($changeDate->gt($segmentStart) && $changeDate->lt($billingEndDate)) {
                    // Add segment for the period before this change
                    Log::info("Processing change #{$index}: Adding segment from {$segmentStart->format('Y-m-d')} to {$changeDate->format('Y-m-d')} with capacity {$currentCapacity}");
                    $addSegment($segmentStart, $changeDate, $currentCapacity, $currentPlan);

                    // Update capacity if this is a capacity change
                    if ($change->isCapacityChange() && $change->new_port_capacity) {
                        $oldCapacity = $currentCapacity;
                        $currentCapacity = $change->new_port_capacity;
                        Log::info("Port capacity change during billing: {$oldCapacity} → {$currentCapacity} effective from {$changeDate->format('Y-m-d')} (Change ID: {$change->id})");
                    } else {
                        Log::info("Change #{$index} is not a capacity change or has no new_port_capacity. Current capacity remains: {$currentCapacity}");
                    }

                    // Billing cycle changes should NOT apply during current billing cycle
                    // They will only affect the NEXT billing cycle calculation
                    // So we keep using the current plan for the rest of this billing period

                    // Move segment start to the change date
                    $segmentStart = $changeDate;
                } else {
                    Log::info("Skipping change #{$index} (effective: {$changeDate->format('Y-m-d')}) - outside segment range ({$segmentStart->format('Y-m-d')} to {$billingEndDate->format('Y-m-d')})");
                }
            }

            // Add final segment from last change (or start) to billing end date
            // Only add if there's remaining time and we haven't reached the end
            if ($segmentStart->lt($billingEndDate)) {
                Log::info("Adding final segment: {$segmentStart->format('Y-m-d')} to {$billingEndDate->format('Y-m-d')} with capacity {$currentCapacity} (after processing ".count($futureChanges).' changes)');
                $addSegment($segmentStart, $billingEndDate->copy(), $currentCapacity, $currentPlan);
            } else {
                Log::info("No final segment needed - segmentStart ({$segmentStart->format('Y-m-d')}) >= billingEndDate ({$billingEndDate->format('Y-m-d')})");
            }

            // Remove any duplicate or zero-day segments
            $segments = array_filter($segments, function ($seg) {
                if (! isset($seg['days']) || $seg['days'] <= 0) {
                    return false;
                }

                // Keep suspension rows even with amount=0
                if (! empty($seg['is_suspension'])) {
                    return true;
                }

                return isset($seg['amount']) && (float) $seg['amount'] > 0;
            });

            // Re-index array to ensure proper ordering
            $segments = array_values($segments);

            if (! empty($suspensionLineItems)) {
                $segments = array_merge($segments, $suspensionLineItems);

                // Sort by start date (and keep service charge rows before suspension rows on same day)
                usort($segments, function ($a, $b) {
                    $aStart = $a['start'] ?? '';
                    $bStart = $b['start'] ?? '';
                    if ($aStart === $bStart) {
                        $aIsSusp = ! empty($a['is_suspension']);
                        $bIsSusp = ! empty($b['is_suspension']);

                        return $aIsSusp <=> $bIsSusp;
                    }

                    return strcmp($aStart, $bStart);
                });
            }

            if ($prorationTotal <= 0) {
                return ['error' => 'Unable to calculate charges. Please check plan and pricing configuration.'];
            }

            $adjustments = [];
            $upgradeAdjustmentTotal = 0.0;
            $downgradeAdjustmentTotal = 0.0;

            // Plan change adjustments from the PREVIOUS billing period appear on the NEXT invoice.
            // This applies even if the previous invoice was not paid: the next invoice will show
            // - Downgrade (credit): extra amount paid in previous period for days after downgrade → subtract from this invoice, no GST (already paid in previous period).
            // - Upgrade (debit): additional amount for days upgraded in previous period → add to this invoice, GST applied on this amount.
            $previousPeriodEnd = $billingStartDate->copy()->subDay()->endOfDay();
            $billingPlanNormalized = strtolower(trim($billingPlan ?? 'monthly'));
            if (in_array($billingPlanNormalized, ['annual', 'arc'])) {
                $previousPeriodStart = $billingStartDate->copy()->subYear()->startOfDay();
            } elseif ($billingPlanNormalized === 'quarterly') {
                $previousPeriodStart = $billingStartDate->copy()->subMonths(3)->startOfDay();
            } else {
                $previousPeriodStart = $billingStartDate->copy()->subMonth()->startOfDay();
            }

            $pendingAdjustments = PlanChangeRequest::where('application_id', $application->id)
                ->where('status', 'approved')
                ->where('adjustment_applied', false)
                ->whereNotNull('effective_from')
                ->whereBetween('effective_from', [$previousPeriodStart, $previousPeriodEnd])
                ->orderBy('effective_from')
                ->get();

            foreach ($pendingAdjustments as $adjustment) {
                if ($adjustment->isCapacityChange() && $adjustment->adjustment_amount != 0) {
                    $adjustmentAmount = (float) $adjustment->adjustment_amount;

                    // Upgrade (positive): additional amount for upgraded days in previous period → add to this invoice, GST will be calculated on it.
                    // Downgrade (negative): credit for overpayment in previous period → subtract from this invoice, no GST (already paid in previous month).
                    if ($adjustmentAmount > 0) {
                        $upgradeAdjustmentTotal += $adjustmentAmount;
                    } else {
                        $downgradeAdjustmentTotal += abs($adjustmentAmount);
                    }

                    $effectiveDateStr = $adjustment->effective_from ? $adjustment->effective_from->format('d/m/Y') : '';
                    $currentPlanDesc = $adjustment->current_port_capacity.(isset($adjustment->current_billing_plan) && $adjustment->current_billing_plan ? ' ('.strtoupper($adjustment->current_billing_plan).')' : '');
                    $newPlanDesc = $adjustment->new_port_capacity.(isset($adjustment->new_billing_plan) && $adjustment->new_billing_plan ? ' ('.strtoupper($adjustment->new_billing_plan).')' : '');
                    $particulars = $adjustment->change_type === 'upgrade'
                        ? "Upgrade adjustment (previous period) - Plan change from {$currentPlanDesc} to {$newPlanDesc} effective {$effectiveDateStr}. Additional amount for upgraded days: ₹".number_format(abs($adjustmentAmount), 2).' (GST applicable)'
                        : "Downgrade adjustment / Credit (previous period) - Plan change from {$currentPlanDesc} to {$newPlanDesc} effective {$effectiveDateStr}. Credit for excess paid: ₹".number_format(abs($adjustmentAmount), 2).' (No GST - already paid in previous period)';

                    $adjustments[] = [
                        'plan_change_id' => $adjustment->id,
                        'type' => $adjustment->change_type,
                        'description' => $adjustment->change_type === 'upgrade'
                            ? "Upgrade adjustment: {$adjustment->current_port_capacity} → {$adjustment->new_port_capacity}"
                            : "Downgrade adjustment: {$adjustment->current_port_capacity} → {$adjustment->new_port_capacity}",
                        'particulars' => $particulars,
                        'effective_from' => $adjustment->effective_from ? $adjustment->effective_from->format('Y-m-d') : null,
                        'amount' => $adjustmentAmount,
                    ];
                }
            }

            // Base amount = proration for current period + upgrade adjustments (from previous period). GST is calculated on this.
            // Downgrade credits (from previous period) are subtracted after GST; no GST on credits (already paid in previous period).
            $baseAmount = $prorationTotal + $upgradeAdjustmentTotal;

            // Determine GST type (IGST vs CGST+SGST) by buyer GST state code vs seller (supplier) state code, not place of supply
            $kycDetails = $application->kyc_details ?? [];
            $buyerGstin = $kycDetails['gstin'] ?? $application->gstin ?? '';
            $buyerStateCode = $this->extractStateCodeFromGstin($buyerGstin);
            $sellerStateCode = $application->seller_state_code ?? $buyerStateCode;
            $nixiCredentials = $this->getNixiLocationCredentials($sellerStateCode);
            $supplierStateCode = $nixiCredentials['supplier_state_code'] ?? '07';
            $normalizedBuyer = trim((string) $buyerStateCode);
            $normalizedSupplier = trim((string) $supplierStateCode);
            $isSameState = ($normalizedBuyer === $normalizedSupplier && $normalizedBuyer !== '' && $normalizedSupplier !== '');

            if ($isSameState) {
                $cgstAmount = round(($baseAmount * 9) / 100, 2);
                $sgstAmount = round(($baseAmount * 9) / 100, 2);
                $gstAmount = $cgstAmount + $sgstAmount;
            } else {
                $gstAmount = round(($baseAmount * 18) / 100, 2);
            }

            $amount = $baseAmount;
            // Total = Base + GST - Downgrade credits (downgrade credits don't have GST as it was already paid)
            $totalAmount = round($amount + $gstAmount - $downgradeAdjustmentTotal, 2);

            $carryForwardAmount = 0.0;
            $hasCarryForward = false;
            $carryForwardInvoices = [];

            // Only find invoices that are MANUALLY marked for carry forward
            // Do NOT automatically carry forward from overdue/pending invoices
            // Carry forward only happens when admin manually marks it during payment verification
            $invoicesWithCarryForward = Invoice::where('application_id', $application->id)
                ->where('status', '!=', 'cancelled')
                ->where('has_carry_forward', true)
                ->where('forwarded_amount', '>', 0)
                ->whereNull('forwarded_to_invoice_date') // Not yet applied to a new invoice
                ->get();

            foreach ($invoicesWithCarryForward as $invoice) {
                // Only use the forwarded_amount from manually marked invoices
                $balance = $invoice->forwarded_amount;

                if ($balance > 0) {
                    $carryForwardAmount += $balance;
                    $hasCarryForward = true;
                    $carryForwardInvoices[] = [
                        'invoice_id' => $invoice->id,
                        'invoice_number' => $invoice->invoice_number,
                        'amount' => $balance,
                    ];
                }
            }

            // Carry forward amount already includes GST from previous invoices, so no GST is added again
            $finalTotalAmount = $totalAmount + $carryForwardAmount;

            // Add adjustments to segments so they appear in the form as line items with proper particulars
            $allSegments = $segments;
            if (! empty($adjustments)) {
                foreach ($adjustments as $adj) {
                    $adjAmount = (float) ($adj['amount'] ?? 0);
                    $isUpgrade = ($adj['type'] ?? '') === 'upgrade' || $adjAmount > 0;
                    $isDowngrade = ($adj['type'] ?? '') === 'downgrade' || $adjAmount < 0;

                    // Use full particulars (effective date, plan change details, amount) for invoice line item
                    $description = $adj['particulars'] ?? (ucfirst($adj['type'] ?? 'Adjustment').' Adjustment: '.($adj['description'] ?? 'Plan change adjustment'));
                    if (empty($adj['particulars']) && isset($adj['effective_from']) && $adj['effective_from']) {
                        $description .= ' (Effective from: '.\Carbon\Carbon::parse($adj['effective_from'])->format('d/m/Y').')';
                    }
                    if (empty($adj['particulars'])) {
                        if ($isUpgrade) {
                            $description .= ' - Additional payment (GST will be calculated)';
                        } elseif ($isDowngrade) {
                            $description .= ' - Credit (GST already paid on excess amount)';
                        }
                    }

                    $allSegments[] = [
                        'description' => $description,
                        'quantity' => 1,
                        'rate' => abs($adjAmount),
                        'amount' => $adjAmount, // Keep original sign (positive for upgrade, negative for downgrade)
                        'is_adjustment' => true,
                        'adjustment_type' => $adj['type'] ?? 'adjustment',
                        'plan_change_id' => $adj['plan_change_id'] ?? null,
                    ];
                }
            }

            return [
                'billing_start_date' => $billingStartDate->format('Y-m-d'),
                'billing_end_date' => $billingEndDate->format('Y-m-d'),
                'billing_period' => $billingPeriod,
                'due_date' => $dueDate->format('Y-m-d'),
                'segments' => $allSegments, // Include adjustments in segments
                'adjustments' => $adjustments,
                'amount' => $amount,
                'gst_amount' => $gstAmount,
                'total_amount' => $totalAmount,
                'carry_forward_amount' => $carryForwardAmount,
                'has_carry_forward' => $hasCarryForward,
                'carry_forward_invoices' => $carryForwardInvoices,
                'final_total_amount' => $finalTotalAmount,
                'proration_total' => $prorationTotal,
                'upgrade_adjustment_total' => $upgradeAdjustmentTotal,
                'downgrade_adjustment_total' => $downgradeAdjustmentTotal,
                'adjustment_total' => $upgradeAdjustmentTotal - $downgradeAdjustmentTotal,
            ];
        } catch (Exception $e) {
            Log::error('Error calculating invoice details: '.$e->getMessage());

            return ['error' => 'Error calculating invoice details: '.$e->getMessage()];
        }
    }

    /**
     * IX Account: Generate Invoice (supports recurring invoices).
     */
    public function ixAccountGenerateInvoice(Request $request, $id)
    {
        try {
            $admin = $this->getCurrentAdmin();

            if (! $this->hasRole($admin, 'ix_account')) {
                return back()->with('error', 'You do not have permission to perform this action.');
            }

            $application = Application::with('user')->where('application_type', 'IX')->findOrFail($id);

            // Only allow invoice generation for LIVE applications,
            // except one-time pending invoices for suspended/disconnected applications.
            if (! $application->is_active && ! $request->boolean('pending_invoice')) {
                return back()->with('error', 'Invoice can only be generated for LIVE applications.');
            }

            if (! $request->boolean('pending_invoice') && ! $application->isVisibleToIxAccount()) {
                return back()->with('error', 'This application is not available for Account review.');
            }

            // If form data is provided, use it; otherwise calculate automatically
            if ($request->has('line_items')) {
                // Use form data
                $validated = $request->validate([
                    'billing_start_date' => 'required|date',
                    'billing_end_date' => 'required|date|after:billing_start_date',
                    'billing_period' => 'nullable|string|max:50',
                    'due_date' => 'required|date|after_or_equal:billing_start_date',
                    'amount' => 'required|numeric|min:0',
                    'gst_amount' => 'required|numeric|min:0',
                    'tds_amount' => [
                        'required',
                        'numeric',
                        'min:0',
                        function ($attribute, $value, $fail) use ($request) {
                            $baseAmount = (float) $request->input('amount', 0);
                            $tdsAmount = (float) $value;
                            $maxTdsAmount = ($baseAmount * 10) / 100; // 10% of base amount

                            if ($tdsAmount > $maxTdsAmount) {
                                $fail('The TDS amount cannot exceed 10% of the base amount (₹'.number_format($maxTdsAmount, 2).').');
                            }
                        },
                    ],
                    'total_amount' => 'required|numeric|min:0',
                    'carry_forward_amount' => 'nullable|numeric|min:0',
                    'has_carry_forward' => 'nullable|boolean',
                    'line_items' => 'required|array',
                    'line_items.*.description' => 'required|string|max:500',
                    'line_items.*.quantity' => 'nullable|numeric|min:0',
                    'line_items.*.rate' => 'nullable|numeric|min:0',
                    'line_items.*.amount' => 'nullable|numeric|min:0',
                    'line_items.*.show_period' => 'nullable|boolean',
                ]);

                // Prepare segments from form data
                $segments = [];
                foreach ($validated['line_items'] as $item) {
                    $segments[] = [
                        'description' => $item['description'],
                        'quantity' => $item['quantity'] ?? 1,
                        'rate' => $item['rate'] ?? 0,
                        'amount' => $item['amount'] ?? 0,
                        // If show_period is set in the request, use it; otherwise default to false (unchecked)
                        'show_period' => isset($item['show_period']) ? (bool) $item['show_period'] : false,
                    ];
                }

                // Parse dates as date-only to avoid timezone issues
                $startDateStr = $validated['billing_start_date'];
                $endDateStr = $validated['billing_end_date'];
                $dueDateStr = $validated['due_date'];

                if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $startDateStr, $match)) {
                    $billingStartDate = \Carbon\Carbon::createFromFormat('Y-m-d', $match[1]);
                } else {
                    $billingStartDate = \Carbon\Carbon::parse($startDateStr);
                }

                if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $endDateStr, $match)) {
                    $billingEndDate = \Carbon\Carbon::createFromFormat('Y-m-d', $match[1]);
                } else {
                    $billingEndDate = \Carbon\Carbon::parse($endDateStr);
                }

                if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $dueDateStr, $match)) {
                    $dueDate = \Carbon\Carbon::createFromFormat('Y-m-d', $match[1]);
                } else {
                    $dueDate = \Carbon\Carbon::parse($dueDateStr);
                }
                $billingPeriod = $validated['billing_period'] ?? null;
                $amount = $validated['amount'];
                $gstAmount = $validated['gst_amount'];
                $tdsAmount = (float) ($validated['tds_amount'] ?? 0);
                // Calculate TDS percentage from TDS amount and base amount
                $tdsPercentage = $amount > 0 ? ($tdsAmount / $amount) * 100 : 0;
                $carryForwardAmount = (float) ($validated['carry_forward_amount'] ?? 0);
                $hasCarryForward = (bool) ($validated['has_carry_forward'] ?? false);
                // Note: total_amount from form is the final_total_amount (base + GST - TDS + carry forward)
                // The form field value already includes TDS deduction and carry forward
                $finalTotalAmount = (float) $validated['total_amount'];
                // Verify calculation: Base + GST - TDS + Carry Forward = Final Total
                // This is for validation - the form value is the source of truth
                $calculatedTotal = round($amount + $gstAmount - $tdsAmount + $carryForwardAmount, 2);
                // Use form value as it's what the user entered/calculated
                // But log if there's a discrepancy for debugging
                if (abs($finalTotalAmount - $calculatedTotal) > 0.01) {
                    Log::warning('Invoice total amount mismatch', [
                        'form_total' => $finalTotalAmount,
                        'calculated_total' => $calculatedTotal,
                        'base' => $amount,
                        'gst' => $gstAmount,
                        'tds' => $tdsAmount,
                        'carry_forward' => $carryForwardAmount,
                    ]);
                }
                // Calculate base total (without carry forward) for display/storage purposes
                // This is: Base + GST - TDS (without carry forward)
                $totalAmount = round($amount + $gstAmount - $tdsAmount, 2);
            } else {
                // Calculate automatically (existing logic)
                $invoiceData = $this->calculateInvoiceDetails($application);
                if (isset($invoiceData['error'])) {
                    return back()->with('error', $invoiceData['error']);
                }
                $segments = $invoiceData['segments'];
                // Parse dates as date-only to avoid timezone issues
                $startDateStr = $invoiceData['billing_start_date'];
                $endDateStr = $invoiceData['billing_end_date'];
                $dueDateStr = $invoiceData['due_date'];

                if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $startDateStr, $match)) {
                    $billingStartDate = \Carbon\Carbon::createFromFormat('Y-m-d', $match[1]);
                } else {
                    $billingStartDate = \Carbon\Carbon::parse($startDateStr);
                }

                if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $endDateStr, $match)) {
                    $billingEndDate = \Carbon\Carbon::createFromFormat('Y-m-d', $match[1]);
                } else {
                    $billingEndDate = \Carbon\Carbon::parse($endDateStr);
                }

                if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $dueDateStr, $match)) {
                    $dueDate = \Carbon\Carbon::createFromFormat('Y-m-d', $match[1]);
                } else {
                    $dueDate = \Carbon\Carbon::parse($dueDateStr);
                }
                $billingPeriod = $invoiceData['billing_period'];
                $amount = $invoiceData['amount'];
                $gstAmount = $invoiceData['gst_amount'];
                // TDS is calculated on base amount (before GST)
                $tdsPercentage = 0; // Default to 0 if not provided in auto-calculation
                $tdsAmount = 0; // Will be calculated if TDS percentage is set
                $totalAmount = $invoiceData['total_amount'];
                $carryForwardAmount = $invoiceData['carry_forward_amount'];
                $hasCarryForward = $invoiceData['has_carry_forward'];
                $finalTotalAmount = $invoiceData['final_total_amount'];
            }

            // Check for duplicate invoice (allow regenerate if credit note exists)
            if ($billingPeriod) {
                $existingInvoice = Invoice::where('application_id', $application->id)
                    ->where('billing_period', $billingPeriod)
                    ->where('status', '!=', 'cancelled')
                    ->whereNull('credit_note_pdf_path')
                    ->first();

                if ($existingInvoice) {
                    return back()->with('error', "An invoice for billing period '{$billingPeriod}' already exists (Invoice: {$existingInvoice->invoice_number}).");
                }

                // If invoice with credit note exists, allow regeneration (no error - continue)
            }

            // Prepare line items data
            $lineItemsData = $segments;
            $adjustments = [];
            $adjustmentTotal = 0.0;
            $prorationTotal = 0.0;
            $carryForwardInvoices = [];

            // If using form data, use the form values (amount, gst_amount, total_amount) directly
            // Don't recalculate from line_items as they might have been edited
            if ($request->has('line_items')) {
                // Use line items for the line_items_data array only
                $lineItemsData = [];
                foreach ($validated['line_items'] as $item) {
                    // Include show_period - unchecked checkboxes don't submit a value, so default to false
                    $lineItem = $item;
                    // If show_period is set in the request, use it; otherwise default to false (unchecked)
                    $lineItem['show_period'] = isset($item['show_period']) ? (bool) $item['show_period'] : false;
                    $lineItemsData[] = $lineItem;
                }

                // Use carry forward amount from form (don't recalculate - use what was shown to admin)
                $carryForwardAmount = (float) ($validated['carry_forward_amount'] ?? 0);
                $hasCarryForward = (bool) ($validated['has_carry_forward'] ?? false);

                // Get carry forward invoice details for line item description
                $carryForwardInvoices = [];
                if ($hasCarryForward && $carryForwardAmount > 0) {
                    // Find the invoices that have carry forward to get their invoice numbers for display
                    $invoicesWithCarryForward = Invoice::where('application_id', $application->id)
                        ->where('status', '!=', 'cancelled')
                        ->where(function ($q) {
                            $q->where(function ($q2) {
                                $q2->where('has_carry_forward', true)
                                    ->where('forwarded_amount', '>', 0)
                                    ->whereNull('forwarded_to_invoice_date');
                            })
                                ->orWhere(function ($q3) {
                                    $q3->where('payment_status', 'partial')
                                        ->orWhere(function ($q4) {
                                            $q4->where('payment_status', 'pending')
                                                ->where('due_date', '<', now('Asia/Kolkata'));
                                        });
                                });
                        })
                        ->get();

                    foreach ($invoicesWithCarryForward as $invoice) {
                        $balance = 0;
                        if ($invoice->has_carry_forward && $invoice->forwarded_amount > 0) {
                            $balance = $invoice->forwarded_amount;
                        } else {
                            $balance = $invoice->getRemainingBalance();
                        }
                        if ($balance > 0) {
                            $carryForwardInvoices[] = [
                                'invoice_id' => $invoice->id,
                                'invoice_number' => $invoice->invoice_number,
                                'amount' => $balance,
                            ];
                        }
                    }
                }

                // Calculate prorationTotal and adjustmentTotal from line items for metadata
                // (but don't use these for amount calculations - use form values instead)
                $prorationTotal = 0.0;
                $adjustmentTotal = 0.0;
                $upgradeAdjustmentTotal = 0.0;
                $downgradeAdjustmentTotal = 0.0;
                $adjustments = [];

                foreach ($validated['line_items'] as $item) {
                    $itemAmount = (float) ($item['amount'] ?? 0);
                    if (isset($item['is_adjustment']) && $item['is_adjustment']) {
                        // This is an adjustment line item
                        if ($itemAmount > 0) {
                            $upgradeAdjustmentTotal += $itemAmount;
                        } else {
                            $downgradeAdjustmentTotal += abs($itemAmount);
                        }
                        $adjustmentTotal += $itemAmount;
                        $planChangeId = isset($item['plan_change_id']) && $item['plan_change_id'] !== '' ? (int) $item['plan_change_id'] : null;
                        $adjustments[] = [
                            'type' => $item['adjustment_type'] ?? ($itemAmount > 0 ? 'upgrade' : 'downgrade'),
                            'description' => $item['description'] ?? 'Plan change adjustment',
                            'amount' => $itemAmount,
                            'plan_change_id' => $planChangeId,
                        ];
                    } elseif (! isset($item['is_carry_forward']) || ! $item['is_carry_forward']) {
                        $prorationTotal += $itemAmount;
                    }
                }

                // IMPORTANT: Use form values for amounts (amount, gst_amount, total_amount, finalTotalAmount)
                // These are already calculated correctly and shown to the admin
                // Don't recalculate from line items as they might differ
                // The values are already set above from $validated array
            } else {
                // Use from calculated data (auto-calculation path)
                $adjustments = $invoiceData['adjustments'] ?? [];
                $upgradeAdjustmentTotal = $invoiceData['upgrade_adjustment_total'] ?? 0;
                $downgradeAdjustmentTotal = $invoiceData['downgrade_adjustment_total'] ?? 0;
                $adjustmentTotal = $invoiceData['adjustment_total'] ?? ($upgradeAdjustmentTotal - $downgradeAdjustmentTotal);
                $prorationTotal = $invoiceData['proration_total'] ?? 0;
                $carryForwardInvoices = $invoiceData['carry_forward_invoices'] ?? [];
                $carryForwardAmount = $invoiceData['carry_forward_amount'] ?? 0;
                $hasCarryForward = $invoiceData['has_carry_forward'] ?? false;
                $amount = $invoiceData['amount'] ?? 0;
                $gstAmount = $invoiceData['gst_amount'] ?? 0;
                $totalAmount = $invoiceData['total_amount'] ?? 0;
                $finalTotalAmount = $invoiceData['final_total_amount'] ?? $totalAmount;
            }

            // Add adjustments as line items if present (only if not already in lineItemsData)
            // Check if adjustments are already in lineItemsData
            $adjustmentsInLineItems = false;
            if (is_array($lineItemsData)) {
                foreach ($lineItemsData as $item) {
                    if (is_array($item) && isset($item['is_adjustment']) && $item['is_adjustment']) {
                        $adjustmentsInLineItems = true;
                        break;
                    }
                }
            }

            // Only add adjustments if they're not already in line items
            if (! empty($adjustments) && ! $adjustmentsInLineItems) {
                Log::info('Adding adjustments to line items', [
                    'adjustments_count' => count($adjustments),
                    'adjustments' => $adjustments,
                ]);

                foreach ($adjustments as $adj) {
                    $adjAmount = (float) ($adj['amount'] ?? 0);
                    $isUpgrade = ($adj['type'] ?? '') === 'upgrade' || $adjAmount > 0;
                    $isDowngrade = ($adj['type'] ?? '') === 'downgrade' || $adjAmount < 0;

                    $description = ucfirst($adj['type'] ?? 'Adjustment').' Adjustment: '.($adj['description'] ?? 'Plan change adjustment');
                    if (isset($adj['effective_from']) && $adj['effective_from']) {
                        $description .= ' (Effective from: '.\Carbon\Carbon::parse($adj['effective_from'])->format('d/m/Y').')';
                    }

                    if ($isUpgrade) {
                        $description .= ' - Additional payment (GST will be calculated)';
                    } elseif ($isDowngrade) {
                        $description .= ' - Credit (GST already paid on excess amount)';
                    }

                    $lineItemsData[] = [
                        'description' => $description,
                        'quantity' => 1,
                        'rate' => abs($adjAmount),
                        'amount' => $adjAmount, // Keep original sign (positive for upgrade, negative for downgrade)
                        'is_adjustment' => true,
                        'adjustment_type' => $adj['type'] ?? 'adjustment',
                    ];
                }

                // Store metadata for subtotal calculations
                $lineItemsData['_metadata'] = [
                    'adjustments' => $adjustments,
                    'upgrade_adjustment_total' => $upgradeAdjustmentTotal ?? 0,
                    'downgrade_adjustment_total' => $downgradeAdjustmentTotal ?? 0,
                    'adjustment_total' => $adjustmentTotal,
                    'proration_total' => $prorationTotal,
                ];

                Log::info('Adjustments added to lineItemsData', [
                    'lineItemsData_count' => count($lineItemsData),
                    'has_metadata' => isset($lineItemsData['_metadata']),
                ]);
            } else {
                Log::info('Skipping adjustments addition', [
                    'adjustments_empty' => empty($adjustments),
                    'adjustments_in_line_items' => $adjustmentsInLineItems,
                    'adjustments_count' => ! empty($adjustments) ? count($adjustments) : 0,
                ]);
            }

            // Add carry forward as a line item if present
            if ($hasCarryForward && $carryForwardAmount > 0) {
                $carryForwardDescription = 'Carry Forward from Previous Invoice(s): ';
                $invoiceNumbers = array_map(function ($inv) {
                    return $inv['invoice_number'];
                }, $carryForwardInvoices);
                $carryForwardDescription .= implode(', ', $invoiceNumbers);

                $lineItemsData[] = [
                    'description' => $carryForwardDescription,
                    'quantity' => 1,
                    'rate' => $carryForwardAmount,
                    'amount' => $carryForwardAmount,
                    'is_carry_forward' => true,
                ];
            }

            // Ensure due_date is properly formatted as date string
            $dueDateFormatted = $dueDate instanceof \Carbon\Carbon ? $dueDate->format('Y-m-d') : $dueDate;

            // Don't recalculate carry-forward when using form data
            // The form values (carryForwardAmount, finalTotalAmount) are already correct
            // and were set earlier from the validated form data

            // Generate invoice number (sequential format: NIXIEX2526-XXXX)
            $baseInvoiceNumber = 'NIXIEX2526-';

            // Get last invoice with this prefix only
            $lastInvoice = \Illuminate\Support\Facades\DB::table('invoices')
                ->where('invoice_number', 'like', $baseInvoiceNumber.'%')
                ->orderBy('id', 'desc')
                ->value('invoice_number');

            if ($lastInvoice && preg_match('/NIXIEX2526-(\d{4})$/', $lastInvoice, $matches)) {
                $lastNumber = (int) $matches[1];
                $nextNumber = $lastNumber + 1;
            } else {
                // First invoice - start from 1923
                $nextNumber = 1925;
            }

            // Final invoice number
            $invoiceNumber = $baseInvoiceNumber.str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

            // Check if invoice number already exists and make it unique
            $counter = 1;
            $originalInvoiceNumber = $invoiceNumber;
            while (Invoice::where('invoice_number', $invoiceNumber)->exists()) {
                // If invoice number exists, try next sequential number
                $nextNumber = $nextNumber + 1;
                $invoiceNumber = $baseInvoiceNumber.str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
                $counter++;

                // Safety check to prevent infinite loop
                if ($counter > 100) {
                    Log::error("Unable to generate unique invoice number for application {$application->id} after 100 attempts");

                    return redirect()->route('admin.applications.show', $application->id)
                        ->with('error', 'Unable to generate unique invoice number. Please contact support.');
                }
            }

            Log::info("Generated invoice number: {$invoiceNumber} for application {$application->id}, billing period: {$billingPeriod}");

            Log::info("Preparing invoice for application {$application->id}: invoiceNumber='{$invoiceNumber}', invoiceDate=".now('Asia/Kolkata')->format('Y-m-d').", dueDate={$dueDateFormatted}, billingPeriod='{$billingPeriod}'");

            // Create temporary invoice object (not saved) to call e-invoice API first
            // Note: We don't create PaymentTransaction yet - only after e-invoice API succeeds
            $tempInvoice = new Invoice([
                'application_id' => $application->id,
                'invoice_number' => $invoiceNumber,
                'invoice_date' => now('Asia/Kolkata'),
                'due_date' => $dueDateFormatted,
                'billing_period' => $billingPeriod,
                'billing_start_date' => $billingStartDate->format('Y-m-d'),
                'billing_end_date' => $billingEndDate->format('Y-m-d'),
                'line_items' => $lineItemsData,
                'amount' => $amount,
                'gst_amount' => $gstAmount,
                'tds_percentage' => $tdsPercentage,
                'tds_amount' => $tdsAmount,
                'total_amount' => $finalTotalAmount,
                'paid_amount' => 0,
                'balance_amount' => $finalTotalAmount,
                'payment_status' => 'pending',
                'carry_forward_amount' => $carryForwardAmount,
                'has_carry_forward' => $hasCarryForward,
                'currency' => 'INR',
                'status' => 'pending',
                'payu_payment_link' => null, // Will be set after API succeeds
                'generated_by' => $admin->id,
            ]);

            // Check GSTIN status before calling e-invoice API
            $kycDetails = $application->kyc_details ?? [];
            $buyerGstin = $kycDetails['gstin'] ?? $application->gstin ?? '';
            $isGstinInactive = false;
            $einvoiceData = null;
            $isEinvoiceSuccess = false;

            if (! empty($buyerGstin)) {
                // Get GST verification to check status
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

                if ($gstVerification) {
                    // Check if GSTIN is inactive/cancelled
                    $companyStatus = strtolower(trim($gstVerification->company_status ?? ''));

                    // Try to get gstin_status from verification_data if not in model
                    $gstinStatus = '';
                    if ($gstVerification->verification_data && is_array($gstVerification->verification_data)) {
                        $verificationData = $gstVerification->verification_data;
                        $sourceOutput = $verificationData['result']['source_output'] ?? [];
                        $gstinStatus = strtolower(trim($sourceOutput['gstin_status'] ?? ''));
                    }

                    // Check if GSTIN is inactive/cancelled
                    $isGstinInactive = in_array($companyStatus, ['cancelled', 'canceled', 'cancelled/suspended', 'inactive', 'suspended'])
                        || in_array($gstinStatus, ['cancelled', 'canceled', 'cancelled/suspended', 'inactive', 'suspended']);
                }
            }

            // Only call e-invoice API if GSTIN is active
            if (! $isGstinInactive) {
                Log::info("Calling e-invoice API for invoice {$invoiceNumber} before creating invoice record");

                try {
                    $einvoiceData = $this->callEinvoiceApi($application, $tempInvoice);
                } catch (\Exception $e) {
                    // Catch validation errors (date validation, etc.)
                    Log::error('E-invoice API validation error', [
                        'invoice_number' => $invoiceNumber,
                        'error_message' => $e->getMessage(),
                        'application_id' => $application->id,
                    ]);

                    return back()->with('error', $e->getMessage());
                }

                // Check if e-invoice API call was successful (callEinvoiceApi returns error array on failure, not null)
                $isEinvoiceSuccess = false;
                if ($einvoiceData && is_array($einvoiceData)) {
                    $status = $einvoiceData['Status'] ?? $einvoiceData['status'] ?? '';
                    $irn = $einvoiceData['Irn'] ?? '';
                    $errorCode = $einvoiceData['ErrorCode'] ?? '';

                    // For ErrorCode 2150 (Duplicate IRN), extract IRN and AckNo from InfoDtls
                    if ($errorCode === '2150' && empty($irn) && isset($einvoiceData['InfoDtls']) && is_array($einvoiceData['InfoDtls'])) {
                        // Extract IRN and AckNo from InfoDtls array
                        foreach ($einvoiceData['InfoDtls'] as $infoDetail) {
                            if (isset($infoDetail['InfCd']) && $infoDetail['InfCd'] === 'DUPIRN' && isset($infoDetail['Desc'])) {
                                $desc = $infoDetail['Desc'];
                                $irn = $desc['Irn'] ?? '';
                                // Update einvoiceData with extracted values for later storage
                                $einvoiceData['Irn'] = $irn;
                                $einvoiceData['AckNo'] = $desc['AckNo'] ?? '';
                                $einvoiceData['AckDate'] = $desc['AckDt'] ?? '';
                                break;
                            }
                        }
                    }

                    // API is successful if:
                    // 1. Status is '1' and IRN is not empty (normal success)
                    // 2. ErrorCode is '2150' (Duplicate IRN) and IRN is extracted from InfoDtls
                    $isEinvoiceSuccess = (($status === '1' || $status === 1) && ! empty($irn))
                        || ($errorCode === '2150' && ! empty($irn));

                    // Log duplicate IRN as info (not error) since it's acceptable
                    if ($errorCode === '2150' && ! empty($irn)) {
                        Log::info('E-invoice API returned duplicate IRN - invoice already registered, proceeding with invoice creation', [
                            'invoice_number' => $invoiceNumber,
                            'irn' => $irn,
                            'ack_no' => $einvoiceData['AckNo'] ?? null,
                        ]);
                    }
                }

                if (! $isEinvoiceSuccess) {
                    $errorMessage = is_array($einvoiceData)
                        ? ($einvoiceData['ErrorMessage'] ?? 'E-invoice API call failed or returned error')
                        : 'E-invoice API did not return a response. Check logs for connection or API errors.';
                    $errorCode = is_array($einvoiceData) ? ($einvoiceData['ErrorCode'] ?? 'Unknown') : 'NO_RESPONSE';
                    Log::error('E-invoice API failed - invoice will not be created', [
                        'invoice_number' => $invoiceNumber,
                        'error_code' => $errorCode,
                        'error_message' => $errorMessage,
                        'response' => $einvoiceData,
                    ]);

                    return back()->with('error', "E-invoice API failed (ErrorCode: {$errorCode}): {$errorMessage}. Invoice was not created.");
                }

                Log::info("E-invoice API succeeded for invoice {$invoiceNumber} - proceeding with invoice creation");
            } else {
                // GSTIN is inactive/cancelled - skip e-invoice API and generate invoice without IRN
                Log::info("GSTIN {$buyerGstin} is inactive/cancelled - skipping e-invoice API call and generating invoice without IRN", [
                    'invoice_number' => $invoiceNumber,
                    'application_id' => $application->id,
                ]);
            }

            // Create PaymentTransaction and invoice
            // Note: For inactive/cancelled GSTINs, e-invoice API is skipped and invoice is created without IRN
            // Generate PayU payment link
            $payuService = new \App\Services\PayuService;
            $transactionId = 'INV-'.time().'-'.strtoupper(\Illuminate\Support\Str::random(8));

            // Create PaymentTransaction for invoice payment
            $paymentTransaction = PaymentTransaction::create([
                'user_id' => $application->user_id,
                'application_id' => $application->id,
                'transaction_id' => $transactionId,
                'payment_status' => 'pending',
                'payment_mode' => 'live',
                'amount' => $finalTotalAmount,
                'currency' => 'INR',
                'product_info' => 'NIXI IX Service Invoice - '.$invoiceNumber,
                'response_message' => 'Invoice payment pending',
            ]);

            $paymentData = $payuService->preparePaymentData([
                'transaction_id' => $transactionId,
                'amount' => $finalTotalAmount,
                'product_info' => 'NIXI IX Service Invoice - '.$invoiceNumber,
                'firstname' => $application->user->fullname,
                'email' => $application->user->email,
                'phone' => $application->user->mobile,
                'success_url' => url(route('user.applications.ix.payment-success', [], false)),
                'failure_url' => url(route('user.applications.ix.payment-failure', [], false)),
                'udf1' => $application->application_id,
                'udf2' => (string) $paymentTransaction->id, // Store payment transaction ID
                'udf3' => $invoiceNumber,
            ]);

            Log::info("Creating invoice for application {$application->id}: invoiceNumber='{$invoiceNumber}', invoiceDate=".now('Asia/Kolkata')->format('Y-m-d').", dueDate={$dueDateFormatted}, billingPeriod='{$billingPeriod}'");

            $buyerStateCode = $this->extractStateCodeFromGstin($buyerGstin);
            $sellerStateCode = $application->seller_state_code ?? $buyerStateCode;
            $nixiCreds = $this->getNixiLocationCredentials($sellerStateCode);
            $sellerGstinForInvoice = $nixiCreds['supplier_gstin'] ?? null;

            // Create the invoice (with or without e-invoice data depending on GSTIN status)
            $invoice = Invoice::create([
                'application_id' => $application->id,
                'invoice_number' => $invoiceNumber,
                'invoice_date' => now('Asia/Kolkata'),
                'due_date' => $dueDateFormatted,
                'billing_period' => $billingPeriod,
                'billing_start_date' => $billingStartDate->format('Y-m-d'),
                'billing_end_date' => $billingEndDate->format('Y-m-d'),
                'line_items' => $lineItemsData,
                'amount' => $amount,
                'gst_amount' => $gstAmount,
                'tds_percentage' => $tdsPercentage,
                'tds_amount' => $tdsAmount,
                'total_amount' => $finalTotalAmount,
                'paid_amount' => 0,
                'balance_amount' => $finalTotalAmount,
                'payment_status' => 'pending',
                'carry_forward_amount' => $carryForwardAmount,
                'has_carry_forward' => $hasCarryForward,
                'currency' => 'INR',
                'status' => 'pending',
                'payu_payment_link' => json_encode($paymentData), // Store full payment data
                'generated_by' => $admin->id,
                'seller_gstin' => $sellerGstinForInvoice,
            ]);

            // Mark adjustments as applied (only when plan_change_id is present)
            if (! empty($adjustments)) {
                $marked = 0;
                foreach ($adjustments as $adj) {
                    $planChangeId = $adj['plan_change_id'] ?? null;
                    if ($planChangeId) {
                        PlanChangeRequest::where('id', $planChangeId)->update([
                            'adjustment_applied' => true,
                            'adjustment_invoice_id' => $invoice->id,
                        ]);
                        $marked++;
                    }
                }
                if ($marked > 0) {
                    Log::info("Marked {$marked} adjustments as applied for invoice {$invoice->id}");
                }
            }

            // Mark previous invoices as paid if carry forward is applied
            if ($hasCarryForward && ! empty($carryForwardInvoices)) {
                foreach ($carryForwardInvoices as $cfInvoice) {
                    $previousInvoice = Invoice::find($cfInvoice['invoice_id']);
                    if ($previousInvoice) {
                        $forwardedAmount = $cfInvoice['amount'];
                        // When amount is carried forward: paid_amount = total_amount - forwarded_amount
                        // This ensures: Total = Paid + Forwarded (correct calculation)
                        $calculatedPaidAmount = $previousInvoice->total_amount - $forwardedAmount;
                        $previousInvoice->update([
                            'payment_status' => 'paid', // Mark as paid since full amount is handled (paid + forwarded)
                            'status' => 'paid',
                            'paid_amount' => $calculatedPaidAmount, // Set as total - forwarded for correct calculation
                            'balance_amount' => 0, // Balance is forwarded, so it's 0
                            'forwarded_amount' => $forwardedAmount,
                            'forwarded_to_invoice_date' => $invoice->invoice_date,
                            'has_carry_forward' => true, // Mark that this invoice had carry forward
                            'carry_forward_amount' => $forwardedAmount, // Store the forwarded amount
                            'paid_at' => now('Asia/Kolkata'),
                            'paid_by' => $admin->id,
                            'manual_payment_notes' => ($previousInvoice->manual_payment_notes ? $previousInvoice->manual_payment_notes.' | ' : '')."Amount forwarded to invoice {$invoice->invoice_number}",
                        ]);
                        Log::info("Marked invoice {$previousInvoice->invoice_number} as paid (forwarded {$forwardedAmount} to invoice {$invoice->invoice_number}). Paid amount: {$calculatedPaidAmount} (Total: {$previousInvoice->total_amount} - Forwarded: {$forwardedAmount})");
                    }
                }
            }

            Log::info("Invoice created successfully: ID={$invoice->id}, due_date={$invoice->due_date}, billing_start_date={$invoice->billing_start_date}, billing_end_date={$invoice->billing_end_date}");

            // Don't update service_activation_date - it should remain as the original activation date
            // The billing dates are now stored in the invoice itself

            // Store e-invoice API response (already called and verified before invoice creation)
            if ($einvoiceData) {
                // Prepare signed data (SignedInvoice and SignedQRCode) for JSON storage
                $signedData = [];
                if (isset($einvoiceData['SignedInvoice'])) {
                    $signedData['SignedInvoice'] = $einvoiceData['SignedInvoice'];
                }
                if (isset($einvoiceData['SignedQRCode'])) {
                    $signedData['SignedQRCode'] = $einvoiceData['SignedQRCode'];
                }

                // Prepare update data
                $updateData = [
                    'einvoice_signed_data' => ! empty($signedData) ? $signedData : null,
                    'einvoice_response' => $einvoiceData, // Store full response for reference
                ];

                // Store other fields in separate columns (matching exact API response field names)
                $fieldsToStore = [
                    'Irn' => 'einvoice_irn',
                    'AckNo' => 'einvoice_ack_no',
                    'AckDate' => 'einvoice_ack_date',
                    'Status' => 'einvoice_status',
                    'ErrorMessage' => 'einvoice_error_message',
                    'ErrorCode' => 'einvoice_error_code', // Add error code field
                ];

                foreach ($fieldsToStore as $apiField => $dbField) {
                    if (isset($einvoiceData[$apiField])) {
                        $value = $einvoiceData[$apiField];
                        // Convert AckNo to string if it's numeric
                        if ($apiField === 'AckNo' && is_numeric($value)) {
                            $value = (string) $value;
                        }
                        // Skip empty strings for error fields
                        if (in_array($apiField, ['ErrorMessage', 'ErrorCode']) && (empty($value) || $value === '')) {
                            continue;
                        }
                        $updateData[$dbField] = $value;
                    }
                }

                // Update invoice with API response data
                try {
                    $invoice->update($updateData);
                    Log::info("E-invoice API response stored for invoice {$invoice->id}", [
                        'irn' => $updateData['einvoice_irn'] ?? null,
                        'ack_no' => $updateData['einvoice_ack_no'] ?? null,
                        'status' => $updateData['einvoice_status'] ?? null,
                        'has_signed_data' => ! empty($signedData),
                        'update_data_keys' => array_keys($updateData),
                    ]);
                } catch (\Exception $e) {
                    Log::error("Failed to store e-invoice response for invoice {$invoice->id}: ".$e->getMessage(), [
                        'update_data' => $updateData,
                        'exception' => $e->getTraceAsString(),
                    ]);
                }
            } else {
                Log::warning("E-invoice API call failed or returned no data for invoice {$invoice->id}");
            }

            // Initialize PDF path variable
            $invoicePdfPath = null;

            // Generate invoice PDF
            try {
                $invoicePdf = $this->generateIxInvoicePdf($application, $invoice);
                $invoicePdfPath = 'applications/'.$application->user_id.'/ix/'.$invoiceNumber.'_invoice.pdf';

                Storage::disk('public')->put($invoicePdfPath, $invoicePdf->output());

                // Update invoice with PDF path
                $invoice->update(['pdf_path' => $invoicePdfPath]);
                Log::info("Invoice PDF generated successfully: {$invoicePdfPath}");
            } catch (Exception $e) {
                Log::error('Error generating IX invoice PDF: '.$e->getMessage());
                // Continue even if PDF generation fails - invoice is already created
            }

            // Log invoice generation
            ApplicationStatusHistory::log(
                $application->id,
                $application->status,
                $application->status, // Keep same status
                'admin',
                $admin->id,
                'Invoice generated by IX Account - '.$invoiceNumber
            );

            // Ensure membership invoice exists for this customer (user) for current FY when first service invoice is generated
            $invoicePurpose = $invoice->invoice_purpose ?? 'service';
            if ($invoicePurpose === 'service') {
                try {
                    $membershipInvoice = app(IxMembershipInvoiceService::class)->ensureMembershipInvoiceForUser($application->user_id, $admin->id);
                    if ($membershipInvoice) {
                        Log::info('Membership invoice generated with first service invoice', [
                            'user_id' => $application->user_id,
                            'membership_invoice_number' => $membershipInvoice->invoice_number,
                        ]);
                    }
                } catch (\Throwable $e) {
                    Log::warning('Membership invoice check failed after service invoice creation', [
                        'user_id' => $application->user_id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Send invoice email with PayU link
            try {
                // Get authorized representative name from application data
                $authorizedPersonName = $application->authorized_representative_details['name']
                    ?? $application->application_data['representative']['name']
                    ?? $application->user->fullname;

                // Get ISP name (user's fullname or company name)
                $ispName = $application->user->fullname;

                // Get billing dates from invoice
                $billingStartDate = $invoice->billing_start_date ? $invoice->billing_start_date->format('Y-m-d') : null;
                $billingEndDate = $invoice->billing_end_date ? $invoice->billing_end_date->format('Y-m-d') : null;

                Mail::to($application->user->email)->send(new IxApplicationInvoiceMail(
                    $application->user->fullname,
                    $application->application_id,
                    $invoiceNumber,
                    $finalTotalAmount,
                    $application->status,
                    $invoicePdfPath ?? null,
                    $payuService->getPaymentUrl(),
                    $paymentData,
                    $authorizedPersonName,
                    $ispName,
                    $billingStartDate,
                    $billingEndDate
                ));

                $invoice->update(['sent_at' => now('Asia/Kolkata')]);
                Log::info("IX invoice email sent to {$application->user->email} for application {$application->application_id}");
            } catch (Exception $e) {
                Log::error('Error sending invoice email: '.$e->getMessage());
            }

            $this->createMessageForAdmin(
                $admin->id,
                $application->user_id,
                'Invoice Generated',
                "Invoice {$invoiceNumber} has been generated for your application {$application->application_id}. Please complete the payment using the PayU link sent to your email."
            );

            $periodLabel = $billingPeriod ? ' ('.$this->getBillingPeriodLabel($application->billing_cycle, $billingPeriod).')' : '';

            return redirect()->route('admin.applications.show', $application->id)
                ->with('success', "Invoice generated and sent to user{$periodLabel}!");
        } catch (Exception $e) {
            Log::error('Error generating invoice: '.$e->getMessage());

            return redirect()->route('admin.applications.show', $application->id)
                ->with('error', 'An error occurred while generating invoice. Please try again.');
        }
    }

    /**
     * IX Account: Mark invoice as paid manually with payment ID and notes.
     */
    public function ixAccountMarkInvoicePaid(Request $request, $invoiceId)
    {
        try {
            $admin = $this->getCurrentAdmin();

            if (! $this->hasRole($admin, 'ix_account')) {
                return back()->with('error', 'You do not have permission to perform this action.');
            }

            $invoice = Invoice::with('application.user')->findOrFail($invoiceId);

            if (! $invoice->application || $invoice->application->application_type !== 'IX') {
                return back()->with('error', 'Invalid invoice or application.');
            }

            if (! $invoice->application->is_active) {
                return back()->with('error', 'Invoice can only be managed for LIVE applications.');
            }

            // Allow updating payment details even if already paid

            $validated = $request->validate([
                'payment_id' => 'required|string|max:255',
                'amount' => 'required|numeric|min:0|max:'.($invoice->total_amount + 10000), // Allow some buffer
                'tds_amount' => [
                    'required',
                    'numeric',
                    'min:0',
                    function ($attribute, $value, $fail) use ($invoice) {
                        $baseAmount = (float) $invoice->amount;
                        $tdsAmount = (float) $value;
                        $maxTdsAmount = ($baseAmount * 10) / 100; // 10% of base amount

                        if ($tdsAmount > $maxTdsAmount) {
                            $fail('The TDS amount cannot exceed 10% of the base amount (₹'.number_format($maxTdsAmount, 2).').');
                        }
                    },
                ],
                'carry_forward' => 'nullable|boolean',
                'payment_receipt' => 'nullable|file|mimes:pdf|max:10240', // 10MB max, PDF only
                'tds_certificate' => 'nullable|file|mimes:pdf|max:10240', // 10MB max, PDF only
                'notes' => 'nullable|string|max:1000',
            ]);

            $amountPaid = (float) $validated['amount'];
            $tdsAmount = (float) ($validated['tds_amount'] ?? 0);
            // Calculate TDS percentage from TDS amount and base amount
            $baseAmount = (float) $invoice->amount;
            $tdsPercentage = $baseAmount > 0 ? ($tdsAmount / $baseAmount) * 100 : 0;

            $shouldCarryForward = (bool) ($validated['carry_forward'] ?? false);
            $currentPaidAmount = (float) ($invoice->paid_amount ?? 0);
            $newPaidAmount = $currentPaidAmount + $amountPaid;
            $balanceAmount = max(0, (float) $invoice->total_amount - $newPaidAmount);

            // Determine payment status
            $paymentStatus = 'pending';
            if ($newPaidAmount >= $invoice->total_amount) {
                $paymentStatus = 'paid';
                $balanceAmount = 0;
            } elseif ($newPaidAmount > 0) {
                $paymentStatus = 'partial';
            }

            // Handle payment receipt upload
            $paymentReceiptPath = null;
            if ($request->hasFile('payment_receipt')) {
                $file = $request->file('payment_receipt');

                // Get user name for filename
                $userName = $invoice->application->user->fullname ?? 'user';
                // Sanitize filename (remove special characters, replace spaces with underscores)
                $sanitizedName = preg_replace('/[^a-zA-Z0-9_]/', '_', $userName);
                $sanitizedName = str_replace(' ', '_', $sanitizedName);

                // Generate filename: name_timestamp.pdf
                $timestamp = now()->format('YmdHis');
                $filename = strtolower($sanitizedName).'_'.$timestamp.'.pdf';

                // Create directory if it doesn't exist
                $directory = public_path('payment_receipt');
                if (! is_dir($directory)) {
                    mkdir($directory, 0755, true);
                }

                // Store file
                $file->move($directory, $filename);
                $paymentReceiptPath = 'payment_receipt/'.$filename;
            }

            // Handle TDS certificate upload
            $tdsCertificatePath = null;
            if ($request->hasFile('tds_certificate')) {
                $file = $request->file('tds_certificate');

                // Get user name for filename
                $userName = $invoice->application->user->fullname ?? 'user';
                // Sanitize filename (remove special characters, replace spaces with underscores)
                $sanitizedName = preg_replace('/[^a-zA-Z0-9_]/', '_', $userName);
                $sanitizedName = str_replace(' ', '_', $sanitizedName);

                // Generate filename: name_timestamp.pdf
                $timestamp = now()->format('YmdHis');
                $filename = strtolower($sanitizedName).'_tds_'.$timestamp.'.pdf';

                // Create directory if it doesn't exist
                $directory = public_path('tds_certificate');
                if (! is_dir($directory)) {
                    mkdir($directory, 0755, true);
                }

                // Store file
                $file->move($directory, $filename);
                $tdsCertificatePath = 'tds_certificate/'.$filename;
            }

            // Initialize forwardedAmount variable
            $forwardedAmount = 0;

            // Handle carry forward: if selected, mark as paid and forward the balance
            if ($shouldCarryForward && $balanceAmount > 0) {
                // When carry forward is selected, mark invoice as "paid" (even if partially paid)
                // because the balance is being handled via carry forward to next invoice
                $paymentStatus = 'paid';
                $forwardedAmount = $balanceAmount;
                $balanceAmount = 0; // Balance is forwarded, so it's 0 for this invoice

                // When amount is carried forward: paid_amount = amount actually paid (not total)
                // This ensures: Total = Paid + Forwarded (correct calculation)
                $updateData = [
                    'payment_status' => 'paid',
                    'status' => 'paid',
                    'paid_amount' => $newPaidAmount, // Actual amount paid
                    'balance_amount' => 0, // Balance is forwarded
                    'forwarded_amount' => $forwardedAmount, // Amount forwarded to next invoice
                    'forwarded_to_invoice_date' => null, // Will be set when next invoice is generated
                    'has_carry_forward' => true,
                    'carry_forward_amount' => $forwardedAmount, // Store the forwarded amount
                    'tds_percentage' => $tdsPercentage,
                    'tds_amount' => $tdsAmount,
                    'paid_tds_amount' => $tdsAmount,
                    'paid_at' => now('Asia/Kolkata'),
                    'paid_by' => $admin->id,
                    'manual_payment_id' => $validated['payment_id'],
                    'manual_payment_notes' => ($validated['notes'] ?? null).($validated['notes'] ? ' | ' : '').'Amount forwarded to next invoice',
                    'payment_receipt_path' => $paymentReceiptPath,
                    'tds_certificate_path' => $tdsCertificatePath,
                ];
            } else {
                // No carry forward - normal payment processing
                $updateData = [
                    'payment_status' => $paymentStatus,
                    'paid_amount' => $newPaidAmount,
                    'balance_amount' => $balanceAmount,
                    'tds_percentage' => $tdsPercentage,
                    'tds_amount' => $tdsAmount,
                    'paid_tds_amount' => $tdsAmount,
                    'paid_at' => $paymentStatus === 'paid' ? now('Asia/Kolkata') : ($invoice->paid_at ?? null),
                    'paid_by' => $paymentStatus === 'paid' ? $admin->id : ($invoice->paid_by ?? null),
                    'manual_payment_id' => $validated['payment_id'],
                    'manual_payment_notes' => $validated['notes'] ?? null,
                    'payment_receipt_path' => $paymentReceiptPath,
                    'tds_certificate_path' => $tdsCertificatePath,
                ];

                // Update status to paid only if fully paid
                if ($paymentStatus === 'paid') {
                    $updateData['status'] = 'paid';
                }
            }

            $invoice->update($updateData);

            // Create manual payment transaction record
            PaymentTransaction::create([
                'user_id' => $invoice->application->user_id,
                'application_id' => $invoice->application_id,
                'transaction_id' => 'MANUAL-'.time().'-'.strtoupper(Str::random(6)),
                'payment_status' => 'success',
                'payment_mode' => 'manual',
                'payment_id' => $validated['payment_id'],
                'amount' => $amountPaid,
                'currency' => 'INR',
                'product_info' => 'Manual payment for invoice '.$invoice->invoice_number.($amountPaid < $invoice->total_amount ? ' (Partial)' : ''),
                'response_message' => $validated['notes'] ?? 'Manual payment recorded by IX Account',
            ]);

            // Create payment verification log to avoid re-verification later (only if fully paid)
            if ($paymentStatus === 'paid') {
                $billingPeriod = $invoice->billing_period;
                $verificationType = $billingPeriod ? 'recurring' : 'initial';
                $existingVerification = null;
                if ($billingPeriod) {
                    $existingVerification = PaymentVerificationLog::where('application_id', $invoice->application_id)
                        ->where('billing_period', $billingPeriod)
                        ->first();
                }

                if (! $existingVerification) {
                    PaymentVerificationLog::create([
                        'application_id' => $invoice->application_id,
                        'verified_by' => $admin->id,
                        'verification_type' => $verificationType,
                        'billing_period' => $billingPeriod,
                        'amount' => $invoice->total_amount,
                        'amount_captured' => $amountPaid,
                        'currency' => 'INR',
                        'payment_method' => 'manual',
                        'payment_id' => $validated['payment_id'],
                        'notes' => $validated['notes'] ?? null,
                        'verified_at' => now('Asia/Kolkata'),
                    ]);
                }
            }

            // Inform user via message
            if ($shouldCarryForward && $forwardedAmount > 0) {
                $paymentMessage = "Your invoice {$invoice->invoice_number} has been marked as paid. Payment of ₹{$newPaidAmount} received. Balance of ₹{$forwardedAmount} will be carried forward to the next invoice. Payment ID: {$validated['payment_id']}";
            } else {
                $paymentMessage = $paymentStatus === 'paid'
                    ? "Your invoice {$invoice->invoice_number} has been marked as fully paid. Payment ID: {$validated['payment_id']}"
                    : "Payment of ₹{$amountPaid} has been recorded for invoice {$invoice->invoice_number}. Balance: ₹{$balanceAmount}. Payment ID: {$validated['payment_id']}";
            }

            $this->createMessageForAdmin(
                $admin->id,
                $invoice->application->user_id,
                'Payment Recorded',
                $paymentMessage
            );

            Log::info('Invoice marked as paid manually', [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'admin_id' => $admin->id,
                'payment_id' => $validated['payment_id'],
                'carry_forward' => $shouldCarryForward,
                'forwarded_amount' => $forwardedAmount,
            ]);

            return redirect()->route('admin.applications.show', $invoice->application_id)
                ->with('success', $shouldCarryForward && $forwardedAmount > 0
                    ? "Invoice marked as paid. Amount of ₹{$forwardedAmount} will be carried forward to the next invoice."
                    : 'Invoice marked as paid successfully.');
        } catch (Exception $e) {
            Log::error('Error marking invoice as paid manually: '.$e->getMessage());

            $applicationId = isset($invoice) && $invoice->application_id ? $invoice->application_id : $invoiceId;

            return redirect()->route('admin.applications.show', $applicationId)
                ->with('error', 'Unable to mark invoice as paid. Please try again.');
        }
    }

    /**
     * IX Account: Allocate partial payment across multiple invoices.
     */
    public function ixAccountAllocatePayment(Request $request)
    {
        try {
            $admin = $this->getCurrentAdmin();

            if (! $this->hasRole($admin, 'ix_account')) {
                return back()->with('error', 'You do not have permission to perform this action.');
            }

            $validated = $request->validate([
                'user_id' => 'required|exists:registrations,id',
                'total_payment_amount' => 'required|numeric|min:0.01',
                'payment_reference' => 'required|string|max:255',
                'notes' => 'nullable|string|max:1000',
                'allocations' => 'required|array|min:1',
                'allocations.*.invoice_id' => 'required|exists:invoices,id',
                'allocations.*.amount' => 'required|numeric|min:0.01',
            ]);

            $totalAllocated = array_sum(array_column($validated['allocations'], 'amount'));

            if (abs($totalAllocated - $validated['total_payment_amount']) > 0.01) {
                return back()->with('error', "Total allocated amount (₹{$totalAllocated}) does not match payment amount (₹{$validated['total_payment_amount']}).");
            }

            DB::beginTransaction();

            $user = Registration::findOrFail($validated['user_id']);
            $allocatedInvoices = [];

            foreach ($validated['allocations'] as $allocation) {
                $invoice = Invoice::with('application')->findOrFail($allocation['invoice_id']);

                // Verify invoice belongs to user
                if ($invoice->application->user_id != $validated['user_id']) {
                    DB::rollBack();

                    return back()->with('error', "Invoice {$invoice->invoice_number} does not belong to this user.");
                }

                $allocatedAmount = (float) $allocation['amount'];
                $currentPaidAmount = (float) ($invoice->paid_amount ?? 0);
                $newPaidAmount = $currentPaidAmount + $allocatedAmount;
                $balanceAmount = max(0, (float) $invoice->total_amount - $newPaidAmount);

                // Determine payment status
                $paymentStatus = 'pending';
                if ($newPaidAmount >= $invoice->total_amount) {
                    $paymentStatus = 'paid';
                    $balanceAmount = 0;
                } elseif ($newPaidAmount > 0) {
                    $paymentStatus = 'partial';
                }

                // Update invoice
                $invoice->update([
                    'paid_amount' => $newPaidAmount,
                    'balance_amount' => $balanceAmount,
                    'payment_status' => $paymentStatus,
                    'status' => $paymentStatus === 'paid' ? 'paid' : $invoice->status,
                    'paid_at' => $paymentStatus === 'paid' ? now('Asia/Kolkata') : $invoice->paid_at,
                    'paid_by' => $paymentStatus === 'paid' ? $admin->id : $invoice->paid_by,
                    'manual_payment_id' => $validated['payment_reference'],
                    'manual_payment_notes' => $validated['notes'] ?? null,
                ]);

                // Create payment allocation record
                PaymentAllocation::create([
                    'invoice_id' => $invoice->id,
                    'application_id' => $invoice->application_id,
                    'user_id' => $validated['user_id'],
                    'allocated_amount' => $allocatedAmount,
                    'payment_reference' => $validated['payment_reference'],
                    'notes' => $validated['notes'] ?? null,
                    'allocated_by' => $admin->id,
                ]);

                $allocatedInvoices[] = $invoice->invoice_number;

                // Create payment transaction record
                PaymentTransaction::create([
                    'user_id' => $validated['user_id'],
                    'application_id' => $invoice->application_id,
                    'transaction_id' => 'ALLOC-'.time().'-'.strtoupper(Str::random(6)),
                    'payment_status' => 'success',
                    'payment_mode' => 'manual',
                    'payment_id' => $validated['payment_reference'],
                    'amount' => $allocatedAmount,
                    'currency' => 'INR',
                    'product_info' => 'Partial payment allocation for invoice '.$invoice->invoice_number,
                    'response_message' => $validated['notes'] ?? 'Payment allocated by IX Account',
                ]);

                // If invoice is now fully paid, create payment verification log
                if ($paymentStatus === 'paid' && $invoice->billing_period) {
                    $existingVerification = PaymentVerificationLog::where('application_id', $invoice->application_id)
                        ->where('billing_period', $invoice->billing_period)
                        ->first();

                    if (! $existingVerification) {
                        PaymentVerificationLog::create([
                            'application_id' => $invoice->application_id,
                            'verified_by' => $admin->id,
                            'verification_type' => 'recurring',
                            'billing_period' => $invoice->billing_period,
                            'amount' => $invoice->total_amount,
                            'currency' => 'INR',
                            'payment_method' => 'manual',
                            'notes' => $validated['notes'] ?? null,
                            'verified_at' => now('Asia/Kolkata'),
                        ]);
                    }
                }
            }

            DB::commit();

            // Inform user via message
            $invoiceList = implode(', ', $allocatedInvoices);
            $this->createMessageForAdmin(
                $admin->id,
                $validated['user_id'],
                'Payment Allocated',
                "Payment of ₹{$validated['total_payment_amount']} has been allocated to invoices: {$invoiceList}. Payment Reference: {$validated['payment_reference']}"
            );

            Log::info('Payment allocated successfully', [
                'user_id' => $validated['user_id'],
                'total_amount' => $validated['total_payment_amount'],
                'invoices' => $allocatedInvoices,
                'admin_id' => $admin->id,
            ]);

            return back()->with('success', "Payment of ₹{$validated['total_payment_amount']} allocated successfully to ".count($allocatedInvoices).' invoice(s).');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error allocating payment: '.$e->getMessage());

            return back()->with('error', 'Unable to allocate payment. Please try again.');
        }
    }

    /**
     * IX Account: Show payment allocation form.
     */
    public function showPaymentAllocationForm(Request $request)
    {
        try {
            $admin = $this->getCurrentAdmin();

            if (! $this->hasRole($admin, 'ix_account')) {
                return redirect()->route('admin.dashboard')
                    ->with('error', 'You do not have permission to access this page.');
            }

            return view('admin.payment-allocation.form', compact('admin'));
        } catch (Exception $e) {
            Log::error('Error loading payment allocation form: '.$e->getMessage());

            return redirect()->route('admin.dashboard')
                ->with('error', 'Unable to load payment allocation form. Please try again.');
        }
    }

    /**
     * IX Account: Search users for payment allocation (JSON API).
     */
    public function searchUsersForAllocation(Request $request)
    {
        try {
            $admin = $this->getCurrentAdmin();

            if (! $this->hasRole($admin, 'ix_account')) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $query = $request->input('q', '');

            if (strlen($query) < 2) {
                return response()->json(['users' => []]);
            }

            $users = Registration::where(function ($q) use ($query) {
                $q->where('fullname', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%")
                    ->orWhere('mobile', 'like', "%{$query}%")
                    ->orWhere('registrationid', 'like', "%{$query}%");
            })
                ->limit(20)
                ->get()
                ->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->fullname,
                        'email' => $user->email,
                        'mobile' => $user->mobile,
                        'registration_id' => $user->registrationid,
                    ];
                });

            return response()->json(['users' => $users]);
        } catch (Exception $e) {
            Log::error('Error searching users for allocation: '.$e->getMessage());

            return response()->json(['error' => 'Unable to search users'], 500);
        }
    }

    /**
     * IX Account: Get user invoices for payment allocation.
     */
    public function getUserInvoicesForAllocation(Request $request, $userId)
    {
        try {
            $admin = $this->getCurrentAdmin();

            if (! $this->hasRole($admin, 'ix_account')) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $user = Registration::findOrFail($userId);

            $invoices = Invoice::whereHas('application', function ($q) use ($userId) {
                $q->where('user_id', $userId)
                    ->where('application_type', 'IX');
            })
                ->activeForTotals()
                ->whereIn('payment_status', ['pending', 'partial'])
                ->orderBy('invoice_date', 'desc')
                ->get()
                ->map(function ($invoice) {
                    return [
                        'id' => $invoice->id,
                        'invoice_number' => $invoice->invoice_number,
                        'application_id' => $invoice->application->application_id,
                        'total_amount' => (float) $invoice->total_amount,
                        'paid_amount' => (float) ($invoice->paid_amount ?? 0),
                        'balance_amount' => (float) ($invoice->balance_amount ?? $invoice->total_amount),
                        'due_date' => $invoice->due_date->format('Y-m-d'),
                        'billing_period' => $invoice->billing_period,
                    ];
                });

            return response()->json(['invoices' => $invoices]);
        } catch (Exception $e) {
            Log::error('Error fetching user invoices: '.$e->getMessage());

            return response()->json(['error' => 'Unable to fetch invoices'], 500);
        }
    }

    /**
     * IX Account: Create a one-time Reactivation Fee invoice for a disconnected application.
     * This invoice is not tied to a billing cycle (billing_period is NULL).
     */
    public function createReactivationInvoiceForApplication(Application $application, Admin $admin, float $feeAmount): Invoice
    {
        $invoiceNumber = 'REACT-'.now('Asia/Kolkata')->format('Ymd').'-'.strtoupper(\Illuminate\Support\Str::random(6));
        $invoiceDate = now('Asia/Kolkata');
        $dueDate = now('Asia/Kolkata')->addDays(7)->format('Y-m-d');

        $billingStartDate = $invoiceDate->format('Y-m-d');
        $billingEndDate = $invoiceDate->format('Y-m-d');

        $setting = \App\Models\ReactivationSetting::current();
        $gstPercentage = (float) ($setting->gst_percentage ?? 18);

        $amount = round($feeAmount, 2);
        $gstAmount = round(($amount * $gstPercentage) / 100, 2);
        $tdsPercentage = 0;
        $tdsAmount = 0;
        $finalTotalAmount = round($amount + $gstAmount, 2);

        $lineItemsData = [
            [
                'description' => 'IX Reactivation Charges',
                'quantity' => 1,
                'rate' => $amount,
                'amount' => $amount,
                'show_period' => false,
            ],
        ];

        // Temporary invoice object (not saved) for e-invoice call
        $tempInvoice = new Invoice([
            'application_id' => $application->id,
            'invoice_number' => $invoiceNumber,
            'invoice_date' => $invoiceDate->format('Y-m-d'),
            'due_date' => $dueDate,
            'billing_period' => null,
            'billing_start_date' => $billingStartDate,
            'billing_end_date' => $billingEndDate,
            'invoice_purpose' => 'reactivation',
            'line_items' => $lineItemsData,
            'amount' => $amount,
            'gst_amount' => $gstAmount,
            'tds_percentage' => $tdsPercentage,
            'tds_amount' => $tdsAmount,
            'total_amount' => $finalTotalAmount,
            'paid_amount' => 0,
            'balance_amount' => $finalTotalAmount,
            'payment_status' => 'pending',
            'carry_forward_amount' => 0,
            'has_carry_forward' => false,
            'currency' => 'INR',
            'status' => 'pending',
            'payu_payment_link' => null,
            'generated_by' => $admin->id,
        ]);

        // E-invoice call gating: only call when buyer GSTIN is present + valid + active.
        $kycDetails = $application->kyc_details ?? [];
        $buyerGstin = $kycDetails['gstin'] ?? $application->gstin ?? '';
        $isGstinInactive = false;
        $einvoiceData = null;

        $buyerGstin = strtoupper(trim($buyerGstin));
        $isGstinValid = (bool) preg_match('/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/', $buyerGstin);

        if ($buyerGstin && $isGstinValid) {
            $gstVerification = \App\Models\GstVerification::where('gstin', $buyerGstin)
                ->where('user_id', $application->user_id)
                ->latest()
                ->first();

            if ($gstVerification) {
                $gstStatus = strtolower(trim($gstVerification->status ?? ''));
                if (in_array($gstStatus, ['inactive', 'cancelled', 'canceled'], true)) {
                    $isGstinInactive = true;
                }
            }
        }

        if ($buyerGstin && $isGstinValid && ! $isGstinInactive) {
            try {
                $einvoiceData = $this->callEinvoiceApi($application, $tempInvoice);

                $isEinvoiceSuccess = false;
                if ($einvoiceData && is_array($einvoiceData)) {
                    $status = $einvoiceData['Status'] ?? $einvoiceData['status'] ?? '';
                    $irn = $einvoiceData['Irn'] ?? '';
                    $errorCode = $einvoiceData['ErrorCode'] ?? '';

                    if (($status === '1' || $status === 1) && $irn) {
                        $isEinvoiceSuccess = true;
                    }

                    if ($errorCode === '2150' && ($einvoiceData['Irn'] ?? null)) {
                        $isEinvoiceSuccess = true;
                    }
                }

                if (! $isEinvoiceSuccess) {
                    Log::warning('Reactivation invoice: e-invoice API failed, generating invoice without IRN', [
                        'application_id' => $application->id,
                        'invoice_number' => $invoiceNumber,
                        'buyer_gstin' => $buyerGstin,
                        'einvoice_response' => $einvoiceData,
                    ]);
                    $einvoiceData = null;
                }
            } catch (\Throwable $e) {
                Log::warning('Reactivation invoice: e-invoice API exception, generating invoice without IRN: '.$e->getMessage(), [
                    'application_id' => $application->id,
                    'invoice_number' => $invoiceNumber,
                    'buyer_gstin' => $buyerGstin,
                ]);
                $einvoiceData = null;
            }
        }

        // Create PaymentTransaction and PayU payment data
        $payuService = new \App\Services\PayuService;
        $transactionId = 'INV-'.time().'-'.strtoupper(\Illuminate\Support\Str::random(8));

        \App\Models\PaymentTransaction::create([
            'user_id' => $application->user_id,
            'application_id' => $application->id,
            'transaction_id' => $transactionId,
            'payment_status' => 'pending',
            'payment_mode' => config('services.payu.mode', 'test'),
            'amount' => $finalTotalAmount,
            'currency' => 'INR',
            'product_info' => 'NIXI IX Reactivation Fee - '.$invoiceNumber,
            'response_message' => 'Invoice payment pending',
        ]);

        $paymentData = $payuService->preparePaymentData([
            'transaction_id' => $transactionId,
            'amount' => $finalTotalAmount,
            'product_info' => 'NIXI IX Reactivation Fee - '.$invoiceNumber,
            'firstname' => $application->user->fullname,
            'email' => $application->user->email,
            'phone' => $application->user->mobile,
            'success_url' => url(route('user.applications.ix.payment-success', [], false)),
            'failure_url' => url(route('user.applications.ix.payment-failure', [], false)),
            'udf1' => $application->application_id,
            'udf3' => $invoiceNumber,
        ]);

        $buyerStateCode = $this->extractStateCodeFromGstin($buyerGstin);
        $sellerStateCode = $application->seller_state_code ?? $buyerStateCode;
        $nixiCredsReactivation = $this->getNixiLocationCredentials($sellerStateCode);
        $sellerGstinReactivation = $nixiCredsReactivation['supplier_gstin'] ?? null;

        $invoice = Invoice::create([
            'application_id' => $application->id,
            'invoice_number' => $invoiceNumber,
            'invoice_date' => $invoiceDate->format('Y-m-d'),
            'due_date' => $dueDate,
            'billing_period' => null,
            'billing_start_date' => $billingStartDate,
            'billing_end_date' => $billingEndDate,
            'invoice_purpose' => 'reactivation',
            'line_items' => $lineItemsData,
            'amount' => $amount,
            'gst_amount' => $gstAmount,
            'tds_percentage' => $tdsPercentage,
            'tds_amount' => $tdsAmount,
            'total_amount' => $finalTotalAmount,
            'paid_amount' => 0,
            'balance_amount' => $finalTotalAmount,
            'payment_status' => 'pending',
            'carry_forward_amount' => 0,
            'has_carry_forward' => false,
            'currency' => 'INR',
            'status' => 'pending',
            'payu_payment_link' => json_encode($paymentData),
            'generated_by' => $admin->id,
            'einvoice_irn' => is_array($einvoiceData) ? ($einvoiceData['Irn'] ?? null) : null,
            'einvoice_ack_no' => is_array($einvoiceData) ? ($einvoiceData['AckNo'] ?? null) : null,
            'einvoice_ack_date' => is_array($einvoiceData) ? ($einvoiceData['AckDt'] ?? null) : null,
            'einvoice_signed_data' => is_array($einvoiceData) ? ($einvoiceData['SignedInvoice'] ?? null) : null,
            'einvoice_status' => is_array($einvoiceData) ? ($einvoiceData['Status'] ?? null) : null,
            'einvoice_error_code' => is_array($einvoiceData) ? ($einvoiceData['ErrorCode'] ?? null) : null,
            'einvoice_error_message' => is_array($einvoiceData) ? ($einvoiceData['ErrorMessage'] ?? null) : null,
            'einvoice_response' => $einvoiceData,
            'seller_gstin' => $sellerGstinReactivation,
        ]);

        try {
            $invoicePdf = $this->generateIxInvoicePdf($application, $invoice);
            $invoicePdfPath = 'applications/'.$application->user_id.'/ix/'.$invoiceNumber.'_invoice.pdf';
            Storage::disk('public')->put($invoicePdfPath, $invoicePdf->output());
            $invoice->update(['pdf_path' => $invoicePdfPath]);
        } catch (\Exception $e) {
            Log::error('Error generating reactivation invoice PDF: '.$e->getMessage());
        }

        return $invoice;
    }

    /**
     * Generate IX Invoice PDF or Credit Note PDF.
     *
     * @param  bool  $isCreditNote  If true, heading shows "Credit Note" and number label "Credit Note No"
     */
    private function generateIxInvoicePdf(Application $application, ?Invoice $invoice = null, bool $isCreditNote = false)
    {
        $data = $application->application_data ?? [];
        $user = $application->user;

        // Check if this is first application or subsequent
        $isFirstApplication = Application::where('user_id', $user->id)
            ->where('application_type', 'IX')
            ->where('id', '<', $application->id)
            ->doesntExist();

        // Get buyer details
        $buyerDetails = [];
        $gstVerification = null;
        $applicationGstin = $data['gstin'] ?? null;

        // Priority 1: Check if application has gst_verification_id
        if ($application->gst_verification_id) {
            $gstVerification = GstVerification::find($application->gst_verification_id);
        }

        // Priority 2: If GSTIN is in application data, try to find matching GST verification
        if (! $gstVerification && $applicationGstin) {
            $gstVerification = GstVerification::where('user_id', $user->id)
                ->where('gstin', strtoupper($applicationGstin))
                ->where('is_verified', true)
                ->latest()
                ->first();
        }

        // Priority 3: For first application, check KYC
        if (! $gstVerification && $isFirstApplication) {
            $kyc = \App\Models\UserKycProfile::where('user_id', $user->id)
                ->where('status', 'completed')
                ->first();

            if ($kyc && $kyc->gst_verification_id) {
                $gstVerification = GstVerification::find($kyc->gst_verification_id);
            }
        }

        // Priority 4: Get latest verified GST for this user (fallback)
        if (! $gstVerification) {
            $gstVerification = GstVerification::where('user_id', $user->id)
                ->where('is_verified', true)
                ->latest()
                ->first();
        }

        // Build buyer details
        if ($gstVerification) {
            $buyerDetails = [
                'company_name' => $gstVerification->legal_name ?? $gstVerification->trade_name ?? $user->fullname,
                'pan' => $gstVerification->pan ?? $user->pancardno,
                'gstin' => $gstVerification->gstin,
                'state' => $gstVerification->state,
            ];

            // Get billing address from GST API response
            if ($gstVerification->verification_data) {
                $verificationData = is_string($gstVerification->verification_data)
                    ? json_decode($gstVerification->verification_data, true)
                    : $gstVerification->verification_data;

                if (isset($verificationData['result']['source_output']['principal_place_of_business_fields']['principal_place_of_business_address'])) {
                    $address = $verificationData['result']['source_output']['principal_place_of_business_fields']['principal_place_of_business_address'];
                    $buyerDetails['address'] = trim(($address['door_number'] ?? '').' '.($address['building_name'] ?? '').' '.($address['street'] ?? '').' '.($address['location'] ?? '').' '.($address['dst'] ?? '').' '.($address['city'] ?? '').' '.($address['state_name'] ?? '').' '.($address['pincode'] ?? ''));
                    $buyerDetails['state_name'] = $address['state_name'] ?? $gstVerification->state;
                } else {
                    $buyerDetails['address'] = $gstVerification->primary_address ?? '';
                }
            } else {
                $buyerDetails['address'] = $gstVerification->primary_address ?? '';
            }

            // Get phone and email from user
            $buyerDetails['phone'] = $user->mobile ?? '';
            $buyerDetails['email'] = $user->email ?? '';
        } else {
            // Fallback: Try to get GSTIN from GST verification table even if not verified
            $fallbackGstin = $applicationGstin;
            if (! $fallbackGstin) {
                // Try to get any GST verification for this user (even if not verified)
                $anyGstVerification = GstVerification::where('user_id', $user->id)
                    ->latest()
                    ->first();
                if ($anyGstVerification) {
                    $fallbackGstin = $anyGstVerification->gstin;
                }
            }

            // Fallback to user data
            $buyerDetails = [
                'company_name' => $user->fullname,
                'pan' => $user->pancardno,
                'gstin' => $fallbackGstin ?? 'N/A',
                'address' => '',
                'phone' => $user->mobile ?? '',
                'email' => $user->email ?? '',
                'state' => null,
                'state_name' => null,
            ];
        }

        // Get Attn (Authorized Representative Name)
        $attnName = null;

        // IX application invoice (membership fee) only: use contact_name from user_kyc_profiles
        if ($invoice && $invoice->invoice_purpose === 'application') {
            $kycProfileAttn = \App\Models\UserKycProfile::where('user_id', $user->id)
                ->where('status', 'completed')
                ->latest('id')
                ->first();
            if ($kycProfileAttn && ! empty(trim((string) $kycProfileAttn->contact_name))) {
                $attnName = trim($kycProfileAttn->contact_name);
            }
        }

        if (! $attnName && $isFirstApplication) {
            // First application: Get from KYC
            $kyc = \App\Models\UserKycProfile::where('user_id', $user->id)
                ->where('status', 'completed')
                ->first();
            if ($kyc && $kyc->contact_name) {
                $attnName = $kyc->contact_name;
            }
        } elseif (! $attnName) {
            // Subsequent application: Get from form (representative name)
            if (isset($data['representative']['name'])) {
                $attnName = $data['representative']['name'];
            }
        }

        // Fallback to user name if no representative found
        if (! $attnName) {
            $attnName = $buyerDetails['company_name'] ?? $user->fullname;
        }

        // Get place of supply from IX location
        $placeOfSupply = null;
        if (isset($data['location']['id'])) {
            $location = IxLocation::find($data['location']['id']);
            if ($location) {
                $placeOfSupply = $location->state;
            }
        }

        // If no location in data, try to get from application
        if (! $placeOfSupply && isset($data['location']['state'])) {
            $placeOfSupply = $data['location']['state'];
        }

        // Fallback to buyer state
        if (! $placeOfSupply) {
            $placeOfSupply = $buyerDetails['state_name'] ?? $buyerDetails['state'] ?? 'N/A';
        }

        // Get application pricing for fallback
        $applicationPricing = IxApplicationPricing::getActive();

        // Use invoice number from invoice record if provided
        // For credit notes: use invoice number + "C" (e.g., NIXIEX2526-2292C)
        if ($isCreditNote && $invoice) {
            $invoiceNumber = $invoice->invoice_number.'C';
            // Use credit note date from API response (DocDate or AckDate)
            if ($invoice->credit_note_doc_date) {
                $invoiceDate = $invoice->credit_note_doc_date->format('d/m/Y');
            } elseif ($invoice->credit_note_ack_date) {
                $invoiceDate = $invoice->credit_note_ack_date->format('d/m/Y');
            } else {
                // Fallback to current date if API date not available
                $invoiceDate = now('Asia/Kolkata')->format('d/m/Y');
            }
            // Credit notes don't have due dates
            $dueDate = null;
        } else {
            $invoiceNumber = $invoice ? $invoice->invoice_number : 'NIXI-IX-'.date('y').'-'.(date('y') + 1).'/'.str_pad($application->id, 4, '0', STR_PAD_LEFT);
            $invoiceDate = $invoice ? $invoice->invoice_date->format('d/m/Y') : now('Asia/Kolkata')->format('d/m/Y');
            $dueDate = $invoice ? $invoice->due_date->format('d/m/Y') : now('Asia/Kolkata')->addDays(28)->format('d/m/Y');
        }

        // Use invoice's stored amounts directly (they are already correct)
        // Only recalculate for very old invoices that might have incorrect values
        $invoiceForView = $invoice ? clone $invoice : null;
        if ($invoiceForView && $invoice->created_at && $invoice->created_at->lt(now()->subMonths(3))) {
            // Only recalculate for invoices older than 3 months (legacy invoices)
            $recalculatedAmounts = $this->recalculateInvoiceAmounts($application, $invoice);
            $invoiceForView->amount = $recalculatedAmounts['amount'];
            $invoiceForView->gst_amount = $recalculatedAmounts['gst_amount'];
            $invoiceForView->total_amount = $recalculatedAmounts['total_amount'];
        }
        // For new invoices, use stored values directly (they are already correct)

        // Get supplier (NIXI) details based on seller_state_code if set, otherwise buyer state code (same logic as e-invoice API)
        $supplierGstin = '07AABCN9308A1ZT'; // Default to Delhi
        $supplierAddress = 'National Internet Exchange of India, B-901, 9th Floor Tower B, World Trade Centre Nauroji Nagar, New Delhi, Delhi, 110001 India'; // Default to Delhi
        $supplierPan = 'AABCN9308A'; // Default PAN
        $buyerGstin = $buyerDetails['gstin'] ?? $applicationGstin ?? '';
        if (! empty($buyerGstin)) {
            $buyerStateCode = $this->extractStateCodeFromGstin($buyerGstin);
            // Use manually assigned seller_state_code if set, otherwise use buyer state code
            $sellerStateCode = $application->seller_state_code ?? $buyerStateCode;
            $nixiCredentials = $this->getNixiLocationCredentials($sellerStateCode);
            $supplierGstin = $nixiCredentials['supplier_gstin'];

            // Extract PAN from GSTIN (positions 2-11, i.e., characters 1-10 after state code)
            // GSTIN format: [2-digit state code][10-char PAN][1-char entity number][1-char Z][1-char check digit]
            if (strlen($supplierGstin) >= 12) {
                $supplierPan = substr($supplierGstin, 2, 10); // Extract PAN from GSTIN
            }

            // Construct full supplier address
            $stateName = $this->getStateNameFromCode($nixiCredentials['supplier_state_code']);
            $addressParts = [
                'National Internet Exchange of India',
                $nixiCredentials['supplier_address'],
            ];

            // Add location, state, and pincode
            if ($stateName) {
                $addressParts[] = $nixiCredentials['supplier_location'].', '.$stateName;
            } else {
                $addressParts[] = $nixiCredentials['supplier_location'];
            }
            $addressParts[] = $nixiCredentials['supplier_pincode'].' India';

            $supplierAddress = implode(', ', $addressParts);
        }

        $pdf = Pdf::loadView('user.applications.ix.pdf.invoice', [
            'application' => $application,
            'user' => $user,
            'data' => $data,
            'buyerDetails' => $buyerDetails,
            'placeOfSupply' => $placeOfSupply,
            'attnName' => $attnName,
            'invoiceNumber' => $invoiceNumber,
            'invoiceDate' => $invoiceDate,
            'dueDate' => $dueDate,
            'invoice' => $invoiceForView ?? $invoice, // Use recalculated amounts
            'gstVerification' => $gstVerification,
            'supplierGstin' => $supplierGstin, // NIXI supplier GSTIN based on buyer location
            'supplierPan' => $supplierPan, // NIXI supplier PAN extracted from GSTIN
            'supplierAddress' => $supplierAddress, // NIXI supplier address based on buyer location
            'isCreditNote' => $isCreditNote,
        ])->setPaper('a4', 'portrait')
            ->setOption('margin-top', 6)
            ->setOption('margin-bottom', 6)
            ->setOption('margin-left', 6)
            ->setOption('margin-right', 6)
            ->setOption('enable-local-file-access', true);

        return $pdf;
    }

    /**
     * Generate IX invoice PDF, save to storage, and update invoice pdf_path.
     * Used for application-fee invoices and for ensuring PDF exists before download.
     */
    public function generateAndSaveIxInvoicePdf(Application $application, Invoice $invoice): ?string
    {
        try {
            $invoicePdf = $this->generateIxInvoicePdf($application, $invoice);
            $path = 'applications/'.$application->user_id.'/ix/'.str_replace(['/', '\\'], '-', $invoice->invoice_number).'_invoice.pdf';
            Storage::disk('public')->put($path, $invoicePdf->output());
            $invoice->update(['pdf_path' => $path]);
            Log::info('IX invoice PDF generated and saved', ['invoice_id' => $invoice->id, 'path' => $path]);

            return $path;
        } catch (Exception $e) {
            Log::error('Error generating/saving IX invoice PDF: '.$e->getMessage(), ['invoice_id' => $invoice->id]);

            return null;
        }
    }

    /**
     * Ensure invoice has a PDF on disk; generate and save if missing (do not regenerate if cancelled or has credit note).
     */
    public function ensureInvoicePdfExists(Application $application, Invoice $invoice): void
    {
        if ($invoice->isCancelledOrHasCreditNote()) {
            return;
        }
        if ($invoice->pdf_path && Storage::disk('public')->exists($invoice->pdf_path)) {
            return;
        }
        $this->generateAndSaveIxInvoicePdf($application, $invoice);
    }

    /**
     * Extract credit note fields from credit_note_api_response if they are null.
     */
    private function extractCreditNoteFieldsFromApiResponse(Invoice $invoice): array
    {
        $updateData = [];

        if (! $invoice->credit_note_api_response) {
            return $updateData;
        }

        $response = is_array($invoice->credit_note_api_response)
            ? $invoice->credit_note_api_response
            : json_decode($invoice->credit_note_api_response, true);

        if (! is_array($response)) {
            return $updateData;
        }

        // Extract Irn if credit_note_irn is null
        if (empty($invoice->credit_note_irn) && isset($response['Irn'])) {
            $updateData['credit_note_irn'] = $response['Irn'];
        }

        // Extract AckNo if credit_note_ack_no is null
        if (empty($invoice->credit_note_ack_no) && isset($response['AckNo'])) {
            $updateData['credit_note_ack_no'] = (string) $response['AckNo'];
        }

        // Extract AckDate if credit_note_ack_date is null
        if (empty($invoice->credit_note_ack_date) && isset($response['AckDate'])) {
            try {
                $updateData['credit_note_ack_date'] = \Carbon\Carbon::parse($response['AckDate'])->setTimezone('Asia/Kolkata');
            } catch (\Exception $e) {
                Log::warning('Failed to parse credit note AckDate from API response: '.$response['AckDate']);
            }
        }

        // Extract DocDate if credit_note_doc_date is null
        if (empty($invoice->credit_note_doc_date) && isset($response['DocDate'])) {
            try {
                // DocDate format can be "13/01/2026" (DD/MM/YYYY) or ISO format
                if (strpos($response['DocDate'], '/') !== false) {
                    $updateData['credit_note_doc_date'] = \Carbon\Carbon::createFromFormat('d/m/Y', $response['DocDate']);
                } else {
                    $updateData['credit_note_doc_date'] = \Carbon\Carbon::parse($response['DocDate']);
                }
            } catch (\Exception $e) {
                Log::warning('Failed to parse credit note DocDate from API response: '.$response['DocDate']);
            }
        }

        // Extract Status if credit_note_status is null
        if (empty($invoice->credit_note_status) && isset($response['Status'])) {
            $updateData['credit_note_status'] = $response['Status'];
        }

        return $updateData;
    }

    /**
     * Ensure credit note PDF exists. If credit_note_pdf_path is set but file doesn't exist, generate and save it.
     */
    public function ensureCreditNotePdfExists(Application $application, Invoice $invoice): void
    {
        if (! $invoice->hasCreditNote()) {
            return; // No credit note exists
        }

        // Extract credit note fields from API response if they are null
        $updateData = $this->extractCreditNoteFieldsFromApiResponse($invoice);
        if (! empty($updateData)) {
            $invoice->update($updateData);
            Log::info('Extracted credit note fields from API response', [
                'invoice_id' => $invoice->id,
                'fields' => array_keys($updateData),
            ]);
        }

        // If PDF path exists and file is found, nothing to do
        if ($invoice->credit_note_pdf_path && Storage::disk('public')->exists($invoice->credit_note_pdf_path)) {
            return;
        }

        // Credit note exists but PDF is missing - generate it
        try {
            // Use existing path if set, otherwise generate standard path
            $creditNoteNumber = $invoice->invoice_number.'C';
            $creditNotePdfPath = $invoice->credit_note_pdf_path ?: 'applications/'.$application->user_id.'/ix/'.$creditNoteNumber.'.pdf';

            $creditNotePdf = $this->generateIxInvoicePdf($application, $invoice, true);
            Storage::disk('public')->put($creditNotePdfPath, $creditNotePdf->output());

            // Update invoice with PDF path if it wasn't set
            if (! $invoice->credit_note_pdf_path) {
                $invoice->update(['credit_note_pdf_path' => $creditNotePdfPath]);
            }

            Log::info("Credit note PDF generated on-demand: {$creditNotePdfPath} for invoice {$invoice->id}");
        } catch (Exception $e) {
            Log::error('Error generating credit note PDF on-demand: '.$e->getMessage(), [
                'invoice_id' => $invoice->id,
                'application_id' => $application->id,
            ]);
            throw $e; // Re-throw so caller can handle
        }
    }

    /**
     * Recalculate invoice amounts using latest calculation logic.
     * This ensures old invoices show correct amounts after fixes.
     */
    private function recalculateInvoiceAmounts(Application $application, ?Invoice $invoice = null): array
    {
        $applicationData = $application->application_data ?? [];

        // Get port amount based on billing cycle
        $billingPlan = $application->billing_cycle ?? ($applicationData['port_selection']['billing_plan'] ?? 'monthly');

        // Map billing plan to pricing plan
        $pricingPlan = match ($billingPlan) {
            'annual', 'arc' => 'arc',
            'monthly', 'mrc' => 'mrc',
            'quarterly' => 'quarterly',
            default => 'mrc',
        };

        // Determine port capacity (same logic as ixAccountGenerateInvoice)
        $portCapacity = null;

        // Check for pending plan change request
        $pendingPlanChange = \App\Models\PlanChangeRequest::where('application_id', $application->id)
            ->where('status', 'pending')
            ->latest()
            ->first();

        if ($pendingPlanChange) {
            $portCapacity = $pendingPlanChange->current_port_capacity ?? $application->assigned_port_capacity ?? ($applicationData['port_selection']['capacity'] ?? null);
            Log::info("Pending plan change found for application {$application->id}, using current capacity: {$portCapacity}");
        } else {
            // Check for approved plan change that has taken effect
            $effectivePlanChange = \App\Models\PlanChangeRequest::where('application_id', $application->id)
                ->where('status', 'approved')
                ->where(function ($query) {
                    $query->whereNull('effective_from')
                        ->orWhere('effective_from', '<=', now('Asia/Kolkata'));
                })
                ->latest('effective_from')
                ->first();

            if ($effectivePlanChange && $effectivePlanChange->effective_from && $effectivePlanChange->effective_from <= now('Asia/Kolkata')) {
                // Use new capacity if effective_from has passed
                $portCapacity = $effectivePlanChange->new_port_capacity ?? $application->assigned_port_capacity ?? ($applicationData['port_selection']['capacity'] ?? null);
                Log::info("Effective plan change found for application {$application->id}, using new capacity: {$portCapacity} (effective from {$effectivePlanChange->effective_from})");
            } else {
                // No plan change or not yet effective, use assigned capacity
                $portCapacity = $application->assigned_port_capacity ?? ($applicationData['port_selection']['capacity'] ?? null);
            }
        }

        // Get pricing for the port capacity
        $location = null;
        if (isset($applicationData['location']['id'])) {
            $location = IxLocation::find($applicationData['location']['id']);
        }

        $portAmount = 0;
        if ($location && $portCapacity) {
            // Normalize port capacity format (same logic as ixAccountGenerateInvoice)
            $normalizedCapacity = trim($portCapacity);
            $normalizedCapacity = preg_replace('/\s+/', '', $normalizedCapacity);

            if (stripos($normalizedCapacity, 'Gbps') !== false || stripos($normalizedCapacity, 'gbps') !== false) {
                $normalizedCapacity = str_ireplace(['Gbps', 'gbps', 'GBPS'], 'Gig', $normalizedCapacity);
            }

            if (! preg_match('/(Gig|M)$/i', $normalizedCapacity)) {
                if (preg_match('/^\d+$/', $normalizedCapacity)) {
                    $normalizedCapacity = $normalizedCapacity.'Gig';
                }
            }

            // Get pricing
            $pricing = \App\Models\IxPortPricing::active()
                ->where('node_type', $location->node_type)
                ->where('port_capacity', $normalizedCapacity)
                ->first();

            // Try variations if exact match not found
            if (! $pricing) {
                $variations = [
                    trim($portCapacity),
                    str_replace(' ', '', trim($portCapacity)),
                    preg_replace('/\s+/', '', trim($portCapacity)),
                    str_replace(['Gbps', 'gbps', 'GBPS'], 'Gig', str_replace(' ', '', trim($portCapacity))),
                    str_replace(['Gbps', 'gbps'], 'Gig', trim($portCapacity)),
                    str_replace([' Gbps', 'Gbps', 'gbps'], 'Gig', trim($portCapacity)),
                ];

                foreach (array_unique($variations) as $variation) {
                    if (empty($variation)) {
                        continue;
                    }
                    $pricing = \App\Models\IxPortPricing::active()
                        ->where('node_type', $location->node_type)
                        ->where('port_capacity', $variation)
                        ->first();
                    if ($pricing) {
                        break;
                    }
                }
            }

            if ($pricing) {
                $portAmount = (float) $pricing->getAmountForPlan($pricingPlan);
            }
        }

        // Determine GST type (IGST vs CGST+SGST) by buyer GST state code vs seller (supplier) state code, not place of supply
        $kycDetails = $application->kyc_details ?? [];
        $buyerGstin = $kycDetails['gstin'] ?? $application->gstin ?? '';
        $buyerStateCode = $this->extractStateCodeFromGstin($buyerGstin);
        $sellerStateCode = $application->seller_state_code ?? $buyerStateCode;
        $nixiCredentials = $this->getNixiLocationCredentials($sellerStateCode);
        $supplierStateCode = $nixiCredentials['supplier_state_code'] ?? '07';
        $normalizedBuyer = trim((string) $buyerStateCode);
        $normalizedSupplier = trim((string) $supplierStateCode);
        $isSameState = ($normalizedBuyer === $normalizedSupplier && $normalizedBuyer !== '' && $normalizedSupplier !== '');

        if ($isSameState) {
            $cgstAmount = round(($portAmount * 9) / 100, 2);
            $sgstAmount = round(($portAmount * 9) / 100, 2);
            $gstAmount = $cgstAmount + $sgstAmount;
        } else {
            $gstAmount = round(($portAmount * 18) / 100, 2);
        }

        $amount = $portAmount;
        $baseTotalAmount = round($amount + $gstAmount, 2);

        // Include carry forward amount if invoice has it
        $carryForwardAmount = 0;
        if ($invoice && $invoice->carry_forward_amount) {
            $carryForwardAmount = (float) $invoice->carry_forward_amount;
        }

        // Final total includes carry forward (carry forward already has GST, so no GST added)
        $totalAmount = round($baseTotalAmount + $carryForwardAmount, 2);

        return [
            'amount' => $amount,
            'gst_amount' => $gstAmount,
            'total_amount' => $totalAmount, // This includes carry forward
        ];
    }

    /**
     * Recalculate invoice due date using latest calculation logic.
     * This ensures old invoices show correct due dates after fixes.
     */
    private function recalculateInvoiceDueDate(Application $application, ?Invoice $invoice = null): \Carbon\Carbon
    {
        $applicationData = $application->application_data ?? [];

        // Get billing cycle
        $billingPlanRaw = $application->billing_cycle ?? ($applicationData['port_selection']['billing_plan'] ?? 'monthly');
        $billingPlan = strtolower(trim($billingPlanRaw));
        if (in_array($billingPlan, ['arc', 'annual'])) {
            $billingPlan = 'annual';
        } elseif (in_array($billingPlan, ['mrc', 'monthly'])) {
            $billingPlan = 'monthly';
        } elseif ($billingPlan === 'quarterly') {
            $billingPlan = 'quarterly';
        } else {
            $billingPlan = 'monthly';
        }

        // Determine start date
        $startDate = null;

        if ($invoice) {
            // Check for previous paid invoices to determine start date
            $lastPaidInvoice = Invoice::where('application_id', $application->id)
                ->where('status', 'paid')
                ->where('id', '<', $invoice->id)
                ->latest('invoice_date')
                ->first();

            if ($lastPaidInvoice && $lastPaidInvoice->due_date) {
                // Subsequent invoice: start from last invoice's due date
                $startDate = \Carbon\Carbon::parse($lastPaidInvoice->due_date);
            } elseif ($application->service_activation_date) {
                // First invoice: start from service activation date
                $startDate = \Carbon\Carbon::parse($application->service_activation_date);
            } else {
                // Fallback: use invoice date
                $startDate = \Carbon\Carbon::parse($invoice->invoice_date);
            }
        } elseif ($application->service_activation_date) {
            // No invoice yet, use service activation date
            $startDate = \Carbon\Carbon::parse($application->service_activation_date);
        } else {
            // Fallback: use current date
            $startDate = now('Asia/Kolkata');
        }

        // Calculate end date (due date) based on billing cycle
        switch ($billingPlan) {
            case 'annual':
            case 'arc':
                $dueDate = $startDate->copy()->addYear();
                break;
            case 'quarterly':
                $dueDate = $startDate->copy()->addMonths(3);
                break;
            case 'monthly':
            case 'mrc':
            default:
                // For monthly billing, due date should be one day before the same day next month
                $dueDate = $startDate->copy()->addMonth()->subDay();
                break;
        }

        return $dueDate;
    }

    /**
     * Get current billing period based on billing cycle and service activation date.
     */
    private function getCurrentBillingPeriod(Application $application): ?string
    {
        if (! $application->service_activation_date || ! $application->billing_cycle) {
            return null;
        }

        $now = now('Asia/Kolkata');

        // Normalize billing cycle (mrc = monthly, arc = annual)
        $billingCycle = strtolower(trim($application->billing_cycle));
        if (in_array($billingCycle, ['mrc', 'monthly'])) {
            $billingCycle = 'monthly';
        } elseif (in_array($billingCycle, ['arc', 'annual'])) {
            $billingCycle = 'annual';
        }

        // Use billing_anchor_date if available (for aligning all billing cycles to 1st of month)
        // Otherwise, use service_activation_date
        $baseDate = $application->billing_anchor_date
            ? \Carbon\Carbon::parse($application->billing_anchor_date)->startOfDay()
            : \Carbon\Carbon::parse($application->service_activation_date)->startOfDay();

        // Get the latest service invoice to determine the current billing period
        $latestServiceInvoice = Invoice::where('application_id', $application->id)
            ->where('invoice_purpose', 'service')
            ->where('status', '!=', 'cancelled')
            ->latest('billing_end_date')
            ->first();

        if ($latestServiceInvoice && $latestServiceInvoice->billing_end_date) {
            // Calculate next billing period from the latest invoice's end date
            $billingStartDate = \Carbon\Carbon::parse($latestServiceInvoice->billing_end_date)->addDay()->startOfDay();
        } else {
            // First invoice: use base date (anchor date or activation date)
            $billingStartDate = $baseDate->copy();
        }

        // Ensure we never generate a service period before the anchor (important after DISCONNECTED → LIVE).
        if ($application->billing_anchor_date) {
            $anchor = \Carbon\Carbon::parse($application->billing_anchor_date)->startOfDay();
            if ($billingStartDate->lt($anchor)) {
                $billingStartDate = $anchor;
            }
        }

        switch ($billingCycle) {
            case 'monthly':
                return $billingStartDate->format('Y-m');

            case 'quarterly':
                $quarter = ceil($billingStartDate->month / 3);

                return $billingStartDate->format('Y').'-Q'.$quarter;

            case 'annual':
                return $billingStartDate->format('Y');

            default:
                return null;
        }
    }

    /**
     * Check if payment is already verified for current billing period.
     */
    private function isPaymentVerifiedForPeriod(Application $application, string $billingPeriod): bool
    {
        return \App\Models\PaymentVerificationLog::where('application_id', $application->id)
            ->where('billing_period', $billingPeriod)
            ->exists();
    }

    /**
     * Check if invoice can be generated for the given billing period.
     * Rules:
     * - For current period: Can generate one month in advance
     * - For next billing cycle: Can only generate one month before the start of next billing cycle
     */
    private function canGenerateInvoiceForPeriod(Application $application, string $billingPeriod): bool
    {
        if (! $application->service_activation_date || ! $application->billing_cycle) {
            return false;
        }

        $activationDate = \Carbon\Carbon::parse($application->service_activation_date);
        $now = now('Asia/Kolkata');

        // Normalize billing cycle (mrc = monthly, arc = annual)
        $billingCycle = strtolower(trim($application->billing_cycle));
        if (in_array($billingCycle, ['mrc', 'monthly'])) {
            $billingCycle = 'monthly';
        } elseif (in_array($billingCycle, ['arc', 'annual'])) {
            $billingCycle = 'annual';
        }

        // Calculate the billing period start date
        $billingPeriodStart = null;
        switch ($billingCycle) {
            case 'monthly':
                // billingPeriod format: Y-m (e.g., 2025-01)
                $parts = explode('-', $billingPeriod);
                if (count($parts) === 2) {
                    $billingPeriodStart = \Carbon\Carbon::createFromDate((int) $parts[0], (int) $parts[1], 1);
                }
                break;
            case 'quarterly':
                // billingPeriod format: Y-QN (e.g., 2025-Q1)
                if (preg_match('/(\d{4})-Q(\d)/', $billingPeriod, $matches)) {
                    $year = (int) $matches[1];
                    $quarter = (int) $matches[2];
                    $month = ($quarter - 1) * 3 + 1;
                    $billingPeriodStart = \Carbon\Carbon::createFromDate($year, $month, 1);
                }
                break;
            case 'annual':
                // billingPeriod format: Y (e.g., 2025)
                $billingPeriodStart = \Carbon\Carbon::createFromDate((int) $billingPeriod, 1, 1);
                break;
        }

        if (! $billingPeriodStart) {
            return false;
        }

        // Check if this is the current billing period
        $currentBillingPeriod = $this->getCurrentBillingPeriod($application);
        $isCurrentPeriod = ($currentBillingPeriod === $billingPeriod);

        if ($isCurrentPeriod) {
            // For current period: Can generate one month in advance
            $oneMonthBefore = $billingPeriodStart->copy()->subMonth();
            if (! $now->gte($oneMonthBefore)) {
                return false;
            }
        } else {
            // For next billing cycle: Can only generate one month before the start
            $oneMonthBefore = $billingPeriodStart->copy()->subMonth();
            if (! ($now->gte($oneMonthBefore) && $now->lt($billingPeriodStart))) {
                return false;
            }
        }

        // No need to check if previous invoice is paid - generate next billing cycle invoice regardless
        // Only requirement is that we can generate one month before the billing cycle starts
        return true;
    }

    /**
     * IX Account: Verify Payment (supports recurring payments and partial payments).
     */
    public function ixAccountVerifyPayment(Request $request, $id)
    {
        try {
            $admin = $this->getCurrentAdmin();

            if (! $this->hasRole($admin, 'ix_account')) {
                return back()->with('error', 'You do not have permission to perform this action.');
            }

            $application = Application::with('user')->where('application_type', 'IX')->findOrFail($id);

            // Only allow verification for LIVE applications
            if (! $application->is_active) {
                return back()->with('error', 'Payment can only be verified for LIVE applications.');
            }

            if (! $application->isVisibleToIxAccount()) {
                return back()->with('error', 'This application is not available for Account review.');
            }

            // Validate input
            $validated = $request->validate([
                'payment_id' => 'required|string|max:255',
                'amount_captured' => 'required|numeric|min:0',
                'notes' => 'nullable|string|max:1000',
            ]);

            // Check if this is initial or recurring payment
            $isInitialPayment = ! $application->service_activation_date;
            $billingPeriod = null;
            $verificationType = 'initial';
            $invoice = null;

            // Prevent verifying initial payment if it's already been verified
            if ($isInitialPayment) {
                $hasInitialVerification = $application->paymentVerificationLogs()
                    ->where('verification_type', 'initial')
                    ->exists();

                if ($hasInitialVerification) {
                    return back()->with('error', 'Initial application payment has already been verified. Only recurring invoice payments can be verified after the application is live.');
                }
            }

            if (! $isInitialPayment) {
                $billingPeriod = $this->getCurrentBillingPeriod($application);
                if ($billingPeriod) {
                    $verificationType = 'recurring';

                    // Check if already verified for this period
                    if ($this->isPaymentVerifiedForPeriod($application, $billingPeriod)) {
                        $periodLabel = $this->getBillingPeriodLabel($application->billing_cycle, $billingPeriod);

                        return back()->with('error', "Payment for this {$periodLabel} has already been verified.");
                    }

                    // Find invoice for this billing period
                    $invoice = Invoice::where('application_id', $application->id)
                        ->where('billing_period', $billingPeriod)
                        ->where('status', '!=', 'cancelled')
                        ->whereNull('credit_note_pdf_path')
                        ->first();

                    if (! $invoice) {
                        return back()->with('error', 'No invoice found for this billing period. Please generate an invoice first.');
                    }

                    // Validate amount_captured doesn't exceed balance
                    $balanceAmount = $invoice->balance_amount ?? $invoice->total_amount;
                    if ($validated['amount_captured'] > $balanceAmount) {
                        return back()->with('error', "Amount captured (₹{$validated['amount_captured']}) cannot exceed the balance amount (₹{$balanceAmount}).");
                    }
                }
            }

            // Get expected payment amount (from invoice if available, otherwise from application data)
            $expectedAmount = 0;
            if ($invoice) {
                $expectedAmount = $invoice->balance_amount ?? $invoice->total_amount;
            } else {
                $applicationData = $application->application_data ?? [];
                $expectedAmount = $applicationData['payment']['total_amount'] ?? $applicationData['payment']['amount'] ?? 0;
            }

            $amountCaptured = (float) $validated['amount_captured'];
            $isPartialPayment = $amountCaptured < $expectedAmount;

            // Update invoice if it exists (for recurring payments)
            if ($invoice) {
                $currentPaidAmount = (float) ($invoice->paid_amount ?? 0);
                $newPaidAmount = $currentPaidAmount + $amountCaptured;
                $balanceAmount = max(0, (float) $invoice->total_amount - $newPaidAmount);

                // Determine payment status
                $paymentStatus = 'pending';
                if ($newPaidAmount >= $invoice->total_amount) {
                    $paymentStatus = 'paid';
                    $balanceAmount = 0;
                } elseif ($newPaidAmount > 0) {
                    $paymentStatus = 'partial';
                }

                // Update invoice
                $invoice->update([
                    'paid_amount' => $newPaidAmount,
                    'balance_amount' => $balanceAmount,
                    'payment_status' => $paymentStatus,
                    'status' => $paymentStatus === 'paid' ? 'paid' : $invoice->status,
                    'paid_at' => $paymentStatus === 'paid' ? now('Asia/Kolkata') : $invoice->paid_at,
                    'paid_by' => $paymentStatus === 'paid' ? $admin->id : $invoice->paid_by,
                    'manual_payment_id' => $validated['payment_id'],
                    'manual_payment_notes' => $validated['notes'] ?? null,
                ]);

                // Create payment transaction record
                \App\Models\PaymentTransaction::create([
                    'application_id' => $application->id,
                    'invoice_id' => $invoice->id,
                    'transaction_id' => $validated['payment_id'],
                    'amount' => $amountCaptured,
                    'currency' => 'INR',
                    'payment_method' => 'manual',
                    'payment_status' => 'success',
                    'payment_mode' => 'manual',
                    'transaction_date' => now('Asia/Kolkata'),
                    'notes' => $validated['notes'] ?? null,
                ]);
            }

            // Create payment verification log
            $verificationLog = \App\Models\PaymentVerificationLog::create([
                'application_id' => $application->id,
                'verified_by' => $admin->id,
                'verification_type' => $verificationType,
                'billing_period' => $billingPeriod,
                'payment_id' => $validated['payment_id'],
                'amount' => $expectedAmount,
                'amount_captured' => $amountCaptured,
                'currency' => 'INR',
                'payment_method' => 'manual',
                'notes' => $validated['notes'],
                'verified_at' => now('Asia/Kolkata'),
            ]);

            // Log status change
            $statusMessage = $verificationType === 'initial'
                ? 'Initial payment verified by IX Account'
                : "Recurring payment verified for {$billingPeriod} by IX Account";

            if ($isPartialPayment) {
                $statusMessage .= " (Partial: ₹{$amountCaptured} of ₹{$expectedAmount})";
            }

            ApplicationStatusHistory::log(
                $application->id,
                $application->status,
                $application->status, // Keep same status, don't change application status
                'admin',
                $admin->id,
                $statusMessage
            );

            // Send message to user
            $periodLabel = $billingPeriod ? $this->getBillingPeriodLabel($application->billing_cycle, $billingPeriod) : 'initial';
            $messageText = "Payment has been verified for your application {$application->application_id} ({$periodLabel} payment).";
            if ($isPartialPayment) {
                $messageText .= " Amount captured: ₹{$amountCaptured} of ₹{$expectedAmount}. Balance: ₹".number_format($expectedAmount - $amountCaptured, 2);
            }

            $this->createMessageForAdmin(
                $admin->id,
                $application->user_id,
                'Payment Verified'.($isPartialPayment ? ' (Partial)' : ''),
                $messageText
            );

            $periodLabel = $billingPeriod ? $this->getBillingPeriodLabel($application->billing_cycle, $billingPeriod) : 'initial';
            $successMessage = $isPartialPayment
                ? "Partial payment verified successfully for {$periodLabel} period! Amount captured: ₹{$amountCaptured} of ₹{$expectedAmount}. Balance: ₹".number_format($expectedAmount - $amountCaptured, 2)
                : "Payment verified successfully for {$periodLabel} period!";

            return back()->with('success', $successMessage);
        } catch (Exception $e) {
            Log::error('Error verifying payment: '.$e->getMessage());

            return back()->with('error', 'An error occurred. Please try again.');
        }
    }

    /**
     * Get billing period label for display.
     */
    private function getBillingPeriodLabel(string $billingCycle, string $billingPeriod): string
    {
        if (str_starts_with($billingPeriod, 'FINAL-')) {
            if (preg_match('/^FINAL-(\d{4})(\d{2})(\d{2})-/', $billingPeriod, $m)) {
                try {
                    $date = \Carbon\Carbon::createFromFormat('Y-m-d', "{$m[1]}-{$m[2]}-{$m[3]}");

                    return 'Final up to '.$date->format('d/m/Y');
                } catch (\Exception $e) {
                    return $billingPeriod;
                }
            }

            return $billingPeriod;
        }

        switch ($billingCycle) {
            case 'monthly':
                if (! preg_match('/^\d{4}-\d{2}$/', $billingPeriod)) {
                    return $billingPeriod;
                }

                $date = \Carbon\Carbon::createFromFormat('Y-m', $billingPeriod);

                return $date->format('F Y');

            case 'quarterly':
                return str_replace('-Q', ' Q', $billingPeriod);

            case 'annual':
                return $billingPeriod;

            default:
                return $billingPeriod;
        }
    }

    /**
     * IX Account: Show invoice edit form.
     */
    public function ixAccountEditInvoice($invoiceId)
    {
        try {
            $admin = $this->getCurrentAdmin();

            if (! $this->hasRole($admin, 'ix_account')) {
                return back()->with('error', 'You do not have permission to perform this action.');
            }

            $invoice = Invoice::with(['application.user', 'generatedBy', 'paidBy'])
                ->whereHas('application', function ($q) {
                    $q->where('application_type', 'IX');
                })
                ->findOrFail($invoiceId);

            if (! $invoice->application->is_active) {
                return back()->with('error', 'Invoice can only be managed for LIVE applications.');
            }

            // Load line items from segments if they exist
            $lineItems = $invoice->line_items ?? [];
            $segments = [];

            // Extract segments from line_items (they might be stored as array or with metadata)
            if (is_array($lineItems)) {
                // Check if metadata exists (new format)
                if (isset($lineItems['_metadata'])) {
                    // Segments are the keys except _metadata
                    foreach ($lineItems as $key => $value) {
                        if ($key !== '_metadata' && is_array($value)) {
                            $segments[] = $value;
                        }
                    }
                } else {
                    // Old format: check if items have segment structure
                    foreach ($lineItems as $item) {
                        if (is_array($item) && (isset($item['start']) || isset($item['description']))) {
                            $segments[] = $item;
                        }
                    }
                }
            }

            // If no segments found, try to regenerate from billing cycle
            if (empty($segments) && $invoice->billing_start_date && $invoice->billing_end_date) {
                try {
                    $application = $invoice->application;
                    $applicationData = $application->application_data ?? [];
                    $billingStartDate = \Carbon\Carbon::parse($invoice->billing_start_date);
                    $billingEndDate = \Carbon\Carbon::parse($invoice->billing_end_date);
                    $billingPlan = $application->billing_cycle ?? ($applicationData['port_selection']['billing_plan'] ?? 'monthly');

                    // Get location and pricing
                    $locationId = $applicationData['location']['id'] ?? null;
                    $location = $locationId ? IxLocation::find($locationId) : null;

                    if ($location) {
                        $baseCapacity = $application->assigned_port_capacity ?? ($applicationData['port_selection']['capacity'] ?? null);

                        if ($baseCapacity) {
                            // Get pricing
                            $pricing = IxPortPricing::where('location_id', $location->id)
                                ->where('port_capacity', $baseCapacity)
                                ->where('billing_plan', strtolower($billingPlan))
                                ->first();

                            if ($pricing) {
                                $amount = $pricing->amount;
                                // Calculate days inclusively (both start and end dates count)
                                $days = $billingStartDate->diffInDays($billingEndDate) + 1;

                                // Calculate prorated amount
                                $billingCycleDays = match (strtolower($billingPlan)) {
                                    'annual', 'arc' => 365,
                                    'quarterly' => 90,
                                    'monthly', 'mrc' => 30,
                                    default => 30,
                                };

                                $prorated = round(($amount * $days) / $billingCycleDays, 2);

                                $segments = [[
                                    'start' => $billingStartDate->format('Y-m-d'),
                                    'end' => $billingEndDate->format('Y-m-d'),
                                    'capacity' => $baseCapacity,
                                    'plan' => strtolower($billingPlan),
                                    'plan_label' => match (strtolower($billingPlan)) {
                                        'annual', 'arc' => 'Annual (ARC)',
                                        'quarterly' => 'Quarterly',
                                        'monthly', 'mrc' => 'Monthly (MRC)',
                                        default => ucfirst($billingPlan),
                                    },
                                    'days' => $days,
                                    'amount_full' => $amount,
                                    'amount_prorated' => $prorated,
                                    'description' => "IX Service - {$baseCapacity} Port Capacity ({$billingPlan})",
                                    'quantity' => 1,
                                    'rate' => $amount,
                                    'amount' => $prorated,
                                ]];
                            }
                        }
                    }
                } catch (Exception $e) {
                    Log::warning('Could not regenerate segments for invoice edit: '.$e->getMessage());
                }
            }

            return view('admin.invoices.edit', compact('invoice', 'admin', 'segments'));
        } catch (Exception $e) {
            Log::error('Error loading invoice edit form: '.$e->getMessage());

            return back()->with('error', 'Unable to load invoice edit form.');
        }
    }

    /**
     * IX Account: Update invoice.
     */
    public function ixAccountUpdateInvoice(\App\Http\Requests\UpdateInvoiceRequest $request, $invoiceId)
    {
        try {
            $admin = $this->getCurrentAdmin();

            if (! $this->hasRole($admin, 'ix_account')) {
                return back()->with('error', 'You do not have permission to perform this action.');
            }

            $invoice = Invoice::with('application')
                ->whereHas('application', function ($q) {
                    $q->where('application_type', 'IX');
                })
                ->findOrFail($invoiceId);

            if (! $invoice->application->is_active) {
                return back()->with('error', 'Invoice can only be managed for LIVE applications.');
            }

            $validated = $request->validated();

            // Prepare line items
            $lineItems = [];
            if (isset($validated['line_items']) && is_array($validated['line_items'])) {
                foreach ($validated['line_items'] as $item) {
                    if (! empty($item['description'])) {
                        $lineItems[] = [
                            'description' => $item['description'],
                            'quantity' => $item['quantity'] ?? 1,
                            'rate' => $item['rate'] ?? 0,
                            'amount' => $item['amount'] ?? 0,
                        ];
                    }
                }
            }

            // Delete old PDF if exists
            $oldPdfPath = $invoice->pdf_path;
            if ($oldPdfPath && Storage::disk('public')->exists($oldPdfPath)) {
                Storage::disk('public')->delete($oldPdfPath);
            }

            // Calculate TDS percentage from TDS amount and base amount
            $tdsAmount = (float) ($validated['tds_amount'] ?? 0);
            $amount = (float) $validated['amount'];
            $tdsPercentage = $amount > 0 ? ($tdsAmount / $amount) * 100 : 0;

            // Update invoice
            $invoice->update([
                'invoice_date' => $validated['invoice_date'],
                'due_date' => $validated['due_date'],
                'billing_period' => $validated['billing_period'] ?? $invoice->billing_period,
                'billing_start_date' => $validated['billing_start_date'] ?? $invoice->billing_start_date,
                'billing_end_date' => $validated['billing_end_date'] ?? $invoice->billing_end_date,
                'line_items' => ! empty($lineItems) ? $lineItems : $invoice->line_items,
                'amount' => $validated['amount'],
                'gst_amount' => $validated['gst_amount'],
                'tds_percentage' => $tdsPercentage,
                'tds_amount' => $tdsAmount,
                'total_amount' => $validated['total_amount'],
                'paid_amount' => $validated['paid_amount'],
                'balance_amount' => $validated['balance_amount'],
                'payment_status' => $validated['payment_status'],
                'status' => $validated['status'],
                'carry_forward_amount' => $validated['carry_forward_amount'] ?? 0,
                'has_carry_forward' => $validated['has_carry_forward'] ?? false,
                'manual_payment_id' => $validated['manual_payment_id'] ?? $invoice->manual_payment_id,
                'manual_payment_notes' => $validated['manual_payment_notes'] ?? $invoice->manual_payment_notes,
                'paid_at' => $validated['payment_status'] === 'paid' && ! $invoice->paid_at ? now('Asia/Kolkata') : ($validated['payment_status'] !== 'paid' ? null : $invoice->paid_at),
                'paid_by' => $validated['payment_status'] === 'paid' && ! $invoice->paid_by ? $admin->id : ($validated['payment_status'] !== 'paid' ? null : $invoice->paid_by),
                'pdf_path' => null, // Will be regenerated
            ]);

            // Regenerate invoice PDF
            $application = $invoice->application()->with('user')->first();
            try {
                $invoicePdf = $this->generateIxInvoicePdf($application, $invoice);
                $invoicePdfPath = 'applications/'.$application->user_id.'/ix/'.$invoice->invoice_number.'_invoice.pdf';
                Storage::disk('public')->put($invoicePdfPath, $invoicePdf->output());
                $invoice->update(['pdf_path' => $invoicePdfPath]);
            } catch (Exception $e) {
                Log::error('Error regenerating IX invoice PDF: '.$e->getMessage());
            }

            // Send updated invoice email to user
            try {
                // Get authorized representative name from application data
                $authorizedPersonName = $application->authorized_representative_details['name']
                    ?? $application->application_data['representative']['name']
                    ?? $application->user->fullname;

                // Get ISP name (user's fullname or company name)
                $ispName = $application->user->fullname;

                // Get billing dates from invoice
                $billingStartDate = $invoice->billing_start_date ? $invoice->billing_start_date->format('Y-m-d') : null;
                $billingEndDate = $invoice->billing_end_date ? $invoice->billing_end_date->format('Y-m-d') : null;

                Mail::to($application->user->email)->send(new IxApplicationInvoiceMail(
                    $application->user->fullname,
                    $application->application_id,
                    $invoice->invoice_number,
                    $invoice->total_amount,
                    $application->status,
                    $invoice->pdf_path ?? null,
                    null, // No PayU URL for updated invoice
                    null,  // No PayU data for updated invoice
                    $authorizedPersonName,
                    $ispName,
                    $billingStartDate,
                    $billingEndDate
                ));
                $invoice->update(['sent_at' => now('Asia/Kolkata')]);
                Log::info("Updated invoice email sent to {$application->user->email} for invoice {$invoice->invoice_number}");
            } catch (Exception $e) {
                Log::error('Error sending updated invoice email: '.$e->getMessage());
            }

            // Log status change
            ApplicationStatusHistory::log(
                $invoice->application_id,
                $invoice->application->status,
                $invoice->application->status,
                'admin',
                $admin->id,
                "Invoice {$invoice->invoice_number} updated by IX Account"
            );

            return redirect()->route('admin.applications.show', $invoice->application_id)
                ->with('success', 'Invoice updated successfully. Updated invoice has been sent to the user.');
        } catch (Exception $e) {
            Log::error('Error updating invoice: '.$e->getMessage());

            return back()->with('error', 'Unable to update invoice. Please try again.');
        }
    }

    /**
     * IX Account: Delete invoice.
     */
    public function ixAccountDeleteInvoice($invoiceId)
    {
        try {
            $admin = $this->getCurrentAdmin();

            if (! $this->hasRole($admin, 'ix_account')) {
                return back()->with('error', 'You do not have permission to perform this action.');
            }

            $invoice = Invoice::with('application')
                ->whereHas('application', function ($q) {
                    $q->where('application_type', 'IX');
                })
                ->findOrFail($invoiceId);

            if (! $invoice->application->is_active) {
                return back()->with('error', 'Invoice can only be managed for LIVE applications.');
            }

            $applicationId = $invoice->application_id;
            $invoiceNumber = $invoice->invoice_number;

            // Delete old PDF if exists
            if ($invoice->pdf_path && Storage::disk('public')->exists($invoice->pdf_path)) {
                Storage::disk('public')->delete($invoice->pdf_path);
            }

            // If this invoice had carry forward, restore previous invoices' status
            if ($invoice->has_carry_forward && $invoice->carry_forward_amount > 0) {
                // Find invoices that were marked as paid due to this invoice's carry forward
                $forwardedInvoices = Invoice::where('application_id', $invoice->application_id)
                    ->where('forwarded_to_invoice_date', $invoice->invoice_date)
                    ->where('forwarded_amount', '>', 0)
                    ->get();

                foreach ($forwardedInvoices as $forwardedInvoice) {
                    // Restore original status based on forwarded amount
                    $forwardedAmount = $forwardedInvoice->forwarded_amount;
                    $originalTotal = $forwardedInvoice->total_amount;
                    // When forwarded, paid_amount was set to (total - forwarded), so we keep it
                    // The balance should be restored to the forwarded amount
                    $currentPaidAmount = $forwardedInvoice->paid_amount ?? 0;
                    $originalBalance = $forwardedAmount; // The forwarded amount was the balance

                    // Determine original payment status
                    $originalPaymentStatus = 'pending';
                    if ($currentPaidAmount > 0 && $currentPaidAmount < $originalTotal) {
                        $originalPaymentStatus = 'partial';
                    } elseif ($currentPaidAmount >= $originalTotal) {
                        $originalPaymentStatus = 'paid';
                    }

                    $forwardedInvoice->update([
                        'payment_status' => $originalPaymentStatus,
                        'status' => $originalPaymentStatus === 'paid' ? 'paid' : 'pending',
                        'paid_amount' => $currentPaidAmount, // Keep current (total - forwarded)
                        'balance_amount' => $originalBalance, // Restore balance
                        'forwarded_amount' => null,
                        'forwarded_to_invoice_date' => null,
                        'has_carry_forward' => false,
                        'carry_forward_amount' => 0,
                        'paid_at' => $originalPaymentStatus === 'paid' ? $forwardedInvoice->paid_at : null,
                        'paid_by' => $originalPaymentStatus === 'paid' ? $forwardedInvoice->paid_by : null,
                        'manual_payment_notes' => preg_replace('/\s*\|\s*Amount forwarded to invoice.*$/i', '', $forwardedInvoice->manual_payment_notes ?? ''),
                    ]);

                    Log::info("Restored invoice {$forwardedInvoice->invoice_number} status after deleting invoice {$invoice->invoice_number}. Paid: {$currentPaidAmount}, Balance: {$originalBalance}, Status: {$originalPaymentStatus}");
                }
            }

            // Delete related records
            $invoice->paymentAllocations()->delete();
            // PaymentTransaction doesn't have invoice_id, delete by application_id and transaction matching invoice number
            PaymentTransaction::where('application_id', $invoice->application_id)
                ->where('product_info', 'like', '%'.$invoice->invoice_number.'%')
                ->delete();
            PaymentVerificationLog::where('application_id', $invoice->application_id)
                ->where('billing_period', $invoice->billing_period)
                ->delete();

            // Delete invoice
            $invoice->delete();

            // Log status change
            ApplicationStatusHistory::log(
                $applicationId,
                $invoice->application->status,
                $invoice->application->status,
                'admin',
                $admin->id,
                "Invoice {$invoiceNumber} deleted by IX Account"
            );

            return redirect()->route('admin.applications.show', $applicationId)
                ->with('success', 'Invoice deleted successfully.');
        } catch (Exception $e) {
            Log::error('Error deleting invoice: '.$e->getMessage());

            return back()->with('error', 'Unable to delete invoice. Please try again.');
        }
    }

    /**
     * IX Account: Cancel invoice (within 24 hours) via CanIRN API, rename PDF to -C.pdf, send cancellation email.
     */
    public function ixAccountCancelInvoice(Request $request, $invoiceId): RedirectResponse
    {
        try {
            $admin = $this->getCurrentAdmin();
            if (! $this->hasRole($admin, 'ix_account')) {
                return back()->with('error', 'You do not have permission to perform this action.');
            }

            $request->validate([
                'cnl_rsn' => 'required|string|max:10',
                'cnl_rem' => 'required|string|max:500',
            ]);

            $invoice = Invoice::with('application')->whereHas('application', function ($q) {
                $q->where('application_type', 'IX');
            })->findOrFail($invoiceId);

            if (! $invoice->canBeCancelled()) {
                return back()->with('error', 'This invoice cannot be cancelled (allowed only within 24 hours of generation, and must have IRN).');
            }

            $application = $invoice->application;
            if (! $application->is_active) {
                return back()->with('error', 'Invoice can only be managed for LIVE applications.');
            }

            $gstin = $invoice->seller_gstin;
            if (empty($gstin)) {
                return back()->with('error', 'Seller GSTIN not found for this invoice.');
            }
            $sellerStateCode = substr($gstin, 0, 2);
            $creds = $this->getNixiLocationCredentials($sellerStateCode);

            $cancelUrl = rtrim(env('EINVOICE_API_URL', 'http://einvlive.webtel.in/v1.03/GenIRN'), '/');
            $cancelUrl = preg_replace('#/GenIRN$#', '/CanIRN', $cancelUrl);
            if (strpos($cancelUrl, 'CanIRN') === false) {
                $cancelUrl = 'http://einvlive.webtel.in/v1.03/CanIRN';
            }

            $payload = [
                'Push_Data_List' => [
                    'Data' => [
                        [
                            'Irn' => $invoice->einvoice_irn,
                            'GSTIN' => $gstin,
                            'CnlRsn' => $request->input('cnl_rsn'),
                            'CnlRem' => $request->input('cnl_rem'),
                            'CDKey' => $creds['cd_key'],
                            'EFUserName' => $creds['ef_user_name'],
                            'EFPassword' => $creds['ef_password'],
                            'EInvUserName' => $creds['einv_user_name'],
                            'EInvPassword' => $creds['einv_password'],
                        ],
                    ],
                ],
            ];

            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->timeout(30)->asJson()->post($cancelUrl, $payload);

            $statusCode = $response->status();
            $responseBody = $response->body();
            $responseData = json_decode($responseBody, true);

            Log::info('CanIRN API response', ['invoice_id' => $invoice->id, 'status_code' => $statusCode, 'response' => $responseData]);

            $cancelApiResponse = is_array($responseData)
                ? array_merge($responseData, ['_meta' => ['http_status_code' => $statusCode]])
                : ['_meta' => ['http_status_code' => $statusCode, 'raw_body' => $responseBody]];
            $invoice->update(['cancel_api_response' => $cancelApiResponse]);

            $ok = $statusCode === 200 && is_array($responseData) && ! empty($responseData);
            $first = $ok ? ($responseData[0] ?? null) : null;
            $status = $first['Status'] ?? $first['status'] ?? null;
            if (! $ok || ($status !== '1' && $status !== 1)) {
                $errMsg = $first['ErrorMessage'] ?? $first['error_message'] ?? $responseBody;

                return back()->with('error', 'Cancellation failed: '.$errMsg);
            }

            $oldPath = $invoice->pdf_path;
            if ($oldPath && Storage::disk('public')->exists($oldPath)) {
                $newPath = preg_replace('/_invoice\.pdf$/i', '_invoice-C.pdf', $oldPath);
                if ($newPath !== $oldPath) {
                    Storage::disk('public')->move($oldPath, $newPath);
                    $invoice->update(['pdf_path' => $newPath]);
                }
            }

            $invoice->update(['status' => 'cancelled']);

            try {
                Mail::to($application->user->email)->send(new IxInvoiceCancellationMail(
                    $application->user->fullname,
                    $application->application_id,
                    $invoice->invoice_number
                ));
            } catch (Exception $e) {
                Log::error('Error sending invoice cancellation email: '.$e->getMessage());
            }

            ApplicationStatusHistory::log(
                $application->id,
                $application->status,
                $application->status,
                'admin',
                $admin->id,
                "Invoice {$invoice->invoice_number} cancelled by IX Account"
            );

            return redirect()->route('admin.applications.show', $application->id)
                ->with('success', 'Invoice cancelled successfully. User has been notified by email.');
        } catch (ValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            Log::error('Error cancelling invoice: '.$e->getMessage());

            return back()->with('error', 'Unable to cancel invoice. Please try again.');
        }
    }

    /**
     * IX Account: Generate credit note for invoice (after 24 hours). Same payload as invoice with DOC_TYP CRN, then generate credit note PDF.
     */
    public function ixAccountGenerateCreditNote(Request $request, $invoiceId): RedirectResponse
    {
        try {
            $admin = $this->getCurrentAdmin();
            if (! $this->hasRole($admin, 'ix_account')) {
                return back()->with('error', 'You do not have permission to perform this action.');
            }

            $invoice = Invoice::with('application')->whereHas('application', function ($q) {
                $q->where('application_type', 'IX');
            })->findOrFail($invoiceId);

            if (! $invoice->canGenerateCreditNote()) {
                return back()->with('error', 'Credit note can only be generated after 24 hours from invoice generation and when invoice has IRN.');
            }

            $application = $invoice->application;
            if (! $application->is_active) {
                return back()->with('error', 'Invoice can only be managed for LIVE applications.');
            }

            $creditNoteResponse = $this->callEinvoiceApi($application, $invoice, true);
            if (! $creditNoteResponse) {
                return back()->with('error', 'Credit note API call failed. Check logs for details.');
            }

            // Extract credit note details from API response
            $creditNoteUpdateData = [
                'credit_note_api_response' => $creditNoteResponse,
            ];

            // Extract individual fields from API response
            if (isset($creditNoteResponse['Irn'])) {
                $creditNoteUpdateData['credit_note_irn'] = $creditNoteResponse['Irn'];
            }
            if (isset($creditNoteResponse['AckNo'])) {
                $creditNoteUpdateData['credit_note_ack_no'] = (string) $creditNoteResponse['AckNo'];
            }
            if (isset($creditNoteResponse['AckDate'])) {
                try {
                    $creditNoteUpdateData['credit_note_ack_date'] = \Carbon\Carbon::parse($creditNoteResponse['AckDate'])->setTimezone('Asia/Kolkata');
                } catch (\Exception $e) {
                    Log::warning('Failed to parse credit note AckDate: '.$creditNoteResponse['AckDate']);
                }
            }
            if (isset($creditNoteResponse['DocDate'])) {
                try {
                    // DocDate format can be "13/01/2026" (DD/MM/YYYY) or ISO format
                    if (strpos($creditNoteResponse['DocDate'], '/') !== false) {
                        $creditNoteUpdateData['credit_note_doc_date'] = \Carbon\Carbon::createFromFormat('d/m/Y', $creditNoteResponse['DocDate']);
                    } else {
                        $creditNoteUpdateData['credit_note_doc_date'] = \Carbon\Carbon::parse($creditNoteResponse['DocDate']);
                    }
                } catch (\Exception $e) {
                    Log::warning('Failed to parse credit note DocDate: '.$creditNoteResponse['DocDate']);
                }
            }
            if (isset($creditNoteResponse['Status'])) {
                $creditNoteUpdateData['credit_note_status'] = $creditNoteResponse['Status'];
            }

            // Update invoice status to 'credit_note' (similar to cancelled invoices)
            $creditNoteUpdateData['status'] = 'cancelled';
            // $creditNoteUpdateData['payment_status'] = 'cancelled'; // Also update payment_status

            $invoice->update($creditNoteUpdateData);

            // Refresh invoice to get latest data
            $invoice->refresh();

            // Ensure any missing fields are extracted from API response (fallback)
            $fallbackUpdateData = $this->extractCreditNoteFieldsFromApiResponse($invoice);
            if (! empty($fallbackUpdateData)) {
                $invoice->update($fallbackUpdateData);
                Log::info('Extracted additional credit note fields from API response after initial update', [
                    'invoice_id' => $invoice->id,
                    'fields' => array_keys($fallbackUpdateData),
                ]);
            }

            // Ensure any missing fields are extracted from API response before generating PDF
            $finalExtractData = $this->extractCreditNoteFieldsFromApiResponse($invoice);
            if (! empty($finalExtractData)) {
                $invoice->update($finalExtractData);
                $invoice->refresh();
            }

            // Credit note PDF filename: invoice_number + "C.pdf" (e.g., NIXIEX2526-2292C.pdf)
            $creditNoteNumber = $invoice->invoice_number.'C';
            $creditNotePdfPath = 'applications/'.$application->user_id.'/ix/'.$creditNoteNumber.'.pdf';
            try {
                $creditNotePdf = $this->generateIxInvoicePdf($application, $invoice, true);
                Storage::disk('public')->put($creditNotePdfPath, $creditNotePdf->output());
                $invoice->update(['credit_note_pdf_path' => $creditNotePdfPath]);
            } catch (Exception $e) {
                Log::error('Error generating credit note PDF: '.$e->getMessage());

                return back()->with('error', 'Credit note was registered but PDF generation failed. Please try again or contact support.');
            }

            // Send credit note email to user
            try {
                $creditNoteNumber = $invoice->invoice_number.'C';
                $authorizedPersonName = $application->authorized_representative_details['name']
                    ?? $application->application_data['representative']['name']
                    ?? $application->user->fullname;

                Mail::to($application->user->email)->send(new IxInvoiceCreditNoteMail(
                    $authorizedPersonName,
                    $application->application_id,
                    $invoice->invoice_number,
                    $creditNoteNumber
                ));
            } catch (Exception $e) {
                Log::error('Error sending credit note email: '.$e->getMessage());
            }

            ApplicationStatusHistory::log(
                $application->id,
                $application->status,
                $application->status,
                'admin',
                $admin->id,
                "Credit note generated for invoice {$invoice->invoice_number}"
            );

            return redirect()->route('admin.applications.show', $application->id)
                ->with('success', 'Credit note generated successfully. User has been notified by email.');
        } catch (Exception $e) {
            Log::error('Error generating credit note: '.$e->getMessage());

            return back()->with('error', 'Unable to generate credit note. Please try again.');
        }
    }

    /**
     * IX Account: Change invoice status.
     */
    public function ixAccountChangeInvoiceStatus(Request $request, $invoiceId)
    {
        try {
            $admin = $this->getCurrentAdmin();

            if (! $this->hasRole($admin, 'ix_account')) {
                return back()->with('error', 'You do not have permission to perform this action.');
            }

            $validated = $request->validate([
                'status' => 'required|in:pending,paid,overdue,cancelled,credit_note',
                'payment_status' => 'required|in:pending,partial,paid,overdue,cancelled',
            ]);

            $invoice = Invoice::with('application')
                ->whereHas('application', function ($q) {
                    $q->where('application_type', 'IX');
                })
                ->findOrFail($invoiceId);

            if (! $invoice->application->is_active) {
                return back()->with('error', 'Invoice can only be managed for LIVE applications.');
            }

            $oldStatus = $invoice->status;
            $oldPaymentStatus = $invoice->payment_status;

            $updateData = [
                'status' => $validated['status'],
                'payment_status' => $validated['payment_status'],
            ];

            // Update paid_at and paid_by if marking as paid
            if ($validated['payment_status'] === 'paid' && $oldPaymentStatus !== 'paid') {
                $updateData['paid_at'] = now('Asia/Kolkata');
                $updateData['paid_by'] = $admin->id;
                $updateData['paid_amount'] = $invoice->total_amount;
                $updateData['balance_amount'] = 0;
            } elseif ($validated['payment_status'] !== 'paid' && $oldPaymentStatus === 'paid') {
                $updateData['paid_at'] = null;
                $updateData['paid_by'] = null;
            }

            $invoice->update($updateData);

            // Log status change
            ApplicationStatusHistory::log(
                $invoice->application_id,
                $invoice->application->status,
                $invoice->application->status,
                'admin',
                $admin->id,
                "Invoice {$invoice->invoice_number} status changed from {$oldStatus}/{$oldPaymentStatus} to {$validated['status']}/{$validated['payment_status']} by IX Account"
            );

            return back()->with('success', 'Invoice status updated successfully.');
        } catch (Exception $e) {
            Log::error('Error changing invoice status: '.$e->getMessage());

            return back()->with('error', 'Unable to update invoice status. Please try again.');
        }
    }

    /**
     * IX Account: Mark invoice as unpaid.
     */
    public function ixAccountMarkInvoiceUnpaid($invoiceId)
    {
        try {
            $admin = $this->getCurrentAdmin();

            if (! $this->hasRole($admin, 'ix_account')) {
                return back()->with('error', 'You do not have permission to perform this action.');
            }

            $invoice = Invoice::with('application')
                ->whereHas('application', function ($q) {
                    $q->where('application_type', 'IX');
                })
                ->findOrFail($invoiceId);

            if (! $invoice->application->is_active) {
                return back()->with('error', 'Invoice can only be managed for LIVE applications.');
            }

            // Check if this is the latest invoice (only allow marking unpaid for the latest invoice)
            $latestInvoice = Invoice::where('application_id', $invoice->application_id)
                ->latest('invoice_date')
                ->latest('created_at')
                ->first();

            if (! $latestInvoice || $latestInvoice->id !== $invoice->id) {
                return back()->with('error', 'You can only mark the most recent invoice as unpaid. Older invoices can only be downloaded.');
            }

            $oldStatus = $invoice->status;
            $oldPaymentStatus = $invoice->payment_status;
            $oldForwardedAmount = $invoice->forwarded_amount ?? 0;

            // If this invoice had carry forward, we need to restore the previous invoice
            if ($invoice->has_carry_forward && $oldForwardedAmount > 0) {
                // Find the invoice that received this carry forward
                $nextInvoice = Invoice::where('application_id', $invoice->application_id)
                    ->where('invoice_date', '>', $invoice->invoice_date)
                    ->orWhere(function ($q) use ($invoice) {
                        $q->where('application_id', $invoice->application_id)
                            ->where('invoice_date', '=', $invoice->invoice_date)
                            ->where('created_at', '>', $invoice->created_at);
                    })
                    ->orderBy('invoice_date')
                    ->orderBy('created_at')
                    ->first();

                if ($nextInvoice) {
                    // Remove carry forward from next invoice's line_items
                    $lineItems = $nextInvoice->line_items ?? [];
                    $updatedLineItems = [];
                    $removedCarryForwardAmount = 0;

                    foreach ($lineItems as $item) {
                        if (isset($item['is_carry_forward']) && $item['is_carry_forward']) {
                            $removedCarryForwardAmount += (float) ($item['amount'] ?? 0);
                            // Skip this item (remove carry forward)
                        } else {
                            $updatedLineItems[] = $item;
                        }
                    }

                    // Recalculate next invoice amounts
                    $baseAmount = 0;
                    foreach ($updatedLineItems as $item) {
                        $baseAmount += (float) ($item['amount'] ?? 0);
                    }

                    $gstAmount = $nextInvoice->gst_amount ?? 0;
                    // Adjust GST if needed (proportional reduction)
                    if ($removedCarryForwardAmount > 0 && $nextInvoice->total_amount > 0) {
                        $gstRatio = ($nextInvoice->gst_amount ?? 0) / $nextInvoice->total_amount;
                        $gstAmount = ($baseAmount * $gstRatio) / (1 + $gstRatio);
                    }

                    $newTotalAmount = $baseAmount + $gstAmount;

                    $nextInvoice->update([
                        'line_items' => $updatedLineItems,
                        'amount' => $baseAmount,
                        'gst_amount' => $gstAmount,
                        'total_amount' => $newTotalAmount,
                        'balance_amount' => $newTotalAmount - ($nextInvoice->paid_amount ?? 0),
                    ]);
                }
            }

            // Delete payment receipt file if exists
            if ($invoice->payment_receipt_path) {
                $receiptPath = public_path($invoice->payment_receipt_path);
                if (File::exists($receiptPath)) {
                    File::delete($receiptPath);
                }
            }

            // Delete TDS certificate file if exists
            if ($invoice->tds_certificate_path) {
                $certificatePath = public_path($invoice->tds_certificate_path);
                if (File::exists($certificatePath)) {
                    File::delete($certificatePath);
                }
            }

            // Reset all payment details including carry forward
            $invoice->update([
                'status' => 'pending',
                'payment_status' => 'pending',
                'paid_amount' => 0,
                'balance_amount' => $invoice->total_amount,
                'carry_forward_amount' => 0,
                'has_carry_forward' => false,
                'forwarded_amount' => 0,
                'forwarded_to_invoice_date' => null,
                'paid_tds_amount' => 0,
                'payment_receipt_path' => null,
                'tds_certificate_path' => null,
                'paid_at' => null,
                'paid_by' => null,
            ]);

            // Log status change
            ApplicationStatusHistory::log(
                $invoice->application_id,
                $invoice->application->status,
                $invoice->application->status,
                'admin',
                $admin->id,
                "Invoice {$invoice->invoice_number} marked as unpaid by IX Account (was {$oldStatus}/{$oldPaymentStatus})"
            );

            return back()->with('success', 'Invoice marked as unpaid successfully. All payment and carry forward amounts have been reset.');
        } catch (Exception $e) {
            Log::error('Error marking invoice as unpaid: '.$e->getMessage());

            return back()->with('error', 'Unable to mark invoice as unpaid. Please try again.');
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
            $storagePrefix = 'applications/'.$application->user_id.'/ix/'.now()->format('YmdHis');

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
            if ($application->is_active && $application->application_type === 'IX') {
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
                    $q->where('application_type', 'IX');
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
     * Display list of all invoices with filters.
     */
    public function invoices(Request $request)
    {
        try {
            $admin = $this->getCurrentAdmin();

            $query = Invoice::with(['application.user', 'generatedBy'])
                ->whereHas('application', function ($q) {
                    $q->where('application_type', 'IX');
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
                'api_identifier' => 'nixiaccoun_API_1ZT',
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

        // Common credentials across all NIXI locations (from image data)
        // CD_KEY is common for all locations - set EINVOICE_CD_KEY in .env file
        // Default '1690678' is from image data (works for Delhi, but you may need a different value for all locations)
        $commonCdKey = env('EINVOICE_CD_KEY', '1690678');
        $commonEfUsername = env('EINVOICE_EF_USERNAME', '1EC942B4-C979-4C96-95B0-7753A644955B');
        $commonEfPassword = env('EINVOICE_EF_PASSWORD', '72660132-31C1-4BD3-A712-684764AE782F');
        $commonEinvPassword = env('EINVOICE_PASSWORD', '123456789@Abc');

        // Match buyer state code with NIXI location
        // If match found, return that location's credentials
        if (! empty($buyerStateCode) && isset($nixiLocations[$buyerStateCode])) {
            $location = $nixiLocations[$buyerStateCode];

            // Get location-specific credentials from env with fallbacks
            // Format: EINVOICE_{LOCATION}_{FIELD} or EINVOICE_{FIELD}
            $locationKey = strtolower(str_replace(' ', '_', $location['location']));

            return [
                // Use common CD_KEY for all locations (can be overridden per location if needed)
                'cd_key' => env("EINVOICE_{$locationKey}_CD_KEY", $commonCdKey),
                // E-invoice username is location-specific (api_identifier from image)
                'einv_user_name' => env("EINVOICE_{$locationKey}_USERNAME", env('EINVOICE_USERNAME', $location['api_identifier'])),
                'einv_password' => env("EINVOICE_{$locationKey}_PASSWORD", $commonEinvPassword),
                // EF credentials are common across all locations
                'ef_user_name' => env("EINVOICE_{$locationKey}_EF_USERNAME", $commonEfUsername),
                'ef_password' => env("EINVOICE_{$locationKey}_EF_PASSWORD", $commonEfPassword),
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
            'cd_key' => env("EINVOICE_{$locationKey}_CD_KEY", $commonCdKey),
            // E-invoice username is location-specific (api_identifier from image)
            'einv_user_name' => env("EINVOICE_{$locationKey}_USERNAME", env('EINVOICE_USERNAME', $delhiLocation['api_identifier'])),
            'einv_password' => env("EINVOICE_{$locationKey}_PASSWORD", $commonEinvPassword),
            // EF credentials are common across all locations
            'ef_user_name' => env("EINVOICE_{$locationKey}_EF_USERNAME", $commonEfUsername),
            'ef_password' => env("EINVOICE_{$locationKey}_EF_PASSWORD", $commonEfPassword),
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
     * Public entry point for e-invoice API (e.g. used by IxMembershipInvoiceService).
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
            $apiUrl = env('EINVOICE_API_URL');

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
            $globalCdKey = env('EINVOICE_CD_KEY');

            // Determine CD_KEY source for logging
            $cdKeySource = 'default (1690678)';
            if ($locationSpecificCdKey !== null) {
                $cdKeySource = "EINVOICE_{$locationKey}_CD_KEY (location-specific)";
            } elseif ($globalCdKey !== null) {
                $cdKeySource = 'EINVOICE_CD_KEY (global)';
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
                    'ErrorMessage' => 'E-invoice API URL is not configured. Set EINVOICE_API_URL in .env.',
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
            foreach ($lineItems as $item) {
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
            foreach ($lineItems as $item) {
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

            // Get all zones from locations
            $zones = IxLocation::whereNotNull('zone')
                ->distinct()
                ->pluck('zone')
                ->filter()
                ->sort()
                ->values();

            // Get all locations/nodes
            $locations = IxLocation::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'zone', 'state', 'node_type']);

            // Get all users for user-wise selection
            $users = Registration::with('applications')
                ->whereHas('applications', function ($query) {
                    $query->where('application_type', 'IX')
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
                'filter_type' => 'required|in:all,zone,location,payment_status,user_wise,application_status',
                'zone' => 'nullable|string|required_if:filter_type,zone',
                'location_id' => 'nullable|integer|exists:ix_locations,id|required_if:filter_type,location',
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
                // Get all users with IX applications
                $query->whereHas('applications', function ($q) {
                    $q->where('application_type', 'IX')
                        ->whereNotNull('membership_id');
                });
                break;

            case 'zone':
                $zone = $request->input('zone');
                $query->whereHas('applications', function ($q) use ($zone) {
                    $q->where('application_type', 'IX')
                        ->whereNotNull('membership_id')
                        ->where(function ($appQuery) use ($zone) {
                            // Check if location zone in application_data matches
                            $appQuery->whereRaw('JSON_EXTRACT(application_data, "$.location.zone") = ?', [$zone])
                                ->orWhereRaw('JSON_EXTRACT(application_data, "$.location.zone") LIKE ?', ["%{$zone}%"]);
                        });
                });
                break;

            case 'location':
                $locationId = $request->input('location_id');
                $location = IxLocation::find($locationId);
                if ($location) {
                    $query->whereHas('applications', function ($q) use ($location) {
                        $q->where('application_type', 'IX')
                            ->whereNotNull('membership_id')
                            ->where(function ($appQuery) use ($location) {
                                // Check if location id or name in application_data matches
                                $appQuery->whereRaw('JSON_EXTRACT(application_data, "$.location.id") = ?', [$location->id])
                                    ->orWhereRaw('JSON_EXTRACT(application_data, "$.location.name") = ?', [$location->name]);
                            });
                    });
                }
                break;

            case 'payment_status':
                $paymentStatus = $request->input('payment_status');
                $query->whereHas('applications', function ($q) use ($paymentStatus) {
                    $q->where('application_type', 'IX')
                        ->whereNotNull('membership_id')
                        ->whereHas('invoices', function ($invQuery) use ($paymentStatus) {
                            if ($paymentStatus === 'paid') {
                                $invQuery->where('status', 'paid');
                            } elseif ($paymentStatus === 'pending') {
                                $invQuery->whereIn('status', ['pending', 'unpaid']);
                            } elseif ($paymentStatus === 'overdue') {
                                $invQuery->where('status', 'overdue');
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
                    $q->where('application_type', 'IX')
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
