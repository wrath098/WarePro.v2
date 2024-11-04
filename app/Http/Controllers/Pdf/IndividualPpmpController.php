<?php

namespace App\Http\Controllers\Pdf;

use App\Http\Controllers\Controller;
use App\Models\PpmpTransaction;
use App\Services\MyPDF;
use App\Services\ProductService;
use Illuminate\Http\Request;

class IndividualPpmpController extends Controller
{
    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function generatePdf_IndividualPpmp(PpmpTransaction $ppmp)
    {
        $ppmp->load('particulars', 'requestee');
        $totalQtySecond = $ppmp->particulars()->sum('qty_second');

        if ($ppmp->ppmp_status == 'final') {
            $ppmp->ppmp_status = 'Approved';
        }
        
        $pdf = new MyPDF('L', 'mm', array(203.2, 330.2), true, 'UTF-8', false);
        $logoPath = public_path('assets/images/benguet_logo.png');
        $pilipinasPath = public_path('assets/images/Bagong_Pilipinas_logo.png');

        $pdf->SetCreator(SYSTEM_GENERATOR);
        $pdf->SetAuthor(SYSTEM_DEVELOPER);
        $pdf->SetTitle('Individual PPMP');
        $pdf->SetSubject('Individual PPMP Particulars');
        $pdf->SetKeywords('Benguet, WarePro, Individual List');

        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(true);

        $pdf->SetFooterMargin(10);
        $pdf->SetAutoPageBreak(TRUE, 10);

        $pdf->AddPage();

        $pdf->Image($logoPath, 110, 15, 15, '', '', '', '', false, 300, '', false, false, 0, false, false, false);
        $pdf->Image($pilipinasPath, 205, 15, 15, '', '', '', '', false, 300, '', false, false, 0, false, false, false);

        $html = '
            <div style="line-height: 0.01;">
                <div style="line-height: 0.5; text-align: center; font-size: 10px;">
                    <p>Republic of the Philippines</p>
                    <p>PROVINCE OF BENGUET</p>
                    <p>La Trinidad</p>
                    <h5>PROVINCIAL GENERAL SERVICES OFFICE</h5>
                </div>
                <div style="line-height: 0.60; text-align: center; font-size: 10px;">
                    <h4>PROJECT PROCUREMENT MANAGEMENT PLAN '. $ppmp->ppmp_year.'</h4>
                    <h5>OFFICE & JANITORIAL SUPPLIES</h5>
                    <h5>'. ucfirst($ppmp->ppmp_status) .'</h5>
                </div>
            </div>
            <div style="line-height: 0.80;">
                <h5>FPP CODE: <u> </u></h5>
                <h5>END USER / OFFICE: ' . strtoupper($ppmp->requestee->office_name) . '</h5>
            </div>
            <br>
            ';     
        $pdf->writeHTML($html, true, false, true, false, '');
        $table = '<table border="1" cellpadding="2" cellspacing="0">';
        $table .= '<thead>';
        $table .= $this->tableHeader($totalQtySecond);
        $table .= '</thead>';
        $table .= '<tbody>';
        $table .= $this->tableContent($ppmp, $totalQtySecond);
        $table .= '</tbody>';
        $table .= '</table>';
        $pdf->writeHTML($table, true, false, true, false, '');

        $table2 = '<p style="font-size: 10px; line-height: 0.0001;"><i>Note: Technical specifications for each item/request being proposed shall be submitted as part of the PPMP.</i></p>';     
        $pdf->writeHTML($table2, true, false, true, false, '');

        $pdf->Output('cPPMP-' . strtoupper($ppmp->ppmp_status) . '-' . $ppmp->ppmp_status . '.pdf', 'I');
    }

