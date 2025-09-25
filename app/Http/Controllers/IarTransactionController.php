<?php

namespace App\Http\Controllers;

use App\Models\IarParticular;
use App\Models\IarTransaction;
use App\Models\Product;
use App\Models\ProductInventory;
use App\Models\ProductInventoryTransaction;
use App\Models\ProductPrice;
use App\Services\ProductService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;

class IarTransactionController extends Controller
{
    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function index()
    {
        $lists = IarTransaction::where('status', 'pending')
            ->orderBy('sdi_iar_id', 'desc')
            ->get();

        $wareproList = IarTransaction::latest('sdi_iar_id')->first();
        $lastIarId = $wareproList ? $wareproList->sdi_iar_id : 0;

        $lists = $lists->map(fn($iar) => [
            'id' => $iar->id,
            'airId' => $iar->sdi_iar_id,
            'airNo' => $iar->iar_no ?? '',
            'poId' => $iar->po_no,
            'supplier' => $iar->supplier,
            'date' => $iar->date,
            'status' => ucfirst($iar->status),
        ]);

        return Inertia::render('Iar/Pending', ['iar' => $lists, 'lastId' => $lastIarId]);
    }

    public function showAllTransactions()
    {
        $transactionList = IarTransaction::withTrashed()
            ->with('updater')
            ->orderby('updated_at', 'desc')
            ->take(1000)
            ->get();

        $transactionList = $transactionList->map(fn($transaction) => [
            'id' => $transaction->id,
            'airId' => $transaction->sdi_iar_id,
            'poId' => $transaction->po_no,
            'supplier' => $transaction->supplier,
            'date' => Carbon::parse($transaction->date)->format('m-d-Y'),
            'status' => ucfirst($transaction->status),
            'updater' => $transaction->updater->name ?? '',
            'dateUpdated' => $transaction->updated_at->format('m-d-Y'),
        ]);

        return Inertia::render('Iar/Transactions', ['transactions' => $transactionList]);
    }

