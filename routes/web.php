<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BillController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\KitchenController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\TableController;
use Illuminate\Support\Facades\Route;

// Redirect root ke POS Billing
Route::get('/', [PosController::class, 'index'])->name('pos.index');

// POS routes
Route::prefix('pos')->name('pos.')->group(function () {
    Route::get('/', [PosController::class, 'index'])->name('index');
    Route::get('/search', [PosController::class, 'search'])->name('search');
});

// Orders & Hold
Route::prefix('orders')->name('orders.')->group(function () {
    Route::post('/', [OrderController::class, 'store'])->name('store');
    Route::get('/held', [OrderController::class, 'getHeldOrders'])->name('held');
    Route::post('/recall/{id}', [OrderController::class, 'recallHeldOrder'])->name('recall');
    Route::post('/cancel/{id}', [OrderController::class, 'cancel'])->name('cancel');
});

// Payments & Receipts
Route::prefix('payments')->name('payments.')->group(function () {
    Route::post('/{orderId}/pay', [PaymentController::class, 'pay'])->name('pay');
    Route::get('/{orderId}/receipt', [PaymentController::class, 'receipt'])->name('receipt');
});

// Kitchen Display (KOT)
Route::prefix('kitchen')->name('kitchen.')->group(function () {
    Route::get('/', [KitchenController::class, 'index'])->name('index');
    Route::post('/{id}/status', [KitchenController::class, 'updateStatus'])->name('status');
});

// Tables Management
Route::prefix('tables')->name('tables.')->group(function () {
    Route::get('/', [TableController::class, 'index'])->name('index');
    Route::post('/{id}/status', [TableController::class, 'updateStatus'])->name('status');
});

// Bills & Invoices
Route::get('/bills', [BillController::class, 'index'])->name('bills.index');

// Products / Catalog
Route::prefix('products')->name('products.')->group(function () {
    Route::get('/', [ProductController::class, 'index'])->name('index');
    Route::post('/', [ProductController::class, 'store'])->name('store');
});

// Inventory & Stock Control
Route::prefix('inventory')->name('inventory.')->group(function () {
    Route::get('/', [InventoryController::class, 'index'])->name('index');
    Route::post('/restock', [InventoryController::class, 'restock'])->name('restock');
    Route::post('/adjust', [InventoryController::class, 'adjust'])->name('adjust');
});

// Auth Routes (Login, Quick Simulation, Logout)
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
Route::get('/login/quick/{id}', [AuthController::class, 'quickLogin'])->name('login.quick');
Route::match(['get', 'post'], '/logout', [AuthController::class, 'logout'])->name('logout');

// Dashboard & Insights
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
Route::get('/switch-user/{id}', [DashboardController::class, 'switchUser'])->name('users.switch');
