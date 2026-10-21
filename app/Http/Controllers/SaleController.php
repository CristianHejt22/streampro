<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Sale;
use App\Models\StreamingAccount;
use App\Models\AccountProfile;
use App\Models\Customer;
use App\Models\Service;
use App\Models\ServicePlan;
use App\Models\Warranty;
use App\Models\RenewalRequest;
use App\Models\AuditLog;
use Carbon\Carbon;
use Illuminate\Support\Str;

class SaleController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Sale::with(['customer', 'service', 'plan', 'streamingAccount', 'profile', 'user']);

        // Si es revendedor, solo ve sus propias ventas
        if ($user->isReseller()) {
            $query->where('user_id', $user->id);
        } elseif ($request->filled('seller_id')) {
            $query->where('user_id', $request->seller_id);
        }

        // Filtro por estado calculado
        $today = Carbon::today();
        if ($request->filled('status')) {
            $status = $request->status;
            if ($status === 'active') {
                $query->where('end_date', '>', $today->copy()->addDays(3)->toDateString())
                      ->where('status', '!=', 'cancelled');
            } elseif ($status === 'expiring_soon') {
                $query->where('end_date', '>=', $today->toDateString())
                      ->where('end_date', '<=', $today->copy()->addDays(3)->toDateString())
                      ->where('status', '!=', 'cancelled');
            } elseif ($status === 'expired') {
                $query->where('end_date', '<', $today->toDateString())
                      ->where('status', '!=', 'cancelled');
            } else {
                $query->where('status', $status);
            }
        }

        // Filtro por servicio
        if ($request->filled('service_id')) {
            $query->where('service_id', $request->service_id);
        }

        // Búsqueda por cliente, código o email
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('sale_code', 'like', "%{$search}%")
                  ->orWhere('account_email_snapshot', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        $sales = $query->latest()->paginate(15)->withQueryString();

        // Solicitudes de Renovación pendientes enviadas desde la Bóveda de Clientes
        $renewalQuery = RenewalRequest::with(['sale.customer', 'sale.service', 'sale.plan', 'customer', 'service', 'plan', 'user'])
            ->where('status', 'pending');
        if ($user->isReseller()) {
            if ($user->isSuperReseller()) {
                $renewalQuery->where(function($q) use ($user) {
                    $q->where('user_id', $user->id)
                      ->orWhereHas('user', function($qu) use ($user) {
                          $qu->where('parent_id', $user->id)
                             ->where('whatsapp_support_preference', 'provider');
                      });
                });
            } else {
                $renewalQuery->where('user_id', $user->id);
            }
        }
        $pendingRenewals = $renewalQuery->latest()->get();

        // Opciones de filtros
        $services = Service::where('status', 'active')->get();
        $sellers = $user->isAdmin() ? \App\Models\User::where('role', 'reseller')->get() : collect();

        // Contadores para píldoras de filtro
        $baseQuery = Sale::query();
        if ($user->isReseller()) {
            $baseQuery->where('user_id', $user->id);
        }

        $countActive = (clone $baseQuery)->where('end_date', '>', $today->copy()->addDays(3)->toDateString())->where('status', '!=', 'cancelled')->count();
        $countExpiring = (clone $baseQuery)->where('end_date', '>=', $today->toDateString())->where('end_date', '<=', $today->copy()->addDays(3)->toDateString())->where('status', '!=', 'cancelled')->count();
        $countExpired = (clone $baseQuery)->where('end_date', '<', $today->toDateString())->where('status', '!=', 'cancelled')->count();

        // Stock disponible para el modal de "Nueva Venta"
        $availablePlans = ServicePlan::with('service')->where('is_active', true)->get();
        $customersList = Customer::where(function ($q) use ($user) {
            if ($user->isReseller()) {
                $q->where('user_id', $user->id);
            }
        })->orderBy('name')->get();

        return view('sales.index', compact(
            'sales',
            'services',
            'sellers',
            'countActive',
            'countExpiring',
            'countExpired',
            'availablePlans',
            'customersList',
            'pendingRenewals'
        ));
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'service_plan_id' => 'required|exists:service_plans,id',
            'customer_mode' => 'required|in:existing,new',
            'customer_id' => 'nullable|required_if:customer_mode,existing|exists:customers,id',
            'customer_name' => 'nullable|required_if:customer_mode,new|string|max:255',
            'customer_phone' => 'nullable|required_if:customer_mode,new|string|max:50',
            'customer_email' => 'nullable|email|max:255',
            'custom_price' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $plan = ServicePlan::with('service')->findOrFail($request->service_plan_id);
        $service = $plan->service;

        // 1. Obtener o crear cliente
        if ($request->customer_mode === 'new') {
            $customer = Customer::create([
                'user_id' => $user->id,
                'name' => $request->customer_name,
                'phone' => $request->customer_phone,
                'email' => $request->customer_email,
            ]);
        } else {
            $customer = Customer::findOrFail($request->customer_id);
        }

        // 2. Control de Saldo, Duración en Meses y Consignación para Revendedores
        $months = max(1, (int) $request->input('months_duration', 1));
        $isConsignment = $request->boolean('is_consignment');

        $pricing = $user->getEffectivePlanPricing($plan);
        $defaultSalePrice = ($user->isReseller() && !empty($pricing['reseller_pvp'])) ? (float)$pricing['reseller_pvp'] : (float)$plan->price;
        $baseSalePrice = $request->filled('custom_price') ? (float)$request->custom_price : $defaultSalePrice;
        $salePrice = $baseSalePrice * $months;

        // Costo mayorista de proveedor efectivo para este revendedor
        $baseResellerPrice = ($user->isReseller() && !empty($pricing['provider_cost']))
            ? (float)$pricing['provider_cost']
            : (($plan->reseller_price > 0) ? (float)$plan->reseller_price : ((float)$plan->price * (1 - ($user->getCommissionForService($service->id) / 100))));

        $costPrice = ($user->isReseller() ? $baseResellerPrice : (float)($plan->cost_estimate ?: 0)) * $months;
        $profit = $salePrice - $costPrice;

        // Comisión del 25% para el panel padre patrocinador (copia de catálogo)
        $parentCommission = 0.00;
        if ($user->parent_id && $user->parent) {
            $pct = (float) ($user->parent_commission_pct ?: 25.00);
            $parentCommission = round($profit * ($pct / 100), 2);
        }

        if ($user->isReseller()) {
            $providerBlockReason = null;
            if (!$user->canDeliverAccounts($providerBlockReason)) {
                return back()->withErrors([
                    'error' => "⛔ Entrega Bloqueada: {$providerBlockReason} No se pueden emitir ni entregar cuentas sin la confirmación de pago del proveedor."
                ])->withInput();
            }

            if (!$isConsignment) {
                if (!$user->hasBalance($costPrice)) {
                    return back()->withErrors([
                        'error' => "⛔ Entrega Bloqueada: No se puede entregar la cuenta sin monto en billetera. Costo requerido: \${$costPrice}, Saldo actual: \${$user->balance}. Por favor recarga saldo antes de realizar la entrega."
                    ])->withInput();
                }
                $user->deductBalance($costPrice);

                // Acreditar comisión del 25% al panel que ofreció/clonó el servicio
                if ($parentCommission > 0 && $user->parent) {
                    $user->parent->addBalance($parentCommission);
                }
            }
        }

        // 3. Asignación automática desde el stock disponible (respetando inventario propio de proveedor y revendedor)
        $streamingAccount = null;
        $profile = null;

        $stockAccountFilter = function ($q) use ($service, $user) {
            $q->where('service_id', $service->id)->where('status', 'active');
            $user->applyInventoryScope($q);
        };

        if ($plan->modality === 'profile') {
            // Buscar un perfil disponible
            $profile = AccountProfile::whereHas('streamingAccount', $stockAccountFilter)
                ->where('status', 'available')
                ->first();

            if (!$profile) {
                return back()->withErrors(['error' => "No hay pantallas disponibles en stock para {$service->name}. Por favor carga cuentas en el módulo de Inventario antes de realizar la venta."])->withInput();
            }

            $streamingAccount = $profile->streamingAccount;
            $profile->update(['status' => 'assigned']);
        } else {
            // Cuenta completa
            $accountQuery = StreamingAccount::query();
            $stockAccountFilter($accountQuery);
            $streamingAccount = $accountQuery->where('modality', 'full_account')
                ->whereDoesntHave('sales', function ($q) {
                    $q->whereIn('status', ['active', 'expiring_soon']);
                })->first();

            if (!$streamingAccount) {
                return back()->withErrors(['error' => "No hay cuentas completas disponibles en stock para {$service->name}. Carga una cuenta completa en el Inventario."])->withInput();
            }
        }

        // 4. Crear registro inmutable de Venta
        $startDate = Carbon::today();
        $endDate = $startDate->copy()->addMonths($months);
        $saleCode = 'CST-' . date('Ymd') . '-' . strtoupper(Str::random(4));

        $sale = Sale::create([
            'sale_code' => $saleCode,
            'user_id' => $user->id,
            'customer_id' => $customer->id,
            'service_id' => $service->id,
            'service_plan_id' => $plan->id,
            'streaming_account_id' => $streamingAccount->id,
            'account_profile_id' => $profile ? $profile->id : null,
            'modality' => $plan->modality,
            'account_email_snapshot' => $streamingAccount->account_email,
            'account_password_snapshot' => $streamingAccount->account_password,
            'profile_name_snapshot' => $profile ? $profile->profile_name : 'Cuenta Completa',
            'profile_pin_snapshot' => $profile ? $profile->pin : null,
            'usage_rules_snapshot' => $service->default_rules,
            'sale_price' => $salePrice,
            'cost_price' => $costPrice,
            'profit' => $profit,
            'parent_commission_amount' => $parentCommission,
            'months_duration' => $months,
            'is_consignment' => $isConsignment,
            'is_consignment_paid' => !$isConsignment,
            'consignment_expires_at' => $isConsignment ? Carbon::now()->addHours(48) : null,
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'status' => 'active',
            'notes' => $request->notes,
        ]);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'create_sale',
            'description' => "Venta {$saleCode} creada para {$customer->name} ({$service->name}). Duración: {$months} mes(es). Consignación: " . ($isConsignment ? 'SÍ (48h)' : 'NO (Pagada)'),
            'ip_address' => $request->ip(),
        ]);

        $consMsg = $isConsignment 
            ? ' [EN CONSIGNACIÓN: Las credenciales quedan RETENIDAS y NO se entregarán en la Bóveda del cliente hasta liquidar los $' . number_format($costPrice, 2) . ' desde tu billetera]' 
            : ' [Credenciales entregadas al cliente en su Bóveda]';

        return redirect()->route('sales.index')
            ->with('success', "¡Venta {$saleCode} registrada con éxito!{$consMsg}")
            ->with('new_sale_id', $sale->id);
    }

    /**
     * Liquidar / Pagar cuenta emitida en Consignación antes de las 48hs
     */
    public function payConsignment(Request $request, Sale $sale)
    {
        $user = Auth::user();
        if ($user->isReseller() && $sale->user_id !== $user->id) {
            abort(403);
        }

        if (!$sale->is_consignment || $sale->is_consignment_paid) {
            return back()->with('info', 'Esta venta ya se encuentra liquidada y pagada.');
        }

        if ($sale->isConsignmentExpired()) {
            return back()->withErrors(['error' => 'El plazo de 48 horas de consignación ya expiró y la cuenta ha caído.']);
        }

        // Si es revendedor, descontar el costo de su balance
        if ($user->isReseller()) {
            $providerBlockReason = null;
            if (!$user->canDeliverAccounts($providerBlockReason)) {
                return back()->withErrors(['error' => "⛔ Liquidación Bloqueada: {$providerBlockReason} Regulariza el estado con tu proveedor."]);
            }

            if (!$user->hasBalance($sale->cost_price)) {
                return back()->withErrors(['error' => "Saldo insuficiente para liquidar y entregar. Requiere \${$sale->cost_price} y tu saldo actual es de \${$user->balance}. Recarga tu billetera."]);
            }
            $user->deductBalance($sale->cost_price);

            // Acreditar 25% de comisión al panel padre patrocinador
            if ($sale->parent_commission_amount > 0 && $user->parent) {
                $user->parent->addBalance($sale->parent_commission_amount);
            }
        }

        $sale->update([
            'is_consignment_paid' => true,
            'status' => 'active',
        ]);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'pay_consignment',
            'description' => "Consignación {$sale->sale_code} liquidada con éxito por \${$sale->cost_price}. Credenciales entregadas al cliente.",
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', "¡Consignación de la venta {$sale->sale_code} pagada exitosamente! Las credenciales han sido entregadas y desbloqueadas en la Bóveda del Cliente.");
    }

    public function renew(Request $request, Sale $sale)
    {
        $user = Auth::user();
        if ($user->isReseller() && $sale->user_id !== $user->id) {
            abort(403);
        }

        $days = (int) $request->input('days', 30);
        $plan = $sale->plan;

        // Descontar saldo si es revendedor
        if ($user->isReseller() && $plan) {
            $providerBlockReason = null;
            if (!$user->canDeliverAccounts($providerBlockReason)) {
                return back()->withErrors(['error' => "⛔ Renovación Bloqueada: {$providerBlockReason} No se pueden entregar renovaciones sin el pago del proveedor."]);
            }

            $cost = (float)($plan->reseller_price > 0 ? $plan->reseller_price : $plan->price);
            if (!$user->hasBalance($cost)) {
                return back()->withErrors(['error' => "⛔ No se puede entregar la renovación sin monto en billetera (\${$cost} requeridos). Tu saldo actual es de \${$user->balance}."]);
            }
            $user->deductBalance($cost);
        }

        // Extender fecha de vencimiento
        $currentEnd = Carbon::parse($sale->end_date);
        $newEnd = $currentEnd->isPast() ? Carbon::today()->addDays($days) : $currentEnd->addDays($days);

        $sale->update([
            'end_date' => $newEnd->toDateString(),
            'status' => 'active',
            'renewal_count' => $sale->renewal_count + 1,
            'reminder_sent_at' => null, // Reset reminder flag
        ]);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'renew_sale',
            'description' => "Venta {$sale->sale_code} renovada por {$days} días más. Nuevo vencimiento: {$newEnd->format('d/m/Y')}.",
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', "¡Venta renovada con éxito! Nueva fecha de vencimiento: {$newEnd->format('d/m/Y')}.");
    }

    public function replaceWarranty(Request $request, Sale $sale)
    {
        $user = Auth::user();
        if ($user->isReseller() && $sale->user_id !== $user->id) {
            abort(403);
        }

        $providerBlockReason = null;
        if (!$user->canDeliverAccounts($providerBlockReason)) {
            return back()->withErrors(['error' => "⛔ Garantía Bloqueada: {$providerBlockReason} Regulariza el estado de tu panel con tu proveedor antes de entregar reemplazos."]);
        }

        if ($sale->is_consignment && !$sale->is_consignment_paid) {
            return back()->withErrors(['error' => 'No puedes modificar ni aplicar cambios a una cuenta en consignación hasta que sea liquidada y pagada dentro del plazo de 48 horas.']);
        }

        $request->validate([
            'reason' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        $service = $sale->service;
        $oldAccount = $sale->streamingAccount;
        $oldProfile = $sale->profile;

        // Buscar reemplazo en stock disponible dentro de su alcance de inventario
        $newAccount = null;
        $newProfile = null;

        if ($sale->modality === 'profile') {
            $newProfile = AccountProfile::whereHas('streamingAccount', function ($q) use ($service, $user) {
                $q->where('service_id', $service->id)->where('status', 'active');
                $user->applyInventoryScope($q);
            })->where('status', 'available')->first();

            if (!$newProfile) {
                return back()->withErrors(['error' => "No hay pantallas disponibles en tu stock para reemplazar la cuenta de {$service->name}. Carga stock primero."]);
            }

            $newAccount = $newProfile->streamingAccount;
            $newProfile->update(['status' => 'assigned']);

            // Marcar perfil anterior en revisión o caído
            if ($oldProfile) {
                $oldProfile->update(['status' => 'in_review']);
            }
        } else {
            $accountQuery = StreamingAccount::where('service_id', $service->id)
                ->where('modality', 'full_account')
                ->where('status', 'active')
                ->where('id', '!=', $oldAccount->id);
            $user->applyInventoryScope($accountQuery);
            $newAccount = $accountQuery->first();

            if (!$newAccount) {
                return back()->withErrors(['error' => "No hay cuentas completas de reemplazo en tu stock para {$service->name}."]);
            }

            $oldAccount->update(['status' => 'dropped']);
        }

        // Registrar garantía
        Warranty::create([
            'sale_id' => $sale->id,
            'user_id' => $user->id,
            'issue_type' => $request->input('issue_type', 'password_changed'),
            'description' => $request->reason,
            'old_streaming_account_id' => $oldAccount ? $oldAccount->id : null,
            'old_account_profile_id' => $oldProfile ? $oldProfile->id : null,
            'new_streaming_account_id' => $newAccount->id,
            'new_account_profile_id' => $newProfile ? $newProfile->id : null,
            'status' => 'resolved',
            'resolution_notes' => 'Reemplazo automático de credenciales asignadas desde el stock.',
            'resolved_at' => now(),
        ]);

        // Actualizar la venta con las nuevas credenciales snapshot
        $sale->update([
            'streaming_account_id' => $newAccount->id,
            'account_profile_id' => $newProfile ? $newProfile->id : null,
            'account_email_snapshot' => $newAccount->account_email,
            'account_password_snapshot' => $newAccount->account_password,
            'profile_name_snapshot' => $newProfile ? $newProfile->profile_name : 'Cuenta Completa',
            'profile_pin_snapshot' => $newProfile ? $newProfile->pin : null,
            'status' => 'active',
            'notes' => ($sale->notes ? $sale->notes . "\n" : '') . "Garantía aplicada el " . date('d/m/Y') . ": " . $request->reason,
        ]);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'warranty_replacement',
            'description' => "Garantía aplicada a venta {$sale->sale_code}. Nueva cuenta: {$newAccount->account_email}.",
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', '¡Garantía aplicada con éxito! Se asignaron nuevas credenciales. Envía los nuevos datos al cliente.');
    }

    public function markReminderSent(Sale $sale)
    {
        $sale->update(['reminder_sent_at' => now()]);
        return response()->json(['success' => true]);
    }

    public function destroy(Sale $sale)
    {
        $user = Auth::user();
        if ($user->isReseller() && $sale->user_id !== $user->id) {
            abort(403);
        }

        // Liberar perfil asignado
        if ($sale->profile) {
            $sale->profile->update(['status' => 'available']);
        }

        $code = $sale->sale_code;
        $sale->delete();

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'delete_sale',
            'description' => "Venta {$code} cancelada y eliminada. Perfil liberado de nuevo al stock.",
            'ip_address' => request()->ip(),
        ]);

        return redirect()->route('sales.index')->with('success', "Venta {$code} eliminada y perfil devuelto al stock.");
    }

    public function exportCsv()
    {
        $user = Auth::user();
        $query = Sale::with(['customer', 'service', 'user']);
        if ($user->isReseller()) {
            $query->where('user_id', $user->id);
        }

        $sales = $query->latest()->get();

        $filename = 'ventas_streaming_' . date('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($sales) {
            $file = fopen('php://output', 'w');
            // UTF-8 BOM para Excel
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($file, [
                'Código', 'Servicio', 'Cliente', 'WhatsApp', 'Email Cuenta', 'Contraseña',
                'Perfil', 'PIN', 'Monto Venta', 'Costo', 'Ganancia', 'Inicio', 'Vencimiento', 'Estado', 'Vendedor'
            ]);

            foreach ($sales as $s) {
                $canDeliver = $s->canDeliverCredentials();
                fputcsv($file, [
                    $s->sale_code,
                    $s->service ? $s->service->name : '',
                    $s->customer ? $s->customer->name : '',
                    $s->customer ? $s->customer->phone : '',
                    $canDeliver ? $s->account_email_snapshot : '[RETENIDO - PAGO PENDIENTE]',
                    $canDeliver ? $s->account_password_snapshot : '[RETENIDO - PAGO PENDIENTE]',
                    $s->profile_name_snapshot,
                    $canDeliver ? $s->profile_pin_snapshot : '[RETENIDO]',
                    $s->sale_price,
                    $s->cost_price,
                    $s->profit,
                    $s->start_date ? $s->start_date->format('d/m/Y') : '',
                    $s->end_date ? $s->end_date->format('d/m/Y') : '',
                    $s->calculated_status,
                    $s->user ? $s->user->name : '',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Aprobar y Extender una Solicitud de Renovación enviada desde la Bóveda del Cliente
     */
    public function approveRenewalRequest(Request $request, RenewalRequest $renewalRequest)
    {
        $user = Auth::user();
        $isOwner = $renewalRequest->user_id === $user->id;
        $isParentSponsor = $user->isSuperReseller() && $renewalRequest->user && $renewalRequest->user->parent_id === $user->id && $renewalRequest->user->whatsapp_support_preference === 'provider';
        if ($user->isReseller() && !$isOwner && !$isParentSponsor) {
            abort(403, 'No tienes permiso para aprobar esta solicitud.');
        }

        if ($renewalRequest->status !== 'pending') {
            return back()->with('info', 'Esta solicitud ya fue procesada anteriormente.');
        }

        $sale = $renewalRequest->sale;
        $months = max(1, (int) $renewalRequest->months_duration);
        $days = $months * 30;
        $plan = $sale->plan;

        // Descontar costo del revendedor si corresponde
        $sellerToDeduct = $renewalRequest->user ?: $user;
        if ($sellerToDeduct->isReseller() && $plan) {
            $providerBlockReason = null;
            if (!$sellerToDeduct->canDeliverAccounts($providerBlockReason)) {
                return back()->withErrors(['error' => "⛔ Renovación Bloqueada: {$providerBlockReason} No se pueden entregar renovaciones sin el pago del proveedor al día."]);
            }

            $pricing = $sellerToDeduct->getEffectivePlanPricing($plan);
            $unitCost = !empty($pricing['provider_cost']) ? (float)$pricing['provider_cost'] : ($plan->reseller_price > 0 ? (float)$plan->reseller_price : ((float)$plan->price * 0.60));
            $totalCost = $unitCost * $months;

            if (!$sellerToDeduct->hasBalance($totalCost)) {
                return back()->withErrors(['error' => "⛔ No se puede entregar la renovación sin monto en billetera de {$sellerToDeduct->name}. Requiere \${$totalCost} y su saldo actual es de \${$sellerToDeduct->balance}. Recarga saldo antes de aprobar."]);
            }

            $sellerToDeduct->deductBalance($totalCost);

            // Acreditar comisión al patrocinador si corresponde
            if ($sellerToDeduct->parent) {
                $parentPct = (float)($sellerToDeduct->parent_commission_pct ?: 25.00);
                $profit = ($renewalRequest->amount - $totalCost);
                $parentCut = round(max(0, $profit) * ($parentPct / 100), 2);
                if ($parentCut > 0) {
                    $sellerToDeduct->parent->addBalance($parentCut);
                }
            }
        }

        // Extender fecha de vencimiento
        $currentEnd = Carbon::parse($sale->end_date);
        $newEnd = $currentEnd->isPast() ? Carbon::today()->addDays($days) : $currentEnd->addDays($days);

        $sale->update([
            'end_date' => $newEnd->toDateString(),
            'status' => 'active',
            'renewal_count' => $sale->renewal_count + 1,
            'reminder_sent_at' => null,
            'is_consignment_paid' => true,
        ]);

        $renewalRequest->update([
            'status' => 'approved',
            'processed_at' => Carbon::now(),
            'admin_notes' => $request->input('admin_notes', 'Aprobada por el distribuidor'),
        ]);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'approve_renewal_request',
            'description' => "Solicitud de renovación aprobada para {$sale->sale_code} ({$sale->service->name}). Extendido por {$months} mes(es) hasta {$newEnd->format('d/m/Y')}. Monto: \${$renewalRequest->amount}.",
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', "¡Solicitud de renovación aprobada! La cuenta {$sale->sale_code} se ha extendido por {$months} mes(es) hasta el {$newEnd->format('d/m/Y')}.");
    }

    /**
     * Rechazar una Solicitud de Renovación
     */
    public function rejectRenewalRequest(Request $request, RenewalRequest $renewalRequest)
    {
        $user = Auth::user();
        $isOwner = $renewalRequest->user_id === $user->id;
        $isParentSponsor = $user->isSuperReseller() && $renewalRequest->user && $renewalRequest->user->parent_id === $user->id && $renewalRequest->user->whatsapp_support_preference === 'provider';
        if ($user->isReseller() && !$isOwner && !$isParentSponsor) {
            abort(403);
        }

        $reason = $request->input('reason', 'Comprobante no acreditado o datos incorrectos');

        $renewalRequest->update([
            'status' => 'rejected',
            'processed_at' => Carbon::now(),
            'admin_notes' => $reason,
        ]);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'reject_renewal_request',
            'description' => "Solicitud de renovación rechazada para {$renewalRequest->sale->sale_code}. Motivo: {$reason}.",
            'ip_address' => $request->ip(),
        ]);

        return back()->with('info', "La solicitud de renovación ha sido rechazada. Motivo: {$reason}");
    }
}
