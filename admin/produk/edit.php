<?php

session_start();

if (!isset($_SESSION['id_admin'])) {
    header("Location: ../login.php");
    exit;
}

require_once "../../config/koneksi.php";
require_once __DIR__ . "/../includes/log_aktivitas.php";

$id_produk = (int)($_GET['id'] ?? $_POST['id_produk'] ?? 0);

if ($id_produk <= 0) {
    header("Location: index.php");
    exit;
}

$error = "";

/* =========================================================
   FUNGSI UPLOAD GAMBAR VARIAN
========================================================= */

function uploadGambarVarian(
    $files,
    $id_varian,
    $conn,
    &$error,
    &$uploaded_variant_files
) {
    if (
        !isset($files['name']) ||
        !is_array($files['name'])
    ) {
        return;
    }

    $folder =
        __DIR__ .
        "/../assets/images/products/variants/";

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
        $uploaded_variant_files[] = $path;
    }
}

/* =========================================================
   AMBIL DATA PRODUK
========================================================= */

$stmt = mysqli_prepare(
    $conn,
    "SELECT *
     FROM produk
     WHERE id_produk = ?
     LIMIT 1"
);

mysqli_stmt_bind_param($stmt, "i", $id_produk);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$produk = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$produk) {
    header("Location: index.php");
    exit;
}

/* =========================================================
   CEK VARIAN PRODUK AWAL
========================================================= */

$stmt = mysqli_prepare(
    $conn,
    "SELECT COUNT(*) AS jumlah
     FROM produk_varian
     WHERE id_produk = ?"
);

mysqli_stmt_bind_param($stmt, "i", $id_produk);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$data_jumlah_varian = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

$jumlah_varian = (int)($data_jumlah_varian['jumlah'] ?? 0);
$memiliki_varian_awal = $jumlah_varian > 0;

/* =========================================================
   DATA VARIAN AWAL
========================================================= */

$varian_awal = [];

$stmt = mysqli_prepare(
    $conn,
    "SELECT
        v.id_varian,
        v.nama_varian,
        v.harga,
        v.stok,
        v.stok_minimum,
        v.lokasi_rak,
        v.status
     FROM produk_varian v
     WHERE v.id_produk = ?
     ORDER BY v.id_varian ASC"
);

mysqli_stmt_bind_param($stmt, "i", $id_produk);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

while ($row = mysqli_fetch_assoc($result)) {
    $row['gambar'] = [];

    $stmt_gambar = mysqli_prepare(
        $conn,
        "SELECT
            id_gambar_varian,
            nama_gambar
         FROM produk_varian_gambar
         WHERE id_varian = ?
         ORDER BY urutan ASC, id_gambar_varian ASC"
    );

    mysqli_stmt_bind_param($stmt_gambar, "i", $row['id_varian']);
    mysqli_stmt_execute($stmt_gambar);

    $result_gambar = mysqli_stmt_get_result($stmt_gambar);

    while ($gambar = mysqli_fetch_assoc($result_gambar)) {
        $row['gambar'][] = $gambar;
    }

    mysqli_stmt_close($stmt_gambar);
    $varian_awal[] = $row;
}

mysqli_stmt_close($stmt);

