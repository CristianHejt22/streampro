<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    /**
     * Webhook de Mercado Pago para aprobación instantánea de pagos
     * POST /api/webhooks/mercadopago/{seller?}
     */
    public function handleMercadoPago(Request $request, $sellerId = null)
    {
        Log::info('MercadoPago Webhook Received:', [
            'query' => $request->query(),
            'body' => $request->all(),
            'seller_id' => $sellerId,
        ]);

        $paymentId = $request->input('data.id') ?? $request->query('id') ?? $request->input('id');
        $type = $request->input('type') ?? $request->query('topic') ?? $request->input('topic');

        // Si es un ping de prueba o no es pago, retornar 200 OK inmediatamente
        if (!$paymentId || ($type && $type !== 'payment')) {
            return response()->json(['status' => 'ignored', 'reason' => 'Not a payment event'], 200);
        }

        // Buscar el vendedor para obtener su Access Token
        $seller = null;
        if ($sellerId) {
            $seller = User::find($sellerId);
        }

        $accessToken = $seller ? $seller->mp_access_token : null;
        if (!$accessToken) {
            $admin = User::where('role', 'admin')->first();
            $accessToken = $admin ? $admin->mp_access_token : null;
        }

        // Consultar API de Mercado Pago si tenemos access token configurado
        $paymentStatus = null;
        $externalReference = null;

        if ($accessToken && !str_contains($accessToken, 'DEMO')) {
            try {
                $response = Http::withToken($accessToken)
                    ->timeout(10)
                    ->get("https://api.mercadopago.com/v1/payments/{$paymentId}");

                if ($response->successful()) {
                    $paymentData = $response->json();
                    $paymentStatus = $paymentData['status'] ?? null;
                    $externalReference = $paymentData['external_reference'] ?? null;
                }
            } catch (\Exception $e) {
                Log::error('Error querying MercadoPago API: ' . $e->getMessage());
            }
        }

        // Si encontramos la orden por external_reference (código de orden) o por payment_reference
        $order = null;
        if ($externalReference) {
            $order = Order::where('order_code', $externalReference)->first();
        }

        if (!$order && $paymentId) {
            $order = Order::where('payment_reference', 'like', "%{$paymentId}%")->first();
        }

        if ($order && ($paymentStatus === 'approved' || empty($accessToken) || str_contains($accessToken, 'DEMO'))) {
            if ($order->payment_status !== 'approved') {
                try {
                    $sale = $order->approveAndClose("Aprobado automáticamente por Webhook de Mercado Pago (Pago ID: {$paymentId})");
                    
                    AuditLog::create([
                        'user_id' => $order->seller_id,
                        'action' => 'mp_webhook_approved',
                        'description' => "Webhook MP acreditó orden {$order->order_code}. Venta {$sale->sale_code} activada y credenciales asignadas.",
                        'ip_address' => $request->ip(),
                    ]);

                    return response()->json([
                        'status' => 'success',
                        'message' => 'Order approved and streaming account assigned',
                        'sale_code' => $sale->sale_code,
                    ], 200);
                } catch (\Exception $e) {
                    Log::error('Error approving order via MP webhook: ' . $e->getMessage());
                    return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
                }
            }
        }

        return response()->json(['status' => 'ok', 'processed' => true], 200);
    }
}
