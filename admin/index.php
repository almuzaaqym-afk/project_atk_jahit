
<?php
session_start();

if (!isset($_SESSION['id_admin'])) {
    header("Location: login.php");
    exit;
}

require_once "../config/koneksi.php";

/* =========================================================
   STATISTIK DASHBOARD
========================================================= */

function dashboardGetTotal(mysqli $conn, string $sql, string $field = 'total') {
    $result = mysqli_query($conn, $sql);
    if (!$result) {
        return 0;
    }

    $row = mysqli_fetch_assoc($result);
    return $row[$field] ?? 0;
}

$totalProduk = (int) dashboardGetTotal($conn, "SELECT COUNT(*) AS total FROM produk");
$totalStok = (int) dashboardGetTotal($conn, "SELECT COALESCE(SUM(stok), 0) AS total FROM produk");
$stokMenipis = (int) dashboardGetTotal($conn, "SELECT COUNT(*) AS total FROM produk WHERE stok > 0 AND stok <= stok_minimum");
$stokHabis = (int) dashboardGetTotal($conn, "SELECT COUNT(*) AS total FROM produk WHERE stok <= 0");
$totalPelanggan = (int) dashboardGetTotal($conn, "SELECT COUNT(*) AS total FROM pelanggan");
$totalPesanan = (int) dashboardGetTotal($conn, "SELECT COUNT(*) AS total FROM pesanan");
$pesananDiproses = (int) dashboardGetTotal($conn, "SELECT COUNT(*) AS total FROM pesanan WHERE status_pesanan = 'Diproses'");

$totalOmzet = (float) dashboardGetTotal(
    $conn,
    "SELECT COALESCE(SUM(total_harga), 0) AS total
     FROM pesanan
     WHERE status_pesanan NOT IN ('Dibatalkan')"
);

$omzetBulan = (float) dashboardGetTotal(
    $conn,
    "SELECT COALESCE(SUM(total_harga), 0) AS total
     FROM pesanan
     WHERE status_pesanan NOT IN ('Dibatalkan')
       AND MONTH(tanggal_pesanan) = MONTH(CURDATE())
       AND YEAR(tanggal_pesanan) = YEAR(CURDATE())"
);


/* =========================================================
   PILIHAN PERIODE GRAFIK
========================================================= */

$pilihanPeriode = [
    '7hari'   => ['label' => '7 Hari',     'mode' => 'day',   'days' => 7],
    '1bulan'  => ['label' => '1 Bulan',    'mode' => 'day',   'days' => 30],
    '3bulan'  => ['label' => '3 Bulan',    'mode' => 'week',  'weeks' => 12],
    '6bulan'  => ['label' => '6 Bulan',    'mode' => 'month', 'months' => 6],
    '1tahun'  => ['label' => '1 Tahun',    'mode' => 'month', 'months' => 12],
    'semua'   => ['label' => 'Semua Data', 'mode' => 'all']
];

$periodeGrafik = (string) ($_GET['grafik_periode'] ?? '7hari');

if (!isset($pilihanPeriode[$periodeGrafik])) {
    $periodeGrafik = '7hari';
}

$konfigurasiGrafik = $pilihanPeriode[$periodeGrafik];
$modeGrafik = $konfigurasiGrafik['mode'];

$hariIni = date('Y-m-d');
$awalBulanIni = date('Y-m-01');
$awalGrafik = $hariIni;

$bucketGrafik = [];
$labelPeriodeGrafik = $konfigurasiGrafik['label'];


/* =========================================================
   MEMBENTUK LABEL TANGGAL GRAFIK
========================================================= */

if ($modeGrafik === 'day') {
    $jumlahHariGrafik = (int) $konfigurasiGrafik['days'];

    $awalGrafik = date(
        'Y-m-d',
        strtotime('-' . ($jumlahHariGrafik - 1) . ' days', strtotime($hariIni))
    );

    for (
        $tanggal = $awalGrafik;
        $tanggal <= $hariIni;
        $tanggal = date('Y-m-d', strtotime($tanggal . ' +1 day'))
    ) {
        $bucketGrafik[] = [
            'key' => $tanggal,
            'date' => $tanggal,
            'label' => date('d M', strtotime($tanggal))
        ];
    }

} elseif ($modeGrafik === 'week') {
    $seninMingguIni = date(
        'Y-m-d',
        strtotime('monday this week', strtotime($hariIni))
    );

    $awalGrafik = date(
        'Y-m-d',
        strtotime('-11 weeks', strtotime($seninMingguIni))
    );

    $tanggal = $awalGrafik;

    for ($i = 0; $i < 12; $i++) {
        $bucketGrafik[] = [
            'key' => date('oW', strtotime($tanggal)),
            'date' => $tanggal,
            'label' => date('d M', strtotime($tanggal))
        ];

        $tanggal = date(
            'Y-m-d',
            strtotime($tanggal . ' +1 week')
        );
    }

} else {
    if ($modeGrafik === 'month') {
        $jumlahBulanGrafik = (int) $konfigurasiGrafik['months'];

        $awalGrafik = date(
            'Y-m-01',
            strtotime(
                '-' . ($jumlahBulanGrafik - 1) . ' months',
                strtotime($awalBulanIni)
            )
        );

    } else {
        $qAwalDataGrafik = mysqli_query(
            $conn,
            "SELECT MIN(DATE(tanggal_pesanan)) AS tanggal
             FROM pesanan
             WHERE status_pesanan NOT IN ('Dibatalkan')
               AND tanggal_pesanan IS NOT NULL"
        );

        $rowAwalDataGrafik = $qAwalDataGrafik
            ? mysqli_fetch_assoc($qAwalDataGrafik)
            : null;

        $tanggalAwalData = $rowAwalDataGrafik['tanggal'] ?? null;

        if (
            $tanggalAwalData &&
            preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggalAwalData)
        ) {
            $awalGrafik = date(
                'Y-m-01',
                strtotime($tanggalAwalData)
            );
        } else {
            $awalGrafik = $awalBulanIni;
        }
    }

    for (
        $tanggal = $awalGrafik;
        $tanggal <= $awalBulanIni;
        $tanggal = date('Y-m-01', strtotime($tanggal . ' +1 month'))
    ) {
        $bucketGrafik[] = [
            'key' => date('Y-m', strtotime($tanggal)),
            'date' => $tanggal,
            'label' => date('M Y', strtotime($tanggal))
        ];
    }
}


