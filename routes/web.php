<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BalikGasaController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DonationController;
use App\Http\Controllers\HugpongBanayController;
use App\Http\Controllers\LedgerController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\OfferingController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/', fn () => redirect()->route('login'));
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::resource('members', MemberController::class)->middleware('role:admin,treasurer');
    Route::get('/members/{member}/balik-gasa-year', [MemberController::class, 'balikGasaYear'])->name('members.balik-gasa-year');
    Route::resource('hugpong-banays', HugpongBanayController::class)->middleware('role:admin,treasurer');
    Route::resource('collections', CollectionController::class)->except('show')->middleware('role:admin,treasurer');
    Route::get('/ledger', [LedgerController::class, 'index'])->name('ledger.index');
    Route::post('/ledger/entries', [LedgerController::class, 'storeEntry'])->middleware('role:admin,treasurer')->name('ledger.entries.store');
    Route::post('/ledger/expenses', [LedgerController::class, 'storeExpense'])->middleware('role:admin,treasurer')->name('ledger.expenses.store');

    Route::get('/balik-gasa', [BalikGasaController::class, 'index'])->name('balik-gasa.index');
    Route::post('/balik-gasa/{member}/quick-pay', [BalikGasaController::class, 'quickPay'])
        ->middleware('role:admin,treasurer')
        ->name('balik-gasa.quick-pay');
    Route::get('/donations', [DonationController::class, 'index'])->name('donations.index');
    Route::post('/donations/{member}/quick-pay', [DonationController::class, 'quickPay'])
        ->middleware('role:admin,treasurer')
        ->name('donations.quick-pay');
    Route::get('/offerings', [OfferingController::class, 'index'])->name('offerings.index');
    Route::post('/offerings/quick-post', [OfferingController::class, 'quickPost'])
        ->middleware('role:admin,treasurer')
        ->name('offerings.quick-post');

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/monthly/print', [ReportController::class, 'print'])->name('reports.print');
    Route::get('/reports/csv', [ReportController::class, 'csv'])->name('reports.csv');

    Route::resource('users', UserController::class)->except('show')->middleware('role:admin');
});
