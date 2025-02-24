<?php

use App\Http\Controllers\CapitalOutlayController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FundController;
use App\Http\Controllers\ItemClassController;
use App\Http\Controllers\OfficeController;
use App\Http\Controllers\PpmpConsolidatedController;
use App\Http\Controllers\PpmpParticularController;
use App\Http\Controllers\PpmpTransactionController;
use App\Http\Controllers\ProductPpmpExceptionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PrParticularController;
use App\Http\Controllers\PrTransactionController;
use App\Http\Controllers\Forms\PrMultiStepFormController;
use App\Http\Controllers\FundAllocationController;
use App\Http\Controllers\IarTransactionController;
use App\Http\Controllers\Pdf\AdjustedIndividualPpmpController;
use App\Http\Controllers\Pdf\ApprovedConsolidatedPpmpController;
use App\Http\Controllers\Pdf\ConsolidatedPpmpController;
use App\Http\Controllers\Pdf\IndividualPpmpController;
use App\Http\Controllers\Pdf\PriceListActiveController;
use App\Http\Controllers\Pdf\ProductListActiveController;
use App\Http\Controllers\Pdf\PurchaseRequestController;
use App\Http\Controllers\Pdf\SsmiController;
use App\Http\Controllers\Pdf\SummaryOfConsolidatedPpmpController;
use App\Http\Controllers\Pdf\StockCardController;
use App\Http\Controllers\ProductInventoryController;
use App\Http\Controllers\ProductInventoryTransactionController;
use App\Http\Controllers\RisTransactionController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

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
    Route::put('/restore/{fundId}', [FundController::class, 'restore'])->name('fund.restore');
    Route::post('/deactivate', [FundController::class, 'deactivate'])->name('fund.deactivate');
});

Route::middleware('auth')->prefix('categories')->group(function () {
    Route::get('/', [CategoryController::class, 'index'])->name('category.display.active');
    Route::post('/save', [CategoryController::class, 'store'])->name('category.store');
    Route::post('/update', [CategoryController::class, 'update'])->name('category.update');
    Route::post('/restore/{catId}', [CategoryController::class, 'restore'])->name('category.restore');
    Route::post('/deactivate', [CategoryController::class, 'deactivate'])->name('category.deactivate');
});

Route::middleware('auth')->prefix('items')->group(function () {
    Route::get('/', [ItemClassController::class, 'index'])->name('item.display.active');
    Route::post('/save', [ItemClassController::class, 'store'])->name('item.store');
    Route::post('/update', [ItemClassController::class, 'update'])->name('item.update');
    Route::post('/deactivate', [ItemClassController::class, 'deactivate'])->name('item.deactivate');
    Route::post('/restore/{itemClass}', [ItemClassController::class, 'restore'])->name('item.restore');
});

Route::middleware('auth')->prefix('offices')->group(function () {
    Route::get('/', [OfficeController::class, 'index'])->name('office.display.active');
    Route::post('/save', [OfficeController::class, 'store'])->name('office.store');
    Route::post('/update', [OfficeController::class, 'update'])->name('office.update');
    Route::post('/deactivate', [OfficeController::class, 'deactivate'])->name('office.deactivate');
});