/* =========================================================
   QUERY OMZET SESUAI PERIODE
========================================================= */

if ($modeGrafik === 'day') {
    $sqlBucketGrafik = "DATE(tanggal_pesanan)";
    $sqlGroupGrafik = "DATE(tanggal_pesanan)";

} elseif ($modeGrafik === 'week') {
    $sqlBucketGrafik = "YEARWEEK(tanggal_pesanan, 3)";
    $sqlGroupGrafik = "YEARWEEK(tanggal_pesanan, 3)";

} else {
    $sqlBucketGrafik = "DATE_FORMAT(tanggal_pesanan, '%Y-%m')";
    $sqlGroupGrafik = "DATE_FORMAT(tanggal_pesanan, '%Y-%m')";
}

$awalGrafikSql = mysqli_real_escape_string($conn, $awalGrafik);
$omzetPerBucket = [];

$qGrafikOmzet = mysqli_query(
    $conn,
    "SELECT
        {$sqlBucketGrafik} AS bucket,
        COALESCE(SUM(total_harga), 0) AS omzet
     FROM pesanan
     WHERE status_pesanan NOT IN ('Dibatalkan')
       AND tanggal_pesanan >= '{$awalGrafikSql} 00:00:00'
       AND tanggal_pesanan < DATE_ADD(CURDATE(), INTERVAL 1 DAY)
     GROUP BY {$sqlGroupGrafik}
     ORDER BY {$sqlGroupGrafik} ASC"
);

if ($qGrafikOmzet) {
    while ($row = mysqli_fetch_assoc($qGrafikOmzet)) {
        $omzetPerBucket[(string) $row['bucket']] = (float) $row['omzet'];
    }
}

$grafikLabels = [];
$grafikOmzet = [];

foreach ($bucketGrafik as $bucket) {
    $nilaiOmzet = (float) ($omzetPerBucket[$bucket['key']] ?? 0);

    $grafikLabels[] = $bucket['label'];
    $grafikOmzet[] = $nilaiOmzet;
}

$totalOmzetPeriode = array_sum($grafikOmzet);


/* =========================================================
   FORMAT RUPIAH
========================================================= */

function dashboardFormatRupiahRingkas($angka) {
    $angka = (float) $angka;

    if ($angka >= 1000000000) {
        return 'Rp ' . number_format($angka / 1000000000, 1, ',', '.') . ' M';
    }

    if ($angka >= 1000000) {
        return 'Rp ' . number_format($angka / 1000000, 1, ',', '.') . ' jt';
    }

    if ($angka >= 1000) {
        return 'Rp ' . number_format($angka / 1000, 0, ',', '.') . ' rb';
    }

    return 'Rp ' . number_format($angka, 0, ',', '.');
}


/* =========================================================
   PESANAN TERBARU
========================================================= */

$pesananTerbaru = [];

$qPesananTerbaru = mysqli_query(
    $conn,
    "SELECT
        p.id_pesanan,
        p.nomor_pesanan,
        p.tanggal_pesanan,
        p.total_harga,
        p.status_pesanan,
        pl.nama
     FROM pesanan p
     LEFT JOIN pelanggan pl ON p.id_pelanggan = pl.id_pelanggan
     ORDER BY p.id_pesanan DESC
     LIMIT 6"
);

if ($qPesananTerbaru) {
    while ($row = mysqli_fetch_assoc($qPesananTerbaru)) {
        $pesananTerbaru[] = $row;
    }
}


/* =========================================================
   PRODUK STOK MENIPIS
========================================================= */

$produkStokMenipis = [];

$qProdukStok = mysqli_query(
    $conn,
    "SELECT
        id_produk,
        nama_produk,
        stok,
        stok_minimum,
        lokasi_rak
     FROM produk
     WHERE stok <= stok_minimum
     ORDER BY stok ASC, nama_produk ASC
     LIMIT 6"
);

if ($qProdukStok) {
    while ($row = mysqli_fetch_assoc($qProdukStok)) {
        $produkStokMenipis[] = $row;
    }
}


/* =========================================================
   PEMBELIAN TERBARU
========================================================= */

$pembelianTerbaru = [];

$qPembelian = mysqli_query(
    $conn,
    "SELECT
        p.id_pembelian,
        p.nomor_pembelian,
        p.tanggal_pembelian,
        p.total_harga,
        p.status,
        s.nama_supplier
     FROM pembelian p
     LEFT JOIN supplier s ON p.id_supplier = s.id_supplier
     ORDER BY p.id_pembelian DESC
     LIMIT 5"
);

