<?php
/**
 * Fonctionnalité bonus : notification par email via l'API Resend
 * lorsqu'une soutenance est programmée (voir config/resend.php).
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
 * Envoie un email de notification de soutenance programmée.
 * $defense doit contenir : titre, date, heure, salle, jury (['president' => 'Nom', ...])
 * Retourne true en cas de succès, false sinon (échec silencieux : fonctionnalité bonus,
 * ne doit jamais bloquer la programmation d'une soutenance).
 */
function send_defense_notification(string $to, string $student_name, array $defense): bool
{
    $config = resend_config();
    if ($config === null) {
        return false;
    }

    $jury_items = '';
    $role_labels = ['president' => 'Président', 'examinateur' => 'Examinateur', 'rapporteur' => 'Rapporteur'];
    foreach ($defense['jury'] as $role => $name) {
        $label = $role_labels[$role] ?? ucfirst($role);
        $jury_items .= '<li>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . ' : ' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</li>';
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

    $payload = json_encode([
        'from' => $config['from'],
        'to' => [$to],
        'subject' => 'Soutenance programmée — ' . $defense['titre'],
        'html' => $html,
    ]);

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
