<?php

namespace App\Http\Controllers\Pdf;

use App\Http\Controllers\Controller;
use App\Models\Office;
use App\Models\RisTransaction;
use App\Services\MyPDF;
use App\Services\ProductService;
use Illuminate\Http\Request;

class IssuanceController extends Controller
{
    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function generatePdf_Issuance(Request $request)
    {
        $risTransaction = RisTransaction::with('requestee')
            ->where('ris_no', $request->transactionId)
            ->where('issued_to', $request->issuedTo)
            ->first();

        $office = $risTransaction->requestee ? $risTransaction->requestee->office_name : $risTransaction->remarks ;
        $risNumber = $risTransaction->ris_no;
        $dateReleased = $risTransaction->created_at->format('m-d-Y');

        $pdf = new MyPDF('P', 'mm', array(203.2, 330.2), true, 'UTF-8', false);
        $logoPath = public_path('assets/images/benguet_logo.png');
        $pilipinasPath = public_path('assets/images/Bagong_Pilipinas_logo.png');

        $pdf->SetCreator(SYSTEM_GENERATOR);
        $pdf->SetAuthor(SYSTEM_DEVELOPER);
        $pdf->SetTitle('Requisition And Issuance Slip');
        $pdf->SetSubject('Requisition And Issuance Slip');
        $pdf->SetKeywords('Benguet, WarePro, Requisition And Issuance Slip');

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
                <div style="line-height: 1; text-align: center; font-size: 10px;">
                    <h4>REQUISITION AND ISSUANCES SLIP</h4>
                </div>
            </div>';  

        $pdf->writeHTML($html, true, false, true, false, '');

        $headTable = '<table border="1" cellpadding="2" cellspacing="0">
                        <thead>
                            <tr style="font-size: 9px;">
                                <th width="45px" style="font-weight:bold;">OFFICE</th>
                                <th width="246px">'. $office .'</th>
                                <th width="45px" style="font-weight:bold;">RIS NO.</th>
                                <th width="75px">'. $risNumber .'</th>
                                <th width="40px" style="font-weight:bold;">DATE</th>
                                <th width="68px">'. $dateReleased .'</th>
                            </tr>
                            <tr style="font-size: 9px;">
                                <th width="45px" style="font-weight:bold;">DIVISION</th>
                                <th width="246px"></th>
                                <th width="45px" style="font-weight:bold;">SAI NO.</th>
                                <th width="75px"></th>
                                <th width="40px" style="font-weight:bold;">DATE</th>
                                <th width="68px"></th>
                            </tr>
                            <tr style="font-size: 9px;">
                                <th width="100px" style="font-weight:bold;">Resp. Center Code</th>
                                <th width="419px"></th>
                            </tr>
                        </thead>
                    </table> 
        ';

        $pdf->writeHTML($headTable, true, false, true, false, '');

        $table = '<table border="1" cellpadding="2" cellspacing="0">';
        $table .= '<thead>';
        $table .= $this->tableHeader();
        $table .= '</thead>';
        $table .= '<tbody>';
        $table .= $this->tableContent($risTransaction->ris_no, $risTransaction->issued_to, $risTransaction);
        $table .= '</tbody>';
        $table .= '</table>';
        $pdf->writeHTML($table, true, false, true, false, '');
        $pdf->Output(strtoupper('asd') . '.pdf', 'I');
    }

    protected function tableHeader()
    {
        return '<tr style="font-size: 10px; font-weight:bold; text-align:center; background-color: #EEEEEE;">
                    <th width="411px">Requisition</th>
                    <th width="108px">Issuances</th>
                </tr>
                <tr style="font-size: 9px; font-weight:bold; text-align:center; background-color: #EEEEEE;">
                    <th width="24px">NO.</th>
                    <th width="50px">STOCK NO.</th>
                    <th width="50px">UNIT</th>
                    <th width="185px">ITEM DESCRIPTION</th>
                    <th width="50px">PRICE</th>
                    <th width="52px">QUANTITY</th>
                    <th width="54px">QUANTITY</th>
                    <th width="54px">REMARKS</th>
                </tr>
        ';
    }

