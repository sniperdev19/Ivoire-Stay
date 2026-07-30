<?php

namespace Controllers;

use Core\{Request, Response, PlanGate, Guard};
use Models\{Expense, Establishment};
use Services\UploadService;

class ExpenseController
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
    // 'expenses' d'un AUTRE de ses établissements que celui réellement ciblé
    // par la requête.
    private function gate(int $estabId): void
    {
        $estab = Establishment::find($estabId) ?? [];
        PlanGate::require($estab, 'expenses');
    }

    public function index(Request $req, array $params = []): void
    {
        $estabId = $this->estabId($req);
        if (!$estabId) Response::error('establishment_id requis');
        $this->gate($estabId);
        $filters = array_filter([
            'category' => $req->get('category'),
            'from'     => $req->get('from'),
            'to'       => $req->get('to'),
        ]);
        Response::success(Expense::findByEstablishment($estabId, $filters));
    }

    public function store(Request $req, array $params = []): void
    {
        $data    = $req->all();
        $estabId = $this->estabId($req);
        if (!$estabId) Response::error('establishment_id requis');
        $this->gate($estabId);

        $required = ['category', 'amount', 'expense_date'];
        foreach ($required as $f) {
            if (empty($data[$f])) Response::error("Champ requis : $f");
        }

        $receiptPath = null;
        $file = $req->file('receipt');
        if ($file && $file['error'] === UPLOAD_ERR_OK) {
            try {
                $receiptPath = UploadService::uploadReceipt($file, 0);
            } catch (\Exception $e) {
                Response::error($e->getMessage());
            }
        }

        $id = Expense::create([
            'establishment_id' => $estabId,
            'category'         => $data['category'],
            'amount'           => (float) $data['amount'],
            'description'      => $data['description'] ?? null,
            'expense_date'     => $data['expense_date'],
            'receipt_path'     => $receiptPath,
        ]);

        if ($receiptPath) {
            Expense::update($id, ['receipt_path' => str_replace('/0/', "/$id/", $receiptPath)]);
        }

        Response::success(Expense::find($id), 'Dépense enregistrée', 201);
    }

    public function update(Request $req, array $params = []): void
    {
        $id = (int) ($params['id'] ?? $_GET['_route_id'] ?? 0);
        Guard::requireExpense($id);

        $data    = $req->all();
        $allowed = ['category','amount','description','expense_date'];
        $update  = array_intersect_key($data, array_flip($allowed));
        Expense::update($id, $update);
        Response::success(Expense::find($id), 'Dépense mise à jour');
    }

    public function destroy(Request $req, array $params = []): void
    {
        $id = (int) ($params['id'] ?? $_GET['_route_id'] ?? 0);
        Guard::requireExpense($id);
        Expense::delete($id);
        Response::success(null, 'Dépense supprimée');
    }
}