Route::middleware('auth')->prefix('general-servies-fund')->group(function () {
    Route::get('/', [CapitalOutlayController::class, 'index'])->name('general.fund.display');
    Route::post('/store-amount', [CapitalOutlayController::class, 'storeFund'])->name('general.fund.store.amount');
    Route::get('/edit-fund-allocations', [CapitalOutlayController::class, 'editFundAllocation'])->name('general.fund.editFundAllocation');
    Route::put('/update-fund-allocations', [CapitalOutlayController::class, 'updateFundAllocation'])->name('general.fund.updateFundAllocation');
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
    Route::post('/proceed-to-approved/{ppmpTransaction}', [PpmpTransactionController::class, 'storeAsFinal'])->name('proceed.to.final.ppmp');
    Route::post('/copy-ppmp', [PpmpTransactionController::class, 'storeCopy'])->name('make.copy.ppmp');
    Route::get('/ppmp-list', [PpmpTransactionController::class, 'showIndividualPpmp_Type'])->name('indiv.ppmp.type');
    Route::get('/consolidated-ppmp-list', [PpmpTransactionController::class, 'showConsolidatedPpmp_Type'])->name('conso.ppmp.type');
    Route::any('/create', [PpmpTransactionController::class, 'store'])->name('create.ppmp.store');
    Route::any('/create-consolidated', [PpmpTransactionController::class, 'storeConsolidated'])->name('consolidated.ppmp.store');
    Route::post('/drop', [PpmpTransactionController::class, 'destroy'])->name('indiv.ppmp.destroy');
    Route::post('/individual-ppmp/create', [PpmpParticularController::class, 'store'])->name('indiv.particular.store');
    Route::put('/individual-ppmp/edit', [PpmpParticularController::class, 'update'])->name('indiv.particular.update');
    Route::post('/consolidated-particular/add', [PpmpConsolidatedController::class, 'store'])->name('conso-particular-store');
    Route::put('/consolidated-particular/update/{ppmpConsolidated}', [PpmpConsolidatedController::class, 'update'])->name('conso-particular-update');
    Route::delete('/consolidated-particular/destroy/{ppmpConsolidated}', [PpmpConsolidatedController::class, 'destroy'])->name('conso-particular-update');
    Route::delete('/individual-ppmp/delete/{ppmpParticular}', [PpmpParticularController::class, 'delete'])->name('indiv.particular.delete');
});

Route::middleware('auth')->prefix('pr')->group(function () {
    Route::get('/create/step-1', [PrMultiStepFormController::class, 'stepOne'])->name('pr.form.step1');
    Route::get('/create/step-2', [PrMultiStepFormController::class, 'stepTwo'])->name('pr.form.step2');
    Route::post('/create/submit', [PrMultiStepFormController::class, 'submit'])->name('pr.form.submit');       
    Route::get('/', [PrTransactionController::class, 'index'])->name('pr.display.transactions');
    Route::get('/procurement-basis', [PrTransactionController::class, 'showProcurementBasis'])->name('pr.display.procurementBasis');
    Route::get('/procurement-basis/available-list/{ppmpTransaction}', [PrTransactionController::class, 'showAvailableToPurchase'])->name('pr.display.availableToPurchase');
    Route::get('/show-pr-particular/{prTransaction}', [PrTransactionController::class, 'showParticulars'])->name('pr.show.particular');
    Route::put('/particular/update', [PrParticularController::class, 'update'])->name('pr.particular.update');
    Route::post('/particular/restore/{prParticular}', [PrParticularController::class, 'restore'])->name('pr.particular.restore');
    Route::delete('/particular/trash/{prParticular}', [PrParticularController::class, 'moveToTrash'])->name('pr.particular.movetotrash');
    Route::put('/particular/approve/{prParticular}', [PrParticularController::class, 'approve'])->name('pr.particular.approve');
    Route::put('/particular/failedAll/{prTransaction}', [PrTransactionController::class, 'failedAll'])->name('pr.particular.failedAll');
    Route::put('/particular/approvedAll/{prTransaction}', [PrTransactionController::class, 'approvedAll'])->name('pr.particular.approvedAll');
});

Route::middleware('auth')->prefix('iar')->group(function () {
    Route::get('/', [IarTransactionController::class, 'index'])->name('iar');
    Route::get('/show-all-air-transaction', [IarTransactionController::class, 'showAllTransactions'])->name('show.iar.transactions');
    Route::get('/collect-transactions', [IarTransactionController::class, 'collectIarTransactions'])->name('iar.collect.transactions');
    Route::get('/particulars', [IarTransactionController::class, 'fetchIarParticular'])->name('iar.particular');
    Route::get('/completed-iar-transaction/particulars', [IarTransactionController::class, 'getCompletedIarTransactionParticulars'])->name('iar.particular.completed');
    Route::post('/particulars/accept', [IarTransactionController::class, 'acceptIarParticular'])->name('iar.particular.accept');
    Route::post('/particulars/acceptAll', [IarTransactionController::class, 'acceptIarParticularAll'])->name('iar.particular.accept.all');
    Route::put('/particulars/update', [IarTransactionController::class, 'updateIarParticular'])->name('iar.particular.update');
    Route::put('/particulars/reject', [IarTransactionController::class, 'rejectIarParticular'])->name('iar.particular.reject');
    Route::post('/particulars/rejectAll', [IarTransactionController::class, 'rejectAllParticular'])->name('iar.particular.reject.all');
});

