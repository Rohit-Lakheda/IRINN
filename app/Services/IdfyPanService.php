<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IdfyPanService
{
    protected string $accountId;

    protected string $apiKey;

    protected string $baseUrl;

    public function __construct()
    {
        $this->accountId = config('services.idfy.account_id');
        $this->apiKey = config('services.idfy.api_key');
        $this->baseUrl = config('services.idfy.base_url');
    }

    /**
     * Create a PAN verification task.
     *
     * @param  string  $dob  Format: YYYY-MM-DD
     *
     * @throws Exception
     */
    public function createVerificationTask(string $panNumber, string $fullName, string $dob): array
    {
        try {
            $taskId = $this->generateTaskId();
            $groupId = $this->generateGroupId();

            $response = Http::timeout(60)->withHeaders([
                'account-id' => $this->accountId,
                'api-key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/v3/tasks/async/verify_with_source/ind_pan", [
                'task_id' => $taskId,
                'group_id' => $groupId,
                'data' => [
                    'id_number' => strtoupper($panNumber),
                    'full_name' => strtoupper($fullName),
                    'dob' => $dob,
                ],
            ]);

            if (! $response->successful()) {
                Log::error('Idfy API Error: '.$response->body());
                throw new Exception('Failed to create PAN verification task: '.$response->body());
            }

            $data = $response->json();

            if (empty($data) || ! isset($data['request_id'])) {
                Log::error('Idfy API Response: '.json_encode($data));
                throw new Exception('Invalid response from Idfy API: Missing request_id');
            }

            return [
                'success' => true,
                'request_id' => $data['request_id'],
                'task_id' => $taskId,
                'group_id' => $groupId,
            ];
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('IdfyPanService createVerificationTask connection error: '.$e->getMessage());
            throw new Exception('Connection timeout. The PAN API is taking too long to respond. Please try again.');
        } catch (\Illuminate\Http\Client\RequestException $e) {
            Log::error('IdfyPanService createVerificationTask request error: '.$e->getMessage());
            throw new Exception('Request failed. Please check your PAN details and try again.');
        } catch (Exception $e) {
            Log::error('IdfyPanService createVerificationTask error: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Get task status by request ID.
     *
     * @throws Exception
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
                Log::error('Idfy API Error: '.$response->body());
                throw new Exception('Failed to get PAN verification status: '.$response->body());
            }

            $data = $response->json();

            if (empty($data) || ! isset($data[0])) {
                Log::error('Idfy API GET Response: '.json_encode($data));
                throw new Exception('Invalid response from Idfy API: Expected array with task data');
            }

            $task = $data[0];

            return [
                'success' => true,
                'status' => $task['status'] ?? 'unknown',
                'result' => $task['result'] ?? null,
                'task' => $task,
            ];
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('IdfyPanService getTaskStatus connection error: '.$e->getMessage());
            throw new Exception('Connection timeout while checking verification status. Please try again.');
        } catch (\Illuminate\Http\Client\RequestException $e) {
            Log::error('IdfyPanService getTaskStatus request error: '.$e->getMessage());
            throw new Exception('Failed to check verification status. Please try again.');
        } catch (Exception $e) {
            Log::error('IdfyPanService getTaskStatus error: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Verify PAN with name and DOB match.
     *
     * @throws Exception
     */
    public function verifyPan(string $panNumber, string $fullName, string $dob, int $maxRetries = 10, int $retryDelay = 2): array
    {
        // Create verification task
        $taskResult = $this->createVerificationTask($panNumber, $fullName, $dob);
        $requestId = $taskResult['request_id'];

        // Poll for result
        $retries = 0;
        while ($retries < $maxRetries) {
            sleep($retryDelay);
            $statusResult = $this->getTaskStatus($requestId);

            if ($statusResult['status'] === 'completed') {
                $result = $statusResult['result'];
                $sourceOutput = $result['source_output'] ?? null;

                if (! $sourceOutput) {
                    throw new Exception('Invalid verification result');
                }

                $panStatus = $sourceOutput['pan_status'] ?? '';
                $nameMatch = $sourceOutput['name_match'] ?? false;
                $dobMatch = $sourceOutput['dob_match'] ?? false;
                $status = $sourceOutput['status'] ?? '';

                $isValid = $status === 'id_found' &&
                          str_contains($panStatus, 'Valid') &&
                          $nameMatch &&
                          $dobMatch;

                return [
                    'success' => $isValid,
                    'request_id' => $requestId,
                    'pan_status' => $panStatus,
                    'name_match' => $nameMatch,
                    'dob_match' => $dobMatch,
                    'status' => $status,
                    'message' => $isValid
                        ? 'PAN verified successfully'
                        : 'PAN verification failed: '.($panStatus ?: 'Invalid PAN or details mismatch'),
                    'source_output' => $sourceOutput,
                    'full_result' => $statusResult['task'] ?? null,
                ];
            } elseif ($statusResult['status'] === 'failed') {
                throw new Exception('PAN verification task failed');
            }

            $retries++;
        }

        throw new Exception('PAN verification timeout. Please try again.');
    }

    /**
     * Generate a unique task ID.
     */
    protected function generateTaskId(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xFFFF),
            mt_rand(0, 0xFFFF),
            mt_rand(0, 0xFFFF),
            mt_rand(0, 0x0FFF) | 0x4000,
            mt_rand(0, 0x3FFF) | 0x8000,
            mt_rand(0, 0xFFFF),
            mt_rand(0, 0xFFFF),
            mt_rand(0, 0xFFFF)
        );
    }

    /**
     * Generate a unique group ID.
     */
    protected function generateGroupId(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xFFFF),
            mt_rand(0, 0xFFFF),
            mt_rand(0, 0xFFFF),
            mt_rand(0, 0x0FFF) | 0x4000,
            mt_rand(0, 0x3FFF) | 0x8000,
            mt_rand(0, 0xFFFF),
            mt_rand(0, 0xFFFF),
            mt_rand(0, 0xFFFF)
        );
    }
}
