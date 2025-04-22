<?php

namespace SmartMailer\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class HandleSmartMailerErrors
{
    public function handle(Request $request, Closure $next)
    {
        try {
            return $next($request);
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'SmartMailer Error: ' . $e->getMessage());
        }
    }
} 