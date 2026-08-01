<?php

namespace Controllers;

use Core\{Request, Response, Database};
use Models\{Agent, AgentPayout, AgentBonusAward};

/** Superadmin uniquement — vue d'ensemble des agents commerciaux et traitement des versements. */
class AgentAdminController
{
    public function agents(Request $req, array $params = []): void
    {
        Response::success(array_map(fn($a) => Agent::safe($a), Agent::allWithStats()));
    }

    public function payouts(Request $req, array $params = []): void
    {
        Response::success(AgentPayout::allWithStatus($req->get('status')));
    }

    /** GET /api/admin/agent-bonuses — primes récentes décernées (toutes, palier inclus), pour admin/agents.php. */
    public function bonuses(Request $req, array $params = []): void
    {
        Response::success(AgentBonusAward::recent());
    }

    public function markPaid(Request $req, array $params = []): void
    {
        $id  = (int) ($params['id'] ?? $_GET['_route_id'] ?? 0);
        $row = AgentPayout::find($id);
        if (!$row) Response::notFound('Versement introuvable');
        if ($row['status'] !== 'pending') Response::error('Ce versement a déjà été traité.');

        $user = $_REQUEST['_user'];
        AgentPayout::update($id, [
            'status'       => 'paid',
            'processed_by' => (int) ($user['id'] ?? 0),
            'processed_at' => date('Y-m-d H:i:s'),
        ]);

        Response::success(AgentPayout::find($id), 'Versement marqué comme payé');
    }

    public function reject(Request $req, array $params = []): void
    {
        $id  = (int) ($params['id'] ?? $_GET['_route_id'] ?? 0);
        $row = AgentPayout::find($id);
        if (!$row) Response::notFound('Versement introuvable');
        if ($row['status'] !== 'pending') Response::error('Ce versement a déjà été traité.');

        $notes = trim((string) $req->post('admin_notes', ''));
        if ($notes === '') Response::error('Un motif de rejet est requis.');

        $user = $_REQUEST['_user'];
        AgentPayout::update($id, [
            'status'       => 'rejected',
            'processed_by' => (int) ($user['id'] ?? 0),
            'processed_at' => date('Y-m-d H:i:s'),
            'admin_notes'  => $notes,
        ]);

        Response::success(AgentPayout::find($id), 'Versement rejeté');
    }
}
