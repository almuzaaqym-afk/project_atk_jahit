<?php

session_start();

if (!isset($_SESSION['id_admin'])) {
    header("Location: ../login.php");
    exit;
}

require_once "../../config/koneksi.php";
require_once __DIR__ . "/../includes/log_aktivitas.php";

$error = "";
$uploaded_files = [];

/* =========================================================
   UPLOAD GAMBAR VARIAN
========================================================= */
function uploadGambarVarian(
    $files,
    $id_varian,
    $conn,
    &$error,
    &$uploaded_files
) {
    if (
        !isset($files['name']) ||
        !is_array($files['name'])
    ) {
        return;
    }

    $folder = __DIR__ . "/../assets/images/products/variants/";

    if (!is_dir($folder)) {
        if (!mkdir($folder, 0777, true)) {
            $error = "Folder gambar varian gagal dibuat.";
            return;
        }
    }

    $allowed = [
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'webp' => 'image/webp'
    ];

    foreach ($files['name'] as $i => $original) {
        if (
            ($files['error'][$i] ?? UPLOAD_ERR_NO_FILE)
            === UPLOAD_ERR_NO_FILE
        ) {
            continue;
        }

        if (($files['error'][$i] ?? 0) !== UPLOAD_ERR_OK) {
            $error = "Salah satu gambar varian gagal diupload.";
            return;
        }

        if (($files['size'][$i] ?? 0) > 5 * 1024 * 1024) {
            $error = "Ukuran setiap gambar varian maksimal 5 MB.";
            return;
        }

        $extension = strtolower(
            pathinfo($original, PATHINFO_EXTENSION)
        );

        if (!isset($allowed[$extension])) {
            $error = "Format gambar varian harus JPG, JPEG, PNG, atau WEBP.";
            return;
        }

        $mime = mime_content_type($files['tmp_name'][$i]);

        if ($mime !== $allowed[$extension]) {
            $error = "File gambar varian tidak valid.";
            return;
        }

        $nama = uniqid('varian_', true) . '.' . $extension;
        $path = $folder . $nama;

        if (!move_uploaded_file($files['tmp_name'][$i], $path)) {
            $error = "Gambar varian gagal disimpan.";
            return;
        }

        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO produk_varian_gambar
            (
                id_varian,
                nama_gambar,
                urutan
            )
            VALUES
            (
                ?,
                ?,
                (
                    SELECT COALESCE(MAX(x.urutan), 0) + 1
                    FROM produk_varian_gambar x
                    WHERE x.id_varian = ?
                )
            )"
        );

        if (!$stmt) {
            @unlink($path);
            $error = "Gambar varian gagal disimpan ke database.";
            return;
        }

        mysqli_stmt_bind_param(
            $stmt,
            "isi",
            $id_varian,
            $nama,
            $id_varian
        );

        if (!mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            @unlink($path);
            $error = "Gambar varian gagal disimpan ke database.";
            return;
        }

        mysqli_stmt_close($stmt);
        $uploaded_files[] = $path;
    }
}

/* =========================================================
   DATA KATEGORI
========================================================= */
$kategoriResult = mysqli_query(
    $conn,
    "SELECT id_kategori, nama_kategori
     FROM kategori
     ORDER BY nama_kategori ASC"
);

if (!$kategoriResult) {
    $error = "Data kategori gagal dimuat.";
}

