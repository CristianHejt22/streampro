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
        $user = Auth::user();
        $query = Service::with('user');

        if ($user->isAdmin()) {
            // Admin ve todos los servicios
        } elseif ($user->isSuperReseller()) {
            // Proveedor ve servicios del sistema + sus servicios propios
            $query->where(function ($q) use ($user) {
                $q->whereNull('user_id')->orWhere('user_id', $user->id);
            });
        } elseif ($user->isPanelCloned() && $user->parent_id) {
            // Revendedor con tienda clonada: ve servicios de su proveedor + sus servicios propios + del sistema
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhere('user_id', $user->parent_id)
                  ->orWhereNull('user_id');
            });
        } else {
            // Revendedor Individual: ve sus servicios individuales propios + del sistema
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhereNull('user_id');
            });
        }

        $services = $query->latest()->get()->map(function ($serv) use ($user) {
            $totalProf = \App\Models\AccountProfile::whereHas('streamingAccount', function ($q) use ($serv, $user) {
                $q->where('service_id', $serv->id)->where('status', 'active');
                $user->applyInventoryScope($q);
            })->count();

            $freeProf = \App\Models\AccountProfile::whereHas('streamingAccount', function ($q) use ($serv, $user) {
                $q->where('service_id', $serv->id)->where('status', 'active');
                $user->applyInventoryScope($q);
            })->where('status', 'available')->count();

            $occupiedProf = \App\Models\AccountProfile::whereHas('streamingAccount', function ($q) use ($serv, $user) {
                $q->where('service_id', $serv->id)->where('status', 'active');
                $user->applyInventoryScope($q);
            })->where('status', 'assigned')->count();

            $accountsCount = \App\Models\StreamingAccount::where('service_id', $serv->id)
                ->where('status', 'active')
                ->where(function ($q) use ($user) {
                    $user->applyInventoryScope($q);
                })->count();

            $statusBadge = $freeProf > 2 ? 'in_stock' : ($freeProf > 0 ? 'low_stock' : 'out_of_stock');

            $serv->scoped_total_profiles = $totalProf;
            $serv->scoped_free_profiles = $freeProf;
            $serv->scoped_occupied_profiles = $occupiedProf;
            $serv->scoped_accounts_count = $accountsCount;
            $serv->scoped_status_badge = $statusBadge;

            return $serv;
        });

        $activeServicesCount = $services->where('status', 'active')->count();

        return view('services.index', compact('services', 'user', 'activeServicesCount'));
    }

    /**
     * Permite a los usuarios PRO, Proveedores y Administradores clonar y sincronizar
     * las plataformas maestras del proveedor o del sistema a su propio catálogo.
     */
    public function cloneCatalog(Request $request)
    {
        $user = Auth::user();

        if (!$user->canResellPanels() && !$user->isAdmin() && !$user->isPanelPro()) {
            return back()->withErrors([
                'error' => '⛔ La clonación de catálogo está reservada para usuarios con Panel PRO (Ilimitado), Proveedores y Administradores. Actualiza tu panel a PRO para acceder a esta función.'
            ]);
        }

        $sourceQuery = Service::where('status', 'active');
        if ($user->parent_id && $user->parent) {
            $sourceQuery->where(function ($q) use ($user) {
                $q->where('user_id', $user->parent_id)->orWhereNull('user_id');
            });
        } else {
            $sourceQuery->whereNull('user_id');
        }

        $sourceServices = $sourceQuery->get();
        if ($sourceServices->isEmpty()) {
            return back()->with('info', 'No se encontraron plataformas maestras disponibles para clonar.');
        }

        $myExistingNames = Service::where('user_id', $user->id)
            ->pluck('name')
            ->map(fn($n) => mb_strtolower(trim($n)))
            ->toArray();

        $clonedCount = 0;
        foreach ($sourceServices as $src) {
            if (in_array(mb_strtolower(trim($src->name)), $myExistingNames)) {
                continue;
            }

            $suffix = '-' . $user->id . '-' . strtolower(Str::random(4));
            $newService = Service::create([
                'user_id' => $user->id,
                'name' => $src->name,
                'slug' => Str::slug($src->name) . $suffix,
                'category' => $src->category,
                'color_hex' => $src->color_hex,
                'max_profiles_default' => $src->max_profiles_default,
                'description' => $src->description,
                'default_rules' => $src->default_rules,
                'status' => 'active',
            ]);

            ServicePlan::create([
                'service_id' => $newService->id,
                'name' => 'Acceso Directo (Pantalla con PIN)',
                'modality' => 'profile',
                'duration_days' => 30,
                'price' => 4000.00,
                'reseller_price' => 3000.00,
                'cost_estimate' => 2000.00,
                'screens_count' => 1,
                'is_active' => true,
                'is_featured' => true,
            ]);

            $clonedCount++;
        }

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'clone_service_catalog',
            'description' => "Catálogo de plataformas clonado ({$clonedCount} plataformas agregadas a su catálogo).",
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', "¡Catálogo clonado exitosamente! Se han sincronizado {$clonedCount} servicios disponibles a tu panel PRO.");
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        // Control de límite para Panel BÁSICO (máximo 10 servicios)
        if (!$user->isAdmin()) {
            $currentServicesCount = Service::where('status', 'active')
                ->where(function ($q) use ($user) {
                    $q->where('user_id', $user->id)->orWhereNull('user_id');
                    if ($user->parent_id) {
                        $q->orWhere('user_id', $user->parent_id);
                    }
                })->count();

            if ($user->isPanelBasic() && $currentServicesCount >= 10) {
                return back()->withErrors([
                    'error' => "⛔ Límite alcanzado: Tu panel actual es BÁSICO (limitado a 10 servicios). Ya tienes {$currentServicesCount} servicios activos. Para agregar servicios individuales ilimitados, actualiza tu panel a PRO con créditos PRO."
                ])->withInput();
            }
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|in:video,music,anime,iptv,combo,other',
            'color_hex' => 'required|string|max:10',
            'max_profiles_default' => 'required|integer|min:1|max:10',
            'description' => 'nullable|string',
            'default_rules' => 'nullable|string',
        ]);

        $suffix = $user->isAdmin() ? '' : '-' . $user->id . '-' . strtolower(Str::random(4));
        $validated['slug'] = Str::slug($validated['name']) . $suffix;
        $validated['status'] = 'active';
        $validated['user_id'] = $user->isAdmin() ? null : $user->id;

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
        $user = Auth::user();
        if (!$user->isAdmin() && $service->user_id !== $user->id) {
            return back()->withErrors(['error' => 'No puedes modificar un servicio global o de tu proveedor. Solo puedes editar tus servicios individuales propios.']);
        }

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
        $user = Auth::user();
        if (!$user->isAdmin() && $service->user_id !== $user->id) {
            abort(403, 'No tienes permiso para eliminar este servicio.');
        }

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

    public function toggleStatus(Service $service)
    {
        $user = Auth::user();
        if (!$user->isAdmin() && $service->user_id !== $user->id) {
            abort(403, 'No tienes permiso para modificar este servicio.');
        }

        $service->status = $service->status === 'active' ? 'inactive' : 'active';
        $service->save();

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'toggle_service_status',
            'description' => "Servicio {$service->name} cambiado a {$service->status}.",
            'ip_address' => request()->ip(),
        ]);

        $stateMsg = $service->status === 'active' ? 'activado para ofrecer a clientes' : 'pausado / desactivado';
        return back()->with('success', "Servicio {$service->name} {$stateMsg}.");
    }
}
