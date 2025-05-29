<?php

namespace App\Http\Controllers\Pdf;

use App\Http\Controllers\Controller;
use App\Models\ProductInventory;
use App\Services\ProductService;
use Illuminate\Http\Request;
use PhpOffice\PhpWord\TemplateProcessor;

class PsDbmController extends Controller
{
    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function generate_psDbm()
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
        $productList = $this->productService->getAllProduct_Category();

        $count = 0;
        $itemsData = [];

        foreach ($productList as $category) {
            if ($category->items->isNotEmpty()) {
                foreach ($category->items as $item) {
                    if ($item->products->isNotEmpty()) {  
                        foreach ($item->products as $product) {
                            $inventory = ProductInventory::where('prod_id', $product->id)->first();
                            
                            if ($product->prod_status !== 'active' && !$inventory) {
                                continue;
                            }

                            $count++;
                            $itemsData[] = [
                                'count' => $count,
                                'item_description' => sanitizeForXml($product->prod_desc) ?? '',
                                'qty' => optional($inventory)->qty_physical_count ?? 0,
                                'unit' => sanitizeForXml($product->prod_unit) ?? '',
                                'price' => sanitizeForXml($product->prod_oldNo) ?? '',
                                'amount' => sanitizeForXml($product->prod_newNo) ?? '',
                            ];
                        }  
                    }         
                }
            }
        }

        $templateProcessor->cloneRow('count', count($itemsData));

        foreach ($itemsData as $index => $item) {
            $rowNumber = $index + 1;
            foreach ($item as $key => $value) {
                $templateProcessor->setValue("$key#$rowNumber", $value);
            }
        }
      
        // Save to storage
        $filename = 'ARP_' . now()->format('Ymd_His') . '.docx';
        $savePath = storage_path('app/public/' . $filename);
        $templateProcessor->saveAs($savePath);

        // Return as downloadable response
        return response()->download($savePath)->deleteFileAfterSend(true);
    }
}
