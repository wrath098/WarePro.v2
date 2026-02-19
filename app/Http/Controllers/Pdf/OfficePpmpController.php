<?php

namespace App\Http\Controllers\Pdf;

use App\Http\Controllers\Controller;
use App\Models\PpmpTransaction;
use App\Services\PpmpPDF;
use App\Services\ProductService;
use Illuminate\Http\Request;

class OfficePpmpController extends Controller
{
    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function generatePdf_Ppmp(PpmpTransaction $ppmp, Request $request)
    {
        $version = $request->query('version');
        $ppmp->load('particulars', 'requestee');
        $totalQtySecond = $ppmp->particulars()->sum('qty_second');

        $pdf = new PpmpPDF('L', 'mm', array(203.2, 330.2), true, 'UTF-8', false, $ppmp->ppmp_code);
        $logoPath = public_path('assets/images/benguet_logo.png');
        $pilipinasPath = public_path('assets/images/Bagong_Pilipinas_logo.png');

        $pdf->SetCreator(SYSTEM_GENERATOR);
        $pdf->SetAuthor(SYSTEM_DEVELOPER);
        $pdf->SetTitle(strtoupper($ppmp->requestee->office_code) . ' | ' . strtoupper($version) );
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
                    <h4>PROJECT PROCUREMENT MANAGEMENT PLAN (PPMP) NO. ___</h4>
                    <h5>INDICATIVE&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;FINAL </h5>
                    <table align="center" cellpadding="0" cellspacing="0">
                        <tr>
                            <td width="420px"></td>
                            <td width="20px" align="center">
                                <table border="1" cellpadding="3" cellspacing="0">
                                    <tr><td></td></tr>
                                </table>
                            </td>
                            <td width="95px"></td>
                            <td width="20px" align="center">
                                <table border="1" cellpadding="3" cellspacing="0">
                                    <tr><td></td></tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            <div style="line-height: 0.80;">
                <h5>Fiscal Year: '. $ppmp->ppmp_year.'</h5>
                <h5>End-User or Implementing Unit: ' . strtoupper($ppmp->requestee->office_name) . '</h5>
            </div>
            ';     
        $pdf->writeHTML($html, true, false, true, false, '');
        $table = '<table border="1" cellpadding="2" cellspacing="0">';
        $table .= '<thead>';
        $table .= $this->tableHeader($totalQtySecond);
        $table .= '</thead>';
        $table .= '<tbody>';
        $table .= $this->tableContent($ppmp, $totalQtySecond, $version);
        $table .= '</tbody>';
        $table .= '</table>';
        $pdf->writeHTML($table, true, false, true, false, '');

        $table2 = '<p style="font-size: 6px; line-height: 0.0001;"><i>Note: Technical specifications for each item/request being proposed shall be submitted as part of the PPMP.</i></p>';     
        $pdf->writeHTML($table2, true, false, true, false, '');

        $pdf->Output(strtoupper($ppmp->requestee->office_name) . '.pdf', 'I');
    }

    protected function tableHeader($totalQtySecond)
    {
        if ($totalQtySecond != 0) {
            return $this->tableHeaderForBothSemesters();
        }
        return $this->tableHeaderForFirstSemester();
    }

