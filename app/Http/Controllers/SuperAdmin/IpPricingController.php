<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\IpPricing;
use App\Models\PaymentType;
use App\Models\PricingHistory;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class IpPricingController extends Controller
{
    /**
     * Display IP pricing management page.
     */
    public function index()
    {
        try {
            $paymentType = PaymentType::where('slug', 'ip-pricing')->first();

            $ipv4Pricings = IpPricing::where('ip_type', 'ipv4')
                ->with('paymentType')
                ->orderBy('effective_from', 'desc')
                ->orderBy('addresses', 'asc')
                ->get();

            $ipv6Pricings = IpPricing::where('ip_type', 'ipv6')
                ->with('paymentType')
                ->orderBy('effective_from', 'desc')
                ->orderBy('addresses', 'asc')
                ->get();

            return view('superadmin.ip-pricing.index', compact('ipv4Pricings', 'ipv6Pricings', 'paymentType'));
        } catch (Exception $e) {
            Log::error('Error loading IP pricing page: '.$e->getMessage());

            return redirect()->route('superadmin.dashboard')
                ->with('error', 'Failed to load IP pricing page.');
        }
    }

    /**
     * Store or update IP pricing.
     */
    public function store(Request $request)
    {
        try {
            // Transform checkbox value before validation
            // HTML checkboxes send "on" when checked, nothing when unchecked
            $isActive = $request->has('is_active') && ($request->is_active === 'on' || $request->is_active === '1' || $request->is_active === true || $request->is_active === 1);
            $request->merge(['is_active' => $isActive]);

            $request->validate([
                'ip_type' => 'required|in:ipv4,ipv6',
                'size' => 'required|string',
                'addresses' => 'required|integer|min:1',
                'amount' => 'required|numeric|min:0',
                'gst_percentage' => 'nullable|numeric|min:0|max:100',
                'igst' => 'nullable|numeric|min:0',
                'cgst' => 'nullable|numeric|min:0',
                'sgst' => 'nullable|numeric|min:0',
                'price' => 'required|numeric|min:0',
                'effective_from' => 'nullable|date',
                'effective_until' => 'nullable|date|after_or_equal:effective_from',
                'is_active' => 'boolean',
            ]);

            $paymentType = PaymentType::where('slug', 'ip-pricing')->first();

            // Normalize effective_from date
            $effectiveFrom = $request->effective_from ?: now()->toDateString();
            
            // Ensure date is in correct format (Y-m-d)
            if ($effectiveFrom instanceof \Carbon\Carbon) {
                $effectiveFrom = $effectiveFrom->format('Y-m-d');
            } elseif (is_string($effectiveFrom)) {
                try {
                    $effectiveFrom = \Carbon\Carbon::parse($effectiveFrom)->format('Y-m-d');
                } catch (\Exception $e) {
                    $effectiveFrom = now()->toDateString();
                }
            }

            // Store old data for history if updating
            $existingPricing = IpPricing::where('ip_type', $request->ip_type)
                ->where('size', $request->size)
                ->where('effective_from', $effectiveFrom)
                ->first();

            $oldData = $existingPricing ? $existingPricing->toArray() : null;

            $pricing = IpPricing::updateOrCreate(
                [
                    'ip_type' => $request->ip_type,
                    'size' => $request->size,
                    'effective_from' => $effectiveFrom,
                ],
                [
                    'addresses' => (int) $request->addresses,
                    'amount' => (float) $request->amount,
                    'gst_percentage' => $request->gst_percentage ? (float) $request->gst_percentage : null,
                    'igst' => $request->igst ? (float) $request->igst : null,
                    'cgst' => $request->cgst ? (float) $request->cgst : null,
                    'sgst' => $request->sgst ? (float) $request->sgst : null,
                    'price' => (float) $request->price,
                    'effective_from' => $effectiveFrom,
                    'effective_until' => $request->effective_until ?: null,
                    'payment_type_id' => $paymentType->id ?? null,
                    'is_active' => $isActive,
                ]
            );

            // Log to history
            PricingHistory::create([
                'pricing_id' => $pricing->id,
                'payment_type_id' => $paymentType->id ?? null,
                'old_data' => $oldData,
                'new_data' => $pricing->toArray(),
                'effective_from' => $pricing->effective_from,
                'effective_until' => $pricing->effective_until,
                'updated_by' => session('superadmin_id') ? 'SuperAdmin' : (session('admin_id') ? 'Admin' : 'System'),
                'change_type' => $existingPricing ? 'updated' : 'created',
            ]);

            Log::info("IP pricing saved: {$request->ip_type} {$request->size}");

            return redirect()->route('superadmin.ip-pricing.index')
                ->with('success', 'IP pricing saved successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error storing IP pricing: '.json_encode($e->errors()));
            return back()->withErrors($e->errors())->withInput();
        } catch (Exception $e) {
            Log::error('Error storing IP pricing: '.$e->getMessage());
            Log::error('Stack trace: '.$e->getTraceAsString());
            Log::error('Request data: '.json_encode($request->all()));

            return back()->with('error', 'Failed to save IP pricing: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Update IP pricing.
     */
    public function update(Request $request, $id)
    {
        try {
            $pricing = IpPricing::findOrFail($id);
            $oldData = $pricing->toArray();

            // Transform checkbox value before validation
            // HTML checkboxes send "on" when checked, nothing when unchecked
            $isActive = $request->has('is_active') && ($request->is_active === 'on' || $request->is_active === '1' || $request->is_active === true || $request->is_active === 1);
            $request->merge(['is_active' => $isActive]);

            // Log incoming request data
            Log::info("Update request received for pricing ID {$id}", [
                'request_data' => $request->all(),
                'is_active_transformed' => $isActive,
            ]);

            $request->validate([
                'addresses' => 'required|integer|min:1',
                'amount' => 'required|numeric|min:0',
                'gst_percentage' => 'nullable|numeric|min:0|max:100',
                'igst' => 'nullable|numeric|min:0',
                'cgst' => 'nullable|numeric|min:0',
                'sgst' => 'nullable|numeric|min:0',
                'price' => 'required|numeric|min:0',
                'effective_from' => 'nullable|date',
                'effective_until' => 'nullable|date|after_or_equal:effective_from',
                'is_active' => 'boolean',
            ]);

            $updateData = [
                'addresses' => (int) $request->addresses,
                'amount' => (float) $request->amount,
                'gst_percentage' => $request->gst_percentage ? (float) $request->gst_percentage : null,
                'igst' => $request->igst ? (float) $request->igst : null,
                'cgst' => $request->cgst ? (float) $request->cgst : null,
                'sgst' => $request->sgst ? (float) $request->sgst : null,
                'price' => (float) $request->price,
                'effective_until' => $request->effective_until ?: null,
            ];

            // Handle effective_from - always update if provided in request
            if ($request->has('effective_from')) {
                if (! empty($request->effective_from)) {
                    $updateData['effective_from'] = $request->effective_from;
                } else {
                    // If explicitly cleared (empty string), set to today
                    $updateData['effective_from'] = now()->toDateString();
                }
            }
            // If not provided in request, keep existing value (don't add to updateData)

            // Handle is_active checkbox - already transformed above
            $updateData['is_active'] = $isActive;

            // Log what we're about to update
            Log::info('Updating pricing with data', [
                'pricing_id' => $id,
                'update_data' => $updateData,
                'old_data' => $oldData,
            ]);

            // Update the pricing - use update() method
            $updated = $pricing->update($updateData);

            if (! $updated) {
                Log::error("Update returned false for pricing ID {$id}");
                throw new Exception('Update operation returned false');
            }

            // Refresh the model to get updated values
            $pricing->refresh();

            // Log to history
            PricingHistory::create([
                'pricing_id' => $pricing->id,
                'payment_type_id' => $pricing->payment_type_id,
                'old_data' => $oldData,
                'new_data' => $pricing->toArray(),
                'effective_from' => $pricing->effective_from,
                'effective_until' => $pricing->effective_until,
                'updated_by' => session('superadmin_id') ? 'SuperAdmin' : (session('admin_id') ? 'Admin' : 'System'),
                'change_type' => 'updated',
            ]);

            Log::info("IP pricing updated: ID {$id}", [
                'old_data' => $oldData,
                'new_data' => $pricing->toArray(),
                'update_data' => $updateData,
            ]);

            return redirect()->route('superadmin.ip-pricing.index')
                ->with('success', 'IP pricing updated successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error updating IP pricing: '.json_encode($e->errors()));

            return back()->withErrors($e->errors())->withInput();
        } catch (Exception $e) {
            Log::error('Error updating IP pricing: '.$e->getMessage().' | Trace: '.$e->getTraceAsString());

            return back()->with('error', 'Failed to update IP pricing: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Get pricing for API (used by frontend).
     */
    public function getPricing()
    {
        try {
            $pricings = IpPricing::getAllActive();

            return response()->json([
                'success' => true,
                'data' => $pricings,
            ]);
        } catch (Exception $e) {
            Log::error('Error fetching IP pricing: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch pricing.',
            ], 500);
        }
    }

    /**
     * Delete IP pricing.
     */
    public function destroy($id)
    {
        try {
            $pricing = IpPricing::findOrFail($id);
            $oldData = $pricing->toArray();

            // Log to history before deleting
            PricingHistory::create([
                'pricing_id' => $pricing->id,
                'payment_type_id' => $pricing->payment_type_id,
                'old_data' => $oldData,
                'new_data' => null,
                'updated_by' => session('superadmin_id') ? 'SuperAdmin' : (session('admin_id') ? 'Admin' : 'System'),
                'change_type' => 'deleted',
            ]);

            $pricing->delete();

            Log::info("IP pricing deleted: ID {$id}");

            return redirect()->route('superadmin.ip-pricing.index')
                ->with('success', 'IP pricing deleted successfully.');
        } catch (Exception $e) {
            Log::error('Error deleting IP pricing: '.$e->getMessage());

            return back()->with('error', 'Failed to delete IP pricing. Please try again.');
        }
    }

    /**
     * Toggle pricing status (active/inactive).
     */
    public function toggleStatus($id)
    {
        try {
            $pricing = IpPricing::findOrFail($id);
            $oldData = $pricing->toArray();

            $pricing->update([
                'is_active' => ! $pricing->is_active,
            ]);

            // Log to history
            PricingHistory::create([
                'pricing_id' => $pricing->id,
                'payment_type_id' => $pricing->payment_type_id,
                'old_data' => $oldData,
                'new_data' => $pricing->fresh()->toArray(),
                'effective_from' => $pricing->effective_from,
                'effective_until' => $pricing->effective_until,
                'updated_by' => session('superadmin_id') ? 'SuperAdmin' : (session('admin_id') ? 'Admin' : 'System'),
                'change_type' => $pricing->is_active ? 'activated' : 'deactivated',
            ]);

            Log::info("IP pricing status toggled: ID {$id}, Status: ".($pricing->is_active ? 'Active' : 'Inactive'));

            return redirect()->route('superadmin.ip-pricing.index')
                ->with('success', 'Pricing status updated successfully.');
        } catch (Exception $e) {
            Log::error('Error toggling pricing status: '.$e->getMessage());

            return back()->with('error', 'Failed to update pricing status. Please try again.');
        }
    }

    /**
     * Get pricing history.
     */
    public function history($id)
    {
        try {
            $pricing = IpPricing::with('paymentType')->findOrFail($id);
            $history = PricingHistory::where('pricing_id', $id)
                ->orderBy('created_at', 'desc')
                ->get();

            return view('superadmin.ip-pricing.history', compact('pricing', 'history'));
        } catch (Exception $e) {
            Log::error('Error loading pricing history: '.$e->getMessage());

            return back()->with('error', 'Failed to load pricing history.');
        }
    }
}
