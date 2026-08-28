<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/pdf.php';
require_login();

$id = (int) get_param('id', 0);
$pdf_content = build_result_proces_verbal($pdo, $id);

if ($pdf_content === null) {
    flash('danger', 'Résultat introuvable.');
    redirect('/results/index.php');
}

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="proces-verbal-' . $id . '.pdf"');
header('Content-Length: ' . strlen($pdf_content));
echo $pdf_content;
exit;
