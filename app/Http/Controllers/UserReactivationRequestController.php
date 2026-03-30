<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\ApplicationReactivationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UserReactivationRequestController extends Controller
{
    public function store(Request $request, $applicationId)
    {
        try {
            $userId = (int) session('user_id');

            $application = Application::query()
                ->where('id', $applicationId)
                ->where('user_id', $userId)
                ->where('application_type', 'IRINN')
                ->firstOrFail();

            if (($application->service_status ?? 'live') !== 'disconnected') {
                return back()->with('error', 'Reactivation is only available for disconnected applications.');
            }

            $validated = $request->validate([
                'user_notes' => 'nullable|string|max:2000',
            ]);

            $activeRequest = ApplicationReactivationRequest::query()
                ->where('application_id', $application->id)
                ->whereIn('status', ['pending', 'approved', 'invoiced', 'paid'])
                ->latest()
                ->first();

            if ($activeRequest) {
                return back()->with('info', 'Your reactivation request is already submitted and is being processed.');
            }

            ApplicationReactivationRequest::query()->create([
                'application_id' => $application->id,
                'user_id' => $userId,
                'status' => 'pending',
                'user_notes' => $validated['user_notes'] ?? null,
            ]);

            return back()->with('success', 'Reactivation request submitted successfully.');
        } catch (\Exception $e) {
            Log::error('Error submitting reactivation request: '.$e->getMessage());

            return back()->with('error', 'Unable to submit reactivation request. Please try again.');
        }
    }
}
