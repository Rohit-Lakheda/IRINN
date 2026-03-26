<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\IxPortPricing;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class IxPortPricingController extends Controller
{
    /**
     * Display IX port pricing management screen.
     */
    public function index()
    {
        try {
            $portPricings = IxPortPricing::orderBy('node_type')
                ->orderBy('display_order')
                ->orderBy('port_capacity')
                ->get()
                ->groupBy('node_type');

            return view('superadmin.ix.port-pricing', compact('portPricings'));
        } catch (Exception $e) {
            Log::error('Failed to load IX port pricing: '.$e->getMessage());

            return redirect()->route('superadmin.dashboard')
                ->with('error', 'Unable to load IX port pricing right now.');
        }
    }

    /**
     * Store a new pricing row.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'node_type' => ['required', Rule::in(['metro', 'edge'])],
            'port_capacity' => 'required|string|max:50',
            'price_arc' => 'required|numeric|min:0',
            'price_mrc' => 'required|numeric|min:0',
            'price_quarterly' => 'required|numeric|min:0',
            'display_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        try {
            IxPortPricing::updateOrCreate(
                [
                    'node_type' => $validated['node_type'],
                    'port_capacity' => $validated['port_capacity'],
                ],
                [
                    'price_arc' => $validated['price_arc'],
                    'price_mrc' => $validated['price_mrc'],
                    'price_quarterly' => $validated['price_quarterly'],
                    'display_order' => $validated['display_order'] ?? 0,
                    'is_active' => $validated['is_active'] ?? true,
                ]
            );

            return redirect()->route('superadmin.ix-port-pricing.index')
                ->with('success', 'Pricing saved successfully.');
        } catch (Exception $e) {
            Log::error('Failed to save IX port pricing: '.$e->getMessage());

            return back()->with('error', 'Unable to save pricing. Please try again.')
                ->withInput();
        }
    }

    /**
     * Update existing pricing.
     */
    public function update(Request $request, IxPortPricing $ixPortPricing)
    {
        $validated = $request->validate([
            'price_arc' => 'required|numeric|min:0',
            'price_mrc' => 'required|numeric|min:0',
            'price_quarterly' => 'required|numeric|min:0',
            'display_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        try {
            $ixPortPricing->update([
                'price_arc' => $validated['price_arc'],
                'price_mrc' => $validated['price_mrc'],
                'price_quarterly' => $validated['price_quarterly'],
                'display_order' => $validated['display_order'] ?? $ixPortPricing->display_order,
                'is_active' => array_key_exists('is_active', $validated)
                    ? (bool) $validated['is_active']
                    : $ixPortPricing->is_active,
            ]);

            return redirect()->route('superadmin.ix-port-pricing.index')
                ->with('success', 'Pricing updated successfully.');
        } catch (Exception $e) {
            Log::error('Failed to update IX port pricing: '.$e->getMessage());

            return back()->with('error', 'Unable to update pricing. Please try again.')
                ->withInput();
        }
    }

    /**
     * Toggle active status.
     */
    public function toggleStatus(IxPortPricing $ixPortPricing)
    {
        try {
            $ixPortPricing->update(['is_active' => ! $ixPortPricing->is_active]);

            return redirect()->route('superadmin.ix-port-pricing.index')
                ->with('success', 'Pricing status updated.');
        } catch (Exception $e) {
            Log::error('Failed to toggle IX port pricing status: '.$e->getMessage());

            return back()->with('error', 'Unable to update status. Please try again.');
        }
    }

    /**
     * Delete pricing row.
     */
    public function destroy(IxPortPricing $ixPortPricing)
    {
        try {
            $ixPortPricing->delete();

            return redirect()->route('superadmin.ix-port-pricing.index')
                ->with('success', 'Pricing removed successfully.');
        } catch (Exception $e) {
            Log::error('Failed to delete IX port pricing: '.$e->getMessage());

            return back()->with('error', 'Unable to delete pricing. Please try again.');
        }
    }
}
