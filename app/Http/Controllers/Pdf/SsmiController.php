<?php

namespace App\Http\Controllers\Pdf;

use App\Http\Controllers\Controller;
use App\Models\RisTransaction;
use App\Services\ProductService;
use App\Services\MyPDF;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SsmiController extends Controller
{
    protected $productService;
    protected $numberOfReleasedItems;
    protected $numberOfIssuances;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function generatePdf_ssmi(Request $request)
    {        
        // $response = Http::withHeaders([
        //     'x-api-key' => '2idqUEqD16WlkMwoWohuluNqFIm9ZqKmsw4GuSsM15E'
        // ])->get('http://192.168.2.50/api/getEmployees');

        $query = $request->filters;

        $startDate = Carbon::parse($query['startDate']);
        $endDate = Carbon::parse($query['endDate']);

        $customEndDate = $endDate->setTime(23, 59, 59);
        
        $formattedStartDate = $startDate->format('Y-m-d H:i:s');
        $formattedEndDate = $customEndDate->format('Y-m-d H:i:s');

        $filteredIssuanceLogs = $this->getFilteredIssuanceLogs($formattedStartDate, $formattedEndDate);

        if(strtoupper($startDate->format('F')) === strtoupper($endDate->format('F'))) {
            $month = strtoupper($startDate->format('F'));
            $from = $startDate->format('j');
            $to = $endDate->format('j');
            $year = $endDate->format('Y');

            $duration = $month. ' ' . $from . '-' . $to . ',' .  ' ' . $year;
        } else {
            $startMonth = strtoupper($startDate->format('F'));
            $startDay = $startDate->format('j');
            $startYear = $startDate->format('Y');

            $endMonth = strtoupper($endDate->format('F'));
            $endDay = $endDate->format('j');
            $endYear = $endDate->format('Y');

            $duration = $startMonth. ' ' . $startDay . ',' .  ' ' . $startYear . ' - ' . $endMonth. ' ' . $endDay . ',' .  ' ' . $endYear ;
        }

        $pdf = new MyPDF('P', 'mm', array(203.2, 330.2), true, 'UTF-8', false);
        $logoPath = public_path('assets/images/benguet_logo.png');
        $pilipinasPath = public_path('assets/images/Bagong_Pilipinas_logo.png');

        $pdf->SetCreator(SYSTEM_GENERATOR);
        $pdf->SetAuthor(SYSTEM_DEVELOPER);
        $pdf->SetTitle('RSMI');
        $pdf->SetSubject('Report of Supplies and Materials Issued');
        $pdf->SetKeywords('Benguet, WarePro, Consolidated List');
        
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
                    <h3>REPORT OF SUPPLIES AND MATERIALS ISSUED</h3>
                    <h5>For the Period of '. $duration .'</h5>
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
        $table .= $this->tableContent($filteredIssuanceLogs);
        $table .= '</tbody>';
        $table .= '</table>';
        $pdf->writeHTML($table, true, false, true, false, '');

        $pdf->writeHtml($this->footer(), true, false, true, false, '');

        $pdf->Output('product.pdf', 'I');
    }

    protected function tableHeader()
    {
        return '<tr style="font-size: 9px; font-weight:bold; text-align:center; background-color: #EEEEEE;">
                    <th width="65px" rowspan="2">Stock No.</th>
                    <th width="205px" rowspan="2">Item Description</th>
                    <th width="65px" rowspan="2">Unit of Measurement</th>
                    <th width="130px" colspan="2">Requisition and Issueance Slip (RIS)</th>
                    <th width="54px" rowspan="2">Total Quantity Issued</th>
                </tr>
                <tr style="font-size: 8px; font-weight:bold; background-color: #EEEEEE;">
                    <th width="80px" style="text-align:center;">No#</th>
                    <th width="50px" style="text-align:center;">Quantity Issued</th>
                </tr>';
    }
    
    protected function tableContent($issuanceLogs)
    {
        $text = '';
        $countItemIssued = 0;
        $countTransactions = 0;

        $categories = $this->productService->getAllProduct_Category();
        $productsOnCategory = $categories->mapWithKeys(function ($category) use ($issuanceLogs) {
            $totalMatchedItems = 0;

            foreach ($category->items as $item) {
                foreach ($item->products as $product) {
                    $matchedParticulars = $issuanceLogs->filter(function ($particular) use ($product) {
                        return $particular['prodId'] === $product->id;
                    });
                    $totalMatchedItems += $matchedParticulars->count();
                }
            }
            return [$category->cat_code => $totalMatchedItems];
        });

        foreach ($categories as $category) {
            $matchedCount = $productsOnCategory->get($category->cat_code, 0);
            if ($category->items->isNotEmpty()) {
                if($matchedCount  > 0 ) {
                    $text .= '<tr class="bg-gray-100" style="font-size: 10px; font-weight: bold;">
                                <td width="100%">' . sprintf('%02d', (int) $category->cat_code) . ' - ' . $category->cat_name . '</td>
                                </tr>';
                }

                foreach ($category->items as $item) {
                    if ($item->products->isNotEmpty()) {  
                        foreach ($item->products as $product) {
                            $matchedParticulars = $issuanceLogs->filter(function ($transaction) use ($product,) {
                                return $transaction['prodId'] === $product->id;
                            });

                            if ($matchedParticulars->isNotEmpty()) {
                                $countItemIssued += 1;
                                $count = count($matchedParticulars);
                                $index = 0;
                                $total = 0;
                                $first = true;

                                foreach ($matchedParticulars as $particular) {
                                    $countTransactions += 1;
                                    $index += 1;
                                    $total += (int) $particular['qty'];

                                    if ($first) {
                                        $first = false;
                                        if ($count == 1) {
                                            $text .= $this->generateRow($particular, $count, true, true, $total);
                                        } else {
                                            $text .= $this->generateRow($particular, $count, true, false);
                                        }
                                    }

                                    elseif ($index === $count) {
                                        $text .= $this->generateRow($particular, $count, false, true, $total);
                                    }

                                    else {
                                        $text .= $this->generateRow($particular, $count, false, false);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        // $text .= '<tr>
        //             <td style="border:1px solid #fff;" width="25%">
        //                 <p style="font-size: 10px; line-height: 0.4;"><i>Number of Items: <span>'. $countItemIssued .'</span></i></p>
        //             </td>
        //             <td style="border:1px solid #fff;" width="25%">
        //                 <p style="font-size: 10px; line-height: 0.4;"><i>Number of Issuances: <span>'. $countTransactions .'</span></i></p>
        //             </td>
        //             <td style="border:1px solid #fff;" width="50%">
        //                 <p style="font-size: 10px; line-height: 0.4;"></p>
        //             </td>
        //         </tr>
        //         ';

        return $text;
    }

    private function getFilteredIssuanceLogs($fromDate, $toDate)
    {
        $resultLogs = RisTransaction::whereBetween('created_at', [$fromDate, $toDate])
            ->with(['productDetails'])
            ->orderBy('created_at', 'asc')
            ->get();

        $logs = $resultLogs->map(fn($transaction) => [
            'id' => $transaction->id,
            'risNo' => $transaction->ris_no,
            'prodId' => $transaction->productDetails->id,
            'stockNo' => $transaction->productDetails->prod_newNo,
            'prodDesc' => $transaction->productDetails->prod_desc,
            'qty' => $transaction->qty,
            'unit' => $transaction->unit,
        ]);

        return $logs ?? '';
    }

    private function generateRow($particular, $count, $isFirst = false, $isLast = false, $total = null)
    {
        $totalText = $isLast ? $total : '';

        return '<tr style="font-size: 9px; text-align: center;">
                    <td width="65px">' . ($isFirst ? $particular['stockNo'] : '') . '</td>
                    <td width="205px" style="text-align: left;">' . ($isFirst ? $particular['prodDesc'] : '') . '</td>
                    <td width="65px">' . ($isFirst ? $particular['unit'] : '') . '</td>
                    <td width="80px" style="text-align:center;">' . $particular['risNo'] . '</td>
                    <td width="50px" style="text-align:center;">' . $particular['qty'] . '</td>
                    <td width="54px">' . $totalText . '</td>
                </tr>';
    }

    private function footer()
    {
        $signatories = $this->signatories();

        return '
            <table>
                <thead>
                    <tr style="font-size: 11px;">
                        <th style="margin-left: 15px;" width="293px" rowspan="3">Prepared By:</th>
                        <th style="margin-left: 15px;" width="293px" rowspan="3">Reviewed By:</th>
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
            <br>
            <br>
            <br>
            <br>
            <table>
                <thead>
                    <tr style="font-size: 11px; text-align:center;" >
                        <th style="margin-left: 15px;" width="300px">Certified Correct:</th>
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
}
