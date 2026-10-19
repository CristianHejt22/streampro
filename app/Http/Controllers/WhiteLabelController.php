<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Storage;

class WhiteLabelController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Check if reseller has permission for white label (Pro or Custom plan or Admin)
        if ($user->isReseller() && $user->reseller_plan !== 'pro' && $user->reseller_plan !== 'custom') {
            return redirect()->route('dashboard')
                ->with('error', 'La función de Marca Blanca está disponible exclusivamente para revendedores con PLAN PRO.');
        }

        return view('whitelabel.index', compact('user'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        if ($user->isReseller() && $user->reseller_plan !== 'pro' && $user->reseller_plan !== 'custom') {
            abort(403);
        }

        $validated = $request->validate([
            'brand_name' => 'required|string|max:100',
            'brand_logo' => 'nullable|image|max:2048',
            'brand_favicon' => 'nullable|image|max:1024',
            'brand_primary_color' => 'required|string|max:20',
            'brand_accent_color' => 'required|string|max:20',
            'custom_login_bg' => 'nullable|image|max:4096',
            'support_whatsapp' => 'nullable|string|max:50',
            'support_telegram' => 'nullable|string|max:50',
        ]);

        $user->brand_name = $validated['brand_name'];
        $user->brand_primary_color = $validated['brand_primary_color'];
        $user->brand_accent_color = $validated['brand_accent_color'];
        $user->support_whatsapp = $validated['support_whatsapp'];
        $user->support_telegram = $validated['support_telegram'];

        if ($request->hasFile('brand_logo')) {
            $user->brand_logo = $request->file('brand_logo')->store('branding', 'public');
        }

        if ($request->hasFile('brand_favicon')) {
            $user->brand_favicon = $request->file('brand_favicon')->store('branding', 'public');
        }

        if ($request->hasFile('custom_login_bg')) {
            $user->custom_login_bg = $request->file('custom_login_bg')->store('branding', 'public');
        }

        $user->save();

        if ($user->isAdmin()) {
            SystemSetting::set('site_name', $user->brand_name);
        }

        return back()->with('success', '¡Identidad de Marca Blanca actualizada con éxito!');
    }
}
