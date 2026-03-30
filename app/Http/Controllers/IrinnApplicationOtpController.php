<?php

namespace App\Http\Controllers;

use App\Mail\RegistrationOtpMail;
use App\Models\MasterOtp;
use App\Models\Registration;
use App\Services\MyTodayBulkSmsService;
use App\Support\IrinnApplicationFlowOtp;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Throwable;

class IrinnApplicationOtpController extends Controller
{
    public function __construct(
        private readonly MyTodayBulkSmsService $myTodayBulkSms,
    ) {}

    public function sendEmailOtp(Request $request): JsonResponse
    {
        try {
            if (! $request->session()->has('user_id')) {
                return response()->json(['success' => false, 'message' => 'Please log in again.'], 401);
            }

            $request->validate([
                'email' => ['required', 'email'],
            ]);

            $email = IrinnApplicationFlowOtp::normalizeEmail($request->input('email'));

            $existing = Registration::query()->where('email', $email)->first();
            $loggedInUserId = (int) $request->session()->get('user_id');

            if ($existing && (! $loggedInUserId || $existing->id !== $loggedInUserId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'This email is already registered to another account.',
                ], 400);
            }

            $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            session([IrinnApplicationFlowOtp::emailOtpSessionKey($email) => $otp]);
            session()->forget(IrinnApplicationFlowOtp::emailVerifiedSessionKey($email));

            try {
                Mail::to($email)->send(new RegistrationOtpMail($otp));
                Log::info("IRINN flow: email OTP sent to {$email}");
            } catch (Throwable $mailException) {
                Log::error('IRINN flow email OTP send failed: '.$mailException->getMessage());

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to send email OTP. Check mail configuration and try again.',
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'OTP has been sent to your email.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (Throwable $e) {
            Log::error('IRINN flow sendEmailOtp: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Unable to send OTP. Please try again.',
            ], 500);
        }
    }

    public function verifyEmailOtp(Request $request): JsonResponse
    {
        try {
            if (! $request->session()->has('user_id')) {
                return response()->json(['success' => false, 'message' => 'Please log in again.'], 401);
            }

            $request->validate([
                'email' => ['required', 'email'],
                'otp' => ['required', 'string', 'size:6'],
            ]);

            $email = IrinnApplicationFlowOtp::normalizeEmail($request->input('email'));
            $otp = $request->input('otp');
            $stored = session(IrinnApplicationFlowOtp::emailOtpSessionKey($email));

            $masterOtp = $request->input('master_otp');
            $masterOk = is_string($masterOtp) && MasterOtp::isValidMasterOtp($masterOtp);

            if ($masterOk || ($stored && hash_equals((string) $stored, (string) $otp))) {
                session([IrinnApplicationFlowOtp::emailVerifiedSessionKey($email) => true]);

                return response()->json([
                    'success' => true,
                    'message' => 'Email verified successfully.',
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
        } catch (Throwable $e) {
            Log::error('IRINN flow verifyEmailOtp: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Unable to verify OTP.',
            ], 500);
        }
    }

    public function sendMobileOtp(Request $request): JsonResponse
    {
        try {
            if (! $request->session()->has('user_id')) {
                return response()->json(['success' => false, 'message' => 'Please log in again.'], 401);
            }

            $request->validate([
                'mobile' => ['required', 'string', 'size:10', 'regex:/^[0-9]{10}$/'],
            ]);

            $mobile = IrinnApplicationFlowOtp::normalizeMobile($request->input('mobile'));

            $existing = Registration::query()->where('mobile', $mobile)->first();
            $loggedInUserId = (int) $request->session()->get('user_id');

            if ($existing && (! $loggedInUserId || $existing->id !== $loggedInUserId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'This mobile number is already registered to another account.',
                ], 400);
            }

            $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            session([IrinnApplicationFlowOtp::mobileOtpSessionKey($mobile) => $otp]);
            session()->forget(IrinnApplicationFlowOtp::mobileVerifiedSessionKey($mobile));

            try {
                $this->myTodayBulkSms->sendOtp($mobile, $otp, 'irinn_application');
            } catch (\RuntimeException $e) {
                session()->forget(IrinnApplicationFlowOtp::mobileOtpSessionKey($mobile));
                Log::error('IRINN flow mobile OTP SMS failed: '.$e->getMessage());

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to send OTP to your mobile. Please try again later.',
                ], 500);
            }

            return response()->json(array_filter([
                'success' => true,
                'message' => 'OTP has been sent to your mobile.',
                'otp' => config('app.debug') ? $otp : null,
            ], fn ($v) => $v !== null));
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (Throwable $e) {
            Log::error('IRINN flow sendMobileOtp: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Unable to send OTP. Please try again.',
            ], 500);
        }
    }

    public function verifyMobileOtp(Request $request): JsonResponse
    {
        try {
            if (! $request->session()->has('user_id')) {
                return response()->json(['success' => false, 'message' => 'Please log in again.'], 401);
            }

            $request->validate([
                'mobile' => ['required', 'string', 'size:10', 'regex:/^[0-9]{10}$/'],
                'otp' => ['required', 'string', 'size:6'],
            ]);

            $mobile = IrinnApplicationFlowOtp::normalizeMobile($request->input('mobile'));
            $otp = $request->input('otp');
            $stored = session(IrinnApplicationFlowOtp::mobileOtpSessionKey($mobile));

            $masterOtp = $request->input('master_otp');
            $masterOk = is_string($masterOtp) && MasterOtp::isValidMasterOtp($masterOtp);

            if ($masterOk || ($stored && hash_equals((string) $stored, (string) $otp))) {
                session([IrinnApplicationFlowOtp::mobileVerifiedSessionKey($mobile) => true]);

                return response()->json([
                    'success' => true,
                    'message' => 'Mobile verified successfully.',
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
        } catch (Throwable $e) {
            Log::error('IRINN flow verifyMobileOtp: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Unable to verify OTP.',
            ], 500);
        }
    }
}
