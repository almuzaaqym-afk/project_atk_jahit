<?php
use Dompdf\Dompdf;
use Dompdf\Options;

if (ob_get_level() === 0) {
    ob_start();
}

session_start();

require_once __DIR__ . '/../../config/koneksi.php';
require_once __DIR__ . '/../includes/hak_akses.php';
require_once __DIR__ . '/../includes/log_aktivitas.php';

cekAkses('laporan');

if (!isset($_SESSION['id_admin'])) {
    header('Location: ../login.php');
    exit;
}

$autoload = __DIR__ . '/../../vendor/autoload.php';

if (!is_file($autoload)) {
    http_response_code(500);
    exit('Library PDF tidak ditemukan. Pastikan Dompdf sudah terpasang melalui Composer.');
}

require_once $autoload;

/* =========================================================
   HELPER PDF
========================================================= */

function pdfEsc($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function pdfRupiah($value): string
{
    return 'Rp ' . number_format((float)$value, 0, ',', '.');
}

function pdfTanggal($value): string
{
    if (!$value || $value === '0000-00-00') {
        return '-';
    }

    $timestamp = strtotime((string)$value);

    return $timestamp ? date('d-m-Y', $timestamp) : pdfEsc($value);
}

function pdfAmbilSemuaTanggal(
    mysqli $conn,
    string $sql,
    string $mulai,
    string $selesai
): array {
    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        throw new RuntimeException('Query laporan gagal disiapkan.');
    }

    mysqli_stmt_bind_param($stmt, 'ss', $mulai, $selesai);

    if (!mysqli_stmt_execute($stmt)) {
        $error = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);

        throw new RuntimeException('Query laporan gagal dijalankan: ' . $error);
    }

    $result = mysqli_stmt_get_result($stmt);
    $rows = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }

    mysqli_stmt_close($stmt);

    return $rows;
}

function pdfAmbilSatu(mysqli $conn, string $sql): array
{
    $result = mysqli_query($conn, $sql);

    if (!$result) {
        throw new RuntimeException('Data ringkasan laporan gagal diambil.');
    }

    $row = mysqli_fetch_assoc($result) ?: [];

    mysqli_free_result($result);

    return $row;
}

function pdfLogoDataUri(string $logoPath): string
{
    $candidates = [];
    $logoPath = trim($logoPath);

    if ($logoPath !== '') {
        $cleanPath = ltrim(str_replace('\\', '/', $logoPath), '/');

        $candidates[] = __DIR__ . '/../' . $cleanPath;
        $candidates[] = __DIR__ . '/../../' . $cleanPath;
    }

    $candidates[] = __DIR__ . '/../assets/images/settings/logo_toko.png';
    $candidates[] = __DIR__ . '/../../pelanggan/assets/images/logo.png';

    foreach ($candidates as $candidate) {
        if (!is_file($candidate) || !is_readable($candidate)) {
            continue;
        }

        $info = @getimagesize($candidate);

        if (!$info || empty($info['mime'])) {
            continue;
        }

        $mime = strtolower($info['mime']);

        if (!in_array($mime, ['image/png', 'image/jpeg', 'image/gif'], true)) {
            continue;
        }

        $bytes = @file_get_contents($candidate);

        if ($bytes !== false) {
            return 'data:' . $mime . ';base64,' . base64_encode($bytes);
        }
    }

    return '';
}

function pdfRapikanAlamat(string $alamat): string
{
    $alamat = str_replace(
        ["\r\n", "\r", "\n", "\t"],
        ' ',
        trim($alamat)
    );

    $alamat = preg_replace('/\s+/u', ' ', $alamat) ?? $alamat;
    $alamat = preg_replace('/\s+([,;])/u', '$1', $alamat) ?? $alamat;
    $alamat = preg_replace('/([,;])\s*/u', '$1 ', $alamat) ?? $alamat;

    return trim($alamat, " \t\n\r\0\x0B,");
}

/* =========================================================
   VALIDASI PARAMETER LAPORAN
========================================================= */

$jenis = $_GET['jenis'] ?? '';

