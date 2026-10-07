<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            $request->session()->put('locale', 'sw');
            app()->setLocale('sw');

            $user = Auth::user();
            
            // Role-based redirects
            if ($user->hasRole('accountant') && !$user->hasAnyRole(['super_admin', 'admin', 'pastor'])) {
                return redirect()->route('financial.dashboard');
            }

            if ($user->hasAnyRole(['deacon', 'shemasi']) && !$user->hasAnyRole(['super_admin', 'admin', 'pastor'])) {
                return redirect()->route('attendance.index');
            }

            if (!$user->hasAnyRole(['super_admin', 'admin', 'pastor', 'secretary'])) {
                return redirect()->route('profile.index');
            }

            return redirect()->intended('dashboard');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();
        $request->session()->put('locale', 'sw');
        app()->setLocale('sw');

        return redirect('/');
    }
}
