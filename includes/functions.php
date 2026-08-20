<?php

declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function setting(string $key, string $default = ''): string
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        try {
            foreach (db()->query('SELECT kunci, nilai FROM pengaturan') as $row) {
                $cache[$row['kunci']] = (string) $row['nilai'];
            }
        } catch (PDOException) {
            // tabel belum ada (misal sebelum migrasi) — pakai default
        }
    }
    return $cache[$key] ?? $default;
}

function nama_rs(): string
{
    return setting('nama_rs', 'SIMRS');
}

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function base_url(string $path = ''): string
{
    return '/' . ltrim($path, '/');
}

function url(string $page, array $params = []): string
{
    return '/index.php?' . http_build_query(array_merge(['page' => $page], $params));
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

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . csrf_token() . '">';
}

function verify_csrf(): void
{
    $token = $_POST['_csrf'] ?? '';
    if (!hash_equals($_SESSION['csrf'] ?? '', $token)) {
        http_response_code(419);
        exit('Sesi tidak valid atau kedaluwarsa. Silakan muat ulang halaman.');
    }
}

function rupiah(float|int|string $amount): string
{
    return 'Rp ' . number_format((float) $amount, 0, ',', '.');
}

function tgl(?string $datetime, bool $withTime = false): string
{
    if (!$datetime) {
        return '-';
    }
    $bulan = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $ts = strtotime($datetime);
    $out = date('j', $ts) . ' ' . $bulan[(int) date('n', $ts)] . ' ' . date('Y', $ts);
    return $withTime ? $out . ' ' . date('H:i', $ts) : $out;
}

function umur(?string $tanggalLahir): string
{
    if (!$tanggalLahir) {
        return '-';
    }
    $lahir = new DateTime($tanggalLahir);
    return $lahir->diff(new DateTime())->y . ' th';
}

function status_badge(string $status): string
{
    $map = [
        'menunggu'  => ['Menunggu', 'warning'],
        'dipanggil' => ['Dipanggil', 'primary'],
        'diperiksa' => ['Diperiksa', 'info'],
        'selesai'   => ['Selesai', 'success'],
        'batal'     => ['Batal', 'danger'],
        'belum'     => ['Belum Lunas', 'warning'],
        'lunas'     => ['Lunas', 'success'],
    ];
    [$label, $color] = $map[$status] ?? [$status, 'secondary'];
    return '<span class="badge text-bg-' . $color . '">' . e($label) . '</span>';
}

function next_number(string $table, string $column, string $prefix, int $width = 6): string
{
    $like = $prefix . '%';
    $stmt = db()->prepare("SELECT $column FROM $table WHERE $column LIKE ? ORDER BY $column DESC LIMIT 1");
    $stmt->execute([$like]);
    $last = $stmt->fetchColumn();
    $seq = $last ? ((int) substr((string) $last, strlen($prefix))) + 1 : 1;
    return $prefix . str_pad((string) $seq, $width, '0', STR_PAD_LEFT);
}

// Nomor antrian berikutnya untuk poli pada tanggal tertentu, mis. UMU-003
function next_queue_number(int $poliId, string $tanggal): string
{
    $stmt = db()->prepare(
        'SELECT COUNT(*) FROM pendaftaran WHERE poli_id = ? AND DATE(tanggal) = ?'
    );
    $stmt->execute([$poliId, $tanggal]);
    $seq = (int) $stmt->fetchColumn() + 1;

    $kode = db()->prepare('SELECT kode FROM poli WHERE id = ?');
    $kode->execute([$poliId]);
    $prefix = (string) ($kode->fetchColumn() ?: 'POL');

    return $prefix . '-' . str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
}

function paginate(int $total, int $perPage, int $page): array
{
    $pages = max(1, (int) ceil($total / $perPage));
    $page = min(max(1, $page), $pages);
    return ['pages' => $pages, 'page' => $page, 'offset' => ($page - 1) * $perPage, 'per_page' => $perPage];
}

function render_pagination(string $page, array $pg, array $extraParams = []): string
{
    if ($pg['pages'] <= 1) {
        return '';
    }
    $html = '<nav><ul class="pagination pagination-sm mb-0 justify-content-end">';
    for ($i = 1; $i <= $pg['pages']; $i++) {
        $active = $i === $pg['page'] ? ' active' : '';
        $html .= '<li class="page-item' . $active . '"><a class="page-link" href="'
            . e(url($page, array_merge($extraParams, ['p' => $i]))) . '">' . $i . '</a></li>';
    }
    return $html . '</ul></nav>';
}
