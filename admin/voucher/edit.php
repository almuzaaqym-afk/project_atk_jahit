<?php

session_start();

if (!isset($_SESSION['id_admin'])) {
    header("Location: ../login.php");
    exit;
}

require_once "../../config/koneksi.php";
require_once __DIR__ . "/../includes/log_aktivitas.php";

/* =========================================================
   AMBIL ID VOUCHER
========================================================= */

$id_voucher = (int) ($_GET['id'] ?? 0);

if ($id_voucher <= 0) {
    header("Location: index.php");
    exit;
}

/* =========================================================
   AMBIL DATA VOUCHER
========================================================= */

$stmt = mysqli_prepare(
    $conn,
    "SELECT *
     FROM voucher
     WHERE id_voucher = ?
     LIMIT 1"
);

if (!$stmt) {
    header("Location: index.php?status=gagal");
    exit;
}

mysqli_stmt_bind_param($stmt, "i", $id_voucher);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$voucher = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$voucher) {
    header("Location: index.php?status=gagal");
    exit;
}

$voucher_sebelumnya = $voucher;
$error = "";

/* =========================================================
   PROSES UPDATE
========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $kode_voucher = strtoupper(trim($_POST['kode_voucher'] ?? ''));
    $nama_voucher = trim($_POST['nama_voucher'] ?? '');
    $jenis_diskon = $_POST['jenis_diskon'] ?? '';
    $nilai_diskon = (float) ($_POST['nilai_diskon'] ?? 0);
    $minimal_belanja = (float) ($_POST['minimal_belanja'] ?? 0);
    $maksimal_diskon = trim($_POST['maksimal_diskon'] ?? '');
    $tanggal_mulai = trim($_POST['tanggal_mulai'] ?? '');
    $tanggal_berakhir = trim($_POST['tanggal_berakhir'] ?? '');
    $kuota = (int) ($_POST['kuota'] ?? 0);
    $status = $_POST['status'] ?? 'Aktif';

    /* =====================================================
       VALIDASI
    ===================================================== */

    if (
        $kode_voucher === '' ||
        $nama_voucher === '' ||
        $jenis_diskon === '' ||
        $tanggal_mulai === '' ||
        $tanggal_berakhir === ''
    ) {
        $error = "Semua field wajib diisi.";
    } elseif (!in_array($jenis_diskon, ['Persen', 'Nominal'], true)) {
        $error = "Jenis diskon tidak valid.";
    } elseif (!in_array($status, ['Aktif', 'Tidak Aktif'], true)) {
        $error = "Status voucher tidak valid.";
    } elseif ($nilai_diskon <= 0) {
        $error = "Nilai diskon harus lebih dari 0.";
    } elseif ($minimal_belanja < 0) {
        $error = "Minimal belanja tidak boleh kurang dari 0.";
    } elseif ($kuota < 0) {
        $error = "Kuota tidak boleh kurang dari 0.";
    } elseif ($jenis_diskon === 'Persen' && $nilai_diskon > 100) {
        $error = "Diskon persen maksimal 100%.";
    }

    /* =====================================================
       VALIDASI TANGGAL
    ===================================================== */

    if ($error === '') {

        $tanggal_mulai = str_replace('T', ' ', $tanggal_mulai);
        $tanggal_berakhir = str_replace('T', ' ', $tanggal_berakhir);

        if (strlen($tanggal_mulai) === 16) {
            $tanggal_mulai .= ':00';
        }

        if (strlen($tanggal_berakhir) === 16) {
            $tanggal_berakhir .= ':00';
        }

        $waktu_mulai = strtotime($tanggal_mulai);
        $waktu_berakhir = strtotime($tanggal_berakhir);

        if (
            $waktu_mulai === false ||
            $waktu_berakhir === false
        ) {
            $error = "Format tanggal tidak valid.";
        } elseif ($waktu_berakhir <= $waktu_mulai) {
            $error = "Tanggal berakhir harus setelah tanggal mulai.";
        }
    }

    /* =====================================================
       MAKSIMAL DISKON
    ===================================================== */

    $maksimal_diskon_db = null;

    if ($error === '' && $maksimal_diskon !== '') {

        if (!is_numeric($maksimal_diskon)) {
            $error = "Maksimal diskon harus berupa angka.";
        } else {

            $maksimal_diskon_db = (float) $maksimal_diskon;

            if ($maksimal_diskon_db < 0) {
                $error = "Maksimal diskon tidak boleh kurang dari 0.";
            }
        }
    }

    /* =====================================================
       CEK KODE VOUCHER
    ===================================================== */

    if ($error === '') {

        $stmt = mysqli_prepare(
            $conn,
            "SELECT id_voucher
             FROM voucher
             WHERE kode_voucher = ?
               AND id_voucher != ?
             LIMIT 1"
        );

        if (!$stmt) {
            $error = "Gagal memproses data voucher.";
        } else {

            mysqli_stmt_bind_param(
                $stmt,
                "si",
                $kode_voucher,
                $id_voucher
            );

            mysqli_stmt_execute($stmt);

            $result = mysqli_stmt_get_result($stmt);
            $kode_terpakai = mysqli_fetch_assoc($result);

            mysqli_stmt_close($stmt);

            if ($kode_terpakai) {
                $error = "Kode voucher sudah digunakan.";
            }
        }
    }

    /* =====================================================
       UPDATE DATABASE
    ===================================================== */

    if ($error === '') {

        if ($maksimal_diskon_db === null) {

            $sql = "
                UPDATE voucher
                SET
                    kode_voucher = ?,
                    nama_voucher = ?,
                    jenis_diskon = ?,
                    nilai_diskon = ?,
                    minimal_belanja = ?,
                    maksimal_diskon = NULL,
                    tanggal_mulai = ?,
                    tanggal_berakhir = ?,
                    kuota = ?,
                    status = ?
                WHERE id_voucher = ?
            ";

            $stmt = mysqli_prepare($conn, $sql);

            if ($stmt) {
                mysqli_stmt_bind_param(
                    $stmt,
                    "sssddssisi",
                    $kode_voucher,
                    $nama_voucher,
                    $jenis_diskon,
                    $nilai_diskon,
                    $minimal_belanja,
                    $tanggal_mulai,
                    $tanggal_berakhir,
                    $kuota,
                    $status,
                    $id_voucher
                );
            }

        } else {

            $sql = "
                UPDATE voucher
                SET
                    kode_voucher = ?,
                    nama_voucher = ?,
                    jenis_diskon = ?,
                    nilai_diskon = ?,
                    minimal_belanja = ?,
                    maksimal_diskon = ?,
                    tanggal_mulai = ?,
                    tanggal_berakhir = ?,
                    kuota = ?,
                    status = ?
                WHERE id_voucher = ?
            ";

            $stmt = mysqli_prepare($conn, $sql);

            if ($stmt) {
                mysqli_stmt_bind_param(
                    $stmt,
                    "sssdddssisi",
                    $kode_voucher,
                    $nama_voucher,
                    $jenis_diskon,
                    $nilai_diskon,
                    $minimal_belanja,
                    $maksimal_diskon_db,
                    $tanggal_mulai,
                    $tanggal_berakhir,
                    $kuota,
                    $status,
                    $id_voucher
                );
            }
        }

        if (!$stmt) {
            $error = "Gagal menyiapkan perubahan voucher.";
        } elseif (mysqli_stmt_execute($stmt)) {

            mysqli_stmt_close($stmt);

            /* =================================================
               CATAT LOG AKTIVITAS
            ================================================= */

            try {

                $kode_lama = (string) ($voucher_sebelumnya['kode_voucher'] ?? '');
                $nama_lama = (string) ($voucher_sebelumnya['nama_voucher'] ?? '');
                $jenis_lama = (string) ($voucher_sebelumnya['jenis_diskon'] ?? '');
                $nilai_lama = (string) ($voucher_sebelumnya['nilai_diskon'] ?? '');
                $status_lama = (string) ($voucher_sebelumnya['status'] ?? '');

                $detail_log =
                    "Mengubah voucher ID: {$id_voucher}. " .
                    "Kode: {$kode_lama} menjadi {$kode_voucher}; " .
                    "nama: {$nama_lama} menjadi {$nama_voucher}; " .
                    "jenis diskon: {$jenis_lama} menjadi {$jenis_diskon}; " .
                    "nilai diskon: {$nilai_lama} menjadi {$nilai_diskon}; " .
                    "status: {$status_lama} menjadi {$status}.";

                catatLogAktivitas(
                    $conn,
                    'voucher',
                    'Edit Voucher',
                    $detail_log
                );

            } catch (Throwable $e) {
                // Log gagal tidak membatalkan perubahan voucher.
            }

            header("Location: index.php?status=edit");
            exit;

        } else {

            $error = "Gagal mengubah voucher: " . mysqli_stmt_error($stmt);
            mysqli_stmt_close($stmt);
        }
    }

    /* =====================================================
       KEMBALIKAN DATA FORM
    ===================================================== */

    $voucher['kode_voucher'] = $kode_voucher;
    $voucher['nama_voucher'] = $nama_voucher;
    $voucher['jenis_diskon'] = $jenis_diskon;
    $voucher['nilai_diskon'] = $nilai_diskon;
    $voucher['minimal_belanja'] = $minimal_belanja;
    $voucher['maksimal_diskon'] = $maksimal_diskon;
    $voucher['tanggal_mulai'] = $tanggal_mulai;
    $voucher['tanggal_berakhir'] = $tanggal_berakhir;
    $voucher['kuota'] = $kuota;
    $voucher['status'] = $status;
}