Route::middleware('auth')->prefix('inventory')->group(function () {
    Route::get('/', [ProductInventoryController::class, 'index'])->name('inventory.index');
    Route::post('/store', [ProductInventoryTransactionController::class, 'store'])->name('store.product.quantity');
    Route::get('/stock-card', [ProductInventoryController::class, 'showStockCard'])->name('show.stockCard');
});

Route::middleware('auth')->prefix('ris')->group(function () {
    Route::get('/', [RisTransactionController::class, 'create'])->name('create.ris');
    Route::post('/store', [RisTransactionController::class, 'store'])->name('store.ris');
    Route::get('/ris-logs', [RisTransactionController::class, 'risTransactions'])->name('ris.display.logs');
    Route::get('/{transactionId}/attachment', [RisTransactionController::class, 'showAttachment'])->name('ris.show.attachment');
});

Route::middleware('auth')->prefix('pdf')->group(function () {
    Route::get('/product-active-list', [ProductListActiveController::class, 'generatePdf_productListActive'])->name('generatePdf.ProductActiveList');
    Route::get('/price-list-active', [PriceListActiveController::class, 'generatePdf_priceListActive'])->name('generatePdf.PriceActiveList');
    Route::get('/consolidated-ppmp-list/{ppmp}', [ConsolidatedPpmpController::class, 'generatePdf_ConsolidatedPpmp'])->name('generatePdf.ConsolidatedPpmp');
    Route::get('/summary-consolidated-ppmp/{ppmp}', [SummaryOfConsolidatedPpmpController::class, 'generatePdf_summaryOfConso'])->name('generatePdf.summaryOfConsolidated');
    Route::get('/consolidated-ppmp-list-approved/{ppmp}', [ApprovedConsolidatedPpmpController::class, 'generatePdf_ApprovedConsolidatedPpmp'])->name('generatePdf.ApprovedConsolidatedPpmp');
    Route::get('/individual-ppmp-list/{ppmp}', [IndividualPpmpController::class, 'generatePdf_IndividualPpmp'])->name('generatePdf.IndividualPpmp');
    Route::get('/adjusted-individual-ppmp-list/{ppmp}', [AdjustedIndividualPpmpController::class, 'generatePdf_IndividualPpmp'])->name('generatePdf.IndividualPpmp.withAdjustment');
    Route::get('/purchase-request-draft/{pr}', [PurchaseRequestController::class, 'generatePdf_purchaseRequestDraft'])->name('generatePdf.PurchaseRequestDraft');
    Route::get('/purchase-request-draft/{pr}', [PurchaseRequestController::class, 'generatePdf_purchaseRequestDraft'])->name('generatePdf.PurchaseRequestDraft');
    Route::get('/stock-card', [StockCardController::class, 'generatePdf_StockCard'])->name('generatePdf.StockCard');
    Route::get('/ssmi', [SsmiController::class, 'generatePdf_ssmi'])->name('generatePdf.ssmi');
});

#AJAX
Route::middleware('auth')->prefix('api')->group(function () {
    Route::get('/office-ppmp-particulars', [PpmpParticularController::class, 'getOfficePpmpParticulars'])->name('get.office.particulars');
    Route::get('/product-inventory-log', [ProductInventoryController::class, 'getProductInventoryLogs'])->name('get.product.inventory.logs');
    Route::get('/search-product-item', [ProductInventoryController::class, 'searchProductItem'])->name('search.product.item');
    Route::get('/issuances-log', [RisTransactionController::class, 'getIssuanceLogs'])->name('get.issuances.logs');
});


require __DIR__.'/auth.php';
