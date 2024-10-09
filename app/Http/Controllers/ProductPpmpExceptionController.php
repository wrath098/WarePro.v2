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

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');

        $unModified = ProductPpmpException::where('status', 'active')->orderBy('created_at', 'desc')
            ->when($search, function ($query) use ($search) {
                $query->whereHas('product', function ($q) use ($search) {
                    $q->where('prod_newNo', 'like', '%' . $search . '%')
                    ->orWhere('prod_desc', 'like', '%' . $search . '%')
                    ->orWhere('prod_oldNo', 'like', '%' . $search . '%');
                });
            })
            ->paginate(10)
            ->withQueryString();

        $products = $unModified->getCollection()->map( function ($product) {
            $data = Product::find($product->prod_id);

            if (!$data) {
                return null;
            }

            return [
                'id' => $product->id,
                'year' => $product->year,
                'code' => $data->prod_newNo,
                'oldCode' => $data->prod_oldNo,
                'desc' => '( ' . $this->productService->getItemName($data->item_id) . ' ) ' . $data->prod_desc,
                'unit' => $data->prod_unit,
            ];

        })->filter();

        $unModified->setCollection($products);

        return Inertia::render('Product/Unmodified', [
            'products' => $unModified,
            'filters' => $request->only('search'),
            'authUserId' => Auth::id()
        ]);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'prodCode' => 'required|string|max:12',
            'prodYear' => 'required|numeric',
        ]);
    
        try {
            $validateProduct = Product::where('prod_newNo', $validatedData['prodCode'])
                ->where('prod_status', 'active')
                ->get();

            $validateList = ProductPpmpException::where('prod_id', $validateProduct->first()->id)
                ->where('year', $validatedData['prodYear'])
                ->first();
            
            if($validateList) {
                $validateList->update(['status'=> 'active']);
                return redirect()->back()
                    ->with(['error' => 'Product Code is already exist on list!']);
            }

            if ($validateProduct && $validateProduct->count() > 1) {
                return redirect()->back()
                    ->with(['error' => 'Product Code has been used by more than products. Please check your product list!']);
            } else if ( $validateProduct && $validateProduct->count() === 1) {
                ProductPpmpException::create([
                    'year' => $validatedData['prodYear'],
                    'prod_id' => $validateProduct->first()->id,
                    ]);

                    return redirect()->back()
                    ->with(['message' => 'Product has been added to the Unmodified successfully!']);
            } else {
                return redirect()->back()
                    ->with(['error' => 'Product Code does not exist.']);
            }

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