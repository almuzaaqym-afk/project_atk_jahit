<?php
session_start();

if (!isset($_SESSION['id_admin'])) {
    header("Location: ../login.php");
    exit;
}

require_once "../../config/koneksi.php";
require_once "../includes/hak_akses.php";
require_once __DIR__ . "/../includes/log_aktivitas.php";

cekAkses('supplier');

$error = "";

$kode_supplier = "";
$nama_supplier = "";
$nama_kontak = "";
$telepon = "";
$email = "";
$alamat = "";
$keterangan = "";
$status = "Aktif";

/* =====================================================
   PROSES TAMBAH SUPPLIER
===================================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode_supplier = trim($_POST['kode_supplier'] ?? '');
    $nama_supplier = trim($_POST['nama_supplier'] ?? '');
    $nama_kontak = trim($_POST['nama_kontak'] ?? '');
    $telepon = trim($_POST['telepon'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');
    $keterangan = trim($_POST['keterangan'] ?? '');
    $status = $_POST['status'] ?? 'Aktif';

    /* =================================================
       VALIDASI INPUT
    ================================================= */

    if ($kode_supplier === '' || $nama_supplier === '') {
        $error = "Kode supplier dan nama supplier wajib diisi.";
    } elseif (mb_strlen($kode_supplier) > 30) {
        $error = "Kode supplier maksimal 30 karakter.";
    } elseif (mb_strlen($nama_supplier) > 100) {
        $error = "Nama supplier maksimal 100 karakter.";
    } elseif (mb_strlen($nama_kontak) > 100) {
        $error = "Nama kontak maksimal 100 karakter.";
    } elseif (mb_strlen($telepon) > 30) {
        $error = "Nomor telepon maksimal 30 karakter.";
    } elseif (mb_strlen($email) > 100) {
        $error = "Email maksimal 100 karakter.";
    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email tidak valid.";
    } elseif (
        $status !== 'Aktif' &&
        $status !== 'Tidak Aktif'
    ) {
        $error = "Status supplier tidak valid.";
    } else {

        /* =============================================
           CEK KODE SUPPLIER
        ============================================= */

        $stmt = mysqli_prepare(
            $conn,
            "SELECT id_supplier
             FROM supplier
             WHERE kode_supplier = ?
             LIMIT 1"
        );

        if (!$stmt) {
            $error = "Gagal memeriksa kode supplier.";
        } else {
            mysqli_stmt_bind_param(
                $stmt,
                "s",
                $kode_supplier
            );

            if (!mysqli_stmt_execute($stmt)) {
                $error = "Gagal memeriksa kode supplier.";
                mysqli_stmt_close($stmt);
            } else {
                $result = mysqli_stmt_get_result($stmt);
                $cek = mysqli_fetch_assoc($result);

                mysqli_stmt_close($stmt);

                if ($cek) {
                    $error = "Kode supplier sudah digunakan.";
                } else {

                    /* =================================
                       INSERT SUPPLIER
                    ================================= */

                    $stmt = mysqli_prepare(
                        $conn,
                        "INSERT INTO supplier (
                            kode_supplier,
                            nama_supplier,
                            nama_kontak,
                            telepon,
                            email,
                            alamat,
                            keterangan,
                            status
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
                    );

                    if (!$stmt) {
                        $error = "Gagal menyiapkan penyimpanan supplier.";
                    } else {
                        mysqli_stmt_bind_param(
                            $stmt,
                            "ssssssss",
                            $kode_supplier,
                            $nama_supplier,
                            $nama_kontak,
                            $telepon,
                            $email,
                            $alamat,
                            $keterangan,
                            $status
                        );

                        if (mysqli_stmt_execute($stmt)) {
                            $idSupplierBaru = (int) mysqli_insert_id($conn);

                            mysqli_stmt_close($stmt);

                            /* =================================
                               CATAT LOG TAMBAH SUPPLIER
                            ================================= */

                            $detailLog = 'Menambahkan supplier "' .
                                $nama_supplier .
                                '" (Kode: ' . $kode_supplier .
                                ', ID: ' . $idSupplierBaru .
                                ', Status: ' . $status . ').';

                            $logBerhasil = catatLogAktivitas(
                                $conn,
                                'supplier',
                                'Tambah Supplier',
                                $detailLog
                            );

                            if (!$logBerhasil) {
                                error_log(
                                    'Gagal mencatat log tambah supplier: ID ' .
                                    $idSupplierBaru
                                );
                            }

                            header("Location: index.php?status=tambah");
                            exit;
                        }

                        $error_code = mysqli_stmt_errno($stmt);
                        mysqli_stmt_close($stmt);

                        if ($error_code === 1062) {
                            $error = "Kode supplier sudah digunakan.";
                        } else {
                            $error = "Supplier gagal ditambahkan. Periksa kembali data dan struktur database.";
                        }
                    }
                }
            }
        }
    }
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

    <title>Tambah Supplier - Toko ATK &amp; Alat Jahit</title>

    <link
        rel="stylesheet"
        href="../assets/css/admin.css"
    >

    <style>
        .supplier-form-page {
            max-width: 900px;
        }

        .supplier-form-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            margin-bottom: 25px;
        }

        .supplier-form-header h1 {
            margin: 0 0 7px;
            color: #102f4b;
            font-size: 28px;
        }

        .supplier-form-header p {
            margin: 0;
            color: #7890a4;
            font-size: 14px;
        }

        .supplier-form-alert {
            margin-bottom: 20px;
            padding: 13px 16px;
            border-radius: 8px;
            background: #fde7e7;
            border: 1px solid #f1caca;
            color: #b23b3b;
            font-size: 13px;
        }

        .supplier-form-card {
            background: #ffffff;
            border: 1px solid #e0e7ed;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(16, 47, 75, 0.04);
        }

        .supplier-form-card-header {
            padding: 20px 22px;
            border-bottom: 1px solid #e6edf2;
        }

        .supplier-form-card-header h2 {
            margin: 0 0 5px;
            color: #102f4b;
            font-size: 18px;
        }

        .supplier-form-card-header p {
            margin: 0;
            color: #7890a4;
            font-size: 13px;
        }

        .supplier-form-card-body {
            padding: 25px 22px;
        }

        .supplier-field {
            margin-bottom: 20px;
        }

        .supplier-field label {
            display: block;
            margin-bottom: 7px;
            color: #102f4b;
            font-size: 13px;
            font-weight: 600;
        }

        .supplier-input,
        .supplier-textarea,
        .supplier-select {
            width: 100%;
            box-sizing: border-box;
            padding: 11px 13px;
            border: 1px solid #d9e2e8;
            border-radius: 8px;
            background: #ffffff;
            color: #334f66;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 14px;
            outline: none;
        }

        .supplier-input:focus,
        .supplier-textarea:focus,
        .supplier-select:focus {
            border-color: #245b83;
            box-shadow: 0 0 0 3px rgba(36, 91, 131, 0.08);
        }

        .supplier-textarea {
            min-height: 110px;
            resize: vertical;
        }

        .supplier-help {
            display: block;
            margin-top: 5px;
            color: #9aafbf;
            font-size: 11px;
        }

        .supplier-form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }

        .supplier-form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            padding-top: 20px;
            margin-top: 5px;
            border-top: 1px solid #edf1f4;
        }

        .supplier-action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-sizing: border-box;
            border: none;
            border-radius: 8px;
            padding: 10px 17px;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
        }

        .supplier-cancel-btn {
            background: #edf1f4;
            color: #506d84;
        }

        .supplier-cancel-btn:hover {
            background: #e2e8ec;
        }

        .supplier-save-btn {
            background: #102f4b;
            color: #ffffff;
        }

        .supplier-save-btn:hover {
            background: #1c496b;
        }

        @media (max-width: 700px) {
            .supplier-form-header {
                flex-direction: column;
            }

            .supplier-form-card-body {
                padding: 20px;
            }

            .supplier-form-grid {
                grid-template-columns: 1fr;
                gap: 0;
            }

            .supplier-form-actions {
                flex-direction: column-reverse;
            }

            .supplier-action-btn {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>

<body>

<div class="admin-layout">

    <?php include "../includes/sidebar.php"; ?>

    <div class="admin-main">

        <?php include "../includes/navbar.php"; ?>

        <main class="admin-content">

            <div class="supplier-form-page">

                <div class="supplier-form-header">
                    <div>
                        <h1>Tambah Supplier</h1>
                        <p>Tambahkan supplier baru ke dalam sistem.</p>
                    </div>
                </div>

                <?php if ($error !== ''): ?>
                    <div class="supplier-form-alert" role="alert">
                        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                <?php endif; ?>

                <div class="supplier-form-card">

                    <div class="supplier-form-card-header">
                        <h2>Informasi Supplier</h2>
                        <p>Lengkapi data supplier dengan benar.</p>
                    </div>

                    <div class="supplier-form-card-body">

                        <form method="POST" autocomplete="off">

                            <!-- KODE DAN NAMA -->

                            <div class="supplier-form-grid">

                                <div class="supplier-field">
                                    <label for="kode_supplier">
                                        Kode Supplier *
                                    </label>

                                    <input
                                        type="text"
                                        name="kode_supplier"
                                        id="kode_supplier"
                                        class="supplier-input"
                                        maxlength="30"
                                        value="<?= htmlspecialchars($kode_supplier, ENT_QUOTES, 'UTF-8'); ?>"
                                        placeholder="Contoh: SUP001"
                                        required
                                    >

                                    <span class="supplier-help">
                                        Kode supplier harus unik.
                                    </span>
                                </div>

                                <div class="supplier-field">
                                    <label for="nama_supplier">
                                        Nama Supplier *
                                    </label>

                                    <input
                                        type="text"
                                        name="nama_supplier"
                                        id="nama_supplier"
                                        class="supplier-input"
                                        maxlength="100"
                                        value="<?= htmlspecialchars($nama_supplier, ENT_QUOTES, 'UTF-8'); ?>"
                                        placeholder="Contoh: CV Sumber ATK"
                                        required
                                    >
                                </div>

                            </div>

                            <!-- KONTAK DAN TELEPON -->

                            <div class="supplier-form-grid">

                                <div class="supplier-field">
                                    <label for="nama_kontak">
                                        Nama Kontak
                                    </label>

                                    <input
                                        type="text"
                                        name="nama_kontak"
                                        id="nama_kontak"
                                        class="supplier-input"
                                        maxlength="100"
                                        value="<?= htmlspecialchars($nama_kontak, ENT_QUOTES, 'UTF-8'); ?>"
                                        placeholder="Nama orang yang dapat dihubungi"
                                    >
                                </div>

                                <div class="supplier-field">
                                    <label for="telepon">
                                        No. Telepon
                                    </label>

                                    <input
                                        type="text"
                                        name="telepon"
                                        id="telepon"
                                        class="supplier-input"
                                        maxlength="30"
                                        value="<?= htmlspecialchars($telepon, ENT_QUOTES, 'UTF-8'); ?>"
                                        placeholder="08xxxxxxxxxx"
                                    >
                                </div>

                            </div>

                            <!-- EMAIL -->

                            <div class="supplier-field">
                                <label for="email">Email</label>

                                <input
                                    type="email"
                                    name="email"
                                    id="email"
                                    class="supplier-input"
                                    maxlength="100"
                                    value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>"
                                    placeholder="supplier@email.com"
                                >
                            </div>

                            <!-- ALAMAT -->

                            <div class="supplier-field">
                                <label for="alamat">Alamat</label>

                                <textarea
                                    name="alamat"
                                    id="alamat"
                                    class="supplier-textarea"
                                    placeholder="Alamat lengkap supplier"
                                ><?= htmlspecialchars($alamat, ENT_QUOTES, 'UTF-8'); ?></textarea>
                            </div>

                            <!-- KETERANGAN -->

                            <div class="supplier-field">
                                <label for="keterangan">Keterangan</label>

                                <textarea
                                    name="keterangan"
                                    id="keterangan"
                                    class="supplier-textarea"
                                    placeholder="Keterangan tambahan"
                                ><?= htmlspecialchars($keterangan, ENT_QUOTES, 'UTF-8'); ?></textarea>
                            </div>

                            <!-- STATUS -->

                            <div class="supplier-field">
                                <label for="status">Status</label>

                                <select
                                    name="status"
                                    id="status"
                                    class="supplier-select"
                                >
                                    <option
                                        value="Aktif"
                                        <?= $status === 'Aktif' ? 'selected' : ''; ?>
                                    >
                                        Aktif
                                    </option>

                                    <option
                                        value="Tidak Aktif"
                                        <?= $status === 'Tidak Aktif' ? 'selected' : ''; ?>
                                    >
                                        Tidak Aktif
                                    </option>
                                </select>
                            </div>

                            <!-- TOMBOL -->

                            <div class="supplier-form-actions">

                                <a
                                    href="index.php"
                                    class="supplier-action-btn supplier-cancel-btn"
                                >
                                    Batal
                                </a>

                                <button
                                    type="submit"
                                    class="supplier-action-btn supplier-save-btn"
                                >
                                    Simpan Supplier
                                </button>

                            </div>

                        </form>

                    </div>
                </div>

            </div>
        </main>

        <?php include "../includes/footer.php"; ?>

    </div>
</div>

<script src="../assets/js/admin.js"></script>

</body>
</html>