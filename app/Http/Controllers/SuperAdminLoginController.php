<?php

namespace App\Http\Controllers;

use App\Models\SuperAdmin;
use App\Models\MasterOtp;
use App\Mail\SuperAdminLoginOtpMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\QueryException;
use PDOException;
use Exception;

class SuperAdminLoginController extends Controller
{
    /**
     * Display the SuperAdmin login page.
     */
    public function index()
    {
        try {
            // If SuperAdmin is already logged in, redirect to dashboard
            if (session('superadmin_id')) {
                return redirect()->route('superadmin.dashboard');
            }
            
            return response()->view('superadmin.login')
                ->header('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate')
                ->header('Pragma', 'no-cache')
                ->header('Expires', 'Fri, 01 Jan 1990 00:00:00 GMT');
        } catch (QueryException $e) {
            Log::error('Database error loading SuperAdmin login page: ' . $e->getMessage());
            abort(503, 'Database connection error. Please try again later.');
        } catch (PDOException $e) {
            Log::error('PDO error loading SuperAdmin login page: ' . $e->getMessage());
            abort(503, 'Database connection error. Please try again later.');
        } catch (Exception $e) {
            Log::error('Error loading SuperAdmin login page: ' . $e->getMessage());
            abort(500, 'Unable to load login page. Please try again later.');
        }
    }

    /**
     * Handle SuperAdmin login request and send OTP.
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

            // Find SuperAdmin by email
            $superAdmin = SuperAdmin::where('email', $email)->first();

            if (!$superAdmin) {
                return back()->with('error', 'Invalid email or password.')
                    ->withInput($request->only('email'));
            }

            // Verify password
            // Check if password is already hashed (starts with $2y$ which is bcrypt)
            $isPasswordHashed = str_starts_with($superAdmin->password, '$2y$') || str_starts_with($superAdmin->password, '$2a$') || str_starts_with($superAdmin->password, '$2b$');
            
            if ($isPasswordHashed) {
                // Password is hashed, use Hash::check
                if (!Hash::check($password, $superAdmin->password)) {
                    return back()->with('error', 'Invalid email or password.')
                        ->withInput($request->only('email'));
                }
            } else {
                // Password is plain text (for manually inserted records), compare directly
                if ($password !== $superAdmin->password) {
                    return back()->with('error', 'Invalid email or password.')
                        ->withInput($request->only('email'));
                }
                
                // If plain text password matches, hash it and update the database
                $superAdmin->password = Hash::make($password);
                $superAdmin->save();
            }

            // Check if SuperAdmin is active
            if (!$superAdmin->is_active) {
                return back()->with('error', 'Your account is inactive. Please contact administrator.')
                    ->withInput($request->only('email'));
            }

            // Generate login OTP
            $loginOtp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

            // Store OTP and SuperAdmin info in session
            session(['superadmin_login_otp_' . md5($email) => $loginOtp]);
            session(['superadmin_login_otp_time_' . md5($email) => now('Asia/Kolkata')]);
            session(['superadmin_login_id_' . md5($email) => $superAdmin->id]);
            session(['pending_superadmin_login_email' => $email]);

            // Send OTP via Email
            try {
                Mail::to($email)->send(new SuperAdminLoginOtpMail($loginOtp));
                Log::info("SuperAdmin Login OTP sent to {$email}");
            } catch (Exception $e) {
                Log::error("Failed to send SuperAdmin login OTP: " . $e->getMessage());
            }

            return redirect()->route('superadmin.login.verify')
                ->with('success', 'OTP has been sent to your registered email. Please verify to continue.');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (QueryException $e) {
            Log::error('Database error during SuperAdmin login: ' . $e->getMessage());
            return back()->with('error', 'Database connection error. Please try again later.')
                ->withInput();
        } catch (PDOException $e) {
            Log::error('PDO error during SuperAdmin login: ' . $e->getMessage());
            return back()->with('error', 'Database connection error. Please try again later.')
                ->withInput();
        } catch (Exception $e) {
            Log::error('Error during SuperAdmin login: ' . $e->getMessage());
            return back()->with('error', 'An error occurred during login. Please try again.')
                ->withInput();
        }
    }

    /**
     * Show OTP verification page for SuperAdmin login.
     */
    public function showVerify()
    {
        try {
            $email = session('pending_superadmin_login_email');
            
            if (!$email) {
                return redirect()->route('superadmin.login')
                    ->with('error', 'Please login first.');
            }

            return response()->view('superadmin.verify-otp', compact('email'))
                ->header('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate')
                ->header('Pragma', 'no-cache')
                ->header('Expires', 'Fri, 01 Jan 1990 00:00:00 GMT');
        } catch (Exception $e) {
            Log::error('Error loading SuperAdmin OTP verification page: ' . $e->getMessage());
            return redirect()->route('superadmin.login')
                ->with('error', 'An error occurred. Please try again.');
        }
    }

