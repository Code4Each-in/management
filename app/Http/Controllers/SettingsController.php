<?php

namespace App\Http\Controllers;

use App\Models\Settings;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        $setting = Settings::first();

        return view('settings.index', compact('setting'));
    }

    public function update(Request $request)
    {
        $setting = Settings::first();

        if (!$setting) {
            $setting = new Settings();
        }

        $setting->is_active = $request->has('is_active');
        $setting->start_time = $request->start_time;
        $setting->end_time = $request->end_time;
        $setting->skip_weekends = $request->has('skip_weekends');
        $setting->save();

        return back()->with('success', 'Settings updated successfully');
    }
}