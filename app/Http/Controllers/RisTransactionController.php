<?php

namespace App\Http\Controllers;

use App\Models\Office;
use App\Models\Product;
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
        $product = Product::where('prod_status', 'active')->get();
        $office = Office::where('office_status', 'active')->select('id', 'office_code')->orderBy('office_code', 'asc')->get();
        return Inertia::render('Ris/Create', ['products' => $product, 'office' => $office]);
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
                $risData['unit'] = $product['prod_unit'];
                $risData['prodId'] = $product['id'];

                $this->createRis($risData);
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

    private function updateQuantity()
    {
        
    }
}
