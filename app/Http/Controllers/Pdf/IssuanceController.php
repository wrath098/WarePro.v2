<?php

namespace App\Http\Controllers\Pdf;

use App\Http\Controllers\Controller;
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

        $office = $risTransaction->requestee->office_name;
        $risNumber = $risTransaction->ris_no;
        $dateReleased = $risTransaction->created_at->format('m-d-Y');

        //dd($risTransaction->toArray());
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
                            <tr style="font-size: 10px;">
                                <th width="45px" style="font-weight:bold;">OFFICE</th>
                                <th width="246px">'. $office .'</th>
                                <th width="45px" style="font-weight:bold;">RIS NO.</th>
                                <th width="75px" style="text-align:center;">'. $risNumber .'</th>
                                <th width="40px" style="font-weight:bold;">DATE</th>
                                <th width="68px" style="text-align:center;">'. $dateReleased .'</th>
                            </tr>
                        </thead>
                    </table> 
        ';

        $pdf->writeHTML($headTable, true, false, true, false, '');

        $table = '<table border="1" cellpadding="2" cellspacing="0">';
        $table .= '<thead>';
        // $table .= $this->tableHeader();
        $table .= '</thead>';
        $table .= '<tbody>';
        // $table .= $this->tableContent($ppmp);
        $table .= '</tbody>';
        $table .= '</table>';
        $pdf->writeHTML($table, true, false, true, false, '');
        $pdf->Output(strtoupper('asd') . '.pdf', 'I');
    }
}
