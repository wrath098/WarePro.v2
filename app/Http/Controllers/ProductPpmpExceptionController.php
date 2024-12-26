<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductPpmpException;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class ProductPpmpExceptionController extends Controller
{
    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function index(Request $request)
    {
        $list = Product::where('prod_status', 'active')
                ->get()
                ->map(fn($item) => [
                    'id' => $item->id,
                    'code' => $item->prod_newNo,
                    'desc' => $item->prod_desc,
                    'unit' => $item->prod_unit,
                ]);

        $unModified = ProductPpmpException::where('status', 'active')
                    ->orderBy('created_at', 'desc')
                    ->get();

        $productIds = $unModified->pluck('prod_id');
        $productsData = Product::whereIn('id', $productIds)->get()->keyBy('id');

        $products = $unModified->map( function ($product) use ($productsData) {
            $data = $productsData->get($product->prod_id);

            if (!$data) {
                return null;
            }

            return [
                'id' => $product->id,
                'year' => $product->year,
                'code' => $data->prod_newNo,
                'oldCode' => $data->prod_oldNo,
                'desc' => $data->prod_desc,
                'unit' => $data->prod_unit,
            ];

        })->filter();

        return Inertia::render('Product/Unmodified', [
            'products' => $products,
            'list' => $list,
            'authUserId' => Auth::id()
        ]);
    }

    public function store(Request $request)
    {
        try {
            $item = Product::findOrFail($request->prod['id']);

            $validateList = ProductPpmpException::where('prod_id', $item->id)
                ->where('year', $request['ppmpYear'])
                ->first();
            
            if($validateList) {
                $validateList->update(['status'=> 'active']);
                return redirect()->back()
                    ->with(['error' => 'Product Code is already exist on list!']);
            }

            ProductPpmpException::create([
                'year' => $request['ppmpYear'],
                'prod_id' => $item->id,
            ]);

            return redirect()->back()
            ->with(['message' => 'Product has been added successfully!']);

        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return redirect()->back()
                ->with(['error' => 'Product Code does not exist.']);
        }
    }

    public function deactivate(Request $request)
    {
        try {
            $productExempt = ProductPpmpException::findOrFail($request->input('prodId'));

            $productExempt->update(['status' => 'deactivate']);

            return redirect()->back()
                ->with(['message' => 'Product has been moved to trash successfully!']);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return redirect()->back()
                ->with(['error' => 'An unexpected error occurred.']);
        }
    }
}