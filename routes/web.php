<?php

use App\Http\Controllers\VoucherController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [VoucherController::class, 'loginScreen'])->name('login');
Route::post('/login', [VoucherController::class, 'login'])->middleware('throttle:10,1');
Route::post('/logout', [VoucherController::class, 'logout'])->name('logout');

Route::middleware('operator')->group(function () {
    Route::redirect('/', '/vouchers');

    Route::get('/vouchers', [VoucherController::class, 'index'])->name('vouchers.index');
    Route::get('/vouchers/new', [VoucherController::class, 'create'])->name('vouchers.create');
    Route::post('/vouchers', [VoucherController::class, 'store'])->name('vouchers.store');

    Route::get('/redeem', [VoucherController::class, 'redeemScreen'])->name('redeem');
    Route::get('/liability', [VoucherController::class, 'liability'])->name('liability');

    Route::get('/vouchers/{voucher}', [VoucherController::class, 'show'])->name('vouchers.show');
    Route::get('/vouchers/{voucher}/print', [VoucherController::class, 'print'])->name('vouchers.print');
    Route::post('/vouchers/{voucher}/email', [VoucherController::class, 'email'])->name('vouchers.email');
    Route::post('/vouchers/{voucher}/redeem', [VoucherController::class, 'redeem'])->name('vouchers.redeem');
    Route::post('/vouchers/{voucher}/void', [VoucherController::class, 'void'])->name('vouchers.void');
});
