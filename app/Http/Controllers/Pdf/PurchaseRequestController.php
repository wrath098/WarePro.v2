<?php

namespace App\Http\Controllers\Pdf;

use App\Http\Controllers\Controller;
use App\Models\PpmpTransaction;
use App\Models\PrTransaction;
use App\Services\MyPDF;
use App\Services\ProductService;
use Illuminate\Http\Request;

class PurchaseRequestController extends Controller
{
    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function generatePdf_purchaseRequestDraft(PrTransaction $pr)
    {
        $pdf = new MyPDF('p', 'mm', array(203.2, 330.2), true, 'UTF-8', false);

        $logoPath = public_path('assets/images/benguet_logo.png');
        $pilipinasPath = public_path('assets/images/Bagong_Pilipinas_logo.png');

        $pdf->SetCreator(SYSTEM_GENERATOR);
        $pdf->SetAuthor(SYSTEM_DEVELOPER);
        $pdf->SetTitle('Consolidated PPMP');
        $pdf->SetSubject('Consolidated PPMP Particulars');
        $pdf->SetKeywords('Benguet, WarePro, Consolidated List');
        
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(true);

        $pdf->SetFooterMargin(10);
        $pdf->SetAutoPageBreak(TRUE, 10);

        $pdf->AddPage();

        $pdf->Image($logoPath, 54, 15, 13, '', '', '', '', false, 300, '', false, false, 0, false, false, false);
        $pdf->Image($pilipinasPath, 134, 15, 13, '', '', '', '', false, 300, '', false, false, 0, false, false, false);

        $html = '
            <div style="line-height: 0.75; text-align: center; font-size: 10px;">
                <div style="line-height: 0.75; ">
                    <h3>PROVINCE OF BENGUET</h3>
                    <p>PURCHASE REQUEST</p>
                </div>
            </div>
            ';     
        $pdf->writeHTML($html, true, false, true, false, '');
        $headTable = '<table border="1" cellpadding="2" cellspacing="0">
                        <thead>
                            <tr style="font-size: 10px;">
                                <th width="120px">Department: <b>PGSO</b></th>
                                <th width="240px">PR No.</th>
                                <th width="159px">Date : </th>
                            </tr>
                            <tr style="font-size: 10px;">
                                <th width="120px">Division: <b>PGSO-WH</b></th>
                                <th width="240px">OBR No.</th>
                                <th width="159px">Date :</th>
                            </tr>
                        <thead>
                    </table> 
        ';

        $pdf->writeHTML($headTable, true, false, true, false, '');
        $table = '<table border="1" cellpadding="2" cellspacing="0">';
        $table .= '<thead>';
        $table .= $this->tableHeader();
        $table .= '</thead>';
        $table .= '<tbody>';
        $table .= $this->tableContent($pr);
        $table .= '</tbody>';
        $table .= '</table>';
        $pdf->writeHTML($table, true, false, true, false, '');

        // $table2 = '
        //     <br>
        //     <table>
        //         <thead>
        //             <tr style="font-size: 11px;">
        //                 <th style="margin-left: 15px;" width="293px" rowspan="3">Prepared By:</th>
        //                 <th style="margin-left: 15px;" width="293px" rowspan="3">Reviewed By:</th>
        //                 <th style="margin-left: 15px;" width="293px" rowspan="3">Approved By:</th>
        //             </tr>
        //         </thead>
        //         <tbody>
        //             <tr><td width="100%"><br><br></td></tr>
        //             <tr style="font-size: 11px; font-weight:bold; text-align:center;">
        //                 <td width="293px">AILEEN G. RAMOS</td>
        //                 <td width="293px">ELIZABETH C. TININGGAL</td>
        //                 <td width="293px">JENNIFER G. BAHOD</td>
        //             </tr>
        //             <tr style="font-size: 11px; text-align:center;">
        //                 <td width="293px">Administrative Officer IV</td>
        //                 <td width="293px">Administrative Officer V</td>
        //                 <td width="293px">Provincial General Services Officer</td>
        //             </tr>
        //         </tbody>
        //     </table>
        //     ';     
        // $pdf->writeHTML($table2, true, false, true, false, '');

        $pdf->Output('PR.pdf', 'I');
    }

    protected function tableHeader()
    {
        return '<tr style="font-size: 10px; font-weight:bold; text-align:center; background-color: #EEEEEE;">
                    <th width="30px">Item No</th>
                    <th width="50px">New Stock. No</th>
                    <th width="40px">Unit of Issue</th>
                    <th width="240px">Item Description</th>
                    <th width="39px">Qty</th>
                    <th width="50px">Unit Price</th>
                    <th width="70px">Total Cost</th>
                </tr>
        ';
    }

    protected function tableContent($pr)
    {
        $text = '';

        $grandTotal = 0;
        $index = 0;
        $particulars = $pr->prParticulars()->get();

        $filteredParticular = $particulars->map(function($particular) {
            $totalCost = $particular->qty * (float)$particular->unitPrice;
            return [
                'prodId' => $particular->prod_id,
                'prodCode' => $this->productService->getProductCode($particular->prod_id),
                'prodName' => $particular->revised_specs,
                'prodUnit' => $particular->unitMeasure,
                'prodPrice' => $particular->unitPrice,
                'qty' => $particular->qty,
                'totalCost' => $totalCost,
            ];
        });

        $sortedParticulars = $filteredParticular->sortBy('prodCode');
        $categories = $this->productService->getAllProduct_Category();

        foreach ($categories as $category) {
            if ($category->items->isNotEmpty()) {
                $text .= '<tr class="bg-gray-100" style="font-size: 10px; font-weight: bold;">
                            <td width="100%">' . sprintf('%02d', (int) $category->cat_code) . ' - ' . $category->cat_name . '</td>
                            </tr>';
                $totalCatAmount = 0;
                foreach ($category->items as $item) {
                    if ($item->products->isNotEmpty()) {  
                        foreach ($item->products as $product) { 
                            $matchedParticulars = $sortedParticulars->filter(function ($particular) use ($product) {
                                return $particular['prodCode'] === $product->prod_newNo;
                            });
                            
                            if ($matchedParticulars->isNotEmpty()) {
                                foreach ($matchedParticulars as $particular) {
                                    $index++;
                                    $totalCatAmount += $particular['totalCost'];
                                    $text .= '<tr style="font-size: 9px; text-align: center;">
                                        <td width="30px">' . $index . '</td>
                                        <td width="50px">' . $particular['prodCode'] . '</td>
                                        <td width="40px">' . $particular['prodUnit']. '</td>
                                        <td width="240px" style="text-align: left;">('. $item->item_name .') ' . $particular['prodName'] . '</td>
                                        <td width="39px" style="text-align: right;">' . $particular['qty'] . '</td>
                                        <td width="50px" style="text-align: right;">' . $particular['prodPrice'] . '</td>
                                        <td width="70px" style="text-align: right;">' . number_format($particular['totalCost'], 2, '.', ',') . '</td>
                                    </tr>';
                                }
                            }
                        }  
                    }      
                }
            }
            $grandTotal += $totalCatAmount;
            $text .= '<tr style="font-size: 9px; text-align: right;">
                        <td width="360px"><b>Sub Total</b></td>
                        <td width="159px">' . number_format($totalCatAmount, 2, '.', ',') . '</td>
                    </tr>';  
        }
        $text .= '<tr style="font-size: 9px; text-align: right;">
                        <td width="360px"><b>GRAND TOTAL</b></td>
                        <td width="159px"><u>' . number_format($grandTotal, 2, '.', ',') . '</u></td>
                    </tr>';
        
        return $text;
    }
}
