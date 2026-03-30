<?php

namespace App\Http\Controllers;

use App\Mail\RegistrationOtpMail;
use App\Mail\RegistrationSuccessMail;
use App\Models\MasterOtp;
use App\Models\Registration;
use App\Services\IdfyPanService;
use App\Services\MyTodayBulkSmsService;
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

class RegisterController extends Controller
{
    public function __construct(
        private readonly MyTodayBulkSmsService $myTodayBulkSms,
    ) {}

    /**
     * Display the registration page.
     */
    public function index()
    {
        try {
            return view('register.index');
        } catch (Exception $e) {
            Log::error('Error loading registration page: '.$e->getMessage());
            abort(500, 'Unable to load registration page. Please try again later.');
        }
    }

    /**
     * Create PAN verification task.
     */
    public function verifyPan(Request $request)
    {
        try {
            // Rate limiting check - DISABLED for development/testing
            // $key = 'pan_verify_'.request()->ip();
            // if (cache()->has($key)) {
            //     $attempts = cache()->get($key);
            //     if ($attempts >= 5) {
            //         return response()->json([
            //             'success' => false,
            //             'message' => 'Too many verification attempts. Please try again after some time.',
            //         ], 429);
            //     }
            // }
            // cache()->put($key, (cache()->get($key, 0) + 1), now()->addMinutes(15));

            $request->validate([
                'pancardno' => [
                    'required',
                    'string',
                    'size:10',
                    'regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/',
                ],
                'fullname' => [
                    'required',
                    'string',
                    'max:255',
                    'regex:/^[a-zA-Z\s\'-]+$/',
                ],
                'dateofbirth' => [
                    'required',
                    'date',
                    'before:today',
                ],
            ], [
                'pancardno.required' => 'PAN Card Number is required.',
                'pancardno.size' => 'PAN Card Number must be exactly 10 characters.',
                'pancardno.regex' => 'PAN Card Number format is invalid. Format: ABCDE1234F',
                'fullname.required' => 'Full Name / Entity Name is required.',
                'fullname.regex' => 'Name can only contain letters, spaces, apostrophes, and hyphens.',
                'dateofbirth.required' => 'Date of Birth / Date of Incorporation is required.',
                'dateofbirth.before' => 'Date must be a past date.',
            ]);

            $panNo = strtoupper(trim($request->input('pancardno')));
            $fullName = trim($request->input('fullname'));
            $dob = $request->input('dateofbirth');

            // Sanitize inputs
            $panNo = preg_replace('/[^A-Z0-9]/', '', $panNo);
            $fullName = strip_tags($fullName);

            $idfyService = new IdfyPanService;
            $taskResult = $idfyService->createVerificationTask($panNo, $fullName, $dob);

            Log::info("PAN verification task created for: {$panNo}, Request ID: {$taskResult['request_id']}");

            return response()->json([
                'success' => true,
                'message' => 'PAN verification initiated. Please wait...',
                'request_id' => $taskResult['request_id'],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => implode(', ', $e->errors()['pancardno'] ?? $e->errors()['fullname'] ?? $e->errors()['dateofbirth'] ?? ['Validation failed']),
            ], 422);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('PAN verification connection error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Connection timeout. Please try again.',
            ], 500);
        } catch (Exception $e) {
            Log::error('Error creating PAN verification task: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while verifying PAN. Please try again.',
            ], 500);
        }
    }

    /**
     * Check PAN verification status.
     */
    public function checkPanStatus(Request $request)
    {
        try {
            $request->validate([
                'request_id' => [
                    'required',
                    'string',
                    'max:255',
                    'regex:/^[a-zA-Z0-9\-_]+$/',
                ],
            ], [
                'request_id.required' => 'Request ID is required.',
                'request_id.regex' => 'Invalid request ID format.',
            ]);

            $requestId = trim($request->input('request_id'));

            // Sanitize request ID
            $requestId = preg_replace('/[^a-zA-Z0-9\-_]/', '', $requestId);

            $idfyService = new IdfyPanService;
            $statusResult = $idfyService->getTaskStatus($requestId);

            if ($statusResult['status'] === 'completed') {
                $result = $statusResult['result'];
                $sourceOutput = $result['source_output'] ?? null;

                if (! $sourceOutput) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid verification result',
                        'status' => 'completed',
                    ]);
                }

                $panStatus = $sourceOutput['pan_status'] ?? '';
                $nameMatch = $sourceOutput['name_match'] ?? false;
                $dobMatch = $sourceOutput['dob_match'] ?? false;
                $status = $sourceOutput['status'] ?? '';

                $isValid = $status === 'id_found' &&
                          str_contains($panStatus, 'Valid') &&
                          $nameMatch &&
                          $dobMatch;

                // Store verification data in session for later use during registration
                session([
                    'pan_verification_data' => [
                        'request_id' => $requestId,
                        'status' => $status,
                        'pan_status' => $panStatus,
                        'name_match' => $nameMatch,
                        'dob_match' => $dobMatch,
                        'is_verified' => $isValid,
                        'source_output' => $sourceOutput,
                        'full_result' => $statusResult,
                    ],
                ]);

                return response()->json([
                    'success' => $isValid,
                    'status' => 'completed',
                    'pan_status' => $panStatus,
                    'name_match' => $nameMatch,
                    'dob_match' => $dobMatch,
                    'message' => $isValid
                        ? 'PAN verified successfully'
                        : 'PAN verification failed: '.($panStatus ?: 'Invalid PAN or details mismatch'),
                ]);
            } elseif ($statusResult['status'] === 'failed') {
                return response()->json([
                    'success' => false,
                    'status' => 'failed',
                    'message' => 'PAN verification task failed',
                ]);
            }

            return response()->json([
                'success' => true,
                'status' => $statusResult['status'],
                'message' => 'Verification in progress...',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => implode(', ', $e->errors()['request_id'] ?? ['Validation failed']),
            ], 422);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('PAN status check connection error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Connection timeout. Please try again.',
            ], 500);
        } catch (Exception $e) {
            Log::error('Error checking PAN verification status: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while checking verification status. Please try again.',
            ], 500);
        }
    }

    /**
     * Send OTP to email.
     */
    public function sendEmailOtp(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
            ]);

            $email = $request->input('email');

            // Check if email already exists for a different user
            $existing = Registration::where('email', $email)->first();
            $loggedInUserId = (int) $request->session()->get('user_id');

            if ($existing && (! $loggedInUserId || $existing->id !== $loggedInUserId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'This email is already registered.',
                ], 400);
            }

            // Generate OTP
            $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

            // Store OTP in session
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
        } catch (ValidationException $e) {
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
     * Send OTP to mobile.
     */
    public function sendMobileOtp(Request $request)
    {
        try {
            $request->validate([
                'mobile' => 'required|string|size:10|regex:/^[0-9]{10}$/',
            ]);

            $mobile = $request->input('mobile');

            // Check if mobile already exists for a different user
            $existing = Registration::where('mobile', $mobile)->first();
            $loggedInUserId = (int) $request->session()->get('user_id');

            if ($existing && (! $loggedInUserId || $existing->id !== $loggedInUserId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'This mobile number is already registered.',
                ], 400);
            }

            // Generate OTP
            $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            // Store OTP in session
            $otpKey = 'mobile_otp_'.md5($mobile);
            $otpTimeKey = 'mobile_otp_time_'.md5($mobile);
            session([$otpKey => $otp]);
            session([$otpTimeKey => now('Asia/Kolkata')]);

            try {
                $this->myTodayBulkSms->sendOtp($mobile, $otp, 'registration');
            } catch (\RuntimeException $e) {
                session()->forget([$otpKey, $otpTimeKey]);
                Log::error('Mobile OTP SMS failed (registration): '.$e->getMessage());

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to send OTP to your mobile. Please try again later.',
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'OTP sent to your mobile.',
                'otp' => config('app.debug') ? $otp : null,
            ]);
        } catch (ValidationException $e) {
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
     * Verify Email OTP.
     */
    public function verifyEmailOtp(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'otp' => 'required|string|size:6',
            ]);

            $email = $request->input('email');
            $otp = $request->input('otp');
            $sessionKey = 'email_otp_'.md5($email);
            $storedOtp = session($sessionKey);

            // Check master OTP
            $masterOtp = $request->input('master_otp');
            $isMasterOtpValid = $masterOtp && MasterOtp::isValidMasterOtp($masterOtp);

            if ($isMasterOtpValid || ($storedOtp && $storedOtp === $otp)) {
                // Mark email as verified in session
                session(['email_verified_'.md5($email) => true]);
                session(['email_otp_verified_'.md5($email) => $otp]); // Store verified OTP

                return response()->json([
                    'success' => true,
                    'message' => 'Email verified successfully!',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Invalid OTP. Please try again.',
            ], 400);
        } catch (ValidationException $e) {
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
     * Verify Mobile OTP.
     */
    public function verifyMobileOtp(Request $request)
    {
        try {
            $request->validate([
                'mobile' => 'required|string|size:10|regex:/^[0-9]{10}$/',
                'otp' => 'required|string|size:6',
            ]);

            $mobile = $request->input('mobile');
            $otp = $request->input('otp');
            $sessionKey = 'mobile_otp_'.md5($mobile);
            $storedOtp = session($sessionKey);

            // Check master OTP
            $masterOtp = $request->input('master_otp');
            $isMasterOtpValid = $masterOtp && MasterOtp::isValidMasterOtp($masterOtp);

            if ($isMasterOtpValid || ($storedOtp && $storedOtp === $otp)) {
                // Mark mobile as verified in session
                session(['mobile_verified_'.md5($mobile) => true]);
                session(['mobile_otp_verified_'.md5($mobile) => $otp]); // Store verified OTP

                return response()->json([
                    'success' => true,
                    'message' => 'Mobile verified successfully!',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Invalid OTP. Please try again.',
            ], 400);
        } catch (ValidationException $e) {
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

    /**
     * Store registration data.
     */
    public function store(Request $request)
    {
        try {
            // Check if email and mobile are verified
            $email = $request->input('email');
            $mobile = $request->input('mobile');
            $emailVerified = session('email_verified_'.md5($email));
            $mobileVerified = session('mobile_verified_'.md5($mobile));

            if (! $emailVerified || ! $mobileVerified) {
                return back()->with('error', 'Please verify both Email and Mobile OTP before submitting.')
                    ->withInput();
            }

            // Get verified OTPs from session
            $emailOtp = session('email_otp_verified_'.md5($email));
            $mobileOtp = session('mobile_otp_verified_'.md5($mobile));

            // Custom validation rules
            $validated = $request->validate([
                'registration_type' => [
                    'required',
                    'string',
                    'in:individual,entity',
                ],
                'pancardno' => [
                    'required',
                    'string',
                    'size:10',
                    'regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/',
                    'unique:registrations,pancardno',
                ],
                'fullname' => [
                    'required',
                    'string',
                    'max:255',
                    'min:2',
                    'regex:/^[a-zA-Z\s\'-]+$/',
                ],
                'email' => [
                    'required',
                    'email:rfc,dns',
                    'max:255',
                    'unique:registrations,email',
                ],
                'mobile' => [
                    'required',
                    'string',
                    'size:10',
                    'regex:/^[0-9]{10}$/',
                    'unique:registrations,mobile',
                ],
                'dateofbirth' => [
                    'required',
                    'date',
                    'before:today',
                ],
                'declaration' => [
                    'required',
                    'accepted',
                ],
            ], [
                'registration_type.required' => 'Registration type is required.',
                'registration_type.in' => 'Registration type must be either individual or entity.',
                'pancardno.required' => 'PAN Card Number is required.',
                'pancardno.size' => 'PAN Card Number must be exactly 10 characters.',
                'pancardno.regex' => 'PAN Card Number format is invalid. Format: ABCDE1234F',
                'pancardno.unique' => 'This PAN Card Number is already registered.',
                'fullname.required' => 'Full Name / Entity Name is required.',
                'fullname.min' => 'Name must be at least 2 characters.',
                'fullname.regex' => 'Name can only contain letters, spaces, apostrophes, and hyphens.',
                'email.required' => 'Email Address is required.',
                'email.email' => 'Please enter a valid email address.',
                'email.unique' => 'This email address is already registered.',
                'mobile.required' => 'Mobile Number is required.',
                'mobile.size' => 'Mobile Number must be exactly 10 digits.',
                'mobile.regex' => 'Mobile Number must contain only digits.',
                'mobile.unique' => 'This mobile number is already registered.',
                'dateofbirth.required' => 'Date of Birth / Date of Incorporation is required.',
                'dateofbirth.before' => 'Date must be a past date.',
                'declaration.required' => 'You must accept the declaration and authorization to proceed.',
                'declaration.accepted' => 'You must accept the declaration and authorization to proceed.',
            ]);

            // Verify PAN was verified via API
            $panVerificationData = session('pan_verification_data');
            if (! $panVerificationData || ! ($panVerificationData['is_verified'] ?? false)) {
                return back()->with('error', 'Please verify your PAN Card before submitting the form.')
                    ->withInput();
            }

            // Sanitize inputs
            $panNo = strtoupper(trim(preg_replace('/[^A-Z0-9]/', '', $validated['pancardno'])));
            $fullName = trim(strip_tags($validated['fullname']));
            $email = strtolower(trim($validated['email']));
            $mobile = preg_replace('/[^0-9]/', '', $validated['mobile']);

            // Generate a secure random password
            $generatedPassword = $this->generateRandomPassword();

            // Generate unique registration ID
            $registrationId = Registration::generateRegistrationId();

            // Start database transaction
            DB::beginTransaction();
            try {
                // Create registration record with verified OTPs
                $registration = Registration::create([
                    'registrationid' => $registrationId,
                    'pancardno' => $panNo,
                    'registration_type' => $validated['registration_type'],
                    'pan_verified' => true,
                    'fullname' => $fullName,
                    'email' => $email,
                    'email_otp' => $emailOtp,
                    'email_verified' => true,
                    'mobile' => $mobile,
                    'mobile_otp' => $mobileOtp,
                    'mobile_verified' => true,
                    'password' => Hash::make($generatedPassword),
                    'dateofbirth' => $validated['dateofbirth'],
                    'registrationdate' => now('Asia/Kolkata')->toDateString(),
                    'registrationtime' => now('Asia/Kolkata')->toTimeString(),
                    'status' => 'approved',
                ]);

                // Create PAN verification record
                \App\Models\PanVerification::create([
                    'user_id' => $registration->id,
                    'pan_number' => $panNo,
                    'request_id' => $panVerificationData['request_id'],
                    'status' => 'completed',
                    'is_verified' => true,
                    'verification_data' => $panVerificationData['full_result'] ?? null,
                    'full_name' => $fullName,
                    'date_of_birth' => $validated['dateofbirth'],
                    'pan_status' => $panVerificationData['pan_status'] ?? null,
                    'name_match' => $panVerificationData['name_match'] ?? false,
                    'dob_match' => $panVerificationData['dob_match'] ?? false,
                ]);

                DB::commit();
            } catch (Exception $e) {
                DB::rollBack();
                Log::error('Error creating registration: '.$e->getMessage());
                throw $e;
            }

            // Clear session data
            session()->forget('email_otp_'.md5($email));
            session()->forget('email_otp_time_'.md5($email));
            session()->forget('email_verified_'.md5($email));
            session()->forget('email_otp_verified_'.md5($email));
            session()->forget('mobile_otp_'.md5($mobile));
            session()->forget('mobile_otp_time_'.md5($mobile));
            session()->forget('mobile_verified_'.md5($mobile));
            session()->forget('mobile_otp_verified_'.md5($mobile));
            session()->forget('pan_verification_data');

            // Send registration success email with credentials
            try {
                $loginUrl = route('login.index');
                // Generate password update token
                $updateToken = Str::random(64);
                DB::table('password_reset_tokens')->updateOrInsert(
                    ['email' => $validated['email']],
                    [
                        'token' => Hash::make($updateToken),
                        'created_at' => now(),
                    ]
                );
                $updatePasswordUrl = route('login.update-password', ['token' => $updateToken, 'email' => $validated['email']]);

                // Determine username (PAN or email)
                $username = $validated['pancardno'];

                Mail::to($validated['email'])->send(new RegistrationSuccessMail(
                    $username,
                    $validated['email'],
                    $generatedPassword,
                    $registrationId,
                    $loginUrl,
                    $updatePasswordUrl
                ));
                Log::info("Registration success email sent to {$validated['email']}");
            } catch (Exception $e) {
                Log::error('Failed to send registration success email: '.$e->getMessage());
                // Don't fail registration if email fails
            }

            return redirect()->route('login.index')
                ->with('success', 'Registration successful! Your Registration ID is: '.$registrationId.'. Please check your email for login credentials. Login to continue.');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (QueryException $e) {
            Log::error('Database error storing registration: '.$e->getMessage());

            return back()->with('error', 'Database connection error. Please try again later.')
                ->withInput();
        } catch (PDOException $e) {
            Log::error('PDO error storing registration: '.$e->getMessage());

            return back()->with('error', 'Database connection error. Please try again later.')
                ->withInput();
        } catch (Exception $e) {
            Log::error('Error storing registration: '.$e->getMessage());

            return back()->with('error', 'An error occurred during registration. Please try again.')
                ->withInput();
        }
    }

    /**
     * Show OTP verification page.
     */
    public function showVerify()
    {
        try {
            $registrationId = session('pending_registration_id');

            if (! $registrationId) {
                return redirect()->route('register.index')
                    ->with('error', 'Please start the registration process first.');
            }

            $registration = Registration::where('registrationid', $registrationId)->first();

            if (! $registration) {
                return redirect()->route('register.index')
                    ->with('error', 'Registration not found.');
            }

            return view('register.verify', compact('registration'));
        } catch (Exception $e) {
            Log::error('Error loading OTP verification page: '.$e->getMessage());

            return redirect()->route('register.index')
                ->with('error', 'An error occurred. Please try again.');
        }
    }

    /**
     * Verify OTPs.
     */
    public function verifyOtp(Request $request)
    {
        try {
            $masterOtp = $request->input('master_otp');

            // If master OTP is provided, individual OTPs are optional
            $rules = [];
            if (! $masterOtp) {
                $rules = [
                    'email_otp' => 'required|string|size:6',
                    'mobile_otp' => 'required|string|size:6',
                ];
            } else {
                $rules = [
                    'email_otp' => 'nullable|string|size:6',
                    'mobile_otp' => 'nullable|string|size:6',
                    'master_otp' => 'required|string|size:6',
                ];
            }

            $request->validate($rules);

            $registrationId = session('pending_registration_id');

            if (! $registrationId) {
                return back()->with('error', 'Session expired. Please start registration again.');
            }

            $registration = Registration::where('registrationid', $registrationId)->first();

            if (! $registration) {
                return redirect()->route('register.index')
                    ->with('error', 'Registration not found.');
            }

            $emailOtp = $request->input('email_otp');
            $mobileOtp = $request->input('mobile_otp');

            $emailVerified = false;
            $mobileVerified = false;

            // Check if master OTP is provided and valid
            $isMasterOtpValid = $masterOtp && MasterOtp::isValidMasterOtp($masterOtp);

            if ($isMasterOtpValid) {
                // Master OTP verifies both email and mobile
                $emailVerified = true;
                $mobileVerified = true;
                $registration->email_verified = true;
                $registration->mobile_verified = true;
            } else {
                // Verify Email OTP individually
                if ($registration->email_otp === $emailOtp) {
                    $emailVerified = true;
                    $registration->email_verified = true;
                }

                // Verify Mobile OTP individually
                if ($registration->mobile_otp === $mobileOtp) {
                    $mobileVerified = true;
                    $registration->mobile_verified = true;
                }
            }

            if (! $emailVerified) {
                return back()->with('error', 'Invalid Email OTP. Please try again.');
            }

            if (! $mobileVerified) {
                return back()->with('error', 'Invalid Mobile OTP. Please try again.');
            }

            // Both OTPs verified, complete registration
            $registration->status = 'pending';
            $registration->save();

            // Clear session
            session()->forget('pending_registration_id');

            return redirect()->route('login.index')
                ->with('success', 'Registration successful! Your Registration ID is: '.$registration->registrationid.'. Login to continue.');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (Exception $e) {
            Log::error('Error verifying OTP: '.$e->getMessage());

            return back()->with('error', 'An error occurred while verifying OTP. Please try again.');
        }
    }

    /**
     * Resend OTPs.
     */
    public function resendOtp(Request $request)
    {
        try {
            $registrationId = session('pending_registration_id');

            if (! $registrationId) {
                return back()->with('error', 'Session expired. Please start registration again.');
            }

            $registration = Registration::where('registrationid', $registrationId)->first();

            if (! $registration) {
                return back()->with('error', 'Registration not found.');
            }

            // Generate new OTPs
            $emailOtp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
            $mobileOtp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

            $registration->email_otp = $emailOtp;
            $registration->mobile_otp = $mobileOtp;
            $registration->save();

            // Send Email OTP
            try {
                Mail::to($registration->email)->send(new RegistrationOtpMail($emailOtp));
                Log::info("Resent Email OTP successfully to {$registration->email}");
            } catch (\Illuminate\Mail\MailException $mailException) {
                Log::error('Email sending error (resend): '.$mailException->getMessage());
                Log::error('Email error trace: '.$mailException->getTraceAsString());
                $previousException = $mailException->getPrevious();
                if ($previousException) {
                    Log::error('Previous exception: '.$previousException->getMessage());
                }

                return back()->with('error', 'Failed to resend email OTP. Please check your email configuration or try again later.');
            } catch (Exception $e) {
                Log::error('Failed to resend email OTP: '.$e->getMessage());
                Log::error('Email error trace: '.$e->getTraceAsString());

                return back()->with('error', 'An error occurred while resending email OTP. Please try again.');
            }

            // Send Mobile OTP (SMS - for now just log, implement SMS service later)
            Log::info("Resent Mobile OTP for {$registration->mobile}: {$mobileOtp}");

            return back()->with('success', 'OTPs have been resent to your email and mobile.');
        } catch (Exception $e) {
            Log::error('Error resending OTP: '.$e->getMessage());

            return back()->with('error', 'An error occurred while resending OTP. Please try again.');
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
}
