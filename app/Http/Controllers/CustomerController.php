<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Customer;
use App\Models\AuditLog;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Customer::withCount(['sales', 'activeSales']);

        if ($user->isReseller()) {
            $query->where('user_id', $user->id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $customers = $query->latest()->paginate(15)->withQueryString();

        return view('customers.index', compact('customers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:50',
            'email' => 'nullable|email|max:255',
            'country' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $validated['user_id'] = Auth::id();
        Customer::create($validated);

        return back()->with('success', 'Cliente registrado exitosamente en la agenda CRM.');
    }

    public function update(Request $request, Customer $customer)
    {
        $user = Auth::user();
        if ($user->isReseller() && $customer->user_id !== $user->id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:50',
            'email' => 'nullable|email|max:255',
            'country' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $customer->update($validated);

        return back()->with('success', 'Datos del cliente actualizados.');
    }

    public function show(Customer $customer)
    {
        $user = Auth::user();
        if ($user->isReseller() && $customer->user_id !== $user->id) {
            abort(403);
        }

        $customer->load(['sales.service', 'sales.profile', 'sales.warranties']);
        return view('customers.show', compact('customer'));
    }

    public function destroy(Customer $customer)
    {
        $user = Auth::user();
        if ($user->isReseller() && $customer->user_id !== $user->id) {
            abort(403);
        }

        $customer->delete();
        return back()->with('success', 'Cliente eliminado de la agenda.');
    }
}
