<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class MyTodayBulkSmsService
{
    /**
     * Send a one-time password SMS via MyToday SingleMsgApi (application/x-www-form-urlencoded).
     *
     * @param  string  $messageKey  One of: registration, profile_update, irinn_application, ix_application, default
     *
     * @throws RuntimeException When SMS is enabled but misconfigured or the gateway fails.
     */
    public function sendOtp(string $mobile10Digits, string $otp, string $messageKey = 'default'): void
    {
        /** @var array<string, mixed> $config */
        $config = config('services.mytoday', []);

        if (! ($config['enabled'] ?? false)) {
            if (config('app.debug')) {
                Log::debug('MyToday SMS disabled; OTP for local testing', [
                    'mobile_suffix' => substr($mobile10Digits, -4),
                    'otp' => $otp,
                ]);
            } else {
                Log::info('MyToday SMS disabled (MYTODAY_SMS_ENABLED=false)', [
                    'mobile_suffix' => substr($mobile10Digits, -4),
                ]);
            }

            return;
        }

        $this->assertConfigured($config);

        $text = str_replace(':otp', $otp, $this->resolveMessageTemplate($config, $messageKey));
        $to = $this->formatDestination($mobile10Digits, (string) ($config['country_prefix'] ?? '91'));

        try {
            $response = Http::asForm()
                ->timeout((int) ($config['timeout'] ?? 30))
                ->post((string) $config['url'], [
                    'feedid' => $config['feedid'],
                    'username' => $config['username'],
                    'password' => $config['password'],
                    'To' => $to,
                    'Text' => $text,
                    'templateid' => $config['templateid'],
                    'senderid' => $config['senderid'],
                ]);

            if (! $response->successful()) {
                Log::warning('MyToday SMS HTTP error', [
                    'status' => $response->status(),
                    'body_snippet' => mb_substr($response->body(), 0, 200),
                ]);
                throw new RuntimeException('SMS gateway returned an error.');
            }

            $snippet = mb_substr(trim($response->body()), 0, 500);
            if ($snippet !== '') {
                Log::debug('MyToday SMS response', ['body_snippet' => $snippet]);
            }
        } catch (Throwable $e) {
            if ($e instanceof RuntimeException) {
                throw $e;
            }
            Log::error('MyToday SMS request failed: '.$e->getMessage());

            throw new RuntimeException('SMS gateway request failed.', 0, $e);
        }
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function assertConfigured(array $config): void
    {
        foreach (['url', 'feedid', 'username', 'password', 'templateid', 'senderid'] as $key) {
            if (empty($config[$key])) {
                throw new RuntimeException('SMS service is not fully configured.');
            }
        }
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function resolveMessageTemplate(array $config, string $messageKey): string
    {
        $default = (string) ($config['text'] ?? 'Dear User, Your OTP for mobile verification is :otp IRINN/NIXI');

        $specific = match ($messageKey) {
            'registration' => $config['text_registration'] ?? null,
            'profile_update' => $config['text_profile_update'] ?? null,
            'irinn_application' => $config['text_irinn_application'] ?? null,
            'ix_application' => $config['text_ix_application'] ?? null,
            default => null,
        };

        $template = is_string($specific) && $specific !== '' ? $specific : $default;

        if (! str_contains($template, ':otp')) {
            throw new RuntimeException('SMS message template must contain :otp placeholder.');
        }

        return $template;
    }

    private function formatDestination(string $mobile10Digits, string $countryPrefix): string
    {
        $digits = preg_replace('/\D/', '', $mobile10Digits) ?? '';

        if (strlen($digits) !== 10) {
            throw new RuntimeException('Invalid mobile number for SMS.');
        }

        $prefix = preg_replace('/\D/', '', $countryPrefix) ?? '';

        return $prefix.$digits;
    }
}
