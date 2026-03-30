<?php

namespace App\Support;

class IrinnApplicationFlowOtp
{
    public static function normalizeEmail(string $email): string
    {
        return strtolower(trim($email));
    }

    public static function normalizeMobile(string $mobile): string
    {
        return preg_replace('/\D/', '', trim($mobile)) ?? '';
    }

    public static function emailOtpSessionKey(string $email): string
    {
        return 'irinn_flow_email_otp_'.md5(self::normalizeEmail($email));
    }

    public static function emailVerifiedSessionKey(string $email): string
    {
        return 'irinn_flow_email_verified_'.md5(self::normalizeEmail($email));
    }

    public static function mobileOtpSessionKey(string $mobile): string
    {
        return 'irinn_flow_mobile_otp_'.md5(self::normalizeMobile($mobile));
    }

    public static function mobileVerifiedSessionKey(string $mobile): string
    {
        return 'irinn_flow_mobile_verified_'.md5(self::normalizeMobile($mobile));
    }

    public static function isEmailVerifiedInFlow(?string $email): bool
    {
        if ($email === null || $email === '') {
            return false;
        }

        return (bool) session(self::emailVerifiedSessionKey($email));
    }

    public static function isMobileVerifiedInFlow(?string $mobile): bool
    {
        if ($mobile === null || $mobile === '') {
            return false;
        }

        return (bool) session(self::mobileVerifiedSessionKey($mobile));
    }

    /**
     * @param  array<int, array{0: string, 1: string}>  $pairs  [emailField, mobileField]
     * @return array<string, string>
     */
    public static function assertPairsVerifiedInSession(\Illuminate\Http\Request $request, array $pairs): array
    {
        $errors = [];
        foreach ($pairs as [$emailField, $mobileField]) {
            $e = (string) $request->input($emailField, '');
            $m = (string) $request->input($mobileField, '');
            if ($e !== '' && ! self::isEmailVerifiedInFlow($e)) {
                $errors[$emailField] = 'Verify this email with OTP before submitting.';
            }
            if ($m !== '' && ! self::isMobileVerifiedInFlow($m)) {
                $errors[$mobileField] = 'Verify this mobile number with OTP before submitting.';
            }
        }

        return $errors;
    }
}