    protected function tableContent($ppmp, $totalQtySecond, $version)
    {
        $text = '';
        $sortedParticulars = $this->formattedAndSortedParticulars($ppmp, $version);
        $funds = $this->productService->getAllProduct_FundModel();
        $categories = $this->productService->getAllProduct_Category();
        $productsOnCategory = $this->countProductsWithinCategory($categories, $sortedParticulars);
        $overallTotal = 0;

        if ($totalQtySecond != 0) {
            foreach ($funds as $fund) {
                if ($fund->categories->isNotEmpty()) {
                    $text .= $this->generateFundHeader($fund);
    
                    $fundFirstTotal = 0; 
                    $fundSecondTotal = 0;
                    $fundTotal = 0;
    
                    foreach ($fund->categories as $category) {
                        $catFirstTotal = 0; 
                        $catSecondTotal = 0;
                        $catTotal = 0;

                        $matchedCount = $productsOnCategory->get($category->cat_code, 0);
                        
                        if ($category->items->isNotEmpty()) {
                            if($matchedCount  > 0 ) {
                                $text .= $this->generateCategoryHeader($category);
                            }
            
                            foreach ($category->items as $item) {
                                if ($item->products->isNotEmpty()) {  
                                    foreach ($item->products as $product) {
                                        $matchedParticulars = $sortedParticulars->where('prodCode', $product->prod_newNo);
                                    
                                        if ($matchedParticulars->isNotEmpty()) {
                                            foreach ($matchedParticulars as $particular) {
                                                $this->generateProductContentForBothSemesters($particular, $product, $text, $catFirstTotal, $catSecondTotal, $catTotal);
                                            }
                                        }
                                    }  
                                }         
                            }

                            $fundFirstTotal += $catFirstTotal; 
                            $fundSecondTotal += $catSecondTotal;
                            $fundTotal += $catTotal;
                        }

                        if ($matchedCount  > 0 ) {
                            $text .= $this->generateCategoryFooterForBothSemesters($category, $catTotal, $catFirstTotal, $catSecondTotal);
                        }
                    }
                    
                    $text .= $this->generateFundFooterForBothSemesters($fund, $fundTotal, $fundFirstTotal, $fundSecondTotal);
                    $overallTotal += $fundTotal;
                }
            }
            $text .= $this->generateTotalBudgetFooterForBothSemesters($overallTotal);
            return $text;
        }

        foreach ($funds as $fund) {
            if ($fund->categories->isNotEmpty()) {
    
                $text .= $this->generateFundHeader($fund);

                $fundFirstTotal = 0; 
                $fundSecondTotal = 0;
                $fundTotal = 0;

                foreach ($fund->categories as $category) {
                    $catFirstTotal = 0; 
                    $catSecondTotal = 0;
                    $catTotal = 0;

                    if ($category->items->isNotEmpty()) {

                        $matchedCount = $productsOnCategory->get($category->cat_code, 0);

                        if($matchedCount  > 0 ) {
                            $text .= $this->generateCategoryHeader($category);
                        }

                        foreach ($category->items as $item) {

                            if ($item->products->isNotEmpty()) {  
                                foreach ($item->products as $product) {
                                    $matchedParticulars = $sortedParticulars->where('prodCode', $product->prod_newNo);

                                    if ($matchedParticulars->isNotEmpty()) {
                                        foreach ($matchedParticulars as $particular) {
                                            $this->generateProductContentForFirstSemester($particular, $product, $text, $catFirstTotal, $catTotal);
                                        }
                                    }
                                }  
                            }         
                        }
                    }

                    if ($catTotal > 0) {
                        $text .= $this->generateCategoryFooterForFirstSemester($category, $catTotal, $catFirstTotal);
                    }
                    
                    $fundFirstTotal += $catFirstTotal; 
                    $fundTotal += $catTotal;

                    
                }

                $text .= $this->generateFundFooterForFirstSemester($fund, $fundTotal, $fundFirstTotal, $overallTotal);

            }
        }

        return $text;
    }

