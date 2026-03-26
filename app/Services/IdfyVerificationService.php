<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IdfyVerificationService
{
    protected string $accountId;

    protected string $apiKey;

    protected string $baseUrl;

    protected string $taskId;

    protected string $groupId;

    public function __construct()
    {
        $this->accountId = config('services.idfy.account_id');
        $this->apiKey = config('services.idfy.api_key');
        $this->baseUrl = config('services.idfy.base_url');
        $this->taskId = '74f4c926-250c-43ca-9c53-453e87ceacd1';
        $this->groupId = '8e16424a-58fc-4ba4-ab20-5bc8e7c3c41e';
    }

    /**
     * Verify GSTIN.
     */
    public function verifyGst(string $gstin): array
    {
        try {
            $gstin = strtoupper(trim($gstin));
            if (! preg_match('/^[0-9A-Z]{15}$/', $gstin)) {
                throw new Exception('GSTIN must be a 15-character alphanumeric value.');
            }

            $response = Http::timeout(60)->withHeaders([
                'account-id' => $this->accountId,
                'api-key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/v3/tasks/async/verify_with_source/ind_gst_certificate", [
                'task_id' => $this->taskId,
                'group_id' => $this->groupId,
                'data' => ['gstin' => $gstin],
            ]);

            if (! $response->successful()) {
                Log::error('Idfy GST API Error: '.$response->body());
                throw new Exception('Failed to create GST verification task: '.$response->body());
            }

            $data = $response->json();


            if (empty($data) || ! isset($data['request_id'])) {
                throw new Exception('Invalid response from Idfy API: Missing request_id');
            }

            return [
                'success' => true,
                'request_id' => $data['request_id'],
            ];
        } catch (Exception $e) {
            Log::error('IdfyVerificationService verifyGst error: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Verify UDYAM Aadhaar.
     */
    public function verifyUdyam(string $uamNumber): array
    {
        try {
            $uamNumber = trim($uamNumber);
            if (empty($uamNumber)) {
                throw new Exception('UAM number is required.');
            }

            $response = Http::timeout(60)->withHeaders([
                'account-id' => $this->accountId,
                'api-key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/v3/tasks/async/verify_with_source/udyam_aadhaar", [
                'task_id' => $this->taskId,
                'group_id' => $this->groupId,
                'data' => ['uam_number' => $uamNumber],
            ]);

            if (! $response->successful()) {
                Log::error('Idfy UDYAM API Error: '.$response->body());
                throw new Exception('Failed to create UDYAM verification task: '.$response->body());
            }

            $data = $response->json();

            if (empty($data) || ! isset($data['request_id'])) {
                throw new Exception('Invalid response from Idfy API: Missing request_id');
            }

            return [
                'success' => true,
                'request_id' => $data['request_id'],
            ];
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('IdfyVerificationService verifyUdyam connection error: '.$e->getMessage());
            throw new Exception('Connection timeout. The UDYAM API is taking too long to respond. Please try again.');
        } catch (\Illuminate\Http\Client\RequestException $e) {
            Log::error('IdfyVerificationService verifyUdyam request error: '.$e->getMessage());
            throw new Exception('Request failed. Please check your UDYAM number and try again.');
        } catch (Exception $e) {
            Log::error('IdfyVerificationService verifyUdyam error: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Verify MCA (CIN).
     */
    public function verifyMca(string $cin): array
    {
        try {
            $cin = trim($cin);
            if (empty($cin)) {
                throw new Exception('CIN is required.');
            }

            $response = Http::timeout(60)->withHeaders([
                'account-id' => $this->accountId,
                'api-key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/v3/tasks/async/verify_with_source/ind_mca", [
                'task_id' => $this->taskId,
                'group_id' => $this->groupId,
                'data' => ['cin' => $cin],
            ]);

            if (! $response->successful()) {
                Log::error('Idfy MCA API Error: '.$response->body());
                throw new Exception('Failed to create MCA verification task: '.$response->body());
            }

            $data = $response->json();

            if (empty($data) || ! isset($data['request_id'])) {
                throw new Exception('Invalid response from Idfy API: Missing request_id');
            }

            return [
                'success' => true,
                'request_id' => $data['request_id'],
            ];
        } catch (Exception $e) {
            Log::error('IdfyVerificationService verifyMca error: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Verify ROC IEC.
     */
    public function verifyRocIec(string $iec): array
    {
        try {
            $iec = trim($iec);
            if (empty($iec)) {
                throw new Exception('Import Export Code is required.');
            }

            $response = Http::timeout(60)->withHeaders([
                'account-id' => $this->accountId,
                'api-key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/v3/tasks/async/verify_with_source/roc_iec", [
                'task_id' => $this->taskId,
                'group_id' => $this->groupId,
                'data' => ['import_export_code' => $iec],
            ]);

            if (! $response->successful()) {
                Log::error('Idfy ROC IEC API Error: '.$response->body());
                throw new Exception('Failed to create ROC IEC verification task: '.$response->body());
            }

            $data = $response->json();

            if (empty($data) || ! isset($data['request_id'])) {
                throw new Exception('Invalid response from Idfy API: Missing request_id');
            }

            return [
                'success' => true,
                'request_id' => $data['request_id'],
            ];
        } catch (Exception $e) {
            Log::error('IdfyVerificationService verifyRocIec error: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Get task status by request ID.
     */
    public function getTaskStatus(string $requestId): array
    {
        try {
            $response = Http::timeout(30)->withHeaders([
                'account-id' => $this->accountId,
                'api-key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->get("{$this->baseUrl}/v3/tasks", [
                'request_id' => $requestId,
            ]);

            if (! $response->successful()) {
                Log::error('Idfy API GET Error: '.$response->body());
                throw new Exception('Failed to get verification status: '.$response->body());
            }

            $data = $response->json();

            // Handle both array and object responses
            $isList = is_array($data) && $this->isListArray($data);
            if ($isList && count($data) > 0) {
                $taskData = $data[0];
            } else {
                $taskData = $data;
            }

            return [
                'success' => true,
                'status' => $taskData['status'] ?? 'unknown',
                'result' => $taskData['result'] ?? null,
                'task' => $taskData,
            ];
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('IdfyVerificationService getTaskStatus connection error: '.$e->getMessage());
            throw new Exception('Connection timeout while checking verification status. Please try again.');
        } catch (\Illuminate\Http\Client\RequestException $e) {
            Log::error('IdfyVerificationService getTaskStatus request error: '.$e->getMessage());
            throw new Exception('Failed to check verification status. Please try again.');
        } catch (Exception $e) {
            Log::error('IdfyVerificationService getTaskStatus error: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Check if array is a list (sequential numeric keys).
     */
    protected function isListArray(array $array): bool
    {
        if (function_exists('array_is_list')) {
            return array_is_list($array);
        }
        $expected = 0;
        foreach ($array as $key => $_value) {
            if ($key !== $expected) {
                return false;
            }
            $expected++;
        }

        return true;
    }
}
