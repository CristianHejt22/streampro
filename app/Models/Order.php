<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_code',
        'seller_id',
        'service_plan_id',
        'customer_name',
        'customer_phone',
        'customer_email',
        'boveda_pin',
        'amount',
        'payment_method',
        'payment_status',
        'receipt_path',
        'sale_id',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (empty($order->order_code)) {
                $order->order_code = 'ORD-' . date('Ymd') . '-' . strtoupper(Str::random(5));
            }
        });
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function servicePlan()
    {
        return $this->belongsTo(ServicePlan::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * Eliminación permanente e inmediata del comprobante físico para no saturar hosting compartido
     */
    public function deleteReceiptFile(): void
    {
        if ($this->receipt_path) {
            if (Storage::disk('local')->exists($this->receipt_path)) {
                Storage::disk('local')->delete($this->receipt_path);
            }
            if (Storage::disk('public')->exists($this->receipt_path)) {
                Storage::disk('public')->delete($this->receipt_path);
            }

            $this->update([
                'receipt_path' => null,
            ]);
        }
    }

    /**
     * Aprobar y Cerrar: Asigna cuenta/pantalla del stock, crea venta, genera credenciales y elimina comprobante
     */
    public function approveAndClose(?string $notes = null): Sale
    {
        $plan = $this->servicePlan;
        if (!$plan) {
            throw new \Exception("El plan seleccionado ya no existe en el catálogo.");
        }

        $service = $plan->service;

        $seller = $this->seller;

        // Buscar stock disponible según modalidad y alcance de inventario del vendedor
        $streamingAccount = null;
        $profile = null;

        if ($plan->modality === 'profile') {
            // Buscar perfil libre dentro del inventario habilitado para este vendedor
            $profileQuery = AccountProfile::whereHas('streamingAccount', function ($q) use ($service, $seller) {
                $q->where('service_id', $service->id)->where('status', 'active');
                if ($seller) {
                    $seller->applyInventoryScope($q);
                }
            })->where('status', 'available');

            $profile = $profileQuery->first();

            if (!$profile) {
                throw new \Exception("No hay pantallas disponibles en stock para {$service->name}. Carga stock primero antes de aprobar la orden.");
            }

            $streamingAccount = $profile->streamingAccount;
        } else {
            // Cuenta completa dentro del alcance de inventario
            $accountQuery = StreamingAccount::where('service_id', $service->id)
                ->where('modality', 'full_account')
                ->where('status', 'active')
                ->whereDoesntHave('sales', function ($q) {
                    $q->whereIn('status', ['active', 'expiring_soon']);
                });

            if ($seller) {
                $seller->applyInventoryScope($accountQuery);
            }

            $streamingAccount = $accountQuery->first();

            if (!$streamingAccount) {
                throw new \Exception("No hay cuentas completas en stock para {$service->name}. Carga stock primero antes de aprobar la orden.");
            }
        }

        // Buscar o crear cliente
        $customer = Customer::firstOrCreate(
            [
                'user_id' => $this->seller_id,
                'phone' => $this->customer_phone,
            ],
            [
                'name' => $this->customer_name,
                'email' => $this->customer_email,
                'password' => bcrypt($this->boveda_pin ?: '123456'),
                'security_pin' => $this->boveda_pin,
            ]
        );

        if ($this->boveda_pin && (empty($customer->security_pin) || $customer->wasRecentlyCreated)) {
            $customer->update(['security_pin' => $this->boveda_pin]);
        }

        // Validación estricta de entrega y descuento de billetera si el vendedor es revendedor
        $parentCommission = 0.00;
        if ($seller && $seller->isReseller()) {
            $providerBlockReason = null;
            if (!$seller->canDeliverAccounts($providerBlockReason)) {
                throw new \Exception("⛔ Entrega Bloqueada: {$providerBlockReason}");
            }

            $cost = $plan->reseller_price > 0 ? (float)$plan->reseller_price : (float)$plan->price;
            if (!$seller->hasBalance($cost)) {
                throw new \Exception("⛔ El revendedor no dispone de saldo suficiente (\${$cost}) en su billetera para autorizar esta orden. Saldo actual: \${$seller->balance}.");
            }
            $seller->deductBalance($cost);

            // Acreditar comisión automática del 25% al Proveedor Mayorista patrocinador (Super Reseller)
            // REGLA: El Super Admin Central NO cobra ninguna comisión por servicios ofrecidos por proveedores o revendedores.
            if ($seller->parent && $seller->parent->isSuperReseller()) {
                $pct = (float) ($seller->parent_commission_pct ?: 25.00);
                $parentCommission = round($this->amount * ($pct / 100), 2);
                $seller->parent->addBalance($parentCommission);
            }
        }

        // Asignar y crear venta
        $startDate = now();
        $endDate = now()->addDays($plan->duration_days ?: 30);
        $saleCode = 'CST-' . date('Ymd') . '-' . strtoupper(Str::random(4));

        $sale = Sale::create([
            'sale_code' => $saleCode,
            'user_id' => $this->seller_id,
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
            'sale_price' => $this->amount,
            'cost_price' => $plan->cost_estimate ?: 0,
            'profit' => $this->amount - ($plan->cost_estimate ?: 0),
            'parent_commission_amount' => $parentCommission,
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'status' => 'active',
            'notes' => 'Generada automáticamente desde orden ' . $this->order_code,
        ]);

        // Marcar perfil como asignado
        if ($profile) {
            $profile->update(['status' => 'assigned']);
        }

        // Actualizar orden
        $this->update([
            'payment_status' => 'approved',
            'sale_id' => $sale->id,
            'notes' => ($this->notes ? $this->notes . "\n" : '') . ($notes ?: 'Aprobado y credenciales asignadas exitosamente: ' . $saleCode),
        ]);

        // Cierre de seguridad: ELIMINAR PERMANENTEMENTE EL COMPROBANTE FÍSICO
        $this->deleteReceiptFile();

        // Auditoría
        AuditLog::create([
            'user_id' => auth()->id() ?? $this->seller_id,
            'action' => 'approve_order',
            'description' => "Orden {$this->order_code} aprobada. Venta {$saleCode} generada. Comprobante eliminado del servidor.",
            'ip_address' => request()->ip(),
        ]);

        return $sale;
    }

    /**
     * Rechazar orden y eliminar comprobante
     */
    public function rejectAndClose(?string $reason = null): void
    {
        $this->update([
            'payment_status' => 'rejected',
            'notes' => ($this->notes ? $this->notes . "\n" : '') . ($reason ?: 'Pago rechazado por el operador.'),
        ]);

        $this->deleteReceiptFile();

        AuditLog::create([
            'user_id' => auth()->id() ?? $this->seller_id,
            'action' => 'reject_order',
            'description' => "Orden {$this->order_code} rechazada. Motivo: {$reason}. Archivo de comprobante eliminado.",
            'ip_address' => request()->ip(),
        ]);
    }
}