/* =========================================================
   PROSES UPDATE
========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_produk = trim($_POST['nama_produk'] ?? '');
    $id_kategori = (int)($_POST['id_kategori'] ?? 0);
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $memiliki_varian = ($_POST['memiliki_varian'] ?? 'tidak') === 'ya';
    $harga = (float)($_POST['harga'] ?? 0);

    $gambar_lama = $produk['gambar'];
    $gambar_baru = $gambar_lama;

    $varian_data = [];
    $varian_id_dipertahankan = [];
    $uploaded_variant_files = [];
    $hapus_gambar_varian_setelah_commit = [];

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

    /* VALIDASI PERUBAHAN TIPE PRODUK */
    if ($error === '') {
        if (!$memiliki_varian && $memiliki_varian_awal) {
            $stmt = mysqli_prepare(
                $conn,
                "SELECT COALESCE(SUM(stok), 0) AS total_stok
                 FROM produk_varian
                 WHERE id_produk = ?"
            );

            mysqli_stmt_bind_param($stmt, "i", $id_produk);
            mysqli_stmt_execute($stmt);

            $result = mysqli_stmt_get_result($stmt);
            $data_stok_varian = mysqli_fetch_assoc($result);

            mysqli_stmt_close($stmt);

            if ((int)($data_stok_varian['total_stok'] ?? 0) > 0) {
                $error =
                    "Produk tidak bisa diubah menjadi tanpa varian karena masih memiliki stok varian. " .
                    "Kosongkan stok varian terlebih dahulu melalui menu Gudang / Stok.";
            }
        }

        if (
            $error === '' &&
            $memiliki_varian &&
            !$memiliki_varian_awal &&
            (int)$produk['stok'] > 0
        ) {
            $error =
                "Produk tidak bisa diubah menjadi produk varian karena masih memiliki stok utama. " .
                "Kosongkan stok utama terlebih dahulu melalui menu Gudang / Stok.";
        }
    }

    /* VALIDASI VARIAN */
    if ($error === '' && $memiliki_varian) {
        $nama_varian_list = $_POST['nama_varian'] ?? [];
        $harga_varian_list = $_POST['harga_varian'] ?? [];
        $id_varian_list = $_POST['id_varian'] ?? [];

        if (
            !is_array($nama_varian_list) ||
            count($nama_varian_list) === 0
        ) {
            $error = "Minimal harus ada satu varian.";
        }

        $nama_varian_dipakai = [];

        if ($error === '') {
            foreach ($nama_varian_list as $index => $nama_varian) {
                $nama_varian = trim($nama_varian);
                $harga_varian = (float)($harga_varian_list[$index] ?? 0);
                $id_varian = (int)($id_varian_list[$index] ?? 0);

                if ($nama_varian === '') {
                    $error = "Nama semua varian wajib diisi.";
                    break;
                }

                if ($harga_varian < 0) {
                    $error = "Harga varian tidak boleh negatif.";
                    break;
                }

                $nama_key = strtolower($nama_varian);

                if (isset($nama_varian_dipakai[$nama_key])) {
                    $error = "Nama varian tidak boleh sama.";
                    break;
                }

                $nama_varian_dipakai[$nama_key] = true;

                $varian_data[] = [
                    'id_varian' => $id_varian,
                    'nama' => $nama_varian,
                    'harga' => $harga_varian,
                    'index' => $index
                ];

                if ($id_varian > 0) {
                    $varian_id_dipertahankan[] = $id_varian;
                }
            }
        }
    }

    /* CEK VARIAN LAMA YANG AKAN DIHAPUS */
    if ($error === '' && $memiliki_varian) {
        foreach ($varian_awal as $varian_lama) {
            $id_lama = (int)$varian_lama['id_varian'];

            if (
                !in_array(
                    $id_lama,
                    $varian_id_dipertahankan,
                    true
                )
            ) {
                if ((int)$varian_lama['stok'] > 0) {
                    $error =
                        "Varian \"" .
                        $varian_lama['nama_varian'] .
                        "\" tidak bisa dihapus karena masih memiliki stok " .
                        (int)$varian_lama['stok'] .
                        ". Kosongkan stok terlebih dahulu melalui Gudang / Stok.";

                    break;
                }
            }
        }
    }

    /* UPLOAD GAMBAR PRODUK */
    if (
        $error === '' &&
        isset($_FILES['gambar']) &&
        $_FILES['gambar']['error'] !== UPLOAD_ERR_NO_FILE
    ) {
        if ($_FILES['gambar']['error'] !== UPLOAD_ERR_OK) {
            $error = "Gagal mengupload gambar.";
        } else {
            $file = $_FILES['gambar'];

            if ($file['size'] > 5 * 1024 * 1024) {
                $error = "Ukuran gambar maksimal 5 MB.";
            } else {
                $allowed = [
                    'jpg' => 'image/jpeg',
                    'jpeg' => 'image/jpeg',
                    'png' => 'image/png',
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
                            $gambar_baru = uniqid('produk_', true) . '.' . $extension;
                            $path_gambar_baru = $folder . $gambar_baru;

                            if (!move_uploaded_file($file['tmp_name'], $path_gambar_baru)) {
                                $error = "Gambar gagal disimpan.";
                                $gambar_baru = $gambar_lama;
                            }
                        }
                    }
                }
            }
        }
    }

    /* =====================================================
       SIMPAN UPDATE
    ===================================================== */
    if ($error === '') {
        mysqli_begin_transaction($conn);

        try {
            $harga_produk = $memiliki_varian ? 0 : $harga;

            if ($memiliki_varian) {
                $stmt = mysqli_prepare(
                    $conn,
                    "UPDATE produk
                     SET
                        id_kategori = ?,
                        nama_produk = ?,
                        deskripsi = ?,
                        harga = ?,
                        stok_minimum = 0,
                        lokasi_rak = NULL,
                        gambar = ?
                     WHERE id_produk = ?"
                );
            } else {
                $stmt = mysqli_prepare(
                    $conn,
                    "UPDATE produk
                     SET
                        id_kategori = ?,
                        nama_produk = ?,
                        deskripsi = ?,
                        harga = ?,
                        gambar = ?
                     WHERE id_produk = ?"
                );
            }

            if (!$stmt) {
                throw new Exception("Produk gagal disiapkan.");
            }

            mysqli_stmt_bind_param(
                $stmt,
                "issdsi",
                $id_kategori,
                $nama_produk,
                $deskripsi,
                $harga_produk,
                $gambar_baru,
                $id_produk
            );

            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception(
                    "Produk gagal diperbarui: " . mysqli_stmt_error($stmt)
                );
            }

            mysqli_stmt_close($stmt);

            /* PRODUK DENGAN VARIAN */
            if ($memiliki_varian) {
                $varian_tersimpan = [];

                foreach ($varian_data as $varian) {
                    $id_varian = (int)$varian['id_varian'];
                    $status = 'Aktif';

                    if ($id_varian > 0) {
                        $stmt = mysqli_prepare(
                            $conn,
                            "UPDATE produk_varian
                             SET
                                nama_varian = ?,
                                harga = ?,
                                status = ?
                             WHERE
                                id_varian = ?
                                AND id_produk = ?"
                        );

                        if (!$stmt) {
                            throw new Exception("Varian gagal disiapkan.");
                        }

                        mysqli_stmt_bind_param(
                            $stmt,
                            "sdsii",
                            $varian['nama'],
                            $varian['harga'],
                            $status,
                            $id_varian,
                            $id_produk
                        );

                        if (!mysqli_stmt_execute($stmt)) {
                            throw new Exception(
                                "Varian gagal diperbarui: " . mysqli_stmt_error($stmt)
                            );
                        }

                        mysqli_stmt_close($stmt);
                    } else {
                        $stok_baru = 0;
                        $stok_minimum_baru = 0;
                        $lokasi_baru = '';

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
                                ?
                            )"
                        );

                        if (!$stmt) {
                            throw new Exception("Varian baru gagal disiapkan.");
                        }

                        mysqli_stmt_bind_param(
                            $stmt,
                            "isdiiss",
                            $id_produk,
                            $varian['nama'],
                            $varian['harga'],
                            $stok_baru,
                            $stok_minimum_baru,
                            $lokasi_baru,
                            $status
                        );

                        if (!mysqli_stmt_execute($stmt)) {
                            throw new Exception(
                                "Varian baru gagal ditambahkan: " . mysqli_stmt_error($stmt)
                            );
                        }

                        $id_varian = mysqli_insert_id($conn);
                        mysqli_stmt_close($stmt);
                    }

                    $varian_tersimpan[] = $id_varian;
                    $index_file = $varian['index'];

                    /* GAMBAR VARIAN BARU */
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
                                $id_varian,
                                $conn,
                                $error,
                                $uploaded_variant_files
                            );

                            if ($error !== '') {
                                throw new Exception($error);
                            }
                        }
                    }
                }

                /* HAPUS VARIAN YANG DIHILANGKAN, HANYA JIKA STOK = 0 */
                foreach ($varian_awal as $varian_lama) {
                    $id_lama = (int)$varian_lama['id_varian'];

                    if (!in_array($id_lama, $varian_tersimpan, true)) {
                        $gambar_lama_varian = [];

                        $stmt = mysqli_prepare(
                            $conn,
                            "SELECT nama_gambar
                             FROM produk_varian_gambar
                             WHERE id_varian = ?"
                        );

                        mysqli_stmt_bind_param($stmt, "i", $id_lama);
                        mysqli_stmt_execute($stmt);

                        $result_gambar = mysqli_stmt_get_result($stmt);

                        while ($gambar = mysqli_fetch_assoc($result_gambar)) {
                            $gambar_lama_varian[] = $gambar['nama_gambar'];
                        }

                        mysqli_stmt_close($stmt);

                        $stmt = mysqli_prepare(
                            $conn,
                            "DELETE FROM produk_varian
                             WHERE id_varian = ?
                               AND id_produk = ?
                               AND stok = 0"
                        );

                        mysqli_stmt_bind_param(
                            $stmt,
                            "ii",
                            $id_lama,
                            $id_produk
                        );

                        if (!mysqli_stmt_execute($stmt)) {
                            throw new Exception(
                                "Varian gagal dihapus: " . mysqli_stmt_error($stmt)
                            );
                        }

                        $deleted = mysqli_stmt_affected_rows($stmt);
                        mysqli_stmt_close($stmt);

                        if ($deleted > 0) {
                            foreach ($gambar_lama_varian as $nama_gambar) {
                                $hapus_gambar_varian_setelah_commit[] =
                                    __DIR__ .
                                    "/../assets/images/products/variants/" .
                                    $nama_gambar;
                            }
                        }
                    }
                }
            } else {
                /* PRODUK TANPA VARIAN */
                if ($memiliki_varian_awal) {
                    $gambar_semua = [];

                    $stmt = mysqli_prepare(
                        $conn,
                        "SELECT g.nama_gambar
                         FROM produk_varian_gambar g
                         INNER JOIN produk_varian v
                            ON v.id_varian = g.id_varian
                         WHERE v.id_produk = ?"
                    );

                    mysqli_stmt_bind_param($stmt, "i", $id_produk);
                    mysqli_stmt_execute($stmt);

                    $result_gambar = mysqli_stmt_get_result($stmt);

                    while ($gambar = mysqli_fetch_assoc($result_gambar)) {
                        $gambar_semua[] = $gambar['nama_gambar'];
                    }

                    mysqli_stmt_close($stmt);

                    $stmt = mysqli_prepare(
                        $conn,
                        "DELETE FROM produk_varian
                         WHERE id_produk = ?"
                    );

                    mysqli_stmt_bind_param($stmt, "i", $id_produk);

                    if (!mysqli_stmt_execute($stmt)) {
                        throw new Exception(
                            "Varian lama gagal dihapus: " . mysqli_stmt_error($stmt)
                        );
                    }

                    mysqli_stmt_close($stmt);

                    foreach ($gambar_semua as $nama_gambar) {
                        $hapus_gambar_varian_setelah_commit[] =
                            __DIR__ .
                            "/../assets/images/products/variants/" .
                            $nama_gambar;
                    }
                }
            }

            mysqli_commit($conn);

            /* =================================================
               CATAT LOG EDIT PRODUK SETELAH TRANSAKSI BERHASIL
            ================================================= */
            $detailLog = 'Mengubah produk "' . $produk['nama_produk'] .
                '" menjadi "' . $nama_produk . '" (ID: ' . $id_produk . '). ';

            if ($memiliki_varian) {
                $detailLog .= 'Jumlah varian setelah diedit: ' . count($varian_data) . '.';
            } else {
                $detailLog .= 'Produk tanpa varian.';
            }

            $logBerhasil = catatLogAktivitas(
                $conn,
                'produk',
                'Edit Produk',
                $detailLog
            );

            if (!$logBerhasil) {
                error_log(
                    'Gagal mencatat log aktivitas edit produk ID: ' .
                    $id_produk
                );
            }

            /* HAPUS FILE GAMBAR VARIAN LAMA SETELAH TRANSAKSI BERHASIL */
            foreach ($hapus_gambar_varian_setelah_commit as $path_varian_lama) {
                if (is_file($path_varian_lama)) {
                    @unlink($path_varian_lama);
                }
            }

            /* HAPUS GAMBAR PRODUK LAMA JIKA DIGANTI */
            if ($gambar_baru !== $gambar_lama && $gambar_lama !== '') {
                $oldFile =
                    __DIR__ .
                    "/../assets/images/products/" .
                    $gambar_lama;

                if (file_exists($oldFile)) {
                    @unlink($oldFile);
                }
            }

            header("Location: index.php?status=edit");
            exit;

        } catch (Exception $e) {
            mysqli_rollback($conn);

            /* HAPUS GAMBAR VARIAN BARU JIKA TRANSAKSI GAGAL */
            foreach ($uploaded_variant_files as $path_varian_baru) {
                if (is_file($path_varian_baru)) {
                    @unlink($path_varian_baru);
                }
            }

            /* HAPUS GAMBAR PRODUK BARU JIKA TRANSAKSI GAGAL */
            if (
                $gambar_baru !== $gambar_lama &&
                $gambar_baru !== ''
            ) {
                $newFile =
                    __DIR__ .
                    "/../assets/images/products/" .
                    $gambar_baru;

                if (file_exists($newFile)) {
                    @unlink($newFile);
                }
            }

            $error = $e->getMessage();
        }
    }
}

