<?php
session_start();

if (!isset($_SESSION['id_admin'])) {
    header("Location: ../login.php");
    exit;
}

require_once "../../config/koneksi.php";
require_once "../includes/hak_akses.php";
require_once __DIR__ . "/../includes/log_aktivitas.php";

cekAkses('pembelian');

/* =====================================================
   VALIDASI REQUEST
===================================================== */

if (
    $_SERVER['REQUEST_METHOD'] !== 'POST' ||
    !isset($_POST['hapus_pembelian'])
) {
    header("Location: index.php");
    exit;
}

/* =====================================================
   ID PEMBELIAN
===================================================== */

$id_pembelian = (int) ($_POST['id_pembelian'] ?? 0);

if ($id_pembelian <= 0) {
    header("Location: index.php?status=tidak_ditemukan");
    exit;
}

/* =====================================================
   PROSES HAPUS PEMBELIAN
===================================================== */

$transaction_started = false;
$stmt_cek = null;
$stmt_detail = null;
$stmt_pembelian = null;

try {
    /* =================================================
       MULAI TRANSAKSI
    ================================================= */

    if (!mysqli_begin_transaction($conn)) {
        throw new Exception("Gagal memulai transaksi database.");
    }

    $transaction_started = true;

    /* =================================================
       KUNCI DAN CEK DATA PEMBELIAN
    ================================================= */

    $stmt_cek = mysqli_prepare(
        $conn,
        "SELECT id_pembelian, nomor_pembelian, status
         FROM pembelian
         WHERE id_pembelian = ?
         LIMIT 1
         FOR UPDATE"
    );

    if (!$stmt_cek) {
        throw new Exception("Gagal menyiapkan pemeriksaan pembelian.");
    }

    mysqli_stmt_bind_param(
        $stmt_cek,
        "i",
        $id_pembelian
    );

    if (!mysqli_stmt_execute($stmt_cek)) {
        throw new Exception("Gagal memeriksa data pembelian.");
    }

    $result = mysqli_stmt_get_result($stmt_cek);
    $pembelian = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt_cek);
    $stmt_cek = null;

    /* =============================================
       PEMBELIAN TIDAK DITEMUKAN
    ============================================= */

    if (!$pembelian) {
        mysqli_rollback($conn);
        $transaction_started = false;

        header("Location: index.php?status=tidak_ditemukan");
        exit;
    }

    /* =============================================
       HANYA DRAFT YANG BOLEH DIHAPUS
    ============================================= */

    if ($pembelian['status'] !== 'Draft') {
        mysqli_rollback($conn);
        $transaction_started = false;

        header("Location: index.php?status=tidak_bisa_hapus");
        exit;
    }

    /* =================================================
       HAPUS DETAIL PEMBELIAN
    ================================================= */

    $stmt_detail = mysqli_prepare(
        $conn,
        "DELETE FROM detail_pembelian
         WHERE id_pembelian = ?"
    );

    if (!$stmt_detail) {
        throw new Exception("Gagal menyiapkan penghapusan detail pembelian.");
    }

    mysqli_stmt_bind_param(
        $stmt_detail,
        "i",
        $id_pembelian
    );

    if (!mysqli_stmt_execute($stmt_detail)) {
        throw new Exception("Gagal menghapus detail pembelian.");
    }

    mysqli_stmt_close($stmt_detail);
    $stmt_detail = null;

    /* =================================================
       HAPUS HEADER PEMBELIAN
    ================================================= */

    $stmt_pembelian = mysqli_prepare(
        $conn,
        "DELETE FROM pembelian
         WHERE id_pembelian = ?
           AND status = 'Draft'"
    );

    if (!$stmt_pembelian) {
        throw new Exception("Gagal menyiapkan penghapusan pembelian.");
    }

    mysqli_stmt_bind_param(
        $stmt_pembelian,
        "i",
        $id_pembelian
    );

    if (!mysqli_stmt_execute($stmt_pembelian)) {
        throw new Exception("Gagal menghapus data pembelian.");
    }

    $affected_rows = mysqli_stmt_affected_rows($stmt_pembelian);

    mysqli_stmt_close($stmt_pembelian);
    $stmt_pembelian = null;

    if ($affected_rows !== 1) {
        throw new Exception("Pembelian gagal dihapus.");
    }

    /* =================================================
       COMMIT TRANSAKSI
    ================================================= */

    if (!mysqli_commit($conn)) {
        throw new Exception("Gagal menyimpan penghapusan pembelian.");
    }

    $transaction_started = false;

    /* =================================================
       CATAT LOG HAPUS PEMBELIAN
    ================================================= */

    $nomorPembelian = $pembelian['nomor_pembelian'] ?? '-';

    $detailLog = 'Menghapus pembelian "' .
        $nomorPembelian .
        '" (ID: ' . $id_pembelian .
        ', status: Draft).';

    $logBerhasil = catatLogAktivitas(
        $conn,
        'pembelian',
        'Hapus Pembelian',
        $detailLog
    );

    if (!$logBerhasil) {
        error_log(
            'Gagal mencatat log hapus pembelian: ID ' .
            $id_pembelian
        );
    }

    header("Location: index.php?status=hapus");
    exit;

} catch (Throwable $e) {
    if ($transaction_started) {
        mysqli_rollback($conn);
    }

    foreach ([$stmt_cek, $stmt_detail, $stmt_pembelian] as $statement) {
        if ($statement instanceof mysqli_stmt) {
            try {
                mysqli_stmt_close($statement);
            } catch (Throwable $ignored) {
                // Abaikan kegagalan penutupan statement.
            }
        }
    }

    error_log(
        "Gagal menghapus pembelian ID " .
        $id_pembelian .
        ": " .
        $e->getMessage()
    );

    header("Location: index.php?status=gagal_hapus");
    exit;
}