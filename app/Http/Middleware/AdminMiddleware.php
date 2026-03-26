<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            // Check if Admin is logged in
            if (! session('admin_id')) {
                return redirect()->route('admin.login')
                    ->with('error', 'Please login to access Admin panel.');
            }

            // Verify Admin is still active
            $admin = \App\Models\Admin::with('roles')->find(session('admin_id'));

            if (! $admin) {
                session()->forget(['admin_id', 'admin_name', 'admin_email', 'admin_userid']);

                return redirect()->route('admin.login')
                    ->with('error', 'Admin not found. Please login again.');
            }

            if (! $admin->is_active) {
                session()->forget(['admin_id', 'admin_name', 'admin_email', 'admin_userid']);

                return redirect()->route('admin.login')
                    ->with('error', 'Your account is inactive. Please contact administrator.');
            }

            // Get active roles
            $activeRoles = $admin->roles->where('is_active', true);
            if ($activeRoles->count() === 0) {
                $activeRoles = $admin->roles; // Fallback to all roles if none are active
            }

            // Handle role selection from query parameter
            $selectedRole = $request->get('role');
            if ($selectedRole) {
                // Validate that the role belongs to the admin and is active
                $roleExists = $activeRoles->contains('slug', $selectedRole);
                if ($roleExists) {
                    session(['admin_selected_role' => $selectedRole]);
                } else {
                    // If invalid role, clear it and auto-select
                    session()->forget('admin_selected_role');
                    $selectedRole = null;
                }
            } else {
                // If no role in query, check session or auto-select
                $selectedRole = session('admin_selected_role', null);

                // Validate session role is still valid
                if ($selectedRole && ! $activeRoles->contains('slug', $selectedRole)) {
                    session()->forget('admin_selected_role');
                    $selectedRole = null;
                }
            }

            // Auto-select role if not set and admin has roles
            if (! $selectedRole && $activeRoles->count() > 0) {
                if ($activeRoles->count() === 1) {
                    // Single role - auto-select it
                    $selectedRole = $activeRoles->first()->slug;
                } else {
                    // Multiple roles - select based on priority
                    $priorityOrder = ['processor', 'finance', 'technical'];
                    foreach ($priorityOrder as $priorityRole) {
                        if ($activeRoles->contains('slug', $priorityRole)) {
                            $selectedRole = $priorityRole;
                            break;
                        }
                    }
                    // If no priority role found, select first one
                    if (! $selectedRole) {
                        $selectedRole = $activeRoles->first()->slug;
                    }
                }
                session(['admin_selected_role' => $selectedRole]);
            }

            // Share admin with views
            view()->share('currentAdmin', $admin);

            return $next($request);
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Database error in Admin middleware: '.$e->getMessage());
            abort(503, 'Database connection error. Please try again later.');
        } catch (\PDOException $e) {
            Log::error('PDO error in Admin middleware: '.$e->getMessage());
            abort(503, 'Database connection error. Please try again later.');
        } catch (\Exception $e) {
            Log::error('Error in Admin middleware: '.$e->getMessage());
            abort(500, 'An error occurred. Please try again later.');
        }
    }
}
