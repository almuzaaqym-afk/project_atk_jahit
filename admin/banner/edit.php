<?php

session_start();

if (!isset($_SESSION['id_admin'])) {
    header("Location: ../login.php");
    exit;
}

require_once "../../config/koneksi.php";
require_once __DIR__ . "/../includes/log_aktivitas.php";

/* =========================================================
   AMBIL ID BANNER
========================================================= */

$id_banner = (int)($_GET['id'] ?? 0);

if ($id_banner <= 0) {
    header("Location: index.php?status=gagal");
    exit;
}

/* =========================================================
   DAFTAR TUJUAN TOMBOL
========================================================= */

$daftar_tujuan = [
    'katalog' => [
        'nama' => 'Katalog Produk',
        'url' => '/project_atk_jahit/pelanggan/pages/katalog.php'
    ],
    'tentang' => [
        'nama' => 'Tentang Kami',
        'url' => '/project_atk_jahit/pelanggan/pages/tentang.php'
    ],
    'lokasi' => [
        'nama' => 'Lokasi Toko',
        'url' => '/project_atk_jahit/pelanggan/pages/lokasi.php'
    ],
    'login' => [
        'nama' => 'Login Pelanggan',
        'url' => '/project_atk_jahit/pelanggan/login.php'
    ],
    'register' => [
        'nama' => 'Daftar Pelanggan',
        'url' => '/project_atk_jahit/pelanggan/register.php'
    ],
    'custom' => [
        'nama' => 'Custom URL',
        'url' => ''
    ]
];

/* =========================================================
   AMBIL DATA BANNER
========================================================= */

$stmt = mysqli_prepare(
    $conn,
    "SELECT *
     FROM banner
     WHERE id_banner = ?
     LIMIT 1"
);

mysqli_stmt_bind_param($stmt, "i", $id_banner);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$banner = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$banner) {
    header("Location: index.php?status=gagal");
    exit;
}

$error = '';

/* =========================================================
   TENTUKAN TUJUAN BANNER LAMA
========================================================= */

$tujuan_tombol_lama = 'custom';
$custom_url_lama = $banner['link_tombol'];

foreach ($daftar_tujuan as $kode => $data) {
    if (
        $kode !== 'custom' &&
        $banner['link_tombol'] === $data['url']
    ) {
        $tujuan_tombol_lama = $kode;
        $custom_url_lama = '';
        break;
    }
}

