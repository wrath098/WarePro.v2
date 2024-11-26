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

    public function index(): Response
    {
        $descriptionMap = [
            'nc' => 'Non-Contract/Bidding',
            'dc' => 'Direct Contract',
            'psdbm' => 'PS-DBM',
        ];

        $pendingPr = PrTransaction::with('ppmpController', 'updater')
            ->where('pr_status', 'draft')
            ->get()
            ->map(function ($pr) use ($descriptionMap) {
                $pr->semester = $pr->semester == 'qty_first' ? 'First Semester' : 'Second Semester';
                $pr->pr_desc = $descriptionMap[$pr->pr_desc] ?? null;
                $pr->qty_adjustment = $pr->qty_adjustment * 100;
                $pr->pr_status = ucfirst($pr->pr_status);
                $pr->formatted_created_at = $pr->created_at->format('m-d-Y');
                return $pr;
            });

        return Inertia::render('Pr/Index', [
            'pendingPr' => $pendingPr,
        ]);
    }

    public function showParticulars(PrTransaction $prTransaction)
    {
        $descriptionMap = [
            'nc' => 'Non-Contract/Bidding',
            'dc' => 'Direct Contract',
            'psdbm' => 'PS-DBM',
        ];

        $prTransaction->load('prParticulars', 'ppmpController', 'updater');
        $prTransaction->semester = $prTransaction->semester === 'qty_first' ? 'First Semester' : 'Second Semester';        
        $prTransaction->pr_desc = $descriptionMap[$prTransaction->pr_desc] ?? null;

        $prTransaction->qty_adjustment = $prTransaction->qty_adjustment * 100;
        $prTransaction->pr_status = ucfirst($prTransaction->pr_status);

        $reformatParticular = $prTransaction->prParticulars->map(function ($particular) {
            $particular->prodCode = $this->productService->getProductCode($particular->prod_id);
            return $particular;
        });

        $trashParticulars = $prTransaction->load(['prParticulars' => function($query) {
            $query->onlyTrashed();
        }]);

        $resultTrashedParticular = $trashParticulars->prParticulars->map(function($particular) {
            $particular->prodCode = $this->productService->getProductCode($particular->prod_id);
            return $particular;
        });

        $prTransaction['totalItems'] = $reformatParticular->count();
        $grandTotal = $reformatParticular->sum(fn($particular) => ((float) $particular->unitPrice * $particular->qty));

        $prTransaction['formattedOverallPrice'] = number_format($grandTotal, 2, '.', ',');
        return Inertia::render('Pr/PendingParticular', [
            'pr' =>  $prTransaction,
            'particulars' => $reformatParticular,
            'trashed' => $resultTrashedParticular,
        ]);
    }

}
