<?php

namespace App\Http\Controllers;

use App\Mail\RegistrationOtpMail;
use App\Models\MasterOtp;
use App\Models\ProfileUpdateRequest;
use App\Models\Registration;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use PDOException;

class ProfileUpdateRequestController extends Controller
{
    /**
     * Show the form for creating a profile update request.
     */
    public function create()
    {
        try {
            $userId = session('user_id');
            $user = Registration::find($userId);

            if (! $user) {
                return redirect()->route('login.index')
                    ->with('error', 'User not found. Please login again.');
            }

            // Check if there's already a pending request
            $pendingRequest = $user->pendingProfileUpdateRequest();
            if ($pendingRequest) {
                return redirect()->route('user.profile')
                    ->with('info', 'You already have a pending profile update request. Please wait for admin approval.');
            }

            return response()->view('user.profile.update-request', compact('user'))
                ->header('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate')
                ->header('Pragma', 'no-cache')
                ->header('Expires', 'Fri, 01 Jan 1990 00:00:00 GMT');
        } catch (Exception $e) {
            Log::error('Error loading profile update request form: '.$e->getMessage());

            return redirect()->route('user.profile')
                ->with('error', 'Unable to load form. Please try again.');
        }
    }

    /**
     * Store a new profile update request.
     */
    public function store(Request $request)
    {
        try {
            $userId = session('user_id');
            $user = Registration::find($userId);

            if (! $user) {
                return redirect()->route('login.index')
                    ->with('error', 'User not found. Please login again.');
            }

            // Check if there's already a pending request
            $pendingRequest = $user->pendingProfileUpdateRequest();
            if ($pendingRequest) {
                return redirect()->route('user.profile')
                    ->with('info', 'You already have a pending profile update request. Please wait for admin approval.');
            }

            $validated = $request->validate([
                'requested_changes' => 'required|string|min:10',
            ], [
                'requested_changes.required' => 'Please describe the changes you want to make.',
                'requested_changes.min' => 'Please provide more details about the requested changes (minimum 10 characters).',
            ]);

            // Create the request
            ProfileUpdateRequest::create([
                'user_id' => $userId,
                'status' => 'pending',
                'requested_changes' => $validated['requested_changes'],
            ]);

            return redirect()->route('user.profile')
                ->with('success', 'Profile update request submitted successfully. You will be notified once admin reviews it.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (Exception $e) {
            Log::error('Error creating profile update request: '.$e->getMessage());

            return redirect()->route('user.profile')
                ->with('error', 'An error occurred while submitting your request. Please try again.');
        }
    }

    /**
     * Show the profile update form (only if approved and not yet submitted).
     */
    public function edit()
    {
        try {
            $userId = session('user_id');
            $user = Registration::find($userId);

            if (! $user) {
                return redirect()->route('login.index')
                    ->with('error', 'User not found. Please login again.');
            }

            // Check if there's an approved request (that hasn't been submitted yet)
            $approvedRequest = $user->profileUpdateRequests()
                ->where('status', 'approved')
                ->whereNull('submitted_at')
                ->latest()
                ->first();

            if (! $approvedRequest) {
                return redirect()->route('user.profile')
                    ->with('error', 'You do not have an approved profile update request, or you have already submitted your update.');
            }

            return response()->view('user.profile.update', compact('user', 'approvedRequest'))
                ->header('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate')
                ->header('Pragma', 'no-cache')
                ->header('Expires', 'Fri, 01 Jan 1990 00:00:00 GMT');
        } catch (Exception $e) {
            Log::error('Error loading profile update form: '.$e->getMessage());

            return redirect()->route('user.profile')
                ->with('error', 'Unable to load form. Please try again.');
        }
    }

    /**
     * Update the user's profile (submit for admin approval).
     */
    public function update(Request $request)
    {
        try {
            $userId = session('user_id');
            $user = Registration::find($userId);

            if (! $user) {
                return redirect()->route('login.index')
                    ->with('error', 'User not found. Please login again.');
            }

            // Check if there's an approved request that hasn't been submitted
            $approvedRequest = $user->profileUpdateRequests()
                ->where('status', 'approved')
                ->whereNull('submitted_at')
                ->latest()
                ->first();

            if (! $approvedRequest) {
                return redirect()->route('user.profile')
                    ->with('error', 'You do not have an approved profile update request, or you have already submitted your update.');
            }

            $validated = $request->validate([
                'email' => 'required|email',
                'mobile' => 'required|string|size:10|regex:/^[0-9]{10}$/',
            ], [
                'email.required' => 'Email address is required.',
                'email.email' => 'Please enter a valid email address.',
                'mobile.required' => 'Mobile number is required.',
                'mobile.size' => 'Mobile Number must be exactly 10 digits.',
                'mobile.regex' => 'Mobile Number must contain only digits.',
            ]);

            // Check if email and mobile are verified in session
            $email = $validated['email'];
            $mobile = $validated['mobile'];
            $emailVerified = session('email_verified_'.md5($email));
            $mobileVerified = session('mobile_verified_'.md5($mobile));

            if (! $emailVerified) {
                return back()->withErrors(['email' => 'Please verify your email address before submitting.'])->withInput();
            }

            if (! $mobileVerified) {
                return back()->withErrors(['mobile' => 'Please verify your mobile number before submitting.'])->withInput();
            }

            // Store submitted data (only email and mobile)
            $submittedData = [
                'email' => $validated['email'],
                'mobile' => $validated['mobile'],
            ];

            $approvedRequest->update([
                'submitted_data' => $submittedData,
                'submitted_at' => now('Asia/Kolkata'),
                'update_approved' => false, // Waiting for admin approval
            ]);

            // Clear OTP sessions (using same format as registration)
            session()->forget('email_otp_'.md5($validated['email']));
            session()->forget('email_otp_time_'.md5($validated['email']));
            session()->forget('email_verified_'.md5($validated['email']));
            session()->forget('email_otp_verified_'.md5($validated['email']));
            session()->forget('mobile_otp_'.md5($validated['mobile']));
            session()->forget('mobile_otp_time_'.md5($validated['mobile']));
            session()->forget('mobile_verified_'.md5($validated['mobile']));
            session()->forget('mobile_otp_verified_'.md5($validated['mobile']));

            return redirect()->route('user.profile')
                ->with('success', 'Your profile update has been submitted for admin approval. You will be notified once it is reviewed.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (Exception $e) {
            Log::error('Error updating profile: '.$e->getMessage());

            return redirect()->route('user.profile')
                ->with('error', 'An error occurred while submitting your update. Please try again.');
        }
    }

    /**
     * Send OTP for email verification during profile update.
     */
    public function sendEmailOtp(Request $request)
    {
        try {
            $userId = session('user_id');
            $user = Registration::find($userId);

            if (! $user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found. Please login again.',
                ], 401);
            }

            $request->validate([
                'email' => 'required|email',
            ]);

            $email = $request->input('email');

            // Check if email already exists for another user
            $existingUser = Registration::where('email', $email)->where('id', '!=', $userId)->first();
            if ($existingUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'This email is already registered by another user.',
                ], 400);
            }

