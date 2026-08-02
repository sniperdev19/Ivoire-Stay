<?php

namespace Controllers;

use Core\{Request, Response, PlanGate, Guard};
use Models\{Invoice, Payment, Booking, Establishment};
use Services\{PdfService, MailService, NotificationService};

class InvoiceController
{
    private function estabId(Request $req): int
    {
        return Guard::resolveEstabId($req);
    }

    // Prend l'établissement déjà résolu (Guard::resolveEstabId) et non
    // $user['establishment_id'] : ce dernier est figé sur le PREMIER
    // établissement du owner à l'inscription — pour un owner
    // multi-établissements (plan Business), gater sur ce seul établissement
    // permettait de contourner (ou déclenchait à tort) la limite plan
    // 'invoices'/'payments' d'un AUTRE de ses établissements que celui
    // réellement ciblé par la requête.
    private function gate(int $estabId, string $feature = 'invoices'): void
    {
        $estab = Establishment::find($estabId) ?? [];
        PlanGate::require($estab, $feature);
    }

    public function index(Request $req, array $params = []): void
    {
        $estabId = $this->estabId($req);
        if (!$estabId) Response::error('establishment_id requis');
        $this->gate($estabId);
        $filters = array_filter(['status' => $req->get('status')]);
        Response::success(Invoice::allWithDetails($estabId, $filters));
    }

    public function store(Request $req, array $params = []): void
    {
        $data = $req->all();
        if (empty($data['booking_id'])) Response::error('booking_id requis');

        $booking = Guard::requireBooking((int) $data['booking_id']);
        $this->gate((int) $booking['establishment_id']);

        $existing = Invoice::first(['booking_id' => $data['booking_id']]);
        if ($existing) Response::error('Facture déjà existante pour cette réservation', 409);

        $id = Invoice::createForBooking(
            (int) $data['booking_id'],
            (float) ($data['amount'] ?? $booking['total_amount']),
            (float) ($data['tax_rate'] ?? 0)
        );

        Response::success(Invoice::findWithDetails($id), 'Facture créée', 201);
    }

    public function show(Request $req, array $params = []): void
    {
        $id = (int) ($params['id'] ?? $_GET['_route_id'] ?? 0);
        Guard::requireInvoice($id);
        Response::success(Invoice::findWithDetails($id));
    }

    public function update(Request $req, array $params = []): void
    {
        $id = (int) ($params['id'] ?? $_GET['_route_id'] ?? 0);
        Guard::requireInvoice($id);

        $data    = $req->all();
        $allowed = ['status','tax_rate','amount_ht','amount_ttc'];
        $update  = array_intersect_key($data, array_flip($allowed));
        if (!empty($update)) Invoice::update($id, $update);

        Response::success(Invoice::findWithDetails($id), 'Facture mise à jour');
    }

    public function pdf(Request $req, array $params = []): void
    {
        $id      = (int) ($params['id'] ?? $_GET['_route_id'] ?? 0);
        $guarded = Guard::requireInvoice($id);
        $this->gate((int) $guarded['establishment_id']);
        $inv = Invoice::findWithDetails($id);
        if (!$inv) Response::notFound('Facture introuvable');

        try {
            $pdfPath = PdfService::generateInvoice($inv);
            Invoice::update($id, ['pdf_path' => $pdfPath]);

            // Serve the PDF if it's a real PDF
            $absPath = BASE_PATH . '/' . $pdfPath;
            if (file_exists($absPath) && str_ends_with($absPath, '.pdf')) {
                header('Content-Type: application/pdf');
                header('Content-Disposition: attachment; filename="' . $inv['invoice_number'] . '.pdf"');
                readfile($absPath);
                exit;
            }

            Response::success(['pdf_path' => $pdfPath]);
        } catch (\Exception $e) {
            Response::error('Erreur génération PDF: ' . $e->getMessage());
        }
    }

    public function sendByMail(Request $req, array $params = []): void
    {
        $id      = (int) ($params['id'] ?? 0);
        $guarded = Guard::requireInvoice($id);
        $estabId = (int) $guarded['establishment_id'];
        $this->gate($estabId);
        $inv = Invoice::findWithDetails($id);
        if (!$inv) Response::notFound('Facture introuvable');

        $email = $req->get('email') ?: ($inv['client_email'] ?? '');
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Response::error('Adresse email invalide ou introuvable');
        }

