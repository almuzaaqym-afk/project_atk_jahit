<?php
/*
|--------------------------------------------------------------------------
| HALAMAN LOG AKTIVITAS ADMIN
| Lokasi: admin/log_aktivitas/index.php
|--------------------------------------------------------------------------
*/

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . "/../includes/hak_akses.php";
$adminLogin = cekOwner();
require_once __DIR__ . "/../../config/koneksi.php";

/* FUNGSI BANTU */
function eLog($nilai)
{
    return htmlspecialchars((string) ($nilai ?? ''), ENT_QUOTES, 'UTF-8');
}

function validTanggalLog($tanggal)
{
    if (!is_string($tanggal) || $tanggal === '') {
        return false;
    }

    $hasil = DateTime::createFromFormat('!Y-m-d', $tanggal);

    return $hasil && $hasil->format('Y-m-d') === $tanggal;
}

function urlHalamanLog($nomor, $parameter)
{
    $parameter['halaman'] = $nomor;

    return '?' . http_build_query($parameter);
}

/* FILTER */
$search = trim($_GET['search'] ?? '');
$modul = trim($_GET['modul'] ?? '');
$tanggal_mulai = $_GET['tanggal_mulai'] ?? '';
$tanggal_selesai = $_GET['tanggal_selesai'] ?? '';

if (!validTanggalLog($tanggal_mulai)) {
    $tanggal_mulai = '';
}

if (!validTanggalLog($tanggal_selesai)) {
    $tanggal_selesai = '';
}

if (
    $tanggal_mulai !== '' &&
    $tanggal_selesai !== '' &&
    $tanggal_mulai > $tanggal_selesai
) {
    [$tanggal_mulai, $tanggal_selesai] = [
        $tanggal_selesai,
        $tanggal_mulai
    ];
}

/* DAFTAR MODUL */
$daftar_modul = [];

$query_modul = mysqli_query(
    $conn,
    "SELECT DISTINCT modul
     FROM admin_log_aktivitas
     ORDER BY modul ASC"
);

if ($query_modul) {
    while ($baris_modul = mysqli_fetch_assoc($query_modul)) {
        $daftar_modul[] = $baris_modul['modul'];
    }
}

/* FILTER QUERY */
$where = [];

$search_sql = mysqli_real_escape_string($conn, $search);
$modul_sql = mysqli_real_escape_string($conn, $modul);

if ($search !== '') {
    $where[] = "(
        nama_admin LIKE '%$search_sql%'
        OR modul LIKE '%$search_sql%'
        OR aktivitas LIKE '%$search_sql%'
        OR detail LIKE '%$search_sql%'
        OR ip_address LIKE '%$search_sql%'
    )";
}

if ($modul !== '') {
    $where[] = "modul = '$modul_sql'";
}

if ($tanggal_mulai !== '') {
    $tanggal_mulai_sql = mysqli_real_escape_string(
        $conn,
        $tanggal_mulai
    );

    $where[] = "created_at >= '$tanggal_mulai_sql 00:00:00'";
}

if ($tanggal_selesai !== '') {
    $tanggal_selesai_sql = mysqli_real_escape_string(
        $conn,
        $tanggal_selesai
    );

    $where[] = "created_at < DATE_ADD(
        '$tanggal_selesai_sql',
        INTERVAL 1 DAY
    )";
}

$where_sql = !empty($where)
    ? "WHERE " . implode(" AND ", $where)
    : "";

/* PAGINATION */
$per_halaman = 20;
$halaman = max(1, (int) ($_GET['halaman'] ?? 1));

$query_total = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM admin_log_aktivitas
     $where_sql"
);

$total_data = 0;

if ($query_total) {
    $data_total = mysqli_fetch_assoc($query_total);
    $total_data = (int) ($data_total['total'] ?? 0);
}

$total_halaman = max(1, (int) ceil($total_data / $per_halaman));
$halaman = min($halaman, $total_halaman);
$offset = ($halaman - 1) * $per_halaman;

