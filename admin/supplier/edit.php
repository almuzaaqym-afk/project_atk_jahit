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

/* =====================================================
   FUNGSI BANTU
===================================================== */

function h($value): string
{
    return htmlspecialchars(
        (string) ($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

/* =====================================================
   VALIDASI ID
===================================================== */

$id_supplier = (int) ($_GET['id'] ?? 0);

if ($id_supplier <= 0) {
    header("Location: index.php?status=tidak_ditemukan");
    exit;
}

/* =====================================================
   AMBIL DATA SUPPLIER
===================================================== */

$stmt = mysqli_prepare(
    $conn,
    "SELECT
        id_supplier,
        kode_supplier,
        nama_supplier,
        nama_kontak,
        telepon,
        email,
        alamat,
        keterangan,
        status
     FROM supplier
     WHERE id_supplier = ?
     LIMIT 1"
);

if (!$stmt) {
    header("Location: index.php?status=gagal");
    exit;
}

mysqli_stmt_bind_param($stmt, "i", $id_supplier);

if (!mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);

    header("Location: index.php?status=gagal");
    exit;
}

$result = mysqli_stmt_get_result($stmt);
$supplier = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$supplier) {
    header("Location: index.php?status=tidak_ditemukan");
    exit;
}

/* =====================================================
   NILAI AWAL FORM
===================================================== */

$error = "";

$kode_supplier = $supplier['kode_supplier'];
$nama_supplier = $supplier['nama_supplier'];
$nama_kontak = $supplier['nama_kontak'] ?? '';
$telepon = $supplier['telepon'] ?? '';
$email = $supplier['email'] ?? '';
$alamat = $supplier['alamat'] ?? '';
$keterangan = $supplier['keterangan'] ?? '';
$status = $supplier['status'] ?? 'Aktif';

/* =====================================================
   PROSES UPDATE
===================================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode_supplier = trim($_POST['kode_supplier'] ?? '');
    $nama_supplier = trim($_POST['nama_supplier'] ?? '');
    $nama_kontak = trim($_POST['nama_kontak'] ?? '');
    $telepon = trim($_POST['telepon'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');
    $keterangan = trim($_POST['keterangan'] ?? '');
    $status = $_POST['status'] ?? '';

    /* =============================================
       VALIDASI INPUT
    ============================================= */

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
    } elseif (
        $email !== '' &&
        !filter_var($email, FILTER_VALIDATE_EMAIL)
    ) {
        $error = "Format email tidak valid.";
    } elseif (
        $status !== 'Aktif' &&
        $status !== 'Tidak Aktif'
    ) {
        $error = "Status supplier tidak valid.";
    } else {

        /* =============================================
           CEK KODE SUPPLIER DUPLIKAT
        ============================================= */

        $stmt = mysqli_prepare(
            $conn,
            "SELECT id_supplier
             FROM supplier
             WHERE kode_supplier = ?
               AND id_supplier != ?
             LIMIT 1"
        );

        if (!$stmt) {
            $error = "Gagal memeriksa kode supplier.";
        } else {
            mysqli_stmt_bind_param(
                $stmt,
                "si",
                $kode_supplier,
                $id_supplier
            );

            if (!mysqli_stmt_execute($stmt)) {
                $error = "Gagal memeriksa kode supplier.";
                mysqli_stmt_close($stmt);
            } else {
                $result = mysqli_stmt_get_result($stmt);
                $cek = mysqli_fetch_assoc($result);

                mysqli_stmt_close($stmt);

                if ($cek) {
                    $error = "Kode supplier sudah digunakan oleh supplier lain.";
                } else {

                    /* =================================
                       UPDATE DATA SUPPLIER
                    ================================= */

                    $stmt = mysqli_prepare(
                        $conn,
                        "UPDATE supplier
                         SET
                            kode_supplier = ?,
                            nama_supplier = ?,
                            nama_kontak = ?,
                            telepon = ?,
                            email = ?,
                            alamat = ?,
                            keterangan = ?,
                            status = ?
                         WHERE id_supplier = ?"
                    );

                    if (!$stmt) {
                        $error = "Gagal menyiapkan perubahan data supplier.";
                    } else {
                        mysqli_stmt_bind_param(
                            $stmt,
                            "ssssssssi",
                            $kode_supplier,
                            $nama_supplier,
                            $nama_kontak,
                            $telepon,
                            $email,
                            $alamat,
                            $keterangan,
                            $status,
                            $id_supplier
                        );

                        if (mysqli_stmt_execute($stmt)) {
                            mysqli_stmt_close($stmt);

                            /* =================================
                               CATAT LOG EDIT SUPPLIER
                            ================================= */

                            $detailLog = 'Memperbarui data supplier "' .
                                $supplier['nama_supplier'] .
                                '" menjadi "' . $nama_supplier .
                                '" (ID: ' . $id_supplier .
                                ', kode sebelumnya: ' .
                                $supplier['kode_supplier'] .
                                ', kode terbaru: ' . $kode_supplier .
                                ', status sebelumnya: ' .
                                ($supplier['status'] ?? 'Aktif') .
                                ', status terbaru: ' . $status . ').';

                            $logBerhasil = catatLogAktivitas(
                                $conn,
                                'supplier',
                                'Edit Supplier',
                                $detailLog
                            );

                            if (!$logBerhasil) {
                                error_log(
                                    'Gagal mencatat log edit supplier: ID ' .
                                    $id_supplier
                                );
                            }

                            header("Location: index.php?status=edit");
                            exit;
                        }

                        $error_code = mysqli_stmt_errno($stmt);
                        mysqli_stmt_close($stmt);

                        if ($error_code === 1062) {
                            $error = "Kode supplier sudah digunakan oleh supplier lain.";
                        } else {
                            $error = "Supplier gagal diperbarui. Periksa kembali data dan struktur database.";
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

    <title>Edit Supplier - Toko ATK &amp; Alat Jahit</title>

    <link
        rel="stylesheet"
        href="../assets/css/admin.css"
    >

    <style>
        .supplier-edit-page {
            max-width: 900px;
        }

        .supplier-edit-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            margin-bottom: 25px;
        }

        .supplier-edit-header h1 {
            margin: 0 0 7px;
            color: #102f4b;
            font-size: 28px;
        }

        .supplier-edit-header p {
            margin: 0;
            color: #7890a4;
            font-size: 14px;
        }

        .supplier-edit-back {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 16px;
            border-radius: 8px;
            background: #fff;
            border: 1px solid #dce4ea;
            color: #102f4b;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            white-space: nowrap;
        }

        .supplier-edit-back:hover {
            background: #f3f6f8;
        }

        .supplier-edit-alert {
            margin-bottom: 20px;
            padding: 13px 16px;
            border-radius: 8px;
            background: #fde7e7;
            border: 1px solid #f1caca;
            color: #b23b3b;
            font-size: 13px;
        }

        .supplier-edit-card {
            background: #fff;
            border: 1px solid #e0e7ed;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(16, 47, 75, 0.04);
        }

        .supplier-edit-card-header {
            padding: 20px 22px;
            border-bottom: 1px solid #e6edf2;
        }

        .supplier-edit-card-header h2 {
            margin: 0 0 5px;
            color: #102f4b;
            font-size: 18px;
        }

        .supplier-edit-card-header p {
            margin: 0;
            color: #7890a4;
            font-size: 13px;
        }

        .supplier-edit-card-body {
            padding: 25px 22px;
        }

        .supplier-edit-field {
            margin-bottom: 20px;
        }

        .supplier-edit-field label {
            display: block;
            margin-bottom: 7px;
            color: #102f4b;
            font-size: 13px;
            font-weight: 600;
        }

        .supplier-edit-input,
        .supplier-edit-textarea,
        .supplier-edit-select {
            width: 100%;
            box-sizing: border-box;
            padding: 11px 13px;
            border: 1px solid #d9e2e8;
            border-radius: 8px;
            background: #fff;
            color: #334f66;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 14px;
            outline: none;
        }

        .supplier-edit-input:focus,
        .supplier-edit-textarea:focus,
        .supplier-edit-select:focus {
            border-color: #245b83;
            box-shadow: 0 0 0 3px rgba(36, 91, 131, 0.08);
        }

        .supplier-edit-textarea {
            min-height: 110px;
            resize: vertical;
        }

        .supplier-edit-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }

        .supplier-edit-help {
            display: block;
            margin-top: 5px;
            color: #9aafbf;
            font-size: 11px;
        }

        .supplier-edit-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            padding-top: 20px;
            margin-top: 5px;
            border-top: 1px solid #edf1f4;
        }

        .supplier-edit-btn {
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

        .supplier-edit-cancel {
            background: #edf1f4;
            color: #506d84;
        }

        .supplier-edit-cancel:hover {
            background: #e2e8ec;
        }

        .supplier-edit-save {
            background: #102f4b;
            color: #fff;
        }

        .supplier-edit-save:hover {
            background: #1c496b;
        }

        @media (max-width: 700px) {
            .supplier-edit-header {
                flex-direction: column;
            }

            .supplier-edit-back {
                width: 100%;
            }

            .supplier-edit-card-body {
                padding: 20px;
            }

            .supplier-edit-grid {
                grid-template-columns: 1fr;
                gap: 0;
            }

            .supplier-edit-actions {
                flex-direction: column-reverse;
            }

            .supplier-edit-btn {
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

            <div class="supplier-edit-page">

                <div class="supplier-edit-header">
                    <div>
                        <h1>Edit Supplier</h1>
                        <p>Perbarui informasi supplier.</p>
                    </div>

                    <a
                        href="index.php"
                        class="supplier-edit-back"
                    >
                        &larr; Kembali
                    </a>
                </div>

                <?php if ($error !== ''): ?>
                    <div class="supplier-edit-alert" role="alert">
                        <?= h($error); ?>
                    </div>
                <?php endif; ?>

                <div class="supplier-edit-card">

                    <div class="supplier-edit-card-header">
                        <h2>Informasi Supplier</h2>
                        <p>Periksa kembali data sebelum menyimpan perubahan.</p>
                    </div>

                    <div class="supplier-edit-card-body">

                        <form method="POST" autocomplete="off">

                            <!-- KODE DAN NAMA -->

                            <div class="supplier-edit-grid">

                                <div class="supplier-edit-field">
                                    <label for="kode_supplier">
                                        Kode Supplier *
                                    </label>

                                    <input
                                        type="text"
                                        name="kode_supplier"
                                        id="kode_supplier"
                                        class="supplier-edit-input"
                                        maxlength="30"
                                        value="<?= h($kode_supplier); ?>"
                                        required
                                    >

                                    <span class="supplier-edit-help">
                                        Kode supplier harus unik.
                                    </span>
                                </div>

                                <div class="supplier-edit-field">
                                    <label for="nama_supplier">
                                        Nama Supplier *
                                    </label>

                                    <input
                                        type="text"
                                        name="nama_supplier"
                                        id="nama_supplier"
                                        class="supplier-edit-input"
                                        maxlength="100"
                                        value="<?= h($nama_supplier); ?>"
                                        required
                                    >
                                </div>

                            </div>

                            <!-- KONTAK DAN TELEPON -->

                            <div class="supplier-edit-grid">

                                <div class="supplier-edit-field">
                                    <label for="nama_kontak">
                                        Nama Kontak
                                    </label>

                                    <input
                                        type="text"
                                        name="nama_kontak"
                                        id="nama_kontak"
                                        class="supplier-edit-input"
                                        maxlength="100"
                                        value="<?= h($nama_kontak); ?>"
                                    >
                                </div>

                                <div class="supplier-edit-field">
                                    <label for="telepon">
                                        No. Telepon
                                    </label>

                                    <input
                                        type="text"
                                        name="telepon"
                                        id="telepon"
                                        class="supplier-edit-input"
                                        maxlength="30"
                                        value="<?= h($telepon); ?>"
                                    >
                                </div>

                            </div>

                            <!-- EMAIL -->

                            <div class="supplier-edit-field">
                                <label for="email">Email</label>

                                <input
                                    type="email"
                                    name="email"
                                    id="email"
                                    class="supplier-edit-input"
                                    maxlength="100"
                                    value="<?= h($email); ?>"
                                    placeholder="supplier@email.com"
                                >
                            </div>

                            <!-- ALAMAT -->

                            <div class="supplier-edit-field">
                                <label for="alamat">Alamat</label>

                                <textarea
                                    name="alamat"
                                    id="alamat"
                                    class="supplier-edit-textarea"
                                ><?= h($alamat); ?></textarea>
                            </div>

                            <!-- KETERANGAN -->

                            <div class="supplier-edit-field">
                                <label for="keterangan">Keterangan</label>

                                <textarea
                                    name="keterangan"
                                    id="keterangan"
                                    class="supplier-edit-textarea"
                                ><?= h($keterangan); ?></textarea>
                            </div>

                            <!-- STATUS -->

                            <div class="supplier-edit-field">
                                <label for="status">Status</label>

                                <select
                                    name="status"
                                    id="status"
                                    class="supplier-edit-select"
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

                            <div class="supplier-edit-actions">

                                <a
                                    href="index.php"
                                    class="supplier-edit-btn supplier-edit-cancel"
                                >
                                    Batal
                                </a>

                                <button
                                    type="submit"
                                    class="supplier-edit-btn supplier-edit-save"
                                >
                                    Simpan Perubahan
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