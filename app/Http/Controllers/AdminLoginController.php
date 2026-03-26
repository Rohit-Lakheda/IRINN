<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\AdminAction;
use App\Models\MasterOtp;
use App\Mail\AdminLoginOtpMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\QueryException;
use PDOException;
use Exception;

class AdminLoginController extends Controller
{
    /**
     * Display the Admin login page.
     */
    public function index()
    {
        try {
            // If Admin is already logged in, redirect to dashboard
            if (session('admin_id')) {
                return redirect()->route('admin.dashboard');
            }
            
            return response()->view('admin.login')
                ->header('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate')
                ->header('Pragma', 'no-cache')
                ->header('Expires', 'Fri, 01 Jan 1990 00:00:00 GMT');
        } catch (QueryException $e) {
            Log::error('Database error loading Admin login page: ' . $e->getMessage());
            abort(503, 'Database connection error. Please try again later.');
        } catch (PDOException $e) {
            Log::error('PDO error loading Admin login page: ' . $e->getMessage());
            abort(503, 'Database connection error. Please try again later.');
        } catch (Exception $e) {
            Log::error('Error loading Admin login page: ' . $e->getMessage());
            abort(500, 'Unable to load login page. Please try again later.');
        }
    }

    /**
     * Handle Admin login request and send OTP.
     */
    public function login(Request $request)
    {
        try {
            $validated = $request->validate([
                'email' => 'required|email',
                'password' => 'required|string|min:8',
            ], [
                'email.required' => 'Email address is required.',
                'email.email' => 'Please enter a valid email address.',
                'password.required' => 'Password is required.',
                'password.min' => 'Password must be at least 8 characters.',
            ]);

            $email = $validated['email'];
            $password = $validated['password'];

            // Find Admin by email
            $admin = Admin::where('email', $email)->first();

            if (!$admin) {
                return back()->with('error', 'Invalid email or password.')
                    ->withInput($request->only('email'));
            }

            // Verify password
            // Check if password is already hashed (starts with $2y$ which is bcrypt)
            $isPasswordHashed = str_starts_with($admin->password, '$2y$') || str_starts_with($admin->password, '$2a$') || str_starts_with($admin->password, '$2b$');
            
            if ($isPasswordHashed) {
                // Password is hashed, use Hash::check
                if (!Hash::check($password, $admin->password)) {
                    Log::warning("Admin login failed - password mismatch for email: {$email}");
                    return back()->with('error', 'Invalid email or password.')
                        ->withInput($request->only('email'));
                }
            } else {
                // Password is plain text (for manually inserted records), compare directly
                if ($password !== $admin->password) {
                    Log::warning("Admin login failed - plain text password mismatch for email: {$email}");
                    return back()->with('error', 'Invalid email or password.')
                        ->withInput($request->only('email'));
                }
                
                // If plain text password matches, hash it and update the database
                $admin->password = Hash::make($password);
                $admin->save();
                Log::info("Admin password hashed and updated for email: {$email}");
            }

            // Check if Admin is active
            if (!$admin->is_active) {
                return back()->with('error', 'Your account is inactive. Please contact administrator.')
                    ->withInput($request->only('email'));
            }

            // Generate login OTP
            $loginOtp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

            // Store OTP and Admin info in session
            session(['admin_login_otp_' . md5($email) => $loginOtp]);
            session(['admin_login_otp_time_' . md5($email) => now('Asia/Kolkata')]);
            session(['admin_login_id_' . md5($email) => $admin->id]);
            session(['pending_admin_login_email' => $email]);

            // Send OTP via Email
            try {
                Mail::to($email)->send(new AdminLoginOtpMail($loginOtp));
                Log::info("Admin Login OTP sent to {$email}");
            } catch (Exception $e) {
                Log::error("Failed to send Admin login OTP: " . $e->getMessage());
            }

            return redirect()->route('admin.login.verify')
                ->with('success', 'OTP has been sent to your registered email. Please verify to continue.');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (QueryException $e) {
            Log::error('Database error during Admin login: ' . $e->getMessage());
            return back()->with('error', 'Database connection error. Please try again later.')
                ->withInput();
        } catch (PDOException $e) {
            Log::error('PDO error during Admin login: ' . $e->getMessage());
            return back()->with('error', 'Database connection error. Please try again later.')
                ->withInput();
        } catch (Exception $e) {
            Log::error('Error during Admin login: ' . $e->getMessage());
            return back()->with('error', 'An error occurred during login. Please try again.')
                ->withInput();
        }
    }

    /**
     * Show OTP verification page for Admin login.
     */
    public function showVerify()
    {
        try {
            $email = session('pending_admin_login_email');
            
            if (!$email) {
                return redirect()->route('admin.login')
                    ->with('error', 'Please login first.');
            }

            return response()->view('admin.verify-otp', compact('email'))
                ->header('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate')
                ->header('Pragma', 'no-cache')
                ->header('Expires', 'Fri, 01 Jan 1990 00:00:00 GMT');
        } catch (Exception $e) {
            Log::error('Error loading Admin OTP verification page: ' . $e->getMessage());
            return redirect()->route('admin.login')
                ->with('error', 'An error occurred. Please try again.');
        }
    }

    /**
     * Verify Admin login OTP and authenticate.
     */
    public function verifyOtp(Request $request)
    {
        try {
            $request->validate([
                'otp' => 'required|string|size:6',
            ], [
                'otp.required' => 'OTP is required.',
                'otp.size' => 'OTP must be 6 digits.',
            ]);

            $email = session('pending_admin_login_email');
            
            if (!$email) {
                return redirect()->route('admin.login')
                    ->with('error', 'Session expired. Please login again.');
            }

            $otp = $request->input('otp');
            $sessionKey = 'admin_login_otp_' . md5($email);
            $storedOtp = session($sessionKey);

            // Check if entered OTP is a master OTP or matches the stored OTP
            $isMasterOtpValid = MasterOtp::isValidMasterOtp($otp);
            $isOtpValid = $storedOtp && $storedOtp === $otp;

            if ($isMasterOtpValid || $isOtpValid) {
                // Get Admin ID from session
                $adminId = session('admin_login_id_' . md5($email));
                $admin = Admin::with('roles')->find($adminId);

                if (!$admin) {
                    return redirect()->route('admin.login')
                        ->with('error', 'Admin not found. Please try again.');
                }

                // Set Admin session
                session(['admin_id' => $admin->id]);
                session(['admin_name' => $admin->name]);
                session(['admin_email' => $admin->email]);
                session(['admin_userid' => $admin->admin_id]);

                // Clear login OTP session data
                session()->forget('admin_login_otp_' . md5($email));
                session()->forget('admin_login_otp_time_' . md5($email));
                session()->forget('admin_login_id_' . md5($email));
                session()->forget('pending_admin_login_email');

                // Log admin login
                AdminAction::logAdminActivity(
                    $admin->id,
                    'admin_login',
                    "Admin {$admin->name} logged in successfully",
                    ['email' => $admin->email, 'admin_id' => $admin->admin_id]
                );

                return redirect()->route('admin.dashboard')
                    ->with('success', 'Login successful! Welcome back, ' . $admin->name);
            }

            return back()->with('error', 'Invalid OTP. Please try again.');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (QueryException $e) {
            Log::error('Database error verifying Admin OTP: ' . $e->getMessage());
            return back()->with('error', 'Database connection error. Please try again later.');
        } catch (PDOException $e) {
            Log::error('PDO error verifying Admin OTP: ' . $e->getMessage());
            return back()->with('error', 'Database connection error. Please try again later.');
        } catch (Exception $e) {
            Log::error('Error verifying Admin OTP: ' . $e->getMessage());
            return back()->with('error', 'An error occurred while verifying OTP. Please try again.');
        }
    }

    /**
     * Resend Admin login OTP.
     */
    public function resendOtp(Request $request)
    {
        try {
            $email = session('pending_admin_login_email');
            
            if (!$email) {
                return back()->with('error', 'Session expired. Please login again.');
            }

            $admin = Admin::where('email', $email)->first();

            if (!$admin) {
                return redirect()->route('admin.login')
                    ->with('error', 'Admin not found.');
            }

            // Generate new OTP
            $loginOtp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

            // Store OTP in session
            session(['admin_login_otp_' . md5($email) => $loginOtp]);
            session(['admin_login_otp_time_' . md5($email) => now('Asia/Kolkata')]);

            // Send OTP (for now, just log it)
            Log::info("Resent Admin Login OTP for {$email}: {$loginOtp}");

            return back()->with('success', 'OTP has been resent to your email.');
        } catch (QueryException $e) {
            Log::error('Database error resending Admin OTP: ' . $e->getMessage());
            return back()->with('error', 'Database connection error. Please try again later.');
        } catch (PDOException $e) {
            Log::error('PDO error resending Admin OTP: ' . $e->getMessage());
            return back()->with('error', 'Database connection error. Please try again later.');
        } catch (Exception $e) {
            Log::error('Error resending Admin OTP: ' . $e->getMessage());
            return back()->with('error', 'An error occurred while resending OTP. Please try again.');
        }
    }

    /**
     * Logout Admin.
     */
    public function logout()
    {
        try {
            $adminId = session('admin_id');
            $adminName = session('admin_name');
            $adminEmail = session('admin_email');
            $adminUserId = session('admin_userid');

            // Log admin logout before clearing session
            if ($adminId) {
                AdminAction::logAdminActivity(
                    $adminId,
                    'admin_logout',
                    "Admin {$adminName} logged out",
                    ['email' => $adminEmail, 'admin_id' => $adminUserId]
                );
            }

            // Clear Admin session data
            session()->forget(['admin_id', 'admin_name', 'admin_email', 'admin_userid']);
            
            // Redirect with cache control headers to prevent back button
            return redirect()->route('admin.login')
                ->with('success', 'You have been logged out successfully.')
                ->header('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate')
                ->header('Pragma', 'no-cache')
                ->header('Expires', 'Fri, 01 Jan 1990 00:00:00 GMT');
        } catch (Exception $e) {
            Log::error('Error during Admin logout: ' . $e->getMessage());
            return redirect()->route('admin.login')
                ->header('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate')
                ->header('Pragma', 'no-cache')
                ->header('Expires', 'Fri, 01 Jan 1990 00:00:00 GMT');
        }
    }
}
