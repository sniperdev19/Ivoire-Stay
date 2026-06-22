<?php

namespace Services;

use PHPMailer\PHPMailer\PHPMailer;

class MailService
{
    // ─── Transport SMTP ───────────────────────────────────────────────────────
    private static function mailer(): PHPMailer
    {
        $m = new PHPMailer(true);
        $m->isSMTP();
        $m->Host       = MAIL_HOST;
        $m->SMTPAuth   = true;
        $m->Username   = MAIL_USER;
        $m->Password   = MAIL_PASS;
        $m->SMTPSecure = (int)MAIL_PORT === 465 ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        $m->Port       = (int) MAIL_PORT;
        $m->CharSet    = 'UTF-8';
        $m->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        if (APP_ENV === 'development') {
            $m->SMTPOptions = ['ssl' => ['verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true]];
        }
        return $m;
    }

    public static function send(string $to, string $toName, string $subject, string $html): void
    {
        try {
            $m = self::mailer();
            $m->addAddress($to, $toName);
            $m->isHTML(true);
            $m->Subject = $subject;
            $m->Body    = $html;
            $m->AltBody = strip_tags(str_replace(['<br>', '<br/>', '</p>', '</tr>'], "\n", $html));
            $m->send();
        } catch (\Exception $e) {
            error_log('[MailService] Échec envoi à ' . $to . ' — ' . $e->getMessage());
        }
    }

    // ─── Layout HTML commun ───────────────────────────────────────────────────
    private static function layout(string $content, string $preheader = ''): string
    {
        $appUrl  = APP_URL;
        $appName = MAIL_FROM_NAME;
        $year    = date('Y');
        $pre     = $preheader ? "<span style='display:none;max-height:0;overflow:hidden;'>{$preheader}&nbsp;&zwnj;</span>" : '';

        return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
</head>
<body style="margin:0;padding:0;background:#F5F0E8;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;">
{$pre}
<table width="100%" cellpadding="0" cellspacing="0" style="background:#F5F0E8;padding:40px 16px;">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.07);">

  <!-- Header -->
  <tr><td style="background:#1B4332;padding:28px 40px;">
    <table width="100%" cellpadding="0" cellspacing="0"><tr>
      <td><span style="font-size:20px;font-weight:700;color:#C9A84C;letter-spacing:0.04em;">{$appName}</span></td>
      <td align="right"><span style="font-size:10px;color:rgba(255,255,255,0.35);letter-spacing:0.15em;text-transform:uppercase;">Hôtellerie · Côte d'Ivoire</span></td>
    </tr></table>
  </td></tr>

  <!-- Body -->
  <tr><td style="padding:40px 40px 32px;">{$content}</td></tr>

  <!-- Footer -->
  <tr><td style="background:#F9FAFB;padding:20px 40px;border-top:1px solid #E5E7EB;">
    <p style="margin:0;font-size:11px;color:#9CA3AF;line-height:1.8;text-align:center;">
      © {$year} {$appName} — Tous droits réservés.<br>
      <a href="{$appUrl}" style="color:#C9A84C;text-decoration:none;">{$appUrl}</a>
    </p>
  </td></tr>

</table>
</td></tr>
</table>
</body>
</html>
HTML;
    }

    // ─── Composants ───────────────────────────────────────────────────────────
    private static function btn(string $url, string $label): string
    {
        return "<table cellpadding='0' cellspacing='0' style='margin:24px 0;'><tr>"
            . "<td style='background:#1B4332;border-radius:8px;'>"
            . "<a href='{$url}' style='display:inline-block;padding:14px 28px;font-size:14px;font-weight:600;color:#ffffff;text-decoration:none;'>{$label}</a>"
            . "</td></tr></table>";
    }

    private static function infoRow(string $label, string $value): string
    {
        return "<tr>"
            . "<td style='padding:10px 0;border-bottom:1px solid #F3F4F6;font-size:13px;color:#6B7280;width:45%;'>{$label}</td>"
            . "<td style='padding:10px 0;border-bottom:1px solid #F3F4F6;font-size:13px;color:#111827;font-weight:600;'>{$value}</td>"
            . "</tr>";
    }

    private static function infoTable(array $rows): string
    {
        $html = "<table width='100%' cellpadding='0' cellspacing='0' style='border:1px solid #E5E7EB;border-radius:12px;overflow:hidden;margin:20px 0 24px;'>"
              . "<tr><td style='padding:4px 20px 12px;'><table width='100%' cellpadding='0' cellspacing='0'>";
        foreach ($rows as [$label, $value]) {
            $html .= self::infoRow($label, $value);
        }
        return $html . "</table></td></tr></table>";
    }

    private static function fmtDate(string $date): string
    {
        if (!$date) return '—';
        $ts = strtotime($date);
        if (!$ts) return $date;
        $months = ['jan.','fév.','mars','avr.','mai','juin','juil.','août','sept.','oct.','nov.','déc.'];
        return date('d', $ts) . ' ' . $months[(int)date('m', $ts) - 1] . ' ' . date('Y', $ts);
    }

    private static function fmtPrice(float $amount): string
    {
        return number_format($amount, 0, ',', ' ') . ' FCFA';
    }

    // =========================================================================
    // 1. BIENVENUE — inscription SaaS
    // =========================================================================
    public static function welcome(string $to, string $name, string $estabName = ''): void
    {
        $appUrl   = APP_URL;
        $estabLine = $estabName
            ? "<p style='margin:4px 0 0;font-size:14px;color:#6B7280;'>Établissement : <strong style='color:#1B4332;'>" . htmlspecialchars($estabName) . "</strong></p>"
            : '';

        $content = "<h1 style='margin:0 0 4px;font-family:Georgia,serif;font-size:28px;font-weight:400;color:#1B4332;line-height:1.2;'>"
            . "Bienvenue, <em style='color:#C9A84C;font-style:italic;'>" . htmlspecialchars($name) . " !</em></h1>"
            . $estabLine
            . "<div style='width:40px;height:2px;background:#C9A84C;margin:20px 0;'></div>"
            . "<p style='margin:0 0 16px;font-size:15px;color:#374151;line-height:1.7;'>Votre compte <strong>Ivoire Stay</strong> est prêt. "
            . "Gérez vos réservations, chambres et finances depuis votre tableau de bord.</p>"
            . "<table width='100%' cellpadding='0' cellspacing='0' style='background:#F0FDF4;border-radius:12px;padding:16px 20px;margin-bottom:24px;'>"
            . "<tr><td style='padding:6px 0;font-size:13px;color:#166534;'>✓ &nbsp;Tableau de bord en temps réel</td></tr>"
            . "<tr><td style='padding:6px 0;font-size:13px;color:#166534;'>✓ &nbsp;Gestion des chambres et réservations</td></tr>"
            . "<tr><td style='padding:6px 0;font-size:13px;color:#166534;'>✓ &nbsp;Facturation automatique</td></tr>"
            . "<tr><td style='padding:6px 0;font-size:13px;color:#166534;'>✓ &nbsp;Rapports financiers et statistiques</td></tr>"
            . "</table>"
            . "<p style='margin:0;font-size:13px;color:#9CA3AF;line-height:1.6;'>Connectez-vous via l'application installée sur votre appareil. "
            . "Des questions ? Répondez à cet email, nous sommes là.</p>";

        self::send($to, $name, 'Bienvenue sur Ivoire Stay — Votre compte est prêt ✓', self::layout($content, 'Votre compte Ivoire Stay est prêt'));
    }

    // =========================================================================
    // 2. CONFIRMATION RÉSERVATION — paiement sur place
    // =========================================================================
    public static function bookingConfirmation(array $booking): void
    {
        $to   = $booking['client_email'] ?? '';
        $name = trim(($booking['first_name'] ?? '') . ' ' . ($booking['last_name'] ?? ''))
              ?: ($booking['client_name'] ?? 'Client');
        if (!$to) return;

        $ref      = (string)($booking['booking_id'] ?? '—');
        $hotel    = htmlspecialchars($booking['establishment_name'] ?? $booking['hotel_name'] ?? '—');
        $room     = $booking['room_number'] ?? '—';
        $type     = ['nuit' => 'Nuit', 'weekend' => 'Week-end', 'passage' => 'Passage horaire'][$booking['booking_type'] ?? 'nuit'] ?? 'Nuit';
        $checkIn  = self::fmtDate($booking['check_in'] ?? '');
        $total    = self::fmtPrice((float)($booking['total_amount'] ?? 0));

        $rows = [
            ['Hôtel / Résidence', $hotel],
            ['Chambre', 'N° ' . $room],
            ['Type de séjour', $type],
            ['Arrivée', $checkIn],
        ];
        if (($booking['booking_type'] ?? '') === 'passage') {
            $rows[] = ['Durée', ($booking['hours'] ?? '—') . ' heure(s)'];
        } else {
            $rows[] = ['Départ', self::fmtDate($booking['check_out'] ?? '')];
        }
        $rows[] = ['Montant total', $total];
        $rows[] = ['Paiement', 'À régler à l\'arrivée'];

        $badge = "<span style='display:inline-block;background:#FEF3C7;color:#92400E;font-size:10px;font-weight:700;padding:4px 12px;border-radius:999px;letter-spacing:0.08em;text-transform:uppercase;'>Paiement sur place</span>";

        $refBlock = "<table width='100%' cellpadding='0' cellspacing='0' style='background:#1B4332;border-radius:10px;margin:16px 0 4px;'>"
            . "<tr><td style='padding:14px 20px;'>"
            . "<span style='font-size:12px;color:rgba(255,255,255,0.5);'>Référence réservation</span>"
            . "<span style='float:right;font-size:18px;font-weight:700;color:#C9A84C;letter-spacing:0.06em;'>#{$ref}</span>"
            . "</td></tr></table>";

        $content = "<h1 style='margin:0 0 6px;font-family:Georgia,serif;font-size:26px;font-weight:400;color:#1B4332;'>Réservation <em style='color:#C9A84C;font-style:italic;'>enregistrée</em></h1>"
            . "<p style='margin:0 0 16px;font-size:14px;color:#6B7280;'>Bonjour " . htmlspecialchars($name) . ", votre demande de réservation à <strong>{$hotel}</strong> a bien été reçue.</p>"
            . $badge . $refBlock
            . self::infoTable($rows)
            . "<p style='margin:0;font-size:13px;color:#6B7280;line-height:1.6;'>Présentez-vous à la réception avec votre référence. Pour toute modification, contactez directement l'établissement.</p>";

        self::send($to, $name, "Réservation #{$ref} — {$hotel}", self::layout($content, "Votre réservation #{$ref} est confirmée"));
    }

    // =========================================================================
    // 3. RÉSERVATION PAYÉE EN LIGNE
    // =========================================================================
    public static function bookingPaid(array $booking): void
    {
        $to   = $booking['client_email'] ?? '';
        $name = $booking['client_name'] ?? trim(($booking['first_name'] ?? '') . ' ' . ($booking['last_name'] ?? '')) ?: 'Client';
        if (!$to) return;

        $ref     = $booking['pay_reference'] ?? (string)($booking['id'] ?? '—');
        $hotel   = htmlspecialchars($booking['establishment_name'] ?? '—');
        $room    = $booking['room_number'] ?? '—';
        $type    = ['nuit' => 'Nuit', 'weekend' => 'Week-end', 'passage' => 'Passage horaire'][$booking['booking_type'] ?? 'nuit'] ?? 'Nuit';
        $checkIn = self::fmtDate($booking['check_in'] ?? '');
        $total   = self::fmtPrice((float)($booking['total_amount'] ?? 0));

        $rows = [
            ['Hôtel / Résidence', $hotel],
            ['Chambre', 'N° ' . $room],
            ['Type de séjour', $type],
            ['Arrivée', $checkIn],
        ];
        if (($booking['booking_type'] ?? '') === 'passage') {
            $rows[] = ['Durée', ($booking['hours'] ?? '—') . ' heure(s)'];
        } else {
            $rows[] = ['Départ', self::fmtDate($booking['check_out'] ?? '')];
        }
        $rows[] = ['Montant payé', $total];
        $rows[] = ['Paiement', '✓ Confirmé en ligne'];

        $badge = "<span style='display:inline-block;background:#DCFCE7;color:#166534;font-size:10px;font-weight:700;padding:4px 12px;border-radius:999px;letter-spacing:0.08em;text-transform:uppercase;'>✓ Paiement confirmé</span>";

        $refBlock = "<table width='100%' cellpadding='0' cellspacing='0' style='background:#1B4332;border-radius:10px;margin:16px 0 4px;'>"
            . "<tr><td style='padding:14px 20px;'>"
            . "<span style='font-size:12px;color:rgba(255,255,255,0.5);'>Référence de paiement</span>"
            . "<span style='float:right;font-size:16px;font-weight:700;color:#C9A84C;letter-spacing:0.04em;'>{$ref}</span>"
            . "</td></tr></table>";

        $content = "<h1 style='margin:0 0 6px;font-family:Georgia,serif;font-size:26px;font-weight:400;color:#1B4332;'>Paiement <em style='color:#C9A84C;font-style:italic;'>reçu</em></h1>"
            . "<p style='margin:0 0 16px;font-size:14px;color:#6B7280;'>Bonjour " . htmlspecialchars($name) . ", votre paiement a été reçu. Votre séjour à <strong>{$hotel}</strong> est <strong>confirmé et garanti</strong>.</p>"
            . $badge . $refBlock
            . self::infoTable($rows)
            . "<p style='margin:0;font-size:13px;color:#6B7280;line-height:1.6;'>Conservez cet email comme justificatif de paiement. Présentez-vous directement à la réception le jour de votre arrivée.</p>";

        self::send($to, $name, "✓ Paiement reçu — Séjour à {$hotel}", self::layout($content, "Paiement reçu pour votre séjour à {$hotel}"));
    }

    // =========================================================================
    // 4. ACTIVATION ABONNEMENT SaaS
    // =========================================================================
    public static function subscriptionActivated(string $to, string $name, string $plan, string $expiresAt): void
    {
        $planLabel = ucfirst($plan);
        $expires   = self::fmtDate($expiresAt);
        $dashUrl   = rtrim(APP_URL, '/') . '/saas/settings';

        $rows = [
            ['Plan activé', $planLabel],
            ['Valide jusqu\'au', $expires],
            ['Statut', '✓ Actif'],
        ];

        $content = "<h1 style='margin:0 0 8px;font-family:Georgia,serif;font-size:26px;font-weight:400;color:#1B4332;'>"
            . "Abonnement <em style='color:#C9A84C;font-style:italic;'>{$planLabel}</em> activé</h1>"
            . "<p style='margin:0 0 24px;font-size:14px;color:#6B7280;'>Bonjour " . htmlspecialchars($name) . ", votre abonnement est maintenant actif. Profitez de toutes les fonctionnalités.</p>"
            . self::infoTable($rows)
            . "<p style='margin:0 0 8px;font-size:15px;color:#374151;line-height:1.7;'>Toutes les fonctionnalités du plan <strong>{$planLabel}</strong> sont disponibles immédiatement dans votre tableau de bord.</p>"
            . self::btn($dashUrl, 'Accéder au tableau de bord →');

        self::send($to, $name, "Abonnement {$planLabel} activé — Ivoire Stay", self::layout($content, "Votre abonnement {$planLabel} est actif"));
    }
}