    public function collectIarTransactions(Request $request)
    {
        DB::beginTransaction();
        
        try {
            $pgsoList = $request->param;

            foreach ($pgsoList as $iar) {
                if(!$this->verifyIarExistence($iar['air_id'])) {
                    $createIar = $this->processCreationOfIarTransaction($iar);
    
                    foreach ($iar['particulars'] as $particular) {
                        $this->processCreationOfIarParticulars($particular, $createIar['id']);
                    }
                }
            }

            DB::commit();
            return redirect()->back()->with(['message' => 'Successfully collected the IAR Transactions from the AssetPro!']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Collecting IAR Transaction from AssetPro failed: ", [
                'user' => Auth::user()->name,
                'error' => $e->getMessage(),
            ]);
            return redirect()->back()->with(['error' => 'Unable to collect IAR Transactions. Please try again!']);
        }
    }

    public function fetchIarParticular(Request $request)
    {   
        $iarId = $request->input('iar');
        $iarTransaction = IarTransaction::findOrFail($iarId);
        $productList = $this->getAllActiveProducts();

        $particulars = $iarTransaction->load(['iarParticulars' => function($query) {
            $query->where('status', 'pending');
        }]);

        $particulars = $particulars->iarParticulars->map(fn($item) => [
            'pId' => $item->id,
            'itemNo' => $item->item_no,
            'stockNo' => $item->stock_no,
            'unit' => ucfirst($item->unit),
            'specs' => $item->description,
            'quantity' => $item->qty,
            'price' => $item->price,
            'status' => $item->status,
            'cost' => number_format(($item->qty * $item->price), 2,'.', ','),
            'expiry' => $item->date_expiry ?? '',
        ]);

        return Inertia::render('Iar/Particular', ['iar' => $iarTransaction,'particulars' => $particulars, 'productList' => $productList]);
    }

    public function getCompletedIarTransactionParticulars(Request $request)
    {   
        $iarId = $request->input('iar');
        $iarTransaction = $this->getIarTransaction($iarId);
        $particulars = $iarTransaction->load(['iarParticulars', 'updater']);

        $particulars = $particulars->iarParticulars->map(fn($item) => [
            'pId' => $item->id,
            'itemNo' => $item->item_no,
            'stockNo' => $item->stock_no ?? 'N/A',
            'unit' => ucfirst($item->unit),
            'specs' => Str::limit($item->description, 100, '.....'),
            'quantity' => $item->qty,
            'price' => $item->price,
            'status' => ucfirst($item->status),
            'cost' => number_format(($item->qty * $item->price), 2,'.', ','),
            'expiry' => $item->date_expiry ?? '',
        ]);

        $iarTransaction = [
            'id' => $iarTransaction->id,
            'sdiIarId' => $iarTransaction->sdi_iar_id,
            'iarNo' => $iarTransaction->iar_no ?? 'N/A',
            'poNo' => $iarTransaction->po_no,
            'supplier' => $iarTransaction->supplier,
            'iarDate' => $iarTransaction->date,
            'status' => ucfirst($iarTransaction->status),
            'remark' => $iarTransaction->remark,
            'dateUpdated' => $iarTransaction->updated_at->format('Y-m-d'),
            'particularCount' => $iarTransaction->iarParticulars->count(),
            'updater' => $iarTransaction->updater->name,
        ];
        
        return Inertia::render('Iar/CompletedTransactionParticulars', ['transaction' => $iarTransaction, 'particulars' => $particulars]);
    }

    public function acceptIarParticular(Request $request)
    {   
        DB::beginTransaction();

        try {
            $particular = IarParticular::findOrFail($request->pid);
            $productDetails = $this->validateProduct($particular->stock_no);
            $formattedDate = $this->productService->defaultDateFormat($request->dateReceive);

            if (!$formattedDate || !$this->productService->isDateValid($formattedDate)) {
                DB::rollBack();
                return back()->with('error', 'Invalid Date. Please try again!');
            }

            if(!$productDetails) {
                DB::rollBack();
                return back()->with('error', 'Product Item/Stock No. not found. Please update the product code and try again!');
            }

            $userId = Auth::id();

            if($productDetails->has_expiry && !$particular->date_expiry) {
                DB::rollBack();
                return back()->with('error', 'Product Item is Time-limited. Please enter the expiry date to proceed!');
            }

            if(strtolower($productDetails->prod_unit) !== strtolower($particular->unit)) {
                DB::rollBack();
                return back()->with('error', 'The unit of measurement of the particular differs from the one in the product record: ' . $productDetails->prod_unit);
            }

            $previousInventoryTransaction = $this->productService->getPreviousProductInventoryTransaction($productDetails->id, $formattedDate);
            $currentStock = (int)($previousInventoryTransaction->current_stock ?? 0) + (int)$particular->qty;

            $succeedingTransactions = $this->productService->getSucceedingProductInventoryTransaction($productDetails->id, $formattedDate);

            $productInventoryInfo = [
                'type' => 'purchase',
                'qty' => $particular->qty,
                'refNo' => $particular->id,
                'prodId' => $productDetails->id,
                'price' => $productDetails->price,
                'date_expiry' => $particular->date_expiry,
                'currentStock' => $currentStock,
                'dateReceived' => $formattedDate,
                'user' => $userId,
            ];

            $this->createInventoryTransaction($productInventoryInfo, $succeedingTransactions);
            $this->updateProductInventory($productInventoryInfo);
            $this->updateProductPrice($productInventoryInfo);
            
            $particular->update(['status' => 'completed', 'updated_by' => $userId]);

            $iarTransaction = IarTransaction::findOrFail($particular->air_id);
            $iarTransaction = $iarTransaction->load(['iarParticulars' => function($query) {
                $query->where('status', 'pending');
            }]);

            if($iarTransaction->iarParticulars->isNotEmpty()) {
                DB::commit();
                return redirect()->back()->with(['message' => 'Item No.' . $particular->item_no . ' was added to the Product Inventory!!']);
            }

            $iarTransaction->update(['status' => 'completed', 'updated_by' => $userId]);

            DB::commit();
            return redirect()->route('iar')->with(['message' => 'Transaction no. ' . $iarTransaction->sdi_iar_id . ' has been removed from the list due to no pending particulars.']);
        } catch (\Exception $e) {
            Log::error("Accepting IAR Particular Failed: ", [
                'user' => Auth::user()->name,
                'error' => $e->getMessage(),
                'data' => $request->toArray(),
            ]);
            return back()->with(['error' => 'Proccessing IAR Particular Failed. Please try again!']);
        }
    }

    public function acceptIarParticularAll(Request $request)
    {
        DB::beginTransaction();

        $formattedDate = $this->productService->defaultDateFormat($request->dateReceive);
        $countParticularsToUpdated = 0;
        $userId = Auth::id();

        if (!$formattedDate || !$this->productService->isDateValid($formattedDate)) {
            DB::rollBack();
            return back()->with('error', 'Invalid Date. Please try again!');
        }

        try {
            foreach ($request->particulars as $particular) {
                $productDetails = $this->validateProduct($particular['stockNo']);

                if(!$productDetails) {
                    $countParticularsToUpdated += 1;
                    continue;
                }

                if($productDetails->has_expiry && !$particular['expiry']) {
                    $countParticularsToUpdated += 1;
                    continue;
                }
    
                if(strtolower($productDetails->prod_unit) !== strtolower($particular['unit'])) {
                    $countParticularsToUpdated += 1;
                    continue;
                }

                $previousInventoryTransaction = $this->productService->getPreviousProductInventoryTransaction($productDetails->id, $formattedDate);
                $currentStock = (int)($previousInventoryTransaction->current_stock ?? 0) + (int)$particular['quantity'];

                $succeedingTransactions = $this->productService->getSucceedingProductInventoryTransaction($productDetails->id, $formattedDate);

                $productInventoryInfo = [
                    'type' => 'purchase',
                    'qty' => $particular['quantity'],
                    'refNo' => $particular['pId'],
                    'prodId' => $productDetails->id,
                    'price' => $productDetails->price,
                    'date_expiry' => $particular['expiry'],
                    'currentStock' => $currentStock,
                    'user' => $userId,
                    'dateReceived' => $formattedDate,
                ];

                $this->createInventoryTransaction($productInventoryInfo, $succeedingTransactions);
                $this->updateProductInventory($productInventoryInfo);
                $this->updateProductPrice($productInventoryInfo);

                $particularDetails = $this->getIarParticular($particular['pId']);
                $particularDetails->update(['status' => 'completed', 'updated_by' => $userId]);
            };

            if($countParticularsToUpdated) {
                DB::commit();
                return redirect()->back()->with(['error' => $countParticularsToUpdated .' item/s were not processed due to the required criteria not meet: [Stock No# | Unit of Measure | Product Expiry]. Please update the product information and try again!']);
            }

            $iarTransaction = $this->getIarTransaction($particularDetails->air_id);
            $iarTransaction->update(['status' => 'completed', 'updated_by' => $userId]);

            DB::commit();
            return redirect()->route('iar')->with(['message' => 'Transaction no. ' . $iarTransaction->sdi_iar_id . ' has been removed from the list due to no pending particulars.']);
        } catch(\Exception $e) {
            Log::error("Accepting All IAR Particular Failed: ", [
                'user' => Auth::user()->name,
                'error' => $e->getMessage(),
                'data' => $request->toArray()
            ]);
            return back()->with(['error' => 'Proccessing All IAR Particular Failed. Please try again!']);
        }
    }

    public function updateIarParticular(Request $request)
    {
        DB::beginTransaction();

        try {
            $userId = Auth::id();

            $particularInfo = IarParticular::findOrFail($request->pid);
            $productDetails = $this->validateProduct($request->stockNo);

            if(!$productDetails) {
                DB::rollBack();
                return back()->with('error', 'Product Item not found!');
            }

            if(strtolower($request->parUnit) != strtolower($productDetails->prod_unit)){
                DB::rollBack();
                return back()->with('error', 'Product and IAR particular units of measurement do not match!');
            }

            $particularInfo->update([
                'stock_no' => $productDetails->prod_newNo,
                'unit' => ucfirst($request->parUnit),
                'qty' => $request->parQty,
                'updated_by' => $userId, 
                'date_expiry' => $request->expiry
            ]);

            DB::commit();
            return redirect()->back()->with(['message' => 'Item No.' . $particularInfo->item_no . ' updated successfully!!']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Updating IAR Particular Failed: ", [
                'user' => Auth::user()->name,
                'error' => $e->getMessage(),
                'data' => $request->toArray()
            ]);
            return back()->with(['error' => 'Updating IAR Particular Failed. Please try again!']);
        }
    }

    public function rejectIarParticular(Request $request)
    {
        DB::beginTransaction();
        
        try {
            $userId = Auth::id();
            $particular = IarParticular::findOrFail($request->pid);

            $particular->update(['status' => 'failed', 'updated_by' => $userId]);

            $iarTransaction = IarTransaction::with(['iarParticulars' => fn($q) => $q->where('status', 'pending')])
                ->findOrFail($particular->air_id);
            
            if($iarTransaction->iarParticulars->isNotEmpty()) {
                DB::commit();
                return redirect()->back()->with(['message' => 'Item No.' . $particular->item_no . ' has been rejected successfully!!']);
            }

            $iarTransaction->update(['status' => 'completed', 'updated_by' => $userId]);

            DB::commit();
            return redirect()->route('iar')->with(['message' => 'Transaction no. ' . $iarTransaction->sdi_iar_id . ' has been removed from the list due to no pending particulars.']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Reject IAR Particular Failed: ", [
                'user' => Auth::user()->name,
                'error' => $e->getMessage(),
                'data' => $request->toArray()
            ]);
            return back()->with(['error' => 'Rejecting IAR Particular Failed. Please try again!']);
        }
    }

    public function rejectAllParticular(Request $request)
    {
        DB::beginTransaction();

        $userId = Auth::id();

        try {

            foreach ($request->particulars as $particular) {
                $particularInfo = $this->getIarParticular($particular['pId']);
                $particularInfo->update(['status' => 'failed', 'updated_by' => $userId]);
            };
            
            $iarTransaction = $this->getIarTransaction($particularInfo->air_id);
            $iarTransaction->update(['status' => 'completed', 'updated_by' => $userId]);

            DB::commit();
            return redirect()->route('iar')->with(['message' => 'Transaction no. ' . $iarTransaction->sdi_iar_id . ' has been removed from the list due to no pending particulars.']);
            
        } catch(\Exception $e) {
            DB::rollBack();
            Log::error("Reject IAR Particular Failed: ", [
                'user' => Auth::user()->name,
                'error' => $e->getMessage(),
                'data' => $request->toArray()
            ]);
            return back()->with(['error' => 'Rejecting IAR Particular Failed. Please try again!']);
        }
    }

    private function verifyIarExistence($request) {
        $record = IarTransaction::withTrashed()->where('sdi_iar_id', $request)->exists();
        return $record;
    }

    private function validateProduct($request)
    {
        if (preg_match("/^\d{2}-\d{2,3}-\d{2,4}$/", $request)) {
            $product = Product::withTrashed()->where('prod_newNo', $request)->first();
            if ($product) {
                return $product;
            } else {
                return false;
            }
        }
        elseif (preg_match("/^\d{2}-\d{4}$/", $request)) {
            if (preg_match('/-(\d+)$/', $request, $matches)) {
                if (isset($matches[1])) {
                    $product = Product::withTrashed()->where('prod_oldNo', $matches[1])->first();
                    if ($product) {
                        return $product;
                    } else {
                        return false;
                    }
                } else {
                    return false;
                }
            }
        }  else {
            return false;
        }
    }

    private function createInventoryTransaction(iterable $request, iterable $transactions): void
    {        
        ProductInventoryTransaction::create([
            'type' => $request['type'],
            'qty' => $request['qty'],
            'stock_qty' => $request['qty'],
            'ref_no' => $request['refNo'],
            'prod_id' => $request['prodId'],
            'date_expiry' => $request['date_expiry'],
            'current_stock' => $request['currentStock'],
            'created_by' => $request['user'],
            'created_at' => $request['dateReceived'],
        ]);

        $this->productService->updateInventoryTransactionsCurrentStock($transactions, $request['currentStock']);
    }

    private function updateProductInventory($request)
    {
        $productExist = $this->getProductInventoryId($request['prodId']);

        if($productExist) {
            $productExist->qty_on_stock += $request['qty'];
            $productExist->qty_purchase += $request['qty'];
            $productExist->updated_by = $request['user'];
            $productExist->save();
        } else {
            return ProductInventory::create([
                'qty_on_stock' => $request['qty'],
                'qty_purchase' => $request['qty'],
                'prod_id' => $request['prodId'],
                'updated_by' => $request['user'],
            ]);
        }
    }

    private function updateProductPrice($product) {
        $latestPrice = ProductPrice::where('prod_id', $product['prodId'])->orderBy('created_at', 'desc')->first();

        if($product['price'] != null && $latestPrice->prod_price > $product['price']){
            ProductPrice::create([
                'prod_price' => $product['prodPrice'],
                'prod_id' => $product['prodId'],
            ]);
        }
    }

    private function getAllActiveProducts() {
        $products = Product::withTrashed()->get(['prod_newNo', 'prod_desc', 'prod_unit', 'has_expiry']);

        return $products->map(fn($product) => [
            'stockNo' => $product->prod_newNo,
            'desc' => $product->prod_desc,
            'unit' => $product->prod_unit,
            'expiry'  => $product->has_expiry,
        ]);
    }

    private function getIarParticular($iarParId) {
        $particular = IarParticular::findOrFail($iarParId);
        return $particular;
    }

    private function getIarTransaction($airTranId) {
        $transaction = IarTransaction::findOrFail($airTranId);
        return $transaction;
    }

    private function getCurrentStockInventory($prodId)
    {
        $inventoryDetails = ProductInventory::where('prod_id', $prodId)->first();
        return $inventoryDetails->qty_on_stock ?? 0;
    }

    private function getProductInventoryId($productId) {
        return ProductInventory::where('prod_id', $productId)->first();
    }

    private function processCreationOfIarTransaction(array $iar): ?IarTransaction
    {
        return IarTransaction::create([
            'sdi_iar_id' => $iar['air_id'],
            'iar_no' => $iar['air_no'],
            'po_no' => $iar['po_no'],
            'supplier' => $iar['name'],
            'date' => $iar['air_date'],
        ]);
    }

    private function processCreationOfIarParticulars(array $particular, int $IarId): ?IarParticular
    {
        $rawItemNo = trim(str_replace('`', '', $particular['item_no']));
        return IarParticular::create([
            'item_no' => ctype_digit($rawItemNo) ? (int) $rawItemNo : 0,
            'stock_no' => $particular['stock_no'],
            'unit' => $particular['unit'],
            'description' => $particular['description'],
            'qty' => $particular['quantity'],
            'price' => (float)$particular['unit_cost'],
            'air_id' => $IarId,
        ]);
    }
}