    /**
     * Verify SuperAdmin login OTP and authenticate.
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

            $email = session('pending_superadmin_login_email');
            
            if (!$email) {
                return redirect()->route('superadmin.login')
                    ->with('error', 'Session expired. Please login again.');
            }

            $otp = $request->input('otp');
            $sessionKey = 'superadmin_login_otp_' . md5($email);
            $storedOtp = session($sessionKey);

            // Check master OTP
            $masterOtp = $request->input('master_otp');
            $isMasterOtpValid = $masterOtp && MasterOtp::isValidMasterOtp($masterOtp);

            if ($isMasterOtpValid || ($storedOtp && $storedOtp === $otp)) {
                // Get SuperAdmin ID from session
                $superAdminId = session('superadmin_login_id_' . md5($email));
                $superAdmin = SuperAdmin::find($superAdminId);

                if (!$superAdmin) {
                    return redirect()->route('superadmin.login')
                        ->with('error', 'SuperAdmin not found. Please try again.');
                }

                // Set SuperAdmin session
                session(['superadmin_id' => $superAdmin->id]);
                session(['superadmin_name' => $superAdmin->name]);
                session(['superadmin_email' => $superAdmin->email]);
                session(['superadmin_userid' => $superAdmin->userid]);

                // Clear login OTP session data
                session()->forget('superadmin_login_otp_' . md5($email));
                session()->forget('superadmin_login_otp_time_' . md5($email));
                session()->forget('superadmin_login_id_' . md5($email));
                session()->forget('pending_superadmin_login_email');

                return redirect()->route('superadmin.dashboard')
                    ->with('success', 'Login successful');
            }

            return back()->with('error', 'Invalid OTP. Please try again.');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (QueryException $e) {
            Log::error('Database error verifying SuperAdmin OTP: ' . $e->getMessage());
            return back()->with('error', 'Database connection error. Please try again later.');
        } catch (PDOException $e) {
            Log::error('PDO error verifying SuperAdmin OTP: ' . $e->getMessage());
            return back()->with('error', 'Database connection error. Please try again later.');
        } catch (Exception $e) {
            Log::error('Error verifying SuperAdmin OTP: ' . $e->getMessage());
            return back()->with('error', 'An error occurred while verifying OTP. Please try again.');
        }
    }

    /**
     * Resend SuperAdmin login OTP.
     */
    public function resendOtp(Request $request)
    {
        try {
            $email = session('pending_superadmin_login_email');
            
            if (!$email) {
                return back()->with('error', 'Session expired. Please login again.');
            }

            $superAdmin = SuperAdmin::where('email', $email)->first();

            if (!$superAdmin) {
                return redirect()->route('superadmin.login')
                    ->with('error', 'SuperAdmin not found.');
            }

            // Generate new OTP
            $loginOtp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

            // Store OTP in session
            session(['superadmin_login_otp_' . md5($email) => $loginOtp]);
            session(['superadmin_login_otp_time_' . md5($email) => now('Asia/Kolkata')]);

            // Send OTP (for now, just log it)
            Log::info("Resent SuperAdmin Login OTP for {$email}: {$loginOtp}");

            return back()->with('success', 'OTP has been resent to your email.');
        } catch (QueryException $e) {
            Log::error('Database error resending SuperAdmin OTP: ' . $e->getMessage());
            return back()->with('error', 'Database connection error. Please try again later.');
        } catch (PDOException $e) {
            Log::error('PDO error resending SuperAdmin OTP: ' . $e->getMessage());
            return back()->with('error', 'Database connection error. Please try again later.');
        } catch (Exception $e) {
            Log::error('Error resending SuperAdmin OTP: ' . $e->getMessage());
            return back()->with('error', 'An error occurred while resending OTP. Please try again.');
        }
    }

    /**
     * Logout SuperAdmin.
     */
    public function logout()
    {
        try {
            // Clear SuperAdmin session data
            session()->forget(['superadmin_id', 'superadmin_name', 'superadmin_email', 'superadmin_userid']);
            
            // Redirect with cache control headers to prevent back button
            return redirect()->route('superadmin.login')
                ->with('success', 'You have been logged out successfully.')
                ->header('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate')
                ->header('Pragma', 'no-cache')
                ->header('Expires', 'Fri, 01 Jan 1990 00:00:00 GMT');
        } catch (Exception $e) {
            Log::error('Error during SuperAdmin logout: ' . $e->getMessage());
            return redirect()->route('superadmin.login')
                ->header('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate')
                ->header('Pragma', 'no-cache')
                ->header('Expires', 'Fri, 01 Jan 1990 00:00:00 GMT');
        }
    }
}
