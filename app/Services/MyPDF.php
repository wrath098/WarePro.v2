<?php

namespace App\Services;
use TCPDF;

class MyPDF extends TCPDF
{
    public function Footer()
    {
        $this->SetY(-10);

        $this->SetFont('helvetica', 'I', 8);

        $waterMark = 'PGSO-WarePro';

        $pageNumber = 'Page ' . $this->getAliasNumPage() . ' of ' . $this->getAliasNbPages();

        $footerText = date('F j, Y') . ' | PGSO-WarePro';

        $this->Cell(0, 10, $pageNumber, 0, 0, 'C');
        $this->Cell(0, 10, $footerText, 0, 0, 'R');
    }
}
