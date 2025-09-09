<?php

namespace App\Http\Controllers\Pdf;

use App\Models\PpmpTransaction;
use App\Services\PpmpPDF;
use App\Services\ProductService;
use Illuminate\Http\Request;

class ApprovedConsolidatedPpmpController extends TemplateController
{
    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function generatePdf_ApprovedConsolidatedPpmp(PpmpTransaction $ppmp)
    {
        $pdf = new PpmpPDF('L', 'mm', array(203.2, 330.2), true, 'UTF-8', false, $ppmp->ppmp_code);

        $logoPath = public_path('assets/images/benguet_logo.png');
        $pilipinasPath = public_path('assets/images/Bagong_Pilipinas_logo.png');

        $pdf->SetCreator(SYSTEM_GENERATOR);
        $pdf->SetAuthor(SYSTEM_DEVELOPER);
        $pdf->SetTitle('Consolidated PPMP | ' . $ppmp->ppmp_status);
        $pdf->SetSubject('Consolidated PPMP Particulars');
        $pdf->SetKeywords('Benguet, WarePro, Consolidated List');
        
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
            <br>
            ';     
        $pdf->writeHTML($html, true, false, true, false, '');
        $table = '<table border="1" cellpadding="2" cellspacing="0">';
        $table .= '<thead>';
        $table .= $this->consolidationHeader();
        $table .= '</thead>';
        $table .= '<tbody>';
        $table .= $this->tableContent($ppmp);
        $table .= '</tbody>';
        $table .= '</table>';
        $pdf->writeHTML($table, true, false, true, false, '');
        $table2 = $this->ppmpFooter();     
        $pdf->writeHTML($table2, true, false, true, false, '');

        $pdf->Output('cPPMP-' . strtoupper($ppmp->ppmp_status) . '-' . $ppmp->ppmp_status . '.pdf', 'I');
    }

