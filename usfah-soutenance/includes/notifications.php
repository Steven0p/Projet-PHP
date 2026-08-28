<?php
/**
 * Fonctionnalité bonus : notifications par email via l'API Resend
 * (voir config/resend.php) — soutenance programmée et résultat délibéré.
 */

function resend_config(): ?array
{
    $path = __DIR__ . '/../config/resend.php';
    if (!file_exists($path)) {
        return null;
    }
    $config = require $path;
    if (empty($config['enabled']) || empty($config['api_key']) || $config['api_key'] === 'CHANGE_ME') {
        return null;
    }
    return $config;
}

/**
 * Envoie un email via l'API Resend. Retourne true en cas de succès, false sinon
 * (échec silencieux : fonctionnalité bonus, ne doit jamais bloquer l'action principale).
 */
function send_resend_email(string $to, string $subject, string $html, string $text, ?array $attachment = null): bool
{
    $config = resend_config();
    if ($config === null) {
        return false;
    }

    $email = [
        'from' => $config['from'],
        'to' => [$to],
        'subject' => $subject,
        'html' => $html,
        'text' => $text,
    ];

    if ($attachment !== null) {
        $email['attachments'] = [[
            'filename' => $attachment['filename'],
            'content' => base64_encode($attachment['content']),
        ]];
    }

    $payload = json_encode($email);

    $context = stream_context_create([
        'http' => [
            'method'        => 'POST',
            'header'        => "Authorization: Bearer {$config['api_key']}\r\nContent-Type: application/json",
            'content'       => $payload,
            'timeout'       => 10,
            'ignore_errors' => true,
        ],
    ]);

    $response = @file_get_contents('https://api.resend.com/emails', false, $context);
    $status_line = $http_response_header[0] ?? '';
    preg_match('/\s(\d{3})\s/', $status_line, $matches);
    $http_code = isset($matches[1]) ? (int) $matches[1] : 0;

    if ($http_code < 200 || $http_code >= 300) {
        error_log('Resend notification failed (HTTP ' . $http_code . '): ' . $response);
        return false;
    }

    return true;
}

/**
 * $defense doit contenir : titre, date, heure, salle, jury (['president' => 'Nom', ...])
 * $attachment (optionnel) : ['filename' => '...', 'content' => '<octets bruts du PDF>']
 */
function send_defense_notification(string $to, string $student_name, array $defense, ?array $attachment = null): bool
{
    $role_labels = ['president' => 'Président', 'examinateur' => 'Examinateur', 'rapporteur' => 'Rapporteur'];

    $jury_items = '';
    $jury_lines = '';
    foreach ($defense['jury'] as $role => $name) {
        $label = $role_labels[$role] ?? ucfirst($role);
        $jury_items .= '<li>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . ' : ' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</li>';
        $jury_lines .= "- {$label} : {$name}\n";
    }

    $html = '<h2>Votre soutenance a été programmée</h2>'
        . '<p>Bonjour ' . htmlspecialchars($student_name, ENT_QUOTES, 'UTF-8') . ',</p>'
        . '<p>Voici les détails de votre soutenance de mémoire :</p>'
        . '<ul>'
        . '<li><strong>Mémoire :</strong> ' . htmlspecialchars($defense['titre'], ENT_QUOTES, 'UTF-8') . '</li>'
        . '<li><strong>Date :</strong> ' . htmlspecialchars($defense['date'], ENT_QUOTES, 'UTF-8') . '</li>'
        . '<li><strong>Heure :</strong> ' . htmlspecialchars($defense['heure'], ENT_QUOTES, 'UTF-8') . '</li>'
        . '<li><strong>Salle :</strong> ' . htmlspecialchars($defense['salle'], ENT_QUOTES, 'UTF-8') . '</li>'
        . '</ul>'
        . '<p><strong>Composition du jury :</strong></p>'
        . '<ul>' . $jury_items . '</ul>'
        . '<p>Cordialement,<br>USFAH — Mémoire &amp; Soutenance Manager</p>';

    $text = "Votre soutenance a été programmée\n\n"
        . "Bonjour {$student_name},\n\n"
        . "Voici les détails de votre soutenance de mémoire :\n\n"
        . "Mémoire : {$defense['titre']}\n"
        . "Date : {$defense['date']}\n"
        . "Heure : {$defense['heure']}\n"
        . "Salle : {$defense['salle']}\n\n"
        . "Composition du jury :\n"
        . $jury_lines . "\n"
        . "Cordialement,\nUSFAH — Mémoire & Soutenance Manager\n";

    return send_resend_email($to, 'Soutenance programmée — ' . $defense['titre'], $html, $text, $attachment);
}

