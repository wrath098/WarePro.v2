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

    public function generatePdf_ApprovedConsolidatedPpmp(PpmpTransaction $ppmp, Request $request)
    {
        $version = $request->query('version');
        $pdf = new PpmpPDF('L', 'mm', array(203.2, 330.2), true, 'UTF-8', false, $ppmp->ppmp_code);

        $logoPath = public_path('assets/images/benguet_logo.png');
        $pilipinasPath = public_path('assets/images/Bagong_Pilipinas_logo.png');

        $pdf->SetCreator(SYSTEM_GENERATOR);
        $pdf->SetAuthor(SYSTEM_DEVELOPER);
        $pdf->SetTitle(strtoupper($version) . ' | Consolidated');
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
        $table .= $this->tableContent($ppmp, $version);
        $table .= '</tbody>';
        $table .= '</table>';
        $pdf->writeHTML($table, true, false, true, false, '');   

        $pdf->Output('cPPMP-' . strtoupper($ppmp->ppmp_status) . '-' . $ppmp->ppmp_status . '.pdf', 'I');
    }

    protected function tableContent(PpmpTransaction $ppmpTransaction, $version)
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
                $text .= '<tr class="bg-gray-100" style="font-size: 8px; font-weight: bold;">
                            <td width="880px" colspan="16">' . strtoupper($fund->fund_name) . '</td>
                            </tr>';

                foreach ($fund->categories as $category) {
                    if ($category->items->isNotEmpty()) {
                        $text .= '<tr class="bg-gray-100" style="font-size: 8px; font-weight: bold;">
                                    <td width="880px" colspan="16">' . sprintf('%02d', (int) $category->cat_code) . ' - ' . $category->cat_name . '</td>
                                    </tr>';

                        $catFirstTotal = 0;
                        $catSecondTotal = 0;
                        $catTotal = 0;

                        $allCategoryParticulars = [];
                        foreach ($category->items as $item) {
                            if ($item->products->isNotEmpty()) {
                                foreach ($item->products as $product) {
                                    $matchedParticulars = $sortedParticulars
                                        ->filter(fn ($p) => $p['prodCode'] === $product->prod_newNo);

                                    foreach ($matchedParticulars as $particular) {
                                        $allCategoryParticulars[] = [
                                            'product' => $product,
                                            'particular' => $particular,
                                        ];
                                    }
                                }
                            }
                        }

                        usort($allCategoryParticulars, function ($a, $b) {
                            $aIsDA = $a['particular']['procurement_mode'] === 'DA/DC' ? 1 : 0;
                            $bIsDA = $b['particular']['procurement_mode'] === 'DA/DC' ? 1 : 0;

                            if ($aIsDA !== $bIsDA) return $aIsDA - $bIsDA;

                            return strcmp($a['particular']['prodCode'], $b['particular']['prodCode']);
                        });

                        foreach ($allCategoryParticulars as $row) {
                            $product = $row['product'];
                            $particular = $row['particular'];

                            $prodQty = $particular['qtyFirst'] + $particular['qtySecond'];
                            $firstQtyAmount = $particular['qtyFirst'] * (float) $particular['prodPrice'];
                            $secondQtyAmount = $particular['qtySecond'] * (float) $particular['prodPrice'];
                            $prodQtyAmount = $firstQtyAmount + $secondQtyAmount;

                            $text .= $prodQtyAmount > 0
                                ? '<tr nobr="true" style="font-size: 8px; text-align: center;">'
                                : '<tr nobr="true" style="font-size: 8px; text-align: center; background-color:#f87171;">';

                            $text .= '
                                <td width="50px"></td>
                                <td width="40px">Goods</td>
                                <td width="45px">' . $product->prod_newNo . '</td>
                                <td width="30px" style="text-align: right;">' . number_format($prodQty, 0, ".", ",") . '</td>
                                <td width="35px">' . $product->prod_unit . '</td>
                                <td width="250px" style="text-align: left;">' . $product->prod_desc . '</td>
                                <td width="50px" style="text-align: right;">' . number_format($particular['prodPrice'], 2, ".", ",") . '</td>
                                <td width="50px">' . $particular['procurement_mode'] . '</td>
                                <td width="40px">' . ($particular['ppc'] ? 'Yes' : 'No') . '</td>
                                <td width="40px">' . $particular['start_pa'] . '</td>
                                <td width="40px">' . $particular['end_pa'] . '</td>
                                <td width="40px">' . $particular['expected_delivery'] . '</td>
                                <td style="font-size: 7px" width="50px">General Fund</td>
                                <td style="font-size: 7px; text-align: right;" width="40px">' . number_format($prodQtyAmount, 2, ".", ",") . '</td>
                                <td width="40px"></td>
                                <td width="40px"></td>
                            </tr>';

                            $catFirstTotal += $firstQtyAmount;
                            $catSecondTotal += $secondQtyAmount;
                            $catTotal += $prodQtyAmount;
                        }

                        $recapitulation[$fund->fund_name][] = [
                            'name' => sprintf('%02d', (int) $category->cat_code) . ' - ' . $category->cat_name,
                            'total' => $catTotal,
                            'firstSem' => $catFirstTotal,
                            'secondSem' => $catSecondTotal,
                        ];
                    }

                    $text .= '<tr style="font-size: 8px; font-weight:bold; text-align: center; background-color: #f2f2f2;">
                            <td colspan="12">Total Amount for ' . htmlspecialchars($category->cat_name) . '</td>
                            <td colspan="2" style="text-align: right;">' . ($catTotal != 0 ? number_format($catTotal, 2, '.', ',') : '-') . '</td>
                            <td></td>
                            <td></td>
                        </tr>';

                    $fundFirstTotal += $catFirstTotal;
                    $fundSecondTotal += $catSecondTotal;
                    $fundTotal += $catTotal;
                }
                $text .= '<tr style="font-size: 8px; font-weight:bold; text-align: center; background-color: #f2f2f2;">
                            <td colspan="12">Grand Total Amount for ' . $fund->fund_name . '</td>
                            <td colspan="2" style="text-align: right;">' . ($fundTotal != 0 ? number_format($fundTotal, 2, '.', ',') : '-') . '</td>
                            <td></td>
                            <td></td>
                        </tr>';
            }
           
            if($version == 'contingency') {
                $capitalOutlay = $this->productService->getCapitalOutlay($ppmpTransaction->ppmp_year, $fund->id);

                $firstSem = '1st';
                $secondSem = '2nd';

                $contingency = $capitalOutlay - $fundTotal;
                $contingencyFirst = $this->productService->getCapitalOutlayContingency($ppmpTransaction->ppmp_year, $fund->id, $firstSem);
                $contingencySecond = $this->productService->getCapitalOutlayContingency($ppmpTransaction->ppmp_year, $fund->id, $secondSem);

                $grandTotalFirstQty = $fundFirstTotal + $contingencyFirst;
                $grandTotalSecondQty = $fundSecondTotal + $contingencySecond;
                $granTotal = $fundTotal + $contingency;

                $text .= '<tr style="font-size: 8px; font-weight:bold; text-align: center; background-color: #f2f2f2;">
                            <td colspan="12">Total Amount of Contingency</td>
                            <td colspan="2" style="text-align: right;">' . ($contingency != 0 ? number_format($contingency, 2, '.', ',') : '-') . '</td>
                            <td></td>
                            <td></td>
                            </tr>
                        <tr style="font-size: 8px; font-weight:bold; text-align: center; background-color: #f2f2f2;">
                            <td colspan="12">Grand Total</td>
                            <td colspan="2" style="text-align: right;">' . ($granTotal != 0 ? number_format($granTotal, 2, '.', ',') : '-') . '</td>
                            <td></td>
                            <td></td>
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
                    <td width="880px" style="border:1px solid black; padding:6px; font-size:10px; line-height:1.2;">
                        <div style="margin:0; padding:0;"> 
                            <i> 
                                <b>Note:</b> Technical specifications for each item/request being proposed shall be submitted as part of the PPMP. 
                            </i>
                        </div>
                        <div style="margin-top:4px;">
                            <i>
                                <b>Recommended Modes of Procurement:</b>
                                    <ul style="padding-left:20px; ">     
                                        <li>Bidding</li>
                                        <li>SVP - Small Value Procurement</li>
                                        <li>DA/DC - Direct Acquisition/Direct Contracting</li>
                                    </ul>
                            </i>
                        </div>
                    </td>
                </tr>';

        $text .= '</tbody></table><br><br>';
        $text .= '<table nobr="true" border="1" cellpadding="2" cellspacing="0">';
        $text .= '<tbody>';
        $text .= '<tr style="font-size: 10px; font-weight:bold;">
                    <td width="440px" style="line-height: 2; border-left:1px solid #000; border-top:1px solid #000; border-bottom:1px solid #000; border-right:0px solid #fff;">Recapitulation</td>
                    <td width="440px" style="line-height: 2; border-right:1px solid #000; border-top:1px solid #000; border-bottom:1px solid #000; border-left:0px solid #fff; text-align: right;">Estimated Budget</td>
                </tr>';

        $overAllAmount = 0;
        $overAllFirstAmount = 0;
        $overAllSecondAmount = 0;
        foreach ($recapitulation as $expenses => $fund) {
            $text .= '<tr style="font-size: 10px; font-weight:bold;">
                    <td width="880px" style="border:1px solid #000;"> <span style="color:#fff;">&nbsp</span> '. $expenses .'</td>
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
                        <td width="625px" style="border-left:0px solid #fff; border-right:0px solid #fff; border-bottom:1px solid #000;">'. $cat['name'] .'</td>
                        <td width="200px" style="text-align:right; border-left:0px solid #fff; border-right:1px solid #000; border-bottom:1px solid #000;">'. number_format($cat['total'], 2, '.', ',') .'</td>
                    </tr>';
                } else {
                    $text .= '<tr style="font-size: 10px; font-weight:bold;">
                        <td width="55px" style="border-left:1px solid #000; border-right:0px solid #fff; border-bottom:1px solid #000;"></td>
                        <td width="625px" style="border-left:0px solid #fff; border-right:0px solid #fff; border-bottom:1px solid #000;">'. $cat['name'] .'</td>
                        <td width="200px" style="text-align:right; border-left:0px solid #fff; border-right:1px solid #000; border-bottom:1px solid #000;">'. number_format($cat['total'], 2, '.', ',') .'</td>
                    </tr>';
                }
            }
            $text .= '<tr style="font-size: 10px; font-weight:bold;">
                    <td width="440px" style="border:1px solid #fff; border-bottom:1px solid #000; border-left:1px solid #000;"><span style="color:white;">&nbsp</span> Total</td>
                    <td width="440px" style="text-align:right; border-left:0px solid #fff; border-right:1px solid #000; border-bottom:1px solid #000;">'. number_format($allAmount, 2, '.', ',') .'</td>
                </tr>';
            $overAllAmount += $allAmount;
            $overAllFirstAmount += $firstAmount;
            $overAllSecondAmount += $secondAmount;
        }
        $text .= '<tr style="font-size: 10px; font-weight:bold;">
                    <td width="440px" style="border:1px solid #fff; border-bottom:1px solid #000; border-left:1px solid #000;">Grand Total</td>
                    <td width="440px" style="text-align:right; border-left:0px solid #fff; border-right:1px solid #000; border-bottom:1px solid #000;">'. number_format($overAllAmount, 2, '.', ',') .'</td>
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
                'procurement_mode' => $items->procurement_mode,
                'ppc' => $items->ppc,
                'start_pa' => $items->start_pa,
                'end_pa' => $items->end_pa,
                'expected_delivery' => $items->expected_delivery,
                'estimated_budget' => $items->estimated_budget,
                'supporting_doc' => $items->supporting_doc,
                'remarks' => $items->remarks,
            ];
        });
        
        return $groupParticulars->sortBy([
    fn ($p) => $p['procurement_mode'] === 'DA/DC' ? 1 : 0,
    fn ($p) => $p['prodCode'],
]);
    }
}
