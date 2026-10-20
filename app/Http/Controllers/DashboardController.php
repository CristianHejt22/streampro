<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Sale;
use App\Models\StreamingAccount;
use App\Models\AccountProfile;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Service;
use App\Models\User;
use App\Models\RenewalRequest;
use App\Models\BalanceTransfer;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if ($user->isAdmin()) {
            return $this->adminDashboard();
        } else {
            return $this->resellerDashboard($user);
        }
    }

    private function adminDashboard()
    {
        $today = Carbon::today();

        // Métricas Globales
        $totalSalesCount = Sale::count();
        $totalRevenue = Sale::sum('sale_price');
        $totalProfit = Sale::sum('profit');
        $activeSalesCount = Sale::where('end_date', '>=', $today->toDateString())->where('status', '!=', 'cancelled')->count();
        
        // Ventas por vencer en los próximos 3 días
        $expiringSoonCount = Sale::where('end_date', '>=', $today->toDateString())
            ->where('end_date', '<=', $today->copy()->addDays(3)->toDateString())
            ->where('status', '!=', 'cancelled')
            ->count();

        // Ventas ya vencidas
        $expiredCount = Sale::where('end_date', '<', $today->toDateString())
            ->where('status', '!=', 'cancelled')
            ->count();

        // Órdenes pendientes de auditoría en la Mini Nube
        $pendingOrdersCount = Order::where('payment_status', 'pending')->count();

        // Clientes registrados
        $totalCustomersCount = Customer::count();

        // Stock disponible
        $availableProfilesCount = AccountProfile::where('status', 'available')->whereHas('streamingAccount', function ($q) {
            $q->where('status', 'active');
        })->count();

        $availableFullAccountsCount = StreamingAccount::where('modality', 'full_account')->where('status', 'active')->count();

        // Stock desglosado por servicio
        $servicesStock = Service::withCount([
            'streamingAccounts as total_accounts' => function ($q) {
                $q->where('status', 'active');
            }
        ])->get()->map(function ($s) {
            $s->available_profiles = AccountProfile::whereHas('streamingAccount', function ($q) use ($s) {
                $q->where('service_id', $s->id)->where('status', 'active');
            })->where('status', 'available')->count();
            return $s;
        });

        // Ventas recientes
        $recentSales = Sale::with(['customer', 'service', 'profile', 'user'])
            ->latest()
            ->take(8)
            ->get();

        // Ventas que vencen pronto (Alerta de cobro)
        $expiringSales = Sale::with(['customer', 'service', 'profile', 'user'])
            ->where('end_date', '>=', $today->toDateString())
            ->where('end_date', '<=', $today->copy()->addDays(3)->toDateString())
            ->where('status', '!=', 'cancelled')
            ->orderBy('end_date', 'asc')
            ->take(5)
            ->get();

        // Gráfico de Ventas de los últimos 7 días
        $chartLabels = [];
        $chartValues = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $chartLabels[] = $date->format('d/m');
            $salesAmount = Sale::whereDate('start_date', $date->toDateString())->sum('sale_price');
            $chartValues[] = (float) $salesAmount;
        }

        // Cuentas en Consignación activas pendientes de pago (48hs)
        $consignmentsActive = Sale::where('is_consignment', true)
            ->where('is_consignment_paid', false)
            ->where('status', 'active')
            ->with(['customer', 'service', 'user'])
            ->latest()
            ->get();

        // Solicitudes de renovación pendientes desde Bóveda de Clientes
        $pendingRenewalsCount = RenewalRequest::where('status', 'pending')->count();

        // Paneles clonados activos y recaudación mensual
        $clonedPanelsCount = User::where('panel_type', 'cloned')->where('status', 'active')->count();
        $clonedPanelsRevenue = $clonedPanelsCount * 15000.00;

        return view('dashboard.admin', compact(
            'totalSalesCount',
            'totalRevenue',
            'totalProfit',
            'activeSalesCount',
            'expiringSoonCount',
            'expiredCount',
            'pendingOrdersCount',
            'totalCustomersCount',
            'availableProfilesCount',
            'availableFullAccountsCount',
            'servicesStock',
            'recentSales',
            'expiringSales',
            'chartLabels',
            'chartValues',
            'consignmentsActive',
            'pendingRenewalsCount',
            'clonedPanelsCount',
            'clonedPanelsRevenue'
        ));
    }

    private function resellerDashboard(User $user)
    {
        $today = Carbon::today();

        $mySalesCount = Sale::where('user_id', $user->id)->count();
        $myActiveSales = Sale::where('user_id', $user->id)
            ->where('end_date', '>=', $today->toDateString())
            ->where('status', '!=', 'cancelled')
            ->count();

        $myExpiringSoon = Sale::where('user_id', $user->id)
            ->where('end_date', '>=', $today->toDateString())
            ->where('end_date', '<=', $today->copy()->addDays(3)->toDateString())
            ->where('status', '!=', 'cancelled')
            ->count();

        $myCustomersCount = Customer::where('user_id', $user->id)->count();

        // Ventas recientes del revendedor
        $recentSales = Sale::with(['customer', 'service', 'profile'])
            ->where('user_id', $user->id)
            ->latest()
            ->take(8)
            ->get();

        // Cuentas por vencer del revendedor
        $expiringSales = Sale::with(['customer', 'service', 'profile'])
            ->where('user_id', $user->id)
            ->where('end_date', '>=', $today->toDateString())
            ->where('end_date', '<=', $today->copy()->addDays(3)->toDateString())
            ->where('status', '!=', 'cancelled')
            ->orderBy('end_date', 'asc')
            ->take(5)
            ->get();

        // Stock disponible general que puede vender
        $services = Service::where('status', 'active')->with('plans')->get()->map(function ($s) {
            $s->available_profiles = AccountProfile::whereHas('streamingAccount', function ($q) use ($s) {
                $q->where('service_id', $s->id)->where('status', 'active');
            })->where('status', 'available')->count();
            return $s;
        });

        // Gráfico de ventas de 7 días del revendedor
        $chartLabels = [];
        $chartValues = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $chartLabels[] = $date->format('d/m');
            $salesAmount = Sale::where('user_id', $user->id)->whereDate('start_date', $date->toDateString())->sum('sale_price');
            $chartValues[] = (float) $salesAmount;
        }

        // Cuentas en Consignación del revendedor pendientes de liquidar (48hs)
        $consignmentsActive = Sale::where('user_id', $user->id)
            ->where('is_consignment', true)
            ->where('is_consignment_paid', false)
            ->where('status', 'active')
            ->with(['customer', 'service'])
            ->latest()
            ->get();

        // Solicitudes de renovación pendientes de sus clientes
        $pendingRenewalsCount = RenewalRequest::where('user_id', $user->id)->where('status', 'pending')->count();

        // Transferencias de saldo enviadas al proveedor
        $myTransfers = BalanceTransfer::where('reseller_id', $user->id)->latest()->take(5)->get();

        // Estadísticas de Proveedor (si es Super Revendedor)
        $subResellersCount = User::where('parent_id', $user->id)->count();
        $clonedPanelsCount = User::where('parent_id', $user->id)->where('panel_type', 'cloned')->count();
        $sponsorshipEarnings = (float) Sale::whereHas('user', function($q) use ($user) {
            $q->where('parent_id', $user->id);
        })->sum('parent_commission_amount');

        return view('dashboard.reseller', compact(
            'user',
            'mySalesCount',
            'myActiveSales',
            'myExpiringSoon',
            'myCustomersCount',
            'recentSales',
            'expiringSales',
            'services',
            'chartLabels',
            'chartValues',
            'consignmentsActive',
            'pendingRenewalsCount',
            'myTransfers',
            'subResellersCount',
            'clonedPanelsCount',
            'sponsorshipEarnings'
        ));
    }
}
