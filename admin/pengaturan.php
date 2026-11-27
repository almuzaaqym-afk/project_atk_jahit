<?php

require_once "includes/hak_akses.php";

$adminLogin = cekOwner();

require_once "../config/koneksi.php";


// =====================================================
// AMBIL DATA PENGATURAN
// =====================================================

$query = mysqli_query(
    $conn,
    "SELECT * FROM pengaturan_toko ORDER BY id_pengaturan ASC LIMIT 1"
);

$pengaturan = mysqli_fetch_assoc($query);


// Jika belum ada data
if (!$pengaturan) {

    mysqli_query(
        $conn,
        "INSERT INTO pengaturan_toko (
            nama_toko,
            alamat,
            telepon,
            email,
            jam_operasional,
            whatsapp,
            google_maps,
            deskripsi,
            pickup,
            notifikasi
        ) VALUES (
            'Toko ATK & Alat Jahit',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            1,
            1
        )"
    );

    $query = mysqli_query(
        $conn,
        "SELECT * FROM pengaturan_toko ORDER BY id_pengaturan ASC LIMIT 1"
    );

    $pengaturan = mysqli_fetch_assoc($query);
}


$id_pengaturan = (int) $pengaturan['id_pengaturan'];


// =====================================================
// PROSES SIMPAN
// =====================================================