/* =========================================================
   FORMAT TANGGAL
========================================================= */

$tanggal_mulai_form = !empty($voucher['tanggal_mulai'])
    ? date('Y-m-d\TH:i', strtotime($voucher['tanggal_mulai']))
    : '';

$tanggal_berakhir_form = !empty($voucher['tanggal_berakhir'])
    ? date('Y-m-d\TH:i', strtotime($voucher['tanggal_berakhir']))
    : '';

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Voucher - Toko ATK & Alat Jahit</title>

    <link rel="stylesheet" href="../assets/css/admin.css">

    <style>
        /* =================================================
           FORM VOUCHER
        ================================================= */

        .voucher-form-card {
            background: #ffffff;
            border: 1px solid #e0e7ed;
            border-radius: 14px;
            padding: 30px;
            max-width: 950px;
            box-shadow: 0 4px 15px rgba(16,47,75,0.04);
        }

        .voucher-form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .voucher-form-group {
            margin-bottom: 2px;
        }

        .voucher-form-group.full {
            grid-column: 1 / -1;
        }

        .voucher-form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #102f4b;
            font-size: 14px;
        }

        .voucher-form-group label span {
            color: #c0392b;
        }

        .voucher-form-group input,
        .voucher-form-group select {
            width: 100%;
            box-sizing: border-box;
            padding: 12px 14px;
            border: 1px solid #d6e0e8;
            border-radius: 8px;
            background: #ffffff;
            color: #263238;
            font-family: inherit;
            font-size: 14px;
            outline: none;
        }

        .voucher-form-group input:focus,
        .voucher-form-group select:focus {
            border-color: #20567d;
            box-shadow: 0 0 0 3px rgba(32,86,125,0.08);
        }

        .voucher-help {
            display: block;
            margin-top: 6px;
            color: #7890a4;
            font-size: 12px;
        }

        .voucher-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 28px;
            padding-top: 22px;
            border-top: 1px solid #e6edf2;
        }

        .btn-secondary {
            display: inline-block;
            padding: 12px 18px;
            border-radius: 8px;
            background: #eef3f7;
            color: #102f4b;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
        }

        .btn-secondary:hover {
            background: #dfe8ef;
        }

        .btn-primary {
            padding: 12px 18px;
            border: none;
            border-radius: 8px;
            background: #102f4b;
            color: #ffffff;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-primary:hover {
            background: #1c496b;
        }

        .voucher-alert {
            max-width: 950px;
            box-sizing: border-box;
            padding: 14px 18px;
            border-radius: 9px;
            margin-bottom: 20px;
            background: #fff0f0;
            border: 1px solid #f0c2c2;
            color: #b52b2b;
            font-size: 14px;
        }

        @media (max-width: 700px) {
            .voucher-form-grid {
                grid-template-columns: 1fr;
            }

            .voucher-form-group.full {
                grid-column: auto;
            }

            .voucher-form-card {
                padding: 20px;
            }

            .voucher-actions {
                flex-direction: column;
            }

            .voucher-actions a,
            .voucher-actions button {
                width: 100%;
                box-sizing: border-box;
                text-align: center;
            }
        }
    </style>
