<?php

session_start();

function permintaanAjaxStok(): bool
{
    return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
}

function responStok(string $status, string $pesan = ''): void
{
    $pesanDefault = [
        'berhasil' => 'Stok berhasil diperbarui.',
        'tidak_valid' => 'Data stok tidak valid.',
        'tidak_ditemukan' => 'Produk atau varian tidak ditemukan.',
        'gagal' => 'Gagal memperbarui stok.'
    ];

    if ($pesan === '') {
        $pesan = $pesanDefault[$status] ?? 'Permintaan tidak dapat diproses.';
    }

    if (permintaanAjaxStok()) {
        $berhasil = $status === 'berhasil';

        http_response_code($berhasil ? 200 : 422);

        header('Content-Type: application/json; charset=UTF-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

        echo json_encode([
            'success' => $berhasil,
            'status' => $status,
            'message' => $pesan
        ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);

        exit;
    }

    $url = 'index.php?status=' . rawurlencode($status);

    if ($status === 'gagal' && $pesan !== ($pesanDefault['gagal'] ?? '')) {
        $url .= '&pesan=' . rawurlencode($pesan);
    }

    header('Location: ' . $url);
    exit;
}

if (!isset($_SESSION['id_admin'])) {
    if (permintaanAjaxStok()) {
        http_response_code(401);

        header('Content-Type: application/json; charset=UTF-8');

        echo json_encode([
            'success' => false,
            'status' => 'sesi_berakhir',
            'message' => 'Sesi admin berakhir. Silakan login kembali.'
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    header('Location: ../login.php');
    exit;
}

require_once "../../config/koneksi.php";
require_once "../includes/hak_akses.php";
require_once __DIR__ . "/../includes/log_aktivitas.php";

cekAkses('stok');

if (
    $_SERVER['REQUEST_METHOD'] !== 'POST' ||
    !isset($_POST['simpan_stok'])
) {
    responStok('tidak_valid');
}

$id_produk = (int)($_POST['id_produk'] ?? 0);
$mode = $_POST['mode'] ?? 'produk';

if ($id_produk <= 0) {
    responStok('tidak_valid');
}

/* =========================================================
   CEK PRODUK
========================================================= */
$stmt = mysqli_prepare(
    $conn,
    "SELECT id_produk, nama_produk, stok, stok_minimum, lokasi_rak
     FROM produk
     WHERE id_produk = ?
     LIMIT 1"
);

if (!$stmt) {
    responStok('gagal', 'Query pemeriksaan produk gagal dibuat.');
}

mysqli_stmt_bind_param($stmt, 'i', $id_produk);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$produk = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$produk) {
    responStok('tidak_ditemukan');
}

/* =========================================================
   MODE PRODUK TANPA VARIAN
========================================================= */
if ($mode === 'produk') {
    $stok_baru = isset($_POST['stok_baru'])
        ? (int)$_POST['stok_baru']
        : -1;

    $stok_minimum = isset($_POST['stok_minimum'])
        ? (int)$_POST['stok_minimum']
        : -1;

    $lokasi_rak = trim($_POST['lokasi_rak'] ?? '');

    if ($stok_baru < 0 || $stok_minimum < 0) {
        responStok(
            'tidak_valid',
            'Stok dan stok minimum tidak boleh negatif.'
        );
    }

    /* PASTIKAN PRODUK TIDAK MEMILIKI VARIAN */
    $stmt = mysqli_prepare(
        $conn,
        "SELECT COUNT(*) AS jumlah
         FROM produk_varian
         WHERE id_produk = ?"
    );

    if (!$stmt) {
        responStok('gagal', 'Query pemeriksaan varian gagal dibuat.');
    }

    mysqli_stmt_bind_param($stmt, 'i', $id_produk);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $jumlah_varian = (int)(mysqli_fetch_assoc($result)['jumlah'] ?? 0);

    mysqli_stmt_close($stmt);

    if ($jumlah_varian > 0) {
        responStok(
            'tidak_valid',
            'Produk ini memiliki varian. Kelola stok melalui masing-masing varian.'
        );
    }

    /* VALIDASI LOKASI RAK */
    if ($lokasi_rak !== '') {
        $stmt = mysqli_prepare(
            $conn,
            "SELECT id_lokasi
             FROM lokasi_rak
             WHERE kode_rak = ?
             LIMIT 1"
        );

        if (!$stmt) {
            responStok('gagal', 'Query validasi lokasi rak gagal dibuat.');
        }

        mysqli_stmt_bind_param($stmt, 's', $lokasi_rak);
        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);
        $ada = mysqli_num_rows($result) > 0;

        mysqli_stmt_close($stmt);

        if (!$ada) {
            responStok(
                'tidak_valid',
                'Lokasi rak yang dipilih tidak ditemukan.'
            );
        }
    }

    /* UPDATE PRODUK */
    $stmt = mysqli_prepare(
        $conn,
        "UPDATE produk
         SET
            stok = ?,
            stok_minimum = ?,
            lokasi_rak = NULLIF(?, '')
         WHERE id_produk = ?"
    );

    if (!$stmt) {
        responStok('gagal', 'Query penyimpanan stok produk gagal dibuat.');
    }

    mysqli_stmt_bind_param(
        $stmt,
        'iisi',
        $stok_baru,
        $stok_minimum,
        $lokasi_rak,
        $id_produk
    );

    $sukses = mysqli_stmt_execute($stmt);
    $pesanError = mysqli_stmt_error($stmt);

    mysqli_stmt_close($stmt);

    if ($sukses) {
        /* CATAT LOG SETELAH UPDATE BERHASIL */
        $stok_lama = (int)($produk['stok'] ?? 0);
        $stok_minimum_lama = (int)($produk['stok_minimum'] ?? 0);
        $lokasi_lama = trim((string)($produk['lokasi_rak'] ?? ''));

        $lokasi_baru_log = $lokasi_rak !== '' ? $lokasi_rak : '-';
        $lokasi_lama_log = $lokasi_lama !== '' ? $lokasi_lama : '-';

        $detailLog = 'Produk "' . $produk['nama_produk'] .
            '" (ID: ' . $id_produk . '). ' .
            'Stok: ' . $stok_lama . ' → ' . $stok_baru . '; ' .
            'stok minimum: ' . $stok_minimum_lama . ' → ' . $stok_minimum . '; ' .
            'lokasi/rak: ' . $lokasi_lama_log . ' → ' . $lokasi_baru_log . '.';

        $logBerhasil = catatLogAktivitas(
            $conn,
            'stok',
            'Perbarui Stok',
            $detailLog
        );

        if (!$logBerhasil) {
            error_log(
                'Gagal mencatat log aktivitas stok produk ID: ' .
                $id_produk
            );
        }

        responStok('berhasil');
    }

    error_log(
        'Gagal update stok produk ' .
        $id_produk .
        ': ' .
        $pesanError
    );

    responStok(
        'gagal',
        'Stok produk gagal disimpan. Periksa koneksi database.'
    );
}

/* =========================================================
   MODE PRODUK DENGAN VARIAN
========================================================= */
if ($mode === 'varian') {
    $varian_post = $_POST['varian'] ?? [];

    if (!is_array($varian_post) || count($varian_post) === 0) {
        responStok('tidak_valid', 'Data varian tidak ditemukan.');
    }

    mysqli_begin_transaction($conn);

    try {
        /* AMBIL DATA VARIAN SEBELUM DIPERBARUI */
        $stmt = mysqli_prepare(
            $conn,
            "SELECT
                id_varian,
                nama_varian,
                stok,
                stok_minimum,
                lokasi_rak
             FROM produk_varian
             WHERE id_produk = ?"
        );

        if (!$stmt) {
            throw new Exception('Data varian gagal diambil.');
        }

        mysqli_stmt_bind_param($stmt, 'i', $id_produk);
        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);
        $varian_valid = [];
        $detail_perubahan_varian = [];

        while ($row = mysqli_fetch_assoc($result)) {
            $varian_valid[(int)$row['id_varian']] = $row;
        }

        mysqli_stmt_close($stmt);

        if (count($varian_valid) === 0) {
            throw new Exception('Produk ini belum memiliki varian.');
        }

        /* VALIDASI DAN UPDATE SETIAP VARIAN */
        foreach ($varian_post as $key_varian => $data) {
            if (!is_array($data)) {
                throw new Exception('Format data varian tidak valid.');
            }

            $id_varian = (int)$key_varian;

            if (
                isset($data['id_varian']) &&
                (int)$data['id_varian'] !== $id_varian
            ) {
                throw new Exception('ID varian tidak sesuai.');
            }

            if (!isset($varian_valid[$id_varian])) {
                throw new Exception(
                    'Varian tidak valid atau bukan milik produk ini.'
                );
            }

            $stok = isset($data['stok'])
                ? (int)$data['stok']
                : -1;

            $stok_minimum = isset($data['stok_minimum'])
                ? (int)$data['stok_minimum']
                : -1;

            $lokasi_rak = trim($data['lokasi_rak'] ?? '');

            if ($stok < 0 || $stok_minimum < 0) {
                throw new Exception(
                    'Stok dan stok minimum varian tidak boleh negatif.'
                );
            }

            /* VALIDASI LOKASI VARIAN */
            if ($lokasi_rak !== '') {
                $stmt = mysqli_prepare(
                    $conn,
                    "SELECT id_lokasi
                     FROM lokasi_rak
                     WHERE kode_rak = ?
                     LIMIT 1"
                );

                if (!$stmt) {
                    throw new Exception('Lokasi rak gagal diverifikasi.');
                }

                mysqli_stmt_bind_param($stmt, 's', $lokasi_rak);
                mysqli_stmt_execute($stmt);

                $resultLokasi = mysqli_stmt_get_result($stmt);
                $lokasi_ada = mysqli_num_rows($resultLokasi) > 0;

                mysqli_stmt_close($stmt);

                if (!$lokasi_ada) {
                    throw new Exception(
                        'Lokasi rak varian tidak ditemukan.'
                    );
                }
            }

            /* SIMPAN PERUBAHAN UNTUK DETAIL LOG */
            $data_varian_lama = $varian_valid[$id_varian];

            $stok_lama_varian = (int)($data_varian_lama['stok'] ?? 0);
            $minimum_lama_varian = (int)($data_varian_lama['stok_minimum'] ?? 0);
            $lokasi_lama_varian = trim(
                (string)($data_varian_lama['lokasi_rak'] ?? '')
            );

            if (
                $stok_lama_varian !== $stok ||
                $minimum_lama_varian !== $stok_minimum ||
                $lokasi_lama_varian !== $lokasi_rak
            ) {
                $detail_perubahan_varian[] =
                    'Varian "' . $data_varian_lama['nama_varian'] .
                    '" (ID: ' . $id_varian . '): stok ' .
                    $stok_lama_varian . ' → ' . $stok .
                    '; stok minimum ' . $minimum_lama_varian . ' → ' . $stok_minimum .
                    '; lokasi/rak ' .
                    ($lokasi_lama_varian !== '' ? $lokasi_lama_varian : '-') .
                    ' → ' . ($lokasi_rak !== '' ? $lokasi_rak : '-') . '.';
            }

            /* UPDATE VARIAN */
            $stmt = mysqli_prepare(
                $conn,
                "UPDATE produk_varian
                 SET
                    stok = ?,
                    stok_minimum = ?,
                    lokasi_rak = NULLIF(?, '')
                 WHERE
                    id_varian = ?
                    AND id_produk = ?"
            );

            if (!$stmt) {
                throw new Exception('Query update varian gagal dibuat.');
            }

            mysqli_stmt_bind_param(
                $stmt,
                'iisii',
                $stok,
                $stok_minimum,
                $lokasi_rak,
                $id_varian,
                $id_produk
            );

            $sukses = mysqli_stmt_execute($stmt);
            $pesanError = mysqli_stmt_error($stmt);

            mysqli_stmt_close($stmt);

            if (!$sukses) {
                error_log(
                    'Gagal update varian ' .
                    $id_varian .
                    ': ' .
                    $pesanError
                );

                throw new Exception(
                    'Varian gagal disimpan ke database.'
                );
            }
        }

        /* HITUNG TOTAL STOK VARIAN AKTIF */
        $stmt = mysqli_prepare(
            $conn,
            "SELECT COALESCE(SUM(stok), 0) AS total_stok
             FROM produk_varian
             WHERE
                id_produk = ?
                AND status = 'Aktif'"
        );

        if (!$stmt) {
            throw new Exception('Total stok varian gagal dihitung.');
        }

        mysqli_stmt_bind_param($stmt, 'i', $id_produk);
        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);
        $data_total = mysqli_fetch_assoc($result);

        mysqli_stmt_close($stmt);

        $total_stok = (int)($data_total['total_stok'] ?? 0);

        /* SINKRONISASI STOK PRODUK UTAMA */
        $stmt = mysqli_prepare(
            $conn,
            "UPDATE produk
             SET
                stok = ?,
                stok_minimum = 0,
                lokasi_rak = NULL
             WHERE id_produk = ?"
        );

        if (!$stmt) {
            throw new Exception(
                'Query sinkronisasi stok produk gagal dibuat.'
            );
        }

        mysqli_stmt_bind_param(
            $stmt,
            'ii',
            $total_stok,
            $id_produk
        );

        $sukses = mysqli_stmt_execute($stmt);
        $pesanError = mysqli_stmt_error($stmt);

        mysqli_stmt_close($stmt);

        if (!$sukses) {
            error_log(
                'Gagal sinkron stok produk ' .
                $id_produk .
                ': ' .
                $pesanError
            );

            throw new Exception(
                'Total stok produk gagal disinkronkan.'
            );
        }

        mysqli_commit($conn);

        /* CATAT LOG SETELAH SELURUH PERUBAHAN BERHASIL */
        $detailLog = 'Memperbarui stok varian produk "' .
            $produk['nama_produk'] . '" (ID: ' . $id_produk . '). ' .
            'Total stok varian aktif: ' . (int)($produk['stok'] ?? 0) .
            ' → ' . $total_stok . '. ';

        if (!empty($detail_perubahan_varian)) {
            $detailLog .= implode(' | ', $detail_perubahan_varian);
        } else {
            $detailLog .=
                'Tidak ada perubahan nilai stok, stok minimum, atau lokasi/rak pada varian.';
        }

        $logBerhasil = catatLogAktivitas(
            $conn,
            'stok',
            'Perbarui Stok Varian',
            $detailLog
        );

        if (!$logBerhasil) {
            error_log(
                'Gagal mencatat log aktivitas stok varian produk ID: ' .
                $id_produk
            );
        }

        responStok('berhasil');

    } catch (Throwable $e) {
        mysqli_rollback($conn);

        error_log(
            'Gagal menyimpan stok varian produk ' .
            $id_produk .
            ': ' .
            $e->getMessage()
        );

        responStok('gagal', $e->getMessage());
    }
}

responStok('tidak_valid', 'Mode penyimpanan stok tidak valid.');