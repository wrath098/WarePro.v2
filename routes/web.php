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
use App\Http\Controllers\IarParticularController;
use App\Http\Controllers\IarTransactionController;
use App\Http\Controllers\Pdf\ApprovedConsolidatedPpmpController;
use App\Http\Controllers\Pdf\AsOfStockCardController;
use App\Http\Controllers\Pdf\ConsolidatedPpmpController;
use App\Http\Controllers\Pdf\OfficePpmpController;
use App\Http\Controllers\Pdf\EmergencyPpmpController;
use App\Http\Controllers\Pdf\InventoryController;
use App\Http\Controllers\Pdf\IssuanceController;
use App\Http\Controllers\Pdf\MonthlyInventoryReportController;
use App\Http\Controllers\Pdf\PriceAdustedController;
use App\Http\Controllers\Pdf\PriceListActiveController;
use App\Http\Controllers\Pdf\ProductListActiveController;
use App\Http\Controllers\Pdf\PsDbmController;
use App\Http\Controllers\Pdf\PurchaseRequestController;
use App\Http\Controllers\Pdf\SsmiController;
use App\Http\Controllers\Pdf\SummaryOfConsolidatedPpmpController;
use App\Http\Controllers\Pdf\StockCardController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ProductInventoryController;
use App\Http\Controllers\ProductInventoryTransactionController;
use App\Http\Controllers\RisTransactionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->route('login');
});

require __DIR__.'/auth.php';

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::any('/import', [ProductController::class, 'importProduct'])->name('product.import');

