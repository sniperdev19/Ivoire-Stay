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
            . "<p style='margin:0 0 16px;font-size:15px;color:#374151;line-height:1.7;'>Votre compte <strong>Afristay</strong> est prêt. "
            . "Gérez vos réservations, chambres et finances depuis votre tableau de bord.</p>"
            . "<table width='100%' cellpadding='0' cellspacing='0' style='background:#F0FDF4;border-radius:12px;padding:16px 20px;margin-bottom:24px;'>"
            . "<tr><td style='padding:6px 0;font-size:13px;color:#166534;'>✓ &nbsp;Tableau de bord en temps réel</td></tr>"
            . "<tr><td style='padding:6px 0;font-size:13px;color:#166534;'>✓ &nbsp;Gestion des chambres et réservations</td></tr>"
            . "<tr><td style='padding:6px 0;font-size:13px;color:#166534;'>✓ &nbsp;Facturation automatique</td></tr>"
            . "<tr><td style='padding:6px 0;font-size:13px;color:#166534;'>✓ &nbsp;Rapports financiers et statistiques</td></tr>"
            . "</table>"
            . "<p style='margin:0;font-size:13px;color:#9CA3AF;line-height:1.6;'>Connectez-vous via l'application installée sur votre appareil. "
            . "Des questions ? Répondez à cet email, nous sommes là.</p>";

        self::send($to, $name, 'Bienvenue sur Afristay — Votre compte est prêt ✓', self::layout($content, 'Votre compte Afristay est prêt'));
    }

    // =========================================================================
    // 1b. RÉINITIALISATION MOT DE PASSE
    // =========================================================================
    public static function passwordReset(string $to, string $name, string $resetUrl): void
    {
        $content = "<h1 style='margin:0 0 4px;font-family:Georgia,serif;font-size:26px;font-weight:400;color:#1B4332;line-height:1.2;'>"
            . "Réinitialisation du <em style='color:#C9A84C;font-style:italic;'>mot de passe</em></h1>"
            . "<div style='width:40px;height:2px;background:#C9A84C;margin:20px 0;'></div>"
            . "<p style='margin:0 0 8px;font-size:15px;color:#374151;line-height:1.7;'>Bonjour " . htmlspecialchars($name) . ", une demande de réinitialisation de mot de passe a été effectuée pour votre compte Afristay.</p>"
            . "<p style='margin:0 0 8px;font-size:14px;color:#6B7280;line-height:1.7;'>Cliquez sur le bouton ci-dessous pour choisir un nouveau mot de passe. Ce lien expire dans <strong>1 heure</strong>.</p>"
            . self::btn($resetUrl, 'Réinitialiser mon mot de passe →')
            . "<p style='margin:16px 0 0;font-size:13px;color:#9CA3AF;line-height:1.6;'>Si vous n'êtes pas à l'origine de cette demande, ignorez simplement cet email : votre mot de passe restera inchangé.</p>";

        self::send($to, $name, 'Réinitialisation de votre mot de passe — Afristay', self::layout($content, 'Réinitialisez votre mot de passe Afristay'));
    }

    // =========================================================================
    // 1c. VÉRIFICATION D'EMAIL — inscription
    // =========================================================================
    public static function verifyEmail(string $to, string $name, string $verifyUrl): void
    {
        $content = "<h1 style='margin:0 0 4px;font-family:Georgia,serif;font-size:26px;font-weight:400;color:#1B4332;line-height:1.2;'>"
            . "Confirmez votre <em style='color:#C9A84C;font-style:italic;'>adresse email</em></h1>"
            . "<div style='width:40px;height:2px;background:#C9A84C;margin:20px 0;'></div>"
            . "<p style='margin:0 0 8px;font-size:15px;color:#374151;line-height:1.7;'>Bonjour " . htmlspecialchars($name) . ", merci de confirmer votre adresse email pour finaliser votre inscription sur Afristay.</p>"
            . "<p style='margin:0 0 8px;font-size:14px;color:#6B7280;line-height:1.7;'>Cliquez sur le bouton ci-dessous. Ce lien expire dans <strong>24 heures</strong>.</p>"
            . self::btn($verifyUrl, 'Confirmer mon email →')
            . "<p style='margin:16px 0 0;font-size:13px;color:#9CA3AF;line-height:1.6;'>Si vous n'êtes pas à l'origine de cette inscription, ignorez simplement cet email.</p>";

        self::send($to, $name, 'Confirmez votre email — Afristay', self::layout($content, 'Confirmez votre adresse email Afristay'));
    }

    // =========================================================================
    // 1d. INVITATION ÉQUIPE — nouveau réceptionniste
    // =========================================================================
    public static function teamInvitation(string $to, string $estabName, string $inviteUrl): void
    {
        $estab = htmlspecialchars($estabName);
        $content = "<h1 style='margin:0 0 4px;font-family:Georgia,serif;font-size:26px;font-weight:400;color:#1B4332;line-height:1.2;'>"
            . "Rejoignez <em style='color:#C9A84C;font-style:italic;'>" . $estab . "</em></h1>"
            . "<div style='width:40px;height:2px;background:#C9A84C;margin:20px 0;'></div>"
            . "<p style='margin:0 0 8px;font-size:15px;color:#374151;line-height:1.7;'>Vous avez été invité(e) à rejoindre l'espace hôtelier de <strong>" . $estab . "</strong> sur Afristay, en tant que réceptionniste.</p>"
            . "<p style='margin:0 0 8px;font-size:14px;color:#6B7280;line-height:1.7;'>Cliquez sur le bouton ci-dessous pour choisir votre nom et votre mot de passe. Ce lien expire dans <strong>24 heures</strong>.</p>"
            . self::btn($inviteUrl, "Accepter l'invitation →")
            . "<p style='margin:16px 0 0;font-size:13px;color:#9CA3AF;line-height:1.6;'>Si vous ne vous attendiez pas à cette invitation, ignorez simplement cet email.</p>";

        self::send($to, $estabName, "Invitation à rejoindre $estabName sur Afristay", self::layout($content, "Invitation à rejoindre $estabName sur Afristay"));
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

        self::send($to, $name, "Réservation #{$ref} — {$hotel}", self::layout($content, "Votre demande de réservation #{$ref} a bien été reçue"));
    }

    // =========================================================================
    // 2b. RAPPEL DE SÉJOUR — veille de l'arrivée
    // =========================================================================
    public static function stayReminder(array $booking): void
    {
        $to   = $booking['client_email'] ?? '';
        $name = $booking['client_name'] ?? trim(($booking['first_name'] ?? '') . ' ' . ($booking['last_name'] ?? '')) ?: 'Client';
        if (!$to) return;

        $hotel   = htmlspecialchars($booking['establishment_name'] ?? '—');
        $room    = $booking['room_number'] ?? '—';
        $checkIn = self::fmtDate($booking['check_in'] ?? '');

        $rows = [
            ['Hôtel / Résidence', $hotel],
            ['Chambre', 'N° ' . $room],
            ['Arrivée', $checkIn],
        ];

        $content = "<h1 style='margin:0 0 6px;font-family:Georgia,serif;font-size:26px;font-weight:400;color:#1B4332;'>Votre séjour <em style='color:#C9A84C;font-style:italic;'>approche</em></h1>"
            . "<p style='margin:0 0 16px;font-size:14px;color:#6B7280;'>Bonjour " . htmlspecialchars($name) . ", petit rappel : votre arrivée à <strong>{$hotel}</strong> est prévue demain.</p>"
            . self::infoTable($rows)
            . "<p style='margin:0;font-size:13px;color:#6B7280;line-height:1.6;'>À très bientôt !</p>";

        self::send($to, $name, "Rappel — votre séjour à {$hotel} approche", self::layout($content, "Votre arrivée à {$hotel} est prévue demain"));
    }

    // =========================================================================
    // 2c. RÉSERVATION ANNULÉE (voyageur)
    // =========================================================================
    public static function bookingCancelledGuest(array $booking): void
    {
        $to   = $booking['client_email'] ?? '';
        $name = $booking['client_name'] ?? trim(($booking['first_name'] ?? '') . ' ' . ($booking['last_name'] ?? '')) ?: 'Client';
        if (!$to) return;

        $hotel = htmlspecialchars($booking['establishment_name'] ?? '—');
        $room  = $booking['room_number'] ?? '—';

        $rows = [
            ['Hôtel / Résidence', $hotel],
            ['Chambre', 'N° ' . $room],
            ['Arrivée', self::fmtDate($booking['check_in'] ?? '')],
        ];

        $content = "<h1 style='margin:0 0 6px;font-family:Georgia,serif;font-size:26px;font-weight:400;color:#1B4332;'>Réservation <em style='color:#C9A84C;font-style:italic;'>annulée</em></h1>"
            . "<p style='margin:0 0 16px;font-size:14px;color:#6B7280;'>Bonjour " . htmlspecialchars($name) . ", votre réservation à <strong>{$hotel}</strong> a bien été annulée.</p>"
            . self::infoTable($rows)
            . "<p style='margin:0;font-size:13px;color:#6B7280;line-height:1.6;'>Pour toute question, contactez directement l'établissement.</p>";

        self::send($to, $name, "Réservation annulée — {$hotel}", self::layout($content, "Votre réservation à {$hotel} a été annulée"));
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

        self::send($to, $name, "Abonnement {$planLabel} activé — Afristay", self::layout($content, "Votre abonnement {$planLabel} est actif"));
    }

    // =========================================================================
    // 4b. ANNULATION / RÉTROGRADATION ABONNEMENT SaaS
    // =========================================================================
    public static function subscriptionCancelled(string $to, string $name, string $fromPlan, string $toPlan): void
    {
        $isFullCancel = strtolower($toPlan) === 'gratuit' || strtolower($toPlan) === 'starter';
        $settingsUrl  = rtrim(APP_URL, '/') . '/saas/settings';

        $rows = [
            ['Ancien plan', $fromPlan],
            ['Nouveau plan', $toPlan],
        ];

        $title = $isFullCancel
            ? "Abonnement <em style='color:#C9A84C;font-style:italic;'>annulé</em>"
            : "Abonnement <em style='color:#C9A84C;font-style:italic;'>rétrogradé</em>";

        $desc = $isFullCancel
            ? "Bonjour " . htmlspecialchars($name) . ", votre abonnement <strong>{$fromPlan}</strong> a bien été annulé. Vous êtes repassé au plan <strong>{$toPlan}</strong> avec effet immédiat."
            : "Bonjour " . htmlspecialchars($name) . ", votre abonnement est passé de <strong>{$fromPlan}</strong> à <strong>{$toPlan}</strong> avec effet immédiat.";

        $content = "<h1 style='margin:0 0 8px;font-family:Georgia,serif;font-size:26px;font-weight:400;color:#1B4332;'>{$title}</h1>"
            . "<p style='margin:0 0 24px;font-size:14px;color:#6B7280;'>{$desc}</p>"
            . self::infoTable($rows)
            . "<p style='margin:0 0 8px;font-size:13px;color:#9CA3AF;line-height:1.6;'>Vous pouvez remettre à niveau votre abonnement à tout moment depuis vos paramètres.</p>"
            . self::btn($settingsUrl, 'Voir mon abonnement →');

        self::send($to, $name, ($isFullCancel ? 'Abonnement annulé' : 'Abonnement rétrogradé') . ' — Afristay', self::layout($content));
    }

    // =========================================================================
    // 4c. RAPPEL D'EXPIRATION ABONNEMENT SaaS
    // =========================================================================
    public static function subscriptionExpiringSoon(string $to, string $name, string $planLabel, string $expiresAt, int $daysLeft): void
    {
        $expires   = self::fmtDate($expiresAt);
        $renewUrl  = rtrim(APP_URL, '/') . '/saas/settings';
        $dayWord   = $daysLeft > 1 ? 'jours' : 'jour';

        $rows = [
            ['Plan actuel', $planLabel],
            ['Expire le', $expires],
        ];

        $content = "<h1 style='margin:0 0 8px;font-family:Georgia,serif;font-size:26px;font-weight:400;color:#1B4332;'>"
            . "Votre abonnement expire <em style='color:#C9A84C;font-style:italic;'>bientôt</em></h1>"
            . "<p style='margin:0 0 24px;font-size:14px;color:#6B7280;'>Bonjour " . htmlspecialchars($name) . ", votre abonnement <strong>{$planLabel}</strong> expire dans <strong>{$daysLeft} {$dayWord}</strong>. "
            . "Renouvelez-le pour continuer à profiter de toutes vos fonctionnalités sans interruption.</p>"
            . self::infoTable($rows)
            . "<p style='margin:0 0 8px;font-size:13px;color:#9CA3AF;line-height:1.6;'>Passé cette date, votre établissement repassera automatiquement au plan Starter (fonctionnalités limitées).</p>"
            . self::btn($renewUrl, 'Renouveler mon abonnement →');

        self::send($to, $name, "Votre abonnement expire dans {$daysLeft} {$dayWord} — Afristay", self::layout($content, "Abonnement {$planLabel} expirant le {$expires}"));
    }

    // =========================================================================
    // 4d. ALERTE SUPERADMIN — événements plateforme (nouvel établissement, retrait…)
    // =========================================================================
    public static function superadminAlert(string $to, string $name, string $title, string $message): void
    {
        if (!$to) return;
        $dashUrl = rtrim(APP_URL, '/') . '/admin';

        $content = "<h1 style='margin:0 0 8px;font-family:Georgia,serif;font-size:24px;font-weight:400;color:#1B4332;'>"
            . htmlspecialchars($title) . "</h1>"
            . "<p style='margin:0 0 24px;font-size:14px;color:#374151;line-height:1.7;'>" . htmlspecialchars($message) . "</p>"
            . self::btn($dashUrl, "Voir dans l'admin →");

        self::send($to, $name ?: 'Admin', $title . ' — Afristay Admin', self::layout($content, $title));
    }

    // =========================================================================
    // 5. ENVOI DE FACTURE
    // =========================================================================
    public static function sendContact(array $data): void
    {
        $adminEmail = MAIL_USER ?: MAIL_FROM;
        if (!$adminEmail) return;

        $name    = htmlspecialchars($data['name']    ?? '');
        $email   = htmlspecialchars($data['email']   ?? '');
        $phone   = htmlspecialchars($data['phone']   ?? '—');
        $subject = htmlspecialchars($data['subject'] ?? 'Contact vitrine');
        $message = nl2br(htmlspecialchars($data['message'] ?? ''));

        $content = "<h1 style='margin:0 0 6px;font-family:Georgia,serif;font-size:24px;font-weight:400;color:#1B4332;'>"
            . "Nouveau message — <em style='color:#C9A84C;font-style:italic;'>{$subject}</em></h1>"
            . self::infoTable([
                ['Nom',      $name],
                ['Email',    $email],
                ['Téléphone', $phone],
                ['Sujet',    $subject],
              ])
            . "<div style='background:#F9FAFB;border-radius:10px;padding:16px 20px;margin-top:8px;font-size:14px;color:#374151;line-height:1.7;'>{$message}</div>"
            . "<p style='margin:16px 0 0;font-size:13px;color:#9CA3AF;'>Pour répondre, écrivez directement à : <a href='mailto:{$email}' style='color:#C9A84C;'>{$email}</a></p>";

        self::send($adminEmail, MAIL_FROM_NAME, "Contact Afristay — {$subject}", self::layout($content));
    }

    public static function invoiceMail(array $inv, string $pdfAbsPath): void
    {
        $to       = $inv['client_email'] ?? '';
        $name     = $inv['client_name']  ?? 'Client';
        $hotel    = $inv['establishment_name'] ?? MAIL_FROM_NAME;
        $num      = $inv['invoice_number']  ?? '';
        $ttc      = number_format((float)($inv['amount_ttc'] ?? 0), 0, ',', ' ') . ' FCFA';
        $paid     = (float)($inv['paid_amount'] ?? 0);
        $remaining = max(0, (float)($inv['amount_ttc'] ?? 0) - $paid);
        $status   = $inv['status'] ?? 'draft';

        if (!$to) return;

        $rows = [['Facture N°', $num], ['Établissement', $hotel]];
        if (!empty($inv['check_in'])) {
            $rows[] = ['Arrivée', self::fmtDate($inv['check_in'])];
        }
        if (!empty($inv['check_out']) && $inv['check_out'] !== $inv['check_in']) {
            $rows[] = ['Départ', self::fmtDate($inv['check_out'])];
        }
        $rows[] = ['Montant TTC', $ttc];
        if ($paid > 0) {
            $rows[] = ['Déjà réglé', number_format($paid, 0, ',', ' ') . ' FCFA'];
        }

        $soldeBlock = '';
        if ($status === 'paid' || $remaining <= 0) {
            $soldeBlock = "<div style='background:#DCFCE7;border-radius:8px;padding:14px 18px;margin:16px 0;text-align:center;font-weight:700;color:#166534;font-size:15px;'>✓ Facture intégralement réglée</div>";
        } elseif ($remaining > 0) {
            $r = number_format($remaining, 0, ',', ' ') . ' FCFA';
            $soldeBlock = "<div style='background:#FEF3C7;border-radius:8px;padding:14px 18px;margin:16px 0;text-align:center;color:#92400E;font-size:14px;'>Solde restant à régler : <strong style='font-size:16px;'>{$r}</strong></div>";
        }

        $content = "<h1 style='margin:0 0 6px;font-family:Georgia,serif;font-size:26px;font-weight:400;color:#1B4332;'>"
            . "Votre facture <em style='color:#C9A84C;font-style:italic;'>{$num}</em></h1>"
            . "<p style='margin:0 0 20px;font-size:14px;color:#6B7280;'>Bonjour " . htmlspecialchars($name) . ", veuillez trouver ci-joint votre facture pour votre séjour à <strong>" . htmlspecialchars($hotel) . "</strong>.</p>"
            . self::infoTable($rows)
            . $soldeBlock
            . "<p style='margin:16px 0 0;font-size:13px;color:#9CA3AF;'>La facture est disponible en pièce jointe au format PDF.</p>";

        try {
            $m = self::mailer();
            $m->addAddress($to, $name);
            $m->isHTML(true);
            $m->Subject = "Facture {$num} — " . $hotel;
            $m->Body    = self::layout($content, "Votre facture {$num} pour votre séjour à {$hotel}");
            $m->AltBody = "Facture {$num} — Montant TTC : {$ttc}. Voir pièce jointe.";
            if ($pdfAbsPath && file_exists($pdfAbsPath)) {
                $m->addAttachment($pdfAbsPath, $num . '.pdf');
            }
            $m->send();
        } catch (\Exception $e) {
            error_log('[MailService] invoiceMail échec — ' . $e->getMessage());
            throw $e;
        }
    }

    // =========================================================================
    // NEWSLETTER — campagne envoyée par le superadmin (AdminNewsletterController)
    // =========================================================================
    public static function newsletterCampaign(string $to, string $subject, string $bodyHtml, string $unsubscribeUrl): void
    {
        $content = $bodyHtml
            . "<p style='margin:24px 0 0;font-size:11px;color:#9CA3AF;text-align:center;border-top:1px solid #F3F4F6;padding-top:16px;'>"
            . "Vous recevez cet email car vous êtes inscrit(e) à la newsletter Afristay. "
            . "<a href='{$unsubscribeUrl}' style='color:#9CA3AF;'>Se désabonner</a></p>";

        self::send($to, '', $subject, self::layout($content));
    }
}
