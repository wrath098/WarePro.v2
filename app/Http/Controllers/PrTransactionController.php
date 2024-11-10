<?php

namespace App\Http\Controllers;

use App\Models\PpmpTransaction;
use App\Models\PrParticular;
use App\Models\PrTransaction;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

        $pendingPr = PrTransaction::with('ppmpController', 'updater')
            ->where('pr_status', 'pending')
            ->get()
            ->map(function ($pr) {
                if($pr->semester == 'qty_first') {
                    $pr->semester = 'First Semester';
                } else {
                    $pr->semester = 'Second Semester';
                }
                $pr->qty_adjustment = $pr->qty_adjustment * 100;
                return $pr;
            });

        return Inertia::render('Pr/Index', [
            'toPr' =>  $resulToPr,
            'pendingPr' => $pendingPr,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {   
        DB::beginTransaction();

        try {
            $ppmpTransactions = PpmpTransaction::with('particulars')
                ->where('ppmp_status', 'approved')
                ->where('ppmp_type', 'individual')
                ->where('ppmp_year', $request->selectedYear)
                ->get();

            $consoTransaction = PpmpTransaction::where('ppmp_status', 'approved')
                ->where('ppmp_type', $request->selectedType)
                ->where('ppmp_year', $request->selectedYear)
                ->first();

            $priceAdjustment = $ppmpTransactions->first()->price_adjustment;
            $semester = $request->semester;
            $qtyAdjustment = $request->qtyAdjust / 100;
            $userId = Auth::id();

            $createPrTransact = PrTransaction::create([
                'pr_no' => now()->format('YmdHis'),
                'semester' => $semester,
                'qty_adjustment' => $qtyAdjustment,
                'trans_id' => $consoTransaction->id,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            $groupParticulars = $ppmpTransactions->flatMap(function ($transaction) {
                return $transaction->particulars;
            })->groupBy('prod_id')->map(function ($items) use ($priceAdjustment, $semester, $qtyAdjustment, $createPrTransact, $userId){
                $prodPrice = (float)$this->productService->getLatestPriceId($items->first()->price_id) * $priceAdjustment;
                $prodPrice = $prodPrice != null ? (float) ceil($prodPrice) : 0;

                $modifiedItems = $items->map(function ($particular) use ($semester, $priceAdjustment, $qtyAdjustment, $createPrTransact, $userId) {
                    $isExempted = $this->productService->validateProductExcemption($particular->prod_id, $particular->transaction->ppmp_year);
                    $qty = $semester == 'qty_first' ? (int) $particular->qty_first : (int) $particular->qty_second;
            
                    $modifiedQtyFirst = !$isExempted && $particular->qty_first > 1
                                        ? floor($qty * $qtyAdjustment)
                                        : $qty;
            
                    $particular->modifiedQuantity = $modifiedQtyFirst;
            
                    return $particular;
                });
                $toPr = $items->sum('modifiedQuantity');

                if ($toPr > 0) {
                    PrParticular::create([
                        'prod_id' => $items->first()->prod_id,
                        'unitPrice' => (float)$prodPrice,
                        'unitMeasure' => $this->productService->getProductUnit($items->first()->prod_id),
                        'qty' => (int)$toPr,
                        'revised_specs' => $this->productService->getProductName($items->first()->prod_id),
                        'pr_id' => $createPrTransact->id,
                        'updated_by' => $userId,
                    ]);
                }
            });

            DB::commit();
            return response()->json(['message' => 'Successfully Created.'], 200);
    } catch (\Exception $e) {
        DB::rollBack();
            Log::error($e->getMessage());
            return response()->json(['error' => 'An error occurred while processing your request.'], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function showParticulars(PrTransaction $prTransaction)
    {
        $prTransaction->load('prParticulars', 'ppmpController', 'updater');

        if($prTransaction->semester == 'qty_first') {
            $prTransaction->semester = 'First Semester';
        } else {
            $prTransaction->semester = 'Second Semester';
        }
        $prTransaction->qty_adjustment = $prTransaction->qty_adjustment * 100;

        $reformatParticular = $prTransaction->prParticulars->map(function ($particular) {
            $particular->prodCode = $this->productService->getProductCode($particular->prod_id);
            return $particular;
        });

        $prTransaction['totalItems'] = $reformatParticular->count();
        $grandTotal = $reformatParticular->sum(fn($particular) => ((float) $particular->unitPrice * $particular->qty));

        $prTransaction['formattedOverallPrice'] = number_format($grandTotal, 2, '.', ',');
        return Inertia::render('Pr/PendingParticular', [
            'pr' =>  $prTransaction,
            'particulars' => $reformatParticular,
        ]);
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
//to be used for print on pdf | store function
                // return [
                //     'prodId' => $items->first()->prod_id,
                //     'prodCode' => $this->productService->getProductCode($items->first()->prod_id),
                //     'prodName' => $this->productService->getProductName($items->first()->prod_id),
                //     'prodUnit' => $this->productService->getProductUnit($items->first()->prod_id),
                //     'prodPrice' => $prodPrice,
                //     'qtyFirst' => $items->sum('qty_first'),
                //     'qtySecond' => $items->sum('qty_second'),
                //     'itemQty' => $toPr,
                // ];
                // return null;