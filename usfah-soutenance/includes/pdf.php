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
 * Rend l'en-tête + les sections communes à tous les documents.
 * $sections : liste d'éléments, chacun étant soit
 *   ['heading' => 'Titre de section']
 *   ['label' => 'Champ', 'value' => 'Valeur']
 * Retourne [contenu du flux PDF jusqu'ici, position Y courante].
 */
function render_document_sections(string $institution, string $document_type, string $thesis_title, array $sections): array
{
    $y = 740;
    $content = "BT\n/F2 15 Tf\n50 {$y} Td\n(" . pdf_escape_text($institution) . ") Tj\nET\n";
    $y -= 20;
    $content .= "BT\n/F1 11 Tf\n50 {$y} Td\n(" . pdf_escape_text($document_type) . ") Tj\nET\n";
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

    return [$content, $y];
}

function build_defense_sheet_pdf(string $institution, string $thesis_title, array $sections): string
{
    [$content, $y] = render_document_sections($institution, 'Fiche de soutenance', $thesis_title, $sections);

    $y = max($y - 20, 40);
    $footer = 'Document genere automatiquement par USFAH Soutenance Manager le ' . date('d/m/Y a H:i');
    $content .= "BT\n/F3 8 Tf\n50 {$y} Td\n(" . pdf_escape_text($footer) . ") Tj\nET\n";

    return assemble_simple_pdf($content);
}

/**
 * $signatories : liste de ['role' => 'President du jury', 'name' => 'Nom Prenom']
 */
function build_proces_verbal_pdf(string $institution, string $thesis_title, array $sections, array $signatories): string
{
    [$content, $y] = render_document_sections($institution, 'Proces-verbal de soutenance', $thesis_title, $sections);

    $y -= 15;
    if ($y < 160) {
        $y = 160;
    }
    $content .= "0.13 0.23 0.37 RG 0.75 w 50 {$y} m 562 {$y} l S\n0 0 0 RG\n";
    $y -= 22;
    $content .= "BT\n/F2 11 Tf\n50 {$y} Td\n(Signatures du jury) Tj\nET\n";
    $y -= 45;

    $count = max(count($signatories), 1);
    $usable_width = 512;
    $col_width = intdiv($usable_width, $count);
    foreach (array_values($signatories) as $i => $signatory) {
        $x_start = 50 + $i * $col_width;
        $x_end = $x_start + $col_width - 25;
        $content .= "0.5 0.5 0.5 RG 0.5 w {$x_start} {$y} m {$x_end} {$y} l S\n0 0 0 RG\n";
        $name_y = $y - 13;
        $role_y = $y - 25;
        $content .= "BT\n/F2 9 Tf\n{$x_start} {$name_y} Td\n(" . pdf_escape_text($signatory['name']) . ") Tj\nET\n";
        $content .= "BT\n/F3 8 Tf\n{$x_start} {$role_y} Td\n(" . pdf_escape_text($signatory['role']) . ") Tj\nET\n";
    }

    $y = max($y - 60, 40);
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
