<?php

namespace App\Http\Controllers;

use App\Models\Office;
use App\Models\PpmpParticular;
use App\Models\Product;
use App\Models\ProductInventory;
use App\Models\ProductInventoryTransaction;
use App\Models\RisTransaction;
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

        $risData = [
            'risNo' => $request->risNo,
            'officeId' => $request->officeId,
            'receivedBy' => $request->receivedBy,
            'user' => Auth::id(),
        ];

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
                $currentStock = (int)$this->getCurrentStockInventory($product['prodId']) - $product['qty'];
                $createRisTransaction = $this->createRis($risData);
                $productInventoryTransaction = $this->createInventoryTransaction($product, $risData, $createRisTransaction, $currentStock);
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

    private function handleFileUpload($file)
    {
        $path = $file->storeAs('uploads/ris', $file->getClientOriginalName());
        return Storage::url($path);
    }

    private function createRis($requestData) {
        return RisTransaction::create([
            'ris_no' => $requestData['risNo'],
            'qty' => $requestData['qty'],
            'unit' => $requestData['unit'],
            'issued_to' => $requestData['receivedBy'],
            'prod_id' => $requestData['prodId'],
            'office_id' => $requestData['officeId'],
            'created_by' => $requestData['user'],
            'attachment' => $requestData['path'] ?? null,
        ]);
    }

    private function createInventoryTransaction($product, $risData, $risId, $currentStock)
    {
        return ProductInventoryTransaction::create([
            'type' => 'issuance',
            'qty' => $product['qty'],
            'current_stock' => $currentStock,
            'prodInv_id' => $product['prodInvId'],
            'ref_id' => $risId,
            'prod_id' => $product['prodId'],
            'created_by' => $risData['user'] ?? null,
        ]);
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
        $transactions = RisTransaction::with(['creator', 'productDetails', 'requestee'])->orderBy('created_at', 'desc')->limit(2000)->get();

        $transactions = $transactions->map(fn($transaction) => [
            'id' => $transaction->id,
            'risNo' => $transaction->ris_no,
            'stockNo' => $transaction->productDetails->prod_newNo,
            'prodDesc' => $transaction->productDetails->prod_desc,
            'qty' => $transaction->qty,
            'unit' => $transaction->unit,
            'issuedTo' => $transaction->issued_to,
            'officeRequestee' => $transaction->requestee->office_code,
            'dateReleased'=> $transaction->created_at->format('Y-m-d H:i:s'),
            'releasedBy' => $transaction->creator->name,
            'attachment' => $transaction->attachment ?? null,
        ]);

        return $transactions ?? '';
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
}
