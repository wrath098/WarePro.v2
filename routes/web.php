<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\FundController;
use App\Http\Controllers\ItemClassController;
use App\Http\Controllers\OfficeController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware('auth')->prefix('funds')->group(function () {
    Route::get('/', [FundController::class, 'index'])->name('fund.display.all');
    Route::post('/save', [FundController::class, 'store'])->name('fund.store');
    Route::post('/update', [FundController::class, 'update'])->name('fund.update');
    Route::post('/deactivate', [FundController::class, 'deactivate'])->name('fund.deactivate');
});

Route::middleware('auth')->prefix('categories')->group(function () {
    Route::get('/', [CategoryController::class, 'index'])->name('category.display.active');
    Route::post('/save', [CategoryController::class, 'store'])->name('category.store');
    Route::post('/update', [CategoryController::class, 'update'])->name('category.update');
    Route::post('/deactivate', [CategoryController::class, 'deactivate'])->name('category.deactivate');
});

Route::middleware('auth')->prefix('items')->group(function () {
    Route::get('/', [ItemClassController::class, 'index'])->name('item.display.active');
    Route::post('/save', [ItemClassController::class, 'store'])->name('item.store');
    Route::post('/update', [ItemClassController::class, 'update'])->name('item.update');
    Route::post('/deactivate', [ItemClassController::class, 'deactivate'])->name('item.deactivate');
});

Route::middleware('auth')->prefix('offices')->group(function () {
    Route::get('/', [OfficeController::class, 'index'])->name('office.display.active');
    Route::post('/save', [OfficeController::class, 'store'])->name('office.store');
    Route::post('/update', [OfficeController::class, 'update'])->name('office.update');
    Route::post('/deactivate', [OfficeController::class, 'deactivate'])->name('office.deactivate');
});

require __DIR__.'/auth.php';