/* =========================================================
   PROSES UPDATE
========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $judul = trim($_POST['judul'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $teks_tombol = trim($_POST['teks_tombol'] ?? '');
    $tujuan_tombol = $_POST['tujuan_tombol'] ?? 'katalog';
    $custom_url = trim($_POST['custom_url'] ?? '');
    $urutan = (int)($_POST['urutan'] ?? 0);
    $status = $_POST['status'] ?? 'Aktif';

    /* =====================================================
       VALIDASI JUDUL
    ===================================================== */

    if ($judul === '') {
        $error = "Judul banner wajib diisi.";
    }

    /* =====================================================
       VALIDASI TUJUAN
    ===================================================== */

    if (!array_key_exists($tujuan_tombol, $daftar_tujuan)) {
        $tujuan_tombol = 'katalog';
    }

    /* =====================================================
       VALIDASI STATUS
    ===================================================== */

    if (!in_array($status, ['Aktif', 'Tidak Aktif'], true)) {
        $status = 'Aktif';
    }

    if ($urutan < 0) {
        $urutan = 0;
    }

    /* =====================================================
       TENTUKAN LINK TOMBOL
    ===================================================== */

    $link_tombol = '';

    if ($error === '') {

        if ($tujuan_tombol === 'custom') {

            if ($custom_url === '') {
                $error = "Custom URL wajib diisi.";
            } else {
                $link_tombol = $custom_url;
            }

        } else {

            $link_tombol = $daftar_tujuan[$tujuan_tombol]['url'];

        }
    }

    /* =====================================================
       FILE GAMBAR
    ===================================================== */

    $nama_file_baru = $banner['gambar'];
    $file_baru = false;
    $tujuan_baru = '';

    /* =====================================================
       UPLOAD GAMBAR BARU
    ===================================================== */

    if (
        $error === '' &&
        isset($_FILES['gambar']) &&
        $_FILES['gambar']['error'] !== UPLOAD_ERR_NO_FILE
    ) {

        if ($_FILES['gambar']['error'] !== UPLOAD_ERR_OK) {

            $error = "Gambar baru gagal diupload.";

        } else {

            $file = $_FILES['gambar'];

            $allowed_extensions = [
                'jpg',
                'jpeg',
                'png',
                'webp'
            ];

            $extension = strtolower(
                pathinfo($file['name'], PATHINFO_EXTENSION)
            );

            if (!in_array($extension, $allowed_extensions, true)) {

                $error = "Format gambar harus JPG, JPEG, PNG, atau WEBP.";

            } elseif ($file['size'] > 5 * 1024 * 1024) {

                $error = "Ukuran gambar maksimal 5 MB.";

            } elseif (getimagesize($file['tmp_name']) === false) {

                $error = "File yang diupload bukan gambar yang valid.";

            } else {

                $upload_dir = __DIR__ . "/../assets/images/banners/";

                if (!is_dir($upload_dir)) {
                    if (
                        !mkdir($upload_dir, 0755, true) &&
                        !is_dir($upload_dir)
                    ) {
                        $error = "Folder penyimpanan gambar gagal dibuat.";
                    }
                }

                if ($error === '') {

                    $nama_file_baru =
                        'banner_' .
                        date('YmdHis') .
                        '_' .
                        bin2hex(random_bytes(4)) .
                        '.' .
                        $extension;

                    $tujuan_baru = $upload_dir . $nama_file_baru;

                    if (
                        !move_uploaded_file(
                            $file['tmp_name'],
                            $tujuan_baru
                        )
                    ) {
                        $error = "Gambar baru gagal disimpan.";
                    } else {
                        $file_baru = true;
                    }
                }
            }
        }
    }

    /* =====================================================
       UPDATE DATABASE
    ===================================================== */

    if ($error === '') {

        $stmt = mysqli_prepare(
            $conn,
            "UPDATE banner
             SET
                judul = ?,
                deskripsi = ?,
                gambar = ?,
                teks_tombol = ?,
                link_tombol = ?,
                urutan = ?,
                status = ?
             WHERE id_banner = ?"
        );

        if (!$stmt) {

            if ($file_baru) {
                @unlink($tujuan_baru);
            }

            $error = "Gagal menyiapkan proses update.";

        } else {

            mysqli_stmt_bind_param(
                $stmt,
                "sssssisi",
                $judul,
                $deskripsi,
                $nama_file_baru,
                $teks_tombol,
                $link_tombol,
                $urutan,
                $status,
                $id_banner
            );

            $berhasil = mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            if ($berhasil) {

                /* =================================================
                   CATAT LOG AKTIVITAS
                ================================================= */

                try {

                    $judul_lama = (string)($banner['judul'] ?? '');
                    $gambar_lama_nama = (string)($banner['gambar'] ?? '');
                    $status_lama = (string)($banner['status'] ?? '');

                    $perubahan_gambar = $file_baru
                        ? "Gambar diganti dari {$gambar_lama_nama} menjadi {$nama_file_baru}"
                        : "Gambar tetap {$gambar_lama_nama}";

                    $detail_log =
                        "Mengubah banner ID: {$id_banner}. " .
                        "Judul: {$judul_lama} menjadi {$judul}; " .
                        "status: {$status_lama} menjadi {$status}; " .
                        "{$perubahan_gambar}.";

                    catatLogAktivitas(
                        $conn,
                        'banner',
                        'Edit Banner',
                        $detail_log
                    );

                } catch (Throwable $e) {
                    // Kegagalan log tidak membatalkan perubahan banner.
                }

                /* =================================================
                   HAPUS GAMBAR LAMA JIKA DIGANTI
                ================================================= */

                if ($file_baru && !empty($banner['gambar'])) {

                    $gambar_lama =
                        __DIR__ .
                        "/../assets/images/banners/" .
                        basename($banner['gambar']);

                    if (is_file($gambar_lama)) {
                        @unlink($gambar_lama);
                    }
                }

                header("Location: index.php?status=edit");
                exit;

            } else {

                if ($file_baru) {
                    @unlink($tujuan_baru);
                }

                $error = "Banner gagal diperbarui.";
            }
        }
    }
}

/* =========================================================
   NILAI FORM
========================================================= */

$form_judul = $_POST['judul'] ?? $banner['judul'];
$form_deskripsi = $_POST['deskripsi'] ?? $banner['deskripsi'];
$form_teks_tombol = $_POST['teks_tombol'] ?? $banner['teks_tombol'];
$form_tujuan = $_POST['tujuan_tombol'] ?? $tujuan_tombol_lama;
$form_custom_url = $_POST['custom_url'] ?? $custom_url_lama;

$form_urutan = (int)(
    $_POST['urutan'] ?? $banner['urutan']
);

