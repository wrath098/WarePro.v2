<?php

namespace App\Http\Controllers\Pdf;

use App\Http\Controllers\Controller;
use App\Models\Product;
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
        $date = Carbon::createFromFormat('Y-m', $request->input('searchDate'));
        $dateInWords = Carbon::parse($date)->format('F Y');
        $query_year = $date->year;
        $query_month= $date->month;

        $start_date = Carbon::createFromDate($query_year, 1, 1)->startOfDay();
        $limit_date = Carbon::createFromDate($query_year, $query_month, 1)->endOfMonth()->endOfDay();

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
        $pdf->Output('Product List.pdf', 'I');
    }

    protected function tableHeader()
    {
        return '<tr style="font-size: 10px; font-weight:bold; text-align:center; background-color: #EEEEEE;">
                    <th width="25px">No.</th>
                    <th width="63px">New Stock No.</th>
                    <th width="308px">Item Description</th>
                    <th width="60px">Unit of Measure</th>
                    <th width="63px">Quantity</th>
                </tr>';
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
}
