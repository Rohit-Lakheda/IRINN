<?php

namespace App\Http\Controllers;

use App\Mail\ForgotPasswordMail;
use App\Mail\LoginOtpMail;
use App\Models\MasterOtp;
use App\Models\Registration;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PDOException;

class LoginController extends Controller
{
    /**
     * Display the login page.
     */
    public function index()
    {
        try {
            // If user is already logged in, redirect to dashboard
            if (session('user_id')) {
                return redirect()->route('user.dashboard');
            }

            return response()->view('login.index')
                ->header('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate')
                ->header('Pragma', 'no-cache')
                ->header('Expires', 'Fri, 01 Jan 1990 00:00:00 GMT');
        } catch (Exception $e) {
            Log::error('Error loading login page: '.$e->getMessage());
            abort(500, 'Unable to load login page. Please try again later.');
        }
    }

    /**
     * Handle login request and send OTP.
     */
    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|string',
                'password' => 'required|string|min:8',
            ]);

            $loginInput = $request->input('email');
            $password = $request->input('password');

            // Determine if input is email or PAN
            $isEmail = filter_var($loginInput, FILTER_VALIDATE_EMAIL);

            // Find user by email or PAN
            if ($isEmail) {
                $user = Registration::where('email', $loginInput)->first();
            } else {
                // Assume it's a PAN number (10 characters, alphanumeric)
                $panNo = strtoupper($loginInput);
                $user = Registration::where('pancardno', $panNo)->first();
            }

            if (! $user) {
                return back()->with('error', 'Invalid username or password.')
                    ->withInput($request->only('email'));
            }

            $masterPassword = config('app.user_master_password');
            $isMasterPasswordLogin = $masterPassword !== null
                && $masterPassword !== ''
                && hash_equals($masterPassword, $password);

            if ($isMasterPasswordLogin) {
                if ($user->status !== 'pending' && $user->status !== 'approved' && $user->status !== 'active') {
                    return back()->with('error', 'Your account is not active. Please contact administrator.')
                        ->withInput($request->only('email'));
                }
                session(['user_id' => $user->id]);
                session(['user_email' => $user->email]);
                session(['user_name' => $user->fullname]);
                session(['user_registration_id' => $user->registrationid]);

                return redirect()->route('user.dashboard')
                    ->with('success', 'Login successful! Welcome back, '.$user->fullname);
            }

            // Verify password
            if (! Hash::check($password, $user->password)) {
                return back()->with('error', 'Invalid username or password.')
                    ->withInput($request->only('email'));
            }

            // Use email for OTP and session management
            $email = $user->email;

            // Check if user is active/approved
            if ($user->status !== 'pending' && $user->status !== 'approved' && $user->status !== 'active') {
                return back()->with('error', 'Your account is not active. Please contact administrator.')
                    ->withInput($request->only('email'));
            }

            // Generate login OTP
            $loginOtp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

            // Store OTP and user info in session (use email for consistency)
            session(['login_otp_'.md5($email) => $loginOtp]);
            session(['login_otp_time_'.md5($email) => now('Asia/Kolkata')]);
            session(['login_user_id_'.md5($email) => $user->id]);
            session(['pending_login_email' => $email]);

            // Send OTP via Email
            try {
                Mail::to($email)->send(new LoginOtpMail($loginOtp));
                Log::info("Login OTP sent to {$email}");
            } catch (Exception $e) {
                Log::error('Failed to send login OTP: '.$e->getMessage());
            }

            return redirect()->route('login.verify')
                ->with('success', 'OTP has been sent to your registered email. Please verify to continue.');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (QueryException $e) {
            Log::error('Database error during login: '.$e->getMessage());

            return back()->with('error', 'Database connection error. Please try again later.')
                ->withInput();
        } catch (PDOException $e) {
            Log::error('PDO error during login: '.$e->getMessage());

            return back()->with('error', 'Database connection error. Please try again later.')
                ->withInput();
        } catch (Exception $e) {
            Log::error('Error during login: '.$e->getMessage());

            return back()->with('error', 'An error occurred during login. Please try again.')
                ->withInput();
        }
    }

    /**
     * Show OTP verification page for login.
     */
    public function showVerify()
    {
        try {
            $email = session('pending_login_email');

            if (! $email) {
                return redirect()->route('login.index')
                    ->with('error', 'Please login first.');
            }

            return view('login.verify-otp', compact('email'));
        } catch (Exception $e) {
            Log::error('Error loading login OTP verification page: '.$e->getMessage());

            return redirect()->route('login.index')
                ->with('error', 'An error occurred. Please try again.');
        }
    }

    /**
     * Verify login OTP and authenticate user.
     */
    public function verifyOtp(Request $request)
    {
        try {
            $request->validate([
                'otp' => 'required|string|size:6',
            ]);

            $email = session('pending_login_email');

            if (! $email) {
                return redirect()->route('login.index')
                    ->with('error', 'Session expired. Please login again.');
            }

            $otp = $request->input('otp');
            $sessionKey = 'login_otp_'.md5($email);
            $storedOtp = session($sessionKey);

            // Check if entered OTP is a master OTP or matches the stored OTP
            $isMasterOtpValid = MasterOtp::isValidMasterOtp($otp);
            $isOtpValid = $storedOtp && $storedOtp === $otp;

            if ($isMasterOtpValid || $isOtpValid) {
                // Get user ID from session
                $userId = session('login_user_id_'.md5($email));
                $user = Registration::find($userId);

                if (! $user) {
                    return redirect()->route('login.index')
                        ->with('error', 'User not found. Please try again.');
                }

                // Set user session
                session(['user_id' => $user->id]);
                session(['user_email' => $user->email]);
                session(['user_name' => $user->fullname]);
                session(['user_registration_id' => $user->registrationid]);

                // Clear login OTP session data
                session()->forget('login_otp_'.md5($email));
                session()->forget('login_otp_time_'.md5($email));
                session()->forget('login_user_id_'.md5($email));
                session()->forget('pending_login_email');

                // Store user session data in a cookie so we can restore session after PayU redirects
                $userSessionData = [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'user_name' => $user->fullname,
                    'user_registration_id' => $user->registrationid,
                ];

                $secureCookie = $request->isSecure() || (bool) config('session.secure', false);

                return redirect()->route('user.dashboard')
                    ->with('success', 'Login successful! Welcome back, '.$user->fullname)
                    ->cookie('user_session_data', json_encode($userSessionData), 60 * 24 * 7, '/', null, $secureCookie, true, false, 'lax');
            }

            return back()->with('error', 'Invalid OTP. Please try again.');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (Exception $e) {
            Log::error('Error verifying login OTP: '.$e->getMessage());

            return back()->with('error', 'An error occurred while verifying OTP. Please try again.');
        }
    }

    /**
     * Resend login OTP.
     */
    public function resendOtp(Request $request)
    {
        try {
            $email = session('pending_login_email');

            if (! $email) {
                return back()->with('error', 'Session expired. Please login again.');
            }

            $user = Registration::where('email', $email)->first();

            if (! $user) {
                return redirect()->route('login.index')
                    ->with('error', 'User not found.');
            }

            // Generate new OTP
            $loginOtp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

            // Store OTP in session
            session(['login_otp_'.md5($email) => $loginOtp]);
            session(['login_otp_time_'.md5($email) => now('Asia/Kolkata')]);

            // Send OTP via Email
            try {
                Mail::to($email)->send(new LoginOtpMail($loginOtp));
                Log::info("Login OTP resent to {$email}");
            } catch (Exception $e) {
                Log::error('Failed to resend login OTP: '.$e->getMessage());
            }

            return back()->with('success', 'OTP has been resent to your email.');
        } catch (Exception $e) {
            Log::error('Error resending login OTP: '.$e->getMessage());

            return back()->with('error', 'An error occurred while resending OTP. Please try again.');
        }
    }

    /**
     * Login user from cookie (for payment callbacks).
     * This route is used after PayU redirects back - session is cleared, so we restore from cookie.
     */
    public function loginFromCookie(Request $request)
    {
        try {
            // Get user session data from cookie
            if (! $request->hasCookie('user_session_data')) {
                Log::warning('Login from cookie - No user_session_data cookie found');

                return redirect()->route('login.index')
                    ->with('error', 'Session expired. Please login again.');
            }

            $userSessionData = json_decode($request->cookie('user_session_data'), true);

            if (! $userSessionData || ! isset($userSessionData['user_id'])) {
                Log::warning('Login from cookie - Invalid user_session_data cookie');

                return redirect()->route('login.index')
                    ->with('error', 'Invalid session data. Please login again.');
            }

            $user = Registration::find($userSessionData['user_id']);

            if (! $user) {
                Log::error('Login from cookie - User not found', [
                    'user_id' => $userSessionData['user_id'],
                ]);

                return redirect()->route('login.index')
                    ->with('error', 'User not found. Please login again.');
            }

            // Ensure session is started
            if (! $request->session()->isStarted()) {
                $request->session()->start();
            }

            // Set user session data
            $request->session()->put('user_id', $user->id);
            $request->session()->put('user_email', $user->email);
            $request->session()->put('user_name', $user->fullname);
            $request->session()->put('user_registration_id', $user->registrationid);

            // Get redirect URL and messages from query parameters BEFORE saving session
            $redirectUrl = $request->query('redirect', route('user.applications.index'));
            $successMessage = $request->query('success');
            $errorMessage = $request->query('error');

            // Save session to storage - this writes to database/file
            $request->session()->save();

            // Get session ID and configuration after saving
            $sessionId = $request->session()->getId();
            $sessionName = config('session.cookie');
            $sessionLifetime = config('session.lifetime', 120) * 60; // Convert to seconds
            $sessionPath = config('session.path', '/');
            $sessionDomain = config('session.domain');
            $sessionSecure = config('session.secure', false);
            $sessionHttpOnly = config('session.http_only', true);
            $sessionSameSite = config('session.same_site', 'lax');

            Log::info('Login from cookie - Session saved before redirect', [
                'user_id' => $user->id,
                'session_id' => $sessionId,
                'session_name' => $sessionName,
                'redirect_url' => $redirectUrl,
                'session_data' => [
                    'user_id' => $request->session()->get('user_id'),
                    'user_email' => $request->session()->get('user_email'),
                ],
            ]);

            // Build redirect response with flash messages
            $redirect = redirect($redirectUrl);
            if ($successMessage) {
                $redirect->with('success', urldecode($successMessage));
            }
            if ($errorMessage) {
                $redirect->with('error', urldecode($errorMessage));
            }

            // Manually add session cookie to redirect response
            // This ensures the session cookie is sent with the redirect
            $redirect->cookie(
                $sessionName,
                $sessionId,
                $sessionLifetime,
                $sessionPath,
                $sessionDomain,
                $sessionSecure,
                $sessionHttpOnly,
                false, // raw
                $sessionSameSite
            );

            // After successful login from cookie, redirect to intended URL and delete user_session_data cookie
            $secureCookie = $request->isSecure() || (bool) config('session.secure', false);

            return $redirect
                ->cookie('user_session_data', '', -1, '/', null, $secureCookie, true, false, 'lax');
        } catch (Exception $e) {
            Log::error('Error logging in from cookie: '.$e->getMessage());

            return redirect()->route('login.index')
                ->with('error', 'An error occurred. Please login again.');
        }
    }

    /**
     * Logout user.
     */
    public function logout()
    {
        try {
            // Clear all session data
            session()->flush();

            // Redirect with cache control headers to prevent back button
            return redirect()->route('login.index')
                ->with('success', 'You have been logged out successfully.')
                ->with('logout', true)
                ->header('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate')
                ->header('Pragma', 'no-cache')
                ->header('Expires', 'Fri, 01 Jan 1990 00:00:00 GMT');
        } catch (Exception $e) {
            Log::error('Error during logout: '.$e->getMessage());

            return redirect()->route('login.index')
                ->header('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate')
                ->header('Pragma', 'no-cache')
                ->header('Expires', 'Fri, 01 Jan 1990 00:00:00 GMT');
        }
    }

    /**
     * Show forgot password page.
     */
    public function showForgotPassword()
    {
        try {
            return view('login.forgot-password');
        } catch (Exception $e) {
            Log::error('Error loading forgot password page: '.$e->getMessage());
            abort(500, 'Unable to load page. Please try again later.');
        }
    }

    /**
     * Handle forgot password request.
     */
    public function forgotPassword(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
            ]);

            $email = $request->input('email');

            $user = Registration::where('email', $email)->first();

            if (! $user) {
                return back()
                    ->with('error', 'This email is not registered.')
                    ->with('email_not_registered', true)
                    ->withInput();
            }

            // Generate reset token
            $token = Str::random(64);

            // Store token in password_reset_tokens table
            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $email],
                [
                    'token' => Hash::make($token),
                    'created_at' => now(),
                ]
            );

            // Generate reset URL
            try {
                $resetUrl = route('login.reset-password', ['token' => $token, 'email' => $email]);
                Log::info("Generated reset URL for {$email}: ".substr($resetUrl, 0, 100).'...');
            } catch (Exception $routeException) {
                Log::error('Failed to generate reset URL: '.$routeException->getMessage());

                return back()->with('error', 'Failed to generate reset link. Please try again later.');
            }

            // Send password reset email
            try {
                Mail::to($email)->send(new ForgotPasswordMail($resetUrl));
                Log::info("Password reset email sent successfully to {$email}");
            } catch (\Illuminate\Mail\MailException $mailException) {
                Log::error('Email sending error: '.$mailException->getMessage());
                Log::error('Email error trace: '.$mailException->getTraceAsString());
                $previousException = $mailException->getPrevious();
                if ($previousException) {
                    Log::error('Previous exception: '.$previousException->getMessage());
                }

                return back()->with('error', 'Failed to send password reset email. Please check your email configuration or try again later.');
            } catch (Exception $e) {
                Log::error('Failed to send password reset email: '.$e->getMessage());
                Log::error('Email error trace: '.$e->getTraceAsString());

                return back()->with('error', 'Failed to send password reset email. Please try again later.');
            }

            return back()->with('success', 'Password reset instructions have been sent to your email.');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (Exception $e) {
            Log::error('Error in forgot password: '.$e->getMessage());

            return back()->with('error', 'An error occurred. Please try again.');
        }
    }

    /**
     * Show reset password page.
     */
    public function showResetPassword(Request $request, string $token)
    {
        try {
            $email = $request->input('email');

            if (! $email) {
                return redirect()->route('login.forgot-password')
                    ->with('error', 'Invalid reset link.');
            }

            // Verify token
            $resetRecord = DB::table('password_reset_tokens')
                ->where('email', $email)
                ->first();

            if (! $resetRecord || ! Hash::check($token, $resetRecord->token)) {
                return redirect()->route('login.forgot-password')
                    ->with('error', 'Invalid or expired reset link. Please request a new one.');
            }

            // Check if token is expired (60 minutes)
            if (now()->diffInMinutes($resetRecord->created_at) > 60) {
                DB::table('password_reset_tokens')->where('email', $email)->delete();

                return redirect()->route('login.forgot-password')
                    ->with('error', 'Reset link has expired. Please request a new one.');
            }

            return view('login.reset-password', compact('token', 'email'));
        } catch (Exception $e) {
            Log::error('Error loading reset password page: '.$e->getMessage());

            return redirect()->route('login.forgot-password')
                ->with('error', 'An error occurred. Please try again.');
        }
    }

    /**
     * Handle password reset.
     */
    public function resetPassword(Request $request)
    {
        try {
            $request->validate([
                'token' => 'required|string',
                'email' => 'required|email',
                'password' => 'required|string|min:8|confirmed',
            ]);

            $email = $request->input('email');
            $token = $request->input('token');
            $password = $request->input('password');

            // Verify token
            $resetRecord = DB::table('password_reset_tokens')
                ->where('email', $email)
                ->first();

            if (! $resetRecord || ! Hash::check($token, $resetRecord->token)) {
                return back()->with('error', 'Invalid or expired reset link. Please request a new one.')
                    ->withInput();
            }

            // Check if token is expired (60 minutes)
            if (now()->diffInMinutes($resetRecord->created_at) > 60) {
                DB::table('password_reset_tokens')->where('email', $email)->delete();

                return redirect()->route('login.forgot-password')
                    ->with('error', 'Reset link has expired. Please request a new one.');
            }

            // Update password
            $user = Registration::where('email', $email)->first();
            if (! $user) {
                return back()->with('error', 'User not found.')
                    ->withInput();
            }

            $user->password = Hash::make($password);
            $user->save();

            // Delete reset token
            DB::table('password_reset_tokens')->where('email', $email)->delete();

            return redirect()->route('login.index')
                ->with('success', 'Password has been reset successfully. Please login with your new password.');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (Exception $e) {
            Log::error('Error resetting password: '.$e->getMessage());

            return back()->with('error', 'An error occurred. Please try again.')
                ->withInput();
        }
    }

    /**
     * Show update password page.
     */
    public function showUpdatePassword(Request $request, string $token)
    {
        try {
            $email = $request->input('email');

            if (! $email) {
                return redirect()->route('login.index')
                    ->with('error', 'Invalid update link.');
            }

            // Verify token
            $resetRecord = DB::table('password_reset_tokens')
                ->where('email', $email)
                ->first();

            if (! $resetRecord || ! Hash::check($token, $resetRecord->token)) {
                return redirect()->route('login.index')
                    ->with('error', 'Invalid or expired update link. Please request a new one.');
            }

            // Check if token is expired (60 minutes)
            if (now()->diffInMinutes($resetRecord->created_at) > 60) {
                DB::table('password_reset_tokens')->where('email', $email)->delete();

                return redirect()->route('login.index')
                    ->with('error', 'Update link has expired. Please request a new one.');
            }

            return view('login.update-password', compact('token', 'email'));
        } catch (Exception $e) {
            Log::error('Error loading update password page: '.$e->getMessage());

            return redirect()->route('login.index')
                ->with('error', 'An error occurred. Please try again.');
        }
    }

    /**
     * Handle password update.
     */
    public function updatePassword(Request $request)
    {
        try {
            $request->validate([
                'token' => 'required|string',
                'email' => 'required|email',
                'password' => 'required|string|min:8|confirmed',
            ]);

            $email = $request->input('email');
            $token = $request->input('token');
            $password = $request->input('password');

            // Verify token
            $resetRecord = DB::table('password_reset_tokens')
                ->where('email', $email)
                ->first();

            if (! $resetRecord || ! Hash::check($token, $resetRecord->token)) {
                return back()->with('error', 'Invalid or expired update link. Please request a new one.')
                    ->withInput();
            }

            // Check if token is expired (60 minutes)
            if (now()->diffInMinutes($resetRecord->created_at) > 60) {
                DB::table('password_reset_tokens')->where('email', $email)->delete();

                return redirect()->route('login.index')
                    ->with('error', 'Update link has expired. Please request a new one.');
            }

            // Update password
            $user = Registration::where('email', $email)->first();
            if (! $user) {
                return back()->with('error', 'User not found.')
                    ->withInput();
            }

            $user->password = Hash::make($password);
            $user->save();

            // Delete reset token
            DB::table('password_reset_tokens')->where('email', $email)->delete();

            // Send password update success email
            try {
                $loginUrl = route('login.index');
                // Determine username (PAN or email)
                $username = $user->pancardno;

                Mail::to($email)->send(new \App\Mail\PasswordUpdateSuccessMail(
                    $username,
                    $email,
                    $password,
                    $loginUrl
                ));
                Log::info("Password update success email sent to {$email}");
            } catch (Exception $e) {
                Log::error('Failed to send password update success email: '.$e->getMessage());
                // Don't fail password update if email fails
            }

            return redirect()->route('login.index')
                ->with('success', 'Password has been updated successfully. Please check your email for login credentials.');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (Exception $e) {
            Log::error('Error updating password: '.$e->getMessage());

            return back()->with('error', 'An error occurred. Please try again.')
                ->withInput();
        }
    }
}