$form_status = $_POST['status'] ?? $banner['status'];

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Edit Banner - Toko ATK & Alat Jahit</title>

    <link
        rel="stylesheet"
        href="../assets/css/admin.css"
    >

    <style>

        .banner-form-page {
            max-width: 900px;
        }

        .banner-form-card {
            background: #fff;
            border: 1px solid #e0e7ed;
            border-radius: 14px;
            box-shadow: 0 4px 15px rgba(16,47,75,.04);
            padding: 28px;
        }

        .form-title {
            margin-bottom: 25px;
        }

        .form-title h1 {
            margin: 0 0 7px;
            color: #102f4b;
            font-size: 28px;
        }

        .form-title p {
            margin: 0;
            color: #7890a4;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #334f66;
            font-size: 13px;
            font-weight: 700;
        }

        .form-control {
            width: 100%;
            box-sizing: border-box;
            padding: 11px 13px;
            border: 1px solid #d6e0e8;
            border-radius: 8px;
            background: #fff;
            color: #334f66;
            font-family: inherit;
            font-size: 14px;
            outline: none;
        }

        .form-control:focus {
            border-color: #102f4b;
        }

        textarea.form-control {
            min-height: 110px;
            resize: vertical;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }

        .form-help {
            display: block;
            margin-top: 6px;
            color: #9aafbf;
            font-size: 12px;
        }

        .form-error {
            padding: 13px 16px;
            margin-bottom: 20px;
            border-radius: 9px;
            background: #fce8e8;
            color: #a33;
            border: 1px solid #f2caca;
            font-size: 13px;
            font-weight: 600;
        }

        .gambar-sekarang {
            display: block;
            width: 100%;
            max-width: 500px;
            max-height: 240px;
            object-fit: cover;
            margin-bottom: 12px;
            border-radius: 10px;
            border: 1px solid #dce5ec;
        }

        .gambar-preview {
            display: none;
            width: 100%;
            max-width: 500px;
            max-height: 240px;
            object-fit: cover;
            margin-top: 12px;
            border-radius: 10px;
            border: 1px solid #dce5ec;
        }

        .custom-url-group {
            display: none;
        }

        .custom-url-group.show {
            display: block;
        }

        .btn-area {
            display: flex;
            gap: 10px;
            margin-top: 25px;
        }

        .btn-simpan {
            border: none;
            padding: 11px 18px;
            border-radius: 8px;
            background: #102f4b;
            color: #fff;
            font-family: inherit;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
        }

        .btn-simpan:hover {
            background: #1c496b;
        }

        .btn-batal {
            display: inline-flex;
            align-items: center;
            padding: 10px 17px;
            border-radius: 8px;
            background: #eef3f7;
            border: 1px solid #dce5ec;
            color: #334f66;
            text-decoration: none;
            font-size: 13px;
            font-weight: 700;
        }

        @media (max-width: 700px) {

            .form-row {
                grid-template-columns: 1fr;
            }

            .banner-form-card {
                padding: 20px;
            }

            .btn-area {
                flex-direction: column;
            }

            .btn-simpan,
            .btn-batal {
                justify-content: center;
                text-align: center;
            }
        }

    </style>

</head>

<body>