/* DATA AKTIVITAS */
$query_log = mysqli_query(
    $conn,
    "SELECT
        id_log,
        id_admin,
        nama_admin,
        modul,
        aktivitas,
        detail,
        ip_address,
        created_at
     FROM admin_log_aktivitas
     $where_sql
     ORDER BY created_at DESC, id_log DESC
     LIMIT $per_halaman OFFSET $offset"
);

$data_log = [];

if ($query_log) {
    while ($row = mysqli_fetch_assoc($query_log)) {
        $data_log[] = $row;
    }
}

$nomor_awal = $total_data > 0 ? $offset + 1 : 0;
$nomor_akhir = min($offset + $per_halaman, $total_data);

$parameter_url = [
    'search' => $search,
    'modul' => $modul,
    'tanggal_mulai' => $tanggal_mulai,
    'tanggal_selesai' => $tanggal_selesai
];

/* =========================================================
   RESPON AJAX UNTUK AUTO-REFRESH
   Bagian ini mengirim data saja, tanpa merender ulang halaman.
========================================================= */
if (($_GET['ajax'] ?? '') === '1') {
    $ajax_rows = [];

    foreach ($data_log as $log) {
        $ajax_rows[] = [
            'waktu' => date(
                'd-m-Y H:i:s',
                strtotime($log['created_at'])
            ),
            'admin' => $log['nama_admin']
                ?: 'Admin #' . ($log['id_admin'] ?? '-'),
            'modul' => ucwords(
                str_replace('_', ' ', $log['modul'])
            ),
            'aktivitas' => $log['aktivitas'],
            'detail' => $log['detail'] ?: '-',
            'ip' => $log['ip_address'] ?: '-'
        ];
    }

    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate');

    echo json_encode([
        'success' => true,
        'total' => $total_data,
        'nomor_awal' => $nomor_awal,
        'nomor_akhir' => $nomor_akhir,
        'halaman' => $halaman,
        'total_halaman' => $total_halaman,
        'modul' => $daftar_modul,
        'data' => $ajax_rows
    ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);

    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Aktivitas Admin</title>

    <link
        rel="stylesheet"
        href="/project_atk_jahit/admin/assets/css/admin.css"
    >

    <style>
        .log-page {
            max-width: 1500px;
        }

        .log-summary,
        .log-filter,
        .log-table-card {
            background: #fff;
            border: 1px solid #e0e7ed;
            border-radius: 14px;
            box-shadow: 0 4px 15px rgba(16, 47, 75, .04);
        }

        .log-summary {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 20px;
            margin-bottom: 22px;
        }

        .log-summary-icon {
            width: 46px;
            height: 46px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            background: #edf3f7;
            color: #163b5c;
            font-size: 23px;
        }

        .log-summary small {
            display: block;
            color: #7890a4;
            font-size: 12px;
        }

        .log-summary strong {
            display: block;
            margin-top: 4px;
            color: #102f4b;
            font-size: 25px;
        }

        .log-filter {
            display: grid;
            grid-template-columns: minmax(180px, 1.6fr) repeat(3, minmax(130px, 1fr)) auto auto;
            gap: 12px;
            align-items: end;
            padding: 20px;
            margin-bottom: 22px;
        }

        .log-filter-group {
            min-width: 0;
        }

        .log-filter-group label {
            display: block;
            margin-bottom: 8px;
            color: #40566b;
            font-size: 12px;
            font-weight: 600;
        }

        .log-filter-group input,
        .log-filter-group select {
            width: 100%;
            min-height: 42px;
            box-sizing: border-box;
            padding: 10px 11px;
            border: 1px solid #d7e0e8;
            border-radius: 8px;
            outline: none;
            background: #fff;
            color: #243b53;
            font-family: inherit;
            font-size: 12px;
        }

        .log-filter-group input:focus,
        .log-filter-group select:focus {
            border-color: #315b78;
            box-shadow: 0 0 0 3px rgba(49, 91, 120, .08);
        }

        .log-btn {
            min-height: 42px;
            padding: 10px 16px;
            display: inline-flex;
            justify-content: center;
            align-items: center;
            box-sizing: border-box;
            border: 1px solid #173b5c;
            border-radius: 8px;
            background: #173b5c;
            color: #fff;
            text-decoration: none;
            font-family: inherit;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            white-space: nowrap;
            transition: .2s ease;
        }

        .log-btn:hover {
            background: #245b83;
            border-color: #245b83;
        }

        .log-btn-reset {
            border-color: #d7e0e8;
            background: #fff;
            color: #40566b;
        }

        .log-btn-reset:hover {
            border-color: #c4d1dc;
            background: #f5f8fb;
            color: #173b5c;
        }

        .log-table-card {
            overflow: hidden;
        }

        .log-table-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            padding: 20px 24px;
            border-bottom: 1px solid #e6edf2;
        }

        .log-table-heading h2 {
            margin: 0;
            color: #102f4b;
            font-size: 18px;
            font-weight: 600;
        }

        .log-table-heading span {
            color: #7890a4;
            font-size: 12px;
        }

        .log-auto-status {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            color: #47765b !important;
            font-size: 11px !important;
        }

        .log-auto-status::before {
            content: "";
            display: inline-block;
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #36a269;
        }

        .log-table-wrap {
            width: 100%;
            overflow-x: auto;
        }

        .log-table {
            width: 100%;
            min-width: 950px;
            border-collapse: collapse;
        }

        .log-table th {
            padding: 14px 18px;
            background: #f5f8fb;
            border-bottom: 1px solid #e3e9ee;
            color: #50677c;
            text-align: left;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .3px;
            white-space: nowrap;
        }

        .log-table td {
            padding: 16px 18px;
            border-bottom: 1px solid #edf1f5;
            color: #40566b;
            font-size: 12px;
            vertical-align: top;
        }

        .log-table tbody tr:last-child td {
            border-bottom: none;
        }

        .log-table tbody tr:hover {
            background: #fafcfe;
        }

        .log-date {
            min-width: 130px;
            color: #50677c;
            white-space: nowrap;
            line-height: 1.7;
        }

        .log-admin {
            min-width: 120px;
            color: #163b5c !important;
            font-weight: 600;
            overflow-wrap: anywhere;
        }

        .log-module {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 6px;
            background: #eaf0f6;
            color: #173b5c;
            font-size: 11px;
            font-weight: 600;
        }

        .log-activity {
            min-width: 130px;
            color: #243b53 !important;
            font-weight: 600;
        }

        .log-detail {
            min-width: 180px;
            max-width: 330px;
            white-space: pre-wrap;
            overflow-wrap: anywhere;
            line-height: 1.6;
        }

        .log-ip {
            min-width: 110px;
            color: #718096 !important;
            white-space: nowrap;
        }

        .log-empty {
            padding: 50px 20px !important;
            color: #7890a4 !important;
            text-align: center;
        }

        .log-pagination {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 14px;
            flex-wrap: wrap;
            padding: 18px 24px;
            border-top: 1px solid #e6edf2;
        }

        .log-pagination-info {
            color: #7890a4;
            font-size: 12px;
        }

        .log-pagination-links {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
        }

        .log-page-link {
            min-width: 35px;
            min-height: 35px;
            display: inline-flex;
            justify-content: center;
            align-items: center;
            padding: 5px 10px;
            box-sizing: border-box;
            border: 1px solid #d7e0e8;
            border-radius: 7px;
            background: #fff;
            color: #40566b;
            text-decoration: none;
            font-size: 12px;
            transition: .2s ease;
        }

        .log-page-link:hover {
            border-color: #173b5c;
            color: #173b5c;
        }

        .log-page-link.active {
            border-color: #173b5c;
            background: #173b5c;
            color: #fff;
        }

        .log-page-link.disabled {
            opacity: .4;
            pointer-events: none;
        }

        @media (max-width: 1150px) {
            .log-filter {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 650px) {
            .log-filter {
                grid-template-columns: 1fr;
                padding: 15px;
            }

            .log-filter .log-btn {
                width: 100%;
            }

            .log-summary {
                padding: 16px;
            }

            .log-table-heading {
                padding: 16px;
                align-items: flex-start;
                flex-direction: column;
            }

            .log-pagination {
                padding: 16px;
                align-items: flex-start;
                flex-direction: column;
            }
        }
    </style>
</head>
<body>

<div class="admin-layout">

    <?php require_once __DIR__ . "/../includes/sidebar.php"; ?>

    <main class="admin-main">

        <?php require_once __DIR__ . "/../includes/navbar.php"; ?>

        <div class="admin-content">
            <div class="log-page">

                <div class="page-header">
                    <div>
                        <h1>Log Aktivitas Admin</h1>
                        <p>Pantau riwayat aktivitas dan perubahan yang dilakukan oleh admin dalam sistem.</p>
                    </div>
                </div>

                <section class="log-summary">
                    <div class="log-summary-icon">◷</div>
                    <div>
                        <small>Total aktivitas sesuai filter</small>
                        <strong id="log-total">
                            <?= number_format($total_data, 0, ',', '.'); ?>
                        </strong>
                    </div>
                </section>

                <form method="GET" action="index.php" class="log-filter">
                    <div class="log-filter-group">
                        <label for="search">Cari aktivitas</label>
                        <input
                            type="text"
                            id="search"
                            name="search"
                            value="<?= eLog($search); ?>"
                            placeholder="Nama admin, aktivitas, detail..."
                        >
                    </div>

                    <div class="log-filter-group">
                        <label for="modul">Modul</label>
                        <select id="modul" name="modul">
                            <option value="">Semua modul</option>
                            <?php foreach ($daftar_modul as $nama_modul): ?>
                                <option
                                    value="<?= eLog($nama_modul); ?>"
                                    <?= $modul === $nama_modul ? 'selected' : ''; ?>
                                >
                                    <?= eLog(ucwords(str_replace('_', ' ', $nama_modul))); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="log-filter-group">
                        <label for="tanggal_mulai">Dari tanggal</label>
                        <input
                            type="date"
                            id="tanggal_mulai"
                            name="tanggal_mulai"
                            value="<?= eLog($tanggal_mulai); ?>"
                        >
                    </div>

                    <div class="log-filter-group">
                        <label for="tanggal_selesai">Sampai tanggal</label>
                        <input
                            type="date"
                            id="tanggal_selesai"
                            name="tanggal_selesai"
                            value="<?= eLog($tanggal_selesai); ?>"
                        >
                    </div>

                    <button type="submit" class="log-btn">Cari</button>
                    <a href="index.php" class="log-btn log-btn-reset">Reset</a>
                </form>

                <section class="log-table-card">
                    <div class="log-table-heading">
                        <h2>Riwayat Aktivitas</h2>
                        <div>
                            <span id="log-range">
                                Menampilkan <?= $nomor_awal; ?>–<?= $nomor_akhir; ?>
                                dari <?= number_format($total_data, 0, ',', '.'); ?> data
                            </span>
                            <span class="log-auto-status" id="log-auto-status">
                                Auto-refresh aktif · 5 detik
                            </span>
                        </div>
                    </div>

                    <div class="log-table-wrap">
                        <table class="log-table">
                            <thead>
                                <tr>
                                    <th>Waktu</th>
                                    <th>Admin</th>
                                    <th>Modul</th>
                                    <th>Aktivitas</th>
                                    <th>Detail</th>
                                    <th>Alamat IP</th>
                                </tr>
                            </thead>
                            <tbody id="log-tbody">
                                <?php if (empty($data_log)): ?>
                                    <tr>
                                        <td colspan="6" class="log-empty">
                                            Belum ada aktivitas yang tercatat, atau data tidak ditemukan.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($data_log as $log): ?>
                                        <tr>
                                            <td class="log-date">
                                                <?= eLog(date('d-m-Y H:i:s', strtotime($log['created_at']))); ?>
                                            </td>
                                            <td class="log-admin">
                                                <?= eLog(
                                                    $log['nama_admin']
                                                    ?: 'Admin #' . ($log['id_admin'] ?? '-')
                                                ); ?>
                                            </td>
                                            <td>
                                                <span class="log-module">
                                                    <?= eLog(
                                                        ucwords(str_replace('_', ' ', $log['modul']))
                                                    ); ?>
                                                </span>
                                            </td>
                                            <td class="log-activity">
                                                <?= eLog($log['aktivitas']); ?>
                                            </td>
                                            <td class="log-detail">
                                                <?= eLog($log['detail'] ?: '-'); ?>
                                            </td>
                                            <td class="log-ip">
                                                <?= eLog($log['ip_address'] ?: '-'); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <div
                        class="log-pagination"
                        id="log-pagination"
                        style="<?= $total_data > 0 ? '' : 'display:none;'; ?>"
                    >
                        <div class="log-pagination-info" id="pagination-info">
                            Halaman <?= $halaman; ?> dari <?= $total_halaman; ?>
                        </div>

                        <div class="log-pagination-links" id="pagination-links">
                            <a
                                class="log-page-link <?= $halaman <= 1 ? 'disabled' : ''; ?>"
                                href="<?= eLog(urlHalamanLog(max(1, $halaman - 1), $parameter_url)); ?>"
                            >‹</a>

                            <?php
                            $awal = max(1, $halaman - 2);
                            $akhir = min($total_halaman, $halaman + 2);
                            ?>

                            <?php for ($i = $awal; $i <= $akhir; $i++): ?>
                                <a
                                    class="log-page-link <?= $i === $halaman ? 'active' : ''; ?>"
                                    href="<?= eLog(urlHalamanLog($i, $parameter_url)); ?>"
                                ><?= $i; ?></a>
                            <?php endfor; ?>

                            <a
                                class="log-page-link <?= $halaman >= $total_halaman ? 'disabled' : ''; ?>"
                                href="<?= eLog(urlHalamanLog(min($total_halaman, $halaman + 1), $parameter_url)); ?>"
                            >›</a>
                        </div>
                    </div>
                </section>

            </div>
        </div>
    </main>
</div>

<script src="/project_atk_jahit/admin/assets/js/admin.js"></script>

<script>
(function () {
    'use strict';

    const intervalRefresh = 5000;

    const tbody = document.getElementById('log-tbody');
    const totalElement = document.getElementById('log-total');
    const rangeElement = document.getElementById('log-range');
    const statusElement = document.getElementById('log-auto-status');
    const paginationElement = document.getElementById('log-pagination');
    const paginationInfo = document.getElementById('pagination-info');
    const paginationLinks = document.getElementById('pagination-links');
    const modulSelect = document.getElementById('modul');

    let sedangMemuat = false;

    function formatAngka(angka) {
        return Number(angka || 0).toLocaleString('id-ID');
    }

    function buatSel(teks, className) {
        const td = document.createElement('td');
        td.textContent = teks ?? '';

        if (className) {
            td.className = className;
        }

        return td;
    }

    function renderTabel(rows) {
        tbody.replaceChildren();

        if (!Array.isArray(rows) || rows.length === 0) {
            const tr = document.createElement('tr');
            const td = document.createElement('td');

            td.colSpan = 6;
            td.className = 'log-empty';
            td.textContent =
                'Belum ada aktivitas yang tercatat, atau data tidak ditemukan.';

            tr.appendChild(td);
            tbody.appendChild(tr);

            return;
        }

        rows.forEach(function (row) {
            const tr = document.createElement('tr');

            tr.appendChild(buatSel(row.waktu, 'log-date'));
            tr.appendChild(buatSel(row.admin, 'log-admin'));

            const tdModul = document.createElement('td');
            const badge = document.createElement('span');

            badge.className = 'log-module';
            badge.textContent = row.modul || '-';

            tdModul.appendChild(badge);
            tr.appendChild(tdModul);

            tr.appendChild(buatSel(row.aktivitas, 'log-activity'));
            tr.appendChild(buatSel(row.detail, 'log-detail'));
            tr.appendChild(buatSel(row.ip, 'log-ip'));

            tbody.appendChild(tr);
        });
    }

    function buatTautanHalaman(label, nomor, aktif, nonaktif) {
        const link = document.createElement('a');

        link.className = 'log-page-link';
        link.textContent = label;

        if (aktif) {
            link.classList.add('active');
        }

        if (nonaktif) {
            link.classList.add('disabled');
            link.setAttribute('aria-disabled', 'true');
            link.href = '#';
        } else {
            const url = new URL(window.location.href);

            url.searchParams.delete('ajax');
            url.searchParams.set('halaman', String(nomor));

            link.href = url.pathname + '?' + url.searchParams.toString();
        }

        return link;
    }

    function renderPagination(data) {
        if (!data.total || data.total <= 0) {
            paginationElement.style.display = 'none';
            return;
        }

        paginationElement.style.display = 'flex';

        paginationInfo.textContent =
            'Halaman ' + data.halaman + ' dari ' + data.total_halaman;

        paginationLinks.replaceChildren();

        const halaman = Number(data.halaman);
        const totalHalaman = Number(data.total_halaman);

        paginationLinks.appendChild(
            buatTautanHalaman('‹', Math.max(1, halaman - 1), false, halaman <= 1)
        );

        const awal = Math.max(1, halaman - 2);
        const akhir = Math.min(totalHalaman, halaman + 2);

        for (let i = awal; i <= akhir; i++) {
            paginationLinks.appendChild(
                buatTautanHalaman(String(i), i, i === halaman, false)
            );
        }

        paginationLinks.appendChild(
            buatTautanHalaman(
                '›',
                Math.min(totalHalaman, halaman + 1),
                false,
                halaman >= totalHalaman
            )
        );
    }

    function renderModul(modulList) {
        if (!Array.isArray(modulList) || !modulSelect) {
            return;
        }

        const pilihanSekarang = modulSelect.value;
        const fragment = document.createDocumentFragment();

        const optionSemua = document.createElement('option');
        optionSemua.value = '';
        optionSemua.textContent = 'Semua modul';
        fragment.appendChild(optionSemua);

        modulList.forEach(function (nama) {
            const option = document.createElement('option');

            option.value = nama;
            option.textContent = String(nama)
                .replace(/_/g, ' ')
                .replace(/\b\w/g, function (huruf) {
                    return huruf.toUpperCase();
                });

            fragment.appendChild(option);
        });

        modulSelect.replaceChildren(fragment);

        const pilihanMasihAda = Array.from(modulSelect.options).some(
            function (option) {
                return option.value === pilihanSekarang;
            }
        );

        modulSelect.value = pilihanMasihAda ? pilihanSekarang : '';
    }

    async function refreshLog() {
        if (sedangMemuat || document.visibilityState === 'hidden') {
            return;
        }

        sedangMemuat = true;

        try {
            const url = new URL(window.location.href);
            url.searchParams.set('ajax', '1');

            const response = await fetch(url.href, {
                method: 'GET',
                cache: 'no-store',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error('Respons server tidak berhasil.');
            }

            const data = await response.json();

            if (!data || data.success !== true) {
                throw new Error('Respons log tidak valid.');
            }

            totalElement.textContent = formatAngka(data.total);

            rangeElement.textContent =
                'Menampilkan ' +
                data.nomor_awal +
                '–' +
                data.nomor_akhir +
                ' dari ' +
                formatAngka(data.total) +
                ' data';

            renderTabel(data.data);
            renderPagination(data);
            renderModul(data.modul);

            statusElement.textContent = 'Auto-refresh aktif · 5 detik';
            statusElement.style.color = '#47765b';
        } catch (error) {
            statusElement.textContent = 'Menunggu koneksi...';
            statusElement.style.color = '#a16207';
        } finally {
            sedangMemuat = false;
        }
    }

    /* Periksa data terbaru setiap 5 detik tanpa reload seluruh halaman. */
    window.setInterval(refreshLog, intervalRefresh);

    /* Ambil data terbaru saat kembali ke tab browser. */
    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'visible') {
            refreshLog();
        }
    });

    /* Jalankan pengecekan pertama setelah halaman siap. */
    refreshLog();
})();
</script>

</body>
</html>