    protected function tableHeader($totalQtySecond)
    {
        if ($totalQtySecond != 0) {
            return '<tr style="font-size: 10px; font-weight:bold; text-align:center; background-color: #EEEEEE;">
                    <th width="40px" rowspan="3">Old Stock. No</th>
                    <th width="45px" rowspan="3">New Stock. No</th>
                    <th width="195px" rowspan="3">Item Description</th>
                    <th width="45px" rowspan="3">Unit of Measure</th>
                    <th width="50px" rowspan="3">Price</th>
                    <th width="40px" rowspan="3">Total Qantity</th>
                    <th width="50px" rowspan="3">Total Amount</th>
                    <th width="414px" colspan="12">SCHEDULE/MILESTONE OF ACTIVITIES</th>
                </tr>
                <tr style="font-size: 8px; font-weight:bold; background-color: #EEEEEE;">
                    <th width="82px" colspan="2" style="text-align:center;">JAN</th>
                    <th width="25px" rowspan="2" style="text-align:center;">FEB</th>
                    <th width="25px" rowspan="2" style="text-align:center;" >MAR</th>
                    <th width="25px" rowspan="2" style="text-align:center;">APR</th>
                    <th width="82px" colspan="2" style="text-align:center;" >MAY</th>
                    <th width="25px" rowspan="2" style="text-align:center;">JUN</th>
                    <th width="25px" rowspan="2" style="text-align:center;">JUL</th>
                    <th width="25px" rowspan="2" style="text-align:center;">AUG</th>
                    <th width="25px" rowspan="2" style="text-align:center;">SEP</th>
                    <th width="25px" rowspan="2" style="text-align:center;">OCT</th>
                    <th width="25px" rowspan="2" style="text-align:center;">NOV</th>
                    <th width="25px" rowspan="2" style="text-align:center;">DEC</th>
                </tr>
                <tr style="font-size: 8px; font-weight: bold; background-color: #EEEEEE;">
                    <th width="32px" style="text-align: center;">QTY</th>
                    <th width="50px" style="text-align: center;">AMT</th>
                    <th width="32px" style="text-align: center;">QTY</th>
                    <th width="50px" style="text-align: center;">AMT</th>
                </tr>';
        }
        return '<tr style="font-size: 10px; font-weight:bold; text-align:center; background-color: #EEEEEE;">
                    <th width="40px" rowspan="3">Old Stock. No</th>
                    <th width="45px" rowspan="3">New Stock. No</th>
                    <th width="252px" rowspan="3">Item Description</th>
                    <th width="45px" rowspan="3">Unit of Measure</th>
                    <th width="50px" rowspan="3">Price</th>
                    <th width="40px" rowspan="3">Total Qantity</th>
                    <th width="50px" rowspan="3">Total Amount</th>
                    <th width="357px" colspan="12">SCHEDULE/MILESTONE OF ACTIVITIES</th>
                </tr>
                <tr style="font-size: 8px; font-weight:bold; background-color: #EEEEEE;">
                    <th width="82px" colspan="2" style="text-align:center;">JAN</th>
                    <th width="25px" rowspan="2" style="text-align:center;">FEB</th>
                    <th width="25px" rowspan="2" style="text-align:center;" >MAR</th>
                    <th width="25px" rowspan="2" style="text-align:center;">APR</th>
                    <th width="25px" rowspan="2" style="text-align:center;" >MAY</th>
                    <th width="25px" rowspan="2" style="text-align:center;">JUN</th>
                    <th width="25px" rowspan="2" style="text-align:center;">JUL</th>
                    <th width="25px" rowspan="2" style="text-align:center;">AUG</th>
                    <th width="25px" rowspan="2" style="text-align:center;">SEP</th>
                    <th width="25px" rowspan="2" style="text-align:center;">OCT</th>
                    <th width="25px" rowspan="2" style="text-align:center;">NOV</th>
                    <th width="25px" rowspan="2" style="text-align:center;">DEC</th>
                </tr>
                <tr style="font-size: 8px; font-weight: bold; background-color: #EEEEEE;">
                    <th width="32px" style="text-align: center;">QTY</th>
                    <th width="50px" style="text-align: center;">AMT</th>
                </tr>';        
    }

