<?php

namespace Services;

class PdfService
{
    public static function generateInvoice(array $inv): string
    {
        $dir = STORAGE_PATH . '/pdf';
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $filename = ($inv['invoice_number'] ?? 'FACTURE') . '.pdf';
        $path     = $dir . '/' . $filename;

        $pdf = new \FPDF('P', 'mm', 'A4');
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(true, 20);
        $pdf->AddPage();

        // ── Helpers ──────────────────────────────────────────────────────────
        $u = fn(string $s): string => mb_convert_encoding($s, 'ISO-8859-1', 'UTF-8');
        $fmt = fn(float $v): string => number_format($v, 0, ',', ' ') . ' FCFA';

        $green  = [27,  67,  50];
        $gold   = [201, 168, 76];
        $light  = [249, 247, 242];
        $gray   = [107, 114, 128];
        $dark   = [17,  24,  39];
        $white  = [255, 255, 255];

        $W = 180; // usable width

        // ── BANDEAU ENTÊTE ────────────────────────────────────────────────────
        $pdf->SetFillColor(...$green);
        $pdf->Rect(0, 0, 210, 36, 'F');

        $pdf->SetXY(15, 10);
        $pdf->SetTextColor(...$gold);
        $pdf->SetFont('Arial', 'B', 18);
        $pdf->Cell(90, 8, $u(MAIL_FROM_NAME), 0, 0);

        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(200, 200, 200);
        $pdf->SetXY(105, 10);
        $pdf->Cell(90, 5, $u('Hôtellerie · Côte d\'Ivoire'), 0, 1, 'R');

        $pdf->SetFont('Arial', '', 9);
        $pdf->SetXY(15, 20);
        $pdf->SetTextColor(...$white);
        $estab = $inv['establishment_name'] ?? '';
        $pdf->Cell(90, 5, $u($estab), 0, 0);
        if (!empty($inv['establishment_phone'])) {
            $pdf->SetXY(105, 20);
            $pdf->Cell(90, 5, $u($inv['establishment_phone']), 0, 0, 'R');
        }
        if (!empty($inv['establishment_address'])) {
            $pdf->SetXY(15, 26);
            $pdf->Cell(90, 5, $u($inv['establishment_address']), 0, 0);
        }

        $pdf->SetY(44);

        // ── LIGNE TITRE FACTURE ───────────────────────────────────────────────
        $pdf->SetFillColor(...$light);
        $pdf->Rect(15, 40, $W, 18, 'F');

        $pdf->SetXY(18, 43);
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->SetTextColor(...$dark);
        $pdf->Cell(90, 7, 'FACTURE', 0, 0);

        $pdf->SetFont('Arial', 'B', 13);
        $pdf->SetTextColor(...$gold);
        $pdf->SetXY(105, 43);
        $pdf->Cell(88, 7, $u($inv['invoice_number'] ?? ''), 0, 0, 'R');

        $statusLabels = ['draft' => 'Brouillon', 'sent' => 'Envoyée', 'paid' => 'Payée'];
        $statusColors = ['draft' => [107,114,128], 'sent' => [217,119,6], 'paid' => [22,163,74]];
        $status = $inv['status'] ?? 'draft';
        $statusLabel = $statusLabels[$status] ?? strtoupper($status);

        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetTextColor(...($statusColors[$status] ?? $gray));
        $pdf->SetXY(18, 51);
        $pdf->Cell(90, 5, $u('Statut : ' . strtoupper($statusLabel)), 0, 0);

        $issuedAt = !empty($inv['issued_at']) ? date('d/m/Y', strtotime($inv['issued_at'])) : date('d/m/Y');
        $pdf->SetTextColor(...$gray);
        $pdf->SetXY(105, 51);
        $pdf->Cell(88, 5, $u('Émise le : ' . $issuedAt), 0, 0, 'R');

        $pdf->SetY(66);

        // ── CLIENT + SÉJOUR (2 colonnes) ──────────────────────────────────────
        // Colonne gauche : client
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetTextColor(...$gray);
        $pdf->SetX(15);
        $pdf->Cell(5, 5, '', 0, 0);
        $pdf->SetFillColor(...$gold);
        $pdf->Rect(15, 66, 3, 14, 'F');

        $pdf->SetXY(21, 66);
        $pdf->SetTextColor(...$gray);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(80, 4, $u('FACTURÉ À'), 0, 1);

        $pdf->SetXY(21, 71);
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->SetTextColor(...$dark);
        $pdf->Cell(80, 6, $u($inv['client_name'] ?? '—'), 0, 1);

        $pdf->SetXY(21, 78);
        $pdf->SetFont('Arial', '', 9);
        $pdf->SetTextColor(...$gray);
        if (!empty($inv['client_email'])) {
            $pdf->Cell(80, 4, $u($inv['client_email']), 0, 1);
            $pdf->SetX(21);
        }
        if (!empty($inv['client_phone'])) {
            $pdf->Cell(80, 4, $u($inv['client_phone']), 0, 1);
        }

        // Colonne droite : séjour
        $pdf->SetFillColor(...$gold);
        $pdf->Rect(120, 66, 3, 14, 'F');
        $pdf->SetXY(126, 66);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetTextColor(...$gray);
        $pdf->Cell(68, 4, $u('DÉTAILS DU SÉJOUR'), 0, 1);

        $isPassage = isset($inv['booking_type']) && $inv['booking_type'] === 'passage';
        $ci = !empty($inv['check_in'])  ? date('d/m/Y', strtotime($inv['check_in']))  : '—';
        $co = !empty($inv['check_out']) ? date('d/m/Y', strtotime($inv['check_out'])) : '—';

        $details = [];
        $details[] = ['Chambre', 'N° ' . ($inv['room_number'] ?? '—') . ' · ' . ($inv['room_type'] ?? '')];
        if ($isPassage) {
            $details[] = ['Date', $ci];
            $details[] = ['Durée', ($inv['hours'] ?? '?') . ' heure(s)'];
        } else {
            $details[] = ['Arrivée', $ci];
            $details[] = ['Départ',  $co];
            if (!empty($inv['check_in']) && !empty($inv['check_out'])) {
                $nights = max(0, (new \DateTime($inv['check_out']))->diff(new \DateTime($inv['check_in']))->days);
                $details[] = ['Durée', $nights . ' nuit' . ($nights > 1 ? 's' : '')];
            }
        }

        $yy = 71;
        foreach ($details as [$label, $val]) {
            $pdf->SetXY(126, $yy);
            $pdf->SetFont('Arial', '', 8);
            $pdf->SetTextColor(...$gray);
            $pdf->Cell(28, 4, $u($label . ' :'), 0, 0);
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->SetTextColor(...$dark);
            $pdf->Cell(40, 4, $u($val), 0, 1);
            $yy += 5;
        }

        $pdf->SetY(max($pdf->GetY(), 94));

        // ── TABLEAU MONTANTS ─────────────────────────────────────────────────
        $pdf->SetY($pdf->GetY() + 6);

        // En-tête du tableau
        $pdf->SetFillColor(...$green);
        $pdf->SetTextColor(...$white);
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetX(15);
        $pdf->Cell(80, 8, $u('Description'), 1, 0, 'L', true);
        $pdf->Cell(50, 8, $u('Type de séjour'), 1, 0, 'C', true);
        $pdf->Cell(50, 8, $u('Montant HT'), 1, 1, 'R', true);

        // Ligne séjour
        $typeSejour = match($inv['booking_type'] ?? 'nuit') {
            'passage' => 'Passage horaire',
            'weekend' => 'Week-end',
            default   => 'Nuit / séjour',
        };
        $desc = 'Chambre ' . ($inv['room_number'] ?? '') . ($inv['room_type'] ? ' — ' . $inv['room_type'] : '');

        $pdf->SetFillColor(250, 250, 250);
        $pdf->SetTextColor(...$dark);
        $pdf->SetFont('Arial', '', 9);
        $pdf->SetX(15);
        $pdf->Cell(80, 7, $u($desc), 1, 0, 'L', false);
        $pdf->Cell(50, 7, $u($typeSejour), 1, 0, 'C', false);
        $pdf->Cell(50, 7, $u($fmt((float)($inv['amount_ht'] ?? $inv['amount_ttc'] ?? 0))), 1, 1, 'R', false);

        $pdf->Ln(4);

        // ── TOTAUX ───────────────────────────────────────────────────────────
        $amountHt  = (float)($inv['amount_ht']  ?? $inv['amount_ttc'] ?? 0);
        $amountTtc = (float)($inv['amount_ttc'] ?? $amountHt);
        $taxRate   = (float)($inv['tax_rate']   ?? 0);
        $taxAmount = $amountTtc - $amountHt;

        $totals = [['Montant HT', $fmt($amountHt)]];
        if ($taxRate > 0) {
            $totals[] = ['TVA (' . $taxRate . '%)', $fmt($taxAmount)];
        }
        $totals[] = ['__TOTAL', $fmt($amountTtc)];

        foreach ($totals as [$label, $val]) {
            $isTotal = $label === '__TOTAL';
            $pdf->SetX(15);
            if ($isTotal) {
                $pdf->SetFont('Arial', 'B', 10);
                $pdf->SetFillColor(...$green);
                $pdf->SetTextColor(...$white);
                $pdf->Cell(130, 9, $u('TOTAL TTC'), 1, 0, 'R', true);
                $pdf->Cell(50, 9, $u($val), 1, 1, 'R', true);
            } else {
                $pdf->SetFont('Arial', '', 9);
                $pdf->SetFillColor(249, 247, 242);
                $pdf->SetTextColor(...$gray);
                $pdf->Cell(130, 7, $u($label), 1, 0, 'R', true);
                $pdf->SetTextColor(...$dark);
                $pdf->Cell(50, 7, $u($val), 1, 1, 'R', true);
            }
        }

        // ── PAIEMENTS REÇUS ──────────────────────────────────────────────────
        $paidAmount  = (float)($inv['paid_amount'] ?? 0);
        $remaining   = max(0, $amountTtc - $paidAmount);

        if ($paidAmount > 0) {
            $pdf->Ln(3);
            $pdf->SetX(15);
            $pdf->SetFont('Arial', 'B', 9);
            $pdf->SetFillColor(240, 253, 244);
            $pdf->SetTextColor(22, 163, 74);
            $pdf->Cell(130, 7, $u('Déjà encaissé'), 1, 0, 'R', true);
            $pdf->Cell(50, 7, $u('-' . $fmt($paidAmount)), 1, 1, 'R', true);

            $pdf->SetX(15);
            if ($remaining <= 0) {
                $pdf->SetFillColor(240, 253, 244);
                $pdf->SetTextColor(22, 163, 74);
                $pdf->Cell(130, 8, $u('SOLDE RESTANT'), 1, 0, 'R', true);
                $pdf->Cell(50, 8, $u('0 FCFA — Soldée'), 1, 1, 'R', true);
            } else {
                $pdf->SetFillColor(255, 251, 235);
                $pdf->SetTextColor(217, 119, 6);
                $pdf->Cell(130, 8, $u('SOLDE RESTANT À PAYER'), 1, 0, 'R', true);
                $pdf->Cell(50, 8, $u($fmt($remaining)), 1, 1, 'R', true);
            }
        }

        // ── NOTE DE BAS ──────────────────────────────────────────────────────
        $pdf->Ln(12);
        $pdf->SetFont('Arial', 'I', 8);
        $pdf->SetTextColor(...$gray);
        $pdf->SetX(15);
        $pdf->MultiCell($W, 4, $u('Merci de votre confiance. Pour toute question concernant cette facture, veuillez contacter ' . ($inv['establishment_name'] ?? MAIL_FROM_NAME) . '.'), 0, 'C');

        $pdf->Ln(4);
        $pdf->SetFillColor(...$green);
        $pdf->Rect(15, $pdf->GetY(), $W, 0.5, 'F');
        $pdf->Ln(3);
        $pdf->SetFont('Arial', '', 7);
        $pdf->SetTextColor(...$gray);
        $pdf->Cell($W, 4, $u(MAIL_FROM_NAME . ' · ' . APP_URL), 0, 1, 'C');

        $pdf->Output('F', $path);
        return 'storage/pdf/' . $filename;
    }
}