    protected function tableContent(string $risNo, string $issuedTo, $transactionInfo)
    {
        $text = '';
        $count = 0;
        $officeRequestee = '';
        $officeHead = '';
        $officeHeadPosition = '';

        $risParticulars = RisTransaction::with(['productDetails', 'requestee'])
            ->where('ris_no', $risNo)
            ->where('issued_to', $issuedTo)
            ->get();

        $gsoInfo = Office::where('office_name', 'Provincial General Services Office')->first();

        foreach($risParticulars as $particular) {
            $prodPrice = $this->productService->getLatestPrice($particular->prod_id);
            $count++;

            $text .= '<tr style="font-size: 9px; text-align:center;">
                <td width="24px">'. $count.'</td>
                <td width="50px">'. $particular->productDetails->prod_newNo .'</td>
                <td width="50px">'. $particular->unit .'</td>
                <td width="185px" style="text-align:left;">'. $particular->productDetails->prod_desc .'</td>
                <td width="50px" style="text-align:right;">'. $prodPrice .'</td>
                <td width="52px">'. $particular->qty .'</td>
                <td width="54px">'. $particular->qty .'</td>
                <td width="54px"></td>
            </tr>';
        }

        if($transactionInfo && $transactionInfo->requestee) {
            $officeRequestee = $transactionInfo->requestee->office_name;
            $officeHead = strtoupper($transactionInfo->requestee->office_head);
            $officeHeadPosition = $transactionInfo->requestee->position_head;
        } else {
            $office = Office::where('office_code', $transactionInfo->remarks)->first();
            $officeRequestee = $office->office_name;
            $officeHead = strtoupper($office->office_head);
            $officeHeadPosition = $office->position_head;
        }

        $text .= '<tr style="font-size: 9px; border:1px solid #000;">
                    <td height="30px" width="124px" style="border:1px solid #000; font-weight:bold; text-align:center;">Purpose: </td>
                    <td height="30px" width="395px">For '. $officeRequestee .'\'s use</td>
                </tr>';
        $text .= '<tr style="font-size:9px">
                    <td width="70px" style="border:1px solid #000; border-bottom:1px solid #fff;"></td>
                    <td width="120px" style="border:1px solid #000; border-bottom:1px solid #fff;">Requested by:</td>
                    <td width="120px" style="border:1px solid #000; border-bottom:1px solid #fff;">Approved by: </td>
                    <td width="110px" style="border:1px solid #000; border-bottom:1px solid #fff;">Issued by:</td>
                    <td width="99px" style="border:1px solid #000; border-bottom:1px solid #fff;">Received by:</td>
                </tr>
                
                <tr style="font-size: 9px;">
                    <td width="70px" style="border:1px solid #000; border-bottom:1px solid #fff; border-top:1px solid #fff;"></td>
                    <td width="120px" style="border:1px solid #000; border-bottom:1px solid #fff; border-top:1px solid #fff;"></td>
                    <td width="120px" style="border:1px solid #000; border-bottom:1px solid #fff; border-top:1px solid #fff;"></td>
                    <td width="110px" style="border:1px solid #000; border-bottom:1px solid #fff; border-top:1px solid #fff;"></td>
                    <td width="99px" style="border:1px solid #000; border-bottom:1px solid #fff; border-top:1px solid #fff;"></td>
                </tr>
                <tr style="font-size: 9px; font-weight:bold;">
                    <td width="70px" style="border:1px solid #000; border-bottom:1px solid #fff; border-top:1px solid #fff;"><b>Signature</b></td>
                    <td width="120px" style="border:1px solid #000; border-bottom:1px solid #fff; border-top:1px solid #fff;"></td>
                    <td width="120px" style="border:1px solid #000; border-bottom:1px solid #fff; border-top:1px solid #fff;"></td>
                    <td width="110px" style="border:1px solid #000; border-bottom:1px solid #fff; border-top:1px solid #fff;"></td>
                    <td width="99px" style="border:1px solid #000; border-bottom:1px solid #fff; border-top:1px solid #fff;"></td>
                </tr>
                <tr style="font-size: 9px; font-weight:bold;">
                    <td width="70px" style="border:1px solid #000; border-bottom:1px solid #fff; border-top:1px solid #fff;"><b>Printed Name</b></td>
                    <td width="120px" style="border:1px solid #000; border-bottom:1px solid #fff; border-top:1px solid #fff; text-align:center;">'. $officeHead .'</td>
                    <td width="120px" style="border:1px solid #000; border-bottom:1px solid #fff; border-top:1px solid #fff; text-align:center;">'. strtoupper($gsoInfo->office_head) . '</td>
                    <td width="110px" style="border:1px solid #000; border-bottom:1px solid #fff; border-top:1px solid #fff; text-align:center;">AILEEN G. RAMOS</td>
                    <td width="99px" style="border:1px solid #000; border-bottom:1px solid #fff; border-top:1px solid #fff; text-align:center;">'. $transactionInfo->issued_to .'</td>
                </tr>
                <tr style="font-size: 8px;">
                    <td width="70px" style="border:1px solid #000; border-bottom:1px solid #fff; border-top:1px solid #fff; font-size: 9px;"><b>Designation</b></td>
                    <td width="120px" style="border:1px solid #000; border-bottom:1px solid #fff; border-top:1px solid #fff; text-align:center;">'. $officeHeadPosition .'</td>
                    <td width="120px" style="border:1px solid #000; border-bottom:1px solid #fff; border-top:1px solid #fff; text-align:center;">'. $gsoInfo->position_head .'</td>
                    <td width="110px" style="border:1px solid #000; border-bottom:1px solid #fff; border-top:1px solid #fff; text-align:center;">Administrative Officer III</td>
                    <td width="99px" style="border:1px solid #000; border-bottom:1px solid #fff; border-top:1px solid #fff; text-align:center;"></td>
                </tr>
                <tr style="font-size: 9px; font-weight:bold;">
                    <td width="70px" style="border:1px solid #000; border-top:1px solid #fff; font-size: 9px;"><b>Date</b></td>
                    <td width="120px" style="border:1px solid #000; border-top:1px solid #fff; text-align:center;"></td>
                    <td width="120px" style="border:1px solid #000; border-top:1px solid #fff; text-align:center;"></td>
                    <td width="110px" style="border:1px solid #000; border-top:1px solid #fff; text-align:center;"></td>
                    <td width="99px" style="border:1px solid #000; border-top:1px solid #fff; text-align:center;"></td>
                </tr>
                ';

        return $text;
    }
}
