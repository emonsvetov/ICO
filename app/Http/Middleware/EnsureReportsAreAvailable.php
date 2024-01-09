<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\ProgramReports;

class EnsureReportsAreAvailable
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $programId = $request->route('program');

        $reportsAvailable = ProgramReports::where('program_id', $programId)->exists();

        if (!$reportsAvailable) {
            return response()->json(['error' => 'Reports not available'], 403);
        }

        return $next($request);
    }
}
