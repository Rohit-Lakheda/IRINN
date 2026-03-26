<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\GrievanceAssignment;
use App\Models\GrievanceCategory;
use App\Models\GrievanceSubcategory;
use App\Models\GrievanceTransferRule;
use App\Models\Ticket;
use Illuminate\Support\Facades\Log;

class TicketAssignmentService
{
    /**
     * Get the role that should handle a ticket based on category and sub_category.
     */
    public static function getAssignedRole(string $category, ?string $subCategory = null): ?string
    {
        // Find category by slug
        $grievanceCategory = GrievanceCategory::where('slug', $category)
            ->where('is_active', true)
            ->first();

        if (!$grievanceCategory) {
            Log::warning('Grievance category not found', ['category' => $category]);
            return null;
        }

        // Find subcategory if provided
        $grievanceSubcategory = null;
        if ($subCategory) {
            $grievanceSubcategory = GrievanceSubcategory::where('category_id', $grievanceCategory->id)
                ->where('slug', $subCategory)
                ->where('is_active', true)
                ->first();
        }

        // Find assignment - prioritize subcategory-specific assignments
        $assignment = null;
        if ($grievanceSubcategory) {
            $assignment = GrievanceAssignment::where('category_id', $grievanceCategory->id)
                ->where('subcategory_id', $grievanceSubcategory->id)
                ->where('is_active', true)
                ->orderBy('priority')
                ->first();
        }

        // Fallback to category-level assignment
        if (!$assignment) {
            $assignment = GrievanceAssignment::where('category_id', $grievanceCategory->id)
                ->whereNull('subcategory_id')
                ->where('is_active', true)
                ->orderBy('priority')
                ->first();
        }

        return $assignment ? $assignment->assigned_role : null;
    }

    /**
     * Get sub-categories for a given category.
     */
    public static function getSubCategories(string $category): array
    {
        $grievanceCategory = GrievanceCategory::where('slug', $category)
            ->where('is_active', true)
            ->first();

        if (!$grievanceCategory) {
            return [];
        }

        $subcategories = $grievanceCategory->activeSubcategories()
            ->orderBy('order')
            ->get();

        $result = [];
        foreach ($subcategories as $subcategory) {
            $result[$subcategory->slug] = $subcategory->name;
        }

        return $result;
    }

    /**
     * Get all categories.
     */
    public static function getCategories(): array
    {
        $categories = GrievanceCategory::active()
            ->orderBy('order')
            ->get();

        $result = [];
        foreach ($categories as $category) {
            $result[$category->slug] = $category->name;
        }

        return $result;
    }

    /**
     * Assign a ticket to an admin based on category and sub_category.
     */
    public static function assignTicket(Ticket $ticket): bool
    {
        if (!$ticket->category) {
            Log::warning('Cannot assign ticket without category', [
                'ticket_id' => $ticket->ticket_id,
            ]);

            return false;
        }

        $assignedRole = self::getAssignedRole($ticket->category, $ticket->sub_category);

        if (!$assignedRole) {
            Log::warning('No role found for ticket category/sub_category', [
                'ticket_id' => $ticket->ticket_id,
                'category' => $ticket->category,
                'sub_category' => $ticket->sub_category,
            ]);

            return false;
        }

        // Find an admin with the required role
        $admin = Admin::whereHas('roles', function ($query) use ($assignedRole) {
            $query->where('slug', $assignedRole);
        })->first();

        if (!$admin) {
            Log::warning('No admin found with required role', [
                'ticket_id' => $ticket->ticket_id,
                'required_role' => $assignedRole,
            ]);

            return false;
        }

        $ticket->update([
            'assigned_to' => $admin->id,
            'assigned_role' => $assignedRole,
            'assigned_at' => now(),
            'status' => 'assigned',
        ]);

        Log::info('Ticket assigned to admin', [
            'ticket_id' => $ticket->ticket_id,
            'admin_id' => $admin->id,
            'role' => $assignedRole,
        ]);

        return true;
    }

    /**
     * Check if an admin can forward a ticket to a specific role.
     */
    public static function canForwardTo(Ticket $ticket, Admin $admin, string $targetRole): bool
    {
        $currentRole = $ticket->assigned_role;

        if (!$currentRole) {
            return false;
        }

        // Check if admin has the current role
        if (!$admin->hasRole($currentRole)) {
            return false;
        }

        // Find transfer rules from database
        $transferRules = GrievanceTransferRule::where('from_role', $currentRole)
            ->where('to_role', $targetRole)
            ->where('is_active', true)
            ->get();

        if ($transferRules->isEmpty()) {
            return false;
        }

        // Check if any rule matches the ticket's category/subcategory
        foreach ($transferRules as $rule) {
            // If rule has no category, it applies to all categories
            if (!$rule->category_id) {
                return true;
            }

            // Check if category matches
            if ($rule->category_id && $ticket->category) {
                $grievanceCategory = GrievanceCategory::where('slug', $ticket->category)->first();
                if ($grievanceCategory && $rule->category_id == $grievanceCategory->id) {
                    // If rule has no subcategory, it applies to all subcategories in this category
                    if (!$rule->subcategory_id) {
                        return true;
                    }

                    // Check if subcategory matches
                    if ($rule->subcategory_id && $ticket->sub_category) {
                        $grievanceSubcategory = GrievanceSubcategory::where('slug', $ticket->sub_category)
                            ->where('category_id', $grievanceCategory->id)
                            ->first();
                        if ($grievanceSubcategory && $rule->subcategory_id == $grievanceSubcategory->id) {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }

    /**
     * Forward a ticket to another admin with a specific role.
     */
    public static function forwardTicket(Ticket $ticket, Admin $fromAdmin, string $targetRole, ?string $notes = null): bool
    {
        if (!self::canForwardTo($ticket, $fromAdmin, $targetRole)) {
            return false;
        }

        // Find an admin with the target role
        $targetAdmin = Admin::whereHas('roles', function ($query) use ($targetRole) {
            $query->where('slug', $targetRole);
        })->first();

        if (!$targetAdmin) {
            Log::warning('No admin found with target role for forwarding', [
                'ticket_id' => $ticket->ticket_id,
                'target_role' => $targetRole,
            ]);

            return false;
        }

        $ticket->update([
            'assigned_to' => $targetAdmin->id,
            'assigned_role' => $targetRole,
            'forwarded_by' => $fromAdmin->id,
            'forwarded_at' => now(),
            'forwarding_notes' => $notes,
            'status' => 'assigned',
        ]);

        Log::info('Ticket forwarded', [
            'ticket_id' => $ticket->ticket_id,
            'from_admin_id' => $fromAdmin->id,
            'to_admin_id' => $targetAdmin->id,
            'from_role' => $ticket->assigned_role,
            'to_role' => $targetRole,
        ]);

        return true;
    }

    /**
     * Get all roles that can receive forwarded tickets from current admin.
     */
    public static function getForwardableRoles(Ticket $ticket, Admin $admin): array
    {
        $forwardableRoles = [];
        $currentRole = $ticket->assigned_role;

        if (!$currentRole || !$admin->hasRole($currentRole)) {
            return $forwardableRoles;
        }

        // Get all roles
        $allRoles = \App\Models\Role::where('is_active', true)
            ->where('slug', '!=', $currentRole)
            ->get();

        // Filter roles based on transfer rules from database
        foreach ($allRoles as $role) {
            if (self::canForwardTo($ticket, $admin, $role->slug)) {
                $forwardableRoles[$role->slug] = $role->name;
            }
        }

        return $forwardableRoles;
    }
}
