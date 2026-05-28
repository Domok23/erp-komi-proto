<?php

namespace App\Http\Middleware;

use App\Services\CompanyContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCompanySelected
{
    public function handle(Request $request, Closure $next): Response
    {
        // Skip check for the select-company page itself to avoid redirect loop
        if ($request->is('admin/select-company') || $request->is('admin/login')) {
            return $next($request);
        }

        // Skip if already on select-company route
        if ($request->route()?->getName() === 'filament.admin.pages.select-company') {
            return $next($request);
        }

        if (!CompanyContext::hasCompany()) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Company not selected'], 403);
            }

            return redirect()->route('filament.admin.pages.select-company');
        }

        return $next($request);
    }
}