if ($qPembelian) {
    while ($row = mysqli_fetch_assoc($qPembelian)) {
        $pembelianTerbaru[] = $row;
    }
}


/* =========================================================
   HELPER STATUS PESANAN
========================================================= */

function statusPesananClass($status) {
    $status = strtolower(trim((string) $status));

    if (
        strpos($status, 'verifikasi') !== false ||
        strpos($status, 'konfirmasi') !== false
    ) {
        return 'verification';
    }

    if (
        strpos($status, 'menunggu') !== false ||
        strpos($status, 'belum') !== false
    ) {
        return 'waiting';
    }

    if (
        strpos($status, 'proses') !== false ||
        strpos($status, 'diproses') !== false
    ) {
        return 'process';
    }

    if (
        strpos($status, 'pickup') !== false ||
        strpos($status, 'siap') !== false
    ) {
        return 'pickup';
    }

    if (
        strpos($status, 'selesai') !== false ||
        strpos($status, 'berhasil') !== false
    ) {
        return 'success';
    }

    if (
        strpos($status, 'batal') !== false ||
        strpos($status, 'gagal') !== false
    ) {
        return 'danger';
    }

    return 'default';
}


/* =========================================================
   HTML PESANAN TERBARU
========================================================= */

ob_start();

if (!empty($pesananTerbaru)):
?>
<table class="dashboard-table">
    <thead>
        <tr>
            <th>Pesanan</th>
            <th>Pelanggan</th>
            <th>Total</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($pesananTerbaru as $pesanan): ?>
        <tr>
            <td>
                <div class="order-number">
                    <?= htmlspecialchars($pesanan['nomor_pesanan'] ?? '-', ENT_QUOTES, 'UTF-8'); ?>
                </div>
                <small>
                    <?= htmlspecialchars($pesanan['tanggal_pesanan'] ?? '-', ENT_QUOTES, 'UTF-8'); ?>
                </small>
            </td>

            <td>
                <span class="customer-name">
                    <?= htmlspecialchars($pesanan['nama'] ?? 'Pelanggan', ENT_QUOTES, 'UTF-8'); ?>
                </span>
            </td>

            <td>
                <strong>
                    Rp <?= number_format((float) $pesanan['total_harga'], 0, ',', '.'); ?>
                </strong>
            </td>

            <td>
                <span class="status-badge <?= statusPesananClass($pesanan['status_pesanan'] ?? ''); ?>">
                    <?= htmlspecialchars($pesanan['status_pesanan'] ?? '-', ENT_QUOTES, 'UTF-8'); ?>
                </span>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<?php else: ?>

<div class="empty-dashboard">Belum ada pesanan.</div>

<?php
endif;

$htmlPesananTerbaru = ob_get_clean();


/* =========================================================
   HTML STOK MENIPIS
========================================================= */

ob_start();

if (!empty($produkStokMenipis)):
?>
<div class="stock-list">
    <?php foreach ($produkStokMenipis as $produk): ?>
        <div class="stock-item">
            <div>
                <div class="stock-name">
                    <?= htmlspecialchars($produk['nama_produk'], ENT_QUOTES, 'UTF-8'); ?>
                </div>

                <div class="stock-rack">
                    Rak: <?= htmlspecialchars($produk['lokasi_rak'] ?: '-', ENT_QUOTES, 'UTF-8'); ?>
                </div>
            </div>

            <div class="stock-number">
                <strong><?= number_format((int) $produk['stok']); ?></strong>
                <span>Min: <?= number_format((int) $produk['stok_minimum']); ?></span>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php else: ?>

<div class="safe-stock">Semua stok dalam kondisi aman.</div>

<?php
endif;

$htmlProdukStok = ob_get_clean();


/* =========================================================
   HTML PEMBELIAN TERBARU
========================================================= */

ob_start();

if (!empty($pembelianTerbaru)):
?>
<div class="purchase-list">
    <?php foreach ($pembelianTerbaru as $pembelian): ?>
        <div class="purchase-item">
            <div>
                <div class="purchase-number">
                    <?= htmlspecialchars($pembelian['nomor_pembelian'], ENT_QUOTES, 'UTF-8'); ?>
                </div>

                <div class="purchase-supplier">
                    <?= htmlspecialchars($pembelian['nama_supplier'] ?? '-', ENT_QUOTES, 'UTF-8'); ?>
                    ·
                    <?= htmlspecialchars($pembelian['tanggal_pembelian'] ?? '-', ENT_QUOTES, 'UTF-8'); ?>
                </div>
            </div>

            <div class="purchase-right">
                <div class="purchase-price">
                    Rp <?= number_format((float) $pembelian['total_harga'], 0, ',', '.'); ?>
                </div>

                <div class="purchase-status">
                    <?= htmlspecialchars($pembelian['status'] ?? '-', ENT_QUOTES, 'UTF-8'); ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php else: ?>

<div class="empty-dashboard">Belum ada data pembelian.</div>

<?php
endif;

$htmlPembelianTerbaru = ob_get_clean();


/* =========================================================
   HTML RINGKASAN TOKO
========================================================= */

