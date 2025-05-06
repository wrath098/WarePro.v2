<?php

namespace App\Http\Controllers\Pdf;

use App\Http\Controllers\Controller;
use App\Models\PpmpTransaction;
use App\Models\PrParticular;
use App\Services\PpmpPDF;
use App\Services\ProductService;
use Illuminate\Http\Request;

class DraftAdjustedOfficePpmpController extends Controller
{
    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function generatePdf_Ppmp(Request $request)
    {
        $ppmp = $this->getPpmp($request->transactionNo);
        $ppmp->load('particulars', 'requestee');
        $totalQtySecond = $ppmp->particulars()->sum('qty_second');
        
        $pdf = new PpmpPDF('L', 'mm', array(203.2, 330.2), true, 'UTF-8', false, $ppmp->ppmp_code);
        $logoPath = public_path('assets/images/benguet_logo.png');
        $pilipinasPath = public_path('assets/images/Bagong_Pilipinas_logo.png');

        $pdf->SetCreator(SYSTEM_GENERATOR);
        $pdf->SetAuthor(SYSTEM_DEVELOPER);
        $pdf->SetTitle(strtoupper($ppmp->requestee->office_code));
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
        $table .= $this->tableContent($ppmp, $totalQtySecond, $request);
        $table .= '</tbody>';
        $table .= '</table>';
        $pdf->writeHTML($table, true, false, true, false, '');

        $table2 = '<p style="font-size: 10px; line-height: 0.0001;"><i>Note: Technical specifications for each item/request being proposed shall be submitted as part of the PPMP.</i></p>';     
        $pdf->writeHTML($table2, true, false, true, false, '');

        $pdf->Output(strtoupper($ppmp->requestee->office_name) . '.pdf', 'I');
    }

    private function getPpmp($transactionId)
    {
        return PpmpTransaction::findOrFail($transactionId);
    }

    protected function tableHeader($totalQtySecond)
    {
        if ($totalQtySecond != 0) {
            return $this->generateTableHeaderForBothSemester();
        }
        return $this->generateTableHeaderForFirstSemester();
    }