/**
 * $result doit contenir : titre, decision (admis|admis_avec_corrections|ajourne),
 * note_finale (string|null), mention (string|null), commentaires (string|null)
 */
function send_result_notification(string $to, string $student_name, array $result, ?array $attachment = null): bool
{
    $decision_labels = ['admis' => 'Admis', 'admis_avec_corrections' => 'Admis avec corrections', 'ajourne' => 'Ajourné'];
    $mention_labels = ['passable' => 'Passable', 'assez_bien' => 'Assez bien', 'bien' => 'Bien', 'tres_bien' => 'Très bien', 'excellent' => 'Excellent'];
    $decision_label = $decision_labels[$result['decision']] ?? $result['decision'];
    $mention_label = $result['mention'] ? ($mention_labels[$result['mention']] ?? $result['mention']) : null;

    $intro = $result['decision'] === 'ajourne'
        ? "Nous vous informons du résultat de votre soutenance de mémoire."
        : "Félicitations ! Voici le résultat de votre soutenance de mémoire.";

    $html_rows = '<li><strong>Mémoire :</strong> ' . htmlspecialchars($result['titre'], ENT_QUOTES, 'UTF-8') . '</li>'
        . '<li><strong>Décision :</strong> ' . htmlspecialchars($decision_label, ENT_QUOTES, 'UTF-8') . '</li>';
    $text_rows = "Mémoire : {$result['titre']}\nDécision : {$decision_label}\n";

    if (!empty($result['note_finale'])) {
        $html_rows .= '<li><strong>Note finale :</strong> ' . htmlspecialchars((string) $result['note_finale'], ENT_QUOTES, 'UTF-8') . '</li>';
        $text_rows .= "Note finale : {$result['note_finale']}\n";
    }
    if ($mention_label) {
        $html_rows .= '<li><strong>Mention :</strong> ' . htmlspecialchars($mention_label, ENT_QUOTES, 'UTF-8') . '</li>';
        $text_rows .= "Mention : {$mention_label}\n";
    }

    $html_comment = !empty($result['commentaires'])
        ? '<p><strong>Commentaires du jury :</strong> ' . nl2br(htmlspecialchars($result['commentaires'], ENT_QUOTES, 'UTF-8')) . '</p>'
        : '';
    $text_comment = !empty($result['commentaires']) ? "\nCommentaires du jury : {$result['commentaires']}\n" : '';

    $html = '<h2>Résultat de votre soutenance</h2>'
        . '<p>Bonjour ' . htmlspecialchars($student_name, ENT_QUOTES, 'UTF-8') . ',</p>'
        . '<p>' . htmlspecialchars($intro, ENT_QUOTES, 'UTF-8') . '</p>'
        . '<ul>' . $html_rows . '</ul>'
        . $html_comment
        . '<p>Cordialement,<br>USFAH — Mémoire &amp; Soutenance Manager</p>';

    $text = "Résultat de votre soutenance\n\n"
        . "Bonjour {$student_name},\n\n"
        . "{$intro}\n\n"
        . $text_rows
        . $text_comment
        . "\nCordialement,\nUSFAH — Mémoire & Soutenance Manager\n";

    return send_resend_email($to, 'Résultat de votre soutenance — ' . $result['titre'], $html, $text, $attachment);
}
