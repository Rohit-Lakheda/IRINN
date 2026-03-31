<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\GrievanceAssignment;
use App\Models\GrievanceCategory;
use App\Models\GrievanceSubcategory;
use App\Models\GrievanceTransferRule;
use App\Models\Role;
use App\Models\TicketEscalationSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class GrievanceManagementController extends Controller
{
    /**
     * Display the main grievance management dashboard.
     */
    public function index(): View
    {
        $categories = GrievanceCategory::with(['subcategories', 'assignments'])->orderBy('order')->get();
        $roles = Role::query()
            ->where('is_active', true)
            ->whereIn('slug', ['helpdesk', 'hostmaster', 'billing'])
            ->orderBy('name')
            ->get();
        $transferRules = GrievanceTransferRule::with(['category', 'subcategory', 'fromRole', 'toRole'])
            ->orderBy('from_role')
            ->orderBy('to_role')
            ->get();
        $escalationSetting = TicketEscalationSetting::current();

        return view('superadmin.grievance-management.index', compact('categories', 'roles', 'transferRules', 'escalationSetting'));
    }

    // ==================== CATEGORIES ====================

    /**
     * Store a new category.
     */
    public function storeCategory(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:grievance_categories,slug',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'order' => 'nullable|integer|min:0',
        ]);

        GrievanceCategory::create($validated);

        return redirect()->route('superadmin.grievance-management.index')
            ->with('success', 'Category created successfully.');
    }

    /**
     * Update a category.
     */
    public function updateCategory(Request $request, GrievanceCategory $category): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:grievance_categories,slug,'.$category->id,
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'order' => 'nullable|integer|min:0',
        ]);

        $category->update($validated);

        return redirect()->route('superadmin.grievance-management.index')
            ->with('success', 'Category updated successfully.');
    }

    /**
     * Delete a category.
     */
    public function deleteCategory(GrievanceCategory $category): RedirectResponse
    {
        $category->delete();

        return redirect()->route('superadmin.grievance-management.index')
            ->with('success', 'Category deleted successfully.');
    }

    // ==================== SUBCATEGORIES ====================

    /**
     * Store a new subcategory.
     */
    public function storeSubcategory(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:grievance_categories,id',
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'order' => 'nullable|integer|min:0',
        ]);

        // Ensure unique slug within category
        $category = GrievanceCategory::findOrFail($validated['category_id']);
        $existing = GrievanceSubcategory::where('category_id', $category->id)
            ->where('slug', $validated['slug'] ?? \Illuminate\Support\Str::slug($validated['name']))
            ->first();

        if ($existing) {
            return back()->withInput()->withErrors(['slug' => 'This slug already exists for this category.']);
        }

        GrievanceSubcategory::create($validated);

        return redirect()->route('superadmin.grievance-management.index')
            ->with('success', 'Subcategory created successfully.');
    }

    /**
     * Update a subcategory.
     */
    public function updateSubcategory(Request $request, GrievanceSubcategory $subcategory): RedirectResponse
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:grievance_categories,id',
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'order' => 'nullable|integer|min:0',
        ]);

        // Ensure unique slug within category
        $existing = GrievanceSubcategory::where('category_id', $validated['category_id'])
            ->where('slug', $validated['slug'] ?? \Illuminate\Support\Str::slug($validated['name']))
            ->where('id', '!=', $subcategory->id)
            ->first();

        if ($existing) {
            return back()->withInput()->withErrors(['slug' => 'This slug already exists for this category.']);
        }

        $subcategory->update($validated);

        return redirect()->route('superadmin.grievance-management.index')
            ->with('success', 'Subcategory updated successfully.');
    }

    /**
     * Delete a subcategory.
     */
    public function deleteSubcategory(GrievanceSubcategory $subcategory): RedirectResponse
    {
        $subcategory->delete();

        return redirect()->route('superadmin.grievance-management.index')
            ->with('success', 'Subcategory deleted successfully.');
    }

    // ==================== ASSIGNMENTS ====================

    /**
     * Store a new assignment.
     */
    public function storeAssignment(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:grievance_categories,id',
            'subcategory_id' => 'nullable|exists:grievance_subcategories,id',
            'assigned_role' => 'required|string|exists:roles,slug',
            'is_active' => 'boolean',
            'priority' => 'nullable|integer|min:0',
        ]);

        // Ensure unique assignment
        $existing = GrievanceAssignment::where('category_id', $validated['category_id'])
            ->where('subcategory_id', $validated['subcategory_id'])
            ->where('assigned_role', $validated['assigned_role'])
            ->first();

        if ($existing) {
            return back()->withInput()->withErrors(['assigned_role' => 'This assignment already exists.']);
        }

        GrievanceAssignment::create($validated);

        return redirect()->route('superadmin.grievance-management.index')
            ->with('success', 'Assignment created successfully.');
    }

    /**
     * Update an assignment.
     */
    public function updateAssignment(Request $request, GrievanceAssignment $assignment): RedirectResponse
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:grievance_categories,id',
            'subcategory_id' => 'nullable|exists:grievance_subcategories,id',
            'assigned_role' => 'required|string|exists:roles,slug',
            'is_active' => 'boolean',
            'priority' => 'nullable|integer|min:0',
        ]);

        // Ensure unique assignment (excluding current)
        $existing = GrievanceAssignment::where('category_id', $validated['category_id'])
            ->where('subcategory_id', $validated['subcategory_id'])
            ->where('assigned_role', $validated['assigned_role'])
            ->where('id', '!=', $assignment->id)
            ->first();

        if ($existing) {
            return back()->withInput()->withErrors(['assigned_role' => 'This assignment already exists.']);
        }

        $assignment->update($validated);

        return redirect()->route('superadmin.grievance-management.index')
            ->with('success', 'Assignment updated successfully.');
    }

    /**
     * Delete an assignment.
     */
    public function deleteAssignment(GrievanceAssignment $assignment): RedirectResponse
    {
        $assignment->delete();

        return redirect()->route('superadmin.grievance-management.index')
            ->with('success', 'Assignment deleted successfully.');
    }

    // ==================== TRANSFER RULES ====================

    /**
     * Store a new transfer rule.
     */
    public function storeTransferRule(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'from_role' => 'required|string|exists:roles,slug',
            'to_role' => 'required|string|exists:roles,slug',
            'category_id' => 'nullable|exists:grievance_categories,id',
            'subcategory_id' => 'nullable|exists:grievance_subcategories,id',
            'is_active' => 'boolean',
        ]);

        // Ensure unique transfer rule
        $existing = GrievanceTransferRule::where('from_role', $validated['from_role'])
            ->where('to_role', $validated['to_role'])
            ->where('category_id', $validated['category_id'])
            ->where('subcategory_id', $validated['subcategory_id'])
            ->first();

        if ($existing) {
            return back()->withInput()->withErrors(['to_role' => 'This transfer rule already exists.']);
        }

        GrievanceTransferRule::create($validated);

        return redirect()->route('superadmin.grievance-management.index')
            ->with('success', 'Transfer rule created successfully.');
    }

    /**
     * Update a transfer rule.
     */
    public function updateTransferRule(Request $request, GrievanceTransferRule $transferRule): RedirectResponse
    {
        $validated = $request->validate([
            'from_role' => 'required|string|exists:roles,slug',
            'to_role' => 'required|string|exists:roles,slug',
            'category_id' => 'nullable|exists:grievance_categories,id',
            'subcategory_id' => 'nullable|exists:grievance_subcategories,id',
            'is_active' => 'boolean',
        ]);

        // Ensure unique transfer rule (excluding current)
        $existing = GrievanceTransferRule::where('from_role', $validated['from_role'])
            ->where('to_role', $validated['to_role'])
            ->where('category_id', $validated['category_id'])
            ->where('subcategory_id', $validated['subcategory_id'])
            ->where('id', '!=', $transferRule->id)
            ->first();

        if ($existing) {
            return back()->withInput()->withErrors(['to_role' => 'This transfer rule already exists.']);
        }

        $transferRule->update($validated);

        return redirect()->route('superadmin.grievance-management.index')
            ->with('success', 'Transfer rule updated successfully.');
    }

    /**
     * Delete a transfer rule.
     */
    public function deleteTransferRule(GrievanceTransferRule $transferRule): RedirectResponse
    {
        $transferRule->delete();

        return redirect()->route('superadmin.grievance-management.index')
            ->with('success', 'Transfer rule deleted successfully.');
    }

    // ==================== ESCALATION SETTINGS ====================

    public function updateEscalationSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'is_enabled' => 'nullable|boolean',
            'ix_head_after_hours' => 'required|integer|min:0|max:720',
            'ceo_after_hours' => 'required|integer|min:0|max:720',
            'level_1_role_slug' => 'required|string|exists:roles,slug',
            'level_2_role_slug' => 'required|string|exists:roles,slug',
        ]);

        $setting = TicketEscalationSetting::current();

        $ixHeadHours = (int) $validated['ix_head_after_hours'];
        $ceoHours = (int) $validated['ceo_after_hours'];

        if ($ceoHours < $ixHeadHours) {
            return back()->withInput()->withErrors([
                'ceo_after_hours' => 'CEO escalation hours must be greater than or equal to IX Head escalation hours.',
            ]);
        }

        $superAdminId = session('superadmin_id');

        $setting->update([
            'is_enabled' => (bool) ($validated['is_enabled'] ?? false),
            'ix_head_after_hours' => $ixHeadHours,
            'ceo_after_hours' => $ceoHours,
            'level_1_role_slug' => $validated['level_1_role_slug'],
            'level_2_role_slug' => $validated['level_2_role_slug'],
            'updated_by' => $superAdminId ?: null,
        ]);

        return redirect()->route('superadmin.grievance-management.index')
            ->with('success', 'Escalation settings updated successfully.');
    }

    public function quickSetupWorkflow(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:grievance_categories,id',
            'subcategory_id' => 'nullable|exists:grievance_subcategories,id',
            'initial_role' => 'required|string|exists:roles,slug',
            'forward_role_1' => 'nullable|string|exists:roles,slug',
            'forward_role_2' => 'nullable|string|exists:roles,slug',
            'forward_role_3' => 'nullable|string|exists:roles,slug',
            'forward_role_4' => 'nullable|string|exists:roles,slug',
            'forward_role_5' => 'nullable|string|exists:roles,slug',
            'replace_existing' => 'nullable|boolean',
        ]);

        $subcategoryId = $validated['subcategory_id'] ?? null;
        $replace = (bool) ($validated['replace_existing'] ?? true);

        $roles = array_values(array_filter([
            $validated['initial_role'],
            $validated['forward_role_1'] ?? null,
            $validated['forward_role_2'] ?? null,
            $validated['forward_role_3'] ?? null,
            $validated['forward_role_4'] ?? null,
            $validated['forward_role_5'] ?? null,
        ]));

        // de-duplicate while preserving order
        $chain = [];
        foreach ($roles as $r) {
            if (! in_array($r, $chain, true)) {
                $chain[] = $r;
            }
        }

        if (count($chain) < 1) {
            return back()->withInput()->withErrors(['initial_role' => 'Please select an initial role.']);
        }

        DB::transaction(function () use ($validated, $subcategoryId, $replace, $chain) {
            if ($replace) {
                GrievanceAssignment::query()
                    ->where('category_id', $validated['category_id'])
                    ->where('subcategory_id', $subcategoryId)
                    ->delete();

                GrievanceTransferRule::query()
                    ->where('category_id', $validated['category_id'])
                    ->where('subcategory_id', $subcategoryId)
                    ->delete();
            }

            GrievanceAssignment::query()->create([
                'category_id' => $validated['category_id'],
                'subcategory_id' => $subcategoryId,
                'assigned_role' => $validated['initial_role'],
                'is_active' => true,
                'priority' => 0,
            ]);

            for ($i = 0; $i < count($chain) - 1; $i++) {
                GrievanceTransferRule::query()->create([
                    'from_role' => $chain[$i],
                    'to_role' => $chain[$i + 1],
                    'category_id' => $validated['category_id'],
                    'subcategory_id' => $subcategoryId,
                    'is_active' => true,
                ]);
            }
        });

        return redirect()->route('superadmin.grievance-management.index')
            ->with('success', 'Workflow saved successfully.');
    }
}
