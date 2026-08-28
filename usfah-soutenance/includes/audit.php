<?php
/**
 * Journal des activités des administrateurs (fonctionnalité bonus).
 * Enregistre chaque création / modification / suppression, ainsi que
 * les connexions et déconnexions.
 */
function log_activity(string $action, string $entity_type, ?int $entity_id, string $description): void
{
    global $pdo;

    $user_id = $_SESSION['user_id'] ?? null;
    $user_name = $_SESSION['user_name'] ?? 'Système';

    $stmt = $pdo->prepare('
        INSERT INTO activity_log (user_id, user_name, action, entity_type, entity_id, description)
        VALUES (?, ?, ?, ?, ?, ?)
    ');
    $stmt->execute([$user_id, $user_name, $action, $entity_type, $entity_id, $description]);
}