        try {
            $pdfPath    = PdfService::generateInvoice($inv);
            $pdfAbsPath = BASE_PATH . '/' . $pdfPath;
            Invoice::update($id, ['pdf_path' => $pdfPath, 'status' => $inv['status'] === 'draft' ? 'sent' : $inv['status']]);

            $inv['client_email'] = $email;
            MailService::invoiceMail($inv, $pdfAbsPath);

            NotificationService::invoiceSent($estabId, $inv['invoice_number'] ?? '#' . $id, $email, $id);

            Response::success(['sent_to' => $email], 'Facture envoyée par email');
        } catch (\Exception $e) {
            Response::error('Erreur envoi email : ' . $e->getMessage());
        }
    }

    // ─── Payments ─────────────────────────────────────────────────────────────

    public function payments(Request $req, array $params = []): void
    {
        $estabId = $this->estabId($req);
        if (!$estabId) Response::error('establishment_id requis');
        $this->gate($estabId, 'payments');
        $filters = array_filter([
            'method' => $req->get('method'),
            'status' => $req->get('status'),
        ]);
        Response::success(Payment::allWithDetails($estabId, $filters));
    }

    public function storePayment(Request $req, array $params = []): void
    {
        $data = $req->all();
        $required = ['booking_id', 'invoice_id', 'amount', 'method'];
        foreach ($required as $f) {
            if (empty($data[$f])) Response::error("Champ requis : $f");
        }

        // La facture et la réservation doivent être dans le périmètre, et liées
        $inv     = Guard::requireInvoice((int) $data['invoice_id']);
        Guard::requireBooking((int) $data['booking_id']);
        if ((int) $inv['booking_id'] !== (int) $data['booking_id']) {
            Response::error('Facture et réservation incohérentes');
        }

        // Phase B du gel (voir EstablishmentFreezeService) : plus d'encaissement
        // possible une fois le délai de grâce dépassé (phase A l'autorisait encore).
        $estab = Establishment::find($inv['establishment_id']);
        if (PlanGate::isHardFrozen($estab ?? [])) {
            Response::error("Cet établissement est totalement gelé (délai de grâce dépassé), plus aucune action possible. Mettez à niveau votre abonnement pour le réactiver.", 403);
        }

        $invoiceId = (int) $data['invoice_id'];
        $id = Invoice::registerPayment(
            (int) $data['booking_id'],
            $invoiceId,
            (float) $data['amount'],
            $data['method'],
            $data['type'] ?? 'full',
            $data['notes'] ?? null
        );

        $estabId = (int) $inv['establishment_id'];
        $inv     = Invoice::find($invoiceId);

        if ($inv && $inv['status'] === 'paid') {
            NotificationService::invoicePaid($estabId, $inv['invoice_number'] ?? '#' . $invoiceId, $invoiceId);
        }
        NotificationService::paymentReceived($estabId, (float) $data['amount'], $data['method'], $invoiceId);

        Response::success(Payment::find($id), 'Paiement enregistré', 201);
    }

    public function updatePayment(Request $req, array $params = []): void
    {
        $id      = (int) ($params['id'] ?? $_GET['_route_id'] ?? 0);
        $payment = Guard::requirePayment($id);
        $this->gate((int) $payment['establishment_id'], 'payments');

        $data    = $req->all();
        $allowed = ['amount', 'method', 'type', 'status', 'notes'];
        $update  = array_intersect_key($data, array_flip($allowed));

        // Si passage à completed et paid_at absent, le dater maintenant
        if (($update['status'] ?? '') === 'completed' && empty($payment['paid_at'])) {
            $update['paid_at'] = date('Y-m-d H:i:s');
        }

        Payment::update($id, $update);

        // Réévaluer la facture associée
        $invoiceId = (int) $payment['invoice_id'];
        $inv       = Invoice::find($invoiceId);
        $paid      = Invoice::paidAmount($invoiceId);

        if ($inv) {
            $now = date('Y-m-d H:i:s');
            $ttc = (float) $inv['amount_ttc'];
            if ($paid >= $ttc && $inv['status'] !== 'paid') {
                Invoice::update($invoiceId, ['status' => 'paid', 'paid_at' => $now]);
            } elseif ($paid > 0 && $inv['status'] === 'draft') {
                Invoice::update($invoiceId, ['status' => 'sent']);
            } elseif ($paid <= 0 && $inv['status'] === 'paid') {
                // Remboursement total — repasser en envoyée
                Invoice::update($invoiceId, ['status' => 'sent', 'paid_at' => null]);
            }
        }

        Response::success(Payment::find($id), 'Paiement mis à jour');
    }
}
