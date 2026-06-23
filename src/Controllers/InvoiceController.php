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

    private function gate(): void
    {
        $user  = $_REQUEST['_user'];
        $estab = Establishment::find($user['establishment_id'] ?? 0) ?? [];
        PlanGate::require($estab, 'invoices');
    }

    public function index(Request $req, array $params = []): void
    {
        $this->gate();
        $estabId = $this->estabId($req);
        if (!$estabId) Response::error('establishment_id requis');
        $filters = array_filter(['status' => $req->get('status')]);
        Response::success(Invoice::allWithDetails($estabId, $filters));
    }

    public function store(Request $req, array $params = []): void
    {
        $this->gate();
        $data = $req->all();
        if (empty($data['booking_id'])) Response::error('booking_id requis');

        $booking = Guard::requireBooking((int) $data['booking_id']);

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
        $this->gate();
        $id  = (int) ($params['id'] ?? $_GET['_route_id'] ?? 0);
        Guard::requireInvoice($id);
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
        $this->gate();
        $id  = (int) ($params['id'] ?? 0);
        Guard::requireInvoice($id);
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

            $estabId = (int) ($_REQUEST['_user']['establishment_id'] ?? 0);
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

        $now = date('Y-m-d H:i:s');
        $id  = Payment::create([
            'booking_id' => (int) $data['booking_id'],
            'invoice_id' => (int) $data['invoice_id'],
            'amount'     => (float) $data['amount'],
            'method'     => $data['method'],
            'type'       => $data['type'] ?? 'full',
            'status'     => 'completed',
            'notes'      => $data['notes'] ?? null,
            'paid_at'    => $now,
        ]);

        // Mettre à jour le statut de la facture selon le montant encaissé
        $invoiceId = (int) $data['invoice_id'];
        $inv       = Invoice::find($invoiceId);
        $paid      = Invoice::paidAmount($invoiceId);

        $estabId = (int) ($_REQUEST['_user']['establishment_id'] ?? 0);

        if ($inv) {
            $ttc = (float) $inv['amount_ttc'];
            if ($paid >= $ttc) {
                Invoice::update($invoiceId, ['status' => 'paid', 'paid_at' => $now]);
                NotificationService::invoicePaid($estabId, $inv['invoice_number'] ?? '#' . $invoiceId, $invoiceId);
            } elseif ($paid > 0) {
                if ($inv['status'] === 'draft') {
                    Invoice::update($invoiceId, ['status' => 'sent']);
                }
            }
        }

        NotificationService::paymentReceived($estabId, (float) $data['amount'], $data['method'], $invoiceId);

        Response::success(Payment::find($id), 'Paiement enregistré', 201);
    }

    public function updatePayment(Request $req, array $params = []): void
    {
        $id      = (int) ($params['id'] ?? $_GET['_route_id'] ?? 0);
        $payment = Guard::requirePayment($id);

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