</head>

<body>

<div class="admin-layout">

    <!-- SIDEBAR STANDAR -->
    <?php require_once "../includes/sidebar.php"; ?>

    <main class="admin-main">

        <!-- NAVBAR STANDAR -->
        <?php require_once "../includes/navbar.php"; ?>

        <section class="admin-content">

            <!-- JUDUL HALAMAN -->
            <div class="page-title">
                <h1>Edit Voucher</h1>
                <p>Ubah informasi voucher promo toko.</p>
            </div>

            <!-- PESAN ERROR -->
            <?php if ($error !== '') : ?>
                <div class="voucher-alert">
                    <?= htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- FORM VOUCHER -->
            <div class="voucher-form-card">

                <form method="POST" autocomplete="off">

                    <div class="voucher-form-grid">

                        <!-- KODE VOUCHER -->
                        <div class="voucher-form-group">
                            <label>
                                Kode Voucher <span>*</span>
                            </label>

                            <input
                                type="text"
                                name="kode_voucher"
                                value="<?= htmlspecialchars($voucher['kode_voucher']); ?>"
                                maxlength="50"
                                placeholder="Contoh: HEMAT10"
                                required
                            >

                            <small class="voucher-help">
                                Kode yang digunakan pelanggan.
                            </small>
                        </div>

                        <!-- NAMA VOUCHER -->
                        <div class="voucher-form-group">
                            <label>
                                Nama Voucher <span>*</span>
                            </label>

                            <input
                                type="text"
                                name="nama_voucher"
                                value="<?= htmlspecialchars($voucher['nama_voucher']); ?>"
                                maxlength="150"
                                placeholder="Contoh: Promo Hemat 10%"
                                required
                            >
                        </div>

                        <!-- JENIS DISKON -->
                        <div class="voucher-form-group">
                            <label>
                                Jenis Diskon <span>*</span>
                            </label>

                            <select name="jenis_diskon" required>
                                <option
                                    value="Persen"
                                    <?= $voucher['jenis_diskon'] === 'Persen' ? 'selected' : ''; ?>
                                >
                                    Persen (%)
                                </option>

                                <option
                                    value="Nominal"
                                    <?= $voucher['jenis_diskon'] === 'Nominal' ? 'selected' : ''; ?>
                                >
                                    Nominal (Rp)
                                </option>
                            </select>
                        </div>

                        <!-- NILAI DISKON -->
                        <div class="voucher-form-group">
                            <label>
                                Nilai Diskon <span>*</span>
                            </label>

                            <input
                                type="number"
                                name="nilai_diskon"
                                value="<?= htmlspecialchars((string) $voucher['nilai_diskon']); ?>"
                                min="0"
                                step="0.01"
                                required
                            >

                            <small class="voucher-help">
                                Persen: isi 10 untuk 10%. Nominal: isi jumlah rupiah.
                            </small>
                        </div>

                        <!-- MINIMAL BELANJA -->
                        <div class="voucher-form-group">
                            <label>Minimal Belanja</label>

                            <input
                                type="number"
                                name="minimal_belanja"
                                value="<?= htmlspecialchars((string) $voucher['minimal_belanja']); ?>"
                                min="0"
                                step="0.01"
                            >

                            <small class="voucher-help">
                                Isi 0 jika tidak ada minimal belanja.
                            </small>
                        </div>

                        <!-- MAKSIMAL DISKON -->
                        <div class="voucher-form-group">
                            <label>Maksimal Diskon</label>

                            <input
                                type="number"
                                name="maksimal_diskon"
                                value="<?= htmlspecialchars((string) ($voucher['maksimal_diskon'] ?? '')); ?>"
                                min="0"
                                step="0.01"
                            >

                            <small class="voucher-help">
                                Kosongkan jika tidak ada batas maksimal.
                            </small>
                        </div>

                        <!-- TANGGAL MULAI -->
                        <div class="voucher-form-group">
                            <label>
                                Tanggal Mulai <span>*</span>
                            </label>

                            <input
                                type="datetime-local"
                                name="tanggal_mulai"
                                value="<?= htmlspecialchars($tanggal_mulai_form); ?>"
                                required
                            >
                        </div>

                        <!-- TANGGAL BERAKHIR -->
                        <div class="voucher-form-group">
                            <label>
                                Tanggal Berakhir <span>*</span>
                            </label>

                            <input
                                type="datetime-local"
                                name="tanggal_berakhir"
                                value="<?= htmlspecialchars($tanggal_berakhir_form); ?>"
                                required
                            >
                        </div>

                        <!-- KUOTA -->
                        <div class="voucher-form-group">
                            <label>Kuota</label>

                            <input
                                type="number"
                                name="kuota"
                                value="<?= htmlspecialchars((string) $voucher['kuota']); ?>"
                                min="0"
                                step="1"
                            >

                            <small class="voucher-help">
                                Isi 0 jika kuota tidak terbatas.
                            </small>
                        </div>

                        <!-- STATUS -->
                        <div class="voucher-form-group">
                            <label>
                                Status <span>*</span>
                            </label>

                            <select name="status" required>
                                <option
                                    value="Aktif"
                                    <?= $voucher['status'] === 'Aktif' ? 'selected' : ''; ?>
                                >
                                    Aktif
                                </option>

                                <option
                                    value="Tidak Aktif"
                                    <?= $voucher['status'] === 'Tidak Aktif' ? 'selected' : ''; ?>
                                >
                                    Tidak Aktif
                                </option>
                            </select>
                        </div>

                    </div>

                    <!-- TOMBOL FORM -->
                    <div class="voucher-actions">

                        <a href="index.php" class="btn-secondary">
                            Batal
                        </a>

                        <button type="submit" class="btn-primary">
                            Simpan Perubahan
                        </button>

                    </div>

                </form>

            </div>

        </section>

        <!-- FOOTER STANDAR -->
        <?php require_once "../includes/footer.php"; ?>

    </main>

</div>

<script src="../assets/js/admin.js"></script>

</body>
</html>