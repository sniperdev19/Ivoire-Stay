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

    /**
     * Confirmation de réservation téléchargeable par le voyageur lui-même
     * (accès public via guest_token, cf. PublicController::bookingPdf) — pour
     * celui qui ne consulte pas l'email de confirmation. Même charte graphique
     * que generateInvoice(), régénéré à chaque téléchargement (le statut peut
     * changer entre deux téléchargements — pending/confirmed/annulée), jamais
     * mis en cache sur disque comme les factures.
     */
    public static function generateBookingConfirmation(array $b): string
    {
        $dir = STORAGE_PATH . '/pdf';
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $filename = 'reservation-' . ($b['id'] ?? '0') . '.pdf';
        $path     = $dir . '/' . $filename;

        $pdf = new \FPDF('P', 'mm', 'A4');
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(true, 20);
        $pdf->AddPage();

        $u   = fn(string $s): string => mb_convert_encoding($s, 'ISO-8859-1', 'UTF-8');
        $fmt = fn(float $v): string => number_format($v, 0, ',', ' ') . ' FCFA';

        $green = [27, 67, 50];
        $gold  = [201, 168, 76];
        $light = [249, 247, 242];
        $gray  = [107, 114, 128];
        $dark  = [17, 24, 39];
        $white = [255, 255, 255];
        $W     = 180;

        // ── BANDEAU ENTÊTE ───────────────────────────────────────────────────
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
        $estab = $b['establishment_name'] ?? '';
        $pdf->Cell(90, 5, $u($estab), 0, 0);
        if (!empty($b['establishment_phone'])) {
            $pdf->SetXY(105, 20);
            $pdf->Cell(90, 5, $u($b['establishment_phone']), 0, 0, 'R');
        }
        if (!empty($b['establishment_address'])) {
            $pdf->SetXY(15, 26);
            $pdf->Cell(90, 5, $u($b['establishment_address']), 0, 0);
        } elseif (!empty($b['establishment_city'])) {
            $pdf->SetXY(15, 26);
            $pdf->Cell(90, 5, $u($b['establishment_city']), 0, 0);
        }

        $pdf->SetY(44);

        // ── LIGNE TITRE ───────────────────────────────────────────────────────
        $pdf->SetFillColor(...$light);
        $pdf->Rect(15, 40, $W, 18, 'F');

        $pdf->SetXY(18, 43);
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->SetTextColor(...$dark);
        $pdf->Cell(90, 7, $u('CONFIRMATION DE RÉSERVATION'), 0, 0);

        $pdf->SetFont('Arial', 'B', 13);
        $pdf->SetTextColor(...$gold);
        $pdf->SetXY(105, 43);
        $pdf->Cell(88, 7, $u('N° ' . ($b['id'] ?? '')), 0, 0, 'R');

        $statusLabels = [
            'pending'      => 'En attente de confirmation',
            'confirmed'    => 'Confirmée',
            'checked_in'   => 'Client arrivé',
            'checked_out'  => 'Séjour terminé',
            'cancelled'    => 'Annulée',
        ];
        $statusColors = [
            'pending'     => [217, 119, 6],
            'confirmed'   => [22, 163, 74],
            'checked_in'  => [22, 163, 74],
            'checked_out' => [107, 114, 128],
            'cancelled'   => [220, 38, 38],
        ];
        $status = $b['status'] ?? 'pending';

        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetTextColor(...($statusColors[$status] ?? $gray));
        $pdf->SetXY(18, 51);
        $pdf->Cell(90, 5, $u('Statut : ' . strtoupper($statusLabels[$status] ?? $status)), 0, 0);

        $issuedAt = date('d/m/Y H:i');
        $pdf->SetTextColor(...$gray);
        $pdf->SetXY(105, 51);
        $pdf->Cell(88, 5, $u('Généré le : ' . $issuedAt), 0, 0, 'R');

        $pdf->SetY(66);

        // ── VOYAGEUR + SÉJOUR (2 colonnes) ──────────────────────────────────────
        $pdf->SetFillColor(...$gold);
        $pdf->Rect(15, 66, 3, 14, 'F');

        $pdf->SetXY(21, 66);
        $pdf->SetTextColor(...$gray);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(80, 4, $u('VOYAGEUR'), 0, 1);

        $pdf->SetXY(21, 71);
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->SetTextColor(...$dark);
        $pdf->Cell(80, 6, $u($b['client_name'] ?? '—'), 0, 1);

        $pdf->SetXY(21, 78);
        $pdf->SetFont('Arial', '', 9);
        $pdf->SetTextColor(...$gray);
        if (!empty($b['client_email'])) {
            $pdf->Cell(80, 4, $u($b['client_email']), 0, 1);
            $pdf->SetX(21);
        }
        if (!empty($b['client_phone'])) {
            $pdf->Cell(80, 4, $u($b['client_phone']), 0, 1);
        }

        $pdf->SetFillColor(...$gold);
        $pdf->Rect(120, 66, 3, 14, 'F');
        $pdf->SetXY(126, 66);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetTextColor(...$gray);
        $pdf->Cell(68, 4, $u('DÉTAILS DU SÉJOUR'), 0, 1);

        $isPassage = ($b['booking_type'] ?? 'nuit') === 'passage';
        $ci = !empty($b['check_in'])  ? date('d/m/Y', strtotime($b['check_in']))  : '—';
        $co = !empty($b['check_out']) ? date('d/m/Y', strtotime($b['check_out'])) : '—';

        $details = [];
        $details[] = ['Chambre', 'N° ' . ($b['room_number'] ?? '—') . ' · ' . ($b['room_type'] ?? '')];
        if ($isPassage) {
            $details[] = ['Date', $ci];
            $details[] = ['Durée', ($b['hours'] ?? '?') . ' heure(s)'];
        } else {
            $details[] = ['Arrivée', $ci];
            $details[] = ['Départ',  $co];
            $details[] = ['Durée', ($b['nights'] ?? 1) . ' nuit' . (($b['nights'] ?? 1) > 1 ? 's' : '')];
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

        $pdf->SetY(max($pdf->GetY(), 94) + 6);

        // ── MONTANT ───────────────────────────────────────────────────────────
        $amount      = (float) ($b['total_amount'] ?? 0);
        $paidAmount  = (float) ($b['paid_amount'] ?? 0);

        $pdf->SetFillColor(...$green);
        $pdf->SetTextColor(...$white);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetX(15);
        $pdf->Cell(130, 9, $u('MONTANT TOTAL DU SÉJOUR'), 1, 0, 'R', true);
        $pdf->Cell(50, 9, $u($fmt($amount)), 1, 1, 'R', true);

        if ($paidAmount > 0) {
            $pdf->SetX(15);
            $pdf->SetFont('Arial', 'B', 9);
            $pdf->SetFillColor(240, 253, 244);
            $pdf->SetTextColor(22, 163, 74);
            $pdf->Cell(130, 7, $u('Déjà réglé'), 1, 0, 'R', true);
            $pdf->Cell(50, 7, $u($fmt($paidAmount)), 1, 1, 'R', true);
        } else {
            $pdf->SetX(15);
            $pdf->SetFont('Arial', '', 9);
            $pdf->SetFillColor(255, 251, 235);
            $pdf->SetTextColor(217, 119, 6);
            $pdf->Cell(130, 7, $u('À régler à l\'arrivée'), 1, 0, 'R', true);
            $pdf->Cell(50, 7, $u($fmt(max(0, $amount - $paidAmount))), 1, 1, 'R', true);
        }

        // ── NOTE DE BAS ──────────────────────────────────────────────────────
        $pdf->Ln(12);
        $pdf->SetFont('Arial', 'I', 8);
        $pdf->SetTextColor(...$gray);
        $pdf->SetX(15);
        $pdf->MultiCell($W, 4, $u('Merci de votre confiance. Ce document atteste de votre réservation auprès de ' . ($b['establishment_name'] ?? MAIL_FROM_NAME) . '. Pour toute question, contactez directement l\'établissement.'), 0, 'C');

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

    /**
     * Rapport financier — simple/combiné (mode 'single'/'all') ou comparatif
     * entre établissements (mode 'compare'). Même charte graphique que
     * generateInvoice() (bandeau vert/or), remplace l'ancien "Exporter PDF"
     * qui n'était en réalité qu'un window.print() côté client.
     */
    public static function generateReport(array $data): string
    {
        $dir = STORAGE_PATH . '/pdf';
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $mode     = $data['mode'] ?? 'single';
        $slug     = $mode === 'compare' ? 'comparatif' : 'rapport';
        $filename = $slug . '-' . ($data['from'] ?? date('Y-m-d')) . '-' . bin2hex(random_bytes(4)) . '.pdf';
        $path     = $dir . '/' . $filename;

        $pdf = new \FPDF('P', 'mm', 'A4');
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(true, 20);
        $pdf->AddPage();

        $u   = fn(string $s): string => mb_convert_encoding($s, 'ISO-8859-1', 'UTF-8');
        $fmt = fn(float $v): string => number_format($v, 0, ',', ' ') . ' FCFA';

        $green = [27, 67, 50];
        $gold  = [201, 168, 76];
        $light = [249, 247, 242];
        $gray  = [107, 114, 128];
        $dark  = [17, 24, 39];
        $white = [255, 255, 255];
        $W     = 180;

        // ── BANDEAU ENTÊTE ───────────────────────────────────────────────────
        $pdf->SetFillColor(...$green);
        $pdf->Rect(0, 0, 210, 30, 'F');
        $pdf->SetXY(15, 10);
        $pdf->SetTextColor(...$gold);
        $pdf->SetFont('Arial', 'B', 18);
        $pdf->Cell(90, 8, $u(MAIL_FROM_NAME), 0, 0);

        $periodLabel = ($data['period'] ?? 'month') === 'year' ? 'Année' : 'Mois';
        $pdf->SetFont('Arial', '', 9);
        $pdf->SetTextColor(...$white);
        $pdf->SetXY(105, 12);
        $rangeLabel = self::fmtRange((string) ($data['from'] ?? ''), (string) ($data['to'] ?? ''));
        $pdf->Cell(90, 5, $u($periodLabel . ' · ' . $rangeLabel), 0, 1, 'R');

        $pdf->SetY(38);

        // Tirets/flèches ASCII uniquement : FPDF (polices core) + ISO-8859-1
        // n'incluent pas "—"/"→", qui ressortiraient en "?" sur le PDF.
        $title = $mode === 'compare'
            ? 'Rapport comparatif - tous établissements'
            : 'Rapport - ' . ($data['title'] ?? 'Établissement');
        $pdf->SetFont('Arial', 'B', 15);
        $pdf->SetTextColor(...$dark);
        $pdf->SetX(15);
        $pdf->Cell($W, 9, $u($title), 0, 1);
        $pdf->Ln(2);

        if ($mode === 'compare') {
            // ── TABLEAU COMPARATIF ───────────────────────────────────────────
            $rows = $data['rows'] ?? [];

            $pdf->SetFillColor(...$green);
            $pdf->SetTextColor(...$white);
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->SetX(15);
            $pdf->Cell(50, 8, $u('Établissement'), 1, 0, 'L', true);
            $pdf->Cell(32, 8, $u('Revenus'), 1, 0, 'R', true);
            $pdf->Cell(32, 8, $u('Dépenses'), 1, 0, 'R', true);
            $pdf->Cell(32, 8, $u('Bénéfice net'), 1, 0, 'R', true);
            $pdf->Cell(34, 8, $u('Occupation'), 1, 1, 'R', true);

            $totRevenue = 0.0; $totExpenses = 0.0; $totProfit = 0.0;
            $pdf->SetFont('Arial', '', 9);
            foreach ($rows as $i => $row) {
                $pdf->SetFillColor(...($i % 2 === 0 ? [255, 255, 255] : $light));
                $pdf->SetTextColor(...$dark);
                $pdf->SetX(15);
                $pdf->Cell(50, 7, $u((string) ($row['name'] ?? '—')), 1, 0, 'L', true);
                $pdf->Cell(32, 7, $u($fmt((float) ($row['revenue'] ?? 0))), 1, 0, 'R', true);
                $pdf->Cell(32, 7, $u($fmt((float) ($row['expenses'] ?? 0))), 1, 0, 'R', true);
                $net = (float) ($row['net_profit'] ?? 0);
                $pdf->SetTextColor(...($net >= 0 ? [22, 163, 74] : [220, 38, 38]));
                $pdf->Cell(32, 7, $u($fmt($net)), 1, 0, 'R', true);
                $pdf->SetTextColor(...$dark);
                $pdf->Cell(34, 7, $u(number_format((float) ($row['occupancy_rate'] ?? 0), 1, ',', ' ') . ' %'), 1, 1, 'R', true);

                $totRevenue  += (float) ($row['revenue'] ?? 0);
                $totExpenses += (float) ($row['expenses'] ?? 0);
                $totProfit   += $net;
            }

            $pdf->SetFont('Arial', 'B', 9);
            $pdf->SetFillColor(...$green);
            $pdf->SetTextColor(...$white);
            $pdf->SetX(15);
            $pdf->Cell(50, 8, $u('TOTAL'), 1, 0, 'L', true);
            $pdf->Cell(32, 8, $u($fmt($totRevenue)), 1, 0, 'R', true);
            $pdf->Cell(32, 8, $u($fmt($totExpenses)), 1, 0, 'R', true);
            $pdf->Cell(32, 8, $u($fmt($totProfit)), 1, 0, 'R', true);
            $pdf->Cell(34, 8, '', 1, 1, 'R', true);
        } else {
            // ── 4 INDICATEURS CLÉS ────────────────────────────────────────────
            // Variation vs période précédente (ReportController::previousPeriodComparison) —
            // pour les dépenses, une hausse est défavorable (couleur inversée). Pas de
            // flèche Unicode (▲/▼) : absente des polices core FPDF + ISO-8859-1, ressortirait
            // en "?" — voir même remarque plus haut pour "—"/"→".
            $prev = $data['previous'] ?? null;
            $pctLabel = fn(?float $p): string => $p === null ? '' :
                ($p >= 0 ? '+' : '') . number_format($p, 1, ',', ' ') . ' % vs préc.';
            $pctColor = fn(?float $p, bool $invert = false): array => $p === null ? $gray
                : ((($invert ? $p <= 0 : $p >= 0)) ? [22, 163, 74] : [220, 38, 38]);

            $kpis = [
                ['Revenus',       $fmt((float) ($data['revenue']     ?? 0)), [22, 163, 74],
                    $pctLabel($prev['revenue_pct']    ?? null), $pctColor($prev['revenue_pct']    ?? null)],
                ['Dépenses',      $fmt((float) ($data['expenses']    ?? 0)), [220, 38, 38],
                    $pctLabel($prev['expenses_pct']   ?? null), $pctColor($prev['expenses_pct']   ?? null, true)],
                ['Bénéfice net',  $fmt((float) ($data['net_profit']  ?? 0)), ((float) ($data['net_profit'] ?? 0)) >= 0 ? [22, 163, 74] : [220, 38, 38],
                    $pctLabel($prev['net_profit_pct'] ?? null), $pctColor($prev['net_profit_pct'] ?? null)],
                ["Taux d'occupation", number_format((float) ($data['occupancy_rate'] ?? 0), 1, ',', ' ') . ' %', [37, 99, 235],
                    $pctLabel($prev['occupancy_pct']  ?? null), $pctColor($prev['occupancy_pct']  ?? null)],
            ];
            $kpiW = $W / 4;
            $kpiY = $pdf->GetY(); // fixe une bonne fois : Cell(...,0,2) déplace le curseur à chaque itération
            foreach ($kpis as $i => [$label, $value, $color, $pctText, $pctCol]) {
                $x = 15 + $i * $kpiW;
                $pdf->SetFillColor(...$light);
                $pdf->Rect($x, $kpiY, $kpiW - 2, 24, 'F');
                $pdf->SetXY($x + 3, $kpiY + 3);
                $pdf->SetFont('Arial', '', 7);
                $pdf->SetTextColor(...$gray);
                $pdf->Cell($kpiW - 6, 4, $u($label), 0, 2);
                $pdf->SetX($x + 3);
                $pdf->SetFont('Arial', 'B', 11);
                $pdf->SetTextColor(...$color);
                $pdf->Cell($kpiW - 6, 6, $u($value), 0, 2);
                if ($pctText !== '') {
                    $pdf->SetX($x + 3);
                    $pdf->SetFont('Arial', '', 7);
                    $pdf->SetTextColor(...$pctCol);
                    $pdf->Cell($kpiW - 6, 4, $u($pctText), 0, 2);
                }
            }
            $pdf->SetY($kpiY + 30);

            // ── DÉPENSES PAR CATÉGORIE ───────────────────────────────────────
            $byCategory = $data['expenses_by_category'] ?? [];
            if ($byCategory) {
                $pdf->SetFont('Arial', 'B', 11);
                $pdf->SetTextColor(...$dark);
                $pdf->SetX(15);
                $pdf->Cell($W, 7, $u('Dépenses par catégorie'), 0, 1);

                $pdf->SetFillColor(...$green);
                $pdf->SetTextColor(...$white);
                $pdf->SetFont('Arial', 'B', 8);
                $pdf->SetX(15);
                $pdf->Cell(120, 7, $u('Catégorie'), 1, 0, 'L', true);
                $pdf->Cell(60, 7, $u('Montant'), 1, 1, 'R', true);

                $pdf->SetFont('Arial', '', 9);
                foreach ($byCategory as $i => $cat) {
                    $pdf->SetFillColor(...($i % 2 === 0 ? [255, 255, 255] : $light));
                    $pdf->SetTextColor(...$dark);
                    $pdf->SetX(15);
                    $label = $cat['category'] ?? '—';
                    $pdf->Cell(120, 6, $u($label), 1, 0, 'L', true);
                    $pdf->Cell(60, 6, $u($fmt((float) ($cat['total'] ?? 0))), 1, 1, 'R', true);
                }
                $pdf->Ln(6);
            }

            // ── CA PAR TYPE DE CHAMBRE ────────────────────────────────────────
            $byRoomType = $data['revenue_by_room_type'] ?? [];
            if ($byRoomType) {
                $pdf->SetFont('Arial', 'B', 11);
                $pdf->SetTextColor(...$dark);
                $pdf->SetX(15);
                $pdf->Cell($W, 7, $u('Répartition du CA par type de chambre'), 0, 1);

                $pdf->SetFillColor(...$green);
                $pdf->SetTextColor(...$white);
                $pdf->SetFont('Arial', 'B', 8);
                $pdf->SetX(15);
                $pdf->Cell(90, 7, $u('Type de chambre'), 1, 0, 'L', true);
                $pdf->Cell(30, 7, $u('Réservations'), 1, 0, 'R', true);
                $pdf->Cell(60, 7, $u('Revenus'), 1, 1, 'R', true);

                $pdf->SetFont('Arial', '', 9);
                foreach ($byRoomType as $i => $rt) {
                    $pdf->SetFillColor(...($i % 2 === 0 ? [255, 255, 255] : $light));
                    $pdf->SetTextColor(...$dark);
                    $pdf->SetX(15);
                    $pdf->Cell(90, 6, $u((string) ($rt['room_type'] ?? '—')), 1, 0, 'L', true);
                    $pdf->Cell(30, 6, $u((string) ($rt['bookings_count'] ?? 0)), 1, 0, 'R', true);
                    $pdf->Cell(60, 6, $u($fmt((float) ($rt['total'] ?? 0))), 1, 1, 'R', true);
                }
                $pdf->Ln(6);
            }

            // ── MEILLEURS CLIENTS ──────────────────────────────────────────────
            $topClients = $data['top_clients'] ?? [];
            if ($topClients) {
                $pdf->SetFont('Arial', 'B', 11);
                $pdf->SetTextColor(...$dark);
                $pdf->SetX(15);
                $pdf->Cell($W, 7, $u('Meilleurs clients'), 0, 1);

                $pdf->SetFillColor(...$green);
                $pdf->SetTextColor(...$white);
                $pdf->SetFont('Arial', 'B', 8);
                $pdf->SetX(15);
                $pdf->Cell(90, 7, $u('Client'), 1, 0, 'L', true);
                $pdf->Cell(30, 7, $u('Réservations'), 1, 0, 'R', true);
                $pdf->Cell(60, 7, $u('CA généré'), 1, 1, 'R', true);

                $pdf->SetFont('Arial', '', 9);
                foreach ($topClients as $i => $c) {
                    $pdf->SetFillColor(...($i % 2 === 0 ? [255, 255, 255] : $light));
                    $pdf->SetTextColor(...$dark);
                    $pdf->SetX(15);
                    $pdf->Cell(90, 6, $u((string) ($c['client_name'] ?? '—')), 1, 0, 'L', true);
                    $pdf->Cell(30, 6, $u((string) ($c['bookings_count'] ?? 0)), 1, 0, 'R', true);
                    $pdf->Cell(60, 6, $u($fmt((float) ($c['total_spent'] ?? 0))), 1, 1, 'R', true);
                }
                $pdf->Ln(6);
            }

            // ── PAIEMENTS ─────────────────────────────────────────────────────
            $payments = $data['recent_payments'] ?? [];
            if ($payments) {
                $pdf->SetFont('Arial', 'B', 11);
                $pdf->SetTextColor(...$dark);
                $pdf->SetX(15);
                $pdf->Cell($W, 7, $u('Paiements (' . count($payments) . ')'), 0, 1);

                $pdf->SetFillColor(...$green);
                $pdf->SetTextColor(...$white);
                $pdf->SetFont('Arial', 'B', 8);
                $pdf->SetX(15);
                $pdf->Cell(40, 7, $u('Référence'), 1, 0, 'L', true);
                $pdf->Cell(45, 7, $u('Client'), 1, 0, 'L', true);
                $pdf->Cell(35, 7, $u('Montant net'), 1, 0, 'R', true);
                $pdf->Cell(30, 7, $u('Commission'), 1, 0, 'R', true);
                $pdf->Cell(30, 7, $u('Date'), 1, 1, 'R', true);

                // Montant net (amount - commission_amount) : ce que l'établissement
                // garde réellement — voir ReportController::gatherSummary(). La
                // commission plateforme est affichée à part, pas déjà déduite en amont.
                $pdf->SetFont('Arial', '', 9);
                foreach ($payments as $i => $p) {
                    $pdf->SetFillColor(...($i % 2 === 0 ? [255, 255, 255] : $light));
                    $pdf->SetTextColor(...$dark);
                    $pdf->SetX(15);
                    $pdf->Cell(40, 6, $u((string) ($p['reference'] ?? '—')), 1, 0, 'L', true);
                    $pdf->Cell(45, 6, $u((string) ($p['client_name'] ?? '—')), 1, 0, 'L', true);
                    $pdf->Cell(35, 6, $u($fmt((float) ($p['net_amount'] ?? $p['amount'] ?? 0))), 1, 0, 'R', true);
                    $commission = (float) ($p['commission_amount'] ?? 0);
                    $pdf->Cell(30, 6, $u($commission > 0 ? $fmt($commission) : '—'), 1, 0, 'R', true);
                    $dateStr = !empty($p['paid_at']) ? date('d/m/Y', strtotime($p['paid_at'])) : '—';
                    $pdf->Cell(30, 6, $u($dateStr), 1, 1, 'R', true);
                }
            }
        }

        // ── PIED DE PAGE ──────────────────────────────────────────────────────
        $pdf->Ln(8);
        $pdf->SetFillColor(...$green);
        $pdf->Rect(15, $pdf->GetY(), $W, 0.5, 'F');
        $pdf->Ln(3);
        $pdf->SetFont('Arial', '', 7);
        $pdf->SetTextColor(...$gray);
        $pdf->Cell($W, 4, $u(MAIL_FROM_NAME . ' · ' . APP_URL . ' · Généré le ' . date('d/m/Y à H:i')), 0, 1, 'C');

        $pdf->Output('F', $path);
        return 'storage/pdf/' . $filename;
    }

    private static function fmtRange(string $from, string $to): string
    {
        $f = $from ? date('d/m/Y', strtotime($from)) : '';
        $t = $to   ? date('d/m/Y', strtotime($to))   : '';
        return $f && $t ? "$f au $t" : ($f ?: $t);
    }
}