$jenisValid = [
    'pembelian',
    'penjualan',
    'produk',
    'stok'
];

if (!in_array($jenis, $jenisValid, true)) {
    http_response_code(400);
    exit('Jenis laporan tidak valid.');
}

$tanggal_mulai = $_GET['tanggal_mulai'] ?? date('Y-m-01');
$tanggal_selesai = $_GET['tanggal_selesai'] ?? date('Y-m-d');
$tanggal_akhir = $_GET['tanggal_akhir'] ?? date('Y-m-d');

$filter = $_GET['filter'] ?? 'semua';

$validDate = static function ($date): bool {
    if (!is_string($date)) {
        return false;
    }

    $parsed = DateTime::createFromFormat('!Y-m-d', $date);

    return $parsed && $parsed->format('Y-m-d') === $date;
};

if (!$validDate($tanggal_mulai)) {
    $tanggal_mulai = date('Y-m-01');
}

if (!$validDate($tanggal_selesai)) {
    $tanggal_selesai = date('Y-m-d');
}

if (!$validDate($tanggal_akhir)) {
    $tanggal_akhir = date('Y-m-d');
}

if ($tanggal_mulai > $tanggal_selesai) {
    [$tanggal_mulai, $tanggal_selesai] = [
        $tanggal_selesai,
        $tanggal_mulai
    ];
}

if ($tanggal_mulai > $tanggal_akhir) {
    [$tanggal_mulai, $tanggal_akhir] = [
        $tanggal_akhir,
        $tanggal_mulai
    ];
}

if (!in_array($filter, ['semua', 'aman', 'menipis', 'habis'], true)) {
    $filter = 'semua';
}

