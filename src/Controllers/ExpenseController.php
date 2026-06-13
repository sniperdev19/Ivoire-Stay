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

    private function gate(): void
    {
        $user  = $_REQUEST['_user'];
        $estab = Establishment::find($user['establishment_id'] ?? 0) ?? [];
        PlanGate::require($estab, 'expenses');
    }

    public function index(Request $req, array $params = []): void
    {
        $this->gate();
        $estabId = $this->estabId($req);
        if (!$estabId) Response::error('establishment_id requis');
        $filters = array_filter([
            'category' => $req->get('category'),
            'from'     => $req->get('from'),
            'to'       => $req->get('to'),
        ]);
        Response::success(Expense::findByEstablishment($estabId, $filters));
    }

    public function store(Request $req, array $params = []): void
    {
        $this->gate();
        $data    = $req->all();
        $estabId = $this->estabId($req);
        if (!$estabId) Response::error('establishment_id requis');

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
