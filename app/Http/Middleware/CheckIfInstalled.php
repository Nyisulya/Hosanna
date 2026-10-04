<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckIfInstalled
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $isInstalled = file_exists(storage_path('installed')) || env('APP_INSTALLED', false);

        // If not installed and not on the installer routes
        if (! $isInstalled) {
            if (! $request->is('install*') && ! $request->is('up') && ! $request->is('assets*')) {
                return redirect()->route('install.index');
            }
        }

        // If already installed and user tries to access installer
        if ($isInstalled && $request->is('install*')) {
            return redirect()->route('login');
        }

        return $next($request);
    }
}