    private function tableHeaderForBothSemesters()
    {
        return '<tr style="font-size: 6px; font-weight:bold; text-align:center; background-color: #FFFFFF;">
                    <th colspan="9">PROCUREMENT PROJECT DETAILS</th>
                    <th colspan="3">PROJECTED TIMELINE (MM/YYYY)</th>
                    <th colspan="2">FUNDING DETAILS</th>
                    <th rowspan="2">ATTACHED SUPPORTING DOCUMENTS</th>
                    <th rowspan="2">REMARKS</th>
                </tr>
                <tr style="font-size: 6px; font-weight:bold; text-align:center; background-color: #FFFFFF;">
                    <th>General Description and Objective of the Prject to be Procured</th>
                    <th>Type of the Project to be Procured (whether Goods, Infrastructure and Consulting Services)</th>
                    <th colspan="5">Quantity and Size of the Project to be Procured</th>
                    <th>Recommended Mode of Procurement</th>
                    <th>Pre-Procurement Conference, if applicable (Yes/No)</th>
                    <th>Start of Procurement Activity</th>
                    <th>End of Procurement Activity</th>
                    <th>Expected Delivery/Implementation Period</th>
                    <th>Source of Funds</th>
                    <th>Estimated Budget / Authorized Budgetary Allocation (PhP)</th>
                </tr>
                <tr style="font-size: 6px; font-weight:bold; text-align:center; background-color: #EEEEEE;">
                    <th>Column 1</th>
                    <th>Column 2</th>
                    <th colspan="5">Column 3</th>
                    <th>Column 4</th>
                    <th>Column 5</th>
                    <th>Column 6</th>
                    <th>Column 7</th>
                    <th>Column 8</th>
                    <th>Column 9</th>
                    <th>Column 10</th>
                    <th>Column 11</th>
                    <th>Column 12</th>
                </tr>
                <tr style="font-size: 6px; font-weight:bold; text-align:center; background-color: #EEEEEE;">
                    <th></th>
                    <th></th>
                    <th width="35px">Stock No.</th>
                    <th width="20px">Qty</th>
                    <th width="25px">Unit</th>
                    <th width="150px">Descriptions</th>
                    <th width="45px">Unit Price</th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                </tr>';
    }

    private function tableHeaderForFirstSemester()
    {
        return '<tr style="font-size: 6px; font-weight:bold; text-align:center; background-color: #FFFFFF;">
                    <th colspan="9">PROCUREMENT PROJECT DETAILS</th>
                    <th colspan="3">PROJECTED TIMELINE (MM/YYYY)</th>
                    <th colspan="2">FUNDING DETAILS</th>
                    <th rowspan="2">ATTACHED SUPPORTING DOCUMENTS</th>
                    <th rowspan="2">REMARKS</th>
                </tr>
                <tr style="font-size: 6px; font-weight:bold; text-align:center; background-color: #FFFFFF;">
                    <th>General Description and Objective of the Prject to be Procured</th>
                    <th>Type of the Project to be Procured (whether Goods, Infrastructure and Consulting Services)</th>
                    <th colspan="5">Quantity and Size of the Project to be Procured</th>
                    <th>Recommended Mode of Procurement</th>
                    <th>Pre-Procurement Conference, if applicable (Yes/No)</th>
                    <th>Start of Procurement Activity</th>
                    <th>End of Procurement Activity</th>
                    <th>Expected Delivery/Implementation Period</th>
                    <th>Source of Funds</th>
                    <th>Estimated Budget / Authorized Budgetary Allocation (PhP)</th>
                </tr>
                <tr style="font-size: 6px; font-weight:bold; text-align:center; background-color: #EEEEEE;">
                    <th>Column 1</th>
                    <th>Column 2</th>
                    <th colspan="5">Column 3</th>
                    <th>Column 4</th>
                    <th>Column 5</th>
                    <th>Column 6</th>
                    <th>Column 7</th>
                    <th>Column 8</th>
                    <th>Column 9</th>
                    <th>Column 10</th>
                    <th>Column 11</th>
                    <th>Column 12</th>
                </tr>
                <tr style="font-size: 6px; font-weight:bold; text-align:center; background-color: #EEEEEE;">
                    <th></th>
                    <th></th>
                    <th width="35px">Stock No.</th>
                    <th width="20px">Qty</th>
                    <th width="25px">Unit</th>
                    <th width="150px">Descriptions</th>
                    <th width="45px">Unit Price</th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                </tr>';
    }

