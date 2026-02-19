<?php

namespace App\Http\Controllers;

use App\Models\Office;
use App\Models\PpmpParticular;
use App\Models\Product;
use App\Models\ProductInventory;
use App\Models\ProductInventoryTransaction;
use App\Models\RisTransaction;
use App\Models\User;
use App\Services\ProductService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
// use Inertia\Response;

class RisTransactionController extends Controller
{
    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function create()
    {
        $office = Office::where('office_status', 'active')->select('id', 'office_code')->orderBy('office_code', 'asc')->get();
        return Inertia::render('Ris/Create', ['office' => $office]);
    }

    public function risTransactions()
    {
        $transaction = $this->getRis();
        return Inertia::render('Ris/Transactions', ['transactions' => $transaction]);
    }

    public function filterRisTransactions(Request $request)
    {   
        $transactions = $this->getRis($request);
        return response()->json(['data' => $transactions]);
    }

    public function ssmi()
    {
        $risTransaction = $this->getRisTransactions();
        $users = User::select('id', 'name', 'position')->get();

        return Inertia::render('Ris/RisLogs', [
            'transactions' => $risTransaction,
            'users' => $users,
        ]);
    }

    public function showAttachment(Request $request)
    {
        $transactionDetails = $this->getRisTransactionInfo((int) $request->transactionId);
        $filePath = storage_path('app/' . str_replace('/storage', '', $transactionDetails->attachment));
        
        if (file_exists($filePath)) {
            return Response::file($filePath);
        }
    
        return abort(404);
    }

    public function showRisItems(Request $request, $transactionId, $issuedTo)
    {
        #TRANSACTIONS WITHIN RIS
        $transaction = RisTransaction::with('productDetails')
            ->where('ris_no', $transactionId)
            ->where('issued_to', $issuedTo)
            ->get();

        #RIS TRANSACTION INFO
        $risInfo = RisTransaction::with(['creator', 'requestee'])
            ->where('ris_no', $transactionId)
            ->where('issued_to', $issuedTo)
            ->first();

        #TRANSFORM RISINFO COLLECTION TO ARRAY
        $transformed = [
            'risNo' => $risInfo->ris_no,
            'officeRequestee' => $risInfo->requestee ? $risInfo->requestee->office_code : 'Others',
            'remarks' => $risInfo->remarks,
            'issuedTo' => $risInfo->issued_to,
            'issuedBy' => $risInfo->creator ? $risInfo->creator->name : '',
            'dateIssued' => $risInfo->ris_date
                ? \Carbon\Carbon::parse($risInfo->ris_date)->format('F d, Y')
                : null,
            'rawDateIssued' => $risInfo->ris_date,
            'noOfItems' => $transaction->count(),
        ];
        
        #RENDER TO PAGE
        return Inertia::render('Ris/TransactionItems', [
            'transactions' => $transaction,
            'risInfo' => $transformed
        ]);
    }

