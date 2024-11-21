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

        $footerText = date('F j, Y') . ' | CG:System Generated';

        $totalWidth = $this->getPageWidth();

        $leftWidth = $totalWidth / 3;
        $centerWidth = $totalWidth / 3;
        $rightWidth = $totalWidth / 3.5;

        $this->SetX(10);
        $this->Cell($leftWidth, 10, $waterMark, 0, 0, 'L');

        $this->SetX($leftWidth);
        $this->Cell($centerWidth, 10, $pageNumber, 0, 0, 'C');

        $this->SetX($leftWidth + $centerWidth);
        $this->Cell($rightWidth, 10, $footerText, 0, 0, 'R');
    }
}
