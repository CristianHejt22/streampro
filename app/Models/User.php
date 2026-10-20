<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role',
        'status',
        'balance',
        'reseller_plan',
        'referral_code',
        'brand_name',
        'brand_logo',
        'brand_favicon',
        'brand_primary_color',
        'brand_accent_color',
        'custom_login_bg',
        'support_whatsapp',
        'support_telegram',
        'mp_public_key',
        'mp_access_token',
        'parent_id',
        'anti_phishing_code',
        'plain_panel_token',
        'panel_type',
        'panel_monthly_fee',
        'panel_expires_at',
        'cloned_store_paid_to_admin',
        'cloned_store_paid_by_reseller',
        'cloned_store_last_payment_date',
        'reseller_commission_pct',
        'parent_commission_pct',
        'bank_cbu',
        'bank_cvu',
        'bank_alias',
        'bank_holder_name',
        'bank_dni',
        'binance_id',
        'bank_instructions',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'mp_access_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'balance' => 'decimal:2',
        'panel_expires_at' => 'datetime',
        'cloned_store_paid_to_admin' => 'boolean',
        'cloned_store_paid_by_reseller' => 'boolean',
        'cloned_store_last_payment_date' => 'datetime',
        'panel_monthly_fee' => 'decimal:2',
        'reseller_commission_pct' => 'decimal:2',
        'parent_commission_pct' => 'decimal:2',
    ];

    public function setPlainPasswordAttribute(?string $val): void
    {
        $this->attributes['plain_panel_token'] = !empty($val) ? \Illuminate\Support\Facades\Crypt::encryptString($val) : null;
    }

    public function getPlainPasswordAttribute(): ?string
    {
        if (empty($this->plain_panel_token)) {
            return null;
        }
        try {
            return \Illuminate\Support\Facades\Crypt::decryptString($this->plain_panel_token);
        } catch (\Throwable $e) {
            return '******';
        }
    }

    public function isPanelCloned(): bool
    {
        return $this->panel_type === 'cloned';
    }

    public function isPanelExpired(): bool
    {
        if (!$this->isPanelCloned()) {
            return false;
        }
        return $this->panel_expires_at && \Carbon\Carbon::parse($this->panel_expires_at)->isPast();
    }

    /**
     * Valida si el usuario o revendedor tiene sus pagos al día para emitir y entregar cuentas.
     * "No se entregará sin monto en billetera o si no se recibe el pago del proveedor."
     */
    public function canDeliverAccounts(&$reason = null): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        if ($this->status !== 'active') {
            $reason = 'El panel del revendedor se encuentra inactivo o suspendido.';
            return false;
        }

        // Si es un panel clonado o depende de un proveedor:
        if ($this->isPanelCloned() || $this->parent_id) {
            // 1. Debe haberse recibido el pago del revendedor al proveedor por la tienda clonada
            if (!$this->cloned_store_paid_by_reseller) {
                $reason = 'No se ha recibido el pago del proveedor. El proveedor tiene pendiente de cobro la suscripción de esta tienda clonada.';
                return false;
            }

            // 2. El proveedor debe haber abonado al Administrador y no estar vencido ($15.000 / 30d)
            if (!$this->cloned_store_paid_to_admin || $this->isPanelExpired()) {
                $reason = 'La suscripción del panel ante el Administrador General no está abonada o se encuentra vencida ($15.000 / 30 días).';
                return false;
            }
        }

        return true;
    }

    public function getDaysRemainingPanelAttribute(): ?int
    {
        if (!$this->panel_expires_at) {
            return null;
        }
        return (int) \Carbon\Carbon::now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($this->panel_expires_at)->startOfDay(), false);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isSuperReseller(): bool
    {
        return $this->role === 'super_reseller';
    }

    public function isReseller(): bool
    {
        return in_array($this->role, ['reseller', 'super_reseller']);
    }

    public function subResellers()
    {
        return $this->hasMany(User::class, 'parent_id');
    }

    public function parent()
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    public function hasBalance(float $amount): bool
    {
        return (float)$this->balance >= $amount;
    }

    public function deductBalance(float $amount): bool
    {
        if ($this->hasBalance($amount)) {
            $this->decrement('balance', $amount);
            return true;
        }
        return false;
    }

    public function addBalance(float $amount): void
    {
        $this->increment('balance', $amount);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function customers()
    {
        return $this->hasMany(Customer::class);
    }

    public function streamingAccounts()
    {
        return $this->hasMany(StreamingAccount::class, 'created_by');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'seller_id');
    }

    public function shortcuts()
    {
        return $this->hasMany(Shortcut::class);
    }

    public function warranties()
    {
        return $this->hasMany(Warranty::class);
    }

    public function renewalRequests()
    {
        return $this->hasMany(RenewalRequest::class, 'user_id');
    }

    public function customServiceCommissions()
    {
        return $this->hasMany(ResellerServiceCommission::class, 'reseller_id');
    }

    public function configuredChildCommissions()
    {
        return $this->hasMany(ResellerServiceCommission::class, 'parent_id');
    }

    public function getCommissionForService($serviceId): float
    {
        $custom = ResellerServiceCommission::where('reseller_id', $this->id)
            ->where('service_id', $serviceId)
            ->value('commission_pct');

        return $custom !== null ? (float)$custom : (float)($this->reseller_commission_pct ?: 40.00);
    }
}
