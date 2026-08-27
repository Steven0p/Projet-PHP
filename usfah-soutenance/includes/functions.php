<?php
/** Échappe une valeur pour affichage HTML sûr. */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function get_flashes(): array
{
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flashes;
}

function redirect(string $path): void
{
    header('Location: ' . BASE_URL . $path);
    exit;
}

function old(array $data, string $key, string $default = ''): string
{
    return e($data[$key] ?? $default);
}

function post(string $key, $default = null)
{
    return $_POST[$key] ?? $default;
}

function get_param(string $key, $default = null)
{
    return $_GET[$key] ?? $default;
}

/**
 * Pagination simple : calcule LIMIT/OFFSET et les métadonnées de page
 * à partir du nombre total de lignes et du paramètre GET "page".
 */
function paginate(int $total_rows, int $per_page = 10): array
{
    $page = max(1, (int) get_param('page', 1));
    $total_pages = max(1, (int) ceil($total_rows / $per_page));
    $page = min($page, $total_pages);
    $offset = ($page - 1) * $per_page;

    return [
        'page'        => $page,
        'per_page'    => $per_page,
        'offset'      => $offset,
        'total_rows'  => $total_rows,
        'total_pages' => $total_pages,
    ];
}

function pagination_links(array $pagination, array $query_params = []): string
{
    if ($pagination['total_pages'] <= 1) {
        return '';
    }

    $html = '<nav aria-label="Pagination"><ul class="pagination">';
    for ($p = 1; $p <= $pagination['total_pages']; $p++) {
        $params = array_merge($query_params, ['page' => $p]);
        $url = '?' . http_build_query($params);
        $active = $p === $pagination['page'] ? ' active' : '';
        $html .= '<li class="page-item' . $active . '"><a class="page-link" href="' . e($url) . '">' . $p . '</a></li>';
    }
    $html .= '</ul></nav>';

    return $html;
}

function format_date(?string $date): string
{
    if (empty($date)) {
        return '—';
    }
    $ts = strtotime($date);
    return $ts ? date('d/m/Y', $ts) : '—';
}

function status_badge(string $statut): string
{
    $map = [
        'actif' => 'success', 'inactif' => 'secondary',
        'en_preparation' => 'secondary', 'soumis' => 'info', 'valide' => 'primary',
        'a_corriger' => 'warning', 'autorise_a_soutenir' => 'info', 'soutenu' => 'success',
        'programmee' => 'primary', 'reportee' => 'warning', 'realisee' => 'success', 'annulee' => 'danger',
        'a_faire' => 'secondary', 'en_cours' => 'info', 'soumise' => 'warning', 'validee' => 'success',
        'admis' => 'success', 'admis_avec_corrections' => 'warning', 'ajourne' => 'danger',
        'disponible' => 'success', 'indisponible' => 'secondary',
    ];
    $color = $map[$statut] ?? 'secondary';
    $label = ucwords(str_replace('_', ' ', $statut));
    return '<span class="badge text-bg-' . $color . '">' . e($label) . '</span>';
}
