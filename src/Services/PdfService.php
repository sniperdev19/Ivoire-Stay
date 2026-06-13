<?php

namespace Services;

use Models\Invoice;

class PdfService
{
    public static function generateInvoice(array $invoice): string
    {
        $pdfDir = STORAGE_PATH . '/pdf';
        if (!is_dir($pdfDir)) mkdir($pdfDir, 0755, true);

        $filename = $invoice['invoice_number'] . '.pdf';
        $path     = $pdfDir . '/' . $filename;

        // Check if FPDF is available
        if (!class_exists('FPDF')) {
            // Fallback: generate simple HTML-based PDF simulation
            self::generateHtmlPdf($invoice, $path);
            return 'storage/pdf/' . $filename;
        }

        $pdf = new \FPDF('P', 'mm', 'A4');
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 18);

        // Header
        $pdf->SetFillColor(108, 99, 255);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell(0, 15, 'FACTURE', 0, 1, 'C', true);

        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('Arial', '', 10);
        $pdf->Ln(5);

        // Invoice info
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(95, 7, 'Hotel-Residence Sync', 0, 0);
        $pdf->Cell(95, 7, 'Facture N° : ' . $invoice['invoice_number'], 0, 1, 'R');

        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(95, 6, $invoice['establishment_name'], 0, 0);
        $pdf->Cell(95, 6, 'Date : ' . date('d/m/Y', strtotime($invoice['issued_at'])), 0, 1, 'R');
        $pdf->Cell(95, 6, $invoice['establishment_address'] ?? '', 0, 0);
        $pdf->Cell(95, 6, 'Statut : ' . strtoupper($invoice['status']), 0, 1, 'R');

        $pdf->Ln(8);

        // Client
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(0, 7, 'Facturé à :', 0, 1);
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(0, 6, $invoice['client_name'], 0, 1);
        $pdf->Cell(0, 6, $invoice['client_email'] ?? '', 0, 1);
        $pdf->Cell(0, 6, $invoice['client_phone'] ?? '', 0, 1);

        $pdf->Ln(8);

        // Details table
        $pdf->SetFillColor(240, 240, 255);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(80, 8, 'Description', 1, 0, 'C', true);
        $pdf->Cell(30, 8, 'Entrée', 1, 0, 'C', true);
        $pdf->Cell(30, 8, 'Sortie', 1, 0, 'C', true);
        $pdf->Cell(25, 8, 'Nuits', 1, 0, 'C', true);
        $pdf->Cell(25, 8, 'Montant', 1, 1, 'C', true);

        $nights = (new \DateTime($invoice['check_out']))->diff(new \DateTime($invoice['check_in']))->days;

        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(80, 7, 'Chambre ' . $invoice['room_number'] . ' - ' . $invoice['room_type'], 1);
        $pdf->Cell(30, 7, date('d/m/Y', strtotime($invoice['check_in'])), 1, 0, 'C');
        $pdf->Cell(30, 7, date('d/m/Y', strtotime($invoice['check_out'])), 1, 0, 'C');
        $pdf->Cell(25, 7, $nights . ' nuit(s)', 1, 0, 'C');
        $pdf->Cell(25, 7, number_format($invoice['amount_ht'], 0, ',', ' ') . ' F', 1, 1, 'R');

        $pdf->Ln(4);

        // Totals
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(165, 7, 'Montant HT', 0, 0, 'R');
        $pdf->Cell(25, 7, number_format($invoice['amount_ht'], 0, ',', ' ') . ' FCFA', 0, 1, 'R');

        if ($invoice['tax_rate'] > 0) {
            $pdf->Cell(165, 7, 'TVA (' . $invoice['tax_rate'] . '%)', 0, 0, 'R');
            $tax = $invoice['amount_ttc'] - $invoice['amount_ht'];
            $pdf->Cell(25, 7, number_format($tax, 0, ',', ' ') . ' FCFA', 0, 1, 'R');
        }

        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(165, 8, 'TOTAL TTC', 0, 0, 'R');
        $pdf->Cell(25, 8, number_format($invoice['amount_ttc'], 0, ',', ' ') . ' FCFA', 0, 1, 'R');

        // Footer
        $pdf->Ln(15);
        $pdf->SetFont('Arial', 'I', 9);
        $pdf->SetTextColor(128, 128, 128);
        $pdf->Cell(0, 6, 'Hotel-Residence Sync - Viens On Se Pose - Côte d\'Ivoire', 0, 1, 'C');

        $pdf->Output('F', $path);
        return 'storage/pdf/' . $filename;
    }

    private static function generateHtmlPdf(array $invoice, string $path): void
    {
        // Simple text fallback when FPDF not available
        $content = "FACTURE\n";
        $content .= "N° : " . $invoice['invoice_number'] . "\n";
        $content .= "Client : " . $invoice['client_name'] . "\n";
        $content .= "Montant TTC : " . number_format($invoice['amount_ttc'], 0) . " FCFA\n";
        file_put_contents(str_replace('.pdf', '.txt', $path), $content);
    }
}
