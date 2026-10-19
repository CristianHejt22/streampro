<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Warranty;
use App\Models\Sale;
use App\Models\AccountProfile;
use App\Models\AuditLog;

class WarrantyController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Warranty::with(['sale.customer', 'sale.service', 'user', 'oldAccount', 'newAccount']);

        if ($user->isReseller()) {
            $query->where('user_id', $user->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $warranties = $query->latest()->paginate(15)->withQueryString();

        return view('warranties.index', compact('warranties'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'sale_id' => 'required|exists:sales,id',
            'issue_type' => 'required|in:password_changed,account_disabled,screen_occupied,geo_blocked,other',
            'description' => 'required|string',
        ]);

        $sale = Sale::findOrFail($request->sale_id);
        $user = Auth::user();

        if ($user->isReseller() && $sale->user_id !== $user->id) {
            abort(403);
        }

        $warranty = Warranty::create([
            'sale_id' => $sale->id,
            'user_id' => $user->id,
            'issue_type' => $validated['issue_type'],
            'description' => $validated['description'],
            'old_streaming_account_id' => $sale->streaming_account_id,
            'old_account_profile_id' => $sale->account_profile_id,
            'status' => 'pending',
        ]);

        return back()->with('success', 'Ticket de garantía registrado para la venta ' . $sale->sale_code);
    }

    public function resolve(Request $request, Warranty $warranty)
    {
        $validated = $request->validate([
            'resolution_notes' => 'required|string',
            'action_type' => 'required|in:replace_stock,manual_credentials,reject',
        ]);

        $sale = $warranty->sale;

        if ($validated['action_type'] === 'reject') {
            $warranty->update([
                'status' => 'rejected',
                'resolution_notes' => $validated['resolution_notes'],
                'resolved_at' => now(),
            ]);
            return back()->with('success', 'Garantía marcada como rechazada.');
        }

        // Si es reemplazo automático desde stock
        if ($validated['action_type'] === 'replace_stock') {
            $service = $sale->service;
            $newProfile = AccountProfile::whereHas('streamingAccount', function ($q) use ($service) {
                $q->where('service_id', $service->id)->where('status', 'active');
            })->where('status', 'available')->first();

            if (!$newProfile) {
                return back()->withErrors(['error' => "No hay pantallas disponibles en stock para {$service->name}. Carga stock primero."]);
            }

            $newAccount = $newProfile->streamingAccount;
            $newProfile->update(['status' => 'assigned']);

            if ($warranty->oldProfile) {
                $warranty->oldProfile->update(['status' => 'in_review']);
            }

            $warranty->update([
                'new_streaming_account_id' => $newAccount->id,
                'new_account_profile_id' => $newProfile->id,
                'status' => 'resolved',
                'resolution_notes' => $validated['resolution_notes'],
                'resolved_at' => now(),
            ]);

            $sale->update([
                'streaming_account_id' => $newAccount->id,
                'account_profile_id' => $newProfile->id,
                'account_email_snapshot' => $newAccount->account_email,
                'account_password_snapshot' => $newAccount->account_password,
                'profile_name_snapshot' => $newProfile->profile_name,
                'profile_pin_snapshot' => $newProfile->pin,
                'status' => 'active',
            ]);
        } else {
            // Manual credentials
            $warranty->update([
                'status' => 'resolved',
                'resolution_notes' => $validated['resolution_notes'],
                'resolved_at' => now(),
            ]);
        }

        return back()->with('success', 'Garantía resuelta satisfactoriamente.');
    }
}
