<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Supplier;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

class SupplierController extends Controller
{
    public function index()
    {
        $suppliers = Supplier::withCount([
            'streamingAccounts as total_accounts_count',
            'streamingAccounts as active_accounts_count' => function ($q) {
                $q->where('status', 'active');
            },
            'streamingAccounts as dropped_accounts_count' => function ($q) {
                $q->where('status', 'dropped');
            },
        ])->latest()->get();

        return view('suppliers.index', compact('suppliers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'whatsapp' => 'nullable|string|max:50',
            'telegram' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'payment_info' => 'nullable|string',
            'rating' => 'required|integer|min:1|max:5',
            'notes' => 'nullable|string',
        ]);

        $validated['status'] = 'active';
        $supplier = Supplier::create($validated);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'create_supplier',
            'description' => "Proveedor {$supplier->name} registrado en el sistema.",
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', "Proveedor {$supplier->name} guardado exitosamente.");
    }

    public function update(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'whatsapp' => 'nullable|string|max:50',
            'telegram' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'payment_info' => 'nullable|string',
            'rating' => 'required|integer|min:1|max:5',
            'notes' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        $supplier->update($validated);

        return back()->with('success', "Datos del proveedor actualizados.");
    }

    public function destroy(Supplier $supplier)
    {
        $name = $supplier->name;
        $supplier->delete();

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'delete_supplier',
            'description' => "Proveedor {$name} eliminado.",
            'ip_address' => request()->ip(),
        ]);

        return back()->with('success', "Proveedor eliminado.");
    }

    public function toggleStatus(Supplier $supplier)
    {
        $supplier->status = $supplier->status === 'active' ? 'inactive' : 'active';
        $supplier->save();

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'toggle_supplier_status',
            'description' => "Proveedor {$supplier->name} actualizado a estado {$supplier->status}.",
            'ip_address' => request()->ip(),
        ]);

        $state = $supplier->status === 'active' ? 'activado para ofrecer cuentas' : 'pausado / desactivado';
        return back()->with('success', "Proveedor {$supplier->name} {$state}.");
    }
}