ob_start();
?>
<div class="stock-list">
    <div class="stock-item">
        <div>
            <div class="stock-name">Pelanggan</div>
            <div class="stock-rack">Total pelanggan terdaftar</div>
        </div>
        <div class="stock-number">
            <strong style="color:#102f4b;">
                <?= number_format($totalPelanggan); ?>
            </strong>
        </div>
    </div>

    <div class="stock-item">
        <div>
            <div class="stock-name">Stok Habis</div>
            <div class="stock-rack">Produk yang perlu diisi</div>
        </div>
        <div class="stock-number">
            <strong><?= number_format($stokHabis); ?></strong>
        </div>
    </div>

    <div class="stock-item">
        <div>
            <div class="stock-name">Stok Menipis</div>
            <div class="stock-rack">Di bawah batas minimum</div>
        </div>
        <div class="stock-number">
            <strong><?= number_format($stokMenipis); ?></strong>
        </div>
    </div>

    <div class="stock-item">
        <div>
            <div class="stock-name">Pesanan Diproses</div>
            <div class="stock-rack">Pesanan yang sedang dikerjakan</div>
        </div>
        <div class="stock-number">
            <strong style="color:#6847a6;">
                <?= number_format($pesananDiproses); ?>
            </strong>
        </div>
    </div>
</div>

<?php
$htmlRingkasan = ob_get_clean();


/* =========================================================
   HTML GRAFIK SVG
========================================================= */

ob_start();

$grafikLebar = 900;
$grafikTinggi = 315;
$grafikXAwal = 82;
$grafikXAkhir = 875;
$grafikYAtas = 28;
$grafikYBawah = 238;

$grafikNilaiMaksimum = !empty($grafikOmzet)
    ? max($grafikOmzet)
    : 0;

$grafikSkalaAtas = $grafikNilaiMaksimum > 0
    ? $grafikNilaiMaksimum * 1.15
    : 1;

$jumlahTitikGrafik = count($grafikOmzet);

$grafikJarakX = $jumlahTitikGrafik > 1
    ? ($grafikXAkhir - $grafikXAwal) / ($jumlahTitikGrafik - 1)
    : 0;

$grafikTitik = [];

foreach ($grafikOmzet as $index => $nilai) {
    $x = $grafikXAwal + ($grafikJarakX * $index);

    $y = $grafikYBawah -
        (((float) $nilai / $grafikSkalaAtas) *
        ($grafikYBawah - $grafikYAtas));

    $grafikTitik[] = [
        'x' => $x,
        'y' => $y,
        'nilai' => (float) $nilai,
        'label' => $grafikLabels[$index] ?? ''
    ];
}

$grafikPathGaris = '';

foreach ($grafikTitik as $index => $titik) {
    $grafikPathGaris .=
        ($index === 0 ? 'M ' : ' L ') .
        round($titik['x'], 2) . ' ' .
        round($titik['y'], 2);
}

$grafikPathArea = $grafikPathGaris;

if (!empty($grafikTitik)) {
    $grafikPathArea .=
        ' L ' .
        round($grafikTitik[count($grafikTitik) - 1]['x'], 2) .
        ' ' . $grafikYBawah;

    $grafikPathArea .=
        ' L ' .
        round($grafikTitik[0]['x'], 2) .
        ' ' . $grafikYBawah . ' Z';
}

$stepLabelGrafik = max(
    1,
    (int) ceil(max(1, $jumlahTitikGrafik) / 8)
);
?>