    private function generateTableHeaderForBothSemester()
    {
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

    private function generateTableHeaderForFirstSemester()
    {
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

    protected function tableContent($ppmp, $totalQtySecond, $request)
    {
        $text = '';

        $sortedParticulars = $this->formattedAndSortedParticulars($ppmp);
        $funds = $this->productService->getAllProduct_FundModel();
        $categories = $this->productService->getAllProduct_Category();
        $productsOnCategory = $this->countProductsWithinCategory($categories, $sortedParticulars);

        if ($totalQtySecond != 0) {
            $this->generateOriginalQuantityBothSem($funds, $text, $productsOnCategory, $sortedParticulars, $request);
            return $text;
        }

        $this->generateFirstSemester($funds, $text, $productsOnCategory, $sortedParticulars, $request);
        return $text;
    }

    private function formattedAndSortedParticulars($ppmp)
    {
        $ppmpParticulars = $ppmp->particulars->map(function($particular) {
            return [
                'id' => $particular->id,
                'qtyFirst' => $particular->qty_first,
                'qtySecond' => $particular->qty_second,
                'prodCode' => $this->productService->getProductCode($particular->prod_id),
                'prodName' => $this->productService->getProductName($particular->prod_id),
                'prodUnit' => $this->productService->getProductUnit($particular->prod_id),
                'prodPrice' => $this->productService->getLatestPrice($particular->prod_id)
            ];
        });

        $sortedParticulars = $ppmpParticulars->sortBy('prodCode');

        return $sortedParticulars;
    }

    private function countProductsWithinCategory($categories, $sortedParticulars){
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

    private function generateOriginalQuantityBothSem($funds, &$text, $productsOnCategory, $sortedParticulars, $request)
    {
        foreach ($funds as $fund) {
            $fundFirstTotal = 0; 
            $fundSecondTotal = 0; 
            $fundTotal = 0;

            if ($fund->categories->isNotEmpty()) {
                $text .= $this->generateFundHeader($fund);

                foreach ($fund->categories as $category) {
                    $matchedCount = $productsOnCategory->get($category->cat_code, 0);

                    $catFirstTotal = 0; 
                    $catSecondTotal = 0; 
                    $catTotal = 0;

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

                                            if($request['printType'] == "original" && $request['threshold'] == 100) {
                                                $this->generateProductContentWithNoAdjustmentBothSems($particular, $text, $product, $catFirstTotal, $catSecondTotal, $catTotal);
                                            } else {
                                                $this->generateProductContentWithAdjustmentBothSems($product, $particular, $text, $catFirstTotal, $catSecondTotal, $catTotal, $request);
                                            }
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
                        $text .= $this->generateCategoryFooter($category, $catTotal, $catFirstTotal, $catSecondTotal);
                    }
                }
            }
            $text .= $this->generateFundFooter($fund, $fundTotal, $fundFirstTotal, $fundSecondTotal);
        }
    }

    private function generateFundHeader($fund)
    {
        return '<tr class="bg-gray-100" style="font-size: 10px; font-weight: bold;">
                    <td width="100%">' . strtoupper($fund->fund_name) . '</td>
                </tr>';
    }

    private function generateCategoryHeader($category)
    {
        return '<tr class="bg-gray-100" style="font-size: 10px; font-weight: bold;">
                    <td width="100%">' . sprintf('%02d', (int) $category->cat_code) . ' - ' . $category->cat_name . '</td>
                </tr>';
    }

    private function generateProductContentWithNoAdjustmentBothSems($particular, &$text, $product, &$catFirstTotal, &$catSecondTotal, &$catTotal)
    {
        $prodQty = $particular['qtyFirst'] + $particular['qtySecond'];
        $firstQtyAmount = (float) $particular['qtyFirst'] * (float) $particular['prodPrice'];
        $secondQtyAmount = (float) $particular['qtySecond'] * (float) $particular['prodPrice'];
        $prodQtyAmount = $firstQtyAmount + $secondQtyAmount;

        $text .= $this->productWithNoAdjustment($product, $particular, $prodQty, $prodQtyAmount, $firstQtyAmount, $secondQtyAmount);

        $catFirstTotal += $firstQtyAmount; 
        $catSecondTotal += $secondQtyAmount;
        $catTotal += $prodQtyAmount;
    }

    private function productWithNoAdjustment($product, $particular, $prodQty, $prodQtyAmount, $firstQtyAmount, $secondQtyAmount)
    {
        return '<tr style="font-size: 9px; text-align: center;">
                <td width="40px">' . $product->prod_oldNo . '</td>
                <td width="45px">' . $product->prod_newNo . '</td>
                <td width="195px" style="text-align: left;">' . $product->prod_desc . '</td>
                <td width="45px">' . $product->prod_unit. '</td>
                <td width="50px" style="text-align: right;">' . $this->formatToFloat($particular['prodPrice']) . '</td>
                <td width="40px" style="text-align: right;">' . $this->formatToInteger($prodQty) . '</td>
                <td width="50px" style="text-align: right;">' . $this->formatToFloat($prodQtyAmount) . '</td>
                <td width="32px" style="text-align: right;">' . ($particular['qtyFirst'] != 0 ? $this->formatToInteger($particular['qtyFirst']) : '-') . '</td>
                <td width="50px" style="text-align: right;">' . ($firstQtyAmount != 0 ? $this->formatToFloat($firstQtyAmount) : '-') . '</td>
                <td width="25px">-</td>
                <td width="25px">-</td>
                <td width="25px">-</td>
                <td width="32px" style="text-align: right;">' . ($particular['qtySecond'] != 0 ? $this->formatToInteger($particular['qtySecond']) : '-') . '</td>
                <td width="50px" style="text-align: right;">' . ($secondQtyAmount != 0 ? $this->formatToFloat($secondQtyAmount) : '-') . '</td>
                <td width="25px">-</td>
                <td width="25px">-</td>
                <td width="25px">-</td>
                <td width="25px">-</td>
                <td width="25px">-</td>
                <td width="25px">-</td>
                <td width="25px">-</td>
            </tr>';
    }

    private function generateProductContentWithAdjustmentBothSems($product, $particular, &$text, &$catFirstTotal, &$catSecondTotal, &$catTotal, $request)
    {
        $adjustment = ((int)$request['qtyAdjust'] / 100);
        $threshold = ((int)$request['threshold'] / 100);

        $isProductExempted = $this->productService->validateProductExcemption($product->id);
        $adjustFirstQty = $this->calculateAdjustedQty($particular['qtyFirst'], $adjustment, $threshold, $isProductExempted);
        $adjustSecondQty = $this->calculateAdjustedQty($particular['qtySecond'], $adjustment, $threshold, $isProductExempted);

        $prodQty = $adjustFirstQty + $adjustSecondQty;
        $firstQtyAmount = (float) $adjustFirstQty * (float) $particular['prodPrice'];
        $secondQtyAmount = (float) $adjustSecondQty * (float) $particular['prodPrice'];
        $prodQtyAmount = $firstQtyAmount + $secondQtyAmount;

        $text .= $this->productWithAdjustment($product, $particular, $prodQty, $prodQtyAmount, $adjustFirstQty, $firstQtyAmount, $adjustSecondQty, $secondQtyAmount);

        $catFirstTotal += $firstQtyAmount; 
        $catSecondTotal += $secondQtyAmount;
        $catTotal += $prodQtyAmount;                     
    }

    private function productWithAdjustment($product, $particular, $prodQty, $prodQtyAmount, $adjustFirstQty, $firstQtyAmount, $adjustSecondQty, $secondQtyAmount)
    {
        return '<tr style="font-size: 9px; text-align: center;">
                <td width="40px">' . $product->prod_oldNo . '</td>
                <td width="45px">' . $product->prod_newNo . '</td>
                <td width="195px" style="text-align: left;">' . $product->prod_desc . '</td>
                <td width="45px">' . $product->prod_unit. '</td>
                <td width="50px" style="text-align: right;">' . $this->formatToFloat($particular['prodPrice']) . '</td>
                <td width="40px" style="text-align: right;">' . $this->formatToInteger($prodQty) . '</td>
                <td width="50px" style="text-align: right;">' . $this->formatToFloat($prodQtyAmount) . '</td>
                <td width="32px" style="text-align: right;">' . ($adjustFirstQty != 0 ? $this->formatToInteger($adjustFirstQty) : '-') . '</td>
                <td width="50px" style="text-align: right;">' . ($firstQtyAmount != 0 ? $this->formatToFloat($firstQtyAmount) : '-') . '</td>
                <td width="25px">-</td>
                <td width="25px">-</td>
                <td width="25px">-</td>
                <td width="32px" style="text-align: right;">' . ($adjustSecondQty != 0 ? $this->formatToInteger($adjustSecondQty) : '-') . '</td>
                <td width="50px" style="text-align: right;">' . ($secondQtyAmount != 0 ? $this->formatToFloat($secondQtyAmount) : '-') . '</td>
                <td width="25px">-</td>
                <td width="25px">-</td>
                <td width="25px">-</td>
                <td width="25px">-</td>
                <td width="25px">-</td>
                <td width="25px">-</td>
                <td width="25px">-</td>
            </tr>';
    }

    public function generateFirstSemester($funds, &$text, $productsOnCategory, $sortedParticulars, $request)
    {
        foreach ($funds as $fund) {
            if ($fund->categories->isNotEmpty()) {
                $text .= $this->generateFundHeader($fund);

                $fundFirstTotal = 0; 
                $fundTotal = 0;

                foreach ($fund->categories as $category) {

                    if ($category->items->isNotEmpty()) {
                        $matchedCount = $productsOnCategory->get($category->cat_code, 0);

                        if($matchedCount  > 0 ) {
                            $text .= $this->generateCategoryHeader($category);
                        }

                        $catFirstTotal = 0; 
                        $catTotal = 0;
                        
                        foreach ($category->items as $item) {

                            if ($item->products->isNotEmpty()) {  
                                foreach ($item->products as $product) {
                                    $matchedParticulars = $sortedParticulars->where('prodCode', $product->prod_newNo);

                                    if ($matchedParticulars->isNotEmpty()) {
                                        foreach ($matchedParticulars as $particular) {

                                            if($request['printType'] == "original" && $request['threshold'] == 100) {
                                                $this->generateProductContentWithNoAdjustmentFirstSemester($particular, $text, $product, $catFirstTotal, $catTotal);
                                            } else {
                                                $this->generateProductContentWithAdjustmentFirstSemester($product, $particular, $text, $catFirstTotal, $catTotal, $request);
                                            }
                                        }
                                    }
                                }  
                            }         
                        }
                    }

                    if ($catTotal > 0) {
                        $text .= $this->generateFundFooterFirstSemester($category, $catTotal, $catFirstTotal);
                    }

                    $fundFirstTotal += $catFirstTotal; 
                    $fundTotal += $catTotal;
                }

                $text .= $this->generateCategoryFooterFirstSemester($fund, $fundTotal, $fundFirstTotal);
            }
        }
    }

    private function generateProductContentWithNoAdjustmentFirstSemester($particular, &$text, $product, &$catFirstTotal, &$catTotal){
        $firstQtyAmount =  $particular['qtyFirst'] * (float) $particular['prodPrice'];

        $text .= $this->productWithNoAdjustmentFirstSemester($product, $particular, $firstQtyAmount);

        $catFirstTotal += $firstQtyAmount; 
        $catTotal += $firstQtyAmount;
    }

    private function productWithNoAdjustmentFirstSemester($product, $particular, $firstQtyAmount)
    {
        return '<tr style="font-size: 9px; text-align: center;">
            <td width="40px">' . $product->prod_oldNo . '</td>
            <td width="45px">' . $product->prod_newNo . '</td>
            <td width="252px" style="text-align: left;">' . $product->prod_desc . '</td>
            <td width="45px">' . $product->prod_unit. '</td>
            <td width="50px" style="text-align: right;">' . $this->formatToFloat($particular['prodPrice']) . '</td>
            <td width="40px" style="text-align: right;">' . $this->formatToInteger($particular['qtyFirst']) . '</td>
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
    }

    private function generateProductContentWithAdjustmentFirstSemester($product, $particular, &$text, &$catFirstTotal, &$catTotal, $request)
    {
        $adjustment = ((int)$request['qtyAdjust'] / 100);
        $threshold = ((int)$request['threshold'] / 100);
        
        $isProductExempted = $this->productService->validateProductExcemption($product->id);
        $adjustFirstQty = $this->calculateAdjustedQty($particular['qtyFirst'], $adjustment, $threshold, $isProductExempted);
        $firstQtyAmount =  $adjustFirstQty * (float) $particular['prodPrice'];

        $text .= $this->productWithAdjustmentFirstSemester($product, $particular, $adjustFirstQty, $firstQtyAmount);

        $catFirstTotal += $firstQtyAmount; 
        $catTotal += $firstQtyAmount;
    }

    private function productWithAdjustmentFirstSemester($product, $particular, $adjustFirstQty, $firstQtyAmount)
    {
        return '<tr style="font-size: 9px; text-align: center;">
                    <td width="40px">' . $product->prod_oldNo . '</td>
                    <td width="45px">' . $product->prod_newNo . '</td>
                    <td width="252px" style="text-align: left;">' . $product->prod_desc . '</td>
                    <td width="45px">' . $product->prod_unit. '</td>
                    <td width="50px" style="text-align: right;">' . $this->formatToFloat($particular['prodPrice']) . '</td>
                    <td width="40px" style="text-align: right;">' . $this->formatToInteger($adjustFirstQty) . '</td>
                    <td width="50px" style="text-align: right;">' . $this->formatToFloat($firstQtyAmount) . '</td>
                    <td width="32px" style="text-align: right;">' . ($adjustFirstQty != 0 ? $this->formatToInteger($adjustFirstQty) : '-') . '</td>
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
    }

    private function calculateAdjustedQty($qty, $adjustment, $threshold, $isExempted)
    {
        if (!$isExempted && $qty > 1) {
            return floor(((int)$qty * (float)$adjustment) * (float)$threshold);
        }

        return (int)$qty;
    }
    
    private function generateCategoryFooter($category, $catTotal, $catFirstTotal, $catSecondTotal)
    {
        return '<tr style="font-size: 10px; font-weight:bold; text-align: center; background-color: #f2f2f2;">
                    <td width="375px">Total Amount for ' . htmlspecialchars($category->cat_name) . '</td>
                    <td width="90px" style="text-align: right;">' . ($catTotal != 0 ? $this->formatToFloat($catTotal) : '-') . '</td>
                    <td width="82px" style="text-align: right;">' . ($catFirstTotal != 0 ? $this->formatToFloat($catFirstTotal) : '-') . '</td>
                    <td width="25px">-</td>
                    <td width="25px">-</td>
                    <td width="25px">-</td>
                    <td width="82px" style="text-align: right;">' . ($catSecondTotal != 0 ? $this->formatToFloat($catSecondTotal) : '-') . '</td>
                    <td width="25px">-</td>
                    <td width="25px">-</td>
                    <td width="25px">-</td>
                    <td width="25px">-</td>
                    <td width="25px">-</td>
                    <td width="25px">-</td>
                    <td width="25px">-</td>
                </tr>';
    }

    private function generateFundFooter($fund, $fundTotal, $fundFirstTotal, $fundSecondTotal)
    {
        return '<tr style="font-size: 10px; font-weight:bold; text-align: center; background-color: #f2f2f2;">
                    <td width="375px">Total Amount for ' . $fund->fund_name . '</td>
                    <td width="90px" style="text-align: right;">' . ($fundTotal != 0 ? $this->formatToFloat($fundTotal) : '-') . '</td>
                    <td width="82px" style="text-align: right;">' . ($fundFirstTotal != 0 ? $this->formatToFloat($fundFirstTotal) : '-') . '</td>
                    <td width="25px">-</td>
                    <td width="25px">-</td>
                    <td width="25px">-</td>
                    <td width="82px" style="text-align: right;">' . ($fundSecondTotal != 0 ? $this->formatToFloat($fundSecondTotal) : '-') . '</td>
                    <td width="25px">-</td>
                    <td width="25px">-</td>
                    <td width="25px">-</td>
                    <td width="25px">-</td>
                    <td width="25px">-</td>
                    <td width="25px">-</td>
                    <td width="25px">-</td>
                </tr>';
    }

    private function generateFundFooterFirstSemester($category, $catTotal, $catFirstTotal){
        return '<tr style="font-size: 10px; font-weight:bold; text-align: center; background-color: #f2f2f2;">
                    <td width="432px">Total Amount for ' . htmlspecialchars($category->cat_name) . '</td>
                    <td width="90px" style="text-align: right;">' . ($catTotal != 0 ? $this->formatToFloat($catTotal) : '-') . '</td>
                    <td width="82px" style="text-align: right;">' . ($catFirstTotal != 0 ? $this->formatToFloat($catFirstTotal) : '-') . '</td>
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

    private function generateCategoryFooterFirstSemester($fund, $fundTotal, $fundFirstTotal)
    {
        return '<tr style="font-size: 10px; font-weight:bold; text-align: center; background-color: #f2f2f2;">
                    <td width="432px">Total Amount for ' . $fund->fund_name . '</td>
                    <td width="90px" style="text-align: right;">' . ($fundTotal != 0 ? $this->formatToFloat($fundTotal) : '-') . '</td>
                    <td width="82px" style="text-align: right;">' . ($fundFirstTotal != 0 ? $this->formatToFloat($fundFirstTotal) : '-') . '</td>
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

    private function formatToFloat($number)
    {
        return number_format($number, 2, '.', ',');
    }

    private function formatToInteger($number)
    {
        return number_format($number, 0, '.', ',');
    }
}

