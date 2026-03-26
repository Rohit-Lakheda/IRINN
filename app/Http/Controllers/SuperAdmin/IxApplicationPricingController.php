<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\IxApplicationPricing;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class IxApplicationPricingController extends Controller
{
    /**
     * Display IX application pricing management page.
     */
    public function index()
    {
        try {
            $pricings = IxApplicationPricing::orderBy('effective_from', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();

            $activePricing = IxApplicationPricing::getActive();

            return view('superadmin.ix.application-pricing', compact('pricings', 'activePricing'));
        } catch (Exception $e) {
            Log::error('Error loading IX application pricing page: '.$e->getMessage());

            return redirect()->route('superadmin.dashboard')
                ->with('error', 'Failed to load IX application pricing page.');
        }
    }

    /**
     * Store new IX application pricing.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'application_fee' => 'required|numeric|min:0',
                'gst_percentage' => 'required|numeric|min:0|max:100',
                'effective_from' => 'nullable|date',
                'effective_until' => 'nullable|date|after_or_equal:effective_from',
                'is_active' => 'nullable|boolean',
            ]);

            $applicationFee = (float) $validated['application_fee'];
            $gstPercentage = (float) $validated['gst_percentage'];
            $gstAmount = ($applicationFee * $gstPercentage) / 100;
            $totalAmount = round($applicationFee + $gstAmount, 2);

            $pricing = IxApplicationPricing::create([
                'application_fee' => $applicationFee,
                'gst_percentage' => $gstPercentage,
                'total_amount' => $totalAmount,
                'effective_from' => $validated['effective_from'] ?? now()->toDateString(),
                'effective_until' => $validated['effective_until'] ?? null,
                'is_active' => $validated['is_active'] ?? true,
            ]);

            Log::info('IX application pricing created', ['pricing_id' => $pricing->id]);

            return redirect()->route('superadmin.ix-application-pricing.index')
                ->with('success', 'IX application pricing saved successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (Exception $e) {
            Log::error('Error storing IX application pricing: '.$e->getMessage());

            return back()->with('error', 'Failed to save IX application pricing. Please try again.')
                ->withInput();
        }
    }

    /**
     * Update existing IX application pricing.
     */
    public function update(Request $request, IxApplicationPricing $ixApplicationPricing)
    {
        try {
            $validated = $request->validate([
                'application_fee' => 'required|numeric|min:0',
                'gst_percentage' => 'required|numeric|min:0|max:100',
                'effective_from' => 'nullable|date',
                'effective_until' => 'nullable|date|after_or_equal:effective_from',
                'is_active' => 'nullable|boolean',
            ]);

            $applicationFee = (float) $validated['application_fee'];
            $gstPercentage = (float) $validated['gst_percentage'];
            $gstAmount = ($applicationFee * $gstPercentage) / 100;
            $totalAmount = round($applicationFee + $gstAmount, 2);

            $ixApplicationPricing->update([
                'application_fee' => $applicationFee,
                'gst_percentage' => $gstPercentage,
                'total_amount' => $totalAmount,
                'effective_from' => $validated['effective_from'] ?? $ixApplicationPricing->effective_from,
                'effective_until' => $validated['effective_until'] ?? null,
                'is_active' => array_key_exists('is_active', $validated)
                    ? (bool) $validated['is_active']
                    : $ixApplicationPricing->is_active,
            ]);

            Log::info('IX application pricing updated', ['pricing_id' => $ixApplicationPricing->id]);

            return redirect()->route('superadmin.ix-application-pricing.index')
                ->with('success', 'IX application pricing updated successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (Exception $e) {
            Log::error('Error updating IX application pricing: '.$e->getMessage());

            return back()->with('error', 'Failed to update IX application pricing. Please try again.')
                ->withInput();
        }
    }

    /**
     * Toggle active status.
     */
    public function toggleStatus(IxApplicationPricing $ixApplicationPricing)
    {
        try {
            $ixApplicationPricing->update(['is_active' => ! $ixApplicationPricing->is_active]);

            return redirect()->route('superadmin.ix-application-pricing.index')
                ->with('success', 'Pricing status updated.');
        } catch (Exception $e) {
            Log::error('Error toggling IX application pricing status: '.$e->getMessage());

            return back()->with('error', 'Unable to update status. Please try again.');
        }
    }

    /**
     * Delete pricing.
     */
    public function destroy(IxApplicationPricing $ixApplicationPricing)
    {
        try {
            $ixApplicationPricing->delete();

            return redirect()->route('superadmin.ix-application-pricing.index')
                ->with('success', 'Pricing removed successfully.');
        } catch (Exception $e) {
            Log::error('Error deleting IX application pricing: '.$e->getMessage());

            return back()->with('error', 'Unable to delete pricing. Please try again.');
        }
    }
}