/* =========================================================
   PROSES SIMPAN PRODUK
========================================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_produk = trim($_POST['nama_produk'] ?? '');
    $id_kategori = (int) ($_POST['id_kategori'] ?? 0);
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $memiliki_varian = ($_POST['memiliki_varian'] ?? 'tidak') === 'ya';
    $harga = (float) ($_POST['harga'] ?? 0);

    $varian_data = [];
    $gambar = '';
    $uploaded_files = [];

    /* VALIDASI DASAR */
    if ($nama_produk === '') {
        $error = "Nama produk wajib diisi.";
    } elseif ($id_kategori <= 0) {
        $error = "Kategori wajib dipilih.";
    } elseif (!$memiliki_varian && $harga < 0) {
        $error = "Harga tidak boleh negatif.";
    }

    /* VALIDASI KATEGORI */
    if ($error === '') {
        $stmt = mysqli_prepare(
            $conn,
            "SELECT id_kategori
             FROM kategori
             WHERE id_kategori = ?
             LIMIT 1"
        );

        if (!$stmt) {
            $error = "Kategori gagal divalidasi.";
        } else {
            mysqli_stmt_bind_param($stmt, "i", $id_kategori);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if (mysqli_num_rows($result) === 0) {
                $error = "Kategori tidak ditemukan.";
            }

            mysqli_stmt_close($stmt);
        }
    }

    /* VALIDASI VARIAN */
    if ($error === '' && $memiliki_varian) {
        $nama_varian_list = $_POST['nama_varian'] ?? [];
        $harga_varian_list = $_POST['harga_varian'] ?? [];

        if (
            !is_array($nama_varian_list) ||
            count($nama_varian_list) === 0
        ) {
            $error = "Minimal harus ada satu varian.";
        }

        $nama_varian_terpakai = [];

        if ($error === '') {
            foreach ($nama_varian_list as $index => $nama_varian) {
                $nama_varian = trim($nama_varian);
                $harga_varian = (float) ($harga_varian_list[$index] ?? 0);

                if ($nama_varian === '') {
                    $error = "Nama semua varian wajib diisi.";
                    break;
                }

                if ($harga_varian < 0) {
                    $error = "Harga varian tidak boleh negatif.";
                    break;
                }

                $nama_key = strtolower($nama_varian);

                if (isset($nama_varian_terpakai[$nama_key])) {
                    $error = "Nama varian tidak boleh sama.";
                    break;
                }

                $nama_varian_terpakai[$nama_key] = true;

                $varian_data[] = [
                    'nama' => $nama_varian,
                    'harga' => $harga_varian,
                    'index' => $index
                ];
            }
        }
    }

    /* UPLOAD GAMBAR PRODUK UTAMA */
    if (
        $error === '' &&
        isset($_FILES['gambar']) &&
        $_FILES['gambar']['error'] !== UPLOAD_ERR_NO_FILE
    ) {
        if ($_FILES['gambar']['error'] !== UPLOAD_ERR_OK) {
            $error = "Gagal mengupload gambar.";
        } else {
            $file = $_FILES['gambar'];

            if ($file['size'] > 2 * 1024 * 1024) {
                $error = "Ukuran gambar maksimal 2 MB.";
            } else {
                $allowed = [
                    'jpg'  => 'image/jpeg',
                    'jpeg' => 'image/jpeg',
                    'png'  => 'image/png',
                    'webp' => 'image/webp'
                ];

                $extension = strtolower(
                    pathinfo($file['name'], PATHINFO_EXTENSION)
                );

                if (!isset($allowed[$extension])) {
                    $error = "Format gambar harus JPG, JPEG, PNG, atau WEBP.";
                } else {
                    $mime = mime_content_type($file['tmp_name']);

                    if ($mime !== $allowed[$extension]) {
                        $error = "File gambar tidak valid.";
                    } else {
                        $folder = __DIR__ . "/../assets/images/products/";

                        if (!is_dir($folder)) {
                            if (!mkdir($folder, 0777, true)) {
                                $error = "Folder gambar produk gagal dibuat.";
                            }
                        }

                        if ($error === '') {
                            $gambar = uniqid('produk_', true) . '.' . $extension;
                            $path_gambar_produk = $folder . $gambar;

                            if (
                                !move_uploaded_file(
                                    $file['tmp_name'],
                                    $path_gambar_produk
                                )
                            ) {
                                $error = "Gambar gagal disimpan.";
                                $gambar = '';
                            } else {
                                $uploaded_files[] = $path_gambar_produk;
                            }
                        }
                    }
                }
            }
        }
    }

    /* =====================================================
       SIMPAN KE DATABASE
    ===================================================== */
    if ($error === '') {
        mysqli_begin_transaction($conn);

        try {
            /*
             * Stok tidak disimpan melalui form Tambah Produk.
             * Semua stok akan dimulai dari 0.
             */

            $harga_produk = $memiliki_varian ? 0 : $harga;
            $stok_produk = 0;
            $stok_minimum_produk = 0;
            $lokasi_produk = '';

            /* INSERT PRODUK */
            $stmt = mysqli_prepare(
                $conn,
                "INSERT INTO produk
                (
                    id_kategori,
                    nama_produk,
                    deskripsi,
                    harga,
                    stok,
                    stok_minimum,
                    lokasi_rak,
                    gambar
                )
                VALUES
                (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    NULLIF(?, ''),
                    ?
                )"
            );

            if (!$stmt) {
                throw new Exception("Produk gagal disiapkan untuk disimpan.");
            }

            mysqli_stmt_bind_param(
                $stmt,
                "issdiiss",
                $id_kategori,
                $nama_produk,
                $deskripsi,
                $harga_produk,
                $stok_produk,
                $stok_minimum_produk,
                $lokasi_produk,
                $gambar
            );

            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception(
                    "Produk gagal ditambahkan: " . mysqli_stmt_error($stmt)
                );
            }

            $id_produk_baru = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);

            /* SIMPAN VARIAN */
            if ($memiliki_varian) {
                foreach ($varian_data as $varian) {
                    $stok_varian = 0;
                    $stok_minimum_varian = 0;
                    $lokasi_varian = '';

                    $stmt = mysqli_prepare(
                        $conn,
                        "INSERT INTO produk_varian
                        (
                            id_produk,
                            nama_varian,
                            harga,
                            stok,
                            stok_minimum,
                            lokasi_rak,
                            status
                        )
                        VALUES
                        (
                            ?,
                            ?,
                            ?,
                            ?,
                            ?,
                            NULLIF(?, ''),
                            'Aktif'
                        )"
                    );

                    if (!$stmt) {
                        throw new Exception("Varian gagal disiapkan untuk disimpan.");
                    }

                    mysqli_stmt_bind_param(
                        $stmt,
                        "isdiis",
                        $id_produk_baru,
                        $varian['nama'],
                        $varian['harga'],
                        $stok_varian,
                        $stok_minimum_varian,
                        $lokasi_varian
                    );

                    if (!mysqli_stmt_execute($stmt)) {
                        throw new Exception(
                            "Varian " . $varian['nama'] .
                            " gagal ditambahkan: " . mysqli_stmt_error($stmt)
                        );
                    }

                    $id_varian_baru = mysqli_insert_id($conn);
                    mysqli_stmt_close($stmt);

                    /* GAMBAR VARIAN */
                    $index_file = $varian['index'];

                    if (
                        isset($_FILES['gambar_varian']) &&
                        isset($_FILES['gambar_varian']['name'][$index_file]) &&
                        is_array($_FILES['gambar_varian']['name'][$index_file])
                    ) {
                        $files_varian = [
                            'name' => [],
                            'type' => [],
                            'tmp_name' => [],
                            'error' => [],
                            'size' => []
                        ];

                        foreach (
                            $_FILES['gambar_varian']['name'][$index_file]
                            as $j => $nama_file
                        ) {
                            $files_varian['name'][] = $nama_file;
                            $files_varian['type'][] =
                                $_FILES['gambar_varian']['type'][$index_file][$j] ?? '';
                            $files_varian['tmp_name'][] =
                                $_FILES['gambar_varian']['tmp_name'][$index_file][$j] ?? '';
                            $files_varian['error'][] =
                                $_FILES['gambar_varian']['error'][$index_file][$j]
                                ?? UPLOAD_ERR_NO_FILE;
                            $files_varian['size'][] =
                                $_FILES['gambar_varian']['size'][$index_file][$j] ?? 0;
                        }

                        if (!empty($files_varian['name'])) {
                            uploadGambarVarian(
                                $files_varian,
                                $id_varian_baru,
                                $conn,
                                $error,
                                $uploaded_files
                            );

                            if ($error !== '') {
                                throw new Exception($error);
                            }
                        }
                    }
                }
            }

            /* TRANSAKSI PRODUK BERHASIL */
            mysqli_commit($conn);

            /* CATAT LOG AKTIVITAS */
            $detailLog = 'Menambahkan produk "' . $nama_produk .
                '" (ID: ' . $id_produk_baru . '). ';

            if ($memiliki_varian) {
                $detailLog .= 'Jumlah varian: ' . count($varian_data) . '.';
            } else {
                $detailLog .= 'Produk tanpa varian.';
            }

            $logBerhasil = catatLogAktivitas(
                $conn,
                'produk',
                'Tambah Produk',
                $detailLog
            );

            if (!$logBerhasil) {
                error_log(
                    'Gagal mencatat log aktivitas tambah produk ID: ' .
                    $id_produk_baru
                );
            }

            header("Location: index.php?status=tambah");
            exit;
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error = $e->getMessage();

            foreach ($uploaded_files as $uploaded_file) {
                if (is_file($uploaded_file)) {
                    @unlink($uploaded_file);
                }
            }
        }
    }
}

