<?php

namespace Controllers;

use Core\{Request, Response, Database, PlanGate, Guard};
use Models\{Expense, ExpenseCategory, Establishment};
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

    // ─── Catégories de dépenses ───────────────────────────────────────────────

    public function indexCategories(Request $req, array $params = []): void
    {
        $estabId = $this->estabId($req);
        if (!$estabId) Response::error('establishment_id requis');
        Response::success(ExpenseCategory::findByEstablishment($estabId));
    }

    public function storeCategory(Request $req, array $params = []): void
    {
        $data    = $req->all();
        $estabId = $this->estabId($req);
        if (!$estabId) Response::error('establishment_id requis');

        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') Response::error('Nom requis');

        $color = (string) ($data['color'] ?? '#6B7280');
        if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) Response::error('Couleur invalide');

        if (ExpenseCategory::first(['establishment_id' => $estabId, 'name' => $name])) {
            Response::error('Cette catégorie existe déjà', 409);
        }

        $id = ExpenseCategory::create(['establishment_id' => $estabId, 'name' => $name, 'color' => $color]);
        Response::success(ExpenseCategory::find($id), 'Catégorie créée', 201);
    }

    public function updateCategory(Request $req, array $params = []): void
    {
        $id  = (int) ($params['id'] ?? $_GET['_route_id'] ?? 0);
        $cat = Guard::requireExpenseCategory($id);

        $data   = $req->all();
        $update = [];

        if (isset($data['name'])) {
            $name = trim((string) $data['name']);
            if ($name === '') Response::error('Nom requis');
            $existing = ExpenseCategory::first(['establishment_id' => $cat['establishment_id'], 'name' => $name]);
            if ($existing && (int) $existing['id'] !== $id) Response::error('Cette catégorie existe déjà', 409);
            $update['name'] = $name;
        }
        if (isset($data['color'])) {
            if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $data['color'])) Response::error('Couleur invalide');
            $update['color'] = $data['color'];
        }

        if ($update) {
            // Les dépenses déjà enregistrées référencent la catégorie par son
            // nom (texte libre, pas de FK) — un renommage doit les suivre pour
            // ne pas les rendre orphelines de leur catégorie.
            if (isset($update['name']) && $update['name'] !== $cat['name']) {
                Database::query(
                    'UPDATE expenses SET category = ? WHERE establishment_id = ? AND category = ?',
                    [$update['name'], $cat['establishment_id'], $cat['name']]
                );
            }
            ExpenseCategory::update($id, $update);
        }
        Response::success(ExpenseCategory::find($id), 'Catégorie mise à jour');
    }

    public function destroyCategory(Request $req, array $params = []): void
    {
        $id  = (int) ($params['id'] ?? $_GET['_route_id'] ?? 0);
        $cat = Guard::requireExpenseCategory($id);

        $inUse = (int) Database::query(
            'SELECT COUNT(*) FROM expenses WHERE establishment_id = ? AND category = ?',
            [$cat['establishment_id'], $cat['name']]
        )->fetchColumn();
        if ($inUse > 0) {
            Response::error("Impossible de supprimer : $inUse dépense(s) utilisent cette catégorie", 409);
        }

        ExpenseCategory::delete($id);
        Response::success(null, 'Catégorie supprimée');
    }
}
