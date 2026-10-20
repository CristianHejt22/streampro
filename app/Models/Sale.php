<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_code',
        'user_id',
        'customer_id',
        'service_id',
        'service_plan_id',
        'streaming_account_id',
        'account_profile_id',
        'modality',
        'account_email_snapshot',
        'account_password_snapshot',
        'profile_name_snapshot',
        'profile_pin_snapshot',
        'usage_rules_snapshot',
        'sale_price',
        'cost_price',
        'profit',
        'start_date',
        'end_date',
        'months_duration',
        'is_consignment',
        'is_consignment_paid',
        'consignment_expires_at',
        'parent_commission_amount',
        'status',
        'renewal_count',
        'reminder_sent_at',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'sale_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'profit' => 'decimal:2',
        'parent_commission_amount' => 'decimal:2',
        'renewal_count' => 'integer',
        'reminder_sent_at' => 'datetime',
        'is_consignment' => 'boolean',
        'is_consignment_paid' => 'boolean',
        'consignment_expires_at' => 'datetime',
        'months_duration' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function plan()
    {
        return $this->belongsTo(ServicePlan::class, 'service_plan_id');
    }

    public function servicePlan()
    {
        return $this->belongsTo(ServicePlan::class, 'service_plan_id');
    }

    public function streamingAccount()
    {
        return $this->belongsTo(StreamingAccount::class);
    }

    public function profile()
    {
        return $this->belongsTo(AccountProfile::class, 'account_profile_id');
    }

    public function warranties()
    {
        return $this->hasMany(Warranty::class);
    }

    public function setAccountPasswordSnapshotAttribute($value)
    {
        $this->attributes['account_password_snapshot'] = !empty($value) ? \Illuminate\Support\Facades\Crypt::encryptString($value) : $value;
    }

    public function getAccountPasswordSnapshotAttribute($value)
    {
        if (empty($value)) {
            return $value;
        }
        try {
            return \Illuminate\Support\Facades\Crypt::decryptString($value);
        } catch (\Throwable $e) {
            return $value;
        }
    }

    public function isConsignment(): bool
    {
        return (bool) $this->is_consignment;
    }

    public function isConsignmentExpired(): bool
    {
        if (!$this->is_consignment || $this->is_consignment_paid) {
            return false;
        }
        return $this->consignment_expires_at && \Carbon\Carbon::parse($this->consignment_expires_at)->isPast();
    }

    /**
     * Regla de Entrega Estricta:
     * "No se entregará sin monto en billetera o si no se recibe el pago del proveedor."
     * Determina si las credenciales (correo, contraseña, perfil, PIN) pueden ser entregadas/visibles al cliente.
     */
    public function canDeliverCredentials(&$blockReason = null): bool
    {
        // 1. Si la cuenta fue emitida en consignación y aún no ha sido pagada/liquidada con saldo en billetera
        if ($this->is_consignment && !$this->is_consignment_paid) {
            $blockReason = 'Entrega retenida: Esta cuenta fue emitida en consignación y aún no cuenta con el costo mayorista liquidado desde la billetera.';
            return false;
        }

        // 2. Si la cuenta cayó por vencimiento del plazo de consignación (48hs)
        if ($this->isConsignmentExpired() || $this->status === 'cancelled') {
            $blockReason = 'Entrega cancelada: El plazo de consignación de 48 horas venció sin registrarse el pago de la cuenta.';
            return false;
        }

        // 3. Si el estado de la venta no es activo
        if (!in_array($this->status, ['active', 'expiring_soon'])) {
            $blockReason = 'La suscripción no se encuentra activa.';
            return false;
        }

        // 4. Si el revendedor emisor tiene panel clonado y no se ha recibido el pago del proveedor/administrador
        $providerReason = null;
        if ($this->user && !$this->user->canDeliverAccounts($providerReason)) {
            $blockReason = 'Entrega retenida por falta de recepción de pago del proveedor: ' . $providerReason;
            return false;
        }

        return true;
    }

    public function getConsignmentHoursRemainingAttribute(): ?int
    {
        if (!$this->is_consignment || $this->is_consignment_paid || !$this->consignment_expires_at) {
            return null;
        }
        return (int) \Carbon\Carbon::now()->diffInHours(\Carbon\Carbon::parse($this->consignment_expires_at), false);
    }

    public function getDaysRemainingAttribute(): int
    {
        $today = Carbon::now()->startOfDay();
        $end = Carbon::parse($this->end_date)->startOfDay();
        return (int) $today->diffInDays($end, false);
    }

    public function getCalculatedStatusAttribute(): string
    {
        if ($this->isConsignmentExpired()) {
            return 'consignment_dropped';
        }

        if ($this->is_consignment && !$this->is_consignment_paid) {
            return 'consignment_pending';
        }

        if (in_array($this->status, ['renewed', 'replaced', 'cancelled'])) {
            return $this->status;
        }

        $days = $this->days_remaining;
        if ($days < 0) {
            return 'expired';
        } elseif ($days <= 3) {
            return 'expiring_soon';
        }
        return 'active';
    }

    /**
     * Devuelve la etiqueta y estilo visual según el estado calculado
     */
    public function getStatusBadgeAttribute(): array
    {
        $status = $this->calculated_status;
        switch ($status) {
            case 'consignment_pending':
                $hours = $this->consignment_hours_remaining ?? 0;
                return [
                    'label' => "Consignación ({$hours}h para liquidar)",
                    'class' => 'bg-amber-500/10 text-amber-400 border-amber-500/20 animate-pulse',
                    'icon' => 'clock',
                ];
            case 'consignment_dropped':
                return [
                    'label' => 'Caída (48h Consignación Vencida)',
                    'class' => 'bg-rose-500/10 text-rose-400 border-rose-500/20 font-bold',
                    'icon' => 'x-circle',
                ];
            case 'active':
                return [
                    'label' => 'Activa (Garantía OK)',
                    'class' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
                    'icon' => 'check-circle',
                ];
            case 'expiring_soon':
                return [
                    'label' => 'Por Vencer (<= 3 días)',
                    'class' => 'bg-amber-500/10 text-amber-400 border-amber-500/20 animate-pulse',
                    'icon' => 'clock',
                ];
            case 'expired':
                return [
                    'label' => 'Vencida',
                    'class' => 'bg-rose-500/10 text-rose-400 border-rose-500/20',
                    'icon' => 'alert-triangle',
                ];
            case 'renewed':
                return [
                    'label' => 'Renovada',
                    'class' => 'bg-sky-500/10 text-sky-400 border-sky-500/20',
                    'icon' => 'refresh-cw',
                ];
            case 'replaced':
                return [
                    'label' => 'Reemplazada (Garantía)',
                    'class' => 'bg-purple-500/10 text-purple-400 border-purple-500/20',
                    'icon' => 'shield-check',
                ];
            default:
                return [
                    'label' => ucfirst($this->status ?: 'Activa'),
                    'class' => 'bg-slate-500/10 text-slate-400 border-slate-500/20',
                    'icon' => 'help-circle',
                ];
        }
    }

    /**
     * Genera el texto formateado de entrega para WhatsApp
     */
    public function getWhatsAppDeliveryMessageAttribute(): string
    {
        $blockReason = null;
        if (!$this->canDeliverCredentials($blockReason)) {
            $brand = $this->user ? ($this->user->brand_name ?: config('app.name', 'CStreame')) : config('app.name', 'CStreame');
            $serviceName = $this->service ? $this->service->name : 'Streaming';
            return "⏳ *AVISO DE ESTADO DE CUENTA - {$serviceName}* ⏳\n"
                . "De parte de *{$brand}*.\n\n"
                . "Tu suscripción se encuentra registrada pero *las credenciales de acceso están retenidas*:\n"
                . "🔒 *Motivo:* {$blockReason}\n\n"
                . "Las credenciales oficiales serán liberadas y entregadas en tu Bóveda una vez confirmado el saldo o el pago con el proveedor. 💬";
        }

        $brand = $this->user ? ($this->user->brand_name ?: config('app.name', 'CStreame')) : config('app.name', 'CStreame');
        $serviceName = $this->service ? $this->service->name : 'Streaming';
        $vence = Carbon::parse($this->end_date)->format('d/m/Y');

        $msg = "🍿 *¡TU CUENTA DE {$serviceName} ESTÁ LISTA!* 🍿\n";
        $msg .= "De parte de *{$brand}*.\n\n";
        $msg .= "👤 *Usuario / Correo:* `{$this->account_email_snapshot}`\n";
        $msg .= "🔑 *Contraseña:* `{$this->account_password_snapshot}`\n";

        if ($this->modality === 'profile') {
            $perfil = $this->profile_name_snapshot ?: 'Perfil Asignado';
            $msg .= "📺 *Perfil:* *{$perfil}*\n";
            if ($this->profile_pin_snapshot) {
                $msg .= "🔢 *PIN de Perfil:* `{$this->profile_pin_snapshot}`\n";
            }
        } else {
            $msg .= "📺 *Modalidad:* Cuenta Completa (Todos los perfiles)\n";
        }

        $msg .= "📅 *Vencimiento:* {$vence}\n\n";

        if ($this->usage_rules_snapshot) {
            $msg .= "⚠️ *REGLAS DE USO OBLIGATORIAS:*\n{$this->usage_rules_snapshot}\n\n";
        } else {
            $msg .= "⚠️ *REGLAS DE USO:*\n- No modificar correo ni contraseña.\n- Usar únicamente tu perfil asignado.\n- Prohibido agregar PIN a otros perfiles.\n\n";
        }

        $msg .= "💬 Cualquier consulta responde a este mensaje. ¡Que disfrutes tu contenido! ✨";

        return $msg;
    }

    /**
     * Genera el texto de recordatorio de cobro / vencimiento
     */
    public function getWhatsAppReminderMessageAttribute(): string
    {
        $brand = $this->user->brand_name ?: config('app.name', 'CStreame');
        $serviceName = $this->service ? $this->service->name : 'Streaming';
        $vence = Carbon::parse($this->end_date)->format('d/m/Y');
        $days = $this->days_remaining;

        $tiempo = $days === 0 ? "¡VENCE HOY!" : ($days < 0 ? "¡HA VENCIDO!" : "vence en {$days} días ({$vence})");

        $msg = "⏰ *AVISO DE RENOVACIÓN - {$serviceName}* ⏰\n\n";
        $msg .= "Hola *{$this->customer->name}*, te recordamos de *{$brand}* que tu cuenta de *{$serviceName}* {$tiempo}.\n\n";
        $msg .= "💰 *Monto de renovación:* $" . number_format($this->sale_price, 2) . "\n\n";
        $msg .= "¿Deseas renovar tu servicio para continuar sin cortes? Responde *RENOVAR* para enviarte los datos de pago. 💳✨";

        return $msg;
    }

    public function getWhatsAppRenewalMessageAttribute(): string
    {
        return $this->whats_app_reminder_message;
    }

    public function renewalRequests()
    {
        return $this->hasMany(RenewalRequest::class);
    }

    public function latestRenewalRequest()
    {
        return $this->hasOne(RenewalRequest::class)->latestOfMany();
    }
}
