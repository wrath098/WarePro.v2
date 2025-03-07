<?php

namespace App\Http\Controllers\Pdf;

use App\Http\Controllers\Controller;
use App\Models\Office;
use App\Models\PpmpTransaction;
use App\Services\PpmpPDF;
use App\Services\ProductService;
use Illuminate\Http\Request;

class SummaryOfConsolidatedPpmpController extends Controller
{
    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function generatePdf_summaryOfConso(PpmpTransaction $ppmp)
    {
        $officePpmp = $this->getOfficePpmp($ppmp->ppmp_year);

        $sortOfficePpmp = $this->formattedAndSort($officePpmp);

        $pdf = new PpmpPDF('L', 'mm', array(420, 594), true, 'UTF-8', false, $ppmp->ppmp_code);
        $logoPath = public_path('assets/images/benguet_logo.png');
        $pilipinasPath = public_path('assets/images/Bagong_Pilipinas_logo.png');

        $pdf->SetCreator(SYSTEM_GENERATOR);
        $pdf->SetAuthor(SYSTEM_DEVELOPER);
        $pdf->SetTitle('Summary Overview - PPMP');
        $pdf->SetSubject('Summary Overview Particulars');
        $pdf->SetKeywords('Benguet, WarePro, Summary Overview');
        
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(true);

        $pdf->SetFooterMargin(10);
        $pdf->SetAutoPageBreak(TRUE, 10);

        $pdf->AddPage();

        $pdf->Image($logoPath, 235, 15, 15, '', '', '', '', false, 300, '', false, false, 0, false, false, false);
        $pdf->Image($pilipinasPath, 345, 15, 15, '', '', '', '', false, 300, '', false, false, 0, false, false, false);

        $html = '
            <div style="line-height: 0.01;">
                <div style="line-height: 0.5; text-align: center; font-size: 10px;">
                    <p>Republic of the Philippines</p>
                    <p>PROVINCE OF BENGUET</p>
                    <p>La Trinidad</p>
                    <h5>PROVINCIAL GENERAL SERVICES OFFICE</h5>
                </div>
                <div style="line-height: 0.60; text-align: center; font-size: 10px;">
                    <h4>PROJECT PROCUREMENT MANAGEMENT PLAN</h4>
                    <h5>OFFICE & JANITORIAL SUPPLIES</h5>
                    <h6>Summary Overview</h6>
                </div>
            </div>
            <br>
            ';     
        $pdf->writeHTML($html, true, false, true, false, '');
        $table = '<table border="1" cellpadding="2" cellspacing="0">';
        $table .= '<thead>';
        $table .= $this->tableHeader($sortOfficePpmp);
        $table .= '</thead>';
        $table .= '<tbody>';
        $table .= $this->tableContent($sortOfficePpmp, $ppmp);
        $table .= '</tbody>';
        $table .= '</table>';
        $pdf->writeHTML($table, true, false, true, false, '');
        $pdf->Output('Component Overview', 'I');
    }

    protected function tableHeader($sortOfficePpmp)
    {
        $header = '<tr style="font-size: 8px; font-weight:bold; text-align:center; background-color: #EEEEEE;">
                    <th width="90px">Item Stock No.</th>
                    <th width="250px">Description.</th>
                ';

        foreach($sortOfficePpmp as $office) {
            $header .= '<th width="42px">'. $office['offCode'] .'</th>';
        }

        $header .=  '<th width="69px">Total</th>
                </tr>';

        return $header;
    }

    protected function tableContent($sortOfficePpmp, $ppmp)
    {
        $content = '';
        $funds = $this->productService->getAllProduct_FundModel();

        foreach ($funds as $fund) {

            if ($fund->categories->isNotEmpty()) {
                $content .= $this->fundHeader($fund);

                foreach ($fund->categories as $category) {

                    if ($category->items->isNotEmpty()) {
                        $content .= $this->categoryHeader($category);

                        foreach ($category->items as $item) {

                            if ($item->products->isNotEmpty()) {  

                                foreach ($item->products as $product) {

                                    $overallTotal = 0;
                                    $productDesc = $this->productService->getProductName($product->id);
                                    $content .= '<tr style="font-size: 8px; text-align: center;">
                                        <td style="font-size: 9px;" width="90px">' . $product->prod_newNo . '</td>
                                        <td style="font-size: 9px;text-align: left;" width="250px">' . $productDesc . '</td>
                                        ';

                                    $content .= $this->totalAmountOfProductPerOffice($sortOfficePpmp, $product, $overallTotal, $content, $ppmp);

                                    $content .= '<td style="font-size: 8px;" width="69px">' . ($overallTotal != 0 ? number_format($overallTotal, 0, '.', ',') : '-') . '</td>
                                            </tr>';
                                }  
                            }         
                        }
                    }
                }
            }
        }

        return $content;
    }

    private function getOfficePpmp($year)
    {
        return PpmpTransaction::with('particulars', 'requestee')
            ->where('ppmp_year', $year)
            ->where('ppmp_type', 'individual')
            ->where('ppmp_status', 'draft')
            ->get();
    }

    private function formattedAndSort($officePpmp)
    {
        return $officePpmp->map(function($office) {
            return [
                'ppmpId' => $office->id,
                'ppmpYear' => $office->ppmp_year,
                'offCode' => $office->requestee->office_code,
                'particulars' => $office->particulars,
            ];
        })->sortBy('offCode');
    }

    private function fundHeader($fund)
    {
        return '<tr class="bg-gray-100" style="font-size: 10px; font-weight: bold;">
            <td width="100%">' . strtoupper($fund->fund_name) . '</td>
        </tr>';
    }

    private function categoryHeader($category)
    {
        return '<tr class="bg-gray-100" style="font-size: 10px; font-weight: bold;">
                    <td width="100%">' . sprintf('%02d', (int) $category->cat_code) . ' - ' . $category->cat_name . '</td>
                </tr>';
    }

    private function totalAmountOfProductPerOffice($sortOfficePpmp, $product, &$overallTotal, &$content, $ppmp)
    {
        $adjustment = (float) $ppmp->qty_adjustment;
        $threshold = (float) $ppmp->tresh_adjustment;

        foreach($sortOfficePpmp as $office) {
            $matchedParticulars = collect($office['particulars'])->where('prod_id', $product->id);

            $isProductExempted = $this->productService->validateProductExcemption($product->id);

            $total = $matchedParticulars->sum(function ($particular) use ($adjustment, $threshold, $isProductExempted) {
                $qtyFirst = $this->calculateAdjustedQty($particular['qty_first'], $adjustment, $threshold, $isProductExempted);
                $qtySecond = $this->calculateAdjustedQty($particular['qty_second'], $adjustment, $threshold, $isProductExempted);

                return $qtyFirst + $qtySecond;
            });

            $overallTotal += $total;

            $content .= '<td style="font-size: 8px;" width="42px">' . ($total != 0 ? $total : '-') . '</td>';
        }
    }

    private function calculateAdjustedQty($qty, $adjustment, $threshold, $isExempted)
    {
        if (!$isExempted && $qty > 1) {
            return floor(((int)$qty * (float)$adjustment) * (float)$threshold);
        }

        return (int)$qty;
    }
}