    protected function tableContent($ppmp, $totalQtySecond)
    {
        $text = '';
        $ppmpParticulars = $ppmp->particulars->map(fn($particular) => [
            'id' => $particular->id,
            'qtyFirst' => $particular->qty_first,
            'qtySecond' => $particular->qty_second,
            'prodCode' => $this->productService->getProductCode($particular->prod_id),
            'prodName' => $this->productService->getProductName($particular->prod_id),
            'prodUnit' => $this->productService->getProductUnit($particular->prod_id),
            'prodPrice' => $this->productService->getLatestPriceId($particular->price_id),
        ]);

        $sortedParticulars = $ppmpParticulars->sortBy('prodCode');
        $funds = $this->productService->getAllProduct_FundModel();

        if ($totalQtySecond != 0) {
            foreach ($funds as $fund) {
                if ($fund->categories->isNotEmpty()) {
                    $text .= '<tr class="bg-gray-100" style="font-size: 10px; font-weight: bold;">
                                <td width="100%">' . strtoupper($fund->fund_name) . '</td>
                                </tr>';
    
                $fundFirstTotal = 0; 
                $fundSecondTotal = 0;
                $fundTotal = 0;
    
                    foreach ($fund->categories as $category) {
                        if ($category->items->isNotEmpty()) {
                            $text .= '<tr class="bg-gray-100" style="font-size: 10px; font-weight: bold;">
                                        <td width="100%">' . sprintf('%02d', (int) $category->cat_code) . ' - ' . $category->cat_name . '</td>
                                        </tr>';
    
                        $catFirstTotal = 0; 
                        $catSecondTotal = 0;
                        $catTotal = 0;
            
                            foreach ($category->items as $item) {
                                if ($item->products->isNotEmpty()) {  
                                    foreach ($item->products as $product) {
                                        $matchedParticulars = $sortedParticulars->filter(function ($particular) use ($product) {
                                            return $particular['prodCode'] === $product->prod_newNo;
                                        });
    
                                        if ($matchedParticulars->isNotEmpty()) {
                                            foreach ($matchedParticulars as $particular) {
                                                $prodQty = $particular['qtyFirst'] + $particular['qtySecond'];
                                                $firstQtyAmount = (float) $particular['qtyFirst'] * (float) $particular['prodPrice'];
                                                $secondQtyAmount = (float) $particular['qtySecond'] * (float) $particular['prodPrice'];
                                                $prodQtyAmount = $firstQtyAmount + $secondQtyAmount;
                                                $text .= '<tr style="font-size: 9px; text-align: center;">
                                                    <td width="40px">' . $product->prod_oldNo . '</td>
                                                    <td width="45px">' . $product->prod_newNo . '</td>
                                                    <td width="195px" style="text-align: left;">('. $item->item_name .') ' . $product->prod_desc . '</td>
                                                    <td width="45px">' . $product->prod_unit. '</td>
                                                    <td width="50px" style="text-align: right;">' . number_format($particular['prodPrice'], 2, '.', ',') . '</td>
                                                    <td width="40px" style="text-align: right;">' . $prodQty . '</td>
                                                    <td width="50px" style="text-align: right;">' . number_format($prodQtyAmount, 2, '.', ',') . '</td>
                                                    <td width="32px" style="text-align: right;">' . ($particular['qtyFirst'] != 0 ? number_format($particular['qtyFirst'], 0, '.', ',') : '-') . '</td>
                                                    <td width="50px" style="text-align: right;">' . ($firstQtyAmount != 0 ? number_format($firstQtyAmount, 2, '.', ',') : '-') . '</td>
                                                    <td width="25px">-</td>
                                                    <td width="25px">-</td>
                                                    <td width="25px">-</td>
                                                    <td width="32px" style="text-align: right;">' . ($particular['qtySecond'] != 0 ? number_format($particular['qtySecond'], 0, '.', ',') : '-') . '</td>
                                                    <td width="50px" style="text-align: right;">' . ($secondQtyAmount != 0 ? number_format($secondQtyAmount, 2, '.', ',') : '-') . '</td>
                                                    <td width="25px">-</td>
                                                    <td width="25px">-</td>
                                                    <td width="25px">-</td>
                                                    <td width="25px">-</td>
                                                    <td width="25px">-</td>
                                                    <td width="25px">-</td>
                                                    <td width="25px">-</td>
                                                </tr>';
    
                                                $catFirstTotal += $firstQtyAmount; 
                                                $catSecondTotal += $secondQtyAmount;
                                                $catTotal += $prodQtyAmount;
                                            }
                                        }
                                    }  
                                }         
                            }
                        }
                        $text .= '<tr style="font-size: 10px; font-weight:bold; text-align: center; background-color: #f2f2f2;">
                                <td width="375px">Total Amount for ' . htmlspecialchars($category->cat_name) . '</td>
                                <td width="90px" style="text-align: right;">' . ($catTotal != 0 ? number_format($catTotal, 2, '.', ',') : '-') . '</td>
                                <td width="82px" style="text-align: right;">' . ($catFirstTotal != 0 ? number_format($catFirstTotal, 2, '.', ',') : '-') . '</td>
                                <td width="25px">-</td>
                                <td width="25px">-</td>
                                <td width="25px">-</td>
                                <td width="82px" style="text-align: right;">' . ($catSecondTotal != 0 ? number_format($catSecondTotal, 2, '.', ',') : '-') . '</td>
                                <td width="25px">-</td>
                                <td width="25px">-</td>
                                <td width="25px">-</td>
                                <td width="25px">-</td>
                                <td width="25px">-</td>
                                <td width="25px">-</td>
                                <td width="25px">-</td>
                            </tr>';
    
                        $fundFirstTotal += $catFirstTotal; 
                        $fundSecondTotal += $catSecondTotal;
                        $fundTotal += $catTotal;
                    }
                    $text .= '<tr style="font-size: 10px; font-weight:bold; text-align: center; background-color: #f2f2f2;">
                                <td width="375px">Total Amount for ' . $fund->fund_name . '</td>
                                <td width="90px" style="text-align: right;">' . ($fundTotal != 0 ? number_format($fundTotal, 2, '.', ',') : '-') . '</td>
                                <td width="82px" style="text-align: right;">' . ($fundFirstTotal != 0 ? number_format($fundFirstTotal, 2, '.', ',') : '-') . '</td>
                                <td width="25px">-</td>
                                <td width="25px">-</td>
                                <td width="25px">-</td>
                                <td width="82px" style="text-align: right;">' . ($fundSecondTotal != 0 ? number_format($fundSecondTotal, 2, '.', ',') : '-') . '</td>
                                <td width="25px">-</td>
                                <td width="25px">-</td>
                                <td width="25px">-</td>
                                <td width="25px">-</td>
                                <td width="25px">-</td>
                                <td width="25px">-</td>
                                <td width="25px">-</td>
                            </tr>';
                }
            }
            return $text;
        }

        foreach ($funds as $fund) {
            if ($fund->categories->isNotEmpty()) {
                $text .= '<tr class="bg-gray-100" style="font-size: 10px; font-weight: bold;">
                            <td width="100%">' . strtoupper($fund->fund_name) . '</td>
                            </tr>';

            $fundFirstTotal = 0; 
            $fundSecondTotal = 0;
            $fundTotal = 0;

                foreach ($fund->categories as $category) {
                    if ($category->items->isNotEmpty()) {
                        $text .= '<tr class="bg-gray-100" style="font-size: 10px; font-weight: bold;">
                                    <td width="100%">' . sprintf('%02d', (int) $category->cat_code) . ' - ' . $category->cat_name . '</td>
                                    </tr>';

                    $catFirstTotal = 0; 
                    $catSecondTotal = 0;
                    $catTotal = 0;
                        
                        foreach ($category->items as $item) {
                            if ($item->products->isNotEmpty()) {  
                                foreach ($item->products as $product) {
                                    $matchedParticulars = $sortedParticulars->filter(function ($particular) use ($product) {
                                        return $particular['prodCode'] === $product->prod_newNo;
                                    });

                                    if ($matchedParticulars->isNotEmpty()) {
                                        foreach ($matchedParticulars as $particular) {
                                            $prodQty = $particular['qtyFirst'] + $particular['qtySecond'];
                                            $firstQtyAmount =  $particular['qtyFirst'] * (float) $particular['prodPrice'];
                                            $text .= '<tr style="font-size: 9px; text-align: center;">
                                                <td width="40px">' . $product->prod_oldNo . '</td>
                                                <td width="45px">' . $product->prod_newNo . '</td>
                                                <td width="252px" style="text-align: left;">('. $item->item_name .') ' . $product->prod_desc . '</td>
                                                <td width="45px">' . $product->prod_unit. '</td>
                                                <td width="50px" style="text-align: right;">' . number_format($particular['prodPrice'], 2, '.', ',') . '</td>
                                                <td width="40px" style="text-align: right;">' . $prodQty . '</td>
                                                <td width="50px" style="text-align: right;">' . number_format($firstQtyAmount, 2, '.', ',') . '</td>
                                                <td width="32px" style="text-align: right;">' . ($particular['qtyFirst'] != 0 ? number_format($particular['qtyFirst'], 0, '.', ',') : '-') . '</td>
                                                <td width="50px" style="text-align: right;">' . ($firstQtyAmount != 0 ? number_format($firstQtyAmount, 2, '.', ',') : '-') . '</td>
                                                <td width="25px">-</td>
                                                <td width="25px">-</td>
                                                <td width="25px">-</td>
                                                <td width="25px">-</td>
                                                <td width="25px">-</td>
                                                <td width="25px">-</td>
                                                <td width="25px">-</td>
                                                <td width="25px">-</td>
                                                <td width="25px">-</td>
                                                <td width="25px">-</td>
                                                <td width="25px">-</td>
                                            </tr>';

                                            $catFirstTotal += $firstQtyAmount; 
                                            $catTotal += $firstQtyAmount;
                                        }
                                    }
                                }  
                            }         
                        }
                    }
                    $text .= '<tr style="font-size: 10px; font-weight:bold; text-align: center; background-color: #f2f2f2;">
                            <td width="432px">Total Amount for ' . htmlspecialchars($category->cat_name) . '</td>
                            <td width="90px" style="text-align: right;">' . ($catTotal != 0 ? number_format($catTotal, 2, '.', ',') : '-') . '</td>
                            <td width="82px" style="text-align: right;">' . ($catFirstTotal != 0 ? number_format($catFirstTotal, 2, '.', ',') : '-') . '</td>
                            <td width="25px">-</td>
                            <td width="25px">-</td>
                            <td width="25px">-</td>
                            <td width="25px">-</td>
                            <td width="25px">-</td>
                            <td width="25px">-</td>
                            <td width="25px">-</td>
                            <td width="25px">-</td>
                            <td width="25px">-</td>
                            <td width="25px">-</td>
                            <td width="25px">-</td>
                        </tr>';

                    $fundFirstTotal += $catFirstTotal; 
                    $fundTotal += $catTotal;
                }
                $text .= '<tr style="font-size: 10px; font-weight:bold; text-align: center; background-color: #f2f2f2;">
                            <td width="432px">Total Amount for ' . $fund->fund_name . '</td>
                            <td width="90px" style="text-align: right;">' . ($fundTotal != 0 ? number_format($fundTotal, 2, '.', ',') : '-') . '</td>
                            <td width="82px" style="text-align: right;">' . ($fundFirstTotal != 0 ? number_format($fundFirstTotal, 2, '.', ',') : '-') . '</td>
                            <td width="25px">-</td>
                            <td width="25px">-</td>
                            <td width="25px">-</td>
                            <td width="25px">-</td>
                            <td width="25px">-</td>
                            <td width="25px">-</td>
                            <td width="25px">-</td>
                            <td width="25px">-</td>
                            <td width="25px">-</td>
                            <td width="25px">-</td>
                            <td width="25px">-</td>
                        </tr>';
            }
        }
        return $text;
    }
}
