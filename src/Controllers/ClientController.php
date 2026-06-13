<?php

namespace Controllers;

use Core\{Request, Response, Guard};
use Models\PublicClient;

class ClientController
{
    public function index(Request $req, array $params = []): void
    {
        // Ne lister que les clients ayant une réservation dans le périmètre
        $estabIds = Guard::isSuperadmin() ? null : Guard::establishmentIds();
        Response::success(PublicClient::allWithBookingCount($estabIds));
    }

    public function show(Request $req, array $params = []): void
    {
        $id     = (int) ($params['id'] ?? $_GET['_route_id'] ?? 0);
        $client = Guard::requireClient($id);
        Response::success($client);
    }

    public function update(Request $req, array $params = []): void
    {
        $id = (int) ($params['id'] ?? $_GET['_route_id'] ?? 0);
        Guard::requireClient($id);

        $data    = $req->all();
        $allowed = ['first_name','last_name','email','phone','id_doc_type','id_doc_number'];
        $update  = array_intersect_key($data, array_flip($allowed));
        PublicClient::update($id, $update);
        Response::success(PublicClient::find($id), 'Client mis à jour');
    }

    public function destroy(Request $req, array $params = []): void
    {
        $id = (int) ($params['id'] ?? $_GET['_route_id'] ?? 0);
        Guard::requireClient($id);
        PublicClient::delete($id);
        Response::success(null, 'Client supprimé');
    }
}