/*
 * Aktifkan exception MySQL agar kesalahan query
 * dapat ditangkap dan ditampilkan ketika debugging localhost.
 */
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {

    /* =========================================================
       INFORMASI TOKO
    ========================================================= */

    $setting = pdfAmbilSatu(
        $conn,
        "SELECT nama_toko, logo, alamat, telepon, email
         FROM pengaturan_toko
         ORDER BY id_pengaturan ASC
         LIMIT 1"
    );

    $namaToko = trim((string)($setting['nama_toko'] ?? ''))
        ?: 'Toko ATK & Alat Jahit';

    $alamatToko = pdfRapikanAlamat(
        (string)($setting['alamat'] ?? '')
    );

    $teleponToko = trim((string)($setting['telepon'] ?? ''));
    $emailToko = trim((string)($setting['email'] ?? ''));

    $logoUri = pdfLogoDataUri(
        (string)($setting['logo'] ?? '')
    );

    $judul = '';
    $periode = '';
    $kolom = [];
    $baris = [];
    $ringkasan = [];
    $filename = 'laporan.pdf';

    /* =========================================================
       LAPORAN PEMBELIAN
    ========================================================= */

    if ($jenis === 'pembelian') {

        $judul = 'LAPORAN PEMBELIAN';

        $periode = 'Periode: '
            . pdfTanggal($tanggal_mulai)
            . ' s.d. '
            . pdfTanggal($tanggal_selesai);

        $filename = 'laporan-pembelian-'
            . $tanggal_mulai . '-sd-'
            . $tanggal_selesai . '.pdf';

        $baris = pdfAmbilSemuaTanggal(
            $conn,
            "SELECT
                pb.id_pembelian,
                pb.nomor_pembelian,
                pb.tanggal_pembelian,
                pb.total_harga,
                pb.status,
                pb.id_supplier,
                s.nama_supplier
             FROM pembelian pb
             LEFT JOIN supplier s
                ON pb.id_supplier = s.id_supplier
             WHERE DATE(pb.tanggal_pembelian) BETWEEN ? AND ?
             ORDER BY
                pb.tanggal_pembelian DESC,
                pb.id_pembelian DESC",
            $tanggal_mulai,
            $tanggal_selesai
        );

        $totalSelesai = 0;
        $supplierIds = [];

        foreach ($baris as $row) {
            if (($row['status'] ?? '') === 'Selesai') {
                $totalSelesai += (float)$row['total_harga'];
            }

            if (!empty($row['id_supplier'])) {
                $supplierIds[(string)$row['id_supplier']] = true;
            }
        }

        $mulaiSQL = mysqli_real_escape_string(
            $conn,
            $tanggal_mulai
        );

        $selesaiSQL = mysqli_real_escape_string(
            $conn,
            $tanggal_selesai
        );

        $itemRow = pdfAmbilSatu(
            $conn,
            "SELECT COALESCE(SUM(dp.jumlah), 0) AS total
             FROM detail_pembelian dp
             INNER JOIN pembelian p
                ON dp.id_pembelian = p.id_pembelian
             WHERE DATE(p.tanggal_pembelian)
                BETWEEN '$mulaiSQL' AND '$selesaiSQL'
               AND p.status = 'Selesai'"
        );

        $ringkasan = [
            [
                'Total Transaksi',
                number_format(count($baris), 0, ',', '.')
            ],
            [
                'Pembelian Selesai',
                pdfRupiah($totalSelesai)
            ],
            [
                'Jumlah Supplier',
                number_format(count($supplierIds), 0, ',', '.')
            ],
            [
                'Total Item Selesai',
                number_format(
                    (int)($itemRow['total'] ?? 0),
                    0,
                    ',',
                    '.'
                )
            ],
        ];

        $kolom = [
            'No',
            'Nomor Pembelian',
            'Tanggal',
            'Supplier',
            'Total Harga',
            'Status'
        ];

        foreach ($baris as $i => $row) {
            $baris[$i] = [
                (string)($i + 1),
                (string)($row['nomor_pembelian'] ?? '-'),
                pdfTanggal($row['tanggal_pembelian'] ?? ''),
                (string)($row['nama_supplier'] ?? 'Supplier dihapus'),
                pdfRupiah($row['total_harga'] ?? 0),
                (string)($row['status'] ?? '-'),
            ];
        }

    /* =========================================================
       LAPORAN PENJUALAN
    ========================================================= */

    } elseif ($jenis === 'penjualan') {

        $judul = 'LAPORAN PENJUALAN';

        $periode = 'Periode: '
            . pdfTanggal($tanggal_mulai)
            . ' s.d. '
            . pdfTanggal($tanggal_selesai);

        $filename = 'laporan-penjualan-'
            . $tanggal_mulai . '-sd-'
            . $tanggal_selesai . '.pdf';

        $baris = pdfAmbilSemuaTanggal(
            $conn,
            "SELECT
                p.id_pesanan,
                p.nomor_pesanan,
                p.tanggal_pesanan,
                p.total_harga,
                p.status_pesanan,
                p.id_voucher,
                p.kode_voucher,
                p.diskon_voucher,
                pl.nama
             FROM pesanan p
             LEFT JOIN pelanggan pl
                ON p.id_pelanggan = pl.id_pelanggan
             WHERE DATE(p.tanggal_pesanan) BETWEEN ? AND ?
             ORDER BY
                p.tanggal_pesanan DESC,
                p.id_pesanan DESC",
            $tanggal_mulai,
            $tanggal_selesai
        );

        $totalPenjualan = 0;
        $totalDiskon = 0;

        foreach ($baris as $row) {
            if (($row['status_pesanan'] ?? '') !== 'Dibatalkan') {
                $totalPenjualan += (float)$row['total_harga'];
                $totalDiskon += (float)$row['diskon_voucher'];
            }
        }

        $itemRows = pdfAmbilSemuaTanggal(
            $conn,
            "SELECT COALESCE(SUM(dp.jumlah), 0) AS total
             FROM detail_pesanan dp
             INNER JOIN pesanan p
                ON dp.id_pesanan = p.id_pesanan
             WHERE DATE(p.tanggal_pesanan) BETWEEN ? AND ?
               AND p.status_pesanan != 'Dibatalkan'",
            $tanggal_mulai,
            $tanggal_selesai
        );

        $ringkasan = [
            [
                'Total Transaksi',
                number_format(count($baris), 0, ',', '.')
            ],
            [
                'Omzet Bersih',
                pdfRupiah($totalPenjualan)
            ],
            [
                'Diskon Voucher',
                pdfRupiah($totalDiskon)
            ],
            [
                'Produk Terjual',
                number_format(
                    (int)($itemRows[0]['total'] ?? 0),
                    0,
                    ',',
                    '.'
                )
            ],
        ];

        $kolom = [
            'No',
            'Nomor Pesanan',
            'Tanggal',
            'Pelanggan',
            'Total',
            'Diskon Voucher',
            'Kode Voucher',
            'Status'
        ];

        foreach ($baris as $i => $row) {
            $baris[$i] = [
                (string)($i + 1),
                (string)($row['nomor_pesanan'] ?? '-'),
                pdfTanggal($row['tanggal_pesanan'] ?? ''),
                (string)($row['nama'] ?? 'Pelanggan dihapus'),
                pdfRupiah($row['total_harga'] ?? 0),
                pdfRupiah($row['diskon_voucher'] ?? 0),
                (string)($row['kode_voucher'] ?? '-'),
                (string)($row['status_pesanan'] ?? '-'),
            ];
        }

    /* =========================================================
       LAPORAN PRODUK TERLARIS
    ========================================================= */

    } elseif ($jenis === 'produk') {

        $judul = 'LAPORAN PRODUK TERLARIS';

        $periode = 'Periode: '
            . pdfTanggal($tanggal_mulai)
            . ' s.d. '
            . pdfTanggal($tanggal_akhir);

        $filename = 'laporan-produk-terlaris-'
            . $tanggal_mulai . '-sd-'
            . $tanggal_akhir . '.pdf';

        $baris = pdfAmbilSemuaTanggal(
            $conn,
            "SELECT
                p.id_produk,
                p.nama_produk,
                p.harga,
                p.stok,
                k.nama_kategori,
                COALESCE(SUM(dp.jumlah), 0) AS total_terjual,
                COALESCE(SUM(dp.subtotal), 0) AS total_pendapatan
             FROM detail_pesanan dp
             INNER JOIN pesanan ps
                ON dp.id_pesanan = ps.id_pesanan
             INNER JOIN produk p
                ON dp.id_produk = p.id_produk
             LEFT JOIN kategori k
                ON p.id_kategori = k.id_kategori
             WHERE DATE(ps.tanggal_pesanan) BETWEEN ? AND ?
               AND ps.status_pesanan != 'Dibatalkan'
             GROUP BY
                p.id_produk,
                p.nama_produk,
                p.harga,
                p.stok,
                k.nama_kategori
             ORDER BY total_terjual DESC, total_pendapatan DESC",
            $tanggal_mulai,
            $tanggal_akhir
        );

        $totalUnit = 0;
        $totalOmzet = 0;

        foreach ($baris as $row) {
            $totalUnit += (int)$row['total_terjual'];
            $totalOmzet += (float)$row['total_pendapatan'];
        }

        $ringkasan = [
            [
                'Jumlah Produk Terjual',
                number_format(count($baris), 0, ',', '.')
            ],
            [
                'Total Unit Terjual',
                number_format($totalUnit, 0, ',', '.')
            ],
            [
                'Total Pendapatan',
                pdfRupiah($totalOmzet)
            ],
        ];

        $kolom = [
            'Peringkat',
            'Nama Produk',
            'Kategori',
            'Harga',
            'Terjual',
            'Pendapatan',
            'Stok'
        ];

        foreach ($baris as $i => $row) {
            $baris[$i] = [
                (string)($i + 1),
                (string)($row['nama_produk'] ?? '-'),
                (string)($row['nama_kategori'] ?? '-'),
                pdfRupiah($row['harga'] ?? 0),
                number_format(
                    (int)($row['total_terjual'] ?? 0),
                    0,
                    ',',
                    '.'
                ),
                pdfRupiah($row['total_pendapatan'] ?? 0),
                number_format(
                    (int)($row['stok'] ?? 0),
                    0,
                    ',',
                    '.'
                ),
            ];
        }

    /* =========================================================
       LAPORAN STOK
    ========================================================= */

    } else {

        $judul = 'LAPORAN STOK';

        $labelsFilter = [
            'semua' => 'Semua Produk',
            'aman' => 'Stok Aman',
            'menipis' => 'Stok Menipis',
            'habis' => 'Stok Habis',
        ];

        $periode = 'Filter: '
            . $labelsFilter[$filter]
            . ' | Data stok saat ini';

        $filename = 'laporan-stok-'
            . $filter
            . '-'
            . date('Y-m-d')
            . '.pdf';

        $totalProduk = (int)(
            pdfAmbilSatu(
                $conn,
                'SELECT COUNT(*) AS total FROM produk'
            )['total'] ?? 0
        );

        $totalStok = (int)(
            pdfAmbilSatu(
                $conn,
                'SELECT COALESCE(SUM(stok), 0) AS total FROM produk'
            )['total'] ?? 0
        );

        $totalHabis = (int)(
            pdfAmbilSatu(
                $conn,
                'SELECT COUNT(*) AS total FROM produk WHERE stok <= 0'
            )['total'] ?? 0
        );

        $totalMenipis = (int)(
            pdfAmbilSatu(
                $conn,
                'SELECT COUNT(*) AS total FROM produk
                 WHERE stok > 0 AND stok <= stok_minimum'
            )['total'] ?? 0
        );

        $totalAman = $totalProduk - $totalHabis - $totalMenipis;

        $ringkasan = [
            [
                'Total Produk',
                number_format($totalProduk, 0, ',', '.')
            ],
            [
                'Total Stok',
                number_format($totalStok, 0, ',', '.')
            ],
            [
                'Stok Aman',
                number_format($totalAman, 0, ',', '.')
            ],
            [
                'Stok Menipis',
                number_format($totalMenipis, 0, ',', '.')
            ],
            [
                'Stok Habis',
                number_format($totalHabis, 0, ',', '.')
            ],
        ];

        $sql = "SELECT
                    p.id_produk,
                    p.nama_produk,
                    p.stok,
                    p.stok_minimum,
                    p.lokasi_rak,
                    k.nama_kategori
                FROM produk p
                LEFT JOIN kategori k
                    ON p.id_kategori = k.id_kategori";

        if ($filter === 'aman') {
            $sql .= ' WHERE p.stok > p.stok_minimum';
        } elseif ($filter === 'menipis') {
            $sql .= ' WHERE p.stok > 0 AND p.stok <= p.stok_minimum';
        } elseif ($filter === 'habis') {
            $sql .= ' WHERE p.stok <= 0';
        }

        $sql .= ' ORDER BY p.stok ASC, p.nama_produk ASC';

        $res = mysqli_query($conn, $sql);

        if (!$res) {
            throw new RuntimeException('Data stok gagal diambil.');
        }

        $stokRows = [];

        while ($row = mysqli_fetch_assoc($res)) {
            $stokRows[] = $row;
        }

        mysqli_free_result($res);

        $kolom = [
            'No',
            'Nama Produk',
            'Kategori',
            'Lokasi/Rak',
            'Stok',
            'Stok Minimum',
            'Status'
        ];

        foreach ($stokRows as $i => $row) {
            $stok = (int)$row['stok'];
            $minimum = (int)$row['stok_minimum'];

            $status = $stok <= 0
                ? 'Habis'
                : ($stok <= $minimum ? 'Menipis' : 'Aman');

            $baris[] = [
                (string)($i + 1),
                (string)($row['nama_produk'] ?? '-'),
                (string)($row['nama_kategori'] ?? '-'),
                (string)($row['lokasi_rak'] ?? '-'),
                number_format($stok, 0, ',', '.'),
                number_format($minimum, 0, ',', '.'),
                $status,
            ];
        }
    }

    /* =========================================================
       SIAPKAN KOP SURAT
    ========================================================= */

    $logoHtml = $logoUri !== ''
        ? '<img class="logo" src="' . $logoUri . '" alt="Logo toko">'
        : '<div class="logo-placeholder">ATK</div>';

    $contactParts = [];

    if ($alamatToko !== '') {
        $contactParts[] = '<span class="contact-label">Alamat</span>: '
            . pdfEsc($alamatToko);
    }

    $kontakParts = [];

    if ($teleponToko !== '') {
        $kontakParts[] = '<span class="contact-label">Telp.</span> '
            . pdfEsc($teleponToko);
    }

    if ($emailToko !== '') {
        $kontakParts[] = '<span class="contact-label">Email</span> '
            . pdfEsc($emailToko);
    }

    $alamatHtml = $contactParts
        ? implode('', $contactParts)
        : 'Alamat toko belum diisi pada Pengaturan Toko';

    $kontakHtml = $kontakParts
        ? implode(' &nbsp;&nbsp; | &nbsp;&nbsp; ', $kontakParts)
        : '';

    /* =========================================================
       RINGKASAN
    ========================================================= */

    $summaryHtml = '';

    foreach ($ringkasan as $item) {
        $summaryHtml .=
            '<td class="summary-cell">'
            . '<div class="summary-label">' . pdfEsc($item[0]) . '</div>'
            . '<div class="summary-value">' . pdfEsc($item[1]) . '</div>'
            . '</td>';
    }

    /* =========================================================
       HEADER TABEL
    ========================================================= */

    $tableHeader = '';

    foreach ($kolom as $name) {
        $tableHeader .= '<th>' . pdfEsc($name) . '</th>';
    }

    /* =========================================================
       BARIS TABEL
    ========================================================= */

    $tableRows = '';

    foreach ($baris as $row) {
        $tableRows .= '<tr>';

        foreach ($row as $cell) {
            $tableRows .= '<td>' . pdfEsc($cell) . '</td>';
        }

        $tableRows .= '</tr>';
    }

    if ($tableRows === '') {
        $tableRows =
            '<tr><td class="empty" colspan="' . count($kolom) . '">'
            . 'Tidak ada data pada periode atau filter ini.'
            . '</td></tr>';
    }

    /* =========================================================
       HTML PDF
    ========================================================= */

    $html = '<!doctype html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <style>
            @page {
                margin: 28px 28px 42px 28px;
            }

            body {
                font-family: DejaVu Sans, sans-serif;
                color: #26384a;
                font-size: 9px;
                line-height: 1.45;
            }

            table.kop {
                width: 100%;
                border-collapse: collapse;
                table-layout: fixed;
                margin: 0;
            }

            .kop td {
                vertical-align: middle;
            }

            .kop-logo {
                padding-top: 5px;
                padding-bottom: 5px;
                vertical-align: middle;
            }

            .kop-logo-kiri {
                text-align: left;
                padding-left: 0;
                padding-right: 8px;
            }

            .kop-logo-kanan {
                text-align: right;
                padding-left: 8px;
                padding-right: 0;
            }

            .kop-divider {
                width: 2%;
                text-align: center;
                vertical-align: middle;
                padding: 0;
            }

            .kop-divider-line {
                width: 2px;
                height: 78px;
                margin: 0 auto;
                background: #102a43;
            }

            .logo {
                display: block;
                width: 82px;
                max-width: 100%;
                max-height: 82px;
                height: auto;
            }

            .logo-placeholder {
                display: inline-block;
                width: 58px;
                height: 43px;
                padding-top: 15px;
                border: 1px solid #c5a46d;
                color: #102a43;
                text-align: center;
                font-size: 15px;
                font-weight: bold;
                box-sizing: border-box;
            }

            .kop-info {
                text-align: center;
                padding: 7px 15px;
            }

            .store-name {
                color: #102a43;
                font-size: 19px;
                font-weight: bold;
                letter-spacing: .35px;
                text-transform: uppercase;
                margin: 0 0 8px 0;
                text-align: center;
            }

            .store-address {
                color: #46596b;
                font-size: 8px;
                line-height: 1.55;
                text-align: center;
                white-space: nowrap;
                margin-bottom: 5px;
            }

            .store-contact {
                color: #536579;
                font-size: 8px;
                line-height: 1.55;
                text-align: center;
                white-space: nowrap;
            }

            .contact-label {
                color: #102a43;
                font-weight: bold;
            }

            .kop-line-navy {
                height: 3px;
                background: #102a43;
                margin-top: 11px;
            }

            .kop-line-gold {
                height: 1px;
                background: #c5a46d;
                margin-top: 3px;
                margin-bottom: 17px;
            }

            .report-title {
                text-align: center;
                color: #102a43;
                font-size: 15px;
                font-weight: bold;
                letter-spacing: 1.1px;
                margin: 0 0 5px 0;
            }

            .title-underline {
                width: 118px;
                height: 1px;
                background: #c5a46d;
                margin: 0 auto 7px auto;
            }

            .period {
                text-align: center;
                color: #536579;
                font-size: 8.5px;
                margin-bottom: 16px;
            }

            table.summary {
                width: 100%;
                border-collapse: separate;
                border-spacing: 6px 0;
                margin: 0 0 17px -6px;
            }

            .summary-cell {
                width: ' . (100 / max(count($ringkasan), 1)) . '%;
                background: #f6f8fb;
                border: 1px solid #e0e6ed;
                border-top: 2px solid #c5a46d;
                padding: 9px 8px 10px 8px;
                vertical-align: top;
            }

            .summary-label {
                color: #718096;
                font-size: 7.5px;
                text-transform: uppercase;
                letter-spacing: .35px;
                margin-bottom: 5px;
            }

            .summary-value {
                color: #102a43;
                font-size: 11px;
                font-weight: bold;
            }

            table.data {
                width: 100%;
                border-collapse: collapse;
            }

            .data thead {
                display: table-header-group;
            }

            .data th {
                background: #102a43;
                color: #ffffff;
                font-size: 7.7px;
                font-weight: bold;
                padding: 8px 6px;
                text-align: left;
                border: 1px solid #102a43;
            }

            .data td {
                border: 1px solid #dfe5ec;
                padding: 7px 6px;
                font-size: 8px;
                vertical-align: top;
            }

            .data tbody tr:nth-child(even) td {
                background: #f5f7fa;
            }

            .data tbody tr:nth-child(odd) td {
                background: #ffffff;
            }

            .empty {
                text-align: center;
                color: #718096;
                padding: 18px !important;
            }

            .footer {
                position: fixed;
                bottom: -25px;
                left: 0;
                right: 0;
                border-top: 1px solid #dbe1e8;
                padding-top: 6px;
                font-size: 7px;
                color: #718096;
            }
        </style>
    </head>
    <body>';

    /* =========================================================
       KOP SURAT
    ========================================================= */

    $html .= '<table class="kop" width="100%" cellpadding="0" cellspacing="0">'
        . '<tr>'
        . '<td class="kop-logo kop-logo-kiri" width="15%" align="left">'
        . $logoHtml
        . '</td>'
        . '<td class="kop-divider" width="2%" align="center">'
        . '<div class="kop-divider-line"></div>'
        . '</td>'
        . '<td class="kop-info" width="66%" align="center">'
        . '<div class="store-name">' . pdfEsc($namaToko) . '</div>'
        . '<div class="store-address">' . $alamatHtml . '</div>'
        . ($kontakHtml !== ''
            ? '<div class="store-contact">' . $kontakHtml . '</div>'
            : '')
        . '</td>'
        . '<td class="kop-divider" width="2%" align="center">'
        . '<div class="kop-divider-line"></div>'
        . '</td>'
        . '<td class="kop-logo kop-logo-kanan" width="15%" align="right">'
        . $logoHtml
        . '</td>'
        . '</tr></table>';

    $html .= '<div class="kop-line-navy"></div>'
        . '<div class="kop-line-gold"></div>';

    /* =========================================================
       JUDUL DAN PERIODE LAPORAN
    ========================================================= */

    $html .= '<div class="report-title">' . pdfEsc($judul) . '</div>'
        . '<div class="title-underline"></div>'
        . '<div class="period">' . pdfEsc($periode) . '</div>';

    /* =========================================================
       RINGKASAN
    ========================================================= */

    $html .= '<table class="summary"><tr>'
        . $summaryHtml
        . '</tr></table>';

    /* =========================================================
       TABEL DATA
    ========================================================= */

    $html .= '<table class="data">'
        . '<thead><tr>' . $tableHeader . '</tr></thead>'
        . '<tbody>' . $tableRows . '</tbody>'
        . '</table>';

    /* =========================================================
       FOOTER
    ========================================================= */

    $html .= '<div class="footer">Dicetak pada '
        . date('d-m-Y H:i')
        . '</div>';

    $html .= '</body></html>';

    /* =========================================================
       GENERATE DAN UNDUH PDF
    ========================================================= */

    $options = new Options();

    $options->set('isRemoteEnabled', false);
    $options->set('defaultFont', 'DejaVu Sans');

    $dompdf = new Dompdf($options);

    $dompdf->loadHtml($html, 'UTF-8');
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();

    $canvas = $dompdf->getCanvas();
    $fontMetrics = $dompdf->getFontMetrics();

    $footerFont = $fontMetrics->getFont(
        'DejaVu Sans',
        'normal'
    );

    $canvas->page_text(
        $canvas->get_width() - 125,
        $canvas->get_height() - 23,
        'Halaman {PAGE_NUM} dari {PAGE_COUNT}',
        $footerFont,
        7,
        [0.39, 0.45, 0.54]
    );

    /*
     * Render PDF menjadi data biner terlebih dahulu.
     */
    $pdfOutput = $dompdf->output();

    /* =========================================================
       CATAT LOG AKTIVITAS EKSPOR PDF
    ========================================================= */

    try {
        catatLogAktivitas(
            $conn,
            'laporan',
            'Ekspor Laporan PDF',
            "Mengekspor {$judul}; jenis laporan: {$jenis}; {$periode}; nama file: {$filename}."
        );
    } catch (Throwable $logError) {
        /*
         * Kegagalan log tidak membatalkan proses ekspor laporan.
         */
    }

    /* =========================================================
       KIRIM PDF KE BROWSER
    ========================================================= */

    /*
     * Buang output tidak sengaja dari include, warning,
     * atau whitespace sebelum mengirim file PDF.
     */
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    header('Content-Type: application/pdf');

    header(
        'Content-Disposition: attachment; filename="'
        . basename($filename)
        . '"'
    );

    header('Content-Transfer-Encoding: binary');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');

    echo $pdfOutput;
    exit;

} catch (Throwable $e) {

    error_log(
        'Export PDF laporan gagal: ' . $e->getMessage()
        . ' | File: ' . $e->getFile()
        . ' | Baris: ' . $e->getLine()
    );

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    http_response_code(500);

    header('Content-Type: text/html; charset=UTF-8');

    $isLocal = in_array(
        strtolower((string)($_SERVER['SERVER_NAME'] ?? '')),
        ['localhost', '127.0.0.1', '::1'],
        true
    );

    $debugHtml = '';

    if ($isLocal) {
        $debugDetail = $e->getMessage()
            . "\nFile: " . $e->getFile()
            . "\nBaris: " . $e->getLine();

        $debugHtml = '<h3>Detail error (khusus localhost)</h3>'
            . '<pre style="white-space:pre-wrap;'
            . 'background:#f4f6f8;padding:12px;'
            . 'border-radius:6px;color:#9b1c1c">'
            . htmlspecialchars($debugDetail, ENT_QUOTES, 'UTF-8')
            . '</pre>';
    }

    exit(
        '<!doctype html><html lang="id"><meta charset="UTF-8">'
        . '<title>PDF Gagal Dibuat</title>'
        . '<body style="font-family:Arial,sans-serif;'
        . 'padding:24px;color:#233044">'
        . '<h2>PDF belum dapat dibuat</h2>'
        . '<p>Silakan coba kembali. Jika masalah berlanjut, '
        . 'hubungi administrator.</p>'
        . $debugHtml
        . '</body></html>'
    );
}