Route::middleware('auth')->prefix('users')->group(function () {

    Route::middleware('userAccess')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('user');
        Route::get('/user-information/{user}', [UserController::class, 'userInformation'])->name('user.information');
        Route::post('/assign-role', [UserController::class, 'assignRole'])->name('user.assign.role');
        Route::post('/assign-permission', [UserController::class, 'assignPermission'])->name('user.assign.permission');
        Route::delete('/revoke-role', [UserController::class, 'revokeRole'])->name('user.revoke.role');
        Route::put('/update-user-information', [UserController::class, 'updateUserInformation'])->name('update.user.information');
        Route::put('/user-new-password', [UserController::class, 'userNewPassword'])->name('user.new.password');
        Route::delete('/user-account/{user}', [UserController::class, 'destroy'])->name('user.account.destroy');
        Route::delete('/revoke-permission', [UserController::class, 'revokePermission'])->name('user.revoke.permission');
    });

    Route::middleware('developer')->group(function () {
        Route::get('/roles', [RoleController::class, 'index'])->name('user.roles');
        Route::post('/roles/store', [RoleController::class, 'store'])->name('user.roles.store');
        Route::delete('/roles/delete/{role}', [RoleController::class, 'destroy'])->name('user.roles.destroy');

        Route::get('/roles/{role}', [RoleController::class, 'showRolePermissions'])->name('user.role.permissions');
        Route::post('/permission-in-role/create', [RoleController::class, 'storeRolePermission'])->name('role.permission.store');
        Route::delete('/permission-in-role/delete', [RoleController::class, 'destroyRolePermission'])->name('role.permission.destroy');

        Route::get('/permissions', [PermissionController::class, 'index'])->name('user.permissions');
        Route::post('/permissions/store', [PermissionController::class, 'store'])->name('user.permissions.store');
        Route::delete('/permissions/delete/{permission}', [PermissionController::class, 'destroy'])->name('user.permissions.destroy');

        #AJAX
        Route::get('/available-permissions-for-role', [RoleController::class, 'showAvailablePermissionsForRole'])->name('available.role.permissions');
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware('auth')->prefix('funds')->group(function () {
    Route::middleware('componentAccess')->get('/', [FundController::class, 'index'])->name('fund.display.all');
    Route::post('/save', [FundController::class, 'store'])->name('fund.store');
    Route::put('/update', [FundController::class, 'update'])->name('fund.update');
    Route::put('/restore/{fundId}', [FundController::class, 'restore'])->name('fund.restore');
    Route::put('/deactivate', [FundController::class, 'deactivate'])->name('fund.deactivate');

    #AJAX
    Route::get('/trashed-funds', [FundController::class, 'showTrashedFunds'])->name('show.trash.funds');
});

Route::middleware('auth')->prefix('categories')->group(function () {
    Route::middleware('componentAccess')->get('/{fund}', [CategoryController::class, 'index'])->name('category.display.active');
    Route::post('/save', [CategoryController::class, 'store'])->name('category.store');
    Route::post('/update', [CategoryController::class, 'update'])->name('category.update');
    Route::post('/restore/{catId}', [CategoryController::class, 'restore'])->name('category.restore');
    Route::post('/deactivate', [CategoryController::class, 'deactivate'])->name('category.deactivate');

    #AJAX
    Route::get('/{fund}/trashed-categories', [CategoryController::class, 'showTrashedCategories'])->name('category.show.trashed');
});

Route::middleware('auth')->prefix('items')->group(function () {
    Route::middleware('componentAccess')->get('/', [ItemClassController::class, 'index'])->name('item.display.active');
    Route::post('/save', [ItemClassController::class, 'store'])->name('item.store');
    Route::put('/update', [ItemClassController::class, 'update'])->name('item.update');
    Route::put('/deactivate', [ItemClassController::class, 'deactivate'])->name('item.deactivate');
    Route::put('/restore/{itemClass}', [ItemClassController::class, 'restore'])->name('item.restore');

    #AJAX
    Route::get('/trashed-itemclass', [ItemClassController::class, 'showTrashedItemClass'])->name('item.show.trashed');
});

Route::middleware('auth')->prefix('offices')->group(function () {
    Route::middleware('componentAccess')->get('/', [OfficeController::class, 'index'])->name('office.display.active');
    Route::post('/save', [OfficeController::class, 'store'])->name('office.store');
    Route::put('/update', [OfficeController::class, 'update'])->name('office.update');
    Route::put('/deactivate', [OfficeController::class, 'deactivate'])->name('office.deactivate');
});

Route::middleware('auth')->prefix('general-servies-fund')->group(function () {
    Route::middleware('componentAccess')->get('/', [CapitalOutlayController::class, 'index'])->name('general.fund.display');
    Route::post('/store-amount', [CapitalOutlayController::class, 'storeFund'])->name('general.fund.store.amount');
    Route::get('/edit-fund-allocations', [CapitalOutlayController::class, 'editFundAllocation'])->name('general.fund.editFundAllocation');
    Route::put('/update-fund-allocations', [CapitalOutlayController::class, 'updateFundAllocation'])->name('general.fund.updateFundAllocation');

    #AJAX
    Route::get('/show-fund-by-year', [CapitalOutlayController::class, 'showFundByYear'])->name('show.fundByYear');
});

Route::middleware('auth')->prefix('products')->group(function () {
    Route::get('/', [ProductController::class, 'index'])->name('product.display.active');
    Route::middleware('productAccess')->group(function() {
        Route::get('/product-price-list', [ProductController::class, 'showPriceList'])->name('product.display.active.pricelist');
        Route::get('/unmodified', [ProductPpmpExceptionController::class, 'index'])->name('product.unmodified.list');
    });
    Route::get('/autocomplete-product', [ProductController::class, 'showAutoComplete'])->name('product.show.autocomplete');
    Route::post('/save', [ProductController::class, 'store'])->name('product.store');
    Route::put('/update', [ProductController::class, 'update'])->name('product.update');
    Route::put('/restore', [ProductController::class, 'restore'])->name('product.restore');
    Route::any('/move-and-modify', [ProductController::class, 'moveAndModify'])->name('product.move.modify');
    Route::put('/deactivate', [ProductController::class, 'deactivate'])->name('product.deactivate');
    Route::post('/store-unmodified-product', [ProductPpmpExceptionController::class, 'store'])->name('store.unmodified.product');
    Route::put('/deactivate-unmodified-product', [ProductPpmpExceptionController::class, 'deactivate'])->name('deactivate.unmodified.product');
    Route::post('/upload-product-image', [ProductController::class, 'uploadProductImage'])->name('upload.product.image');


    #AJAX
    Route::get('/trashed-items', [ProductController::class, 'getTrashedItems'])->name('trashed.product.items');
    Route::get('/search-product', [ProductController::class, 'searchProduct'])->name('search.product.items');
});

Route::middleware('auth')->prefix('ppmp')->group(function () {
    Route::middleware('ppmpAccess')->group(function() {
        Route::get('/', [PpmpTransactionController::class, 'index'])->name('import.ppmp.index');
        Route::get('/office-list', [PpmpTransactionController::class, 'showIndividualPpmp_Type'])->name('indiv.ppmp.type');
        Route::get('/consolidated-ppmp-list', [PpmpTransactionController::class, 'showConsolidatedPpmp_Type'])->name('conso.ppmp.type');
    });
    
    Route::get('/individual-ppmp/{ppmpTransaction}', [PpmpTransactionController::class, 'showIndividualPpmp'])->name('indiv.ppmp.show');
    Route::get('/consolidated-ppmp/{ppmpTransaction}', [PpmpTransactionController::class, 'showConsolidatedPpmp'])->name('conso.ppmp.show');
    Route::post('/proceed-to-approved/{ppmpTransaction}', [PpmpTransactionController::class, 'storeAsFinal'])->name('proceed.to.final.ppmp');
    Route::post('/copy-ppmp', [PpmpTransactionController::class, 'storeCopy'])->name('make.copy.ppmp');
    Route::any('/create', [PpmpTransactionController::class, 'store'])->name('create.ppmp.store');
    Route::any('/create-consolidated', [PpmpTransactionController::class, 'storeConsolidated'])->name('consolidated.ppmp.store');
    Route::post('/update-adjustment', [PpmpTransactionController::class, 'updateInitialAdjustment'])->name('updateInitialAdjustment');
    Route::post('/update-final-adjustment', [PpmpTransactionController::class, 'updateFinalAdjustment'])->name('updateFinalAdjustment');
    // Route::post('/update', [PpmpTransactionController::class, 'updateConsolidatedDescription'])->name('indiv.ppmp.update');
    Route::delete('/drop', [PpmpTransactionController::class, 'destroy'])->name('indiv.ppmp.destroy');
    Route::post('/individual-ppmp/create', [PpmpParticularController::class, 'store'])->name('indiv.particular.store');
    Route::put('/individual-ppmp/edit', [PpmpParticularController::class, 'update'])->name('indiv.particular.update');
    Route::post('/consolidated-particular/add', [PpmpConsolidatedController::class, 'store'])->name('conso-particular-store');
    Route::put('/consolidated-particular/update/{ppmpConsolidated}', [PpmpConsolidatedController::class, 'update'])->name('conso-particular-update');
    Route::delete('/consolidated-particular/destroy/{ppmpConsolidated}', [PpmpConsolidatedController::class, 'destroy'])->name('conso-particular-destroy');
    Route::delete('/individual-ppmp/delete', [PpmpParticularController::class, 'delete'])->name('indiv.particular.delete');

    #AJAX
    Route::get('/offices-with-no-ppmp', [PpmpTransactionController::class, 'showOfficeListWithNoPpmp'])->name('show.ppmp.withNoPpmp');
});

Route::middleware('auth')->prefix('pr')->group(function () {
    Route::middleware('purchaseRequestAccess')->group(function() {
        Route::get('/', [PrTransactionController::class, 'index'])->name('pr.display.transactions');
        Route::get('/procurement-basis', [PrTransactionController::class, 'showProcurementBasis'])->name('pr.display.procurementBasis');
        Route::get('/create/step-1', [PrMultiStepFormController::class, 'stepOne'])->name('pr.form.step1');
    });
    
    Route::get('/create/step-2', [PrMultiStepFormController::class, 'stepTwo'])->name('pr.form.step2');
    Route::post('/create/submit', [PrMultiStepFormController::class, 'submit'])->name('pr.form.submit');       
    Route::get('/procurement-basis/available-list/{ppmpTransaction}', [PrTransactionController::class, 'showAvailableToPurchase'])->name('pr.display.availableToPurchase');
    Route::get('/show-pr-particular/{prTransaction}', [PrTransactionController::class, 'showParticulars'])->name('pr.show.particular');
    Route::post('/particular/store', [PrParticularController::class, 'store'])->name('pr.particular.store');
    Route::put('/particular/update', [PrParticularController::class, 'update'])->name('pr.particular.update');
    Route::post('/particular/restore/{prParticular}', [PrParticularController::class, 'restore'])->name('pr.particular.restore');
    Route::delete('/particular/trash/{prParticular}', [PrParticularController::class, 'moveToTrash'])->name('pr.particular.movetotrash');
    Route::put('/particular/approve/{prParticular}', [PrParticularController::class, 'approve'])->name('pr.particular.approve');
    Route::put('/particular/failedAll/{prTransaction}', [PrTransactionController::class, 'failedAll'])->name('pr.particular.failedAll');
    Route::put('/particular/approvedAll/{prTransaction}', [PrTransactionController::class, 'approvedAll'])->name('pr.particular.approvedAll');
});

Route::middleware(['auth', 'role_or_permission:view-purchase-order|Developer'])->prefix('po')->group(function () {
    Route::get('/on-process', [PrTransactionController::class, 'showOnProgress'])->name('pr.show.onProcess');
});

Route::middleware('auth')->prefix('iar')->group(function () {
    Route::middleware(['auth', 'role_or_permission:view-iar-transaction-pending|Developer'])->get('/', [IarTransactionController::class, 'index'])->name('iar');
    Route::middleware(['auth', 'role_or_permission:view-iar-transaction-all|Developer'])->get('/show-all-air-transaction', [IarTransactionController::class, 'showAllTransactions'])->name('show.iar.transactions');
    Route::post('/collect-transactions', [IarTransactionController::class, 'collectIarTransactions'])->name('iar.collect.transactions');
    Route::get('/particulars', [IarTransactionController::class, 'fetchIarParticular'])->name('iar.particular');
    Route::get('/completed-iar-transaction/particulars', [IarTransactionController::class, 'getCompletedIarTransactionParticulars'])->name('iar.particular.completed');
    Route::post('/particulars/accept', [IarTransactionController::class, 'acceptIarParticular'])->name('iar.particular.accept');
    Route::post('/particulars/acceptAll', [IarTransactionController::class, 'acceptIarParticularAll'])->name('iar.particular.accept.all');
    Route::put('/particulars/update', [IarTransactionController::class, 'updateIarParticular'])->name('iar.particular.update');
    Route::put('/particulars/reject', [IarTransactionController::class, 'rejectIarParticular'])->name('iar.particular.reject');
    Route::post('/particulars/rejectAll', [IarTransactionController::class, 'rejectAllParticular'])->name('iar.particular.reject.all');
    Route::post('/particulars/return', [IarTransactionController::class, 'returnParticular_toPending'])->name('return.iar.particular');
});

Route::middleware('auth')->prefix('inventory')->group(function () {
    Route::middleware(['auth', 'role_or_permission:view-products-inventory|Developer'])->get('/', [ProductInventoryController::class, 'index'])->name('inventory.index');
    Route::post('/store', [ProductInventoryTransactionController::class, 'store'])->name('store.product.quantity');
    Route::post('/edit-re-order-level', [ProductInventoryTransactionController::class, 'updateReOrderLevel'])->name('update.reorder.level');
    Route::post('/iar-particular/remove', [ProductInventoryTransactionController::class, 'removeParticularTransaction'])->name('remove.air.inventory');
    Route::middleware(['auth', 'role_or_permission:view-products-inventory|Developer'])->get('/stock-card', [ProductInventoryController::class, 'showStockCard'])->name('show.stockCard');
    Route::middleware(['auth', 'role_or_permission:monitor-expiring-products|Developer'])->get('/expiry-products', [ProductInventoryTransactionController::class, 'showProductsOnExpired'])->name('show.expiry.products');
    Route::middleware(['auth', 'role_or_permission:monitor-expiring-products|Developer'])->get('/inventory-report', [ProductInventoryTransactionController::class, 'inventoryReport'])->name('inventory.report');
});

Route::middleware('auth')->prefix('ris')->group(function () {
    Route::middleware(['auth', 'role_or_permission:create-ris-transaction|Developer'])->get('/', [RisTransactionController::class, 'create'])->name('create.ris');
    Route::get('/ssmi', [RisTransactionController::class, 'ssmi'])->name('view.ssmi');
    Route::get('/filter-ris-transactions', [RisTransactionController::class, 'filterRisTransactions'])->name('filter.ris.transactions');
    Route::post('/store', [RisTransactionController::class, 'store'])->name('store.ris');
    Route::post('/update', [RisTransactionController::class, 'update'])->name('update.ris');
    Route::post('/particular/update', [RisTransactionController::class, 'updateParticular'])->name('update.ris.particular');
    Route::post('/particular/delete', [RisTransactionController::class, 'removeParticular'])->name('remove.ris.particular');
    Route::middleware(['auth', 'role_or_permission:view-ris-transactions|Developer'])->get('/ris-logs', [RisTransactionController::class, 'risTransactions'])->name('ris.display.logs');
    Route::get('/{transactionId}/attachment', [RisTransactionController::class, 'showAttachment'])->name('ris.show.attachment');
    Route::get('/{transactionId}/{issuedTo}', [RisTransactionController::class, 'showRisItems'])->name('ris.show.items');
});

Route::middleware('auth')->prefix('pdf')->group(function () {
    Route::get('/beginning-balance', [InventoryController::class, 'generatePdf_productInventoryList'])->name('generatePdf.productInventoryList');
    Route::get('/consolidated-ppmp-list/{ppmp}', [ConsolidatedPpmpController::class, 'generatePdf_ConsolidatedPpmp'])->name('generatePdf.ConsolidatedPpmp');
    Route::get('/consolidated-ppmp-list-approved/{ppmp}', [ApprovedConsolidatedPpmpController::class, 'generatePdf_ApprovedConsolidatedPpmp'])->name('generatePdf.ApprovedConsolidatedPpmp');
    Route::get('/drafted-office-ppmp-list/{ppmp}', [OfficePpmpController::class, 'generatePdf_Ppmp'])->name('generatePdf.DraftedOfficePpmp');
    Route::get('/emergency-ppmp/{ppmp}', [EmergencyPpmpController::class, 'generatePdf_Ppmp'])->name('generatePdf.emergencyPpmp');
    Route::get('/monthly-product-inventory', [MonthlyInventoryReportController::class, 'generatePdf_MonthlyInventoryReport'])->name('generatePdf.MonthlyInventoryReport');
    Route::get('/price-list-active', [PriceListActiveController::class, 'generatePdf_priceListActive'])->name('generatePdf.PriceActiveList');
    Route::get('/product-active-list', [ProductListActiveController::class, 'generatePdf_productListActive'])->name('generatePdf.ProductActiveList');
    Route::get('/ps-dbm', [PsDbmController::class, 'generate_psDbm'])->name('generatePdf.psDbm');
    Route::get('/purchase-request-draft/{pr}', [PurchaseRequestController::class, 'generatePdf_purchaseRequestDraft'])->name('generatePdf.PurchaseRequestDraft');
    Route::get('/purchase-request-excel/{pr}', [PurchaseRequestController::class, 'generateExcel_purchaseRequestDraft'])->name('generatePdf.PurchaseRequestExcel');
    Route::get('/purchase-request-ps-dbm/{pr}', [PurchaseRequestController::class, 'generate_psDbm'])->name('generatePdf.pr.psDbm');
    Route::get('/ssmi', [SsmiController::class, 'generatePdf_ssmi'])->name('generatePdf.ssmi');
    Route::get('/stock-card', [StockCardController::class, 'generatePdf_StockCard'])->name('generatePdf.StockCard');
    Route::get('/stock-card-as-of', [AsOfStockCardController::class, 'generatePdf_StockCard'])->name('generatePdf.StockCard.AsOf');
    Route::get('/summary-consolidated-ppmp/{ppmp}', [SummaryOfConsolidatedPpmpController::class, 'generatePdf_summaryOfConso'])->name('generatePdf.summaryOfConsolidated');
    Route::get('/issuance', [IssuanceController::class, 'generatePdf_Issuance'])->name('generatePdf.issuance');    
    Route::get('/price-adjusted', [PriceAdustedController::class, 'generate_priceAdjusted'])->name('generatePdf.priceAdjusted');  
});

Route::middleware('auth')->prefix('word')->group(function () {
    Route::get('/product-active-list', [ProductListActiveController::class, 'generate_productlist_word'])->name('generate.product.active.list.word');
});

#AJAX
Route::middleware('auth')->prefix('api')->group(function () {

    #PPMP
    Route::get('/office-ppmp-particulars', [PpmpParticularController::class, 'getOfficePpmpParticulars'])->name('get.office.particulars');

    #DASHBOARD
    Route::get('/filter-dashboard', [DashboardController::class, 'filterByDate'])->name('filter.dashboard');
    Route::get('/fast-moving-items', [DashboardController::class, 'getFastMovingItems'])->name('get.fast.moving.items');
    Route::get('/monthly-product-inventory', [ProductInventoryController::class, 'getMonthlyInventory'])->name('get.monthly.inventory');

    #PRODUCT
    Route::get('/search-product-catalog', [ProductController::class, 'searchProductCatalog'])->name('search.product.catalog');
    Route::get('/search-product-item', [ProductInventoryController::class, 'searchProductItem'])->name('search.product.item');
    Route::get('/filter-product-catalog', [ProductController::class, 'filterProductCatalog'])->name('filter.product.catalog');
    Route::get('/fetch-product/{prodId}', [ProductController::class, 'filterProductById'])->name('filter.product.by.id');
    
    #PR
    Route::get('/ppmp-type', [PrMultiStepFormController::class, 'filterToPurchase'])->name('filter.purchase.request');

    #RIS
    Route::get('/issuances-log', [RisTransactionController::class, 'getIssuanceLogs'])->name('get.issuances.logs');

    #INVENTORY
    Route::get('/product-inventory-log', [ProductInventoryController::class, 'getProductInventoryLogs'])->name('get.product.inventory.logs');
});

#For IAR NO UPLOAD
#REMOVE IF NO REVISION TO BE MADE
//Route::get('/upload-iar-no', [IarTransactionController::class, 'uploadIarNo'])->name('upload.iar.no');



Route::fallback(function () {
    return Inertia::render('Auth/NotFound', ['isAuth' => Auth::check()]);
});
