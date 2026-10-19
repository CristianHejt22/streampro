<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Sale;
use App\Models\StreamingAccount;
use App\Models\Order;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class CStreameCronCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cstreame:cron {--clean-receipts : Forzar limpieza de comprobantes antiguos}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Tarea automatizada para cPanel: actualiza cuentas y ventas vencidas, y purga comprobantes temporales';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('====================================================');
        $this->info('  CSTREAME - Cron Job de Control de Streaming');
        $this->info('====================================================');

        $today = Carbon::today()->toDateString();
        $in3Days = Carbon::today()->addDays(3)->toDateString();

        // 1. Detectar ventas expiradas
        $expiredSales = Sale::where('status', 'active')
            ->where('end_date', '<', $today)
            ->get();

        $expiredCount = 0;
        foreach ($expiredSales as $sale) {
            $sale->update(['status' => 'expired']);
            $expiredCount++;
        }
        $this->info("[✓] Ventas vencidas actualizadas a 'expired': {$expiredCount}");

        // 2. Detectar ventas por vencer pronto (<= 3 días)
        $expiringSales = Sale::where('status', 'active')
            ->where('end_date', '>=', $today)
            ->where('end_date', '<=', $in3Days)
            ->update(['status' => 'expiring_soon']);

        $this->info("[✓] Ventas marcadas 'expiring_soon' (<= 3 días): {$expiringSales}");

        // 3. Detectar cuentas de proveedores vencidas
        $expiredMasterAccounts = StreamingAccount::where('status', 'active')
            ->whereNotNull('supplier_expiration_date')
            ->where('supplier_expiration_date', '<', $today)
            ->update(['status' => 'expired']);

        $this->info("[✓] Cuentas maestras de proveedor vencidas: {$expiredMasterAccounts}");

        // 4. Limpieza de comprobantes temporales huérfanos o con más de 48h
        $purgedReceiptsCount = 0;
        $files = Storage::disk('local')->files('receipts_temp');
        $retentionLimit = Carbon::now()->subHours(48)->getTimestamp();

        foreach ($files as $file) {
            $lastModified = Storage::disk('local')->lastModified($file);
            $order = Order::where('receipt_path', $file)->first();

            if (!$order || $order->payment_status !== 'pending' || $lastModified < $retentionLimit) {
                Storage::disk('local')->delete($file);
                $purgedReceiptsCount++;

                if ($order && $order->payment_status !== 'pending') {
                    $order->update(['receipt_path' => null]);
                }
            }
        }

        $this->info("[✓] Comprobantes temporales purgados de Mini Nube: {$purgedReceiptsCount}");

        // 5. Auditoría del sistema
        AuditLog::create([
            'user_id' => null,
            'action' => 'cron_maintenance',
            'description' => "Cron ejecutado. Ventas expiradas: {$expiredCount}. Cuentas proveedor vencidas: {$expiredMasterAccounts}. Archivos purgados: {$purgedReceiptsCount}.",
            'ip_address' => '127.0.0.1 (CLI)',
        ]);

        $this->info('====================================================');
        $this->info('  Mantenimiento finalizado exitosamente.');
        $this->info('====================================================');

        return Command::SUCCESS;
    }
}
