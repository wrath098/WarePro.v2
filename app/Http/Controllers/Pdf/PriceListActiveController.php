<?php

namespace App\Http\Controllers\Pdf;

use App\Http\Controllers\Controller;
use App\Services\MyPDF;
use App\Services\ProductService;
use Illuminate\Http\Request;

class PriceListActiveController extends Controller
{
    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function generatePdf_priceListActive()
    {
        $pdf = new MyPDF('L', 'mm', array(203.2, 330.2), true, 'UTF-8', false);

        $logoPath = public_path('assets/images/benguet_logo.png');
        $pilipinasPath = public_path('assets/images/Bagong_Pilipinas_logo.png');

        $pdf->SetCreator(SYSTEM_GENERATOR);
        $pdf->SetAuthor(SYSTEM_DEVELOPER);
        $pdf->SetTitle('Product List');
        $pdf->SetSubject('Master List');
        $pdf->SetKeywords('Benguet, WarePro');
        
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
                    <h4>PRODUCT PRICE LIST</h4>
                    <h5>OFFICE & JANITORIAL SUPPLIES</h5>
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
        $table .= $this->tableContent();
        $table .= '</tbody>';
        $table .= '</table>';
        $pdf->writeHTML($table, true, false, true, false, '');
        $pdf->Output('Product Price List.pdf', 'I');
    }

    protected function tableHeader()
    {
        return '<tr style="font-size: 10px; font-weight:bold; text-align:center; background-color: #EEEEEE;">
                    <th width="25px">No.</th>
                    <th width="40px">Old Stock No.</th>
                    <th width="63px">New Stock No.</th>
                    <th width="376px">Item Description</th>
                    <th width="60px">Unit of Measure</th>
                    <th width="63px">Price <br> (1)</th>
                    <th width="63px">Price <br> (2)</th>
                    <th width="63px">Price <br> (3)</th>
                    <th width="63px">Price <br> (4)</th>
                    <th width="63px">Price <br> (5)</th>
                </tr>';
    }

    protected function tableContent()
    {
        $text = '';
        $count = 0;
        $productList = $this->productService->getActiveProduct_FundModel();

        foreach ($productList as $fund) {
            if ($fund->categories->isNotEmpty()) {
                $text .= '<tr class="bg-gray-100" style="font-size: 10px; font-weight: bold;">
                            <td width="100%">' . strtoupper($fund->fund_name) . '</td>
                          </tr>';
        
                foreach ($fund->categories as $category) {
                    if ($category->items->isNotEmpty()) {
                        $text .= '<tr class="bg-gray-100" style="font-size: 10px; font-weight: bold;">
                                    <td width="100%">' . sprintf('%02d', (int) $category->cat_code) . ' - ' . $category->cat_name . '</td>
                                  </tr>';
        
                        foreach ($category->items as $item) {   
                            if ($item->products->isNotEmpty()) {  
                                foreach ($item->products as $product) {
                                    $count++;
                                    $price = $product->prices()->orderBy('created_at', 'desc')->limit(5)->pluck('prod_price')->toArray();
                                    $price = array_pad($price, 5, 0.0);
                                    $text .= '<tr style="font-size: 9px;">
                                                <th width="25px">' . $count . '</th>
                                                <th width="40px">' . $product->prod_oldNo . '</th>
                                                <th width="63px">' . $product->prod_newNo . '</th>
                                                <th width="376px">' . $product->prod_desc . '</th>
                                                <th width="60px">' . $product->prod_unit . '</th>
                                                <th width="63px" style="text-align: right;">' . ($price[0] != 0 ? number_format($price[0], 2, '.', ',') : '-') . '</th>
                                                <th width="63px" style="text-align: right;">' . ($price[1] != 0 ? number_format($price[1], 2, '.', ',') : '-') . '</th>
                                                <th width="63px" style="text-align: right;">' . ($price[2] != 0 ? number_format($price[2], 2, '.', ',') : '-') . '</th>
                                                <th width="63px" style="text-align: right;">' . ($price[3] != 0 ? number_format($price[3], 2, '.', ',') : '-') . '</th>
                                                <th width="63px" style="text-align: right;">' . ($price[4] != 0 ? number_format($price[4], 2, '.', ',') : '-') . '</th>
                                              </tr>';
                                }  
                            }         
                        }
                    }
                }
            }
        }

        return $text;
    }
}
