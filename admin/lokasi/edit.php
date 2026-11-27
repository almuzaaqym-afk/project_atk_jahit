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

$id_lokasi = (int) ($_GET['id'] ?? 0);

if ($id_lokasi <= 0) {
    header("Location: index.php?status=tidak_ditemukan");
    exit;
}

/* =====================================================
   AMBIL DATA LOKASI
===================================================== */

$stmt = mysqli_prepare(
    $conn,
    "SELECT id_lokasi, kode_rak, nama_lokasi, keterangan
     FROM lokasi_rak
     WHERE id_lokasi = ?
     LIMIT 1"
);

if (!$stmt) {
    header("Location: index.php?status=gagal");
    exit;
}

mysqli_stmt_bind_param($stmt, "i", $id_lokasi);

if (!mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);
    header("Location: index.php?status=gagal");
    exit;
}

$result = mysqli_stmt_get_result($stmt);
$lokasi = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$lokasi) {
    header("Location: index.php?status=tidak_ditemukan");
    exit;
}

$error = "";

/* =====================================================
   NILAI AWAL FORM
===================================================== */

$kode_rak = $lokasi['kode_rak'];
$nama_lokasi = $lokasi['nama_lokasi'];
$keterangan = $lokasi['keterangan'] ?? '';

/* =====================================================
   PROSES UPDATE
===================================================== */

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
        /* =============================================
           CEK KODE RAK DUPLIKAT
        ============================================= */

        $stmt = mysqli_prepare(
            $conn,
            "SELECT id_lokasi
             FROM lokasi_rak
             WHERE kode_rak = ?
               AND id_lokasi != ?
             LIMIT 1"
        );

        if (!$stmt) {
            $error = "Gagal memeriksa kode rak.";
        } else {
            mysqli_stmt_bind_param(
                $stmt,
                "si",
                $kode_rak,
                $id_lokasi
            );

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
                    /*
                     * Gunakan kode lama dari database,
                     * bukan dari input formulir.
                     */
                    $kode_rak_lama = $lokasi['kode_rak'];
                    $nama_lokasi_lama = $lokasi['nama_lokasi'];
                    $berhasil = true;
                    $kode_error = 0;

                    if (!mysqli_begin_transaction($conn)) {
                        $error = "Gagal memulai transaksi database.";
                        $berhasil = false;
                    }

                    /*
                     * Jika kode berubah, perbarui referensi
                     * di produk dan varian dalam transaksi.
                     */
                    if ($berhasil && $kode_rak_lama !== $kode_rak) {
                        $stmt_produk = mysqli_prepare(
                            $conn,
                            "UPDATE produk
                             SET lokasi_rak = ?
                             WHERE lokasi_rak = ?"
                        );

                        if (!$stmt_produk) {
                            $berhasil = false;
                            $kode_error = mysqli_errno($conn);
                        } else {
                            mysqli_stmt_bind_param(
                                $stmt_produk,
                                "ss",
                                $kode_rak,
                                $kode_rak_lama
                            );

                            if (!mysqli_stmt_execute($stmt_produk)) {
                                $berhasil = false;
                                $kode_error = mysqli_stmt_errno(
                                    $stmt_produk
                                );
                            }

                            mysqli_stmt_close($stmt_produk);
                        }

                        if ($berhasil) {
                            $stmt_varian = mysqli_prepare(
                                $conn,
                                "UPDATE produk_varian
                                 SET lokasi_rak = ?
                                 WHERE lokasi_rak = ?"
                            );

                            if (!$stmt_varian) {
                                $berhasil = false;
                                $kode_error = mysqli_errno($conn);
                            } else {
                                mysqli_stmt_bind_param(
                                    $stmt_varian,
                                    "ss",
                                    $kode_rak,
                                    $kode_rak_lama
                                );

                                if (!mysqli_stmt_execute($stmt_varian)) {
                                    $berhasil = false;
                                    $kode_error = mysqli_stmt_errno(
                                        $stmt_varian
                                    );
                                }

                                mysqli_stmt_close($stmt_varian);
                            }
                        }
                    }

                    /*
                     * Perbarui data lokasi hanya jika
                     * pembaruan referensi sebelumnya berhasil.
                     */
                    if ($berhasil) {
                        $stmt_update = mysqli_prepare(
                            $conn,
                            "UPDATE lokasi_rak
                             SET kode_rak = ?,
                                 nama_lokasi = ?,
                                 keterangan = ?
                             WHERE id_lokasi = ?"
                        );

                        if (!$stmt_update) {
                            $berhasil = false;
                            $kode_error = mysqli_errno($conn);
                        } else {
                            mysqli_stmt_bind_param(
                                $stmt_update,
                                "sssi",
                                $kode_rak,
                                $nama_lokasi,
                                $keterangan,
                                $id_lokasi
                            );

                            if (!mysqli_stmt_execute($stmt_update)) {
                                $berhasil = false;
                                $kode_error = mysqli_stmt_errno(
                                    $stmt_update
                                );
                            }

                            mysqli_stmt_close($stmt_update);
                        }
                    }

                    if ($berhasil) {
                        if (mysqli_commit($conn)) {
                            /* =================================
                               CATAT LOG EDIT LOKASI / RAK
                            ================================= */

                            $detailLog = 'Memperbarui lokasi/rak "' .
                                $nama_lokasi_lama .
                                '" menjadi "' . $nama_lokasi .
                                '" (ID lokasi: ' . $id_lokasi .
                                ', kode rak: ' . $kode_rak_lama .
                                ' menjadi ' . $kode_rak . ').';

                            try {
                                $logBerhasil = catatLogAktivitas(
                                    $conn,
                                    'lokasi',
                                    'Edit Lokasi/Rak',
                                    $detailLog
                                );

                                if (!$logBerhasil) {
                                    error_log(
                                        'Gagal mencatat log edit lokasi/rak: ID ' .
                                        $id_lokasi
                                    );
                                }
                            } catch (Throwable $e) {
                                error_log(
                                    'Kesalahan saat mencatat log edit lokasi/rak ID ' .
                                    $id_lokasi . ': ' . $e->getMessage()
                                );
                            }

                            header("Location: index.php?status=edit");
                            exit;
                        }

                        mysqli_rollback($conn);
                        $error = "Gagal menyimpan perubahan lokasi.";
                    } else {
                        mysqli_rollback($conn);

                        if ($kode_error === 1062) {
                            $error = "Kode rak tersebut sudah digunakan.";
                        } elseif ($error === '') {
                            $error = "Data lokasi gagal diperbarui. Periksa kembali struktur database dan relasi lokasi rak.";
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

    <title>Edit Lokasi / Rak - Admin</title>

    <link rel="stylesheet" href="../assets/css/admin.css">

    <style>
        .lokasi-edit {
            max-width: 900px;
        }

        .lokasi-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 25px;
            gap: 20px;
        }

        .lokasi-header h1 {
            font-size: 28px;
            color: #102f4b;
            margin: 0 0 6px;
        }

        .lokasi-header p {
            color: #7890a4;
            font-size: 14px;
            margin: 0;
        }

        .lokasi-alert {
            background: #fde7e7;
            color: #b23b3b;
            border: 1px solid #f4caca;
            padding: 13px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 13px;
        }

        .lokasi-card {
            background: #fff;
            border: 1px solid #e0e7ed;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(16, 47, 75, 0.04);
        }

        .lokasi-card-header {
            padding: 20px 22px;
            border-bottom: 1px solid #e6edf2;
        }

        .lokasi-card-header h2 {
            font-size: 18px;
            color: #102f4b;
            margin: 0 0 5px;
        }

        .lokasi-card-header p {
            color: #7890a4;
            font-size: 13px;
            margin: 0;
        }

        .lokasi-card-body {
            padding: 25px 22px;
        }

        .lokasi-field {
            margin-bottom: 20px;
        }

        .lokasi-field label {
            display: block;
            color: #102f4b;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 7px;
        }

        .lokasi-input,
        .lokasi-textarea {
            display: block;
            width: 100%;
            box-sizing: border-box;
            padding: 11px 13px;
            border: 1px solid #d9e2e8;
            border-radius: 8px;
            background: #fff;
            color: #334f66;
            font-family: inherit;
            font-size: 14px;
            outline: none;
        }

        .lokasi-input:focus,
        .lokasi-textarea:focus {
            border-color: #245b83;
            box-shadow: 0 0 0 3px rgba(36, 91, 131, 0.08);
        }

        .lokasi-textarea {
            min-height: 120px;
            resize: vertical;
        }

        .lokasi-help {
            display: block;
            color: #9aafbf;
            font-size: 11px;
            margin-top: 5px;
        }

        .lokasi-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            padding-top: 20px;
            margin-top: 5px;
            border-top: 1px solid #edf1f4;
        }

        .lokasi-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-sizing: border-box;
            border: none;
            border-radius: 8px;
            padding: 10px 17px;
            font-family: inherit;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
        }

        .lokasi-btn-cancel {
            background: #edf1f4;
            color: #506d84;
        }

        .lokasi-btn-cancel:hover {
            background: #e2e8ec;
        }

        .lokasi-btn-save {
            background: #102f4b;
            color: #fff;
        }

        .lokasi-btn-save:hover {
            background: #1c496b;
        }

        @media (max-width: 700px) {
            .lokasi-header {
                flex-direction: column;
            }

            .lokasi-card-body {
                padding: 20px;
            }

            .lokasi-actions {
                flex-direction: column;
            }

            .lokasi-btn {
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
            <div class="lokasi-edit">

                <div class="lokasi-header">
                    <div>
                        <h1>Edit Lokasi / Rak</h1>
                        <p>Perbarui informasi lokasi atau rak produk.</p>
                    </div>
                </div>

                <?php if ($error !== ''): ?>
                    <div class="lokasi-alert" role="alert">
                        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                <?php endif; ?>

                <div class="lokasi-card">

                    <div class="lokasi-card-header">
                        <h2>Informasi Lokasi</h2>
                        <p>Silakan ubah data lokasi sesuai kebutuhan.</p>
                    </div>

                    <div class="lokasi-card-body">
                        <form method="POST" autocomplete="off">

                            <div class="lokasi-field">
                                <label for="kode_rak">Kode Rak *</label>

                                <input
                                    type="text"
                                    id="kode_rak"
                                    name="kode_rak"
                                    class="lokasi-input"
                                    value="<?= htmlspecialchars($kode_rak, ENT_QUOTES, 'UTF-8'); ?>"
                                    maxlength="50"
                                    placeholder="Contoh: BR004"
                                    required
                                >

                                <span class="lokasi-help">
                                    Jika kode berubah, referensi rak produk dan varian akan ikut diperbarui.
                                </span>
                            </div>

                            <div class="lokasi-field">
                                <label for="nama_lokasi">Nama Lokasi *</label>

                                <input
                                    type="text"
                                    id="nama_lokasi"
                                    name="nama_lokasi"
                                    class="lokasi-input"
                                    value="<?= htmlspecialchars($nama_lokasi, ENT_QUOTES, 'UTF-8'); ?>"
                                    maxlength="100"
                                    placeholder="Contoh: Etalase Bagian Depan"
                                    required
                                >
                            </div>

                            <div class="lokasi-field">
                                <label for="keterangan">Keterangan</label>

                                <textarea
                                    id="keterangan"
                                    name="keterangan"
                                    class="lokasi-textarea"
                                    placeholder="Tambahkan keterangan lokasi..."
                                ><?= htmlspecialchars($keterangan, ENT_QUOTES, 'UTF-8'); ?></textarea>
                            </div>

                            <div class="lokasi-actions">
                                <a
                                    href="index.php"
                                    class="lokasi-btn lokasi-btn-cancel"
                                >
                                    Batal
                                </a>

                                <button
                                    type="submit"
                                    class="lokasi-btn lokasi-btn-save"
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