    public function store(Request $request)
    {
        #VALIDATE REQUEST FORM INPUT
        $request->validate([
            'risNo' => 'required|string',
            'receivedBy' => 'required|string',
            'risDate' => 'required|string',
            'remarks' => 'nullable|string',
            'officeId' => 'nullable|string',
            'ppmpYear' => 'required|string',
            'requestProducts' => 'required|string',
        ]);

        #CONVERT REEQUEST PRODUCTS TO ARRAY
        $products = json_decode($request->requestProducts, true);

        #REFORMAT DATE WITH NOW() TIME
        $formattedDate = $this->productService->defaultDateFormat($request->risDate);

        #TRANSPOSED REQUEST FORM INPUT TO COSTUMIZE ARRAY
        $risData = [
            'risNo' => $request->risNo,
            'officeId' => $request->officeId,
            'receivedBy' => $request->receivedBy,
            'risDate' => $formattedDate,
            'remarks' => $request->remarks,
            'user' => Auth::id(),
        ];

        #CHECK FORMATTED DATE IF STILL VALID WITHIN THE GIVEN DATE DURATION
        if (!$formattedDate || !$this->productService->isDateValid($formattedDate)) {
            return back()->with(['error' => 'Invalid date. Please try again!']);
        }

        #STARTS DATABASE TRANSACTION
        DB::beginTransaction();

        #PROCEED IF VALUE IS NOT EQUAL TO "OTHERS"
        if ($risData['officeId'] == "others") {
            $this->storeOtherOfficeRequest($risData, $products, $formattedDate);
            DB::commit();
            return redirect()->back()->with(['message' => 'RIS created successfully!']);
        }

        try {

            #LOOP REQUESTED PRODUCTS
            foreach ($products as $product) {

                #CHECK QUANTITY VALIDITY
                $isProductAvailable = $this->validateAvailability($product['prodId'], $product['requestedQty']);
                if($isProductAvailable !== true) {
                    DB::rollback();
                    return redirect()->back()->with(['error' => 'The available quantity for product no. ' . $product['stockNo'] . ' is ' . $isProductAvailable . ' ' . $product['unit'] . '.']);
                }
                
                #CALCULATE REMAINING QUANTITY
                $nextAvailQty = (int) $product['remainingQty'] - (int) $product['requestedQty'];
                if($nextAvailQty <= -1) {
                    DB::rollback();
                    return redirect()->back()->with(['error' => 'The inputted quantity of the product exceeds the requested quantity of the selected office.!']);
                }

                #GET PRODUCT'S PREVIOUS INVENTORY TRANSACTION 
                $previousInventoryTransaction = $this->productService->getPreviousProductInventoryTransaction($product['prodId'], $formattedDate);
                $currentStock = (int)($previousInventoryTransaction->current_stock ?? 0) - (int)$product['requestedQty'];

                #GET PRODUCT'S SUCCEEDING INVENTORY TRANSACTIONS
                $succeedingTransactions = $this->productService->getSucceedingProductInventoryTransaction($product['prodId'], $formattedDate);

                #CREATE RIS TRANSACTION
                $createRisTransaction = $this->createRis($risData, $product['requestedQty'], $product['prodId'], $product['unit'], $product['id']);
                if (!$createRisTransaction) {
                    return redirect()->back()->with(['error' => 'Failed to create RIS transaction for product ID ' . $product['prodId']]);
                }

                #CREATE PRODUCT INVENTORY TRANSACTION
                $productInventoryTransaction = $this->createInventoryTransaction($product, $risData, $createRisTransaction->id, $currentStock , $succeedingTransactions);
                if (!$productInventoryTransaction) {
                    return redirect()->back()->with(['error' => 'Failed to create inventory transaction for product ID ' . $product['prodId']]);
                }
                
                #UPDATE PRODUCT INVENTORY QUANTITY STOCK
                $this->updateQuantity($product['prodId'], $product['requestedQty']);

                #UPDATE PRODUCT INVENTORY STOCK_QTY AND DISPATCH COLUMN
                $this->updateInventoryTransaction($product['prodId'], $product['requestedQty']);

                #UPDATE QUANTITY RELEASED FROM THE PPMP OWNER
                $this->updateReleasedItemQtyOnPpmp($product['id'], $product['requestedQty']);

                #TEMPORARY DELETE THE TRANSACTION
                $this->moveToTrashProductInventoryTransaction($productInventoryTransaction);
            }   

        DB::commit();
        return redirect()->back()->with(['message' => 'RIS created successfully!']);
        } catch (\Exception $e) {
            Log::error("Proccess RIS Failed: ", [
                'user' => Auth::user()->name,
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->with(['error' => 'Proccessing RIS Failed. Please try again!']);
        }
    }

    public function update(Request $request) 
    {
        #VALIDATE REQUEST FORM INPUT
        $request->validate([
            'risNo' => 'required|string',
            'issuedTo' => 'required|string',
            'dateIssued' => 'required|date',
            'oldData' => 'required|array',
        ]);

        DB::beginTransaction();

        try {

            #TRANSACTIONS WITHIN RIS
            $transactions = RisTransaction::where('ris_no', $request->oldData['risNo'])
                ->where('issued_to', $request->oldData['issuedTo'])
                ->get();

            #UPDATE TRANSACTIONS
            foreach($transactions as $transaction) {
                $transaction->update([
                    'ris_no' => $request->risNo,
                    'issued_to' => $request->issuedTo,
                    'ris_date' => $request->dateIssued
                ]);
            }
            
            #RENDER PAGE IF NO ERROR
            DB::commit();
            return redirect()->route('ris.display.logs')->with('message', 'Updated the RIS Information successfully!');
        } catch (\Exception $e) {
            
            #RENDER PAGE IF THERE IS ERROR
            DB::rollBack();
            Log::error("Updating RIS Failed: ", [
                'user' => Auth::user()->name,
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->with(['error' => 'Updating RIS Failed. Please try again!']);
        
        }
    }

    public function updateParticular(Request $request)
    {
        #VALIDATE FORM REQUEST INPUT
        $request->validate([
            'risParticularId' => 'required',
            'stockNo' => 'nullable|string',
            'proDesc' => 'nullable|string',
            'requestedQty' => 'required',
        ]);

        DB::beginTransaction();

        try{
            #GET RIS PARTICULAR
            $particular = RisTransaction::with(['productDetails', 'releasedBasis'])->findOrFail($request->risParticularId);

            #GET DIFFERENCE OF CURRENT QTY AND NEWLY REQUESTED QTY THEN UPDATE PRODUCT INVENTORY
            $requestedQty = (int) $request['requestedQty'];
            $originalQty = (int) $particular->qty;
            $difference = $requestedQty - $originalQty;

            #CALCULATE REMAINING QUANTITY
            $releasedBasis = $particular->releasedBasis;
            $nextAvailQty = 0;

            if($releasedBasis) {
                $grandTotalOfRequest = (int)$releasedBasis->tresh_first_qty + (int)$particular->tresh_second_qty;
                $availableQuantityOnRequest = $grandTotalOfRequest - (int)$releasedBasis->released_qty;
                $nextAvailQty = $availableQuantityOnRequest - $difference;
            }
            
            if($nextAvailQty <= -1) {
                DB::rollback();
                return redirect()->back()->with(['error' => 'The inputted quantity of the product exceeds the requested quantity of the selected office. Available Quantity : ' . $availableQuantityOnRequest]);
            }

            #UPDATE PRODUCT INVENTORY QUANTITY STOCK
            $this->updateQuantity($particular->prod_id, $difference);

            #UPDATE PRODUCT INVENTORY TRANSACTION
            $this->updateQuantityOnInventoryTransaction($particular->id, $requestedQty, $difference);

            #UPDATE RELEASED QUANTITY FROM THE PPMP OWNER
            if($releasedBasis) {
                $this->updateReleasedItemQtyOnPpmp($releasedBasis->id, $difference);
            }

            #UPDATE RELEASED STOCK_QTY IN PRODUCT INVENTORY TRANSACTION
            $this->updateInventoryTransactionStockQty($particular->prod_id, $difference);

            #UPDATE CURRENT QUANTITY IN PRODUCT INVENTORY TRANSACTIONS
            $this->updateCurrentStock($particular->id, $particular->prod_id, $difference);
            
            #UPDATE RIS TRANSACTION
            $particular->update(['qty' => $request->requestedQty]);

            #RENDER PAGE IF NO ERROR
            DB::commit();
            return redirect()->back()->with('message', 'Updated the RIS Particular successfully!');
        } catch(\Exception $e) {

            #RENDER PAGE IF THERE IS ERROR
            DB::rollBack();
            Log::error("Updating RIS Particular Failed: ", [
                'user' => Auth::user()->name,
                'error_message' => $e->getMessage(),
            ]);

            return redirect()->back()->with(['error' => 'Updating RIS Particular Failed. Please try again!']);
        }
    }

    public function removeParticular(Request $request)
    {
        #VALIDATE INPUT REQUEST FORM
        $request->validate([
            'risParticularId' => 'required|integer'
        ]);

        #FETCH REQUESTED RIS TRANSACTION
        $risTransaction = RisTransaction::with(['productDetails', 'releasedBasis'])->findOrFail($request->risParticularId);
        $releasedBasis = $risTransaction->releasedBasis;

        $originalQty = - (int) $risTransaction->qty;

        DB::beginTransaction();
    

        try {
            #UPDATE PRODUCT INVENTORY QUANTITY STOCK
            $this->updateQuantity($risTransaction->prod_id, $originalQty);

            #UPDATE RELEASED QUANTITY FROM THE PPMP OWNER
            if($releasedBasis) {
                $this->updateReleasedItemQtyOnPpmp($releasedBasis->id, $originalQty);
            }

            #UPDATE RELEASED STOCK_QTY IN PRODUCT INVENTORY TRANSACTION
            $this->updateInventoryTransactionStockQty($risTransaction->prod_id, $originalQty);

            #UPDATE CURRENT QUANTITY IN PRODUCT INVENTORY TRANSACTIONS
            $this->updateCurrentStock($risTransaction->id, $risTransaction->prod_id, $originalQty);

            #GET PRODUCT INVENTORY TRANSACTION INFORMATION
            $inventoryTransaction = ProductInventoryTransaction::withTrashed()
                ->where('ref_no', $risTransaction->id)
                ->where('type', 'issuance')
                ->first();

            $risNumber = $risTransaction->ris_no;
            $risIssuedTo = $risTransaction->issued_to;

            #REMOVE RIS TRANSACTION IN PRODUCT INVENTORY TRANSACTION 
            $inventoryTransaction->forceDelete();
            $risTransaction->forceDelete();
            
            #COUNT TRANSACTION WITHIN RIS
            $noOfTransaction = RisTransaction::where('ris_no', $risNumber)
                ->where('issued_to', $risIssuedTo)
                ->count();

            if($noOfTransaction == 0) {
                #RENDER PAGE IF NO ERROR
                DB::commit();
                return redirect()->route('ris.display.logs')->with('message', 'Deleted the RIS Particular successfully!');
            }

            #RENDER PAGE IF NO ERROR
            DB::commit();
            return redirect()->back()->with('message', 'Deleted the RIS Particular successfully!');
        } catch(\Exception $e) {

            #RENDER PAGE IF THERE IS ERROR
            DB::rollBack();
            Log::error("Removing RIS Particular Failed: ", [
                'user' => Auth::user()->name,
                'error_message' => $e->getMessage(),
            ]);

            return redirect()->back()->with(['error' => 'Removing RIS Particular Failed. Please try again!']);
        }

        
    }

    private function storeOtherOfficeRequest(iterable $risData, iterable $requestedProducts, string $formattedDate)
    {
        #LOOP REQUESTED PRODUCTS
        foreach ($requestedProducts as $product) {

            #CHECK QUANTITY VALIDITY
            $isProductAvailable = $this->validateAvailability($product['prodId'], $product['requestedQty']);
            if($isProductAvailable !== true) {
                DB::rollback();
                return redirect()->back()->with(['error' => 'The available quantity for product no. ' . $product['stockNo'] . ' is ' . $isProductAvailable . ' ' . $product['unit'] . '.']);
            }

            #GET PRODUCT'S PREVIOUS INVENTORY TRANSACTION 
            $previousInventoryTransaction = $this->productService->getPreviousProductInventoryTransaction($product['prodId'], $formattedDate);
            $currentStock = (int)($previousInventoryTransaction->current_stock ?? 0) - (int)$product['requestedQty'];
            
            #GET PRODUCT'S SUCCEEDING INVENTORY TRANSACTIONS
            $succeedingTransactions = $this->productService->getSucceedingProductInventoryTransaction($product['prodId'], $formattedDate);

            #CREATE RIS TRANSACTION
            $createRisTransaction = $this->createRis($risData, $product['requestedQty'], $product['prodId'], $product['unit']);
            if (!$createRisTransaction) {
                return redirect()->back()->with(['error' => 'Failed to create RIS transaction for product ID ' . $product['prodId']]);
            }

            #CREATE PRODUCT INVENTORY TRANSACTION
            $productInventoryTransaction = $this->createInventoryTransaction($product, $risData, $createRisTransaction->id, $currentStock, $succeedingTransactions);
            if (!$productInventoryTransaction) {
                return redirect()->back()->with(['error' => 'Failed to create inventory transaction for product ID ' . $product['prodId']]);
            }

            #UPDATE PRODUCT INVENTORY
            $this->updateQuantity($product['prodId'], $product['requestedQty']);

            #UPDATED STOCK_QTY IN PRODUCT INVENTORY TRANSACTION
            $this->updateInventoryTransaction($product['prodId'], $product['requestedQty']);

            #TEMPORARILY DELETE THE TRANSACTION
            $this->moveToTrashProductInventoryTransaction($productInventoryTransaction);
        }
    }

    private function handleFileUpload($file)
    {
        $path = $file->storeAs('uploads/ris', $file->getClientOriginalName());
        return Storage::url($path);
    }

    private function createRis(iterable $requestData, int $requestedQty, int $prodId, string $prodUnit, $ppmpId = null) {
        $officeId = $requestData['officeId'] == "others" ? null : $requestData['officeId'];

        return RisTransaction::create([
            'ris_no' => $requestData['risNo'],
            'qty' => $requestedQty,
            'unit' => $prodUnit,
            'issued_to' => $requestData['receivedBy'],
            'remarks' => $requestData['remarks'],
            'prod_id' => $prodId,
            'office_id' => $officeId,
            'ppmp_ref_no' => $ppmpId,
            'created_by' => $requestData['user'],
            'created_at' => $requestData['risDate'],
        ]);
    }

    private function createInventoryTransaction(iterable $product, iterable $risData, int $risId, int $currentStock, iterable $transactions)
    {
        #RETURN PRODUCT INVENTORY ID
        $prodInven_id = $product['prodInvId'] ? (int) $product['prodInvId'] : ProductInventory::where('prod_id', $product['prodId'])->value('id');

        #CREATE PRODUCT INVENTORY TRANSACTION
        $query = ProductInventoryTransaction::create([
            'type' => 'issuance',
            'qty' => $product['requestedQty'],
            'current_stock' => $currentStock,
            'prodInv_id' => $prodInven_id,
            'ref_no' => $risId,
            'prod_id' => $product['prodId'],
            'created_by' => $risData['user'] ?? null,
            'created_at' => $risData['risDate'],
        ]);

        #UPDATE PRODUCT INVENTORY TRANSACTION'S CURRENT_STOCK
        $this->productService->updateInventoryTransactionsCurrentStock($transactions, $currentStock);

        return $query;
    }

    public function validateAvailability(int $prodId, int $requestedQty)
    {
        $productQuantity = ProductInventory::where('prod_id', $prodId)->first();
        $quantity = $productQuantity ? $productQuantity->qty_on_stock : 0;

        if ($quantity >= $requestedQty) {
            return true;
        } else {
            return $quantity;
        }
    }

    public function getIssuanceLogs(Request $request)
    {
        $query = $request->input('query');

        $startDate = Carbon::parse($query['startDate']);
        $endDate = Carbon::parse($query['endDate']);

        $customEndDate = $endDate->setTime(23, 59, 59);
        
        $formattedStartDate = $startDate->format('Y-m-d H:i:s');
        $formattedEndDate = $customEndDate->format('Y-m-d H:i:s');

        $resultLogs = $this->getFilteredIssuanceLogs($formattedStartDate, $formattedEndDate);
        return response()->json(['data' => $resultLogs]);
    }

    private function updateQuantity($requestedProdId, $requestedQty)
    {
        $productQuantity = ProductInventory::where('prod_id', $requestedProdId)->lockForUpdate()->first();
        $productQuantity->qty_on_stock -= $requestedQty;
        $productQuantity->qty_issued += $requestedQty;
        return $productQuantity->save();
    }

    private function updateReleasedItemQtyOnPpmp(int $requestProdId, int $requestedQty)
    {
        $itemOnPPmpInfo = PpmpParticular::findOrFail($requestProdId);

        $newReleasedQty = $itemOnPPmpInfo->released_qty + $requestedQty;

        if($itemOnPPmpInfo->update(['released_qty' => $newReleasedQty])) {
            return $itemOnPPmpInfo;
        }

        return false;
    }

    private function getRisTransactions() {
        $transactions = RisTransaction::with(['creator', 'requestee', 'productDetails' => function($query) {
                $query->withTrashed();
            }])
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get();

        $transactions = $transactions->map(function ($transaction){

            $requestee = $transaction->requestee;
            $productDetails = $transaction->productDetails;
            $creator = $transaction->creator;

            return [
                'id' => $transaction->id,
                'risNo' => $transaction->ris_no,
                'stockNo' => $productDetails->prod_newNo,
                'prodDesc' => $productDetails->prod_desc,
                'qty' => $transaction->qty,
                'unit' => $transaction->unit,
                'issuedTo' => $transaction->issued_to,
                'officeRequestee' => $requestee ? $requestee->office_code : 'Other',
                'dateReleased'=> $transaction->created_at->format('M d, Y'),
                'releasedBy' => $creator->name,
                'attachment' => $transaction->attachment ?? null,
            ];
        });

        return $transactions;
    }

    private function updateInventoryTransaction(int $requestedProdId, int $requestedQty)
    {
        $currentQty = (int) $requestedQty;
        $transactions = $this->getIncompleteInventoryTransactions($requestedProdId);

        foreach ($transactions as $transaction) {
            $stockQty = (int) $transaction->stock_qty;

            if ($currentQty === 0) {
                break;
            }

            if($currentQty != 0  && $currentQty < (int) $stockQty) {
                $transactionQty = $stockQty - $currentQty;
                $currentQty = 0;

                $transaction->update(['stock_qty' => $transactionQty]);
                break;
            } elseif($currentQty != 0  && $currentQty >= $stockQty) {
                $currentQty -= $stockQty;
                $transactionQty = 0;

                $transaction->update(['stock_qty' => $transactionQty, 'dispatch' => 'complete']);
                $transaction->delete();
                continue;
            } else {
                return 'this is an error message!!!!';
            }
        }

        return $transactions;
    }

    private function getIncompleteInventoryTransactions($prodId)
    {
        $transactions = ProductInventoryTransaction::where('prod_id', $prodId)
                        ->where('dispatch', 'incomplete')
                        ->orderBy('created_at', 'asc')
                        ->take(20)
                        ->get();

        return $transactions;
    }

    private function getRisTransactionInfo($transactionId)
    {
        $transaction = RisTransaction::findOrFail($transactionId);
        return $transaction;
    }

    private function moveToTrashProductInventoryTransaction(ProductInventoryTransaction  $productInventory)
    {
        return $productInventory->delete();
    }

    private function getFilteredIssuanceLogs($fromDate, $toDate)
    {
        $resultLogs = RisTransaction::whereBetween('created_at', [$fromDate, $toDate])
            ->with(['creator', 'requestee', 'productDetails' => function($query) {
                $query->withTrashed();
            }])
            ->latest('created_at')
            ->get();

        return $resultLogs->map(fn($transaction) => [
            'id' => $transaction->id,
            'risNo' => $transaction->ris_no,
            'stockNo' => $transaction->productDetails->prod_newNo,
            'prodDesc' => $transaction->productDetails->prod_desc,
            'qty' => $transaction->qty,
            'unit' => $transaction->unit,
            'issuedTo' => $transaction->issued_to,
            'officeRequestee' => $transaction->requestee ? $transaction->requestee->office_code : 'Others',
            'dateReleased'=> $transaction->created_at->format('M d, Y'),
            'releasedBy' => $transaction->creator->name,
            'attachment' => $transaction->attachment ?? null,
        ])->values();
    }

    private function getRis($request = null)
    {
        if($request) {
            $period = $request->input('period');

            $transactions = RisTransaction::select(
                'ris_no',
                'issued_to',
                'office_id',
                'created_by',
                'remarks',
                'created_at',
                DB::raw('COUNT(id) as transaction_count')
            )
            ->with(['creator', 'requestee'])
            ->when(isset($period['year']), function($q) use ($period) {
                $q->whereYear('created_at', $period['year']);
            })
            ->when(isset($period['month']), function($q) use ($period) {
                $month = str_pad($period['month'], 2, '0', STR_PAD_LEFT);
                $q->whereMonth('created_at', $month);
            })
            ->when(isset($period['day']), function($q) use ($period) {
                $day = str_pad($period['day'], 2, '0', STR_PAD_LEFT);
                $q->whereDay('created_at', $day);
            })
            ->groupBy('ris_no', 'issued_to', 'office_id', 'created_by', 'remarks', 'created_at')
            ->orderByDesc('ris_no')
            ->get();

        } else {
            $transactions = RisTransaction::select('ris_no', 'issued_to', 'office_id', 'created_by', 'remarks', DB::raw('COUNT(id) as transaction_count'))
                ->with(['creator', 'requestee'])
                ->groupBy('ris_no', 'issued_to', 'office_id', 'created_by', 'remarks')
                ->orderByDesc('ris_no')
                ->limit(200)
                ->get();
        }
        
        return $transactions->map(fn($transaction) => [
            'risNo' => $transaction->ris_no,
            'officeRequestee' => $transaction->requestee ? $transaction->requestee->office_code : 'Others',
            'requesteeRemarks' => $transaction->remarks,
            'issuedTo' => $transaction->issued_to,
            'releasedBy' => $transaction->creator->name,
            'noOfItems' => $transaction->transaction_count,
        ]);
    }

    private function updateInventoryTransactionStockQty(int $requestedProdId, int $requestedQty)
    {
        $transaction = ProductInventoryTransaction::where('prod_id', $requestedProdId)
            ->where('dispatch', 'incomplete')
            ->orderBy('created_at', 'asc')
            ->first();

        if(!$transaction) {
            $transaction = ProductInventoryTransaction::withTrashed()
                ->where('prod_id', $requestedProdId)
                ->where('dispatch', 'complete')
                ->orderBy('created_at', 'desc')
                ->first();
        }

        $stockQty = (int) $transaction->stock_qty - $requestedQty;

        if($transaction->update(['stock_qty' => $stockQty, 'deleted_at' => null])) {
            return $transaction;
        }

        return false;
    }

    private function updateQuantityOnInventoryTransaction(int $refId, int $requestQty, int $difference)
    {
        $transaction = ProductInventoryTransaction::withTrashed()
            ->where('ref_no', $refId)
            ->where('type', 'issuance')
            ->first();

        $adjustedCurrentStock = $transaction->current_stock - $difference;

        $updateData = [
            'qty' => $requestQty,
            'current_stock' => $adjustedCurrentStock
        ];

        if($transaction->update($updateData)) {
            return $transaction;
        }

        return false;
    }

    public function updateCurrentStock(int $risId, int $prodId, int  $currentStock)
    {
        $risTransaction = ProductInventoryTransaction::withTrashed()
            ->where('ref_no', $risId)
            ->where('type', 'issuance')
            ->first();

        $date = $risTransaction ? $risTransaction->created_at : null;

        $succeedingTransactions = ProductInventoryTransaction::withTrashed()
            ->where('prod_id', $prodId)
            ->where('created_at', '>' , $date)
            ->get();

        $updates = [];
        
        foreach ($succeedingTransactions as $transaction) {
            $runningStock = (int)$transaction->current_stock - $currentStock;
            $updates[] = ['id' => $transaction->id, 'current_stock' => $runningStock];
        }

        ProductInventoryTransaction::upsert($updates, ['id'], ['current_stock']);
    }
}
