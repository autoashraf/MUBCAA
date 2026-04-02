<?php

namespace App\Services;

use App\Support\PhoneNumber;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class MimSmsService
{
    public function sendOtp(string $mobileNumber, string $otp, ?string $context = null): array
    {
        $message = "Your MUBCAA OTP is {$otp}. It will expire in 15 minutes.";

        return $this->send($mobileNumber, $message, $context);
    }

    public function send(string $mobileNumber, string $message, ?string $context = null): array
    {
        $normalized = $this->normalizeMobileNumber($mobileNumber);

        if (blank($normalized)) {
            throw new RuntimeException('A valid mobile number is required for SMS delivery.');
        }

        if (App::environment(['local', 'testing'])) {
            Log::info('MiMSMS SMS skipped in local/testing.', [
                'mobile_number' => $normalized,
                'message' => $message,
                'context' => $context,
            ]);

            return [
                'success' => true,
                'message' => 'MiMSMS skipped in local/testing.',
                'trxnId' => null,
            ];
        }

        $config = config('services.mimsms');
        $enabled = (bool) ($config['enabled'] ?? false);
        $username = (string) ($config['user'] ?? '');
        $apiKey = (string) ($config['api_key'] ?? '');
        $sender = (string) ($config['sender'] ?? '');
        $endpoint = (string) ($config['url'] ?? '');
        $transactionType = (string) ($config['transaction_type'] ?? 'T');
        $campaignId = $config['campaign_id'] ?? 'null';

        if (! $enabled || blank($username) || blank($apiKey) || blank($sender) || blank($endpoint)) {
            throw new RuntimeException('MiMSMS is not fully configured.');
        }

        $payload = [
            'UserName' => $username,
            'Apikey' => $apiKey,
            'MobileNumber' => $normalized,
            'CampaignId' => $campaignId,
            'SenderName' => $sender,
            'TransactionType' => $transactionType,
            'Message' => $message,
        ];

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->timeout(15)->post($endpoint, $payload);

            $result = $response->json() ?? [];

            Log::info('MiMSMS API response.', [
                'context' => $context,
                'mobile_number' => $normalized,
                'payload' => array_merge($payload, ['Apikey' => '***hidden***']),
                'status' => $response->status(),
                'result' => $result,
            ]);

            if (
                $response->successful()
                && (string) ($result['statusCode'] ?? '') === '200'
                && strtolower((string) ($result['status'] ?? '')) === 'success'
            ) {
                return [
                    'success' => true,
                    'message' => $result['responseResult'] ?? 'SMS sent successfully.',
                    'trxnId' => $result['trxnId'] ?? null,
                ];
            }

            throw new RuntimeException((string) ($result['responseResult'] ?? 'Failed to send SMS.'));
        } catch (Throwable $exception) {
            Log::error('MiMSMS API exception.', [
                'context' => $context,
                'mobile_number' => $normalized,
                'error' => $exception->getMessage(),
            ]);

            throw new RuntimeException('Unexpected error while sending SMS.', previous: $exception);
        }
    }

    private function normalizeMobileNumber(string $mobileNumber): string
    {
        return PhoneNumber::smsDialString($mobileNumber, '+880');
    }
}
