<?php

namespace App\Http\Controllers\Pdf;

use App\Http\Controllers\Controller;
use App\Models\Office;
use App\Models\PpmpTransaction;
use App\Services\SumPdf;
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
        
        $individualPpmp = PpmpTransaction::with('particulars', 'requestee')
            ->where('ppmp_year', $ppmp->ppmp_year)
            ->where('ppmp_type', 'individual')
            ->where('qty_adjustment', $ppmp->qty_adjustment)
            ->where('price_adjustment', $ppmp->price_adjustment)
            ->get();

        $individualPpmp = $individualPpmp->map(function($office) {
            return [
                'ppmpId' => $office->id,
                'ppmpYear' => $office->ppmp_year,
                'offCode' => $office->requestee->office_code,
                'particulars' => $office->particulars,
            ];
        })->sortBy('offCode');

        $pdf = new SumPdf('L', 'mm', array(297, 420), true, 'UTF-8', false, $ppmp->ppmp_code);

        $logoPath = public_path('assets/images/benguet_logo.png');
        $pilipinasPath = public_path('assets/images/Bagong_Pilipinas_logo.png');

        $pdf->SetCreator(SYSTEM_GENERATOR);
        $pdf->SetAuthor(SYSTEM_DEVELOPER);
        $pdf->SetTitle('Component Overview - PPMP');
        $pdf->SetSubject('Component Overview Particulars');
        $pdf->SetKeywords('Benguet, WarePro, Component Overview');
        
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(true);

        $pdf->SetFooterMargin(10);
        $pdf->SetAutoPageBreak(TRUE, 10);

        $pdf->AddPage();

        $pdf->Image($logoPath, 148, 15, 15, '', '', '', '', false, 300, '', false, false, 0, false, false, false);
        $pdf->Image($pilipinasPath, 255, 15, 15, '', '', '', '', false, 300, '', false, false, 0, false, false, false);

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
                    <h6>Component Overview</h6>
                </div>
            </div>
            <br>
            ';     
        $pdf->writeHTML($html, true, false, true, false, '');
        $table = '<table border="1" cellpadding="2" cellspacing="0">';
        $table .= '<thead>';
        $table .= $this->tableHeader($individualPpmp);
        $table .= '</thead>';
        $table .= '<tbody>';
        $table .= $this->tableContent($individualPpmp);
        $table .= '</tbody>';
        $table .= '</table>';
        $pdf->writeHTML($table, true, false, true, false, '');
        $pdf->Output('Component Overview', 'I');
    }

    protected function tableHeader($individualPpmp)
    {
        $header = '<tr style="font-size: 8px; font-weight:bold; text-align:center; background-color: #EEEEEE;">
                    <th width="90px">Item Stock No.</th>
                ';

        foreach($individualPpmp as $office) {
            $header .= '<th width="36px">'. $office['offCode'] .'</th>';
        }

        $header .=  '</tr>';

        return $header;
    }

    protected function tableContent($individualPpmp)
    {
        $content = '';
        $funds = $this->productService->getAllProduct_FundModel();

        foreach ($funds as $fund) {
            if ($fund->categories->isNotEmpty()) {
                $content .= '<tr class="bg-gray-100" style="font-size: 10px; font-weight: bold;">
                            <td width="100%">' . strtoupper($fund->fund_name) . '</td>
                            </tr>';
                foreach ($fund->categories as $category) {
                    if ($category->items->isNotEmpty()) {
                        $content .= '<tr class="bg-gray-100" style="font-size: 10px; font-weight: bold;">
                                    <td width="100%">' . sprintf('%02d', (int) $category->cat_code) . ' - ' . $category->cat_name . '</td>
                                    </tr>';

                        foreach ($category->items as $item) {
                            if ($item->products->isNotEmpty()) {  
                                foreach ($item->products as $product) {
                                    $content .= '<tr style="font-size: 8px; text-align: center;">
                                        <td style="font-size: 9px;" width="90px">' . $product->prod_newNo . '</td>
                                        ';

                                        foreach($individualPpmp as $office) {
                                            $matchedParticulars = collect($office['particulars'])->filter(function ($particular) use ($product) {
                                                return $particular['prod_id'] === $product->id;
                                            });

                                            $total = $matchedParticulars->sum(function ($particular) {
                                                $qtyFirst = isset($particular['qty_first']) ? $particular['qty_first'] : 0;
                                                $qtySecond = isset($particular['qty_second']) ? $particular['qty_second'] : 0;

                                                return $qtyFirst + $qtySecond;
                                            });

                                            $content .= '<td style="font-size: 8px;" width="36px">' . ($total != 0 ? $total : '-') . '</td>';
                                        }
                                    $content .= '</tr>';
                                }  
                            }         
                        }
                    }
                }
            }
        }

        return $content;
    }
}
