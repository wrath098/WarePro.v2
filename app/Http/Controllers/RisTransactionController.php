<?php

namespace App\Http\Controllers;

use App\Models\Office;
use App\Models\PpmpParticular;
use App\Models\Product;
use App\Models\ProductInventory;
use App\Models\ProductInventoryTransaction;
use App\Models\RisTransaction;
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
        $risTransaction = $this->getRisTransactions();
        return Inertia::render('Ris/RisLogs', ['transactions' => $risTransaction]);
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

    public function store(Request $request)
    {
        DB::beginTransaction();
        $products = json_decode($request->requestProducts, true);
        $formattedDate = $this->productService->defaultDateFormat($request->risDate);

        if (!$formattedDate || !$this->productService->isDateValid($formattedDate)) {
            DB::rollBack();
            return redirect()->back()->with(['error' => 'Invalid date format or year!']);
        }

        $risData = [
            'risNo' => $request->risNo,
            'officeId' => $request->officeId,
            'receivedBy' => $request->receivedBy,
            'risDate' => $formattedDate,
            'remarks' => $request->remarks,
            'user' => Auth::id(),
        ];

        if ($risData['officeId'] == "others") {
            $this->storeOtherOfficeRequest($risData, $products, $formattedDate);
            DB::commit();
            return redirect()->route('ris.display.logs')->with(['message' => 'RIS created successfully!']);
        }

        if ($request->file) {
            try {
                $path = $this->handleFileUpload($request->file);
                $risData['path'] = $path;
            } catch (\Exception $e) {
                DB::rollback();
                Log::error("File upload failed: " . $e->getMessage());
                return redirect()->back()->with(['error' => 'File upload failed']);
            }
        }

        try {
            foreach ($products as $product) {
                $risData['qty'] = $product['qty'];
                $risData['unit'] = $product['prodUnit'];
                $risData['prodId'] = $product['prodId'];
                
                $isProductAvailable = $this->validateAvailability($risData);
                if($isProductAvailable !== true) {
                    DB::rollback();
                    return redirect()->back()->with(['error' => 'The available quantity for product no. ' . $product['prodStockNo'] . ' is ' . $isProductAvailable . ' ' . $product['prodUnit'] . '.']);
                }

                $totalTreshold = (int) $product['treshFirstQty'] + (int) $product['treshSecondQty'];
                $totalReleased = (int) $product['releasedQty'] + (int) $product['qty'];

                if($totalReleased > $totalTreshold) {
                    DB::rollback();
                    throw new \Exception('The inputted quantity of the product exceeds the requested quantity of the selected office.!');
                }

                $previousInventoryTransaction = $this->productService->getPreviousProductInventoryTransaction($product['prodId'], $formattedDate);
                $currentStock = (int)($previousInventoryTransaction->current_stock ?? 0) + (int)$product['qty'];

                $succeedingTransactions = $this->productService->getSucceedingProductInventoryTransaction($product['prodId'], $formattedDate);

                $createRisTransaction = $this->createRis($risData);
                $productInventoryTransaction = $this->createInventoryTransaction($product, $risData, $createRisTransaction->id, $currentStock , $succeedingTransactions);
                $this->updateQuantity($risData);
                $this->updateInventoryTransaction($risData);
                $this->updateReleasedItemQtyOnPpmp($product);
                $this->moveToTrashProductInventoryTransaction($productInventoryTransaction);
            }   

        DB::commit();
        return redirect()->route('ris.display.logs')->with(['message' => 'RIS created successfully!']);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error("Error creating RIS transaction: " . $e->getMessage());
            return redirect()->back()->with(['error' => $e->getMessage()]);
        }
    }

    private function storeOtherOfficeRequest(iterable $risData, iterable $requestedProducts, string $formattedDate)
    {
        foreach ($requestedProducts as $product) {
            $risData['qty'] = $product['qty'];
            $risData['unit'] = $product['prodUnit'];
            $risData['prodId'] = $product['prodId'];
            
            $isProductAvailable = $this->validateAvailability($risData);
            if($isProductAvailable !== true) {
                DB::rollback();
                return redirect()->back()->with(['error' => 'The available quantity for product no. ' . $product['prodStockNo'] . ' is ' . $isProductAvailable . ' ' . $product['prodUnit'] . '.']);
            }

            $previousInventoryTransaction = $this->productService->getPreviousProductInventoryTransaction($product['prodId'], $formattedDate);
            $currentStock = (int)($previousInventoryTransaction->current_stock ?? 0) + (int)$product['qty'];

            $succeedingTransactions = $this->productService->getSucceedingProductInventoryTransaction($product['prodId'], $formattedDate);

            $createRisTransaction = $this->createRis($risData);
            $productInventoryTransaction = $this->createInventoryTransaction($product, $risData, $createRisTransaction->id, $currentStock, $succeedingTransactions);
            $this->updateQuantity($risData);
            $this->updateInventoryTransaction($risData);
            $this->moveToTrashProductInventoryTransaction($productInventoryTransaction);
        }
    }

    private function handleFileUpload($file)
    {
        $path = $file->storeAs('uploads/ris', $file->getClientOriginalName());
        return Storage::url($path);
    }

    private function createRis($requestData) {
        $officeId = $requestData['officeId'] == "others" ? null : $requestData['officeId'];

        return RisTransaction::create([
            'ris_no' => $requestData['risNo'],
            'qty' => $requestData['qty'],
            'unit' => $requestData['unit'],
            'issued_to' => $requestData['receivedBy'],
            'remarks' => $requestData['remarks'],
            'prod_id' => $requestData['prodId'],
            'office_id' => $officeId,
            'created_by' => $requestData['user'],
            'created_at' => $requestData['risDate'],
            'attachment' => $requestData['path'] ?? null,
        ]);
    }

    private function createInventoryTransaction(iterable $product, iterable $risData, int $risId, int $currentStock, iterable $transactions)
    {
        $query = ProductInventoryTransaction::create([
            'type' => 'issuance',
            'qty' => $product['qty'],
            'current_stock' => $currentStock,
            'prodInv_id' => $product['prodInvId'],
            'ref_no' => $risId,
            'prod_id' => $product['prodId'],
            'created_by' => $risData['user'] ?? null,
            'created_at' => $risData['risDate'],
        ]);

        $this->productService->updateInventoryTransactionsCurrentStock($transactions, $currentStock);

        return $query;
    }

    public function validateAvailability($requestData)
    {
        $productQuantity = ProductInventory::where('prod_id', $requestData['prodId'])->first();
        $quantity = $productQuantity ? $productQuantity->qty_on_stock : 0;

        if ($quantity >= $requestData['qty']) {
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

    private function updateQuantity($requestData)
    {
        $productQuantity = ProductInventory::where('prod_id', $requestData['prodId'])->lockForUpdate()->first();
        $productQuantity->qty_on_stock -= $requestData['qty'];
        $productQuantity->qty_issued += $requestData['qty'];
        return $productQuantity->save();
    }

    private function updateReleasedItemQtyOnPpmp($requestData)
    {
        $itemOnPPmpInfo = PpmpParticular::findOrFail($requestData['id']);
        return $itemOnPPmpInfo->update(['released_qty' => $itemOnPPmpInfo->released_qty += $requestData['qty']]);
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
                'dateReleased'=> $transaction->created_at->format('F d, Y'),
                'releasedBy' => $creator->name,
                'attachment' => $transaction->attachment ?? null,
            ];
        });

        return $transactions;
    }

    private function updateInventoryTransaction($requestData)
    {
        $currentQty = (int) $requestData['qty'];
        $transactions = $this->getIncompleteInventoryTransactions($requestData['prodId']);

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

    private function getCurrentStockInventory($prodId)
    {
        $inventoryDetails = ProductInventory::where('prod_id', $prodId)->first();
        return $inventoryDetails->qty_on_stock ?? 0;
    }

    private function getFilteredIssuanceLogs($fromDate, $toDate)
    {
        $resultLogs = RisTransaction::whereBetween('created_at', [$fromDate, $toDate])
            ->with(['creator', 'requestee', 'productDetails' => function($query) {
                $query->withTrashed();
            }])
            ->orderBy('created_at', 'desc')
            ->get();

        $logs = $resultLogs->map(fn($transaction) => [
            'id' => $transaction->id,
            'risNo' => $transaction->ris_no,
            'stockNo' => $transaction->productDetails->prod_newNo,
            'prodDesc' => $transaction->productDetails->prod_desc,
            'qty' => $transaction->qty,
            'unit' => $transaction->unit,
            'issuedTo' => $transaction->issued_to,
            'officeRequestee' => $transaction->requestee ? $transaction->requestee->office_code : 'Others',
            'dateReleased'=> $transaction->created_at->format('F d, Y'),
            'releasedBy' => $transaction->creator->name,
            'attachment' => $transaction->attachment ?? null,
        ]);

        return $logs ?? '';
    }

    private function risDateValidation($prodId, $date)
    {
        $query = ProductInventoryTransaction::where('prod_id', $prodId)->orderBy('created_at', 'desc')->first();
        return $date > $query->created_at;
    }

    private function defaultDateFormat($inputDate)
    {
        $date = $inputDate;
        $currentTime = Carbon::now()->toTimeString();

        $combinedDateTime = Carbon::parse($date . ' ' . $currentTime)->format('Y-m-d H:i:s');
        
        return $combinedDateTime;
    }
}
