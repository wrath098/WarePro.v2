<?php

namespace App\Http\Controllers\Pdf;

use App\Http\Controllers\Controller;
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
        $templatePath = public_path('assets/word_temps/arp_template.docx');

        if (!file_exists($templatePath)) {
            abort(500, 'Template file not found at: ' . $templatePath);
        }

        $templateProcessor = new TemplateProcessor($templatePath);

        // Replace placeholders
        $templateProcessor->setValue('firstname', 'John');
        $templateProcessor->setValue('lastname', 'Doe');
        $templateProcessor->setValue('checkbox', '☑'); // Unicode checkbox

        // Save to storage
        $filename = 'ARP_' . now()->format('Ymd_His') . '.docx';
        $savePath = storage_path('app/public/' . $filename);
        $templateProcessor->saveAs($savePath);

        // Return as downloadable response
        return response()->download($savePath)->deleteFileAfterSend(true);
    }
}
