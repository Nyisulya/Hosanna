<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SystemSettingController extends Controller
{
    public function index()
    {
        $settings = SystemSetting::all()->pluck('value', 'key');
        return view('settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $data = $request->except('_token', 'church_logo');

        // Handle text fields
        foreach ($data as $key => $value) {
            SystemSetting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        // Handle Logo Upload
        if ($request->hasFile('church_logo')) {
            $path = $request->file('church_logo')->store('public/settings');
            // Remove 'public/' from path for storage link access
            $publicPath = str_replace('public/', '', $path);
            
            SystemSetting::updateOrCreate(
                ['key' => 'church_logo'],
                ['value' => $publicPath]
            );
        }

        return redirect()->back()->with('success', 'Settings updated successfully!');
    }

    public function testSms(Request $request)
    {
        $phone = $request->input('test_phone');

        if ($phone) {
            $churchName = SystemSetting::where('key', 'church_name')->value('value') ?: config('app.name', 'Kanisa');
            $testMsg = "Jaribio la SMS kutoka mfumo wa {$churchName} (SMS Gate Cloud API) limefanikiwa!";
            $sent = \App\Services\SmsService::send($phone, $testMsg);

            if ($sent) {
                return redirect()->back()->with('success', "SMS ya majaribio imetumwa kikamilifu kwenda namba {$phone}!");
            } else {
                return redirect()->back()->with('error', "SMS ya majaribio imeshindikana. Tafadhali hakikisha simu imeunganishwa na internet kwenye app ya SMS Gate.");
            }
        }

        $connection = \App\Services\SmsService::testConnection();
        if ($connection['connected']) {
            $deviceCount = count($connection['devices']);
            $deviceNames = collect($connection['devices'])->pluck('name')->implode(', ');
            return redirect()->back()->with('success', "Muunganisho na SMS Gate uko imara! Vifaa vilivyounganishwa ({$deviceCount}): {$deviceNames}");
        }

        return redirect()->back()->with('error', "Muunganisho umeshindikana: " . $connection['message']);
    }
}
