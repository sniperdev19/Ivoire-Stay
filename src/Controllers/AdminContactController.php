<?php

namespace Controllers;

use Core\{Request, Response};
use Models\ContactMessage;

/**
 * Boîte de réception des messages du formulaire de contact public (/contact).
 * Auparavant envoyés par email uniquement (PublicController::sendContact()),
 * sans aucune trace consultable — voir Models\ContactMessage.
 */
class AdminContactController
{
    public function index(Request $req, array $params = []): void
    {
        Response::success(ContactMessage::allRecent());
    }

    public function markRead(Request $req, array $params = []): void
    {
        $id = (int) ($params['id'] ?? $_GET['_route_id'] ?? 0);
        if (!ContactMessage::find($id)) Response::notFound('Message introuvable');
        ContactMessage::markRead($id);
        Response::success(null, 'Message marqué comme lu');
    }

    public function destroy(Request $req, array $params = []): void
    {
        $id = (int) ($params['id'] ?? $_GET['_route_id'] ?? 0);
        if (!ContactMessage::find($id)) Response::notFound('Message introuvable');
        ContactMessage::delete($id);
        Response::success(null, 'Message supprimé');
    }
}
