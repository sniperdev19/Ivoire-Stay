<?php

namespace Controllers;

use Core\{Request, Response, Guard};
use Models\{PublicClient, Booking};

class ClientController
{
    public function index(Request $req, array $params = []): void
    {
        // Scope à l'établissement actif (sélecteur front-end), pas à tous les
        // établissements de l'owner — cf. Guard::resolveEstabId(), même
        // pattern que BookingController::index(). Sans ça, un owner
        // multi-établissements voyait les clients de tous ses établissements
        // mélangés, quel que soit celui sélectionné dans l'interface.
        $estabId = Guard::resolveEstabId($req);
        if (!$estabId) Response::error('establishment_id requis');
        $search = trim((string) ($req->get('search') ?? ''));
        Response::success(PublicClient::allWithBookingCount([$estabId], $search));
    }

    public function show(Request $req, array $params = []): void
    {
        $id = (int) ($params['id'] ?? $_GET['_route_id'] ?? 0);
        Guard::requireClient($id);

        $estabIds = Guard::isSuperadmin() ? null : Guard::establishmentIds();
        $client   = PublicClient::withStats($id, $estabIds);
        $bookings = Booking::historyForClient($id, $estabIds);

        Response::success(['client' => $client, 'bookings' => $bookings]);
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
