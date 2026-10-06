<?php

namespace App\Http\Controllers;

use App\Models\Member;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Public (guest) self-registration.
 *
 * Allows ordinary people to submit their basic details without logging in.
 * Records are stored directly in the `members` table with a "pending" status
 * so that administrators can review and approve them later.
 */
class PublicRegistrationController extends Controller
{
    /**
     * Show the short public registration form.
     */
    public function showForm(): View
    {
        return view('public.register');
    }

    /**
     * Store the submitted basic details directly into the members table.
     */
    public function store(Request $request): RedirectResponse
    {
        // Simple honeypot: real users never fill this hidden field.
        if ($request->filled('website')) {
            return redirect()->route('public.register.success');
        }

        $data = $request->validate([
            'full_name'      => ['required', 'string', 'max:255'],
            'phone'          => ['required', 'string', 'max:20'],
            'email'          => ['nullable', 'email', 'max:255'],
            'gender'         => ['nullable', 'in:male,female,other'],
            'date_of_birth'  => ['nullable', 'date', 'before:today'],
            'marital_status' => ['nullable', 'in:single,married,widowed,divorced'],
            'address'        => ['nullable', 'string', 'max:500'],
        ], [
            'full_name.required'   => 'Tafadhali ingiza jina lako kamili.',
            'phone.required'       => 'Tafadhali ingiza namba yako ya simu.',
            'email.email'          => 'Barua pepe uliyoingiza si sahihi.',
            'date_of_birth.before' => 'Tarehe ya kuzaliwa haiwezi kuwa ya baadaye.',
        ]);

        // members.email is required and unique, so generate one when missing
        // or when the supplied email already exists.
        if (empty($data['email']) || Member::where('email', $data['email'])->exists()) {
            $data['email'] = $this->generateUniqueEmail($data['full_name']);
        }

        $data['status']            = 'pending';
        $data['registration_type'] = 'self_registration';

        Member::create($data);

        return redirect()->route('public.register.success');
    }

    /**
     * Thank-you page shown after a successful submission.
     */
    public function success(): View
    {
        return view('public.register-success');
    }

    /**
     * Build a unique placeholder email for members who did not provide one.
     */
    protected function generateUniqueEmail(string $fullName): string
    {
        $base = Str::slug($fullName, '.');
        if (empty($base)) {
            $base = 'mshiriki';
        }

        $candidate = $base . rand(100, 999) . '@hosannachurch.org';
        while (Member::where('email', $candidate)->exists()) {
            $candidate = $base . rand(1000, 9999) . '@hosannachurch.org';
        }

        return $candidate;
    }
}