    protected function tableContent(PpmpTransaction $ppmpTransaction)
    {
        $text = '';

        $recapitulation = [];
        $ppmpTransaction->load('updater', 'consolidated');

        $sortedParticulars = $this->formattedAndSortedParticulars($ppmpTransaction);
        $funds = $this->productService->getAllProduct_FundModel();

        foreach ($funds as $fund) {
            $fundFirstTotal = 0; 
            $fundSecondTotal = 0;
            $fundTotal = 0;

            if ($fund->categories->isNotEmpty()) {
                $text .= '<tr class="bg-gray-100" style="font-size: 10px; font-weight: bold;">
                            <td width="100%">' . strtoupper($fund->fund_name) . '</td>
                            </tr>';

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
                                            $secondQtyAmount =  $particular['qtySecond'] * (float) $particular['prodPrice'];
                                            $prodQtyAmount = $firstQtyAmount + $secondQtyAmount;
                                            $text .= $prodQtyAmount > 0 ? '<tr style="font-size: 9px; text-align: center;">' : '<tr style="font-size: 9px; text-align: center; background-color:#f87171;">';
                                            $text .= '
                                                <td width="40px">' . $product->prod_oldNo . '</td>
                                                <td width="45px">' . $product->prod_newNo . '</td>
                                                <td width="190px" style="text-align: left;">' . $product->prod_desc . '</td>
                                                <td width="45px">' . $product->prod_unit. '</td>
                                                <td width="45px" style="text-align: right;">' . number_format($particular['prodPrice'], 2, '.', ',') . '</td>
                                                <td width="40px" style="text-align: right;">' . number_format($prodQty, 0, '.', ',') . '</td>
                                                <td width="60px" style="text-align: right;">' . number_format($prodQtyAmount, 2, '.', ',') . '</td>
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
                        $recapitulation[$fund->fund_name][] =  [
                            'name' => sprintf('%02d', (int) $category->cat_code) . ' - ' . $category->cat_name,
                            'total' => $catTotal,
                            'firstSem' => $catFirstTotal,
                            'secondSem' => $catSecondTotal,
                        ];
                    }
                    $text .= '<tr style="font-size: 10px; font-weight:bold; text-align: center; background-color: #f2f2f2;">
                            <td width="365px">Total Amount for ' . htmlspecialchars($category->cat_name) . '</td>
                            <td width="100px" style="text-align: right;">' . ($catTotal != 0 ? number_format($catTotal, 2, '.', ',') : '-') . '</td>
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
                            <td width="365px">Total Amount for ' . $fund->fund_name . '</td>
                            <td width="100px" style="text-align: right;">' . ($fundTotal != 0 ? number_format($fundTotal, 2, '.', ',') : '-') . '</td>
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
           
            $capitalOutlay = $this->productService->getCapitalOutlay($ppmpTransaction->ppmp_year, $fund->id);

            if($capitalOutlay > 0) {
                $firstSem = '1st';
                $secondSem = '2nd';
                $contingency = $capitalOutlay - $fundTotal;
                $contingencyFirst = $this->productService->getCapitalOutlayContingency($ppmpTransaction->ppmp_year, $fund->id, $firstSem);
                $contingencySecond = $this->productService->getCapitalOutlayContingency($ppmpTransaction->ppmp_year, $fund->id, $secondSem);

                $grandTotalFirstQty = $fundFirstTotal + $contingencyFirst;
                $grandTotalSecondQty = $fundSecondTotal + $contingencySecond;
                $granTotal = $fundTotal + $contingency;

                $text .= '<tr style="font-size: 10px; font-weight:bold; text-align: center; background-color: #f2f2f2;">
                            <td width="365px">Total Amount of Contingency</td>
                            <td width="100px" style="text-align: right;">' . ($contingency != 0 ? number_format($contingency, 2, '.', ',') : '-') . '</td>
                            <td width="82px" style="text-align: right;">' . ($contingencyFirst != 0 ? number_format($contingencyFirst, 2, '.', ',') : '-') . '</td>
                            <td width="25px">-</td>
                            <td width="25px">-</td>
                            <td width="25px">-</td>
                            <td width="82px" style="text-align: right;">' . ($contingencySecond != 0 ? number_format($contingencySecond, 2, '.', ',') : '-') . '</td>
                            <td width="25px">-</td>
                            <td width="25px">-</td>
                            <td width="25px">-</td>
                            <td width="25px">-</td>
                            <td width="25px">-</td>
                            <td width="25px">-</td>
                            <td width="25px">-</td>
                        </tr>
                        <tr style="font-size: 10px; font-weight:bold; text-align: center; background-color: #f2f2f2;">
                            <td width="365px">Grand Total</td>
                            <td width="100px" style="text-align: right;">' . ($granTotal != 0 ? number_format($granTotal, 2, '.', ',') : '-') . '</td>
                            <td width="82px" style="text-align: right;">' . ($grandTotalFirstQty != 0 ? number_format($grandTotalFirstQty, 2, '.', ',') : '-') . '</td>
                            <td width="25px">-</td>
                            <td width="25px">-</td>
                            <td width="25px">-</td>
                            <td width="82px" style="text-align: right;">' . ($grandTotalSecondQty != 0 ? number_format($grandTotalSecondQty, 2, '.', ',') : '-') . '</td>
                            <td width="25px">-</td>
                            <td width="25px">-</td>
                            <td width="25px">-</td>
                            <td width="25px">-</td>
                            <td width="25px">-</td>
                            <td width="25px">-</td>
                            <td width="25px">-</td>
                        </tr>';

                $recapitulation[$fund->fund_name][] = [
                    'name' => 'Contingency',
                    'total' => $contingency,
                    'firstSem' => $contingencyFirst,
                    'secondSem' => $contingencySecond,
                ];
            }
        }
        
        $text .= '<tr>
                    <td style="border:1px solid #fff;" width="100%">
                        <p style="font-size: 10px; line-height: 0.4;"><i>Note: Technical specifications for each item/request being proposed shall be submitted as part of the PPMP.</i></p>
                        <br>
                    </td>
                </tr>
                <tr>
                    <td width="100%" style="line-height: 0.00001; border:1px solid black; border-left:1px solid white;  border-right:1px solid white;"></td>
                </tr>
                ';
        
        $text .= '<tr style="font-size: 10px; font-weight:bold;">
                    <td width="100%" style="line-height: 2; border:1px solid black;">Recapitulation</td>
                </tr>';

        $overAllAmount = 0;
        $overAllFirstAmount = 0;
        $overAllSecondAmount = 0;
        foreach ($recapitulation as $expenses => $fund) {
            $text .= '<tr style="font-size: 10px; font-weight:bold;">
                    <td width="100%" style="border:1px solid #000;"> <span style="color:#fff;">&nbsp</span> '. $expenses .'</td>
                </tr>';
            
            $allAmount = 0;
            $firstAmount = 0;
            $secondAmount = 0;
            foreach ($fund as $cat) {
                $allAmount += $cat['total'];
                $firstAmount += $cat['firstSem'];
                $secondAmount += $cat['secondSem'];
                if($cat['name'] != 'Contingency') {
                    $text .= '<tr style="font-size: 10px; font-weight:bold;">
                        <td width="55px" style="border-left:1px solid #000; border-right:0px solid #fff; border-bottom:1px solid #000;"></td>
                        <td width="320px" style="border-left:0px solid #fff; border-right:0px solid #fff; border-bottom:1px solid #000;">'. $cat['name'] .'</td>
                        <td width="90px" style="text-align:right; border:1px solid #fff; border-bottom:1px solid #000;">'. number_format($cat['total'], 2, '.', ',') .'</td>
                        <td width="82px" style="text-align:right; border:1px solid #fff; border-bottom:1px solid #000;border-right:1px solid #fff;">'. number_format($cat['firstSem'], 2, '.', ',') .'</td>
                        <td width="75px" style="border:1px solid #fff; border-bottom:1px solid #000;border-right:1px solid #fff;"></td>
                        <td width="82px" style="text-align:right; border:1px solid #fff; border-bottom:1px solid #000;border-right:1px solid #fff;">'. number_format($cat['secondSem'], 2, '.', ',') .'</td>
                        <td width="175px" style="border:1px solid #fff; border-bottom:1px solid #000;border-right:1px solid #000;"></td>
                    </tr>';
                } else {
                    $text .= '<tr style="font-size: 10px; font-weight:bold;">
                        <td width="75px" style="border-left:1px solid #000; border-right:0px solid #fff; border-bottom:1px solid #000;"></td>
                        <td width="300px" style="border-left:0px solid #fff; border-right:0px solid #fff; border-bottom:1px solid #000;">'. $cat['name'] .'</td>
                        <td width="90px" style="text-align:right; border:1px solid #fff; border-bottom:1px solid #000;">'. number_format($cat['total'], 2, '.', ',') .'</td>
                        <td width="82px" style="text-align:right; border:1px solid #fff; border-bottom:1px solid #000;border-right:1px solid #fff;">'. number_format($cat['firstSem'], 2, '.', ',') .'</td>
                        <td width="75px" style="border:1px solid #fff; border-bottom:1px solid #000;border-right:1px solid #fff;"></td>
                        <td width="82px" style="text-align:right; border:1px solid #fff; border-bottom:1px solid #000;border-right:1px solid #fff;">'. number_format($cat['secondSem'], 2, '.', ',') .'</td>
                        <td width="175px" style="border:1px solid #fff; border-bottom:1px solid #000;border-right:1px solid #000;"></td>
                    </tr>';
                }
            }
            $text .= '<tr style="font-size: 10px; font-weight:bold;">
                    <td width="375px" style="border:1px solid #fff; border-bottom:1px solid #000; border-left:1px solid #000;"><span style="color:white;">&nbsp</span> Total</td>
                    <td width="90px" style="text-align:right; border:1px solid #fff; border-bottom:1px solid #000;">'. number_format($allAmount, 2, '.', ',') .'</td>
                    <td width="82px" style="text-align:right; border:1px solid #fff; border-bottom:1px solid #000;border-right:1px solid #fff;">'. number_format($firstAmount, 2, '.', ',') .'</td>
                    <td width="75px" style="border:1px solid #fff; border-bottom:1px solid #000;border-right:1px solid #fff;"></td>
                    <td width="82px" style="text-align:right; border:1px solid #fff; border-bottom:1px solid #000;border-right:1px solid #fff;">'. number_format($secondAmount, 2, '.', ',') .'</td>
                    <td width="175px" style="border:1px solid #fff; border-bottom:1px solid #000;border-right:1px solid #000;"></td>
                </tr>';
            $overAllAmount += $allAmount;
            $overAllFirstAmount += $firstAmount;
            $overAllSecondAmount += $secondAmount;
        }
        $text .= '<tr style="font-size: 10px; font-weight:bold;">
                    <td width="375px" style="border:1px solid #fff; border-bottom:1px solid #000; border-left:1px solid #000;">Grand Total</td>
                    <td width="90px" style="text-align:right; border:1px solid #fff; border-bottom:1px solid #000;"><u>'. number_format($overAllAmount, 2, '.', ',') .'</u></td>
                    <td width="82px" style="text-align:right; border:1px solid #fff; border-bottom:1px solid #000;border-right:1px solid #fff;"><u>'. number_format($overAllFirstAmount, 2, '.', ',') .'</u></td>
                    <td width="75px" style="border:1px solid #fff; border-bottom:1px solid #000;border-right:1px solid #fff;"></td>
                    <td width="82px" style="text-align:right; border:1px solid #fff; border-bottom:1px solid #000;border-right:1px solid #fff;"><u>'. number_format($overAllSecondAmount, 2, '.', ',') .'</u></td>
                    <td width="175px" style="border:1px solid #fff; border-bottom:1px solid #000;border-right:1px solid #000;"></td>
                </tr>';
        
        return $text;
    }

    private function formattedAndSortedParticulars($ppmpTransaction)
    {
        $groupParticulars = $ppmpTransaction->consolidated->map(function ($items) use ($ppmpTransaction) {
            $prodPrice = (float)$this->productService->getLatestPriceId($items->price_id) * $ppmpTransaction->price_adjustment;

            $qtyFirst = (int) $items->qty_first;
            $qtySecond = (int) $items->qty_second;

            return [
                'prodId' => $items->prod_id,
                'prodCode' => $this->productService->getProductCode($items->prod_id),
                'prodName' => $this->productService->getProductName($items->prod_id),
                'prodUnit' => $this->productService->getProductUnit($items->prod_id),
                'prodPrice' => $prodPrice,
                'qtyFirst' => $qtyFirst,
                'qtySecond' => $qtySecond,
            ];
        });
        
        return $groupParticulars->sortBy('prodCode');
    }
}
