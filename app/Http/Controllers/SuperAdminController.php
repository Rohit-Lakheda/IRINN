<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\AdminAction;
use App\Models\Application;
use App\Models\ApplicationStatusHistory;
use App\Models\GstVerification;
use App\Models\Invoice;
use App\Models\McaVerification;
use App\Models\Message;
use App\Models\NodalOfficerEmail;
use App\Models\PanVerification;
use App\Models\PaymentTransaction;
use App\Models\ProfileUpdateRequest;
use App\Models\ReactivationSetting;
use App\Models\Registration;
use App\Models\RocIecVerification;
use App\Models\Role;
use App\Models\SuperAdmin;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketMessage;
use App\Models\UdyamVerification;
use App\Models\UserKycProfile;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use PDOException;

class SuperAdminController extends Controller
{
    public function reactivationFee()
    {
        try {
            $superAdminId = session('superadmin_id');
            $superAdmin = SuperAdmin::findOrFail($superAdminId);

            $setting = ReactivationSetting::current();

            return view('superadmin.reactivation-fee', compact('superAdmin', 'setting'));
        } catch (Exception $e) {
            Log::error('Error loading reactivation fee page: '.$e->getMessage());

            return redirect()->route('superadmin.dashboard')
                ->with('error', 'Unable to load reactivation fee settings.');
        }
    }

    public function updateReactivationFee(Request $request)
    {
        try {
            $superAdminId = session('superadmin_id');
            $superAdmin = SuperAdmin::findOrFail($superAdminId);

            $validated = $request->validate([
                'fee_amount' => 'required|numeric|min:0',
                'gst_percentage' => 'required|numeric|min:0|max:100',
            ]);

            ReactivationSetting::query()->create([
                'fee_amount' => (float) $validated['fee_amount'],
                'currency' => 'INR',
                'gst_percentage' => (float) $validated['gst_percentage'],
                'updated_by' => $superAdmin->id,
            ]);

            return back()->with('success', 'Reactivation fee updated successfully.');
        } catch (Exception $e) {
            Log::error('Error updating reactivation fee: '.$e->getMessage());

            return back()->with('error', 'Unable to update reactivation fee. Please try again.');
        }
    }