/* =========================================================
   DATA TAMPILAN ULANG SETELAH ERROR
========================================================= */

if (
    $error !== '' &&
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    $memiliki_varian
) {
    $stok_asli_varian = [];

    foreach ($varian_awal as $varian_asli) {
        $stok_asli_varian[(int)$varian_asli['id_varian']] =
            (int)$varian_asli['stok'];
    }

    $varian_awal = [];

    if (
        isset($_POST['nama_varian']) &&
        is_array($_POST['nama_varian'])
    ) {
        foreach ($_POST['nama_varian'] as $index => $nama_varian) {
            $id_varian_post = (int)($_POST['id_varian'][$index] ?? 0);

            $varian_awal[] = [
                'id_varian' => $id_varian_post,
                'nama_varian' => trim($nama_varian),
                'harga' => (float)($_POST['harga_varian'][$index] ?? 0),
                'stok' => $stok_asli_varian[$id_varian_post] ?? 0,
                'stok_minimum' => 0,
                'lokasi_rak' => '',
                'status' => 'Aktif',
                'gambar' => []
            ];
        }
    }
}

/* =========================================================
   AMBIL DATA VARIAN TERBARU UNTUK TAMPILAN
========================================================= */

$varian_tampil = [];

if ($error !== '' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $varian_tampil = $varian_awal;
} else {
    $stmt = mysqli_prepare(
        $conn,
        "SELECT
            v.id_varian,
            v.nama_varian,
            v.harga,
            v.stok,
            v.stok_minimum,
            v.lokasi_rak,
            v.status
         FROM produk_varian v
         WHERE v.id_produk = ?
         ORDER BY v.id_varian ASC"
    );

    mysqli_stmt_bind_param($stmt, "i", $id_produk);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($result)) {
        $row['gambar'] = [];

        $stmt_gambar = mysqli_prepare(
            $conn,
            "SELECT
                id_gambar_varian,
                nama_gambar
             FROM produk_varian_gambar
             WHERE id_varian = ?
             ORDER BY urutan ASC, id_gambar_varian ASC"
        );

        mysqli_stmt_bind_param($stmt_gambar, "i", $row['id_varian']);
        mysqli_stmt_execute($stmt_gambar);

        $result_gambar = mysqli_stmt_get_result($stmt_gambar);

        while ($gambar = mysqli_fetch_assoc($result_gambar)) {
            $row['gambar'][] = $gambar;
        }

        mysqli_stmt_close($stmt_gambar);
        $varian_tampil[] = $row;
    }

    mysqli_stmt_close($stmt);
}

