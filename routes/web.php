<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\StreamingAccountController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\WarrantyController;
use App\Http\Controllers\ResellerController;
use App\Http\Controllers\TreasuryController;
use App\Http\Controllers\ShortcutController;
use App\Http\Controllers\WhiteLabelController;
use App\Http\Controllers\InstallationController;

/*
|--------------------------------------------------------------------------
| Public Routes (Catálogo Digital & Checkout Frontend)
|--------------------------------------------------------------------------
*/
Route::get('/', [CatalogController::class, 'index'])->name('home');
Route::get('/catalogo', [CatalogController::class, 'index'])->name('catalog.index');
Route::get('/ref/{code}', function ($code) {
    return redirect()->route('catalog.index', ['ref' => $code]);
})->name('catalog.referral');

Route::get('/checkout/{plan}', [CatalogController::class, 'checkout'])->name('catalog.checkout');
Route::post('/checkout/{plan}', [CatalogController::class, 'processCheckout'])->name('catalog.checkout.process');
Route::get('/checkout/mercadopago/{order}', [CatalogController::class, 'mpRedirect'])->name('catalog.mp_redirect');
Route::post('/checkout/mercadopago/{order}/simulate', [CatalogController::class, 'mpSimulatePayment'])->name('catalog.mp_simulate');
Route::get('/orden/{order}/exito', [CatalogController::class, 'success'])->name('catalog.success');

/*
|--------------------------------------------------------------------------
| Portal del Cliente & Bóveda de Streaming (Registro & Login por Correo y Contraseña)
|--------------------------------------------------------------------------
*/
Route::get('/registro', [\App\Http\Controllers\CustomerPortalController::class, 'showRegister'])->name('portal.register');
Route::post('/registro', [\App\Http\Controllers\CustomerPortalController::class, 'processRegister'])->name('portal.register.process');
Route::get('/ref/{code}/registro', [\App\Http\Controllers\CustomerPortalController::class, 'showRegister'])->name('portal.register.ref');

Route::get('/boveda/login', [\App\Http\Controllers\CustomerPortalController::class, 'showLogin'])->name('boveda.login');
Route::post('/boveda/login', [\App\Http\Controllers\CustomerPortalController::class, 'processLogin'])->name('boveda.login.process');
Route::post('/boveda/logout', [\App\Http\Controllers\CustomerPortalController::class, 'logout'])->name('boveda.logout');

Route::middleware(['auth:customer'])->group(function () {
    Route::get('/boveda', [\App\Http\Controllers\CustomerPortalController::class, 'index'])->name('boveda.index');
    Route::post('/boveda/report-issue/{sale}', [\App\Http\Controllers\CustomerPortalController::class, 'reportIssue'])->name('boveda.report');
    Route::post('/boveda/verify-unlock', [\App\Http\Controllers\CustomerPortalController::class, 'verifyUnlock'])->name('boveda.verify_unlock');
});

/*
|--------------------------------------------------------------------------
| Installation & Wizard Routes
|--------------------------------------------------------------------------
*/
Route::get('/install', [InstallationController::class, 'index'])->name('install.index');
Route::post('/install/test-db', [InstallationController::class, 'testDatabase'])->name('install.test_db');
Route::post('/install/verify-license', [InstallationController::class, 'verifyLicense'])->name('install.verify_license');
Route::post('/install/process', [InstallationController::class, 'process'])->name('install.process');

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

