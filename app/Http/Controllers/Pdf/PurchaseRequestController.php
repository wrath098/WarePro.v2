<?php

namespace App\Http\Controllers\Pdf;

use App\Http\Controllers\Controller;
use App\Models\PrTransaction;
use App\Services\MyPDF;
use App\Services\ProductService;
use PhpOffice\PhpWord\TemplateProcessor;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

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
                        </thead>
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

        $productsOnCategory = $categories->mapWithKeys(function ($category) use ($sortedParticulars) {
            $totalMatchedItems = 0;

            foreach ($category->items as $item) {
                foreach ($item->products as $product) {
                    $matchedParticulars = $sortedParticulars->filter(function ($particular) use ($product) {
                        return $particular['prodCode'] === $product->prod_newNo;
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
                                        <td width="240px" style="text-align: left;">' . $particular['prodName'] . '</td>
                                        <td width="39px" style="text-align: right;">' . number_format($particular['qty'], 0, '.', ',') . '</td>
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
            if($totalCatAmount > 0) {
                $text .= '<tr style="font-size: 10px; text-align: right;">
                    <td width="360px"><b>Sub Total</b></td>
                    <td width="159px"><b>' . number_format($totalCatAmount, 2, '.', ',') . '</b></td>
                </tr>';  
            }
        }
        $text .= '<tr style="font-size: 10px; text-align: right;">
                        <td width="360px"><b>GRAND TOTAL</b></td>
                        <td width="159px"><b><u>' . number_format($grandTotal, 2, '.', ',') . '</u></b></td>
                    </tr>';
        $text .= '<tr style="font-size: 9px; font-weight:bold;">
                    <td width="100%" style="border:1px solid #000;"><span style="color:white;">&nbsp</span>Purpose: </td>
                </tr>
                <tr style="font-size: 9px; font-weight:bold;">
                    <td width="100%" height="30px" style="text-align:center;"><br/>Office supplies for common use.</td>
                </tr>';
        $text .= '<tr style="font-size:9px">
                    <td width="80px" style="border:1px solid #000; border-bottom:1px solid #fff;"></td>
                    <td width="140px" style="border:1px solid #000; border-bottom:1px solid #fff;">Requested by:</td>
                    <td width="140px" style="border:1px solid #000; border-bottom:1px solid #fff;">Cash Available: </td>
                    <td width="159px" style="border:1px solid #000; border-bottom:1px solid #fff;">Approved by:</td>
                </tr>
                
                <tr style="font-size: 9px;">
                    <td width="80px" style="border:1px solid #000; border-bottom:1px solid #fff; border-top:1px solid #fff;"></td>
                    <td width="140px" style="border:1px solid #000; border-bottom:1px solid #fff; border-top:1px solid #fff;"></td>
                    <td width="140px" style="border:1px solid #000; border-bottom:1px solid #fff; border-top:1px solid #fff;"></td>
                    <td width="159px" style="border:1px solid #000; border-bottom:1px solid #fff; border-top:1px solid #fff;"></td>
                </tr>
                <tr style="font-size: 9px; font-weight:bold;">
                    <td width="80px" style="border:1px solid #000; border-bottom:1px solid #fff; border-top:1px solid #fff;"><b>Signature</b></td>
                    <td width="140px" style="border:1px solid #000; border-bottom:1px solid #fff; border-top:1px solid #fff;"></td>
                    <td width="140px" style="border:1px solid #000; border-bottom:1px solid #fff; border-top:1px solid #fff;"></td>
                    <td width="159px" style="border:1px solid #000; border-bottom:1px solid #fff; border-top:1px solid #fff;"></td>
                </tr>
                <tr style="font-size: 9px; font-weight:bold;">
                    <td width="80px" style="border:1px solid #000; border-bottom:1px solid #fff; border-top:1px solid #fff;"><b>Printed Name</b></td>
                    <td width="140px" style="border:1px solid #000; border-bottom:1px solid #fff; border-top:1px solid #fff; text-align:center;">JENNIFFER G. BAHOD</td>
                    <td width="140px" style="border:1px solid #000; border-bottom:1px solid #fff; border-top:1px solid #fff; text-align:center;">IMELDA I. MACANES</td>
                    <td width="159px" style="border:1px solid #000; border-bottom:1px solid #fff; border-top:1px solid #fff; text-align:center;">MELCHOR D. DICLAS, M.D.</td>
                </tr>
                <tr style="font-size: 8px;">
                    <td width="80px" style="border:1px solid #000; border-bottom:1px solid #fff; border-top:1px solid #fff; font-size: 9px;"><b>Designation</b></td>
                    <td width="140px" style="border:1px solid #000; border-bottom:1px solid #fff; border-top:1px solid #fff; text-align:center;">Provincial General Services Officer</td>
                    <td width="140px" style="border:1px solid #000; border-bottom:1px solid #fff; border-top:1px solid #fff; text-align:center;">Provincial Treasurer</td>
                    <td width="159px" style="border:1px solid #000; border-bottom:1px solid #fff; border-top:1px solid #fff; text-align:center;">Provincial Governor</td>
                </tr>
                <tr style="font-size: 9px; font-weight:bold;">
                    <td width="80px" style="border:1px solid #000; border-top:1px solid #fff; font-size: 9px;"><b>Date</b></td>
                    <td width="140px" style="border:1px solid #000; border-top:1px solid #fff; text-align:center;"></td>
                    <td width="140px" style="border:1px solid #000; border-top:1px solid #fff; text-align:center;"></td>
                    <td width="159px" style="border:1px solid #000; border-top:1px solid #fff; text-align:center;"></td>
                </tr>

                ';
        
        return $text;
    }

    public function generate_psDbm(PrTransaction $pr)
    {
        ini_set('memory_limit', '512M');
        set_time_limit(300);

        $templatePath = public_path('assets/word_temps/arp_template.docx');

        if (!file_exists($templatePath)) {
            abort(500, 'Template file not found at: ' . $templatePath);
        }

        function sanitizeForXml($value) {
            if (is_numeric($value)) return $value;
            return htmlspecialchars($value ?? '', ENT_XML1 | ENT_QUOTES, 'UTF-8');
        }

        $templateProcessor = new TemplateProcessor($templatePath);

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

        $count = 0;
        $itemsData = [];
        $totalAmount = 0;

        foreach ($filteredParticular as $particular) {
            $count++;
            $itemsData[] = [
                'count' => $count,
                'item_description' => sanitizeForXml($particular['prodName']) ?? '',
                'qty' => number_format($particular['qty'], 0, '.', ','),
                'unit' => sanitizeForXml($particular['prodUnit']) ?? '',
                'price' => number_format($particular['prodPrice'], 2, '.', ','),
                'amount' => number_format($particular['totalCost'], 2, '.', ','),
            ];

            $totalAmount += (float)$particular['totalCost'];
        }

        $templateProcessor->cloneRow('count', count($itemsData));
        $templateProcessor->setValue('total_amount', number_format($totalAmount, 2, '.', ','));

        foreach ($itemsData as $index => $item) {
            $rowNumber = $index + 1;
            foreach ($item as $key => $value) {
                $templateProcessor->setValue("$key#$rowNumber", $value);
            }
        }
      
        $filename = 'ARP_' . now()->format('Ymd_His') . '.docx';
        $savePath = storage_path('app/public/' . $filename);
        $templateProcessor->saveAs($savePath);

        return response()->download($savePath)->deleteFileAfterSend(true);
    }

    public function generateExcel_purchaseRequestDraft(PrTransaction $pr)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set document properties
        $spreadsheet->getProperties()
            ->setCreator(SYSTEM_DEVELOPER)
            ->setTitle('Consolidated PPMP')
            ->setSubject('Consolidated PPMP Particulars');

        // Add logos (if you want to add images)
        $drawing1 = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
        $drawing1->setName('Benguet Logo');
        $drawing1->setPath(public_path('assets/images/benguet_logo.png'));
        $drawing1->setCoordinates('B1');
        $drawing1->setHeight(50);
        $drawing1->setWorksheet($sheet);

        $drawing2 = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
        $drawing2->setName('Bagong Pilipinas Logo');
        $drawing2->setPath(public_path('assets/images/Bagong_Pilipinas_logo.png'));
        $drawing2->setCoordinates('H1');
        $drawing2->setHeight(50);
        $drawing2->setWorksheet($sheet);

        // Title
        $sheet->mergeCells('B3:I3');
        $sheet->setCellValue('B3', 'PROVINCE OF BENGUET');
        $sheet->getStyle('B3')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('B3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('B4:I4');
        $sheet->setCellValue('B4', 'PURCHASE REQUEST');
        $sheet->getStyle('B4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Header info table: Department, PR No, Date
        $sheet->setCellValue('B6', 'Department:');
        $sheet->setCellValue('C6', 'PGSO');
        $sheet->setCellValue('F6', 'PR No.:');
        // TODO: Set actual PR No here
        $sheet->setCellValue('G6', $pr->pr_no ?? ''); 

        $sheet->setCellValue('B7', 'Division:');
        $sheet->setCellValue('C7', 'PGSO-WH');
        $sheet->setCellValue('F7', 'OBR No.:');
        // TODO: Set actual OBR No here
        $sheet->setCellValue('G7', $pr->obr_no ?? '');

        $sheet->setCellValue('I6', 'Date:');
        $sheet->setCellValue('J6', $pr->date->format('Y-m-d') ?? '');

        $sheet->setCellValue('I7', 'Date:');
        $sheet->setCellValue('J7', $pr->date->format('Y-m-d') ?? '');

        // Table headers
        $headers = [
            'Item No', 'New Stock No', 'Unit of Issue', 'Item Description', 'Qty', 'Unit Price', 'Total Cost'
        ];
        $colLetters = range('B', 'H');
        $headerRow = 9;
        foreach ($headers as $key => $header) {
            $sheet->setCellValue($colLetters[$key] . $headerRow, $header);
            $sheet->getStyle($colLetters[$key] . $headerRow)->getFont()->setBold(true);
            $sheet->getStyle($colLetters[$key] . $headerRow)->getFill()
                ->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFEEEEEE');
            $sheet->getStyle($colLetters[$key] . $headerRow)->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        // Fetch particulars and group by categories
        $particulars = $pr->prParticulars()->get();
        $filteredParticulars = $particulars->map(function ($particular) {
            return [
                'prodCode' => $this->productService->getProductCode($particular->prod_id),
                'prodName' => $particular->revised_specs,
                'prodUnit' => $particular->unitMeasure,
                'prodPrice' => $particular->unitPrice,
                'qty' => $particular->qty,
                'totalCost' => $particular->qty * (float)$particular->unitPrice,
                'prodId' => $particular->prod_id,
            ];
        })->sortBy('prodCode');

        $categories = $this->productService->getAllProduct_Category();

        $row = $headerRow + 1;
        $index = 0;
        $grandTotal = 0;

        foreach ($categories as $category) {
            // Check if this category has any matching products
            $categoryItems = [];
            foreach ($category->items as $item) {
                foreach ($item->products as $product) {
                    $matches = $filteredParticulars->filter(function($p) use ($product) {
                        return $p['prodCode'] === $product->prod_newNo;
                    });
                    if ($matches->count() > 0) {
                        $categoryItems = $categoryItems->merge($matches);
                    }
                }
            }
            if ($categoryItems->isEmpty()) {
                continue; // skip empty categories
            }

            // Category header row
            $sheet->mergeCells("B{$row}:H{$row}");
            $sheet->setCellValue("B{$row}", sprintf('%02d - %s', (int)$category->cat_code, $category->cat_name));
            $sheet->getStyle("B{$row}")->getFont()->setBold(true);
            $row++;

            $categoryTotal = 0;
            foreach ($categoryItems as $item) {
                $index++;
                $sheet->setCellValue("B{$row}", $index);
                $sheet->setCellValue("C{$row}", $item['prodCode']);
                $sheet->setCellValue("D{$row}", $item['prodUnit']);
                $sheet->setCellValue("E{$row}", $item['prodName']);
                $sheet->setCellValue("F{$row}", $item['qty']);
                $sheet->setCellValue("G{$row}", $item['prodPrice']);
                $sheet->setCellValue("H{$row}", $item['totalCost']);

                // Format numeric columns
                $sheet->getStyle("F{$row}:H{$row}")
                    ->getNumberFormat()
                    ->setFormatCode('#,##0.00');

                $categoryTotal += $item['totalCost'];
                $row++;
            }

            // Subtotal for category
            $sheet->mergeCells("B{$row}:G{$row}");
            $sheet->setCellValue("B{$row}", "Sub Total");
            $sheet->setCellValue("H{$row}", $categoryTotal);
            $sheet->getStyle("B{$row}:H{$row}")->getFont()->setBold(true);
            $sheet->getStyle("H{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
            $row++;

            $grandTotal += $categoryTotal;
        }

        // Grand Total row
        $sheet->mergeCells("B{$row}:G{$row}");
        $sheet->setCellValue("B{$row}", "GRAND TOTAL");
        $sheet->setCellValue("H{$row}", $grandTotal);
        $sheet->getStyle("B{$row}:H{$row}")->getFont()->setBold(true);
        $sheet->getStyle("H{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
        $row += 2;

        // Purpose row
        $sheet->mergeCells("B{$row}:H{$row}");
        $sheet->setCellValue("B{$row}", "Purpose:");
        $sheet->getStyle("B{$row}")->getFont()->setBold(true);
        $row++;

        $sheet->mergeCells("B{$row}:H{$row}");
        $sheet->setCellValue("B{$row}", "Office supplies for common use.");
        $sheet->getStyle("B{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $row += 2;

        // Signature table
        $sheet->setCellValue("B{$row}", "");
        $sheet->setCellValue("C{$row}", "Requested by:");
        $sheet->setCellValue("D{$row}", "Cash Available:");
        $sheet->setCellValue("E{$row}", "Approved by:");
        $row++;

        // Empty signature rows
        $sheet->setCellValue("B{$row}", "");
        $sheet->setCellValue("C{$row}", "");
        $sheet->setCellValue("D{$row}", "");
        $sheet->setCellValue("E{$row}", "");
        $row++;

        // Signature labels
        $sheet->setCellValue("B{$row}", "Signature");
        $sheet->setCellValue("C{$row}", "");
        $sheet->setCellValue("D{$row}", "");
        $sheet->setCellValue("E{$row}", "");
        $row++;

        // Printed names
        $sheet->setCellValue("B{$row}", "Printed Name");
        $sheet->setCellValue("C{$row}", "JENNIFFER G. BAHOD");
        $sheet->setCellValue("D{$row}", "IMELDA I. MACANES");
        $sheet->setCellValue("E{$row}", "MELCHOR D. DICLAS, M.D.");
        $row++;

        // Designations
        $sheet->setCellValue("B{$row}", "Designation");
        $sheet->setCellValue("C{$row}", "Provincial General Services Officer");
        $sheet->setCellValue("D{$row}", "Provincial Treasurer");
        $sheet->setCellValue("E{$row}", "Provincial Governor");

        // Autosize columns for better view
        foreach (range('B', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Output to browser or save locally
        $writer = new Xlsx($spreadsheet);
        $filename = 'Purchase_Request_' . now()->format('Ymd_His') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $writer->save('php://output');
        exit;
    }

}