$memiliki_varian_tampil = count($varian_tampil) > 0;

/* DATA KATEGORI */
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
    <title>Edit Produk - Toko ATK & Alat Jahit</title>
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

        .image-preview {
            margin-top: 10px;
        }

        .image-preview img {
            width: 110px;
            height: 110px;
            object-fit: cover;
            border-radius: 10px;
            border: 1px solid #e5e7eb;
        }

        .no-image {
            width: 110px;
            height: 110px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f3f4f6;
            border-radius: 10px;
            color: #9ca3af;
            font-size: 12px;
            text-align: center;
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

        .readonly {
            background: #f9fafb !important;
            color: #6b7280;
            cursor: not-allowed;
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

        .variant-existing-note {
            margin-bottom: 15px;
            padding: 11px 13px;
            background: #f1f5f9;
            border-radius: 8px;
            color: #475569;
            font-size: 12px;
            line-height: 1.5;
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

        .variant-stock-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 9px;
            padding: 12px;
        }

        .variant-stock-number {
            font-size: 18px;
            font-weight: 700;
            color: #1e3a5f;
        }

        .variant-stock-label {
            margin-top: 3px;
            font-size: 12px;
            color: #64748b;
        }

        .variant-images {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }

        .variant-image-item img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #dbe3ea;
        }

        .variant-empty {
            padding: 25px;
            text-align: center;
            color: #6b7280;
            background: #fff;
            border: 1px dashed #cbd5e1;
            border-radius: 9px;
        }

        .new-variant-note {
            grid-column: 1 / -1;
            padding: 10px 12px;
            background: #f8fafc;
            border-radius: 8px;
            color: #475569;
            font-size: 12px;
            line-height: 1.5;
        }

        .hidden {
            display: none !important;
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
            .info-stock,
            .new-variant-note {
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
                    <h1>Edit Produk</h1>
                    <p>Perbarui informasi produk dan variannya.</p>
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
                    id="form-edit-produk"
                >

                    <input
                        type="hidden"
                        name="id_produk"
                        value="<?= $id_produk; ?>"
                    >

                    <div class="form-grid">

                        <div class="form-group full">
                            <label for="nama_produk">Nama Produk *</label>
                            <input
                                type="text"
                                id="nama_produk"
                                name="nama_produk"
                                value="<?= htmlspecialchars($produk['nama_produk']); ?>"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label for="id_kategori">Kategori *</label>
                            <select id="id_kategori" name="id_kategori" required>
                                <option value="">-- Pilih Kategori --</option>
                                <?php while ($kategori = mysqli_fetch_assoc($kategoriResult)): ?>
                                    <option
                                        value="<?= $kategori['id_kategori']; ?>"
                                        <?= ($produk['id_kategori'] == $kategori['id_kategori']) ? 'selected' : ''; ?>
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
                                    <?= !$memiliki_varian_tampil ? 'selected' : ''; ?>
                                >
                                    Tidak
                                </option>
                                <option
                                    value="ya"
                                    <?= $memiliki_varian_tampil ? 'selected' : ''; ?>
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
                                value="<?= htmlspecialchars($produk['harga']); ?>"
                            >
                            <span class="form-help">
                                Digunakan hanya untuk produk tanpa varian.
                            </span>
                        </div>

                        <div class="form-group">
                            <label>Stok Saat Ini</label>
                            <input
                                type="number"
                                value="<?= htmlspecialchars($produk['stok']); ?>"
                                class="readonly"
                                readonly
                            >
                            <span class="form-help">
                                Stok tidak dapat diubah di halaman ini.
                                Gunakan menu Gudang / Stok.
                            </span>
                        </div>

                        <div class="info-stock">
                            <strong>Informasi Pengelolaan Stok</strong><br>
                            Stok produk maupun stok varian tidak diubah dari halaman Edit Produk.
                            Semua perubahan stok dilakukan melalui
                            <strong>Gudang / Pengadaan Stok</strong>.
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
                                Kosongkan jika tidak ingin mengganti gambar. Maksimal 5 MB.
                            </span>

                            <div class="image-preview">
                                <?php if (!empty($produk['gambar'])): ?>
                                    <img
                                        src="../assets/images/products/<?= htmlspecialchars($produk['gambar']); ?>"
                                        alt="Gambar Produk"
                                    >
                                <?php else: ?>
                                    <div class="no-image">Belum ada gambar</div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="form-group full">
                            <label for="deskripsi">Deskripsi</label>
                            <textarea
                                id="deskripsi"
                                name="deskripsi"
                                placeholder="Masukkan deskripsi produk..."
                            ><?= htmlspecialchars($produk['deskripsi'] ?? ''); ?></textarea>
                        </div>

                        <div
                            class="variant-section <?= !$memiliki_varian_tampil ? 'hidden' : ''; ?>"
                            id="variant-section"
                        >
                            <div class="variant-section-header">
                                <div>
                                    <h3>Varian Produk</h3>
                                    <p>Tambahkan, ubah, atau hapus varian sesuai kebutuhan.</p>
                                </div>
                                <button
                                    type="button"
                                    class="btn-add-varian"
                                    onclick="tambahVarian()"
                                >
                                    + Tambah Varian
                                </button>
                            </div>

                            <div class="variant-existing-note">
                                <strong>Catatan:</strong>
                                stok tidak dapat diedit dari halaman ini.
                                Stok setiap varian dikelola melalui
                                <strong>Gudang / Pengadaan Stok</strong>.
                            </div>

                            <div id="variant-list">
                                <?php if (count($varian_tampil) > 0): ?>
                                    <?php foreach ($varian_tampil as $index => $varian): ?>
                                        <div
                                            class="variant-item"
                                            data-index="<?= $index; ?>"
                                        >
                                            <div class="variant-item-header">
                                                <strong class="variant-number">
                                                    Varian <?= $index + 1; ?>
                                                </strong>
                                                <button
                                                    type="button"
                                                    class="btn-remove-varian"
                                                    onclick="hapusVarian(this)"
                                                >
                                                    Hapus Varian
                                                </button>
                                            </div>

                                            <input
                                                type="hidden"
                                                name="id_varian[<?= $index; ?>]"
                                                value="<?= (int)$varian['id_varian']; ?>"
                                            >

                                            <div class="variant-grid">

                                                <div class="form-group">
                                                    <label>Nama Varian *</label>
                                                    <input
                                                        type="text"
                                                        name="nama_varian[<?= $index; ?>]"
                                                        value="<?= htmlspecialchars($varian['nama_varian']); ?>"
                                                        placeholder="Contoh: Merah / Ukuran M"
                                                        required
                                                    >
                                                </div>

                                                <div class="form-group">
                                                    <label>Harga *</label>
                                                    <input
                                                        type="number"
                                                        name="harga_varian[<?= $index; ?>]"
                                                        min="0"
                                                        step="1"
                                                        value="<?= htmlspecialchars($varian['harga']); ?>"
                                                        required
                                                    >
                                                </div>

                                                <div class="variant-stock-box">
                                                    <div class="variant-stock-number">
                                                        <?= (int)$varian['stok']; ?>
                                                    </div>
                                                    <div class="variant-stock-label">
                                                        Stok Saat Ini
                                                    </div>
                                                    <div class="form-help">
                                                        Dikelola melalui Gudang / Pengadaan Stok.
                                                    </div>
                                                </div>

                                                <div class="form-group">
                                                    <label>Tambah Gambar Varian</label>
                                                    <input
                                                        type="file"
                                                        name="gambar_varian[<?= $index; ?>][]"
                                                        accept=".jpg,.jpeg,.png,.webp"
                                                        multiple
                                                    >
                                                    <span class="form-help">
                                                        Maksimal 5 MB per gambar.
                                                    </span>
                                                </div>

                                                <div class="form-group full">
                                                    <label>Gambar Varian Saat Ini</label>

                                                    <?php if (!empty($varian['gambar'])): ?>
                                                        <div class="variant-images">
                                                            <?php foreach ($varian['gambar'] as $gambar): ?>
                                                                <div class="variant-image-item">
                                                                    <img
                                                                        src="../assets/images/products/variants/<?= htmlspecialchars($gambar['nama_gambar']); ?>"
                                                                        alt="Gambar Varian"
                                                                    >
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="form-help">
                                                            Belum ada gambar varian.
                                                        </span>
                                                    <?php endif; ?>
                                                </div>

                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="variant-empty" id="variant-empty">
                                        Belum ada varian. Klik
                                        <b>+ Tambah Varian</b>
                                        untuk menambahkan.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="index.php" class="btn-cancel">Batal</a>
                        <button type="submit" class="btn-save">
                            Simpan Perubahan
                        </button>
                    </div>

                </form>
            </div>

        </main>

        <?php include "../includes/footer.php"; ?>

    </div>
</div>

<script>
    let nomorVarian = <?= count($varian_tampil); ?>;

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

            <input
                type="hidden"
                name="id_varian[${index}]"
                value="0"
            >

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

                <div class="variant-stock-box">
                    <div class="variant-stock-number">0</div>
                    <div class="variant-stock-label">Stok Saat Ini</div>
                    <div class="form-help">
                        Stok baru dimulai dari 0.
                        Pengelolaan stok dilakukan melalui Gudang / Pengadaan Stok.
                    </div>
                </div>

                <div class="form-group">
                    <label>Tambah Gambar Varian</label>
                    <input
                        type="file"
                        name="gambar_varian[${index}][]"
                        accept=".jpg,.jpeg,.png,.webp"
                        multiple
                    >
                    <span class="form-help">Maksimal 5 MB per gambar.</span>
                </div>

                <div class="new-variant-note">
                    Varian baru dibuat dengan <strong>stok 0</strong>.
                    Stok akan dikelola melalui
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

        if (!item) {
            return;
        }

        const stokBox = item.querySelector('.variant-stock-number');

        if (stokBox) {
            const stok = parseInt(stokBox.textContent.trim(), 10) || 0;

            if (stok > 0) {
                alert(
                    'Varian ini masih memiliki stok. Kosongkan stok terlebih dahulu melalui Gudang / Pengadaan Stok.'
                );
                return;
            }
        }

        item.remove();
        reindexVarian();

        const list = document.getElementById('variant-list');
        const empty = document.getElementById('variant-empty');

        if (list.querySelectorAll('.variant-item').length === 0) {
            if (!empty) {
                const div = document.createElement('div');

                div.id = 'variant-empty';
                div.className = 'variant-empty';
                div.innerHTML =
                    'Belum ada varian. Klik <b>+ Tambah Varian</b> untuk menambahkan.';

                list.appendChild(div);
            } else {
                empty.classList.remove('hidden');
            }
        }
    }

    /* REINDEX VARIAN */
    function reindexVarian() {
        const items = document.querySelectorAll(
            '#variant-list .variant-item'
        );

        items.forEach((item, index) => {
            item.dataset.index = index;

            const number = item.querySelector('.variant-number');
            const idVarian = item.querySelector('input[name^="id_varian"]');
            const nama = item.querySelector('input[name^="nama_varian"]');
            const harga = item.querySelector('input[name^="harga_varian"]');
            const gambar = item.querySelector('input[name^="gambar_varian"]');

            if (number) {
                number.textContent = 'Varian ' + (index + 1);
            }

            if (idVarian) {
                idVarian.name = `id_varian[${index}]`;
            }

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

    /* VALIDASI FORM */
    document
        .getElementById('form-edit-produk')
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

            const memilikiVarianAwal =
                <?= $memiliki_varian_awal ? 'true' : 'false'; ?>;

            if (pilihan === 'tidak' && memilikiVarianAwal) {
                const yakin = confirm(
                    'Produk ini memiliki varian. Jika diubah menjadi tanpa varian, semua varian yang stoknya 0 akan dihapus. Lanjutkan?'
                );

                if (!yakin) {
                    event.preventDefault();
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