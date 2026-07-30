<?php

namespace Controllers;

use Core\{Request, Response};
use Models\{NewsletterSubscriber, NewsletterCampaign};
use Services\MailService;

/**
 * Campagnes newsletter envoyées par le superadmin aux abonnés de la vitrine
 * publique (formulaire footer, PublicController::newsletterSubscribe()).
 * Envoi synchrone au clic "Envoyer" — même logique assumée que
 * Services\BackupService : le volume actuel ne justifie pas de file d'attente
 * asynchrone, à revoir si la liste d'abonnés grossit significativement.
 */
class AdminNewsletterController
{
    public function subscribers(Request $req, array $params = []): void
    {
        Response::success([
            'count'       => NewsletterSubscriber::countActive(),
            'subscribers' => NewsletterSubscriber::activeSubscribers(),
        ]);
    }

    public function campaigns(Request $req, array $params = []): void
    {
        Response::success(NewsletterCampaign::allRecent());
    }

    public function send(Request $req, array $params = []): void
    {
        $subject = trim((string) $req->input('subject', ''));
        $body    = trim((string) $req->input('body', ''));
        if ($subject === '') Response::error('Le sujet est requis');
        if ($body === '') Response::error('Le message est requis');

        $subscribers = NewsletterSubscriber::activeSubscribers();
        $bodyHtml    = "<div style='font-size:14px;color:#374151;line-height:1.7;white-space:pre-wrap;'>"
                     . nl2br(htmlspecialchars($body)) . '</div>';

        foreach ($subscribers as $sub) {
            $unsubscribeUrl = APP_URL . '/newsletter/desabonnement?token=' . $sub['unsubscribe_token'];
            MailService::newsletterCampaign($sub['email'], $subject, $bodyHtml, $unsubscribeUrl);
        }

        $campaignId = NewsletterCampaign::create([
            'subject'         => $subject,
            'body'            => $body,
            'recipient_count' => count($subscribers),
        ]);

        Response::success(NewsletterCampaign::find($campaignId), 'Campagne envoyée à ' . count($subscribers) . ' abonné(s)');
    }
}
