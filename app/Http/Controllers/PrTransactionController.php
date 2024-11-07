<?php

namespace App\Http\Controllers;

use App\Models\PpmpTransaction;
use App\Models\PrTransaction;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PrTransactionController extends Controller
{
    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        $transactions = PpmpTransaction::select('ppmp_type', 'ppmp_year')
        ->where(function($query) {
            $query->where('ppmp_type', 'consolidated')
                ->where('ppmp_status', 'approved');
            })
            ->orWhere(function($query) {
                    $query->where('ppmp_type', 'emergency');
                })
            ->get();

        
        $resulToPr = $transactions->groupBy('ppmp_type')->map(function ($group) {
            $years = $group->groupBy('ppmp_year')->map(function ($yearGroup) {
                return [
                    'ppmp_year' => $yearGroup->first()->ppmp_year,
                ];
            })->values()->all();
        
            return [
                'ppmp_type' => $group->first()->ppmp_type,
                'years' => $years
            ];
        })->values()->all();

        return Inertia::render('Pr/Index', [
            'toPr' =>  $resulToPr,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {   
        $ppmpTransactions = PpmpTransaction::with('particulars')
            ->where('ppmp_status', 'approved')
            ->where('ppmp_type', 'individual')
            ->where('ppmp_year', $request->selectedYear)
            ->get();

        $priceAdjustment = $ppmpTransactions->first()->price_adjustment;
        $semester = $request->semester;
        $qtyAdjustment = $request->qtyAdjust;

        $groupParticulars = $ppmpTransactions->flatMap(function ($transaction) {
            return $transaction->particulars;
        })->groupBy('prod_id')->map(function ($items) use ($priceAdjustment, $semester, $qtyAdjustment){
            $prodPrice = (float)$this->productService->getLatestPriceId($items->first()->price_id) * $priceAdjustment;
            $prodPrice = $prodPrice != null ? (float) ceil($prodPrice) : 0;

            $modifiedItems = $items->map(function ($particular) use ($semester, $priceAdjustment, $qtyAdjustment) {
                $isExempted = $this->productService->validateProductExcemption($particular->prod_id, $particular->transaction->ppmp_year);
                $qty = $semester == 'qty_first' ? $particular->qty_first : (int) $particular->qty_second;
        
                $modifiedQtyFirst = !$isExempted && $particular->qty_first > 1
                                    ? floor($qty * $qtyAdjustment)
                                    : $qty;
        
                $particular->modifiedQuantity = $modifiedQtyFirst;
        
                return $particular;
            });
            $toPr = $modifiedItems->sum('modifiedQuantity');

            return [
                'prodId' => $items->first()->prod_id,
                'prodCode' => $this->productService->getProductCode($items->first()->prod_id),
                'prodName' => $this->productService->getProductName($items->first()->prod_id),
                'prodUnit' => $this->productService->getProductUnit($items->first()->prod_id),
                'prodPrice' => $prodPrice,
                'qtyFirst' => $particular->qty_first,
                'qtyFirst' => $particular->qty_first,
                'itemQty' => $toPr,
            ];
        });

        $sortedParticulars = $groupParticulars->sortBy('prodCode');

        dd($sortedParticulars->toArray());
    }
    // "semester" => "qty_first"
    // "qtyAdjust" => 75

    /**
     * Display the specified resource.
     */
    public function show(PrTransaction $prTransaction)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PrTransaction $prTransaction)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PrTransaction $prTransaction)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PrTransaction $prTransaction)
    {
        //
    }
}
