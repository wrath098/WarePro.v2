<?php

namespace App\Http\Controllers\Pdf;

use App\Http\Controllers\Controller;
use App\Models\IarParticular;
use App\Models\IarTransaction;
use App\Models\ProductInventory;
use App\Models\ProductInventoryTransaction;
use App\Models\RisTransaction;
use App\Services\MyPDF;
use App\Services\ProductService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AsOfStockCardController extends Controller
{
    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function generatePdf_StockCard(Request $request)
    {
        $query = $request->productDetails;

        $query['prodUnit'] = $this->productService->getProductUnit($query['prodId']);
        $query['stockNo'] = $this->productService->getProductCode($query['prodId']);
        $query['reorder'] = $this->productService->getProductReorderPoint($query['prodId']);

        $startDate =  Carbon::now()->firstOfYear();
        $endDate = Carbon::parse($request->productDetails['endDate']);
        $customEndDate = $endDate->setTime(23, 59, 59);
        $formattedEndDate = $customEndDate->format('Y-m-d H:i:s');
        
        $duration = $endDate->format('F d, Y') ;

        $inventoryTransactions = $this->getProductInventoryTransactions($query['prodId'], $startDate, $formattedEndDate);
        $totalRequest = count($inventoryTransactions);

        $pdf = new MyPDF('P', 'mm', array(203.2, 330.2), true, 'UTF-8', false);
        $logoPath = public_path('assets/images/benguet_logo.png');
        $pilipinasPath = public_path('assets/images/Bagong_Pilipinas_logo.png');

        $pdf->SetCreator(SYSTEM_GENERATOR);
        $pdf->SetAuthor(SYSTEM_DEVELOPER);
        $pdf->SetTitle('Stock Card');
        $pdf->SetSubject('StockCard');
        $pdf->SetKeywords('Benguet, WarePro, StockCard, Stock Card');
        
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
                    <h4>STOCK CARD</h4>
                    <h5>As of '. $duration .'</h5>
                </div>
            </div>
            <br>
            ';     
        $pdf->writeHTML($html, true, false, true, false, '');

        $headTable = '<table border="1" cellpadding="2" cellspacing="0">
                        <thead>
                            <tr style="font-size: 10px;">
                                <th width="60px" rowspan="2" style="font-weight:bold;">Item</th>
                                <th width="324px" rowspan="2">'. $query['product'].'</th>
                                <th width="87px" style="font-weight:bold;">Unit</th>
                                <th width="48px" style="text-align:center;">'. $query['prodUnit'].'</th>
                            </tr>
                            <tr style="font-size: 10px;">
                                <th width="87px" style="font-weight:bold;">Re-order Point</th>
                                <th width="48px" style="text-align:center;">'. $query['reorder'].'</th>
                            </tr>
                            <tr style="font-size: 10px;">
                                <th width="60px" style="font-weight:bold;">Stock No</th>
                                <th width="324px">'. $query['stockNo'].'</th>
                                <th width="87px" style="font-weight:bold;">Total Request</th>
                                <th width="48px" style="text-align:center;">'. $totalRequest.'</th>
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
        $table .= $this->tableContent($inventoryTransactions, $query['prodId']);
        $table .= '</tbody>';
        $table .= '</table>';
        $pdf->writeHTML($table, true, false, true, false, '');

        $pdf->Output('product.pdf', 'I');
    }

    protected function tableHeader()
    {
        return '<tr style="font-size: 9px; font-weight:bold; text-align:center; background-color: #EEEEEE;">
                    <th width="60px" rowspan="2">DATE</th>
                    <th width="150px" rowspan="2">REFERENCE</th>
                    <th width="87px" colspan="2">RECEIPT</th>
                    <th width="87px" colspan="2">ISSUANCE</th>
                    <th width="87px" rowspan="2">BALANCE</th>
                    <th width="48px" rowspan="2">REMARK/S</th>
                </tr>
                <tr style="font-size: 8px; font-weight:bold; background-color: #EEEEEE;">
                    <th width="35px" style="text-align:center;">Qty</th>
                    <th width="52px" style="text-align:center;">Unit Cost</th>
                    <th width="35px" style="text-align:center;">Qty</th>
                    <th width="52px" style="text-align:center;">Office</th>
                </tr>';
    }

    protected function tableContent(iterable $inventoryTransactions, int $prodId)
    {
        $text = '';
        
        $lastTransaction  = $inventoryTransactions->last();
        $beginningBalance = $this->getBeginningBalance($prodId);

        $text .= '
                    <tr style="font-size: 9px; font-weight:bold;">
                        <td width="60px"></td>
                        <td width="150px" style="text-align:center;">**** Beginning Balance ****</td>
                        <td width="35px" style="text-align:center;">'. number_format($beginningBalance, 0, '.', ',') .'</td>
                        <td width="52px"></td>
                        <td width="35px"></td>
                        <td width="52px"></td>
                        <td width="87px" style="text-align:center;">'. number_format($beginningBalance, 0, '.', ',') .'</td>
                        <td width="48px"></td>
                    </tr>
                ';

        foreach($inventoryTransactions as $transaction) {
            $text .= '
                <tr style="font-size: 9px;">
                    <td width="60px" style="text-align:center;">' . $transaction['created'] . '</td>
                    <td width="150px">';

                    if ($transaction['type'] == 'issuance') {
                        $text .= 'RIS #' . $transaction['risDetails']['risNo'];
                    } elseif ($transaction['type'] == 'purchase') {
                        $text .= 'IAR #' . $transaction['iarDetails']['iarNo'] . '<br>PO #' . $transaction['iarDetails']['poNo'];
                    } elseif ($transaction['type'] == 'adjustment') {
                        $text .= '';
                    }

            $text .= '</td>
                    <td width="35px" style="text-align:center;">' . ($transaction['type'] != 'issuance' ? number_format($transaction['qty'], 0, '.', ',') : '') . '</td>
                    <td width="52px"style="text-align:right;">' . ($transaction['type'] == 'purchase' ? $transaction['iarDetails']['price'] : '') . '</td>
                    <td width="35px" style="text-align:center;">' . ($transaction['type'] == 'issuance' ? number_format($transaction['qty'], 0, '.', ',') : '') . '</td>
                    <td width="52px" style="text-align:center;">' . ($transaction['type'] == 'issuance' ? $transaction['risDetails']['officeCode'] : '') . '</td>
                    <td width="87px" style="text-align:center;">' . number_format($transaction['currentStock'], 0, '.', ',') . '</td>
                    <td width="48px" style="text-align:center;">' . ($transaction['type'] == 'issuance' ? $transaction['risDetails']['remarks'] : '') . '</td>
                </tr>
            ';
        }
        
        $text .= '
                    <tr style="font-size: 9px; font-weight:bold;">
                        <td width="60px"></td>
                        <td width="150px" style="text-align:center;">**** Ending Balance ****</td>
                        <td width="35px" style="text-align:center;">'. number_format($lastTransaction['currentStock'], 0, '.', ',') .'</td>
                        <td width="52px"></td>
                        <td width="35px"></td>
                        <td width="52px"></td>
                        <td width="87px" style="text-align:center;">'. number_format($lastTransaction['currentStock'], 0, '.', ',') .'</td>
                        <td width="48px"></td>
                    </tr>
                ';

        return $text;
    }

    private function getProductInventoryTransactions($productId, $fromDate, $toDate)
    {
        $productUnit = $this->productService->getProductUnit($productId);

        return ProductInventoryTransaction::withTrashed()
                ->where('prod_id', $productId)
                ->whereBetween('created_at', [$fromDate, $toDate])
                ->oldest('created_at')
                ->get()
                ->map(function($transaction) use ($productUnit) {
                    $issuanceDetails = '';
                    $purchaseDetails = '';
                    $adjustmentDetails = '';
                    if($transaction->type == 'purchase') {
                        $purchaseDetails  = $this->getPurchaseTypeDetails($transaction->ref_no);
                    } elseif ($transaction->type == 'issuance') {
                        $issuanceDetails = $this->getIssuanceTypeDetails($transaction->ref_no);
                    } elseif ($transaction->type == 'adjustment') {
                        $adjustmentDetails = '';
                    } else {
                        return null;
                    }

                    return [
                        'id' => $transaction->id,
                        'created' => $transaction->created_at->format('Y-m-d'),
                        'unit' => $productUnit,
                        'type' => $transaction->type,
                        'qty' => $transaction->qty,
                        'adjustedTotalStock' => $transaction->current_stock,
                        'iarDetails' => $purchaseDetails,
                        'risDetails' => $issuanceDetails,
                        'adjustmentDetails' => $adjustmentDetails,
                        'currentStock' => $transaction->current_stock,
                    ];
                });
    }

    private function getPurchaseTypeDetails(int $id): array
    {
        $iarParticular = IarParticular::withTrashed()->select('air_id', 'price')->find($id);

        if ($iarParticular) {
            $transaction = IarTransaction::select('sdi_iar_id', 'po_no')
                ->where('id', $iarParticular->air_id)
                ->first();

            if ($transaction) {
                return [
                    'iarNo' => $transaction->sdi_iar_id,
                    'poNo' => $transaction->po_no,
                    'price' => $iarParticular->price ? number_format($iarParticular->price, 2, '.', ',') : 0.00,
                ];
            }
        }

        return [];
    }

    private function getIssuanceTypeDetails(int $id): array
    {
        $risTransaction = RisTransaction::withTrashed()
            ->select('ris_no', 'office_id', 'remarks')
            ->find($id);

        if($risTransaction) {
            $requestee = $risTransaction->requestee()->select('office_code')->first();
            return [
                'risNo' => $risTransaction->ris_no,
                'officeCode' => $requestee ? $requestee->office_code : 'Others',
                'remarks' => $risTransaction->remarks ?? '',
            ];
        }

        return [];
    }

    private function getBeginningBalance(int $id): int
    {
        return ProductInventory::withTrashed()
            ->where('prod_id', $id)
            ->value('qty_physical_count') ?? 0;
    }
}
