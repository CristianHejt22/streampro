<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Service;
use App\Models\ServicePlan;
use App\Models\AuditLog;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class ServiceController extends Controller
{
    public function index()
    {
        $services = Service::with('plans')->latest()->get();
        return view('services.index', compact('services'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|in:video,music,anime,iptv,combo,other',
            'color_hex' => 'required|string|max:10',
            'max_profiles_default' => 'required|integer|min:1|max:10',
            'description' => 'nullable|string',
            'default_rules' => 'nullable|string',
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        $validated['status'] = 'active';

        $service = Service::create($validated);

        // Crear por defecto un plan de 1 Pantalla y uno de Cuenta Completa
        ServicePlan::create([
            'service_id' => $service->id,
            'name' => '1 Pantalla con PIN (30 días)',
            'modality' => 'profile',
            'duration_days' => 30,
            'price' => 4000.00,
            'reseller_price' => 3000.00,
            'cost_estimate' => 2000.00,
            'screens_count' => 1,
            'is_active' => true,
            'is_featured' => true,
        ]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'create_service',
            'description' => "Plataforma streaming {$service->name} creada con planes iniciales.",
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('services.index')->with('success', "Plataforma {$service->name} creada con éxito.");
    }

    public function update(Request $request, Service $service)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|in:video,music,anime,iptv,combo,other',
            'color_hex' => 'required|string|max:10',
            'max_profiles_default' => 'required|integer|min:1|max:10',
            'description' => 'nullable|string',
            'default_rules' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        $service->update($validated);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'update_service',
            'description' => "Plataforma {$service->name} actualizada.",
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('services.index')->with('success', "Plataforma {$service->name} actualizada.");
    }

    public function storePlan(Request $request, Service $service)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'modality' => 'required|in:profile,full_account',
            'duration_days' => 'required|integer|min:1|max:365',
            'price' => 'required|numeric|min:0',
            'reseller_price' => 'nullable|numeric|min:0',
            'cost_estimate' => 'nullable|numeric|min:0',
            'screens_count' => 'required|integer|min:1|max:10',
            'is_featured' => 'nullable|boolean',
        ]);

        $validated['service_id'] = $service->id;
        // Ganancia Revendedor del 40%: compra al 60% del precio oficial de venta
        $price = (float)$validated['price'];
        if (empty($validated['reseller_price']) || (float)$validated['reseller_price'] == 0) {
            $validated['reseller_price'] = round($price * 0.60, 2);
        }
        $validated['cost_estimate'] = $validated['cost_estimate'] ?? 0;
        $validated['is_featured'] = $request->boolean('is_featured');
        $validated['is_active'] = true;

        ServicePlan::create($validated);

        return back()->with('success', "Nuevo plan creado para {$service->name}.");
    }

    public function togglePlan(ServicePlan $plan)
    {
        $plan->update(['is_active' => !$plan->is_active]);
        return back()->with('success', "Estado del plan actualizado.");
    }

    public function destroy(Service $service)
    {
        $name = $service->name;
        $service->delete();

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'delete_service',
            'description' => "Plataforma {$name} eliminada.",
            'ip_address' => request()->ip(),
        ]);

        return redirect()->route('services.index')->with('success', "Plataforma eliminada.");
    }
}