    /**
     * Display the SuperAdmin dashboard.
     */
    public function index()
    {
        try {
            $superAdminId = session('superadmin_id');
            $superAdmin = SuperAdmin::findOrFail($superAdminId);

            // Recent logged in users (top 5) - using updated_at as proxy for recent activity
            $recentLoggedInUsers = Registration::orderBy('updated_at', 'desc')->take(5)->get();

            // Recent admin activities (top 5) - only login and logout activities
            $recentAdminActivities = AdminAction::with(['admin'])
                ->whereNotNull('admin_id')
                ->where(function ($query) {
                    $query->where('action_type', 'admin_login')
                        ->orWhere('action_type', 'admin_logout');
                })
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();

            // Recent messages (top 10) - interactions between admins and users
            $recentMessages = Message::with('user')
                ->orderBy('created_at', 'desc')
                ->take(10)
                ->get();

            // Get admin names for messages sent by admin
            $recentMessageIds = $recentMessages->pluck('id');
            $recentAdminActions = AdminAction::with('admin')
                ->where('action_type', 'sent_message')
                ->where('actionable_type', Message::class)
                ->whereIn('actionable_id', $recentMessageIds)
                ->get()
                ->keyBy('actionable_id');

            $adminsWithRoles = Admin::with('roles')->where('is_super_admin', false)->get();
            $roleSlugs = ['helpdesk', 'hostmaster', 'billing'];
            $roles = Role::whereIn('slug', $roleSlugs)->get()->keyBy('slug');

            $totalApplications = Application::count();
            $fullyApproved = Application::where(function ($query) {
                $query->where('status', 'approved')
                    ->orWhere('status', 'payment_verified');
            })
                ->count();

            $ixProcessorApproved = $ixProcessorPending = 0;
            $ixLegalApproved = $ixLegalPending = 0;
            $ixHeadApproved = $ixHeadPending = 0;
            $ceoApproved = $ceoPending = 0;
            $nodalOfficerApproved = $nodalOfficerPending = 0;
            $ixTechTeamApproved = $ixTechTeamPending = 0;
            $ixAccountApproved = $ixAccountPending = 0;
            $totalIxPoints = $edgeIxPoints = $metroIxPoints = 0;

            // Approved Applications with payment verification
            $approvedApplications = Application::whereIn('status', ['approved', 'payment_verified'])
                ->count();
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
                ->where('is_active', true)
                ->orderBy('updated_at', 'desc')
                ->take(10)
                ->get();

            // Grievance Tracking
            $openGrievances = Ticket::whereIn('status', ['open', 'assigned', 'in_progress'])->count();
            $pendingGrievances = Ticket::where('status', 'assigned')->count();
            $closedGrievances = Ticket::whereIn('status', ['resolved', 'closed'])->count();

            // Payment Summary - This Month
            $currentMonthStart = now('Asia/Kolkata')->startOfMonth();
            $currentMonthEnd = now('Asia/Kolkata')->endOfMonth();

            // Total invoices generated this month (split by purpose) - exclude cancelled and credit_note
            $serviceInvoicesThisMonth = Invoice::whereBetween('invoice_date', [$currentMonthStart, $currentMonthEnd])
                ->where('status', '!=', 'cancelled')
                ->where('status', '!=', 'credit_note')
                ->where('invoice_purpose', 'service')
                ->count();

            $reactivationInvoicesThisMonth = Invoice::whereBetween('invoice_date', [$currentMonthStart, $currentMonthEnd])
                ->where('status', '!=', 'cancelled')
                ->where('status', '!=', 'credit_note')
                ->where('invoice_purpose', 'reactivation')
                ->count();

            $invoicesThisMonth = $serviceInvoicesThisMonth + $reactivationInvoicesThisMonth;

            // Total amount of invoices generated this month (split by purpose) - exclude cancelled and credit_note
            $serviceInvoicedThisMonth = Invoice::whereBetween('invoice_date', [$currentMonthStart, $currentMonthEnd])
                ->where('status', '!=', 'cancelled')
                ->where('status', '!=', 'credit_note')
                ->where('invoice_purpose', 'service')
                ->sum('total_amount');

            $reactivationInvoicedThisMonth = Invoice::whereBetween('invoice_date', [$currentMonthStart, $currentMonthEnd])
                ->where('status', '!=', 'cancelled')
                ->where('status', '!=', 'credit_note')
                ->where('invoice_purpose', 'reactivation')
                ->sum('total_amount');

            $totalInvoicedThisMonth = $serviceInvoicedThisMonth + $reactivationInvoicedThisMonth;

            // Application payments (initial/one-time) collected this month - separate calculation
            // Primary method: Sum of PaymentVerificationLog where verified_at is this month and type is 'initial'
            $applicationPaymentsFromVerification = \App\Models\PaymentVerificationLog::whereBetween('verified_at', [$currentMonthStart, $currentMonthEnd])
                ->where('verification_type', 'initial') // Only application fees
                ->sum(DB::raw('COALESCE(amount_captured, amount, 0)'));

            // Secondary method: Sum of PaymentTransaction for application payments (those without invoice pattern)
            $applicationPaymentsFromTransactions = PaymentTransaction::where('payment_status', 'success')
                ->whereBetween('created_at', [$currentMonthStart, $currentMonthEnd])
                ->whereNotNull('application_id')
                ->where(function ($query) {
                    // Exclude invoice payments (those with invoice patterns)
                    // Application payments don't have invoice patterns in product_info
                    $query->where(function ($q) {
                        $q->whereNull('product_info')
                            ->orWhere(function ($q2) {
                                $q2->where('product_info', 'NOT LIKE', 'INV-%')
                                    ->where('product_info', 'NOT LIKE', 'BULK-%')
                                    ->where('product_info', 'NOT LIKE', '%Invoice%');
                            });
                    });
                })
                ->sum('amount');

            // Use verification logs as primary source (most accurate)
            $applicationPaymentsThisMonth = $applicationPaymentsFromVerification > 0
                ? $applicationPaymentsFromVerification
                : $applicationPaymentsFromTransactions;

            // Invoice payments (recurring) collected this month - Amount Received
            // This should match the calculation in AdminController for "Amount received"
            // Sum of paid_amount from invoices where paid_at is in this month
            // This is the most accurate as it reflects actual payments received this month
            $serviceCollectedThisMonth = Invoice::whereBetween('paid_at', [$currentMonthStart, $currentMonthEnd])
                ->where('status', '!=', 'cancelled')
                ->where('status', '!=', 'credit_note')
                ->whereIn('payment_status', ['paid', 'partial'])
                ->where('invoice_purpose', 'service')
                ->sum(DB::raw('COALESCE(paid_amount, 0)'));

            $reactivationCollectedThisMonth = Invoice::whereBetween('paid_at', [$currentMonthStart, $currentMonthEnd])
                ->where('status', '!=', 'cancelled')
                ->where('status', '!=', 'credit_note')
                ->whereIn('payment_status', ['paid', 'partial'])
                ->where('invoice_purpose', 'reactivation')
                ->sum(DB::raw('COALESCE(paid_amount, 0)'));

            $totalCollectedThisMonth = (float) $serviceCollectedThisMonth + (float) $reactivationCollectedThisMonth;

            // Pending amount for invoices generated this month
            // Calculate as (total_amount - paid_amount) for December invoices only
            $pendingInvoicesThisMonth = Invoice::whereBetween('invoice_date', [$currentMonthStart, $currentMonthEnd])
                ->whereIn('payment_status', ['pending', 'partial'])
                ->where('status', '!=', 'cancelled')
                ->where('status', '!=', 'credit_note')
                ->where('status', '!=', 'paid') // Exclude invoices marked as paid
                ->get();

            $totalPendingAmountThisMonth = $pendingInvoicesThisMonth->sum(function ($invoice) {
                // Calculate balance as total_amount - paid_amount
                // This ensures accuracy even if balance_amount field is incorrect
                $paidAmount = (float) ($invoice->paid_amount ?? 0);
                $totalAmount = (float) $invoice->total_amount;

                // If fully paid (paid_amount >= total_amount), return 0
                if ($paidAmount >= $totalAmount && $totalAmount > 0) {
                    return 0;
                }

                // Return the calculated balance
                return max(0, $totalAmount - $paidAmount);
            });

            // Also calculate all-time pending amount for reference
            $pendingInvoicesAllTime = Invoice::whereIn('payment_status', ['pending', 'partial'])
                ->where('status', '!=', 'cancelled')
                ->where('status', '!=', 'credit_note')
                ->where('status', '!=', 'paid') // Exclude invoices marked as paid
                ->get();

            $totalPendingAmount = $pendingInvoicesAllTime->sum(function ($invoice) {
                // Calculate balance as total_amount - paid_amount
                $paidAmount = (float) ($invoice->paid_amount ?? 0);
                $totalAmount = (float) $invoice->total_amount;

                // If fully paid (paid_amount >= total_amount), return 0
                if ($paidAmount >= $totalAmount && $totalAmount > 0) {
                    return 0;
                }

                // Return the calculated balance
                return max(0, $totalAmount - $paidAmount);
            });

            // Use this month's pending amount for the payment summary display
            $totalPendingAmount = $totalPendingAmountThisMonth;

            // Total overdue amount - exclude cancelled and credit_note
            $totalOverdueAmount = Invoice::where('status', 'overdue')
                ->where('status', '!=', 'cancelled')
                ->where('status', '!=', 'credit_note')
                ->sum('balance_amount');

            // Partial payments this month - exclude cancelled and credit_note
            $partialPaymentsThisMonth = Invoice::whereBetween('invoice_date', [$currentMonthStart, $currentMonthEnd])
                ->where('payment_status', 'partial')
                ->where('status', '!=', 'cancelled')
                ->where('status', '!=', 'credit_note')
                ->count();

            // Total invoices by status (all time)
            $totalInvoices = Invoice::where('status', '!=', 'cancelled')->where('status', '!=', 'credit_note')->count();
            $paidInvoices = Invoice::where('payment_status', 'paid')->where('status', '!=', 'cancelled')->where('status', '!=', 'credit_note')->count();
            $pendingInvoices = Invoice::where('payment_status', 'pending')->where('status', '!=', 'cancelled')->where('status', '!=', 'credit_note')->count();
            $partialInvoices = Invoice::where('payment_status', 'partial')->where('status', '!=', 'cancelled')->where('status', '!=', 'credit_note')->count();
            $overdueInvoices = Invoice::where('status', 'overdue')->where('status', '!=', 'cancelled')->where('status', '!=', 'credit_note')->count();

            return view('superadmin.dashboard', compact(
                'superAdmin',
                'recentLoggedInUsers',
                'recentAdminActivities',
                'recentMessages',
                'recentAdminActions',
                'adminsWithRoles',
                'roles',
                'roleSlugs',
                'totalApplications',
                'fullyApproved',
                'approvedApplications',
                'approvedApplicationsWithPayment',
                'totalMembers',
                'activeMembers',
                'disconnectedMembers',
                'recentLiveMembers',
                'openGrievances',
                'pendingGrievances',
                'closedGrievances',
                // New IX Workflow Roles
                'ixProcessorApproved',
                'ixProcessorPending',
                'ixLegalApproved',
                'ixLegalPending',
                'ixHeadApproved',
                'ixHeadPending',
                'ceoApproved',
                'ceoPending',
                'nodalOfficerApproved',
                'nodalOfficerPending',
                'ixTechTeamApproved',
                'ixTechTeamPending',
                'ixAccountApproved',
                'ixAccountPending',
                // IX Points
                'totalIxPoints',
                'edgeIxPoints',
                'metroIxPoints',
                // Payment Summary
                'invoicesThisMonth',
                'totalInvoicedThisMonth',
                'totalCollectedThisMonth',
                'serviceInvoicesThisMonth',
                'reactivationInvoicesThisMonth',
                'serviceInvoicedThisMonth',
                'reactivationInvoicedThisMonth',
                'serviceCollectedThisMonth',
                'reactivationCollectedThisMonth',
                'applicationPaymentsThisMonth',
                'totalPendingAmount',
                'totalOverdueAmount',
                'partialPaymentsThisMonth',
                'totalInvoices',
                'paidInvoices',
                'pendingInvoices',
                'partialInvoices',
                'overdueInvoices'
            ));
        } catch (QueryException $e) {
            Log::error('Database error loading SuperAdmin dashboard: '.$e->getMessage());
            abort(503, 'Database connection error. Please try again later.');
        } catch (PDOException $e) {
            Log::error('PDO error loading SuperAdmin dashboard: '.$e->getMessage());
            abort(503, 'Database connection error. Please try again later.');
        } catch (Exception $e) {
            Log::error('Error loading SuperAdmin dashboard: '.$e->getMessage());
            abort(500, 'Unable to load dashboard. Please try again later.');
        }
    }