$pesan = '';
$tipe_pesan = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nama_toko = trim($_POST['nama_toko'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');
    $telepon = trim($_POST['telepon'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $jam_operasional = trim($_POST['jam_operasional'] ?? '');
    $whatsapp = trim($_POST['whatsapp'] ?? '');
    $google_maps = trim($_POST['google_maps'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');

    $pickup = isset($_POST['pickup']) ? 1 : 0;
    $notifikasi = isset($_POST['notifikasi']) ? 1 : 0;


    // Validasi nama toko
    if ($nama_toko === '') {

        $pesan = "Nama toko wajib diisi.";
        $tipe_pesan = "error";

    } else {

        // =================================================
        // DATA LOGO LAMA
        // =================================================

        $logo_lama = $pengaturan['logo'];

        $logo_baru = $logo_lama;


        // =================================================
        // UPLOAD LOGO
        // =================================================

        if (
            isset($_FILES['logo']) &&
            $_FILES['logo']['error'] !== UPLOAD_ERR_NO_FILE
        ) {

            if ($_FILES['logo']['error'] === UPLOAD_ERR_OK) {

                $nama_file = $_FILES['logo']['name'];
                $tmp_file = $_FILES['logo']['tmp_name'];
                $ukuran_file = $_FILES['logo']['size'];

                $ekstensi = strtolower(
                    pathinfo($nama_file, PATHINFO_EXTENSION)
                );

                $ekstensi_diizinkan = [
                    'jpg',
                    'jpeg',
                    'png',
                    'webp'
                ];

                if (!in_array($ekstensi, $ekstensi_diizinkan, true)) {

                    $pesan = "Format logo harus JPG, JPEG, PNG, atau WEBP.";
                    $tipe_pesan = "error";

                } elseif ($ukuran_file > 2 * 1024 * 1024) {

                    $pesan = "Ukuran logo maksimal 2 MB.";
                    $tipe_pesan = "error";

                } else {

                    $folder_logo = __DIR__ . "/assets/images/settings/";

                    if (!is_dir($folder_logo)) {
                        mkdir($folder_logo, 0777, true);
                    }

                    $nama_logo = "logo_toko_" . time() . "." . $ekstensi;

                    $tujuan_logo = $folder_logo . $nama_logo;

                    if (move_uploaded_file($tmp_file, $tujuan_logo)) {

                        // Hapus logo lama
                        if (
                            !empty($logo_lama) &&
                            file_exists(__DIR__ . "/" . $logo_lama)
                        ) {
                            @unlink(
                                __DIR__ . "/" . $logo_lama
                            );
                        }

                        $logo_baru =
                            "assets/images/settings/" . $nama_logo;

                    } else {

                        $pesan = "Logo gagal diupload.";
                        $tipe_pesan = "error";
                    }
                }

            } else {

                $pesan = "Terjadi kesalahan saat upload logo.";
                $tipe_pesan = "error";
            }
        }


        // =================================================
        // UPLOAD QRIS
        // =================================================

        $qris_lama = $pengaturan['qris'];

        $qris_baru = $qris_lama;


        if (
            $pesan === '' &&
            isset($_FILES['qris']) &&
            $_FILES['qris']['error'] !== UPLOAD_ERR_NO_FILE
        ) {

            if ($_FILES['qris']['error'] === UPLOAD_ERR_OK) {

                $nama_file = $_FILES['qris']['name'];
                $tmp_file = $_FILES['qris']['tmp_name'];
                $ukuran_file = $_FILES['qris']['size'];

                $ekstensi = strtolower(
                    pathinfo($nama_file, PATHINFO_EXTENSION)
                );

                $ekstensi_diizinkan = [
                    'jpg',
                    'jpeg',
                    'png',
                    'webp'
                ];

                if (!in_array($ekstensi, $ekstensi_diizinkan, true)) {

                    $pesan = "Format QRIS harus JPG, JPEG, PNG, atau WEBP.";
                    $tipe_pesan = "error";

                } elseif ($ukuran_file > 5 * 1024 * 1024) {

                    $pesan = "Ukuran QRIS maksimal 5 MB.";
                    $tipe_pesan = "error";

                } else {

                    $folder_qris = __DIR__ . "/assets/images/settings/";

                    if (!is_dir($folder_qris)) {
                        mkdir($folder_qris, 0777, true);
                    }

                    $nama_qris = "qris_" . time() . "." . $ekstensi;

                    $tujuan_qris = $folder_qris . $nama_qris;

                    if (move_uploaded_file($tmp_file, $tujuan_qris)) {

                        // Hapus QRIS lama
                        if (
                            !empty($qris_lama) &&
                            file_exists(__DIR__ . "/" . $qris_lama)
                        ) {
                            @unlink(
                                __DIR__ . "/" . $qris_lama
                            );
                        }

                        $qris_baru =
                            "assets/images/settings/" . $nama_qris;

                    } else {

                        $pesan = "QRIS gagal diupload.";
                        $tipe_pesan = "error";
                    }
                }

            } else {

                $pesan = "Terjadi kesalahan saat upload QRIS.";
                $tipe_pesan = "error";
            }
        }


        // =================================================
        // SIMPAN DATABASE
        // =================================================

        if ($pesan === '') {

            $stmt = mysqli_prepare(
                $conn,
                "UPDATE pengaturan_toko SET
                    nama_toko = ?,
                    logo = ?,
                    alamat = ?,
                    telepon = ?,
                    email = ?,
                    jam_operasional = ?,
                    whatsapp = ?,
                    google_maps = ?,
                    deskripsi = ?,
                    qris = ?,
                    pickup = ?,
                    notifikasi = ?
                 WHERE id_pengaturan = ?"
            );

            mysqli_stmt_bind_param(
                $stmt,
                "ssssssssssiii",
                $nama_toko,
                $logo_baru,
                $alamat,
                $telepon,
                $email,
                $jam_operasional,
                $whatsapp,
                $google_maps,
                $deskripsi,
                $qris_baru,
                $pickup,
                $notifikasi,
                $id_pengaturan
            );

            if (mysqli_stmt_execute($stmt)) {

                $pesan = "Pengaturan toko berhasil disimpan.";
                $tipe_pesan = "success";

                // Refresh data
                $query = mysqli_query(
                    $conn,
                    "SELECT * FROM pengaturan_toko
                     WHERE id_pengaturan = $id_pengaturan
                     LIMIT 1"
                );

                $pengaturan = mysqli_fetch_assoc($query);

            } else {

                $pesan = "Pengaturan gagal disimpan: " . mysqli_error($conn);
                $tipe_pesan = "error";
            }

            mysqli_stmt_close($stmt);
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

    <title>Pengaturan Toko</title>

    <link
        rel="stylesheet"
        href="assets/css/admin.css"
    >


    <style>

        .pengaturan-wrapper {
            max-width: 1100px;
        }

        .pengaturan-card {
            background: #ffffff;
            border-radius: 14px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 5px 18px rgba(0, 0, 0, 0.06);
        }

        .pengaturan-card h3 {
            margin: 0 0 20px;
            font-size: 18px;
            color: #172033;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-group.full {
            grid-column: 1 / -1;
        }

        .form-group label {
            font-size: 14px;
            font-weight: 600;
            color: #333b4f;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            box-sizing: border-box;
            border: 1px solid #d9dee8;
            border-radius: 8px;
            padding: 11px 13px;
            font-size: 14px;
            outline: none;
            transition: 0.2s;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            border-color: #243b64;
            box-shadow: 0 0 0 3px rgba(36, 59, 100, 0.08);
        }

        .form-group textarea {
            min-height: 110px;
            resize: vertical;
        }

        .form-help {
            font-size: 12px;
            color: #777f8f;
        }

        .upload-preview {
            margin-top: 10px;
        }

        .upload-preview img {
            max-width: 180px;
            max-height: 130px;
            object-fit: contain;
            border: 1px solid #e0e4eb;
            border-radius: 10px;
            padding: 8px;
            background: #f8f9fb;
        }

        .switch-row {
            display: flex;
            gap: 30px;
            flex-wrap: wrap;
        }

        .switch-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .switch-item input {
            width: 18px;
            height: 18px;
        }

        .alert {
            padding: 13px 16px;
            border-radius: 9px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-success {
            background: #eaf7ee;
            color: #1d6b38;
            border: 1px solid #bce3c8;
        }

        .alert-error {
            background: #fff0f0;
            color: #a52a2a;
            border: 1px solid #f0bcbc;
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            margin-top: 10px;
        }

        .btn-simpan {
            border: none;
            background: #1f355b;
            color: #ffffff;
            padding: 12px 22px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-simpan:hover {
            opacity: 0.9;
        }

        @media (max-width: 768px) {

            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-group.full {
                grid-column: auto;
            }

            .pengaturan-card {
                padding: 18px;
            }

        }

    </style>

</head>


<body>


<div class="admin-layout">


    <?php include "includes/sidebar.php"; ?>


    <main class="admin-main">


        <?php include "includes/navbar.php"; ?>


        <div class="admin-content">


            <div class="page-header">

                <div>

                    <h1>Pengaturan Toko</h1>

                    <p>
                        Kelola informasi dan pengaturan toko.
                    </p>

                </div>

            </div>


            <div class="pengaturan-wrapper">


                <?php if ($pesan !== ''): ?>

                    <div
                        class="alert <?= $tipe_pesan === 'success'
                            ? 'alert-success'
                            : 'alert-error'; ?>"
                    >

                        <?= htmlspecialchars($pesan); ?>

                    </div>

                <?php endif; ?>


                <form
                    method="POST"
                    enctype="multipart/form-data"
                >


                    <!-- INFORMASI TOKO -->

                    <div class="pengaturan-card">

                        <h3>Informasi Toko</h3>


                        <div class="form-grid">


                            <div class="form-group">

                                <label>Nama Toko *</label>

                                <input
                                    type="text"
                                    name="nama_toko"
                                    value="<?= htmlspecialchars(
                                        $pengaturan['nama_toko'] ?? ''
                                    ); ?>"
                                    required
                                >

                            </div>


                            <div class="form-group">

                                <label>Telepon</label>

                                <input
                                    type="text"
                                    name="telepon"
                                    value="<?= htmlspecialchars(
                                        $pengaturan['telepon'] ?? ''
                                    ); ?>"
                                    placeholder="Contoh: 02112345678"
                                >

                            </div>


                            <div class="form-group">

                                <label>Email</label>

                                <input
                                    type="email"
                                    name="email"
                                    value="<?= htmlspecialchars(
                                        $pengaturan['email'] ?? ''
                                    ); ?>"
                                    placeholder="contoh@email.com"
                                >

                            </div>


                            <div class="form-group">

                                <label>WhatsApp</label>

                                <input
                                    type="text"
                                    name="whatsapp"
                                    value="<?= htmlspecialchars(
                                        $pengaturan['whatsapp'] ?? ''
                                    ); ?>"
                                    placeholder="Contoh: 628123456789"
                                >

                            </div>


                            <div class="form-group full">

                                <label>Alamat Toko</label>

                                <textarea
                                    name="alamat"
                                    placeholder="Masukkan alamat lengkap toko..."
                                ><?= htmlspecialchars(
                                    $pengaturan['alamat'] ?? ''
                                ); ?></textarea>

                            </div>


                            <div class="form-group">

                                <label>Jam Operasional</label>

                                <input
                                    type="text"
                                    name="jam_operasional"
                                    value="<?= htmlspecialchars(
                                        $pengaturan['jam_operasional'] ?? ''
                                    ); ?>"
                                    placeholder="Contoh: Senin - Sabtu, 08.00 - 17.00"
                                >

                            </div>


                            <div class="form-group">

                                <label>Google Maps</label>

                                <input
                                    type="text"
                                    name="google_maps"
                                    value="<?= htmlspecialchars(
                                        $pengaturan['google_maps'] ?? ''
                                    ); ?>"
                                    placeholder="Link Google Maps"
                                >

                            </div>


                            <div class="form-group full">

                                <label>Deskripsi Toko</label>

                                <textarea
                                    name="deskripsi"
                                    placeholder="Deskripsi singkat mengenai toko..."
                                ><?= htmlspecialchars(
                                    $pengaturan['deskripsi'] ?? ''
                                ); ?></textarea>

                            </div>


                        </div>

                    </div>



                    <!-- LOGO -->

                    <div class="pengaturan-card">

                        <h3>Logo Toko</h3>


                        <div class="form-group">

                            <label>Upload Logo</label>

                            <input
                                type="file"
                                name="logo"
                                accept=".jpg,.jpeg,.png,.webp"
                            >

                            <span class="form-help">
                                Format JPG, JPEG, PNG, WEBP. Maksimal 2 MB.
                            </span>


                            <?php if (!empty($pengaturan['logo'])): ?>

                                <div class="upload-preview">

                                    <img
                                        src="<?= htmlspecialchars(
                                            $pengaturan['logo']
                                        ); ?>"
                                        alt="Logo Toko"
                                    >

                                </div>

                            <?php endif; ?>


                        </div>

                    </div>



                    <!-- QRIS -->

                    <div class="pengaturan-card">

                        <h3>Pembayaran QRIS</h3>


                        <div class="form-group">

                            <label>Upload QRIS</label>

                            <input
                                type="file"
                                name="qris"
                                accept=".jpg,.jpeg,.png,.webp"
                            >

                            <span class="form-help">
                                Format JPG, JPEG, PNG, WEBP. Maksimal 5 MB.
                            </span>


                            <?php if (!empty($pengaturan['qris'])): ?>

                                <div class="upload-preview">

                                    <img
                                        src="<?= htmlspecialchars(
                                            $pengaturan['qris']
                                        ); ?>"
                                        alt="QRIS Toko"
                                    >

                                </div>

                            <?php endif; ?>


                        </div>

                    </div>



                    <!-- OPERASIONAL -->

                    <div class="pengaturan-card">

                        <h3>Operasional Toko</h3>


                        <div class="switch-row">


                            <label class="switch-item">

                                <input
                                    type="checkbox"
                                    name="pickup"
                                    value="1"
                                    <?= !empty($pengaturan['pickup'])
                                        ? 'checked'
                                        : ''; ?>
                                >

                                <span>
                                    Pickup di Toko Aktif
                                </span>

                            </label>


                            <label class="switch-item">

                                <input
                                    type="checkbox"
                                    name="notifikasi"
                                    value="1"
                                    <?= !empty($pengaturan['notifikasi'])
                                        ? 'checked'
                                        : ''; ?>
                                >

                                <span>
                                    Notifikasi Aktif
                                </span>

                            </label>


                        </div>

                    </div>



                    <!-- SIMPAN -->

                    <div class="form-actions">

                        <button
                            type="submit"
                            class="btn-simpan"
                        >

                            💾 Simpan Pengaturan

                        </button>

                    </div>


                </form>


            </div>


        </div>


    </main>


</div>


<script src="assets/js/admin.js"></script>

</body>

</html>