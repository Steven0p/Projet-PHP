<?php
/**
 * Générateur de PDF minimal en PHP pur (aucune dépendance externe).
 * Suffisant pour un document texte structuré sur une page,
 * comme la fiche de soutenance (fonctionnalité bonus).
 */

function pdf_escape_text(string $text): string
{
    $converted = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $text);
    if ($converted !== false) {
        $text = $converted;
    }
    return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
}

/**
 * $sections : liste d'éléments, chacun étant soit
 *   ['heading' => 'Titre de section']
 *   ['label' => 'Champ', 'value' => 'Valeur']
 */
function build_defense_sheet_pdf(string $institution, string $thesis_title, array $sections): string
{
    $y = 740;
    $content = "BT\n/F2 15 Tf\n50 {$y} Td\n(" . pdf_escape_text($institution) . ") Tj\nET\n";
    $y -= 20;
    $content .= "BT\n/F1 11 Tf\n50 {$y} Td\n(Fiche de soutenance) Tj\nET\n";
    $y -= 12;
    $content .= "0.13 0.23 0.37 RG 1 w 50 {$y} m 562 {$y} l S\n0 0 0 RG\n";
    $y -= 25;

    $content .= "BT\n/F2 13 Tf\n50 {$y} Td\n(" . pdf_escape_text($thesis_title) . ") Tj\nET\n";
    $y -= 28;

    foreach ($sections as $section) {
        if ($y < 60) {
            break; // document d'une page : on s'arrête proprement si le contenu déborde
        }
        if (isset($section['heading'])) {
            $y -= 6;
            $content .= "BT\n/F2 11 Tf\n50 {$y} Td\n(" . pdf_escape_text($section['heading']) . ") Tj\nET\n";
            $y -= 18;
            continue;
        }
        $label = (string) ($section['label'] ?? '');
        $value = (string) ($section['value'] ?? '');
        $content .= "BT\n/F1 10 Tf\n60 {$y} Td\n(" . pdf_escape_text($label . ' : ' . $value) . ") Tj\nET\n";
        $y -= 17;
    }

    $y = max($y - 20, 40);
    $footer = 'Document genere automatiquement par USFAH Soutenance Manager le ' . date('d/m/Y a H:i');
    $content .= "BT\n/F3 8 Tf\n50 {$y} Td\n(" . pdf_escape_text($footer) . ") Tj\nET\n";

    return assemble_simple_pdf($content);
}

function assemble_simple_pdf(string $page_content): string
{
    $objects = [];
    $objects[1] = "<< /Type /Catalog /Pages 2 0 R >>";
    $objects[2] = "<< /Type /Pages /Kids [3 0 R] /Count 1 >>";
    $objects[3] = "<< /Type /Page /Parent 2 0 R /Resources << /Font << /F1 4 0 R /F2 5 0 R /F3 6 0 R >> >> /MediaBox [0 0 612 792] /Contents 7 0 R >>";
    $objects[4] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>";
    $objects[5] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>";
    $objects[6] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Oblique /Encoding /WinAnsiEncoding >>";
    $objects[7] = "<< /Length " . strlen($page_content) . " >>\nstream\n" . $page_content . "endstream";

    $pdf = "%PDF-1.4\n";
    $offsets = [];
    foreach ($objects as $num => $body) {
        $offsets[$num] = strlen($pdf);
        $pdf .= "{$num} 0 obj\n{$body}\nendobj\n";
    }

    $xref_offset = strlen($pdf);
    $count = count($objects) + 1;
    $pdf .= "xref\n0 {$count}\n0000000000 65535 f \n";
    for ($i = 1; $i < $count; $i++) {
        $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
    }
    $pdf .= "trailer\n<< /Size {$count} /Root 1 0 R >>\nstartxref\n{$xref_offset}\n%%EOF";

    return $pdf;
}