    /**
     * Display all users.
     */
    public function users(Request $request)
    {
        try {
            $filter = $request->get('filter', 'all'); // all, active, disconnected

            $query = Registration::with(['messages', 'profileUpdateRequests', 'applications']);

            // Apply member filter if provided (based on is_active for Live/Not Live)
            if ($filter === 'active') {
                // Live members: is_active = true
                $query->whereHas('applications', function ($q) {
                    $q->whereNotNull('membership_id')
                        ->where('is_active', true);
                });
            } elseif ($filter === 'disconnected') {
                // Not live members: is_active = false
                $query->whereHas('applications', function ($q) {
                    $q->whereNotNull('membership_id')
                        ->where('is_active', false);
                });
            }

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

            $users = $query->latest()->paginate(20)->withQueryString();

            return view('superadmin.users.index', compact('users', 'filter'));
        } catch (QueryException $e) {
            Log::error('Database error loading users: '.$e->getMessage());
            abort(503, 'Database connection error. Please try again later.');
        } catch (PDOException $e) {
            Log::error('PDO error loading users: '.$e->getMessage());
            abort(503, 'Database connection error. Please try again later.');
        } catch (Exception $e) {
            Log::error('Error loading users: '.$e->getMessage());

            return redirect()->route('superadmin.dashboard')
                ->with('error', 'Unable to load users. Please try again.');
        }
    }

    /**
     * Display user details with full history.
     */
    public function showUser($id)
    {
        try {
            $user = Registration::with([
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

            $ixApplications = Application::where('user_id', $id)
                ->where('application_type', 'IRINN')
                ->get();

            $paymentTransactions = PaymentTransaction::whereIn('application_id', $ixApplications->pluck('id'))
                ->latest()
                ->get()
                ->keyBy('application_id');

            // Get all admin actions related to this user
            $adminActions = AdminAction::where('actionable_type', Registration::class)
                ->where('actionable_id', $id)
                ->orWhere(function ($query) use ($user) {
                    $query->where('actionable_type', ProfileUpdateRequest::class)
                        ->whereIn('actionable_id', $user->profileUpdateRequests->pluck('id'));
                })
                ->orWhere(function ($query) use ($user) {
                    $query->where('actionable_type', Message::class)
                        ->whereIn('actionable_id', $user->messages->pluck('id'));
                })
                ->with(['admin', 'superAdmin'])
                ->latest()
                ->get();

            return view('superadmin.users.show', compact('user', 'adminActions', 'ixApplications', 'paymentTransactions'));
        } catch (QueryException $e) {
            Log::error('Database error loading user details: '.$e->getMessage());
            abort(503, 'Database connection error. Please try again later.');
        } catch (PDOException $e) {
            Log::error('PDO error loading user details: '.$e->getMessage());
            abort(503, 'Database connection error. Please try again later.');
        } catch (Exception $e) {
            Log::error('Error loading user details: '.$e->getMessage());

            return redirect()->route('superadmin.users')
                ->with('error', 'User not found.');
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
            $superAdminId = session('superadmin_id');

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
            AdminAction::create([
                'admin_id' => null,
                'superadmin_id' => $superAdminId,
                'action_type' => 'deleted_user',
                'actionable_type' => null,
                'actionable_id' => null,
                'description' => "Deleted user: {$userName} (Registration ID: {$userRegistrationId})",
                'metadata' => ['deleted_user_id' => $userId, 'deleted_user_name' => $userName, 'deleted_registration_id' => $userRegistrationId],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            // Delete the user
            $user->delete();

            DB::commit();

            return redirect()->route('superadmin.users')
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
     * Display all admins.
     */
    public function admins(Request $request)
    {
        try {
            $query = Admin::with('roles');

            // Search functionality
            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('admin_id', 'like', "%{$search}%")
                        ->orWhereHas('roles', function ($roleQuery) use ($search) {
                            $roleQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('slug', 'like', "%{$search}%");
                        });
                });
            }

            $admins = $query->latest()->paginate(20)->withQueryString();

            return view('superadmin.admins.index', compact('admins'));
        } catch (QueryException $e) {
            Log::error('Database error loading admins: '.$e->getMessage());
            abort(503, 'Database connection error. Please try again later.');
        } catch (PDOException $e) {
            Log::error('PDO error loading admins: '.$e->getMessage());
            abort(503, 'Database connection error. Please try again later.');
        } catch (Exception $e) {
            Log::error('Error loading admins: '.$e->getMessage());

            return redirect()->route('superadmin.dashboard')
                ->with('error', 'Unable to load admins. Please try again.');
        }
    }

    /**
     * Display admin details.
     */
    public function showAdmin($id)
    {
        try {
            $admin = Admin::with('roles')->findOrFail($id);

            // Get recent login/logout activities (top 10)
            $recentActivities = AdminAction::where('admin_id', $admin->id)
                ->where(function ($query) {
                    $query->where('action_type', 'admin_login')
                        ->orWhere('action_type', 'admin_logout');
                })
                ->orderBy('created_at', 'desc')
                ->take(10)
                ->get();

            // Get messages sent by this admin
            $messages = Message::whereHas('adminActions', function ($query) use ($admin) {
                $query->where('admin_id', $admin->id)
                    ->where('action_type', 'sent_message');
            })
                ->with('user')
                ->orderBy('created_at', 'desc')
                ->take(20)
                ->get();

            // Get all admin actions for this admin (for activity count)
            $totalActions = AdminAction::where('admin_id', $admin->id)->count();

            return view('superadmin.admins.show', compact('admin', 'recentActivities', 'messages', 'totalActions'));
        } catch (QueryException $e) {
            Log::error('Database error loading admin details: '.$e->getMessage());
            abort(503, 'Database connection error. Please try again later.');
        } catch (PDOException $e) {
            Log::error('PDO error loading admin details: '.$e->getMessage());
            abort(503, 'Database connection error. Please try again later.');
        } catch (Exception $e) {
            Log::error('Error loading admin details: '.$e->getMessage());

            return redirect()->route('superadmin.admins')
                ->with('error', 'Admin not found.');
        }
    }

    /**
     * Show form to create new admin.
     */
    public function createAdmin()
    {
        try {
            // Only show three workflow roles for application processing:
            // Helpdesk, Hostmaster, Billing.
            $roles = Role::where('is_active', true)
                ->whereIn('slug', ['helpdesk', 'hostmaster', 'billing'])
                ->orderBy('name')
                ->get();

            return view('superadmin.admins.create', compact('roles'));
        } catch (QueryException $e) {
            Log::error('Database error loading create admin form: '.$e->getMessage());
            abort(503, 'Database connection error. Please try again later.');
        } catch (PDOException $e) {
            Log::error('PDO error loading create admin form: '.$e->getMessage());
            abort(503, 'Database connection error. Please try again later.');
        } catch (Exception $e) {
            Log::error('Error loading create admin form: '.$e->getMessage());

            return redirect()->route('superadmin.admins')
                ->with('error', 'Unable to load form. Please try again.');
        }
    }

    /**
     * Check if Employee ID already exists.
     */
    public function checkEmployeeId(Request $request)
    {
        try {
            $request->validate([
                'employee_id' => 'required|string',
            ]);

            $exists = Admin::where('admin_id', $request->input('employee_id'))->exists();

            return response()->json([
                'exists' => $exists,
            ]);
        } catch (Exception $e) {
            Log::error('Error checking employee ID: '.$e->getMessage());

            return response()->json([
                'exists' => false,
                'error' => 'Error checking employee ID',
            ], 500);
        }
    }

    /**
     * Store new admin.
     */
    public function storeAdmin(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'employee_id' => 'required|string|max:255|unique:admins,admin_id',
                'email' => 'required|email|unique:admins,email',
                'password' => 'required|string|min:8|confirmed',
                'roles' => 'nullable|array',
                'roles.*' => 'exists:roles,id',
            ], [
                'name.required' => 'Name is required.',
                'employee_id.required' => 'Employee ID is required.',
                'employee_id.unique' => 'This Employee ID is already registered. Please use a different Employee ID.',
                'email.required' => 'Email is required.',
                'email.unique' => 'This email is already registered.',
                'password.required' => 'Password is required.',
                'password.min' => 'Password must be at least 8 characters.',
                'password.confirmed' => 'Password confirmation does not match.',
            ]);

            $admin = Admin::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'admin_id' => $validated['employee_id'],
                'is_super_admin' => false,
                'is_active' => true,
            ]);

