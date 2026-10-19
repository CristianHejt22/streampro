<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\WebhookController;

/*
|--------------------------------------------------------------------------
| API v1 - Health check y Endpoints
|--------------------------------------------------------------------------
*/
Route::prefix('v1')->group(function () {
    Route::get('/ping', function () {
        return response()->json([
            'status' => 'ok',
            'system' => 'CStreame Streaming Accounts Platform',
            'version' => '1.0.0',
            'timestamp' => now()->toIso8601String(),
        ]);
    })->name('api.v1.ping');
});

/*
|--------------------------------------------------------------------------
| Webhooks de Pasarelas de Pago (Mercado Pago Multi-Usuario)
|--------------------------------------------------------------------------
*/
Route::post('/webhooks/mercadopago/{seller?}', [WebhookController::class, 'handleMercadoPago'])->name('api.webhooks.mercadopago');
Route::match(['get', 'post'], '/mercadopago/ipn', [WebhookController::class, 'handleMercadoPago'])->name('api.webhooks.mp_ipn');