/* =========================================================
   DATA KATEGORI UNTUK FORM
========================================================= */
$kategoriResult = mysqli_query(
    $conn,
    "SELECT id_kategori, nama_kategori
     FROM kategori
     ORDER BY nama_kategori ASC"
);

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Produk - Toko ATK & Alat Jahit</title>
    <link rel="stylesheet" href="../assets/css/admin.css">

    <style>
        .form-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 24px;
            max-width: 1100px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .form-group.full {
            grid-column: 1 / -1;
        }

        .form-group label {
            font-weight: 600;
            color: #1f2937;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 11px 13px;
            border: 1px solid #d1d5db;
            border-radius: 9px;
            font-size: 14px;
            box-sizing: border-box;
            background: #fff;
        }

        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #1e3a5f;
            box-shadow: 0 0 0 3px rgba(30, 58, 95, 0.10);
        }

        .form-help {
            font-size: 12px;
            color: #6b7280;
            line-height: 1.5;
        }

        .alert-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #b91c1c;
            padding: 12px 15px;
            border-radius: 9px;
            margin-bottom: 20px;
        }

        .info-stock {
            grid-column: 1 / -1;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #1e40af;
            border-radius: 10px;
            padding: 13px 15px;
            line-height: 1.6;
            font-size: 13px;
        }

        .info-stock strong {
            color: #1e3a5f;
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }

        .btn-cancel,
        .btn-save,
        .btn-add-varian,
        .btn-remove-varian {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 11px 18px;
            border-radius: 9px;
            text-decoration: none;
            border: none;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
        }

        .btn-cancel {
            background: #f3f4f6;
            color: #374151;
        }

        .btn-save,
        .btn-add-varian {
            background: #1e3a5f;
            color: #fff;
        }

        .btn-remove-varian {
            background: #fef2f2;
            color: #b91c1c;
            padding: 8px 12px;
        }

        .btn-cancel:hover {
            background: #e5e7eb;
        }

        .btn-save:hover,
        .btn-add-varian:hover {
            background: #162d49;
        }

        .btn-remove-varian:hover {
            background: #fee2e2;
        }

        .variant-section {
            grid-column: 1 / -1;
            margin-top: 5px;
            padding: 20px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
        }

        .variant-section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-bottom: 18px;
        }

        .variant-section-header h3 {
            margin: 0;
            color: #1e3a5f;
            font-size: 17px;
        }

        .variant-section-header p {
            margin: 4px 0 0;
            color: #6b7280;
            font-size: 12px;
        }

        .variant-item {
            background: #fff;
            border: 1px solid #dbe3ea;
            border-radius: 10px;
            padding: 18px;
            margin-bottom: 15px;
        }

        .variant-item:last-child {
            margin-bottom: 0;
        }

        .variant-item-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 15px;
        }

        .variant-item-header strong {
            color: #1e3a5f;
            font-size: 14px;
        }

        .variant-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 15px;
        }

        .variant-stock-note {
            grid-column: 1 / -1;
            background: #f1f5f9;
            border-radius: 8px;
            padding: 10px 12px;
            font-size: 12px;
            color: #475569;
            line-height: 1.5;
        }

        .hidden {
            display: none !important;
        }

        .empty-variant {
            padding: 25px;
            text-align: center;
            color: #6b7280;
            background: #fff;
            border: 1px dashed #cbd5e1;
            border-radius: 9px;
        }

        @media (max-width: 700px) {
            .form-card {
                padding: 16px;
            }

            .form-grid,
            .variant-grid {
                grid-template-columns: 1fr;
            }

            .form-group.full,
            .variant-stock-note,
            .info-stock {
                grid-column: auto;
            }

            .form-actions {
                flex-direction: column;
            }

            .btn-cancel,
            .btn-save {
                width: 100%;
            }

            .variant-section-header {
                flex-direction: column;
                align-items: stretch;
            }

            .btn-add-varian {
                width: 100%;
            }

            .variant-item {
                padding: 14px;
            }

            .variant-item-header {
                align-items: flex-start;
                flex-direction: column;
            }

            .btn-remove-varian {
                width: 100%;
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
            <div class="page-header">
                <div>
                    <h1>Tambah Produk</h1>
                    <p>Tambahkan produk baru ke dalam katalog toko.</p>
                </div>
            </div>

            <?php if ($error !== ''): ?>
                <div class="alert-error">
                    <?= htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <div class="form-card">
                <form
                    method="POST"
                    enctype="multipart/form-data"
                    id="form-tambah-produk"
                >
                    <div class="form-grid">
                        <div class="form-group full">
                            <label for="nama_produk">Nama Produk *</label>
                            <input
                                type="text"
                                id="nama_produk"
                                name="nama_produk"
                                value="<?= htmlspecialchars($_POST['nama_produk'] ?? ''); ?>"
                                placeholder="Contoh: Buku Tulis Sinar Dunia"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label for="id_kategori">Kategori *</label>
                            <select
                                id="id_kategori"
                                name="id_kategori"
                                required
                            >
                                <option value="">-- Pilih Kategori --</option>
                                <?php while ($kategori = mysqli_fetch_assoc($kategoriResult)): ?>
                                    <option
                                        value="<?= $kategori['id_kategori']; ?>"
                                        <?= (($_POST['id_kategori'] ?? '') == $kategori['id_kategori']) ? 'selected' : ''; ?>
                                    >
                                        <?= htmlspecialchars($kategori['nama_kategori']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="memiliki_varian">Produk Memiliki Varian? *</label>
                            <select
                                id="memiliki_varian"
                                name="memiliki_varian"
                                required
                                onchange="toggleVarian()"
                            >
                                <option
                                    value="tidak"
                                    <?= (($_POST['memiliki_varian'] ?? 'tidak') === 'tidak') ? 'selected' : ''; ?>
                                >
                                    Tidak
                                </option>
                                <option
                                    value="ya"
                                    <?= (($_POST['memiliki_varian'] ?? '') === 'ya') ? 'selected' : ''; ?>
                                >
                                    Ya
                                </option>
                            </select>
                            <span class="form-help">
                                Pilih Ya jika produk memiliki pilihan seperti warna, ukuran, atau jenis.
                            </span>
                        </div>

                        <div class="form-group" id="harga-produk-group">
                            <label for="harga">Harga *</label>
                            <input
                                type="number"
                                id="harga"
                                name="harga"
                                min="0"
                                step="1"
                                value="<?= htmlspecialchars($_POST['harga'] ?? ''); ?>"
                                placeholder="Contoh: 5000"
                            >
                            <span class="form-help" id="harga-help">
                                Digunakan jika produk tidak memiliki varian.
                            </span>
                        </div>

                        <div class="form-group">
                            <label for="gambar">Gambar Produk</label>
                            <input
                                type="file"
                                id="gambar"
                                name="gambar"
                                accept=".jpg,.jpeg,.png,.webp"
                            >
                            <span class="form-help">
                                JPG, JPEG, PNG, WEBP. Maksimal 2 MB.
                            </span>
                        </div>

                        <div class="info-stock">
                            <strong>Informasi Stok</strong><br>
                            Stok produk tidak diatur pada halaman Tambah Produk.
                            Produk baru akan memiliki stok awal <strong>0</strong>.
                            Pengaturan stok, stok minimum, dan lokasi/rak akan dilakukan
                            melalui modul <strong>Gudang / Pengadaan Stok</strong>.
                        </div>

                        <div class="form-group full">
                            <label for="deskripsi">Deskripsi</label>
                            <textarea
                                id="deskripsi"
                                name="deskripsi"
                                placeholder="Masukkan deskripsi produk..."
                            ><?= htmlspecialchars($_POST['deskripsi'] ?? ''); ?></textarea>
                        </div>

                        <div
                            class="variant-section <?= (($_POST['memiliki_varian'] ?? '') === 'ya') ? '' : 'hidden'; ?>"
                            id="variant-section"
                        >
                            <div class="variant-section-header">
                                <div>
                                    <h3>Varian Produk</h3>
                                    <p>
                                        Tambahkan varian sesuai kebutuhan produk.
                                        Stok setiap varian dikelola melalui Gudang / Pengadaan Stok.
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    class="btn-add-varian"
                                    onclick="tambahVarian()"
                                >
                                    + Tambah Varian
                                </button>
                            </div>

                            <div
                                class="empty-variant <?= !empty($_POST['nama_varian']) ? 'hidden' : ''; ?>"
                                id="variant-empty"
                            >
                                Belum ada varian. Klik
                                <b>+ Tambah Varian</b>
                                untuk menambahkan.
                            </div>

                            <div id="variant-list">
                                <?php
                                $old_nama_varian = $_POST['nama_varian'] ?? [];
                                $old_harga_varian = $_POST['harga_varian'] ?? [];

                                if (is_array($old_nama_varian)):
                                    foreach ($old_nama_varian as $index => $old_nama):
                                ?>
                                    <div class="variant-item" data-index="<?= (int) $index; ?>">
                                        <div class="variant-item-header">
                                            <strong class="variant-number">
                                                Varian <?= (int) $index + 1; ?>
                                            </strong>
                                            <button
                                                type="button"
                                                class="btn-remove-varian"
                                                onclick="hapusVarian(this)"
                                            >
                                                Hapus Varian
                                            </button>
                                        </div>

                                        <div class="variant-grid">
                                            <div class="form-group">
                                                <label>Nama Varian *</label>
                                                <input
                                                    type="text"
                                                    name="nama_varian[<?= (int) $index; ?>]"
                                                    value="<?= htmlspecialchars($old_nama); ?>"
                                                    placeholder="Contoh: Merah / Ukuran M"
                                                    required
                                                >
                                            </div>

                                            <div class="form-group">
                                                <label>Harga *</label>
                                                <input
                                                    type="number"
                                                    name="harga_varian[<?= (int) $index; ?>]"
                                                    min="0"
                                                    step="1"
                                                    value="<?= htmlspecialchars($old_harga_varian[$index] ?? '0'); ?>"
                                                    required
                                                >
                                            </div>

                                            <div class="form-group full">
                                                <label>Gambar Varian</label>
                                                <input
                                                    type="file"
                                                    name="gambar_varian[<?= (int) $index; ?>][]"
                                                    accept=".jpg,.jpeg,.png,.webp"
                                                    multiple
                                                >
                                                <span class="form-help">
                                                    Bisa memilih beberapa gambar. Maksimal 5 MB per gambar.
                                                </span>
                                            </div>

                                            <div class="variant-stock-note">
                                                Stok awal varian otomatis <strong>0</strong>.
                                                Stok, stok minimum, dan lokasi/rak dikelola melalui
                                                <strong>Gudang / Pengadaan Stok</strong>.
                                            </div>
                                        </div>
                                    </div>
                                <?php
                                    endforeach;
                                endif;
                                ?>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="index.php" class="btn-cancel">Batal</a>
                        <button type="submit" class="btn-save">+ Simpan Produk</button>
                    </div>
                </form>
            </div>
        </main>

        <?php include "../includes/footer.php"; ?>
    </div>
</div>

<script>
    let nomorVarian = <?= (
        !empty($_POST['nama_varian']) &&
        is_array($_POST['nama_varian'])
    ) ? count($_POST['nama_varian']) : 0; ?>;

    /* TOGGLE VARIAN */
    function toggleVarian() {
        const pilihan = document.getElementById('memiliki_varian').value;
        const variantSection = document.getElementById('variant-section');
        const hargaGroup = document.getElementById('harga-produk-group');
        const harga = document.getElementById('harga');

        if (pilihan === 'ya') {
            variantSection.classList.remove('hidden');
            hargaGroup.classList.add('hidden');
            harga.removeAttribute('required');
        } else {
            variantSection.classList.add('hidden');
            hargaGroup.classList.remove('hidden');
            harga.setAttribute('required', 'required');
        }
    }

    /* TAMBAH VARIAN */
    function tambahVarian() {
        const list = document.getElementById('variant-list');
        const empty = document.getElementById('variant-empty');

        if (empty) {
            empty.classList.add('hidden');
        }

        const index = nomorVarian++;
        const div = document.createElement('div');
        div.className = 'variant-item';
        div.dataset.index = index;

        div.innerHTML = `
            <div class="variant-item-header">
                <strong class="variant-number">Varian ${index + 1}</strong>
                <button
                    type="button"
                    class="btn-remove-varian"
                    onclick="hapusVarian(this)"
                >
                    Hapus Varian
                </button>
            </div>

            <div class="variant-grid">
                <div class="form-group">
                    <label>Nama Varian *</label>
                    <input
                        type="text"
                        name="nama_varian[${index}]"
                        placeholder="Contoh: Merah / Ukuran M"
                        required
                    >
                </div>

                <div class="form-group">
                    <label>Harga *</label>
                    <input
                        type="number"
                        name="harga_varian[${index}]"
                        min="0"
                        step="1"
                        value="0"
                        required
                    >
                </div>

                <div class="form-group full">
                    <label>Gambar Varian</label>
                    <input
                        type="file"
                        name="gambar_varian[${index}][]"
                        accept=".jpg,.jpeg,.png,.webp"
                        multiple
                    >
                    <span class="form-help">
                        Bisa memilih beberapa gambar. Maksimal 5 MB per gambar.
                    </span>
                </div>

                <div class="variant-stock-note">
                    Stok awal varian otomatis <strong>0</strong>.
                    Stok, stok minimum, dan lokasi/rak dikelola melalui
                    <strong>Gudang / Pengadaan Stok</strong>.
                </div>
            </div>
        `;

        list.appendChild(div);
        reindexVarian();
    }

    /* HAPUS VARIAN */
    function hapusVarian(button) {
        const item = button.closest('.variant-item');

        if (item) {
            item.remove();
        }

        reindexVarian();

        const list = document.getElementById('variant-list');
        const empty = document.getElementById('variant-empty');

        if (list.querySelectorAll('.variant-item').length === 0) {
            empty.classList.remove('hidden');
        }
    }

    /* REINDEX VARIAN */
    function reindexVarian() {
        const items = document.querySelectorAll('#variant-list .variant-item');

        items.forEach((item, index) => {
            item.dataset.index = index;

            const number = item.querySelector('.variant-number');

            if (number) {
                number.textContent = 'Varian ' + (index + 1);
            }

            const nama = item.querySelector('input[name^="nama_varian"]');
            const harga = item.querySelector('input[name^="harga_varian"]');
            const gambar = item.querySelector('input[name^="gambar_varian"]');

            if (nama) {
                nama.name = `nama_varian[${index}]`;
            }

            if (harga) {
                harga.name = `harga_varian[${index}]`;
            }

            if (gambar) {
                gambar.name = `gambar_varian[${index}][]`;
            }
        });

        nomorVarian = items.length;
    }

    /* VALIDASI SUBMIT */
    document
        .getElementById('form-tambah-produk')
        .addEventListener('submit', function(event) {
            const pilihan = document.getElementById('memiliki_varian').value;

            if (pilihan === 'ya') {
                const jumlah = document.querySelectorAll(
                    '#variant-list .variant-item'
                ).length;

                if (jumlah === 0) {
                    event.preventDefault();
                    alert('Tambahkan minimal satu varian produk.');
                    return;
                }
            }
        });

    /* SAAT HALAMAN DIBUKA */
    document.addEventListener('DOMContentLoaded', function() {
        toggleVarian();
        reindexVarian();
    });
</script>

<script src="../assets/js/admin.js"></script>
</body>
</html>