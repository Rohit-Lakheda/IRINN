<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class SuperAdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            // Check if SuperAdmin is logged in
            if (!session('superadmin_id')) {
                return redirect()->route('superadmin.login')
                    ->with('error', 'Please login to access Super Admin panel.');
            }

            // Verify SuperAdmin is still active
            $superAdmin = \App\Models\SuperAdmin::find(session('superadmin_id'));
            
            if (!$superAdmin) {
                session()->forget(['superadmin_id', 'superadmin_name', 'superadmin_email', 'superadmin_userid']);
                return redirect()->route('superadmin.login')
                    ->with('error', 'SuperAdmin not found. Please login again.');
            }

            if (!$superAdmin->is_active) {
                session()->forget(['superadmin_id', 'superadmin_name', 'superadmin_email', 'superadmin_userid']);
                return redirect()->route('superadmin.login')
                    ->with('error', 'Your account is inactive. Please contact administrator.');
            }

            return $next($request);
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Database error in SuperAdmin middleware: ' . $e->getMessage());
            abort(503, 'Database connection error. Please try again later.');
        } catch (\PDOException $e) {
            Log::error('PDO error in SuperAdmin middleware: ' . $e->getMessage());
            abort(503, 'Database connection error. Please try again later.');
        } catch (\Exception $e) {
            Log::error('Error in SuperAdmin middleware: ' . $e->getMessage());
            abort(500, 'An error occurred. Please try again later.');
        }
    }
}

