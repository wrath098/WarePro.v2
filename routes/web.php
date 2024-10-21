<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\FundController;
use App\Http\Controllers\ItemClassController;
use App\Http\Controllers\OfficeController;
use App\Http\Controllers\PpmpParticularController;
use App\Http\Controllers\PpmpTransactionController;
use App\Http\Controllers\ProductPpmpExceptionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\Pdf\PriceListActiveController;
use App\Http\Controllers\Pdf\ProductListActiveController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::any('/import', [ProductController::class, 'importProduct'])->name('product.import');

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

Route::middleware('auth')->prefix('products')->group(function () {
    Route::get('/', [ProductController::class, 'index'])->name('product.display.active');
    Route::get('/product-price-list', [ProductController::class, 'showPriceList'])->name('product.display.active.pricelist');
    Route::get('/autocomplete-product', [ProductController::class, 'showAutoComplete'])->name('product.show.autocomplete');
    Route::post('/save', [ProductController::class, 'store'])->name('product.store');
    Route::post('/update', [ProductController::class, 'update'])->name('product.update');
    Route::any('/move-and-modify', [ProductController::class, 'moveAndModify'])->name('product.move.modify');
    Route::post('/deactivate', [ProductController::class, 'deactivate'])->name('product.deactivate');
    Route::get('/unmodified', [ProductPpmpExceptionController::class, 'index'])->name('product.unmodified.list');
    Route::post('/store-unmodified-product', [ProductPpmpExceptionController::class, 'store'])->name('store.unmodified.product');
    Route::post('/deactivate-unmodified-product', [ProductPpmpExceptionController::class, 'deactivate'])->name('deactivate.unmodified.product');
});

Route::middleware('auth')->prefix('ppmp')->group(function () {
    Route::get('/', [PpmpTransactionController::class, 'index'])->name('import.ppmp.index');
    Route::get('/individual-ppmp/{ppmpTransaction}', [PpmpTransactionController::class, 'showIndividualPpmp'])->name('indiv.ppmp.show');
    Route::get('/consolidated-ppmp/{ppmpTransaction}', [PpmpTransactionController::class, 'showConsolidatedPpmp'])->name('conso.ppmp.show');
    Route::get('/ppmp-list', [PpmpTransactionController::class, 'showIndividualPpmp_Type'])->name('indiv.ppmp.type');
    Route::get('/consolidated-ppmp-list', [PpmpTransactionController::class, 'showConsolidatedPpmp_Type'])->name('conso.ppmp.type');
    Route::any('/create', [PpmpTransactionController::class, 'store'])->name('create.ppmp.store');
    Route::any('/create-consolidated', [PpmpTransactionController::class, 'storeConsolidated'])->name('consolidated.ppmp.store');
    Route::post('/drop', [PpmpTransactionController::class, 'destroy'])->name('indiv.ppmp.destroy');
    Route::post('/individual-ppmp/create', [PpmpParticularController::class, 'store'])->name('indiv.particular.store');
    Route::put('/individual-ppmp/edit', [PpmpParticularController::class, 'update'])->name('indiv.particular.update');
    Route::delete('/individual-ppmp/delete/{ppmpParticular}', [PpmpParticularController::class, 'delete'])->name('indiv.particular.delete');
});

Route::middleware('auth')->prefix('pdf')->group(function () {
    Route::get('/product-active-list', [ProductListActiveController::class, 'generatePdf_productListActive'])->name('generatePdf.ProductActiveList');
    Route::get('/price-list-active', [PriceListActiveController::class, 'generatePdf_priceListActive'])->name('generatePdf.PriceActiveList');
});

require __DIR__.'/auth.php';
