<?php
session_start();

if (!isset($_SESSION['id_admin'])) {
    header("Location: ../login.php");
    exit;
}

require_once "../../config/koneksi.php";
require_once "../includes/hak_akses.php";
require_once __DIR__ . "/../includes/log_aktivitas.php";

cekAkses('lokasi');

$error = "";
$kode_rak = "";
$nama_lokasi = "";
$keterangan = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode_rak = trim($_POST['kode_rak'] ?? '');
    $nama_lokasi = trim($_POST['nama_lokasi'] ?? '');
    $keterangan = trim($_POST['keterangan'] ?? '');

    if ($kode_rak === '' || $nama_lokasi === '') {
        $error = "Kode rak dan nama lokasi wajib diisi.";
    } elseif (mb_strlen($kode_rak) > 50) {
        $error = "Kode rak maksimal 50 karakter.";
    } elseif (mb_strlen($nama_lokasi) > 100) {
        $error = "Nama lokasi maksimal 100 karakter.";
    } else {
        // Periksa apakah kode rak sudah digunakan.
        $stmt = mysqli_prepare(
            $conn,
            "SELECT id_lokasi
             FROM lokasi_rak
             WHERE kode_rak = ?
             LIMIT 1"
        );

        if (!$stmt) {
            $error = "Terjadi kesalahan saat memeriksa kode rak.";
        } else {
            mysqli_stmt_bind_param($stmt, "s", $kode_rak);

            if (!mysqli_stmt_execute($stmt)) {
                $error = "Gagal memeriksa kode rak.";
                mysqli_stmt_close($stmt);
            } else {
                $result = mysqli_stmt_get_result($stmt);
                $cek = mysqli_fetch_assoc($result);
                mysqli_stmt_close($stmt);

                if ($cek) {
                    $error = "Kode rak tersebut sudah digunakan.";
                } else {
                    // Simpan lokasi rak baru.
                    $stmt = mysqli_prepare(
                        $conn,
                        "INSERT INTO lokasi_rak
                            (kode_rak, nama_lokasi, keterangan)
                         VALUES (?, ?, ?)"
                    );

                    if (!$stmt) {
                        $error = "Gagal menyiapkan penyimpanan lokasi rak.";
                    } else {
                        mysqli_stmt_bind_param(
                            $stmt,
                            "sss",
                            $kode_rak,
                            $nama_lokasi,
                            $keterangan
                        );

                        if (mysqli_stmt_execute($stmt)) {
                            $idLokasiBaru = (int) mysqli_insert_id($conn);

                            mysqli_stmt_close($stmt);

                            /* =====================================
                               CATAT LOG TAMBAH LOKASI / RAK
                            ===================================== */

                            $detailLog = 'Menambahkan lokasi/rak "' .
                                $nama_lokasi .
                                '" dengan kode rak ' . $kode_rak .
                                ' (ID lokasi: ' . $idLokasiBaru . ').';

                            try {
                                $logBerhasil = catatLogAktivitas(
                                    $conn,
                                    'lokasi',
                                    'Tambah Lokasi/Rak',
                                    $detailLog
                                );

                                if (!$logBerhasil) {
                                    error_log(
                                        'Gagal mencatat log tambah lokasi/rak: ID ' .
                                        $idLokasiBaru
                                    );
                                }
                            } catch (Throwable $e) {
                                error_log(
                                    'Kesalahan saat mencatat log tambah lokasi/rak ID ' .
                                    $idLokasiBaru . ': ' . $e->getMessage()
                                );
                            }

                            header("Location: index.php?status=berhasil");
                            exit;
                        }

                        $stmt_error = mysqli_stmt_errno($stmt);
                        mysqli_stmt_close($stmt);

                        if ($stmt_error === 1062) {
                            $error = "Kode rak tersebut sudah digunakan.";
                        } else {
                            $error = "Gagal menambahkan lokasi atau rak.";
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Rak - Toko ATK &amp; Alat Jahit</title>

    <link rel="stylesheet" href="../assets/css/admin.css">

    <style>
        .lokasi-page-header {
            margin-bottom: 28px;
        }

        .lokasi-page-header h1 {
            margin: 0 0 7px;
            color: #102f4b;
            font-size: 28px;
            font-weight: 700;
        }

        .lokasi-page-header p {
            margin: 0;
            color: #7890a4;
            font-size: 14px;
        }

        .lokasi-alert {
            padding: 13px 16px;
            border-radius: 9px;
            margin-bottom: 22px;
            font-size: 13px;
        }

        .lokasi-alert.error {
            background: #fff0f0;
            border: 1px solid #f0c2c2;
            color: #b52b2b;
        }

        .lokasi-form-card {
            max-width: 750px;
            background: #fff;
            border: 1px solid #e0e7ed;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(16, 47, 75, 0.04);
        }

        .lokasi-form-header {
            padding: 20px 24px;
            border-bottom: 1px solid #e6edf2;
        }

        .lokasi-form-header h2 {
            margin: 0 0 5px;
            color: #102f4b;
            font-size: 18px;
            font-weight: 600;
        }

        .lokasi-form-header p {
            margin: 0;
            color: #7890a4;
            font-size: 13px;
        }

        .lokasi-form-body {
            padding: 25px;
        }

        .lokasi-form-group {
            margin-bottom: 20px;
        }

        .lokasi-form-group label {
            display: block;
            margin-bottom: 8px;
            color: #29485f;
            font-size: 13px;
            font-weight: 600;
        }

        .lokasi-form-group input,
        .lokasi-form-group textarea {
            display: block;
            width: 100%;
            box-sizing: border-box;
            padding: 12px 14px;
            border: 1px solid #d9e2ea;
            border-radius: 8px;
            background: #fff;
            color: #29485f;
            font-family: inherit;
            font-size: 13px;
            outline: none;
            transition: border 0.2s ease, box-shadow 0.2s ease;
        }

        .lokasi-form-group textarea {
            min-height: 110px;
            resize: vertical;
        }

        .lokasi-form-group input:focus,
        .lokasi-form-group textarea:focus {
            border-color: #245b83;
            box-shadow: 0 0 0 3px rgba(36, 91, 131, 0.08);
        }

        .lokasi-form-help {
            display: block;
            margin-top: 6px;
            color: #8a9dac;
            font-size: 12px;
            line-height: 1.5;
        }

        .lokasi-form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            padding-top: 20px;
            border-top: 1px solid #e6edf2;
        }

        .btn-batal-lokasi,
        .btn-simpan-lokasi {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 40px;
            box-sizing: border-box;
            padding: 0 18px;
            border: none;
            border-radius: 8px;
            font-family: inherit;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: 0.2s;
        }

        .btn-batal-lokasi {
            background: #eef2f6;
            color: #506d84;
        }

        .btn-batal-lokasi:hover {
            background: #e2e9ef;
        }

        .btn-simpan-lokasi {
            background: #102f4b;
            color: #fff;
        }

        .btn-simpan-lokasi:hover {
            background: #245b83;
        }

        @media (max-width: 600px) {
            .lokasi-form-body {
                padding: 20px;
            }

            .lokasi-form-actions {
                flex-direction: column;
            }

            .btn-batal-lokasi,
            .btn-simpan-lokasi {
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

            <div class="lokasi-page-header">
                <h1>Tambah Rak</h1>
                <p>Tambahkan lokasi rak baru untuk penyimpanan produk.</p>
            </div>

            <?php if ($error !== ""): ?>
                <div class="lokasi-alert error" role="alert">
                    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <div class="lokasi-form-card">

                <div class="lokasi-form-header">
                    <h2>Informasi Rak</h2>
                    <p>Isi data lokasi rak dengan lengkap.</p>
                </div>

                <div class="lokasi-form-body">
                    <form method="POST" autocomplete="off">

                        <div class="lokasi-form-group">
                            <label for="kode_rak">Kode Rak *</label>

                            <input
                                type="text"
                                id="kode_rak"
                                name="kode_rak"
                                value="<?= htmlspecialchars($kode_rak, ENT_QUOTES, 'UTF-8'); ?>"
                                placeholder="Contoh: RAK-A01"
                                maxlength="50"
                                required
                                autofocus
                            >

                            <span class="lokasi-form-help">
                                Kode rak harus unik dan digunakan sebagai identitas lokasi produk.
                            </span>
                        </div>

                        <div class="lokasi-form-group">
                            <label for="nama_lokasi">Nama Lokasi *</label>

                            <input
                                type="text"
                                id="nama_lokasi"
                                name="nama_lokasi"
                                value="<?= htmlspecialchars($nama_lokasi, ENT_QUOTES, 'UTF-8'); ?>"
                                placeholder="Contoh: Rak ATK Umum"
                                maxlength="100"
                                required
                            >

                            <span class="lokasi-form-help">
                                Nama lokasi membantu admin mengenali tempat penyimpanan.
                            </span>
                        </div>

                        <div class="lokasi-form-group">
                            <label for="keterangan">Keterangan</label>

                            <textarea
                                id="keterangan"
                                name="keterangan"
                                placeholder="Contoh: Rak bagian depan toko."
                            ><?= htmlspecialchars($keterangan, ENT_QUOTES, 'UTF-8'); ?></textarea>

                            <span class="lokasi-form-help">
                                Keterangan bersifat opsional.
                            </span>
                        </div>

                        <div class="lokasi-form-actions">
                            <a href="index.php" class="btn-batal-lokasi">
                                Batal
                            </a>

                            <button type="submit" class="btn-simpan-lokasi">
                                Simpan Rak
                            </button>
                        </div>

                    </form>
                </div>

            </div>

        </main>

        <?php include "../includes/footer.php"; ?>

    </div>
</div>

<script src="../assets/js/admin.js"></script>
</body>
</html>