            // Generate OTP
            $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

            // Store OTP in session (using same format as registration)
            session(['email_otp_'.md5($email) => $otp]);
            session(['email_otp_time_'.md5($email) => now('Asia/Kolkata')]);

            // Send OTP via Email
            try {
                Mail::to($email)->send(new RegistrationOtpMail($otp));
                Log::info("Email OTP sent successfully to {$email}");
            } catch (\Illuminate\Mail\MailException $mailException) {
                Log::error('Email sending error: '.$mailException->getMessage());
                Log::error('Email error trace: '.$mailException->getTraceAsString());
                $previousException = $mailException->getPrevious();
                if ($previousException) {
                    Log::error('Previous exception: '.$previousException->getMessage());
                }

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to send email OTP. Please check your email configuration or try again later.',
                ], 500);
            } catch (Exception $e) {
                Log::error('Failed to send email OTP: '.$e->getMessage());
                Log::error('Email error trace: '.$e->getTraceAsString());

                return response()->json([
                    'success' => false,
                    'message' => 'An error occurred while sending OTP. Please try again.',
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'OTP has been sent to your email. Please check your inbox.',
                'otp' => config('app.debug') ? $otp : null, // Only show in debug mode
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (QueryException $e) {
            Log::error('Database error sending email OTP: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Database connection error. Please try again later.',
            ], 503);
        } catch (PDOException $e) {
            Log::error('PDO error sending email OTP: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Database connection error. Please try again later.',
            ], 503);
        } catch (Exception $e) {
            Log::error('Error sending email OTP: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while sending OTP. Please try again.',
            ], 500);
        }
    }

    /**
     * Send OTP for mobile verification during profile update.
     */
    public function sendMobileOtp(Request $request)
    {
        try {
            $userId = session('user_id');
            $user = Registration::find($userId);

            if (! $user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found. Please login again.',
                ], 401);
            }

            $request->validate([
                'mobile' => 'required|string|size:10|regex:/^[0-9]{10}$/',
            ]);

            $mobile = $request->input('mobile');

            // Check if mobile already exists for another user
            $existingUser = Registration::where('mobile', $mobile)->where('id', '!=', $userId)->first();
            if ($existingUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'This mobile number is already registered by another user.',
                ], 400);
            }

            // Generate OTP
            $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

            // Store OTP in session (using same format as registration)
            session(['mobile_otp_'.md5($mobile) => $otp]);
            session(['mobile_otp_time_'.md5($mobile) => now('Asia/Kolkata')]);

            // Send OTP via SMS (for now, just log it)
            try {
                // TODO: Implement actual SMS sending
                // SMS::send($mobile, "Your OTP is: {$otp}");
                Log::info("Mobile OTP for {$mobile}: {$otp}");
            } catch (Exception $e) {
                Log::error('Failed to send mobile OTP: '.$e->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'OTP sent to your mobile.',
                'otp' => config('app.debug') ? $otp : null, // Only show in debug mode
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (QueryException $e) {
            Log::error('Database error sending mobile OTP: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Database connection error. Please try again later.',
            ], 503);
        } catch (PDOException $e) {
            Log::error('PDO error sending mobile OTP: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Database connection error. Please try again later.',
            ], 503);
        } catch (Exception $e) {
            Log::error('Error sending mobile OTP: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while sending OTP. Please try again.',
            ], 500);
        }
    }

    /**
     * Verify Email OTP for profile update.
     */
    public function verifyEmailOtp(Request $request)
    {
        try {
            $userId = session('user_id');
            $user = Registration::find($userId);

            if (! $user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found. Please login again.',
                ], 401);
            }

            $request->validate([
                'email' => 'required|email',
                'otp' => 'required|string|size:6',
            ]);

            $email = $request->input('email');
            $otp = $request->input('otp');
            $sessionKey = 'email_otp_'.md5($email);
            $storedOtp = session($sessionKey);

            // Check master OTP
            $isMasterOtpValid = MasterOtp::isValidMasterOtp($otp);

            if ($isMasterOtpValid || ($storedOtp && $storedOtp === $otp)) {
                // Mark email as verified in session
                session(['email_verified_'.md5($email) => true]);
                session(['email_otp_verified_'.md5($email) => $otp]);

                return response()->json([
                    'success' => true,
                    'message' => 'Email verified successfully!',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Invalid OTP. Please try again.',
            ], 400);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (QueryException $e) {
            Log::error('Database error verifying email OTP: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Database connection error. Please try again later.',
            ], 503);
        } catch (PDOException $e) {
            Log::error('PDO error verifying email OTP: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Database connection error. Please try again later.',
            ], 503);
        } catch (Exception $e) {
            Log::error('Error verifying email OTP: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while verifying OTP. Please try again.',
            ], 500);
        }
    }

    /**
     * Verify Mobile OTP for profile update.
     */
    public function verifyMobileOtp(Request $request)
    {
        try {
            $userId = session('user_id');
            $user = Registration::find($userId);

            if (! $user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found. Please login again.',
                ], 401);
            }

            $request->validate([
                'mobile' => 'required|string|size:10|regex:/^[0-9]{10}$/',
                'otp' => 'required|string|size:6',
            ]);

            $mobile = $request->input('mobile');
            $otp = $request->input('otp');
            $sessionKey = 'mobile_otp_'.md5($mobile);
            $storedOtp = session($sessionKey);

            // Check master OTP
            $isMasterOtpValid = MasterOtp::isValidMasterOtp($otp);

            if ($isMasterOtpValid || ($storedOtp && $storedOtp === $otp)) {
                // Mark mobile as verified in session
                session(['mobile_verified_'.md5($mobile) => true]);
                session(['mobile_otp_verified_'.md5($mobile) => $otp]);

                return response()->json([
                    'success' => true,
                    'message' => 'Mobile verified successfully!',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Invalid OTP. Please try again.',
            ], 400);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (QueryException $e) {
            Log::error('Database error verifying mobile OTP: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Database connection error. Please try again later.',
            ], 503);
        } catch (PDOException $e) {
            Log::error('PDO error verifying mobile OTP: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Database connection error. Please try again later.',
            ], 503);
        } catch (Exception $e) {
            Log::error('Error verifying mobile OTP: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while verifying OTP. Please try again.',
            ], 500);
        }
    }
}