            // Assign roles
            if (! empty($validated['roles'])) {
                $admin->roles()->attach($validated['roles']);
            }

            // Log action
            AdminAction::logSuperAdmin(
                session('superadmin_id'),
                'created_admin',
                $admin,
                "Created new admin: {$admin->name}",
                ['roles' => $admin->roles->pluck('name')->toArray()]
            );

            return redirect()->route('superadmin.admins')
                ->with('success', 'Admin created successfully!');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (QueryException $e) {
            Log::error('Database error creating admin: '.$e->getMessage());

            return back()->with('error', 'Database connection error. Please try again later.')
                ->withInput();
        } catch (PDOException $e) {
            Log::error('PDO error creating admin: '.$e->getMessage());

            return back()->with('error', 'Database connection error. Please try again later.')
                ->withInput();
        } catch (Exception $e) {
            Log::error('Error creating admin: '.$e->getMessage());

            return redirect()->route('superadmin.admins')
                ->with('error', 'An error occurred while creating admin. Please try again.');
        }
    }

    /**
     * Show form to edit admin.
     */
    public function editAdmin($id)
    {
        try {
            $admin = Admin::with('roles')->findOrFail($id);
            $roles = Role::where('is_active', true)->get();

            return view('superadmin.admins.edit', compact('admin', 'roles'));
        } catch (QueryException $e) {
            Log::error('Database error loading edit admin form: '.$e->getMessage());
            abort(503, 'Database connection error. Please try again later.');
        } catch (PDOException $e) {
            Log::error('PDO error loading edit admin form: '.$e->getMessage());
            abort(503, 'Database connection error. Please try again later.');
        } catch (Exception $e) {
            Log::error('Error loading edit admin form: '.$e->getMessage());

            return redirect()->route('superadmin.admins')
                ->with('error', 'Admin not found.');
        }
    }

    /**
     * Update admin.
     */
    public function updateAdmin(Request $request, $id)
    {
        try {
            $admin = Admin::findOrFail($id);

            $rules = [
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:admins,email,'.$id,
                'roles' => 'nullable|array',
                'roles.*' => 'exists:roles,id',
                'is_active' => 'boolean',
            ];

            if ($request->filled('password')) {
                $rules['password'] = 'required|string|min:8|confirmed';
            }

            $validated = $request->validate($rules, [
                'name.required' => 'Name is required.',
                'email.required' => 'Email is required.',
                'email.unique' => 'This email is already registered.',
                'password.required' => 'Password is required.',
                'password.min' => 'Password must be at least 8 characters.',
                'password.confirmed' => 'Password confirmation does not match.',
            ]);

            $admin->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'is_active' => $request->has('is_active') ? true : false,
            ]);

            // Update password if provided
            if (! empty($validated['password'])) {
                $admin->password = Hash::make($validated['password']);
                $admin->save();
            }

            // Update roles
            $admin->roles()->sync($validated['roles'] ?? []);

            // Log action
            AdminAction::logSuperAdmin(
                session('superadmin_id'),
                'updated_admin',
                $admin,
                "Updated admin: {$admin->name}",
                ['roles' => $admin->roles->pluck('name')->toArray()]
            );

            return redirect()->route('superadmin.admins')
                ->with('success', 'Admin updated successfully!');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (QueryException $e) {
            Log::error('Database error updating admin: '.$e->getMessage());

            return back()->with('error', 'Database connection error. Please try again later.')
                ->withInput();
        } catch (PDOException $e) {
            Log::error('PDO error updating admin: '.$e->getMessage());

            return back()->with('error', 'Database connection error. Please try again later.')
                ->withInput();
        } catch (Exception $e) {
            Log::error('Error updating admin: '.$e->getMessage());

            return redirect()->route('superadmin.admins')
                ->with('error', 'An error occurred while updating admin. Please try again.');
        }
    }

    /**
     * Show form to edit admin details (name, email, password - no roles).
     */
    public function editAdminDetails($id)
    {
        try {
            $admin = Admin::findOrFail($id);

            return view('superadmin.admins.edit-details', compact('admin'));
        } catch (QueryException $e) {
            Log::error('Database error loading edit admin details form: '.$e->getMessage());
            abort(503, 'Database connection error. Please try again later.');
        } catch (PDOException $e) {
            Log::error('PDO error loading edit admin details form: '.$e->getMessage());
            abort(503, 'Database connection error. Please try again later.');
        } catch (Exception $e) {
            Log::error('Error loading edit admin details form: '.$e->getMessage());

            return redirect()->route('superadmin.admins')
                ->with('error', 'Admin not found.');
        }
    }

    /**
     * Update admin details (name, email, password - no roles).
     */
    public function updateAdminDetails(Request $request, $id)
    {
        try {
            $admin = Admin::findOrFail($id);

            $rules = [
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:admins,email,'.$id,
            ];

            if ($request->filled('password')) {
                $rules['password'] = 'required|string|min:8|confirmed';
            }

            $validated = $request->validate($rules, [
                'name.required' => 'Name is required.',
                'email.required' => 'Email is required.',
                'email.unique' => 'This email is already registered.',
                'password.required' => 'Password is required.',
                'password.min' => 'Password must be at least 8 characters.',
                'password.confirmed' => 'Password confirmation does not match.',
            ]);

            $admin->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
            ]);

            // Update password if provided
            if (! empty($validated['password'] ?? null)) {
                $admin->password = Hash::make($validated['password']);
                $admin->save();
            }

            // Log action
            AdminAction::logSuperAdmin(
                session('superadmin_id'),
                'updated_admin_details',
                $admin,
                "Updated admin details: {$admin->name}",
                []
            );

            return redirect()->route('superadmin.admins')
                ->with('success', 'Admin details updated successfully!');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (QueryException $e) {
            Log::error('Database error updating admin details: '.$e->getMessage());

            return back()->with('error', 'Database connection error. Please try again later.')
                ->withInput();
        } catch (PDOException $e) {
            Log::error('PDO error updating admin details: '.$e->getMessage());

            return back()->with('error', 'Database connection error. Please try again later.')
                ->withInput();
        } catch (Exception $e) {
            Log::error('Error updating admin details: '.$e->getMessage());

            return redirect()->route('superadmin.admins')
                ->with('error', 'An error occurred while updating admin details. Please try again.');
        }
    }

    /**
     * Show form to edit admin type/roles only.
     */
    public function editAdminType($id)
    {
        try {
            $admin = Admin::with('roles')->findOrFail($id);
            // Only show three workflow roles for application processing:
            // Helpdesk, Hostmaster, Billing.
            $roles = Role::where('is_active', true)
                ->whereIn('slug', ['helpdesk', 'hostmaster', 'billing'])
                ->orderBy('name')
                ->get();

            return view('superadmin.admins.edit-type', compact('admin', 'roles'));
        } catch (QueryException $e) {
            Log::error('Database error loading edit admin type form: '.$e->getMessage());
            abort(503, 'Database connection error. Please try again later.');
        } catch (PDOException $e) {
            Log::error('PDO error loading edit admin type form: '.$e->getMessage());
            abort(503, 'Database connection error. Please try again later.');
        } catch (Exception $e) {
            Log::error('Error loading edit admin type form: '.$e->getMessage());

            return redirect()->route('superadmin.admins')
                ->with('error', 'Admin not found.');
        }
    }

    /**
     * Update admin type/roles only.
     */
    public function updateAdminType(Request $request, $id)
    {
        try {
            $admin = Admin::findOrFail($id);

            $validated = $request->validate([
                'roles' => 'nullable|array',
                'roles.*' => 'exists:roles,id',
            ], [
                'roles.array' => 'Roles must be an array.',
                'roles.*.exists' => 'One or more selected roles are invalid.',
            ]);

            // Update roles
            $admin->roles()->sync($validated['roles'] ?? []);

            // Log action
            AdminAction::logSuperAdmin(
                session('superadmin_id'),
                'updated_admin_type',
                $admin,
                "Updated admin roles: {$admin->name}",
                ['roles' => $admin->roles->pluck('name')->toArray()]
            );

            return redirect()->route('superadmin.admins')
                ->with('success', 'Admin roles updated successfully!');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (QueryException $e) {
            Log::error('Database error updating admin type: '.$e->getMessage());

            return back()->with('error', 'Database connection error. Please try again later.')
                ->withInput();
        } catch (PDOException $e) {
            Log::error('PDO error updating admin type: '.$e->getMessage());

            return back()->with('error', 'Database connection error. Please try again later.')
                ->withInput();
        } catch (Exception $e) {
            Log::error('Error updating admin type: '.$e->getMessage());

            return redirect()->route('superadmin.admins')
                ->with('error', 'An error occurred while updating admin roles. Please try again.');
        }
    }

    /**
     * Toggle admin status (activate/deactivate).
     */
    public function toggleAdminStatus($id)
    {
        try {
            $admin = Admin::findOrFail($id);

            // Prevent deactivating super admin
            if ($admin->is_super_admin) {
                return redirect()->route('superadmin.admins')
                    ->with('error', 'Cannot deactivate super admin account.');
            }

            $oldStatus = $admin->is_active;
            $admin->is_active = ! $admin->is_active;
            $admin->save();

            // Log action
            AdminAction::logSuperAdmin(
                session('superadmin_id'),
                $admin->is_active ? 'activated_admin' : 'deactivated_admin',
                $admin,
                ($admin->is_active ? 'Activated' : 'Deactivated')." admin: {$admin->name}",
                ['old_status' => $oldStatus, 'new_status' => $admin->is_active]
            );

            return redirect()->route('superadmin.admins')
                ->with('success', "Admin {$admin->name} has been ".($admin->is_active ? 'activated' : 'deactivated').' successfully!');
        } catch (QueryException $e) {
            Log::error('Database error toggling admin status: '.$e->getMessage());

            return redirect()->route('superadmin.admins')
                ->with('error', 'Database connection error. Please try again later.');
        } catch (PDOException $e) {
            Log::error('PDO error toggling admin status: '.$e->getMessage());

            return redirect()->route('superadmin.admins')
                ->with('error', 'Database connection error. Please try again later.');
        } catch (Exception $e) {
            Log::error('Error toggling admin status: '.$e->getMessage());

            return redirect()->route('superadmin.admins')
                ->with('error', 'An error occurred while updating admin status. Please try again.');
        }
    }

    /**
     * Display all messages with search functionality.
     */
    public function messages(Request $request)
    {
        try {
            $query = Message::with(['user']);

            // Search functionality
            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('subject', 'like', "%{$search}%")
                        ->orWhere('message', 'like', "%{$search}%")
                        ->orWhere('user_reply', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('fullname', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        })
                        ->orWhereHas('adminActions', function ($adminQuery) use ($search) {
                            $adminQuery->where('action_type', 'sent_message')
                                ->whereHas('admin', function ($adminNameQuery) use ($search) {
                                    $adminNameQuery->where('name', 'like', "%{$search}%")
                                        ->orWhere('email', 'like', "%{$search}%");
                                });
                        });
                });
            }

            // Filter by sent_by (include 'system' as an option)
            if ($request->filled('sent_by')) {
                $query->where('sent_by', $request->input('sent_by'));
            }

            $messages = $query->orderBy('created_at', 'desc')->paginate(20);

            // Get admin names for messages sent by admin
            $messageIds = $messages->pluck('id');
            $adminActions = AdminAction::with('admin')
                ->where('action_type', 'sent_message')
                ->where('actionable_type', Message::class)
                ->whereIn('actionable_id', $messageIds)
                ->get()
                ->keyBy('actionable_id');

            return view('superadmin.messages.index', compact('messages', 'adminActions'));
        } catch (QueryException $e) {
            Log::error('Database error loading messages: '.$e->getMessage());
            abort(503, 'Database connection error. Please try again later.');
        } catch (PDOException $e) {
            Log::error('PDO error loading messages: '.$e->getMessage());
            abort(503, 'Database connection error. Please try again later.');
        } catch (Exception $e) {
            Log::error('Error loading messages: '.$e->getMessage());
            abort(500, 'Unable to load messages. Please try again later.');
        }
    }

    /**
     * Display message details.
     */
    public function showMessage($id)
    {
        try {
            $message = Message::with('user')->findOrFail($id);

            // Get admin who sent the message (if sent by admin)
            $adminAction = null;
            if ($message->sent_by === 'admin') {
                $adminAction = AdminAction::with('admin')
                    ->where('action_type', 'sent_message')
                    ->where('actionable_type', Message::class)
                    ->where('actionable_id', $message->id)
                    ->first();
            }

            return view('superadmin.messages.show', compact('message', 'adminAction'));
        } catch (QueryException $e) {
            Log::error('Database error loading message details: '.$e->getMessage());
            abort(503, 'Database connection error. Please try again later.');
        } catch (PDOException $e) {
            Log::error('PDO error loading message details: '.$e->getMessage());
            abort(503, 'Database connection error. Please try again later.');
        } catch (Exception $e) {
            Log::error('Error loading message details: '.$e->getMessage());
            abort(500, 'Unable to load message details. Please try again later.');
        }
    }

    /**
     * Accept payment for IRINN application (Super Admin action).
     */
    public function acceptPayment($applicationId)
    {
        try {
            $superAdminId = session('superadmin_id');
            $superAdmin = SuperAdmin::findOrFail($superAdminId);

            $application = Application::with('user')
                ->where('application_type', 'IRINN')
                ->findOrFail($applicationId);

            // Check if payment is already accepted
            $paymentTransaction = PaymentTransaction::where('application_id', $applicationId)->first();

            DB::beginTransaction();

            $amount = 0;
            $applicationData = $application->application_data ?? [];
            $part5 = $applicationData['part5'] ?? [];

            if (isset($part5['total_amount'])) {
                $amount = (float) $part5['total_amount'];
            } elseif (isset($applicationData['payment']['total_amount'])) {
                $amount = (float) $applicationData['payment']['total_amount'];
            } elseif (isset($applicationData['payment']['amount'])) {
                $amount = (float) $applicationData['payment']['amount'];
            } else {
                $amount = 1000.00;
            }

            // Generate a unique transaction ID for manual approval
            $transactionId = 'MANUAL-'.time().'-'.rand(1000, 9999);
            $paymentId = 'approved-by-superadmin-'.$superAdminId.'-'.time();

            // Update or create payment transaction
            if ($paymentTransaction) {
                $paymentTransaction->update([
                    'payment_status' => 'success',
                    'payment_id' => $paymentId,
                    'transaction_id' => $transactionId,
                    'amount' => $amount, // Update amount in case it changed
                    'response_message' => 'Payment accepted by Super Admin',
                ]);
            } else {
                // Create new payment transaction
                $paymentTransaction = PaymentTransaction::create([
                    'user_id' => $application->user_id,
                    'application_id' => $applicationId,
                    'transaction_id' => $transactionId,
                    'payment_status' => 'success',
                    'payment_id' => $paymentId,
                    'payment_mode' => 'manual',
                    'amount' => $amount,
                    'currency' => 'INR',
                    'product_info' => 'IRINN Application Fee',
                    'response_message' => 'Payment accepted by Super Admin',
                ]);
            }

            $oldStatus = $application->status;
            $application->update([
                'status' => 'helpdesk',
                'submitted_at' => $application->submitted_at ?? now('Asia/Kolkata'),
            ]);

            ApplicationStatusHistory::log(
                $application->id,
                $oldStatus,
                'helpdesk',
                'superadmin',
                $superAdminId,
                'Payment accepted by Super Admin - IRINN application submitted for helpdesk review'
            );

            AdminAction::logSuperAdmin(
                $superAdminId,
                'accepted_payment',
                $application,
                "Accepted payment for IRINN application {$application->application_id}",
                [
                    'application_id' => $application->application_id,
                    'user_id' => $application->user_id,
                    'payment_id' => $paymentId ?? 'approved-by-superadmin',
                    'transaction_id' => $transactionId ?? 'MANUAL-'.time(),
                ]
            );

            Message::create([
                'user_id' => $application->user_id,
                'subject' => 'Payment Accepted - Application Submitted',
                'message' => "Your payment for application {$application->application_id} has been accepted by Super Admin. Your IRINN application is now under review.",
                'is_read' => false,
                'sent_by' => 'superadmin',
            ]);

            DB::commit();

            return redirect()->route('superadmin.users.show', $application->user_id)
                ->with('success', 'Payment accepted successfully! Application has been submitted for review.');
        } catch (QueryException $e) {
            DB::rollBack();
            Log::error('Database error accepting payment: '.$e->getMessage());

            return back()->with('error', 'Database connection error. Please try again later.');
        } catch (PDOException $e) {
            DB::rollBack();
            Log::error('PDO error accepting payment: '.$e->getMessage());

            return back()->with('error', 'Database connection error. Please try again later.');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error accepting payment: '.$e->getMessage());

            return back()->with('error', 'An error occurred while accepting payment. Please try again.');
        }
    }

    /**
     * Display list of all IRINN-related invoices.
     */
    public function invoices(Request $request)
    {
        try {
            $superAdminId = session('superadmin_id');
            $superAdmin = SuperAdmin::findOrFail($superAdminId);

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
                                ->orWhereHas('user', function ($userQuery) use ($search) {
                                    $userQuery->where('fullname', 'like', "%{$search}%")
                                        ->orWhere('email', 'like', "%{$search}%");
                                });
                        });
                });
            }

            // Filter by status
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

            $invoices = $query->latest('invoice_date')->paginate(20)->withQueryString();

            return view('superadmin.invoices.index', compact('invoices'));
        } catch (Exception $e) {
            Log::error('Error loading invoices: '.$e->getMessage());

            return redirect()->route('superadmin.dashboard')
                ->with('error', 'Unable to load invoices.');
        }
    }

    /**
     * Display invoice details.
     */
    public function showInvoice($id)
    {
        try {
            $superAdminId = session('superadmin_id');
            $superAdmin = SuperAdmin::findOrFail($superAdminId);

            $invoice = Invoice::with(['application.user', 'generatedBy'])
                ->whereHas('application', function ($q) {
                    $q->where('application_type', 'IRINN');
                })
                ->findOrFail($id);

            return view('superadmin.invoices.show', compact('invoice'));
        } catch (Exception $e) {
            Log::error('Error loading invoice details: '.$e->getMessage());

            return redirect()->route('superadmin.invoices.index')
                ->with('error', 'Unable to load invoice details.');
        }
    }

    /**
     * Download invoice PDF or credit note PDF.
     * Query parameter 'type' can be 'invoice' or 'credit_note' to specify which PDF to download.
     */
    public function downloadInvoice($id, Request $request)
    {
        try {
            $superAdminId = session('superadmin_id');
            $superAdmin = SuperAdmin::findOrFail($superAdminId);

            $invoice = Invoice::with(['application'])
                ->whereHas('application', function ($q) {
                    $q->where('application_type', 'IRINN');
                })
                ->findOrFail($id);

            $downloadType = $request->query('type', 'auto'); // 'invoice', 'credit_note', or 'auto'

            // If credit note exists and type is 'credit_note' or 'auto', serve credit note
            if (($downloadType === 'credit_note' || $downloadType === 'auto') && $invoice->hasCreditNote()) {
                // Ensure credit note PDF exists and extract fields from API response if needed
                app(AdminController::class)->ensureCreditNotePdfExists($invoice->application, $invoice);
                $invoice->refresh();

                if ($invoice->credit_note_pdf_path && Storage::disk('public')->exists($invoice->credit_note_pdf_path)) {
                    $filePath = Storage::disk('public')->path($invoice->credit_note_pdf_path);
                    // Credit note filename: invoice_number + "C.pdf" (e.g., NIXIEX2526-2292C.pdf)
                    $safeFilename = str_replace(['/', '\\'], '-', $invoice->invoice_number).'C.pdf';

                    return response()->download($filePath, $safeFilename);
                }
            }

            // Serve invoice PDF (original or cancelled version)
            if ($downloadType === 'invoice' || ($downloadType === 'auto' && ! $invoice->hasCreditNote())) {
                app(AdminController::class)->ensureInvoicePdfExists($invoice->application, $invoice);

                if ($invoice->pdf_path && Storage::disk('public')->exists($invoice->pdf_path)) {
                    $filePath = Storage::disk('public')->path($invoice->pdf_path);
                    $safeFilename = str_replace(['/', '\\'], '-', $invoice->invoice_number).'_invoice.pdf';

                    return response()->download($filePath, $safeFilename);
                }

                return redirect()->route('superadmin.invoices.index')
                    ->with('error', 'Invoice PDF not found. It may not have been generated yet.');
            }

            return redirect()->route('superadmin.invoices.index')
                ->with('error', 'Invalid download type specified.');
        } catch (Exception $e) {
            Log::error('Error downloading invoice PDF: '.$e->getMessage());

            return redirect()->route('superadmin.invoices.index')
                ->with('error', 'Unable to download invoice PDF.');
        }
    }

    /**
     * Display all applications list.
     */
    public function applications(Request $request)
    {
        try {
            $superAdminId = session('superadmin_id');
            $superAdmin = SuperAdmin::findOrFail($superAdminId);

            $query = Application::with(['user'])
                ->where('application_type', 'IRINN')
                ->latest();

            // Search functionality
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('application_id', 'LIKE', "%{$search}%")
                        ->orWhere('membership_id', 'LIKE', "%{$search}%")
                        ->orWhere('status', 'LIKE', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('fullname', 'LIKE', "%{$search}%")
                                ->orWhere('email', 'LIKE', "%{$search}%");
                        });
                });
            }

            $applications = $query->paginate(20);

            return view('superadmin.applications.index', compact('applications', 'superAdmin'));
        } catch (Exception $e) {
            Log::error('Error loading applications: '.$e->getMessage());

            return redirect()->route('superadmin.dashboard')
                ->with('error', 'Unable to load applications. Please try again.');
        }
    }

    /**
     * Display application details.
     */
    public function showApplication($id)
    {
        try {
            $superAdminId = session('superadmin_id');
            $superAdmin = SuperAdmin::findOrFail($superAdminId);

            $application = Application::with([
                'user',
                'statusHistory',
                'paymentVerificationLogs',
                'invoices',
                'gstVerification',
                'udyamVerification',
                'mcaVerification',
                'rocIecVerification',
            ])->findOrFail($id);

            return view('superadmin.applications.show', compact('application', 'superAdmin'));
        } catch (Exception $e) {
            Log::error('Error loading application: '.$e->getMessage());

            return redirect()->route('superadmin.applications.index')
                ->with('error', 'Application not found.');
        }
    }

    /**
     * Update invoice status.
     */
    public function updateInvoiceStatus(Request $request, $id)
    {
        try {
            $superAdminId = session('superadmin_id');
            $superAdmin = SuperAdmin::findOrFail($superAdminId);

            $validated = $request->validate([
                'status' => 'required|in:pending,paid,overdue,cancelled',
            ]);

            $invoice = Invoice::whereHas('application', function ($q) {
                $q->where('application_type', 'IRINN');
            })->findOrFail($id);

            $oldStatus = $invoice->status;
            $invoice->update([
                'status' => $validated['status'],
                'paid_at' => $validated['status'] === 'paid' ? now('Asia/Kolkata') : null,
            ]);

            Log::info("SuperAdmin {$superAdmin->name} updated invoice {$invoice->invoice_number} status from {$oldStatus} to {$validated['status']}");

            return back()->with('success', 'Invoice status updated successfully.');
        } catch (Exception $e) {
            Log::error('Error updating invoice status: '.$e->getMessage());

            return back()->with('error', 'Unable to update invoice status.');
        }
    }

    /**
     * Display all members (users with membership_id).
     */
    public function members(Request $request)
    {
        try {
            $filter = $request->get('filter', 'all'); // all, active, disconnected

            $query = Registration::whereHas('applications', function ($query) {
                $query->whereNotNull('membership_id');
            });

            if ($filter === 'active') {
                // Live members: is_active = true
                $query->whereHas('applications', function ($q) {
                    $q->whereNotNull('membership_id')
                        ->where('is_active', true);
                });
            } elseif ($filter === 'disconnected') {
                // Not live members: is_active = false
                $query->whereHas('applications', function ($q) {
                    $q->whereNotNull('membership_id')
                        ->where('is_active', false);
                });
            }

            // Search functionality
            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('fullname', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('registrationid', 'like', "%{$search}%")
                        ->orWhere('pancardno', 'like', "%{$search}%");
                });
            }

            // Show all applications (including inactive) for management purposes
            $members = $query->with(['applications' => function ($q) {
                $q->whereNotNull('membership_id')
                    ->latest();
            }])->distinct()->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

            return view('superadmin.members.index', compact('members', 'filter'));
        } catch (Exception $e) {
            Log::error('Error loading members: '.$e->getMessage());

            return redirect()->route('superadmin.dashboard')
                ->with('error', 'Unable to load members. Please try again.');
        }
    }

    /**
     * Display member details.
     */
    public function showMember($id)
    {
        try {
            $user = Registration::with([
                'messages',
                'profileUpdateRequests.approver',
                'profileUpdateRequests' => function ($query) {
                    $query->with('approver')->latest();
                },
                'applications' => function ($query) {
                    $query->whereNotNull('membership_id')
                        ->with(['invoices' => function ($q) {
                            $q->latest('invoice_date');
                        }])
                        ->latest();
                },
            ])->findOrFail($id);

            // Check if this is a member (has applications with membership_id)
            $isMember = $user->applications->whereNotNull('membership_id')->count() > 0;

            if (! $isMember) {
                return redirect()->route('superadmin.members')
                    ->with('error', 'This user is not a member.');
            }

            // Get all admin actions related to this user
            $adminActions = AdminAction::where('actionable_type', Registration::class)
                ->where('actionable_id', $id)
                ->orWhere(function ($query) use ($user) {
                    $query->where('actionable_type', ProfileUpdateRequest::class)
                        ->whereIn('actionable_id', $user->profileUpdateRequests->pluck('id'));
                })
                ->orWhere(function ($query) use ($user) {
                    $query->where('actionable_type', Message::class)
                        ->whereIn('actionable_id', $user->messages->pluck('id'));
                })
                ->with(['admin', 'superAdmin'])
                ->latest()
                ->get();

            return view('superadmin.members.show', compact('user', 'adminActions'));
        } catch (Exception $e) {
            Log::error('Error loading member details: '.$e->getMessage());

            return redirect()->route('superadmin.members')
                ->with('error', 'Member not found.');
        }
    }

    /**
     * Display nodal officer email management page.
     */
    public function nodalOfficerEmails()
    {
        try {
            $superAdminId = session('superadmin_id');
            $superAdmin = SuperAdmin::findOrFail($superAdminId);

            $nodalOfficerEmails = NodalOfficerEmail::orderBy('order')->orderBy('name')->get();

            $nodalOfficerNames = collect();

            return view('superadmin.nodal-officer-emails.index', compact('superAdmin', 'nodalOfficerEmails', 'nodalOfficerNames'));
        } catch (Exception $e) {
            Log::error('Error loading nodal officer emails page: '.$e->getMessage());

            return redirect()->route('superadmin.dashboard')
                ->with('error', 'Unable to load nodal officer emails page.');
        }
    }

    /**
     * Store a new nodal officer email.
     */
    public function storeNodalOfficerEmail(Request $request)
    {
        try {
            $superAdminId = session('superadmin_id');
            $superAdmin = SuperAdmin::findOrFail($superAdminId);

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255|unique:nodal_officer_emails,email',
                'is_active' => 'boolean',
                'order' => 'nullable|integer|min:0',
            ]);

            // Check if email already exists for this name
            $existing = NodalOfficerEmail::where('name', $validated['name'])->first();
            if ($existing) {
                return back()->with('error', 'Email already exists for this nodal officer name. Please update the existing entry instead.');
            }

            $nodalOfficerEmail = NodalOfficerEmail::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'is_active' => $validated['is_active'] ?? true,
                'order' => $validated['order'] ?? 0,
            ]);

            AdminAction::logSuperAdmin(
                $superAdmin->id,
                'created_nodal_officer_email',
                $nodalOfficerEmail,
                "Created nodal officer email: {$validated['name']} ({$validated['email']})"
            );

            return back()->with('success', 'Nodal officer email added successfully.');
        } catch (Exception $e) {
            Log::error('Error storing nodal officer email: '.$e->getMessage());

            return back()->with('error', 'Unable to add nodal officer email. Please try again.');
        }
    }

    /**
     * Update a nodal officer email.
     */
    public function updateNodalOfficerEmail(Request $request, $id)
    {
        try {
            $superAdminId = session('superadmin_id');
            $superAdmin = SuperAdmin::findOrFail($superAdminId);

            $nodalOfficerEmail = NodalOfficerEmail::findOrFail($id);

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255|unique:nodal_officer_emails,email,'.$id,
                'is_active' => 'boolean',
                'order' => 'nullable|integer|min:0',
            ]);

            // Check if email already exists for this name (excluding current record)
            $existing = NodalOfficerEmail::where('name', $validated['name'])
                ->where('id', '!=', $id)
                ->first();
            if ($existing) {
                return back()->with('error', 'Email already exists for this nodal officer name. Please update the existing entry instead.');
            }

            $nodalOfficerEmail->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'is_active' => $validated['is_active'] ?? $nodalOfficerEmail->is_active,
                'order' => $validated['order'] ?? $nodalOfficerEmail->order,
            ]);

            // Refresh the model to get updated values
            $nodalOfficerEmail->refresh();

            AdminAction::logSuperAdmin(
                $superAdmin->id,
                'updated_nodal_officer_email',
                $nodalOfficerEmail,
                "Updated nodal officer email: {$validated['name']} ({$validated['email']})"
            );

            return back()->with('success', 'Nodal officer email updated successfully.');
        } catch (Exception $e) {
            Log::error('Error updating nodal officer email: '.$e->getMessage());

            return back()->with('error', 'Unable to update nodal officer email. Please try again.');
        }
    }

    /**
     * Delete a nodal officer email.
     */
    public function destroyNodalOfficerEmail($id)
    {
        try {
            $superAdminId = session('superadmin_id');
            $superAdmin = SuperAdmin::findOrFail($superAdminId);

            $nodalOfficerEmail = NodalOfficerEmail::findOrFail($id);
            $name = $nodalOfficerEmail->name;
            $email = $nodalOfficerEmail->email;

            // Log before deleting (since we need the model instance)
            AdminAction::logSuperAdmin(
                $superAdmin->id,
                'deleted_nodal_officer_email',
                $nodalOfficerEmail,
                "Deleted nodal officer email: {$name} ({$email})"
            );

            $nodalOfficerEmail->delete();

            return back()->with('success', 'Nodal officer email deleted successfully.');
        } catch (Exception $e) {
            Log::error('Error deleting nodal officer email: '.$e->getMessage());

            return back()->with('error', 'Unable to delete nodal officer email. Please try again.');
        }
    }

    /**
     * Toggle nodal officer email active status.
     */
    public function toggleNodalOfficerEmailStatus($id)
    {
        try {
            $superAdminId = session('superadmin_id');
            $superAdmin = SuperAdmin::findOrFail($superAdminId);

            $nodalOfficerEmail = NodalOfficerEmail::findOrFail($id);
            $nodalOfficerEmail->update([
                'is_active' => ! $nodalOfficerEmail->is_active,
            ]);

            // Refresh the model to get updated status
            $nodalOfficerEmail->refresh();

            $status = $nodalOfficerEmail->is_active ? 'activated' : 'deactivated';

            AdminAction::logSuperAdmin(
                $superAdmin->id,
                'toggled_nodal_officer_email_status',
                $nodalOfficerEmail,
                "{$status} nodal officer email: {$nodalOfficerEmail->name} ({$nodalOfficerEmail->email})"
            );

            return back()->with('success', "Nodal officer email {$status} successfully.");
        } catch (Exception $e) {
            Log::error('Error toggling nodal officer email status: '.$e->getMessage());

            return back()->with('error', 'Unable to toggle nodal officer email status. Please try again.');
        }
    }
}
