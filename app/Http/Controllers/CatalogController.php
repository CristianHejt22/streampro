<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Service;
use App\Models\ServicePlan;
use App\Models\User;
use App\Models\Order;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CatalogController extends Controller
{
    public function index(Request $request)
    {
        $refCode = $request->query('ref');
        $seller = null;

        if ($refCode) {
            $seller = User::where('referral_code', $refCode)->where('status', 'active')->first();
        }

        if (!$seller) {
            $seller = User::where('role', 'admin')->first();
        }

        $query = Service::where('status', 'active')->with(['plans' => function ($q) {
            $q->where('is_active', true);
        }]);

        // Si es revendedor, filtrar catálogo: servicios propios + servicios de su proveedor + del sistema
        if ($seller && $seller->isReseller()) {
            $query->where(function ($q) use ($seller) {
                $q->where('user_id', $seller->id)
                  ->orWhereNull('user_id');
                if ($seller->parent_id) {
                    $q->orWhere('user_id', $seller->parent_id);
                }
            });

            // Si su panel es Básico, límite estricto de 10 servicios
            if ($seller->isPanelBasic()) {
                $query->take(10);
            }
        }

        if ($request->filled('category')) {
            $query->where('category', $request->query('category'));
        }

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $services = $query->latest()->get();
        $categories = Service::where('status', 'active')->distinct()->pluck('category');

        $brandName = $seller->brand_name ?: ($seller->isAdmin() ? 'CStreame Master Platform' : $seller->name);
        $brandLogo = $seller->brand_logo;
        $primaryColor = $seller->brand_primary_color ?: '#E50914';

        // Regla de WhatsApp: si es copia, usa su revendedor, salvo que el proveedor lo desee
        $whatsappSupportPhone = $seller->support_whatsapp;
        if ($seller && $seller->isReseller() && $seller->parent_id && $seller->parent) {
            if ($seller->parent->provider_override_support_whatsapp && !empty($seller->parent->support_whatsapp)) {
                $whatsappSupportPhone = $seller->parent->support_whatsapp;
            }
        }
        if (empty($whatsappSupportPhone)) {
            $whatsappSupportPhone = SystemSetting::get('whatsapp_support_admin_phone', '5491199887766');
        }

        $supportWhatsapp = $whatsappSupportPhone;
        $supportTelegram = $seller->support_telegram;

        return view('catalog.index', compact(
            'services',
            'categories',
            'seller',
            'brandName',
            'brandLogo',
            'primaryColor',
            'supportWhatsapp',
            'supportTelegram'
        ));
    }

    public function checkout(Request $request, ServicePlan $plan)
    {
        $sellerId = $request->input('seller_id');
        $seller = User::find($sellerId) ?? User::where('role', 'admin')->first();

        $plan->load('service');

        return view('catalog.checkout', compact('plan', 'seller'));
    }

    public function processCheckout(Request $request, ServicePlan $plan)
    {
        $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:50',
            'customer_email' => 'nullable|email|max:255',
            'payment_method' => 'required|in:bank_transfer,mercadopago,binance',
            'receipt' => 'nullable|file|mimes:jpeg,png,jpg,pdf,webp,svg|max:10240',
            'seller_id' => 'required|exists:users,id',
        ]);

        $seller = User::findOrFail($request->seller_id);

        $order = new Order();
        $order->seller_id = $seller->id;
        $order->service_plan_id = $plan->id;
        $order->customer_name = $request->customer_name;
        $order->customer_phone = $request->customer_phone;
        $order->customer_email = $request->customer_email;
        $order->amount = $plan->price;
        $order->payment_method = $request->payment_method;
        $order->payment_status = 'pending';

        if ($request->payment_method === 'bank_transfer' || $request->payment_method === 'binance') {
            if ($request->hasFile('receipt')) {
                $file = $request->file('receipt');
                $filename = 'recibo_' . time() . '_' . Str::random(6) . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('receipts_temp', $filename, 'local');
                $order->receipt_path = $path;
            }

            $order->notes = 'Comprobante cargado por el cliente desde el catálogo. Listo para auditoría.';
            $order->save();

            return redirect()->route('catalog.success', ['order' => $order->order_code])
                ->with('success', '¡Comprobante recibido con éxito! En breve auditaremos el pago y recibirás tus credenciales.');
        } else {
            // Mercado Pago
            $order->save();
            return redirect()->route('catalog.mp_redirect', ['order' => $order->order_code]);
        }
    }

    public function mpRedirect($orderCode)
    {
        $order = Order::with(['seller', 'servicePlan.service'])->where('order_code', $orderCode)->firstOrFail();
        return view('catalog.mercadopago', compact('order'));
    }

    public function mpSimulatePayment(Request $request, $orderCode)
    {
        $order = Order::with(['seller', 'servicePlan.service'])->where('order_code', $orderCode)->firstOrFail();

        try {
            $sale = $order->approveAndClose('Aprobado automáticamente mediante pasarela Mercado Pago.');
            return redirect()->route('catalog.success', ['order' => $order->order_code])
                ->with('success', '¡Pago acreditado por Mercado Pago! Aquí tienes tus credenciales de streaming.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function success($orderCode)
    {
        $order = Order::with(['seller', 'servicePlan.service', 'sale'])->where('order_code', $orderCode)->firstOrFail();
        return view('catalog.success', compact('order'));
    }
}
