<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\StreamingAccount;
use App\Models\AccountProfile;
use App\Models\Service;
use App\Models\Supplier;
use App\Models\AuditLog;
use Carbon\Carbon;

class StreamingAccountController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = StreamingAccount::with(['service', 'supplier', 'profiles.activeSale.customer', 'creator']);

        // Aplicar el scope estricto de inventario según tipo de panel:
        // Admin: ve todo | Proveedor: ve lo suyo y global | Revendedor clonado: ve lo de su proveedor y propio | Individual: solo lo suyo
        $user->applyInventoryScope($query);

        if ($request->filled('service_id')) {
            $query->where('service_id', $request->service_id);
        }

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->filled('modality')) {
            $query->where('modality', $request->modality);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('account_email', 'like', "%{$search}%")
                  ->orWhere('recovery_email', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        $accounts = $query->latest()->paginate(15)->withQueryString();

        $services = Service::where('status', 'active')->get();
        $suppliers = Supplier::where('status', 'active')->get();

        // Resumen dinámico de stock en vivo por plataforma (estilo StreamPro)
        // Estrictamente filtrado por el inventario real del usuario logueado
        $platformStockSummary = [];
        foreach ($services as $srv) {
            $totalProf = AccountProfile::whereHas('streamingAccount', function ($q) use ($srv, $user) {
                $q->where('service_id', $srv->id)->where('status', 'active');
                $user->applyInventoryScope($q);
            })->count();

            $freeProf = AccountProfile::whereHas('streamingAccount', function ($q) use ($srv, $user) {
                $q->where('service_id', $srv->id)->where('status', 'active');
                $user->applyInventoryScope($q);
            })->where('status', 'available')->count();

            $occupiedProf = AccountProfile::whereHas('streamingAccount', function ($q) use ($srv, $user) {
                $q->where('service_id', $srv->id)->where('status', 'active');
                $user->applyInventoryScope($q);
            })->where('status', 'assigned')->count();

            $accountsWithCupo = StreamingAccount::where('service_id', $srv->id)
                ->where('status', 'active')
                ->where(function ($q) use ($user) {
                    $user->applyInventoryScope($q);
                })
                ->whereHas('profiles', function ($q) {
                    $q->where('status', 'available');
                })->count();

            $statusBadge = $freeProf > 2 ? 'in_stock' : ($freeProf > 0 ? 'low_stock' : 'out_of_stock');

            $platformStockSummary[$srv->id] = [
                'service' => $srv,
                'total' => $totalProf,
                'free' => $freeProf,
                'occupied' => $occupiedProf,
                'accountsWithCupo' => $accountsWithCupo,
                'statusBadge' => $statusBadge,
            ];
        }

        // Totales de métricas para la barra superior (estrictamente filtrados por el inventario del usuario)
        $baseStatsQuery = StreamingAccount::query();
        $user->applyInventoryScope($baseStatsQuery);

        $totalMasterAccounts = (clone $baseStatsQuery)->count();
        $activeAccounts = (clone $baseStatsQuery)->where('status', 'active')->count();
        $droppedAccounts = (clone $baseStatsQuery)->where('status', 'dropped')->count();
        $totalAvailableProfiles = AccountProfile::where('status', 'available')->whereHas('streamingAccount', function ($q) use ($user) {
            $q->where('status', 'active');
            $user->applyInventoryScope($q);
        })->count();

        return view('accounts.index', compact(
            'accounts',
            'services',
            'suppliers',
            'platformStockSummary',
            'totalMasterAccounts',
            'activeAccounts',
            'droppedAccounts',
            'totalAvailableProfiles'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'service_id' => 'required|exists:services,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'account_email' => 'required|string|max:255',
            'account_password' => 'required|string|max:255',
            'recovery_email' => 'nullable|string|max:255',
            'modality' => 'required|in:profile,full_account',
            'total_profiles' => 'required|integer|min:1|max:10',
            'supplier_cost' => 'nullable|numeric|min:0',
            'supplier_expiration_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'profile_names' => 'nullable|array',
            'profile_pins' => 'nullable|array',
        ]);

        $validated['created_by'] = Auth::id();
        $validated['supplier_cost'] = $validated['supplier_cost'] ?? 0;
        $validated['status'] = 'active';

        $account = StreamingAccount::create($validated);

        // Crear perfiles según la cantidad
        $total = $validated['total_profiles'];
        for ($i = 1; $i <= $total; $i++) {
            $profileName = $request->input("profile_names.{$i}", "Pantalla {$i}");
            $pin = $request->input("profile_pins.{$i}", str_pad((string)rand(1000, 9999), 4, '0', STR_PAD_LEFT));

            AccountProfile::create([
                'streaming_account_id' => $account->id,
                'profile_number' => $i,
                'profile_name' => $profileName,
                'pin' => $pin,
                'status' => 'available',
            ]);
        }

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'create_streaming_account',
            'description' => "Cuenta maestra {$account->account_email} para servicio ID {$account->service_id} agregada con {$total} perfiles.",
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('accounts.index')->with('success', 'Cuenta de streaming y sus perfiles cargados exitosamente al inventario.');
    }

    public function update(Request $request, StreamingAccount $account)
    {
        $user = Auth::user();
        if (!$user->isAdmin() && $account->created_by !== $user->id) {
            return back()->withErrors(['error' => 'No tienes permiso para modificar una cuenta que no fue cargada por ti.']);
        }

        $validated = $request->validate([
            'account_email' => 'required|string|max:255',
            'account_password' => 'required|string|max:255',
            'recovery_email' => 'nullable|string|max:255',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'supplier_cost' => 'nullable|numeric|min:0',
            'supplier_expiration_date' => 'nullable|date',
            'status' => 'required|in:active,in_review,dropped,expired,paused',
            'notes' => 'nullable|string',
        ]);

        $account->update($validated);

        // Actualizar perfiles si se enviaron
        if ($request->has('profiles')) {
            foreach ($request->input('profiles') as $pId => $pData) {
                $prof = AccountProfile::where('streaming_account_id', $account->id)->find($pId);
                if ($prof) {
                    $prof->update([
                        'profile_name' => $pData['name'] ?? $prof->profile_name,
                        'pin' => $pData['pin'] ?? $prof->pin,
                        'status' => $pData['status'] ?? $prof->status,
                    ]);
                }
            }
        }

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'update_streaming_account',
            'description' => "Cuenta maestra {$account->account_email} actualizada (Estado: {$account->status}).",
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('accounts.index')->with('success', 'Cuenta actualizada correctamente.');
    }

    public function toggleStatus(Request $request, StreamingAccount $account)
    {
        $user = Auth::user();
        if (!$user->isAdmin() && $account->created_by !== $user->id) {
            return back()->withErrors(['error' => 'No tienes permiso para modificar una cuenta que no fue cargada por ti.']);
        }

        $newStatus = $account->status === 'active' ? 'dropped' : 'active';
        $account->update(['status' => $newStatus]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'toggle_account_status',
            'description' => "Estado de la cuenta {$account->account_email} cambiado a {$newStatus}.",
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', "Estado de la cuenta actualizado a: {$newStatus}");
    }

    public function destroy(StreamingAccount $account)
    {
        $user = Auth::user();
        if (!$user->isAdmin() && $account->created_by !== $user->id) {
            return back()->withErrors(['error' => 'No tienes permiso para eliminar una cuenta que no fue cargada por ti.']);
        }

        $hasActiveSales = $account->sales()->whereIn('status', ['active', 'expiring_soon'])->exists();
        if ($hasActiveSales) {
            return back()->withErrors(['error' => 'No puedes eliminar una cuenta que tiene ventas activas vigentes. Primero reemplaza las ventas por garantía o cancélalas.']);
        }

        $email = $account->account_email;
        $account->delete();

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'delete_streaming_account',
            'description' => "Cuenta de streaming {$email} eliminada del inventario.",
            'ip_address' => request()->ip(),
        ]);

        return redirect()->route('accounts.index')->with('success', 'Cuenta eliminada del inventario.');
    }

    /**
     * Reabastecimiento Rápido (+1 Cuenta Máster con perfiles y PINs automáticos estilo StreamPro)
     */
    public function quickReplenish(Request $request)
    {
        $request->validate([
            'service_id' => 'required|exists:services,id',
        ]);

        $service = Service::findOrFail($request->service_id);
        $cleanPrefix = preg_replace('/[^a-z0-9]/', '', strtolower($service->slug));
        $randSuffix = rand(100, 999);
        $masterEmail = "{$cleanPrefix}.stock{$randSuffix}@streampro.me";
        $masterPass = "StreamPro" . rand(1000, 9999) . "*";
        $maxProfiles = $service->max_profiles_default ?: 4;

        $account = StreamingAccount::create([
            'service_id' => $service->id,
            'supplier_id' => Supplier::value('id'),
            'account_email' => $masterEmail,
            'account_password' => $masterPass,
            'modality' => 'profile',
            'total_profiles' => $maxProfiles,
            'supplier_cost' => 12000.00,
            'supplier_expiration_date' => Carbon::now()->addDays(30),
            'status' => 'active',
            'notes' => 'Generada automáticamente por Reabastecimiento Rápido.',
            'created_by' => Auth::id(),
        ]);

        for ($i = 1; $i <= $maxProfiles; $i++) {
            AccountProfile::create([
                'streaming_account_id' => $account->id,
                'profile_number' => $i,
                'profile_name' => "PERFIL {$i}",
                'pin' => str_pad((string)rand(1000, 9999), 4, '0', STR_PAD_LEFT),
                'status' => 'available',
            ]);
        }

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'quick_replenish_stock',
            'description' => "Reabastecimiento rápido: Cuenta máster {$masterEmail} agregada para {$service->name} con {$maxProfiles} perfiles libres.",
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', "¡+1 Cuenta máster para {$service->name} reabastecida con {$maxProfiles} perfiles libres y PINs!");
    }

    /**
     * Cambio Privado de Contraseña en Panel Proveedor (estilo StreamPro)
     */
    public function updatePrivatePassword(Request $request, StreamingAccount $account)
    {
        $request->validate([
            'account_password' => 'required|string|max:255',
            'notify_active_clients' => 'nullable|boolean',
        ]);

        $oldPassword = $account->account_password;
        $newPassword = $request->account_password;

        $account->update([
            'account_password' => $newPassword,
        ]);

        $affectedCount = 0;
        if ($request->boolean('notify_active_clients')) {
            // Actualizar snapshots de ventas activas vinculadas
            $activeSales = $account->sales()->whereIn('status', ['active', 'expiring_soon'])->get();
            foreach ($activeSales as $sale) {
                $sale->update(['account_password_snapshot' => $newPassword]);
                $affectedCount++;
            }
        }

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'change_account_password',
            'description' => "Contraseña de la cuenta máster {$account->account_email} modificada. Notificados clientes activos: {$affectedCount}.",
            'ip_address' => $request->ip(),
        ]);

        $msg = $request->boolean('notify_active_clients')
            ? "Contraseña actualizada y sincronizada en {$affectedCount} cliente(s) con suscripción activa."
            : "Contraseña de {$account->account_email} guardada privadamente (solo visible por Proveedor/Admin).";

        return back()->with('success', $msg);
    }
}