    private function formattedAndSortedParticulars($ppmp, $requestType)
    {
        if (!$ppmp->particulars) {
            return collect([]);
        }

        $getProductData = function ($particular) {
            return [
                'prodCode' => $this->productService->getProductCode($particular->prod_id),
                'prodName' => $this->productService->getProductName($particular->prod_id),
                'prodUnit' => $this->productService->getProductUnit($particular->prod_id),
                'prodPrice' => $this->productService->getLatestPriceId($particular->price_id),
            ];
        };

        $ppmpParticulars = collect();

        switch ($requestType) {
            case 'raw':
                $ppmpParticulars = $ppmp->particulars->map(fn($particular) => [
                    'id' => $particular->id,
                    'qtyFirst' => $particular->qty_first,
                    'qtySecond' => $particular->qty_second,
                    ...$getProductData($particular),
                ]);
                break;

            case 'initial':
                $ppmpParticulars = $ppmp->particulars->map(fn($particular) => [
                    'id' => $particular->id,
                    'qtyFirst' => $particular->adjusted_firstQty,
                    'qtySecond' => $particular->adjusted_secondQty,
                    ...$getProductData($particular),
                ]);
                break;

            case 'final':
                $ppmpParticulars = $ppmp->particulars->map(fn($particular) => [
                    'id' => $particular->id,
                    'qtyFirst' => $particular->tresh_first_qty,
                    'qtySecond' => $particular->tresh_second_qty,
                    ...$getProductData($particular),
                ]);
                break;

            default:
                $ppmpParticulars = collect();
                break;
        }
        
        $sortedParticulars = $ppmpParticulars->sortBy('prodCode');

        return $sortedParticulars;
    }

    private function countProductsWithinCategory($categories, $sortedParticulars)
    {
        $productsOnCategory = $categories->mapWithKeys(function ($category) use ($sortedParticulars) {
            $totalMatchedItems = 0;

            foreach ($category->items as $item) {
                foreach ($item->products as $product) {
                    $matchedParticulars = $sortedParticulars->where('prodCode', $product->prod_newNo);
                    $totalMatchedItems += $matchedParticulars->count();
                }
            }
            return [$category->cat_code => $totalMatchedItems];
        });

        return $productsOnCategory;

    }

    public function generateFundHeader($fund)
    {
        return '<tr class="bg-gray-100" style="font-size: 6px; font-weight: bold;">
                    <td width="100%" colspan="16">' . strtoupper($fund->fund_name) . '</td>
                </tr>';
    }

    public function generateCategoryHeader($category)
    {
        return '<tr class="bg-gray-100" style="font-size: 6px; font-weight: bold;">
                    <td width="100%" colspan="16">' . sprintf('%02d', (int) $category->cat_code) . ' - ' . $category->cat_name . '</td>
                </tr>';
    }

    public function generateProductContentForBothSemesters($particular, $product, &$text, &$catFirstTotal, &$catSecondTotal, &$catTotal)
    {
        $prodQty = $particular['qtyFirst'] + $particular['qtySecond'];
        $firstQtyAmount = (float) $particular['qtyFirst'] * (float) $particular['prodPrice'];
        $secondQtyAmount = (float) $particular['qtySecond'] * (float) $particular['prodPrice'];
        $prodQtyAmount = $firstQtyAmount + $secondQtyAmount;
        $text .= $prodQtyAmount > 0
    ? '<tr nobr="true" style="font-size: 6px; text-align: center;">'
    : '<tr nobr="true" style="font-size: 6px; text-align: center; background-color:#f87171;">';
        $text .= '
            <td></td>
            <td></td>
            <td width="35px">' . $product->prod_newNo . '</td>
            <td width="20px">' . $this->formatToInteger($prodQty) . '</td>
            <td width="25px">' . $product->prod_unit. '</td>
            <td width="150px">' . $product->prod_desc . '</td>
            <td width="45px">' . $this->formatToFloat($particular['prodPrice']) . '</td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td>' . $this->formatToFloat($prodQtyAmount) . '</td>
            <td>-</td>
            <td>-</td>
        </tr>';

        $catFirstTotal += $firstQtyAmount; 
        $catSecondTotal += $secondQtyAmount;
        $catTotal += $prodQtyAmount;
    }

