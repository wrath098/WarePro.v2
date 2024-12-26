<?php

namespace App\Http\Controllers\Pdf;

use App\Http\Controllers\Controller;
use App\Services\MyPDF;
use App\Services\ProductService;
use Illuminate\Http\Request;

class ProductListActiveController extends Controller
{
    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function generatePdf_productListActive()
    {
        $pdf = new MyPDF('P', 'mm', array(203.2, 330.2), true, 'UTF-8', false);

        $logoPath = public_path('assets/images/benguet_logo.png');
        $pilipinasPath = public_path('assets/images/Bagong_Pilipinas_logo.png');

        $pdf->SetCreator(SYSTEM_GENERATOR);
        $pdf->SetAuthor(SYSTEM_DEVELOPER);
        $pdf->SetTitle('Product Master List');
        $pdf->SetSubject('Product List');
        $pdf->SetKeywords('Benguet, WarePro, Product, List');
        
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(true);

        $pdf->SetFooterMargin(10);
        $pdf->SetAutoPageBreak(TRUE, 10);

        $pdf->AddPage();

        $pdf->Image($logoPath, 47, 15, 15, '', '', '', '', false, 300, '', false, false, 0, false, false, false);
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
                    <h4>PRODUCT LIST</h4>
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
        $pdf->Output('Product List.pdf', 'I');
    }

    protected function tableHeader()
    {
        return '<tr style="font-size: 10px; font-weight:bold; text-align:center; background-color: #EEEEEE;">
                    <th width="25px">No.</th>
                    <th width="40px">Old Stock No.</th>
                    <th width="63px">New Stock No.</th>
                    <th width="268px">Item Description</th>
                    <th width="60px">Unit of Measure</th>
                    <th width="63px">Price</th>
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
                                    $price = $this->productService->getLatestPrice($product->id);
                                    $formattedPrice = number_format($price, 2, '.', ',');
                                    $text .= '<tr style="font-size: 9px;">
                                                <th width="25px">' . $count . '</th>
                                                <th width="40px" style="text-align: center;">' . $product->prod_oldNo . '</th>
                                                <th width="63px">' . $product->prod_newNo . '</th>
                                                <th width="268px">' . $product->prod_desc . '</th>
                                                <th width="60px">' . $product->prod_unit . '</th>
                                                <th width="63px" style="text-align: right;">' . $formattedPrice . '</th>
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