<div class="dashboard-chart-card">

    <div class="dashboard-chart-heading">

        <div class="dashboard-chart-title">
            <span class="dashboard-chart-eyebrow">
                ANALISIS PENJUALAN
            </span>

            <h2>Grafik Omzet Toko</h2>

            <p>
                Bandingkan omzet berdasarkan periode yang kamu pilih.
            </p>
        </div>

        <div class="dashboard-chart-controls">
            <label for="dashboardGrafikPeriode">
                Periode grafik
            </label>

            <select
                id="dashboardGrafikPeriode"
                class="dashboard-chart-select"
            >
                <?php foreach ($pilihanPeriode as $keyPeriode => $periodeItem): ?>
                    <option
                        value="<?= htmlspecialchars($keyPeriode, ENT_QUOTES, 'UTF-8'); ?>"
                        <?= $periodeGrafik === $keyPeriode ? 'selected' : ''; ?>
                    >
                        <?= htmlspecialchars($periodeItem['label'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="dashboard-chart-total">
            <span>
                Total <?= htmlspecialchars($labelPeriodeGrafik, ENT_QUOTES, 'UTF-8'); ?>
            </span>

            <strong>
                Rp <?= number_format($totalOmzetPeriode, 0, ',', '.'); ?>
            </strong>
        </div>

    </div>

    <div class="dashboard-chart-legend">
        <span class="dashboard-chart-legend-dot"></span>
        <span>Omzet berdasarkan periode</span>

        <span class="dashboard-chart-live">
            <span></span>
            Pembaruan otomatis
        </span>
    </div>

    <div class="dashboard-chart-scroll">

        <svg
            class="dashboard-sales-chart"
            viewBox="0 0 <?= $grafikLebar; ?> <?= $grafikTinggi; ?>"
            role="img"
            aria-label="Grafik omzet periode <?= htmlspecialchars($labelPeriodeGrafik, ENT_QUOTES, 'UTF-8'); ?>"
            xmlns="http://www.w3.org/2000/svg"
        >

            <defs>
                <linearGradient
                    id="dashboardAreaGradient"
                    x1="0"
                    y1="0"
                    x2="0"
                    y2="1"
                >
                    <stop
                        offset="0%"
                        stop-color="#d8b675"
                        stop-opacity="0.4"
                    />

                    <stop
                        offset="100%"
                        stop-color="#d8b675"
                        stop-opacity="0.015"
                    />
                </linearGradient>

                <linearGradient
                    id="dashboardLineGradient"
                    x1="0"
                    y1="0"
                    x2="1"
                    y2="0"
                >
                    <stop offset="0%" stop-color="#183b5b" />
                    <stop offset="100%" stop-color="#c09b53" />
                </linearGradient>
            </defs>

            <?php for ($i = 0; $i <= 4; $i++): ?>

                <?php
                $gridY = $grafikYBawah -
                    ($i / 4) * ($grafikYBawah - $grafikYAtas);

                $gridNilai = ($i / 4) * $grafikSkalaAtas;
                ?>

                <line
                    x1="<?= $grafikXAwal; ?>"
                    y1="<?= round($gridY, 2); ?>"
                    x2="<?= $grafikXAkhir; ?>"
                    y2="<?= round($gridY, 2); ?>"
                    stroke="#e8edf3"
                    stroke-width="1"
                    stroke-dasharray="4 5"
                />

                <text
                    x="69"
                    y="<?= round($gridY + 4, 2); ?>"
                    text-anchor="end"
                    font-size="11"
                    fill="#8794a7"
                >
                    <?= htmlspecialchars(dashboardFormatRupiahRingkas($gridNilai), ENT_QUOTES, 'UTF-8'); ?>
                </text>

            <?php endfor; ?>

            <?php if (!empty($grafikTitik)): ?>

                <path
                    d="<?= $grafikPathArea; ?>"
                    fill="url(#dashboardAreaGradient)"
                    class="dashboard-chart-area"
                />

                <path
                    d="<?= $grafikPathGaris; ?>"
                    fill="none"
                    stroke="url(#dashboardLineGradient)"
                    stroke-width="3.5"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    vector-effect="non-scaling-stroke"
                    class="dashboard-chart-line"
                />

                <?php foreach ($grafikTitik as $indexTitik => $titik): ?>

                    <circle
                        cx="<?= round($titik['x'], 2); ?>"
                        cy="<?= round($titik['y'], 2); ?>"
                        r="<?= $jumlahTitikGrafik > 40 ? '3' : '5'; ?>"
                        fill="#ffffff"
                        stroke="#bd9851"
                        stroke-width="2.5"
                        class="dashboard-chart-point"
                    >
                        <title>
                            <?= htmlspecialchars(
                                $titik['label'] . ': Rp ' .
                                number_format($titik['nilai'], 0, ',', '.'),
                                ENT_QUOTES,
                                'UTF-8'
                            ); ?>
                        </title>
                    </circle>

                    <?php if (
                        $indexTitik % $stepLabelGrafik === 0 ||
                        $indexTitik === $jumlahTitikGrafik - 1
                    ): ?>
                        <text
                            x="<?= round($titik['x'], 2); ?>"
                            y="273"
                            text-anchor="middle"
                            font-size="11"
                            fill="#8290a3"
                        >
                            <?= htmlspecialchars($titik['label'], ENT_QUOTES, 'UTF-8'); ?>
                        </text>
                    <?php endif; ?>

                <?php endforeach; ?>

            <?php endif; ?>

        </svg>

    </div>

    <div class="dashboard-chart-footer">
        <span>
            Omzet dihitung dari pesanan yang tidak berstatus Dibatalkan.
        </span>

        <span>
            Data per <?= htmlspecialchars($labelPeriodeGrafik, ENT_QUOTES, 'UTF-8'); ?>
        </span>
    </div>

</div>

<?php
$htmlGrafik = ob_get_clean();


/* =========================================================
   DATA GRAFIK UNTUK SINKRONISASI
========================================================= */

$dataGrafikAwal = [
    'periode' => $periodeGrafik,
    'labels' => $grafikLabels,
    'omzet' => $grafikOmzet
];

$signatureGrafikAwal = json_encode(
    $dataGrafikAwal,
    JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
);


/* =========================================================
   MODE SINKRONISASI JSON
========================================================= */

if (isset($_GET['_live_dashboard'])) {
    header("Content-Type: application/json; charset=UTF-8");
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Pragma: no-cache");

    echo json_encode(
        [
            'success' => true,

            'stats' => [
                'totalProduk' => number_format($totalProduk),
                'totalStok' => number_format($totalStok),
                'stokMenipis' => number_format($stokMenipis),
                'stokHabis' => number_format($stokHabis),
                'totalPesanan' => number_format($totalPesanan),
                'pesananDiproses' => number_format($pesananDiproses),
                'totalOmzet' => number_format($totalOmzet, 0, ',', '.'),
                'omzetBulan' => number_format($omzetBulan, 0, ',', '.')
            ],

            'html' => [
                'pesanan' => $htmlPesananTerbaru,
                'stok' => $htmlProdukStok,
                'pembelian' => $htmlPembelianTerbaru,
                'ringkasan' => $htmlRingkasan,
                'grafik' => $htmlGrafik
            ],

            'grafik' => $dataGrafikAwal
        ],
        JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
    );

    exit;
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Dashboard Admin - Toko ATK &amp; Alat Jahit
    </title>

    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">

    <style>
        .dashboard-chart-card {
            min-width: 0;
            margin-bottom: 25px;
            overflow: hidden;
            background: #fff;
            border: 1px solid #e7ebf1;
            border-radius: 18px;
            box-shadow: 0 8px 28px rgba(19,39,67,.055);
            animation: dashFadeUp .7s ease both;
            animation-delay: .28s;
        }

        .dashboard-chart-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            padding: 24px 26px 18px;
        }

        .dashboard-chart-title {
            min-width: 0;
            flex: 1;
        }

        .dashboard-chart-eyebrow {
            display: block;
            margin-bottom: 8px;
            color: #b28b45;
            font-size: 9px;
            font-weight: 800;
            letter-spacing: 2px;
        }

        .dashboard-chart-title h2 {
            margin: 0;
            color: #102640;
            font-size: 19px;
            font-weight: 800;
            line-height: 1.45;
        }

        .dashboard-chart-title p {
            margin: 6px 0 0;
            color: #8995a6;
            font-size: 12px;
            line-height: 1.6;
        }

        .dashboard-chart-controls {
            display: flex;
            flex-direction: column;
            gap: 7px;
            flex-shrink: 0;
        }

        .dashboard-chart-controls label {
            color: #76869b;
            font-size: 10px;
            font-weight: 700;
        }

        .dashboard-chart-select {
            min-width: 132px;
            max-width: 190px;
            padding: 10px 32px 10px 12px;
            color: #18334e;
            background: #f8fafc;
            border: 1px solid #dfe6ee;
            border-radius: 10px;
            font-family: inherit;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            outline: none;
        }

        .dashboard-chart-select:focus {
            border-color: #c9a45f;
            box-shadow: 0 0 0 3px rgba(201,164,95,.14);
        }

        .dashboard-chart-total {
            flex-shrink: 0;
            padding: 14px 17px;
            background: linear-gradient(145deg,#0b1d33,#193d5c);
            border: 1px solid #274964;
            border-radius: 13px;
        }

        .dashboard-chart-total span {
            display: block;
            margin-bottom: 7px;
            color: #cad8e6;
            font-size: 10px;
        }

        .dashboard-chart-total strong {
            display: block;
            color: #f2d9a4;
            font-size: clamp(16px,2vw,22px);
            font-weight: 800;
            line-height: 1.35;
            font-variant-numeric: tabular-nums;
            overflow-wrap: anywhere;
        }

        .dashboard-chart-legend {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 0 26px 14px;
            color: #718095;
            font-size: 11px;
        }

        .dashboard-chart-legend-dot {
            width: 9px;
            height: 9px;
            flex-shrink: 0;
            background: #c5a15e;
            border-radius: 50%;
        }

        .dashboard-chart-live {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-left: auto;
            color: #718095;
            font-size: 10px;
        }

        .dashboard-chart-live span {
            width: 7px;
            height: 7px;
            background: #37a775;
            border-radius: 50%;
            box-shadow: 0 0 0 3px rgba(55,167,117,.12);
            animation: dashboardLivePulse 1.8s ease-in-out infinite;
        }

        .dashboard-chart-scroll {
            width: 100%;
            padding: 0 15px 0 5px;
        }

        .dashboard-sales-chart {
            display: block;
            width: 100%;
            height: auto;
            min-height: 230px;
            overflow: visible;
        }

        .dashboard-chart-area {
            animation: dashboardChartAreaIn 1s ease both;
        }

        .dashboard-chart-line {
            stroke-dasharray: 1600;
            stroke-dashoffset: 1600;
            animation: dashboardChartLineIn 1.6s ease forwards;
        }

        .dashboard-chart-point {
            transform-box: fill-box;
            transform-origin: center;
            transition: r .2s ease, fill .2s ease;
        }

        .dashboard-chart-point:hover {
            r: 8;
            fill: #f7edda;
        }

        .dashboard-chart-footer {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 8px 18px;
            padding: 14px 25px 18px;
            color: #8995a6;
            background: #fbfcfe;
            border-top: 1px solid #edf0f5;
            font-size: 10px;
            line-height: 1.65;
        }

        @keyframes dashboardChartLineIn {
            to {
                stroke-dashoffset: 0;
            }
        }

        @keyframes dashboardChartAreaIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes dashboardLivePulse {
            0%, 100% {
                box-shadow: 0 0 0 3px rgba(55,167,117,.12);
            }

            50% {
                box-shadow: 0 0 0 5px rgba(55,167,117,.06);
            }
        }

        @media (max-width: 900px) {
            .dashboard-chart-heading {
                flex-wrap: wrap;
            }

            .dashboard-chart-title {
                flex-basis: 100%;
            }

            .dashboard-chart-controls {
                flex: 1;
            }

            .dashboard-chart-total {
                flex: 1;
            }
        }

        @media (max-width: 650px) {
            .dashboard-chart-card {
                margin-bottom: 17px;
                border-radius: 14px;
            }

            .dashboard-chart-heading {
                align-items: stretch;
                flex-direction: column;
                gap: 14px;
                padding: 20px 17px 16px;
            }

            .dashboard-chart-title h2 {
                font-size: 17px;
            }

            .dashboard-chart-title p {
                font-size: 11px;
            }

            .dashboard-chart-controls {
                width: 100%;
            }

            .dashboard-chart-select {
                width: 100%;
                max-width: none;
                box-sizing: border-box;
            }

            .dashboard-chart-total {
                width: 100%;
                box-sizing: border-box;
                padding: 12px 14px;
            }

            .dashboard-chart-total strong {
                font-size: 20px;
            }

            .dashboard-chart-legend {
                padding: 0 17px 12px;
            }

            .dashboard-chart-scroll {
                padding: 0 7px 0 0;
            }

            .dashboard-sales-chart {
                min-height: 170px;
            }

            .dashboard-chart-footer {
                padding: 12px 17px 15px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .dashboard-chart-card,
            .dashboard-chart-card *,
            .dashboard-chart-card *::before,
            .dashboard-chart-card *::after {
                animation-duration: .01ms !important;
                animation-delay: 0ms !important;
                transition-duration: .01ms !important;
            }

            .dashboard-chart-line {
                stroke-dashoffset: 0;
            }
        }
    </style>
</head>

<body>

<div class="admin-layout">

    <?php include "includes/sidebar.php"; ?>

    <main class="admin-main">

        <?php include "includes/navbar.php"; ?>

        <section class="admin-content store-dashboard">

            <div class="welcome-box">
                <div>
                    <h2>
                        Selamat Datang,
                        <?= htmlspecialchars($_SESSION['nama_admin'] ?? 'Administrator', ENT_QUOTES, 'UTF-8'); ?>!
                    </h2>

                    <p>
                        Kelola Toko ATK &amp; Alat Jahit melalui dashboard admin.
                    </p>
                </div>

                <div class="welcome-date">
                    <?= date('d F Y'); ?>
                </div>
            </div>


            <!-- KARTU STATISTIK -->

            <div class="store-stats">

                <div class="store-stat">
                    <div class="store-stat-top">
                        <span class="store-stat-label">Produk</span>
                        <div class="store-stat-icon">P</div>
                    </div>

                    <div class="store-stat-number" id="dashTotalProduk">
                        <?= number_format($totalProduk); ?>
                    </div>

                    <div class="store-stat-info">
                        Total produk
                    </div>
                </div>


                <div class="store-stat">
                    <div class="store-stat-top">
                        <span class="store-stat-label">Stok</span>
                        <div class="store-stat-icon">S</div>
                    </div>

                    <div class="store-stat-number" id="dashTotalStok">
                        <?= number_format($totalStok); ?>
                    </div>

                    <div class="stock-stat-breakdown">
                        <div class="stock-stat-mini menipis">
                            <span>Stok Menipis</span>
                            <strong id="dashInfoStok">
                                <?= number_format($stokMenipis); ?>
                            </strong>
                        </div>

                        <div class="stock-stat-mini habis">
                            <span>Stok Habis</span>
                            <strong id="dashInfoStokHabis">
                                <?= number_format($stokHabis); ?>
                            </strong>
                        </div>
                    </div>
                </div>


                <div class="store-stat">
                    <div class="store-stat-top">
                        <span class="store-stat-label">Pesanan</span>
                        <div class="store-stat-icon">O</div>
                    </div>

                    <div class="store-stat-number" id="dashTotalPesanan">
                        <?= number_format($totalPesanan); ?>
                    </div>

                    <div class="store-stat-info" id="dashInfoPesanan">
                        <?= number_format($pesananDiproses); ?> diproses
                    </div>
                </div>


                <div class="store-stat money-stat">
                    <div class="store-stat-top">
                        <span class="store-stat-label">Omzet</span>
                        <div class="store-stat-icon">Rp</div>
                    </div>

                    <div class="store-stat-number" id="dashTotalOmzet">
                        Rp <?= number_format($totalOmzet, 0, ',', '.'); ?>
                    </div>

                    <div class="store-stat-info" id="dashInfoOmzet">
                        Bulan ini Rp <?= number_format($omzetBulan, 0, ',', '.'); ?>
                    </div>
                </div>

            </div>


            <!-- GRAFIK OMZET -->

            <div id="dashboardGrafikContent">
                <?= $htmlGrafik; ?>
            </div>


            <!-- PESANAN DAN STOK -->

            <div class="dashboard-columns">

                <div class="dashboard-card">
                    <div class="dashboard-card-header">
                        <h2>Pesanan Terbaru</h2>
                        <span>6 pesanan terakhir</span>
                    </div>

                    <div
                        class="dashboard-card-body"
                        id="dashboardPesananContent"
                    >
                        <?= $htmlPesananTerbaru; ?>
                    </div>
                </div>


                <div class="dashboard-card">
                    <div class="dashboard-card-header">
                        <h2>Stok Menipis</h2>
                        <span>Perlu diperhatikan</span>
                    </div>

                    <div id="dashboardStokContent">
                        <?= $htmlProdukStok; ?>
                    </div>
                </div>

            </div>


            <!-- PEMBELIAN DAN RINGKASAN -->

            <div class="dashboard-columns">

                <div class="dashboard-card">
                    <div class="dashboard-card-header">
                        <h2>Pembelian Terbaru</h2>
                        <span>5 transaksi terakhir</span>
                    </div>

                    <div id="dashboardPembelianContent">
                        <?= $htmlPembelianTerbaru; ?>
                    </div>
                </div>


                <div class="dashboard-card">
                    <div class="dashboard-card-header">
                        <h2>Ringkasan Toko</h2>
                        <span>Informasi</span>
                    </div>

                    <div id="dashboardRingkasanContent">
                        <?= $htmlRingkasan; ?>
                    </div>
                </div>

            </div>

        </section>

        <?php include "includes/footer.php"; ?>

    </main>

</div>


<script src="assets/js/admin.js"></script>
<script src="assets/js/dashboard.js"></script>

<script>
(function () {
    "use strict";

    let sedangMemuat = false;
    let refreshTertunda = false;

    const elemen = {
        totalProduk: document.getElementById("dashTotalProduk"),
        totalStok: document.getElementById("dashTotalStok"),
        infoStok: document.getElementById("dashInfoStok"),
        infoStokHabis: document.getElementById("dashInfoStokHabis"),
        totalPesanan: document.getElementById("dashTotalPesanan"),
        infoPesanan: document.getElementById("dashInfoPesanan"),
        totalOmzet: document.getElementById("dashTotalOmzet"),
        infoOmzet: document.getElementById("dashInfoOmzet"),

        grafik: document.getElementById("dashboardGrafikContent"),
        pesanan: document.getElementById("dashboardPesananContent"),
        stok: document.getElementById("dashboardStokContent"),
        pembelian: document.getElementById("dashboardPembelianContent"),
        ringkasan: document.getElementById("dashboardRingkasanContent")
    };

    const periodeAwal = <?= json_encode($periodeGrafik, JSON_UNESCAPED_UNICODE); ?>;
    const dataGrafikAwal = <?= $signatureGrafikAwal ?: '{}'; ?>;
    let signatureGrafikTerakhir = JSON.stringify(dataGrafikAwal);


    function ubahTeksJikaBerbeda(target, nilai) {
        if (
            target &&
            target.textContent.trim() !== String(nilai)
        ) {
            target.textContent = String(nilai);
        }
    }


    function ubahHTMLJikaBerbeda(target, html) {
        if (
            !target ||
            typeof html !== "string"
        ) {
            return;
        }

        const temp = document.createElement("div");
        temp.innerHTML = html;

        if (target.innerHTML.trim() !== temp.innerHTML.trim()) {
            target.innerHTML = html;
        }
    }


    function perbaruiGrafik(html, dataGrafik) {
        if (
            !elemen.grafik ||
            typeof html !== "string" ||
            !dataGrafik
        ) {
            return;
        }

        const signatureBaru = JSON.stringify(dataGrafik);

        if (
            signatureBaru === signatureGrafikTerakhir &&
            elemen.grafik.querySelector("svg")
        ) {
            return;
        }

        elemen.grafik.innerHTML = html;
        signatureGrafikTerakhir = signatureBaru;

        const selectPeriode = document.getElementById(
            "dashboardGrafikPeriode"
        );

        if (selectPeriode && dataGrafik.periode) {
            selectPeriode.value = dataGrafik.periode;
        }
    }


    async function sinkronDashboard() {
        if (sedangMemuat) {
            refreshTertunda = true;
            return;
        }

        if (document.visibilityState !== "visible") {
            return;
        }

        sedangMemuat = true;
        refreshTertunda = false;

        try {
            const url = new URL(window.location.href);

            const selectPeriode = document.getElementById(
                "dashboardGrafikPeriode"
            );

            const periodeAktif = selectPeriode
                ? selectPeriode.value
                : periodeAwal;

            url.searchParams.set("_live_dashboard", "1");
            url.searchParams.set("grafik_periode", periodeAktif);
            url.searchParams.set("_", Date.now().toString());

            const response = await fetch(url.toString(), {
                method: "GET",
                credentials: "same-origin",
                cache: "no-store",
                headers: {
                    "Accept": "application/json",
                    "X-Requested-With": "XMLHttpRequest"
                }
            });

            if (!response.ok) {
                return;
            }

            const contentType =
                response.headers.get("content-type") || "";

            if (!contentType.includes("application/json")) {
                return;
            }

            const data = await response.json();

            if (
                !data.success ||
                !data.stats ||
                !data.html
            ) {
                return;
            }

            const s = data.stats;

            ubahTeksJikaBerbeda(
                elemen.totalProduk,
                s.totalProduk
            );

            ubahTeksJikaBerbeda(
                elemen.totalStok,
                s.totalStok
            );

            ubahTeksJikaBerbeda(
                elemen.infoStok,
                s.stokMenipis
            );

            ubahTeksJikaBerbeda(
                elemen.infoStokHabis,
                s.stokHabis
            );

            ubahTeksJikaBerbeda(
                elemen.totalPesanan,
                s.totalPesanan
            );

            ubahTeksJikaBerbeda(
                elemen.infoPesanan,
                s.pesananDiproses + " diproses"
            );

            ubahTeksJikaBerbeda(
                elemen.totalOmzet,
                "Rp " + s.totalOmzet
            );

            ubahTeksJikaBerbeda(
                elemen.infoOmzet,
                "Bulan ini Rp " + s.omzetBulan
            );


            /* GRAFIK HANYA DIGANTI JIKA DATANYA BERUBAH */

            if (data.html.grafik && data.grafik) {
                perbaruiGrafik(
                    data.html.grafik,
                    data.grafik
                );
            }


            /* KONTEN DASHBOARD LAIN */

            ubahHTMLJikaBerbeda(
                elemen.pesanan,
                data.html.pesanan
            );

            ubahHTMLJikaBerbeda(
                elemen.stok,
                data.html.stok
            );

            ubahHTMLJikaBerbeda(
                elemen.pembelian,
                data.html.pembelian
            );

            ubahHTMLJikaBerbeda(
                elemen.ringkasan,
                data.html.ringkasan
            );

        } catch (error) {
            console.error(
                "Gagal menyinkronkan dashboard:",
                error
            );

        } finally {
            sedangMemuat = false;

            if (refreshTertunda) {
                refreshTertunda = false;

                window.setTimeout(
                    sinkronDashboard,
                    100
                );
            }
        }
    }


    /* =====================================================
       GANTI PERIODE GRAFIK
    ===================================================== */

    document.addEventListener("change", function (event) {
        if (
            event.target &&
            event.target.id === "dashboardGrafikPeriode"
        ) {
            sinkronDashboard();
        }
    });


    /* =====================================================
       REFRESH OTOMATIS SETIAP 5 DETIK
    ===================================================== */

    window.setInterval(
        sinkronDashboard,
        5000
    );

})();
</script>

</body>
</html>
