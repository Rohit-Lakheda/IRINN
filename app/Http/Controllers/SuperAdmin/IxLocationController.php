<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\IxLocation;
use App\Models\IxLocationHistory;
use App\Models\SuperAdmin;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class IxLocationController extends Controller
{
    /**
     * Display IX location management screen.
     */
    public function index()
    {
        try {
            $locations = IxLocation::orderBy('node_type')
                ->orderBy('state')
                ->orderBy('name')
                ->get();

            return view('superadmin.ix.locations', compact('locations'));
        } catch (Exception $e) {
            Log::error('Failed to load IX locations: '.$e->getMessage());

            return redirect()->route('superadmin.dashboard')
                ->with('error', 'Unable to load IX locations right now.');
        }
    }

    /**
     * Store a new IX location.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:ix_locations,name',
            'state' => 'required|string|max:255',
            'city' => 'nullable|string|max:255',
            'node_type' => ['required', Rule::in(['metro', 'edge'])],
            'switch_details' => 'nullable|string|max:255',
            'ports' => 'nullable|integer|min:1|max:1000',
            'nodal_officer' => 'nullable|string|max:255',
            'zone' => 'nullable|string|max:255',
            'p2p_capacity' => 'nullable|string|max:255',
            'p2p_provider' => 'nullable|string|max:255',
            'connected_main_node' => 'nullable|string|max:255',
            'p2p_arc' => 'nullable|numeric|min:0',
            'colocation_provider' => 'nullable|string|max:255',
            'colocation_arc' => 'nullable|numeric|min:0',
        ]);

        try {
            $location = IxLocation::create($validated);

            // Log to history
            $this->logHistory($location, null, $location->toArray(), 'created');

            return redirect()->route('superadmin.ix-locations.index')
                ->with('success', 'IX location created successfully.');
        } catch (Exception $e) {
            Log::error('Failed to create IX location: '.$e->getMessage());

            return back()->with('error', 'Unable to create location. Please try again.')
                ->withInput();
        }
    }

    /**
     * Update an IX location.
     */
    public function update(Request $request, IxLocation $ixLocation)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('ix_locations', 'name')->ignore($ixLocation->id),
            ],
            'state' => 'required|string|max:255',
            'city' => 'nullable|string|max:255',
            'node_type' => ['required', Rule::in(['metro', 'edge'])],
            'switch_details' => 'nullable|string|max:255',
            'ports' => 'nullable|integer|min:1|max:1000',
            'nodal_officer' => 'nullable|string|max:255',
            'zone' => 'nullable|string|max:255',
            'p2p_capacity' => 'nullable|string|max:255',
            'p2p_provider' => 'nullable|string|max:255',
            'connected_main_node' => 'nullable|string|max:255',
            'p2p_arc' => 'nullable|numeric|min:0',
            'colocation_provider' => 'nullable|string|max:255',
            'colocation_arc' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        try {
            $oldData = $ixLocation->toArray();
            $ixLocation->update($validated);
            $newData = $ixLocation->fresh()->toArray();

            // Log to history
            $this->logHistory($ixLocation, $oldData, $newData, 'updated');

            return redirect()->route('superadmin.ix-locations.index')
                ->with('success', 'IX location updated successfully.');
        } catch (Exception $e) {
            Log::error('Failed to update IX location: '.$e->getMessage());

            return back()->with('error', 'Unable to update location. Please try again.')
                ->withInput();
        }
    }

    /**
     * Toggle active flag.
     */
    public function toggleStatus(IxLocation $ixLocation)
    {
        try {
            $oldData = $ixLocation->toArray();
            $ixLocation->update(['is_active' => ! $ixLocation->is_active]);
            $newData = $ixLocation->fresh()->toArray();

            // Log to history
            $changeType = $ixLocation->is_active ? 'activated' : 'deactivated';
            $this->logHistory($ixLocation, $oldData, $newData, $changeType);

            return redirect()->route('superadmin.ix-locations.index')
                ->with('success', 'Location status updated.');
        } catch (Exception $e) {
            Log::error('Failed to toggle IX location status: '.$e->getMessage());

            return back()->with('error', 'Unable to update status. Please try again.');
        }
    }

    /**
     * Delete a location.
     */
    public function destroy(IxLocation $ixLocation)
    {
        try {
            $oldData = $ixLocation->toArray();
            
            // Log to history before deleting
            $this->logHistory($ixLocation, $oldData, null, 'deleted');
            
            $ixLocation->delete();

            return redirect()->route('superadmin.ix-locations.index')
                ->with('success', 'Location removed successfully.');
        } catch (Exception $e) {
            Log::error('Failed to delete IX location: '.$e->getMessage());

            return back()->with('error', 'Unable to delete location. Please try again.');
        }
    }

    /**
     * Get location history.
     */
    public function history(IxLocation $ixLocation)
    {
        try {
            $history = IxLocationHistory::where('ix_location_id', $ixLocation->id)
                ->orderBy('created_at', 'desc')
                ->get();

            return view('superadmin.ix.location-history', compact('ixLocation', 'history'));
        } catch (Exception $e) {
            Log::error('Error loading IX location history: '.$e->getMessage());

            return back()->with('error', 'Failed to load location history.');
        }
    }

    /**
     * Log history for IX location changes.
     */
    private function logHistory(IxLocation $location, ?array $oldData, ?array $newData, string $changeType): void
    {
        try {
            $updatedBy = $this->getUpdatedBy();

            IxLocationHistory::create([
                'ix_location_id' => $location->id,
                'old_data' => $oldData,
                'new_data' => $newData,
                'updated_by' => $updatedBy,
                'change_type' => $changeType,
                'notes' => $this->getChangeNotes($oldData, $newData, $changeType),
            ]);
        } catch (Exception $e) {
            Log::error('Failed to log IX location history: '.$e->getMessage());
        }
    }

    /**
     * Get who made the change.
     */
    private function getUpdatedBy(): string
    {
        if (session('superadmin_id')) {
            $superAdmin = SuperAdmin::find(session('superadmin_id'));
            return $superAdmin ? "SuperAdmin: {$superAdmin->name}" : 'SuperAdmin';
        }

        if (session('admin_id')) {
            $admin = Admin::find(session('admin_id'));
            return $admin ? "Admin: {$admin->name}" : 'Admin';
        }

        return 'System';
    }

    /**
     * Generate change notes based on what changed.
     */
    private function getChangeNotes(?array $oldData, ?array $newData, string $changeType): ?string
    {
        if ($changeType === 'created') {
            return 'IX location created.';
        }

        if ($changeType === 'deleted') {
            return 'IX location deleted.';
        }

        if ($changeType === 'activated' || $changeType === 'deactivated') {
            return "Location {$changeType}.";
        }

        if ($oldData && $newData) {
            $changes = [];
            foreach ($newData as $key => $value) {
                if (isset($oldData[$key]) && $oldData[$key] != $value && !in_array($key, ['updated_at', 'created_at'])) {
                    $changes[] = ucfirst(str_replace('_', ' ', $key)).": {$oldData[$key]} → {$value}";
                }
            }

            return !empty($changes) ? implode(', ', $changes) : 'No significant changes detected.';
        }

        return null;
    }
}
