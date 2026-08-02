<?php

namespace Controllers;

use Core\{Request, Response, PlanGate, Guard};
use Models\{PayoutRequest, Establishment, User};
use Services\NotificationService;

class PayoutController
{
    private function estabId(Request $req): int
    {
        return Guard::resolveEstabId($req);
    }

    /**
     * Un superadmin n'a pas nécessairement d'établissement/plan propre (il peut
     * consulter/traiter les demandes de TOUS les établissements) : le gate plan
     * ne s'applique qu'aux appels faits pour le compte d'un établissement précis.
     *
     * Prend l'établissement déjà résolu (Guard::resolveEstabId) et non
     * $user['establishment_id'] : ce dernier est figé sur le PREMIER
     * établissement du owner à l'inscription — pour un owner
     * multi-établissements (plan Business), gater sur ce seul établissement
     * permettait de contourner (ou déclenchait à tort) la limite plan
     * 'online_payment_control' d'un AUTRE de ses établissements que celui
     * réellement ciblé par la requête.
     */
    private function gate(int $estabId): void
    {
        if (Guard::isSuperadmin()) return;
        $estab = Establishment::find($estabId) ?? [];
        PlanGate::require($estab, 'online_payment_control');
    }

    public function balance(Request $req, array $params = []): void
    {
        $estabId = $this->estabId($req);
        if (!$estabId) Response::error('establishment_id requis');
        $this->gate($estabId);

        Response::success([
            'gross_online_collected' => PayoutRequest::grossOnlineCollected($estabId),
            'reserved_or_paid'       => PayoutRequest::reservedOrPaid($estabId),
            'available_balance'      => PayoutRequest::availableBalance($estabId),
        ]);
    }

    public function index(Request $req, array $params = []): void
    {
        if (Guard::isSuperadmin() && $req->get('scope') === 'all') {
            Response::success(PayoutRequest::allWithStatus($req->get('status')));
            return;
        }

        $estabId = $this->estabId($req);
        if (!$estabId) Response::error('establishment_id requis');
        $this->gate($estabId);
        Response::success(PayoutRequest::findByEstablishment($estabId));
    }

    public function store(Request $req, array $params = []): void
    {
        $data    = $req->all();
        $estabId = $this->estabId($req);
        if (!$estabId) Response::error('establishment_id requis');
        $this->gate($estabId);

        // Un retrait déplace de l'argent réel vers un numéro Mobile Money : contrairement
        // au reste de l'app (email non vérifié = simple rappel, jamais bloquant, cf.
        // saas/layout.php), cette action est assez sensible pour justifier la friction —
        // ça garantit aussi que l'owner peut récupérer son compte (mot de passe oublié)
        // s'il perd l'accès après une demande de retrait litigieuse.
        $requester = $_REQUEST['_user'];
        if (($requester['role'] ?? '') === 'owner') {
            $freshUser = User::find((int) ($requester['id'] ?? 0));
            if (empty($freshUser['email_verified_at'])) {
                Response::error(
                    "Vérifiez votre adresse email avant de demander un retrait. Cette action déplace des fonds réels et nécessite un compte sécurisé. Un bandeau en haut de page vous permet de renvoyer le lien de confirmation.",
                    403
                );
            }
        }

        $required = ['amount', 'mobile_money_operator', 'mobile_money_number'];
        foreach ($required as $f) {
            if (empty($data[$f])) Response::error("Champ requis : $f");
        }

        if (!in_array($data['mobile_money_operator'], ['orange', 'wave', 'mtn'], true)) {
            Response::error('Opérateur Mobile Money invalide');
        }

        $amount = (float) $data['amount'];
        if ($amount <= 0) Response::error('Montant invalide');

        $available = PayoutRequest::availableBalance($estabId);
        if ($amount > $available) {
            Response::error("Montant supérieur au solde disponible (" . number_format($available, 0, ',', ' ') . " FCFA)");
        }

        $user = $_REQUEST['_user'];
        $id = PayoutRequest::create([
            'establishment_id'      => $estabId,
            'amount'                => $amount,
            'mobile_money_operator' => $data['mobile_money_operator'],
            'mobile_money_number'   => trim($data['mobile_money_number']),
            'status'                => 'pending',
            'requested_by'          => (int) ($user['id'] ?? 0),
        ]);

        $estab = Establishment::find($estabId);
        NotificationService::payoutRequested($estab['name'] ?? '#' . $estabId, $amount, $id);

        Response::success(PayoutRequest::find($id), 'Demande de retrait envoyée', 201);
    }

    // ─── Superadmin uniquement : un établissement ne peut pas auto-valider sa propre demande ───

    public function markPaid(Request $req, array $params = []): void
    {
        $id  = (int) ($params['id'] ?? $_GET['_route_id'] ?? 0);
        $row = Guard::requirePayoutRequest($id);
        if ($row['status'] !== 'pending') Response::error('Cette demande a déjà été traitée.');

        $user = $_REQUEST['_user'];
        PayoutRequest::update($id, [
            'status'       => 'paid',
            'processed_by' => (int) ($user['id'] ?? 0),
            'processed_at' => date('Y-m-d H:i:s'),
        ]);

        Response::success(PayoutRequest::find($id), 'Retrait marqué comme payé');
    }

    public function reject(Request $req, array $params = []): void
    {
        $id  = (int) ($params['id'] ?? $_GET['_route_id'] ?? 0);
        $row = Guard::requirePayoutRequest($id);
        if ($row['status'] !== 'pending') Response::error('Cette demande a déjà été traitée.');

        $notes = trim((string) $req->post('admin_notes', ''));
        if ($notes === '') Response::error('Un motif de rejet est requis.');

        $user = $_REQUEST['_user'];
        PayoutRequest::update($id, [
            'status'       => 'rejected',
            'processed_by' => (int) ($user['id'] ?? 0),
            'processed_at' => date('Y-m-d H:i:s'),
            'admin_notes'  => $notes,
        ]);

        Response::success(PayoutRequest::find($id), 'Demande rejetée');
    }
}