    public function generateProductContentForFirstSemester($particular, $product, &$text, &$catFirstTotal, &$catTotal)
    {
        $prodQty = $particular['qtyFirst'] + $particular['qtySecond'];
        $firstQtyAmount =  $particular['qtyFirst'] * (float) $particular['prodPrice'];
        $text .= $firstQtyAmount > 0 ? '<tr style="font-size: 9px; text-align: center;">' : '<tr style="font-size: 9px; text-align: center; background-color:#f87171;">';
        $text .= '
            <td width="40px">' . $product->prod_oldNo . '</td>
            <td width="45px">' . $product->prod_newNo . '</td>
            <td width="252px" style="text-align: left;">'. $product->prod_desc . '</td>
            <td width="45px">' . $product->prod_unit. '</td>
            <td width="50px" style="text-align: right;">' . $this->formatToFloat($particular['prodPrice']) . '</td>
            <td width="40px" style="text-align: right;">' . $this->formatToInteger($prodQty) . '</td>
            <td width="50px" style="text-align: right;">' . $this->formatToFloat($firstQtyAmount) . '</td>
            <td width="32px" style="text-align: right;">' . ($particular['qtyFirst'] != 0 ? $this->formatToInteger($particular['qtyFirst']) : '-') . '</td>
            <td width="50px" style="text-align: right;">' . ($firstQtyAmount != 0 ? $this->formatToFloat($firstQtyAmount) : '-') . '</td>
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

    private function generateCategoryFooterForBothSemesters($category, $catTotal, $catFirstTotal, $catSecondTotal) {
        return '<tr style="font-size: 6px; font-weight:bold; text-align: center; background-color: #f2f2f2;">
                    <td colspan="12">Total Amount for ' . htmlspecialchars($category->cat_name) . '</td>
                    <td colspan="2" style="text-align: right;">' . ($catTotal != 0 ? $this->formatToFloat($catTotal) : '-') . '</td>
                    <td></td>
                    <td></td>
                </tr>';
    }

    private function generateCategoryFooterForFirstSemester($category, $catTotal, $catFirstTotal)
    {
        return '<tr style="font-size: 6px; font-weight:bold; text-align: center; background-color: #f2f2f2;">
                    <td colspan="12">Total Amount for ' . htmlspecialchars($category->cat_name) . '</td>
                    <td colspan="2" style="text-align: right;">' . ($catTotal != 0 ? $this->formatToFloat($catTotal) : '-') . '</td>
                    <td></td>
                    <td></td>
                </tr>';
    }

    private function generateFundFooterForBothSemesters($fund, $fundTotal, $fundFirstTotal, $fundSecondTotal)
    {
        return '<tr style="font-size: 6px; font-weight:bold; text-align: center; background-color: #f2f2f2;">
                    <td colspan="12">Total Amount for ' . $fund->fund_name . '</td>
                    <td colspan="2" style="text-align: right;">' . ($fundTotal != 0 ? $this->formatToFloat($fundTotal) : '-') . '</td>
                    <td></td>
                    <td></td>
                </tr>';
    }

    private function generateFundFooterForFirstSemester($fund, $fundTotal, $fundFirstTotal)
    {
        return '<tr style="font-size: 6px; font-weight:bold; text-align: center; background-color: #f2f2f2;">
                    <td colspan="12">Total Amount for ' . $fund->fund_name . '</td>
                    <td colspan="2" style="text-align: right;">' . ($fundTotal != 0 ? $this->formatToFloat($fundTotal) : '-') . '</td>
                    <td></td>
                    <td></td>
                </tr>';
    }

    private function generateTotalBudgetFooterForBothSemesters($overallTotal)
    {
        return '<tr style="font-size: 7px; font-weight:bold; text-align: center;">
                    <td colspan="10"></td>
                    <td colspan="2" style="text-align: right; background-color: #d1fae5;">TOTAL BUDGET</td>
                    <td colspan="2" style="text-align: right; background-color: #d1fae5;">' .
                        ($overallTotal != 0 ? $this->formatToFloat($overallTotal) : '-') .
                    '</td>
                    <td></td>
                    <td></td>
                </tr>';
    }

    private function formatToFloat($number)
    {
        return number_format($number, 2, '.', ',');
    }

    private function formatToInteger($number)
    {
        return number_format($number, 0, '.', ',');
    }
}
