<?php

namespace App\Http\Controllers\Pdf;

use App\Http\Controllers\Controller;
use App\Models\PpmpTransaction;
use App\Services\PpmpPDF;
use App\Services\ProductService;
use Illuminate\Http\Request;

class EmergencyPpmpController extends Controller
{
    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function generatePdf_Ppmp(PpmpTransaction $ppmp)
    {
        $ppmp->load('consolidated', 'requestee');
        $pdf = new PpmpPDF('P', 'mm', array(203.2, 330.2), true, 'UTF-8', false, $ppmp->ppmp_code);
        $logoPath = public_path('assets/images/benguet_logo.png');
        $pilipinasPath = public_path('assets/images/Bagong_Pilipinas_logo.png');

        $pdf->SetCreator(SYSTEM_GENERATOR);
        $pdf->SetAuthor(SYSTEM_DEVELOPER);
        $pdf->SetTitle(strtoupper($ppmp->requestee->office_code));
        $pdf->SetSubject('Emergency Particulars');
        $pdf->SetKeywords('Benguet, WarePro, Individual List');

        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(true);

        $pdf->SetFooterMargin(10);
        $pdf->SetAutoPageBreak(TRUE, 10);

        $pdf->AddPage();

        $pdf->Image($logoPath, 45, 15, 15, '', '', '', '', false, 300, '', false, false, 0, false, false, false);
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
                    <h4>OFFICE & JANITORIAL SUPPLIES</h4>
                    <h5>'. $ppmp->ppmp_year.'</h5>
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
        $table .= $this->tableHeader();
        $table .= '</thead>';
        $table .= '<tbody>';
        $table .= $this->tableContent($ppmp);
        $table .= '</tbody>';
        $table .= '</table>';
        $pdf->writeHTML($table, true, false, true, false, '');
        $pdf->Output(strtoupper($ppmp->requestee->office_name) . '.pdf', 'I');
    }

    private function tableHeader()
    {
        return '<tr style="font-size: 10px; font-weight:bold; text-align:center; background-color: #EEEEEE;">
                    <th width="40px">Old Stock. No</th>
                    <th width="45px">New Stock. No</th>
                    <th width="249px">Item Description</th>
                    <th width="45px">Unit of Measure</th>
                    <th width="50px">Price</th>
                    <th width="40px">Total Qantity</th>
                    <th width="50px">Total Amount</th>
                </tr>';     
    }

    private function tableContent($ppmp)
    {
        $text = '';
        $sortedParticulars = $this->formattedAndSortedParticulars($ppmp);
        $funds = $this->productService->getAllProduct_FundModel();
        $categories = $this->productService->getAllProduct_Category();
        $productsOnCategory = $this->countProductsWithinCategory($categories, $sortedParticulars);

        foreach ($funds as $fund) {
            if ($fund->categories->isNotEmpty()) {
                
                $hasCategoryAvailable = $this->checkAvailableCategoryInFund($fund, $productsOnCategory);
                $text .= $hasCategoryAvailable ? $this->generateFundHeader($fund) : '';

                $fundFirstTotal = 0; 
                $fundTotal = 0;

                foreach ($fund->categories as $category) {
                    $catFirstTotal = 0; 
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

                $text .= $hasCategoryAvailable ? $this->generateFundFooterForFirstSemester($fund, $fundTotal, $fundFirstTotal) : '';
            }
        }

        return $text;
    }

    private function formattedAndSortedParticulars($ppmp)
    {
        $ppmpParticulars = $ppmp->consolidated->map(fn($particular) => [
            'id' => $particular->id,
            'qtyFirst' => $particular->qty_first,
            'qtySecond' => $particular->qty_second,
            'prodCode' => $this->productService->getProductCode($particular->prod_id),
            'prodName' => $this->productService->getProductName($particular->prod_id),
            'prodUnit' => $this->productService->getProductUnit($particular->prod_id),
            'prodPrice' => $this->productService->getLatestPrice($particular->prod_id),
        ]);
        
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

    public function generateProductContentForFirstSemester($particular, $product, &$text, &$catFirstTotal, &$catTotal)
    {
        $prodQty = $particular['qtyFirst'] + $particular['qtySecond'];
        $firstQtyAmount =  $particular['qtyFirst'] * (float) $particular['prodPrice'];
        $text .= '<tr style="font-size: 9px; text-align: center;">
            <td width="40px">' . $product->prod_oldNo . '</td>
            <td width="45px">' . $product->prod_newNo . '</td>
            <td width="249px" style="text-align: left;">'. $product->prod_desc . '</td>
            <td width="45px">' . $product->prod_unit. '</td>
            <td width="50px" style="text-align: right;">' . $this->formatToFloat($particular['prodPrice']) . '</td>
            <td width="40px" style="text-align: right;">' . $this->formatToInteger($prodQty) . '</td>
            <td width="50px" style="text-align: right;">' . $this->formatToFloat($firstQtyAmount) . '</td>
        </tr>';

        $catFirstTotal += $firstQtyAmount; 
        $catTotal += $firstQtyAmount;
    }

    private function formatToFloat($number)
    {
        return number_format($number, 2, '.', ',');
    }

    private function formatToInteger($number)
    {
        return number_format($number, 0, '.', ',');
    }

    private function generateCategoryFooterForFirstSemester($category, $catTotal, $catFirstTotal)
    {
        return '<tr style="font-size: 10px; font-weight:bold; text-align: center; background-color: #f2f2f2;">
                    <td width="334px">Total Amount for ' . htmlspecialchars($category->cat_name) . '</td>
                    <td width="95px" style="text-align: right;">' . ($catTotal != 0 ? $this->formatToFloat($catTotal) : '-') . '</td>
                    <td width="90px" style="text-align: right;">' . ($catFirstTotal != 0 ? $this->formatToFloat($catFirstTotal) : '-') . '</td>
                </tr>';
    }

    private function generateFundFooterForFirstSemester($fund, $fundTotal, $fundFirstTotal)
    {
        return '<tr style="font-size: 10px; font-weight:bold; text-align: center; background-color: #f2f2f2;">
                    <td width="334px">Total Amount for ' . $fund->fund_name . '</td>
                    <td width="95px" style="text-align: right;">' . ($fundTotal != 0 ? $this->formatToFloat($fundTotal) : '-') . '</td>
                    <td width="90px" style="text-align: right;">' . ($fundFirstTotal != 0 ? $this->formatToFloat($fundFirstTotal) : '-') . '</td>
                </tr>';
    }

    private function checkAvailableCategoryInFund($fund, $productsOnCategory)
    {
        $catCodes = collect($fund->categories)->pluck('cat_code')->toArray();
        $countAvailableCategory = 0;

        foreach ($catCodes as $code) {
            if (isset($productsOnCategory[$code]) && $productsOnCategory[$code] > 0) {
                $countAvailableCategory++;
            }
        }

        return $countAvailableCategory;
    }

}
