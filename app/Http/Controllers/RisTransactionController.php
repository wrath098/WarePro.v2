<?php

namespace App\Http\Controllers;

use App\Models\Office;
use App\Models\Product;
use App\Models\ProductInventory;
use App\Models\RisTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class RisTransactionController extends Controller
{
    public function create(): Response
    {
        $office = Office::where('office_status', 'active')->select('id', 'office_code')->orderBy('office_code', 'asc')->get();
        return Inertia::render('Ris/Create', ['office' => $office]);
    }

    public function risLogs(): Response
    {
        $risTransaction = RisTransaction::orderBy('created_at', 'desc')->limit(1000)->get();
        return Inertia::render('Ris/RisLogs', ['transactions' => $risTransaction]);
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
                return response()->json(['error' => 'File upload failed'], 500);
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
                
                $this->createRis($risData);
                $this->updateQuantity($risData);
            }
        DB::commit();
        return redirect()->back()->with(['message' => 'RIS created successfully!']);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error("Error creating RIS transaction: " . $e->getMessage());
            return redirect()->back()->with(['error' => 'Failed to create RIS!']);
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
        $productQuantity->qty_issued -= $requestData['qty'];
        return $productQuantity->save();
    }
}
