<?php

namespace App\Http\Controllers\Pdf;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\User;
use App\Services\MyPDF;
use App\Services\ProductService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MonthlyInventoryReportController extends Controller
{
    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function generatePdf_MonthlyInventoryReport(Request $request)
    {       
        $query = $request->filters;

        $preparedById = $request->filters['prepared_by'] ?? null;
        $reviewedById = $request->filters['reviewed_by'] ?? null;
        $certifiedCorrectId = $request->filters['certified_correct'] ?? null;

        $signatories = [
            'prepared_by' => $preparedById ? $this->getUserSignatory($preparedById) : ['name'=>'', 'position'=>''],
            'reviewed_by' => $reviewedById ? $this->getUserSignatory($reviewedById) : ['name'=>'', 'position'=>''],
            'approved_by' => $certifiedCorrectId ? $this->getUserSignatory($certifiedCorrectId) : ['name'=>'', 'position'=>''],
        ];


        $date_from = Carbon::createFromFormat('Y-m-d', $request->input('date_from'))->startOfDay();
        $date_to   = Carbon::createFromFormat('Y-m-d', $request->input('date_to'))->endOfDay();

        $dateInWords = $date_from->format('F d, Y') . " to " . $date_to->format('F d, Y');

        $start_date = $date_from;
        $limit_date = $date_to;

        $pdf = new MyPDF('P', 'mm', array(203.2, 330.2), true, 'UTF-8', false);

        $logoPath = public_path('assets/images/benguet_logo.png');
        $pilipinasPath = public_path('assets/images/Bagong_Pilipinas_logo.png');

        $pdf->SetCreator(SYSTEM_GENERATOR);
        $pdf->SetAuthor(SYSTEM_DEVELOPER);
        $pdf->SetTitle('Monthly Inventory Report');
        $pdf->SetSubject('Inventory Report');
        $pdf->SetKeywords('Benguet, WarePro, Inventory, Report');
        
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(true);

        $pdf->SetFooterMargin(10);
        $pdf->SetAutoPageBreak(TRUE, 10);

        $pdf->AddPage();

        $pdf->Image($logoPath, 47, 15, 15, '', '', '', '', false, 300, '', false, false, 0, false, false, false);
        $pdf->Image($pilipinasPath, 140, 15, 15, '', '', '', '', false, 300, '', false, false, 0, false, false, false);

        $html = '
            <div style="line-height: 0.01;">
                <div style="line-height: 0.5; text-align: center; font-size: 10px;">
                    <p>Republic of the Philippines</p>
                    <p>PROVINCE OF BENGUET</p>
                    <p>La Trinidad</p>
                    <h5>PROVINCIAL GENERAL SERVICES OFFICE</h5>
                </div>
                <div style="line-height: 0.60; text-align: center; font-size: 10px;">
                    <h4>OFFICE AND JANITORIAL SUPPLIES INVENTORY REPORT</h4>
                    <h5>As of '. $dateInWords .'</h5>
                </div>
            </div>
            <br>
            ';     
        $pdf->writeHTML($html, true, false, true, false, '');
        $table = '<table border="1" cellpadding="2" cellspacing="0">';
        $table .= '<thead>';
        $table .= $this->tableHeader();
        $table .= '</thead>';
        $table .= '<tbody>';
        $table .= $this->tableContent($start_date, $limit_date);
        $table .= '</tbody>';
        $table .= '</table>';
        $pdf->writeHTML($table, true, false, true, false, '');
        
        $pdf->writeHtml($this->footer($signatories), true, false, true, false, '');

        $pdf->Output('Product List.pdf', 'I');
    }

    protected function tableHeader()
    {
        return '<tr style="font-size: 10px; font-weight:bold; text-align:center; background-color: #EEEEEE;">
                    <th width="25px">No.</th>
                    <th width="63px">Stock No.</th>
                    <th width="308px">Item Description</th>
                    <th width="60px">Unit of Measure</th>
                    <th width="63px">Quantity</th>
                </tr>';
    }

    public function fetchMonthlyInventory(Request $request)
    {
        $request->validate([
            'date_from' => 'required|date',
            'date_to'   => 'required|date|after_or_equal:date_from',
        ]);

        try {
            $start_date = Carbon::parse($request->date_from)->startOfDay();
            $end_date   = Carbon::parse($request->date_to)->endOfDay();

            $products = Product::with([
                'inventory:id,prod_id,qty_physical_count',
                'inventoryTransactions' => function ($q) use ($start_date, $end_date) {
                    $q->withTrashed()
                    ->select('id','type','qty','prod_id','created_at')
                    ->whereBetween('created_at', [$start_date, $end_date])
                    ->orderBy('created_at','asc');
                }
            ])->get();

            $adjustedProducts = $this->reformatProductArray($products);

            return response()->json(['data' => $adjustedProducts]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch product inventory', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
            ]);
            return response()->json(['error' => 'Failed to fetch product inventory'], 500);
        }
    }

    protected function tableContent($start_date, $limit_date)
    {
        $text = '';
        $funds = $this->productService->getAllProduct_FundModel();

        $products = Product::with([
            'inventory' => function ($query) {
                $query->select('id', 'prod_id', 'qty_physical_count');
            }, 
            'inventoryTransactions' => function ($query) use ($start_date, $limit_date) {
                $query->withTrashed()
                    ->select('id', 'type', 'qty', 'prod_id', 'created_at')
                    ->whereBetween('created_at', [$start_date, $limit_date])
                    ->orderBy('created_at', 'asc');
            }])
            ->get();

        $adjustedProducts = $this->reformatProductArray($products);

        $count = 0;
        foreach ($funds as $fund) {

            if ($fund->categories->isNotEmpty()) {
                $text .= $this->generateFundHeader($fund);

                foreach ($fund->categories as $category) {
                    if ($category->items->isNotEmpty()) {
                        $text .= $this->generateCategoryHeader($category);

                        foreach ($category->items as $item) {
                            if ($item->products->isNotEmpty()) {  
                                foreach ($item->products as $product) {

                                    $matchedParticulars = collect($adjustedProducts)->first(function ($item) use ($product) {
                                        return $item['newStockNo'] == $product->prod_newNo;
                                    });

                                    if ($matchedParticulars) {
                                        $count++;
                                        $text .= '<tr style="font-size: 9px; text-align: center;">
                                                <td width="25px">'. $count .'</td>
                                                <td width="63px">'. $matchedParticulars['newStockNo'] .'</td>
                                                <td width="308px" style="text-align: left;">'. $matchedParticulars['description'] .'</td>
                                                <td width="60px">'. $matchedParticulars['unit'] .'</td>
                                                <td width="63px" style="text-align: right;">'. $matchedParticulars['currentInventory'] .'</td>
                                            </tr>';
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return $text;
    }

    public function generateFundHeader($fund)
    {
        return '<tr class="bg-gray-100" style="font-size: 10px; font-weight: bold;">
                    <td width="100%">' . strtoupper($fund->fund_name) . '</td>
                </tr>';
    }

    public function generateCategoryHeader($category)
    {
        return '<tr class="bg-gray-100" style="font-size: 10px; font-weight: bold;">
                    <td width="100%">' . sprintf('%02d', (int) $category->cat_code) . ' - ' . $category->cat_name . '</td>
                </tr>';
    }

    private function reformatProductArray($products)
    {
        $newProductArray = [];

        foreach ($products as $product) {
            $currentStock = $product->inventory ? $product->inventory->qty_physical_count : 0;

            foreach ($product->inventoryTransactions as $transaction){
                if ($transaction->type == 'issuance') {
                    $currentStock -= $transaction->qty;
                } elseif ($transaction->type == 'purchase') {
                    $currentStock += $transaction->qty;
                } else {
                    continue;
                }
            }

            if($product->prod_status == 'active' || ($product->prod_status == 'deactivated' && $currentStock > 0)) {
                $newProductArray[] = [
                    'id' => $product->id,
                    'newStockNo' => $product->prod_newNo,
                    'description' => $product->prod_desc,
                    'unit' => $product->prod_unit,
                    'oldStockNo' => $product->prod_oldNo,
                    'currentInventory' => number_format($currentStock, 0, '.', ','),
                ];
            }
        }

        return $newProductArray;
    }

    private function footer($signatories)
    {
        return '
            <table>
                <thead>
                    <tr style="font-size: 11px;">
                        <th width="293px" rowspan="3">Prepared By:</th>
                        <th width="293px" rowspan="3">Reviewed By:</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td width="100%"><br></td></tr>
                    <tr style="font-size: 11px; font-weight:bold; text-align:center;">
                        <td width="273px">'. $signatories['prepared_by']['name'].'</td>
                        <td width="273px">'. $signatories['reviewed_by']['name'].'</td>
                    </tr>
                    <tr style="font-size: 11px; text-align:center;">
                        <td width="273px">'. $signatories['prepared_by']['position'].'</td>
                        <td width="273px">'. $signatories['reviewed_by']['position'].'</td>
                    </tr>
                </tbody>
            </table>
            <br><br><br><br>
            <table>
                <thead>
                    <tr style="font-size: 11px; text-align:center;" >
                        <th width="300px">Certified Correct:</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td width="100%"><br></td></tr>
                    <tr style="font-size: 11px; font-weight:bold; text-align:center;">
                        <td width="100%">'. $signatories['approved_by']['name'].'</td>
                    </tr>
                    <tr style="font-size: 11px; text-align:center;">
                        <td width="100%">'. $signatories['approved_by']['position'].'</td>
                    </tr>
                </tbody>
            </table>';
    }

    private function signatories()
    {
        return [
            'prepared_by' => [
                'name' => 'JESSER ANJELO G. MAYOS',
                'position' => 'Administrative Aide VI',
            ],
            'reviewed_by' => [
                'name' => 'MARJORIE A. BOMOGAO',
                'position' => 'Supervising Administrative Officer',
            ],
            'approved_by' => [
                'name' => 'JENNIFER G. BAHOD',
                'position' => 'Provincial General Services Officer',
            ],
        ];
    }

    private function getUserSignatory($userId)
    {
        $user = \App\Models\User::find($userId);
        if(!$user) {
            return ['name'=>'', 'position'=>''];
        }

        return [
            'name' => strtoupper($user->name),
            'position' => ($user->position ?? ''),
        ];
    }
}