/*
|--------------------------------------------------------------------------
| Protected Panel Routes (Admin & Revendedores)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Ventas & CRM de Cuentas Streaming (Usuario, Contraseña, Perfiles, Vencimiento, WhatsApp)
    Route::get('/sales', [SaleController::class, 'index'])->name('sales.index');
    Route::get('/sales/export', [SaleController::class, 'exportCsv'])->name('sales.export');
    Route::post('/sales', [SaleController::class, 'store'])->name('sales.store');
    Route::post('/sales/{sale}/renew', [SaleController::class, 'renew'])->name('sales.renew');
    Route::post('/sales/{sale}/pay-consignment', [SaleController::class, 'payConsignment'])->name('sales.pay_consignment');
    Route::post('/sales/{sale}/warranty', [SaleController::class, 'replaceWarranty'])->name('sales.warranty');
    Route::post('/sales/{sale}/reminder-sent', [SaleController::class, 'markReminderSent'])->name('sales.reminder_sent');
    Route::delete('/sales/{sale}', [SaleController::class, 'destroy'])->name('sales.destroy');

    // Inventario de Cuentas Maestras y Perfiles
    Route::get('/accounts', [StreamingAccountController::class, 'index'])->name('accounts.index');
    Route::post('/accounts', [StreamingAccountController::class, 'store'])->name('accounts.store');
    Route::post('/accounts/quick-replenish', [StreamingAccountController::class, 'quickReplenish'])->name('accounts.quick_replenish');
    Route::post('/accounts/{account}/private-password', [StreamingAccountController::class, 'updatePrivatePassword'])->name('accounts.private_password');
    Route::put('/accounts/{account}', [StreamingAccountController::class, 'update'])->name('accounts.update');
    Route::post('/accounts/{account}/toggle', [StreamingAccountController::class, 'toggleStatus'])->name('accounts.toggle');
    Route::delete('/accounts/{account}', [StreamingAccountController::class, 'destroy'])->name('accounts.destroy');

    // Plataformas y Servicios de Streaming con sus Planes
    Route::get('/services', [ServiceController::class, 'index'])->name('services.index');
    Route::post('/services', [ServiceController::class, 'store'])->name('services.store');
    Route::put('/services/{service}', [ServiceController::class, 'update'])->name('services.update');
    Route::delete('/services/{service}', [ServiceController::class, 'destroy'])->name('services.destroy');
    Route::post('/services/{service}/plans', [ServiceController::class, 'storePlan'])->name('services.plans.store');
    Route::post('/services/plans/{plan}/toggle', [ServiceController::class, 'togglePlan'])->name('services.plans.toggle');

    // Agenda de Clientes CRM
    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');
    Route::post('/customers/{customer}/activate', [CustomerController::class, 'activateAccount'])->name('customers.activate');
    Route::post('/customers/{customer}/reset-password', [CustomerController::class, 'resetPassword'])->name('customers.reset_password');
    Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
    Route::put('/customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
    Route::delete('/customers/{customer}', [CustomerController::class, 'destroy'])->name('customers.destroy');

    // Proveedores de Cuentas
    Route::get('/suppliers', [SupplierController::class, 'index'])->name('suppliers.index');
    Route::post('/suppliers', [SupplierController::class, 'store'])->name('suppliers.store');
    Route::put('/suppliers/{supplier}', [SupplierController::class, 'update'])->name('suppliers.update');
    Route::delete('/suppliers/{supplier}', [SupplierController::class, 'destroy'])->name('suppliers.destroy');

    // Garantías & Caídas
    Route::get('/warranties', [WarrantyController::class, 'index'])->name('warranties.index');
    Route::post('/warranties', [WarrantyController::class, 'store'])->name('warranties.store');
    Route::post('/warranties/{warranty}/resolve', [WarrantyController::class, 'resolve'])->name('warranties.resolve');

    // Generador de Revendedores & Billetera de Saldo (Solo Admin)
    Route::get('/resellers', [ResellerController::class, 'index'])->name('resellers.index');
    Route::post('/resellers', [ResellerController::class, 'store'])->name('resellers.store');
    Route::post('/resellers/{reseller}/recharge', [ResellerController::class, 'rechargeBalance'])->name('resellers.recharge');
    Route::post('/resellers/{reseller}/toggle', [ResellerController::class, 'toggleStatus'])->name('resellers.toggle');
    Route::get('/resellers/{reseller}/reveal-password', [ResellerController::class, 'revealPassword'])->name('resellers.reveal_password');
    Route::post('/resellers/{reseller}/update-password', [ResellerController::class, 'updatePassword'])->name('resellers.update_password');
    Route::post('/resellers/{reseller}/clone-panel', [ResellerController::class, 'clonePanel'])->name('resellers.clone_panel');
    Route::post('/resellers/{reseller}/update-commissions', [ResellerController::class, 'updateCommissions'])->name('resellers.update_commissions');
    Route::post('/resellers/{reseller}/reset-password', [ResellerController::class, 'resetPassword'])->name('resellers.reset_password');

    // Módulo de Tesorería Multi-Usuario & Mini Nube de Comprobantes
    Route::get('/treasury', [TreasuryController::class, 'index'])->name('treasury.index');
    Route::get('/treasury/export', [TreasuryController::class, 'exportCsv'])->name('treasury.export');
    Route::get('/treasury/settings', [TreasuryController::class, 'settings'])->name('treasury.settings');
    Route::post('/treasury/settings', [TreasuryController::class, 'updateSettings'])->name('treasury.settings.update');
    Route::get('/treasury/receipts/{order}', [TreasuryController::class, 'viewReceipt'])->name('treasury.receipt');
    Route::post('/treasury/orders/{order}/approve', [TreasuryController::class, 'approveAndClose'])->name('treasury.approve');
    Route::post('/treasury/orders/{order}/reject', [TreasuryController::class, 'rejectAndClose'])->name('treasury.reject');

    // Gestor de Atajos (Dock rápido)
    Route::get('/shortcuts', [ShortcutController::class, 'index'])->name('shortcuts.index');
    Route::post('/shortcuts', [ShortcutController::class, 'store'])->name('shortcuts.store');
    Route::delete('/shortcuts/{shortcut}', [ShortcutController::class, 'destroy'])->name('shortcuts.destroy');

    // Marca Blanca & Customización
    Route::get('/whitelabel', [WhiteLabelController::class, 'index'])->name('whitelabel.index');
    Route::post('/whitelabel', [WhiteLabelController::class, 'update'])->name('whitelabel.update');
});
