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
        'renewal_count' => 'integer',
        'reminder_sent_at' => 'datetime',
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

    public function getDaysRemainingAttribute(): int
    {
        $today = Carbon::now()->startOfDay();
        $end = Carbon::parse($this->end_date)->startOfDay();
        return (int) $today->diffInDays($end, false);
    }

    public function getCalculatedStatusAttribute(): string
    {
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
     * Genera el texto formateado de entrega para WhatsApp
     */
    public function getWhatsAppDeliveryMessageAttribute(): string
    {
        $brand = $this->user->brand_name ?: config('app.name', 'CStreame');
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
}