<div class="admin-layout">

    <?php require_once "../includes/sidebar.php"; ?>

    <main class="admin-main">

        <?php require_once "../includes/navbar.php"; ?>

        <section class="admin-content">

            <div class="banner-form-page">

                <div class="form-title">

                    <h1>Edit Banner</h1>

                    <p>
                        Perbarui informasi banner homepage.
                    </p>

                </div>

                <?php if ($error !== '') : ?>

                    <div class="form-error">
                        ⚠ <?= htmlspecialchars($error); ?>
                    </div>

                <?php endif; ?>

                <div class="banner-form-card">

                    <form
                        method="POST"
                        enctype="multipart/form-data"
                    >

                        <!-- JUDUL -->

                        <div class="form-group">

                            <label>Judul Banner *</label>

                            <input
                                type="text"
                                name="judul"
                                class="form-control"
                                maxlength="150"
                                required
                                value="<?= htmlspecialchars($form_judul); ?>"
                            >

                        </div>

                        <!-- DESKRIPSI -->

                        <div class="form-group">

                            <label>Deskripsi</label>

                            <textarea
                                name="deskripsi"
                                class="form-control"
                            ><?= htmlspecialchars($form_deskripsi); ?></textarea>

                        </div>

                        <!-- GAMBAR -->

                        <div class="form-group">

                            <label>Gambar Saat Ini</label>

                            <img
                                src="../assets/images/banners/<?= htmlspecialchars($banner['gambar']); ?>"
                                alt="Banner"
                                class="gambar-sekarang"
                            >

                            <label>Ganti Gambar</label>

                            <input
                                type="file"
                                name="gambar"
                                class="form-control"
                                accept=".jpg,.jpeg,.png,.webp"
                                onchange="previewBanner(this)"
                            >

                            <span class="form-help">
                                Kosongkan jika tidak ingin mengganti gambar.
                            </span>

                            <img
                                id="gambarPreview"
                                class="gambar-preview"
                                alt="Preview gambar baru"
                            >

                        </div>

                        <!-- TEKS DAN TUJUAN TOMBOL -->

                        <div class="form-row">

                            <div class="form-group">

                                <label>Teks Tombol</label>

                                <input
                                    type="text"
                                    name="teks_tombol"
                                    class="form-control"
                                    maxlength="50"
                                    value="<?= htmlspecialchars($form_teks_tombol); ?>"
                                    placeholder="Contoh: Belanja Sekarang"
                                >

                            </div>

                            <div class="form-group">

                                <label>Tujuan Tombol</label>

                                <select
                                    name="tujuan_tombol"
                                    id="tujuanTombol"
                                    class="form-control"
                                >

                                    <?php foreach ($daftar_tujuan as $kode => $data) : ?>

                                        <option
                                            value="<?= htmlspecialchars($kode); ?>"
                                            <?= $form_tujuan === $kode ? 'selected' : ''; ?>
                                        >
                                            <?= htmlspecialchars($data['nama']); ?>
                                        </option>

                                    <?php endforeach; ?>

                                </select>

                                <span class="form-help">
                                    Pilih halaman pelanggan yang akan dibuka saat tombol diklik.
                                </span>

                            </div>

                        </div>

                        <!-- CUSTOM URL -->

                        <div
                            class="form-group custom-url-group"
                            id="customUrlGroup"
                        >

                            <label>Custom URL</label>

                            <input
                                type="text"
                                name="custom_url"
                                class="form-control"
                                maxlength="255"
                                value="<?= htmlspecialchars($form_custom_url); ?>"
                                placeholder="Contoh: https://contoh.com"
                            >

                            <span class="form-help">
                                Isi URL sendiri jika memilih Custom URL.
                            </span>

                        </div>

                        <!-- URUTAN DAN STATUS -->

                        <div class="form-row">

                            <div class="form-group">

                                <label>Urutan</label>

                                <input
                                    type="number"
                                    name="urutan"
                                    class="form-control"
                                    min="0"
                                    value="<?= $form_urutan; ?>"
                                >

                                <span class="form-help">
                                    Angka lebih kecil tampil lebih dahulu.
                                </span>

                            </div>

                            <div class="form-group">

                                <label>Status</label>

                                <select
                                    name="status"
                                    class="form-control"
                                >

                                    <option
                                        value="Aktif"
                                        <?= $form_status === 'Aktif' ? 'selected' : ''; ?>
                                    >
                                        Aktif
                                    </option>

                                    <option
                                        value="Tidak Aktif"
                                        <?= $form_status === 'Tidak Aktif' ? 'selected' : ''; ?>
                                    >
                                        Tidak Aktif
                                    </option>

                                </select>

                            </div>

                        </div>

                        <!-- TOMBOL -->

                        <div class="btn-area">

                            <button
                                type="submit"
                                class="btn-simpan"
                            >
                                Simpan Perubahan
                            </button>

                            <a
                                href="index.php"
                                class="btn-batal"
                            >
                                Batal
                            </a>

                        </div>

                    </form>

                </div>

            </div>

        </section>

        <?php require_once "../includes/footer.php"; ?>

    </main>

</div>

<script src="../assets/js/admin.js"></script>

<script>

/* =========================================================
   PREVIEW GAMBAR
========================================================= */

function previewBanner(input) {

    const preview = document.getElementById("gambarPreview");

    if (input.files && input.files[0]) {

        const reader = new FileReader();

        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = "block";
        };

        reader.readAsDataURL(input.files[0]);

    } else {

        preview.style.display = "none";

    }
}

/* =========================================================
   TUJUAN TOMBOL
========================================================= */

const tujuanTombol = document.getElementById("tujuanTombol");
const customUrlGroup = document.getElementById("customUrlGroup");

function cekTujuanTombol() {

    if (tujuanTombol.value === "custom") {
        customUrlGroup.classList.add("show");
    } else {
        customUrlGroup.classList.remove("show");
    }

}

tujuanTombol.addEventListener("change", cekTujuanTombol);
cekTujuanTombol();

</script>

</body>
</html>