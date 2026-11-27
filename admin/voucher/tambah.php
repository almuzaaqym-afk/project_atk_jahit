<?php

session_start();

if (!isset($_SESSION['id_admin'])) {
    header("Location: ../login.php");
    exit;
}

require_once "../../config/koneksi.php";
require_once __DIR__ . "/../includes/log_aktivitas.php";


/* =========================================================
   VARIABEL
========================================================= */

$error = '';

$kode_voucher = '';
$nama_voucher = '';
$jenis_diskon = 'Persen';
$nilai_diskon = '';
$minimal_belanja = '0';
$maksimal_diskon = '';
$tanggal_mulai = '';
$tanggal_berakhir = '';
$kuota = '0';
$status = 'Aktif';


/* =========================================================
   PROSES TAMBAH
========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $kode_voucher = strtoupper(trim($_POST['kode_voucher'] ?? ''));
    $nama_voucher = trim($_POST['nama_voucher'] ?? '');
    $jenis_diskon = trim($_POST['jenis_diskon'] ?? 'Persen');
    $nilai_diskon = trim($_POST['nilai_diskon'] ?? '');
    $minimal_belanja = trim($_POST['minimal_belanja'] ?? '0');
    $maksimal_diskon = trim($_POST['maksimal_diskon'] ?? '');
    $tanggal_mulai = trim($_POST['tanggal_mulai'] ?? '');
    $tanggal_berakhir = trim($_POST['tanggal_berakhir'] ?? '');
    $kuota = trim($_POST['kuota'] ?? '0');
    $status = trim($_POST['status'] ?? 'Aktif');


    /* =====================================================
       VALIDASI DASAR
    ===================================================== */

    if (
        $kode_voucher === '' ||
        $nama_voucher === '' ||
        $nilai_diskon === '' ||
        $tanggal_mulai === '' ||
        $tanggal_berakhir === ''
    ) {

        $error = "Semua field wajib diisi kecuali maksimal diskon.";

    }


    /* =====================================================
       VALIDASI JENIS DISKON
    ===================================================== */

    if ($error === '') {

        if (
            $jenis_diskon !== 'Persen' &&
            $jenis_diskon !== 'Nominal'
        ) {

            $error = "Jenis diskon tidak valid.";

        }

    }


    /* =====================================================
       VALIDASI NILAI
    ===================================================== */

    if ($error === '') {

        $nilai_diskon_float = (float)$nilai_diskon;
        $minimal_belanja_float = (float)$minimal_belanja;
        $kuota_int = (int)$kuota;


        if ($nilai_diskon_float <= 0) {

            $error = "Nilai diskon harus lebih dari 0.";

        }


        if (
            $error === '' &&
            $minimal_belanja_float < 0
        ) {

            $error = "Minimal belanja tidak boleh kurang dari 0.";

        }


        if (
            $error === '' &&
            $kuota_int < 0
        ) {

            $error = "Kuota tidak boleh kurang dari 0.";

        }


        if (
            $error === '' &&
            $jenis_diskon === 'Persen' &&
            $nilai_diskon_float > 100
        ) {

            $error = "Diskon persen tidak boleh lebih dari 100%.";

        }

    }


    /* =====================================================
       VALIDASI MAKSIMAL DISKON
    ===================================================== */

    if ($error === '') {

        if ($maksimal_diskon === '') {

            $maksimal_diskon_db = null;

        } else {

            $maksimal_diskon_float = (float)$maksimal_diskon;

            if ($maksimal_diskon_float < 0) {

                $error = "Maksimal diskon tidak boleh kurang dari 0.";

            } else {

                $maksimal_diskon_db = $maksimal_diskon_float;

            }

        }

    }


    /* =====================================================
       VALIDASI TANGGAL
    ===================================================== */

    if ($error === '') {

        $waktu_mulai = strtotime($tanggal_mulai);
        $waktu_berakhir = strtotime($tanggal_berakhir);


        if (
            $waktu_mulai === false ||
            $waktu_berakhir === false
        ) {

            $error = "Format tanggal tidak valid.";

        }


        if (
            $error === '' &&
            $waktu_berakhir <= $waktu_mulai
        ) {

            $error = "Tanggal berakhir harus setelah tanggal mulai.";

        }

    }


    /* =====================================================
       VALIDASI STATUS
    ===================================================== */

    if ($error === '') {

        if (
            $status !== 'Aktif' &&
            $status !== 'Tidak Aktif'
        ) {

            $error = "Status voucher tidak valid.";

        }

    }


    /* =====================================================
       CEK KODE VOUCHER
    ===================================================== */

    if ($error === '') {

        $stmt = mysqli_prepare(
            $conn,
            "SELECT id_voucher
             FROM voucher
             WHERE kode_voucher = ?
             LIMIT 1"
        );


        if (!$stmt) {

            $error = "Gagal memproses data.";

        } else {

            mysqli_stmt_bind_param(
                $stmt,
                "s",
                $kode_voucher
            );

            mysqli_stmt_execute($stmt);

            $result = mysqli_stmt_get_result($stmt);

            $voucher_ada = mysqli_fetch_assoc($result);

            mysqli_stmt_close($stmt);


            if ($voucher_ada) {

                $error = "Kode voucher tersebut sudah digunakan.";

            }

        }

    }


    /* =====================================================
       INSERT
    ===================================================== */

    if ($error === '') {

        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO voucher
            (
                kode_voucher,
                nama_voucher,
                jenis_diskon,
                nilai_diskon,
                minimal_belanja,
                maksimal_diskon,
                tanggal_mulai,
                tanggal_berakhir,
                kuota,
                jumlah_digunakan,
                status
            )
            VALUES
            (
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                0,
                ?
            )"
        );


        if (!$stmt) {

            $error = "Gagal menyiapkan data.";

        } else {

            mysqli_stmt_bind_param(
                $stmt,
                "sssddsssis",
                $kode_voucher,
                $nama_voucher,
                $jenis_diskon,
                $nilai_diskon_float,
                $minimal_belanja_float,
                $maksimal_diskon_db,
                $tanggal_mulai,
                $tanggal_berakhir,
                $kuota_int,
                $status
            );


            /*
             * Jika maksimal_diskon NULL,
             * mysqli tetap mengirim nilai sesuai tipe
             * yang telah ditentukan.
             */

            $berhasil = mysqli_stmt_execute($stmt);

            mysqli_stmt_close($stmt);


            if ($berhasil) {

                $id_voucher_baru = (int) mysqli_insert_id($conn);

                try {
                    catatLogAktivitas(
                        $conn,
                        'voucher',
                        'Tambah Voucher',
                        "Menambahkan voucher ID: {$id_voucher_baru}, kode: {$kode_voucher}, nama: {$nama_voucher}, jenis diskon: {$jenis_diskon}, nilai diskon: {$nilai_diskon_float}, status: {$status}."
                    );
                } catch (Throwable $e) {
                    // Kegagalan pencatatan log tidak membatalkan penambahan voucher.
                }

                header("Location: index.php?status=tambah");
                exit;

            } else {

                $error = "Voucher gagal ditambahkan.";

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

    <title>
        Tambah Voucher - Toko ATK & Alat Jahit
    </title>


    <link
        rel="stylesheet"
        href="../assets/css/admin.css"
    >


    <style>

        .voucher-form-page {
            max-width:1000px;
        }


        /* =====================================================
           HEADER
        ===================================================== */

        .form-header {
            display:flex;
            justify-content:space-between;
            align-items:flex-start;
            gap:20px;
            margin-bottom:25px;
        }


        .form-header h1 {
            margin:0 0 7px;
            color:#102f4b;
            font-size:28px;
        }


        .form-header p {
            margin:0;
            color:#7890a4;
            font-size:14px;
        }


        .btn-kembali {
            display:inline-flex;
            align-items:center;
            justify-content:center;
            padding:10px 16px;
            border-radius:8px;
            background:#eef3f7;
            border:1px solid #dce5ec;
            color:#334f66;
            text-decoration:none;
            font-size:13px;
            font-weight:700;
            white-space:nowrap;
        }


        .btn-kembali:hover {
            background:#e1e9ef;
        }


        /* =====================================================
           ALERT
        ===================================================== */

        .voucher-alert {
            padding:14px 18px;
            border-radius:10px;
            margin-bottom:20px;
            background:#fce8e8;
            color:#a33;
            border:1px solid #f2caca;
            font-size:14px;
            font-weight:600;
        }


        /* =====================================================
           CARD
        ===================================================== */

        .form-card {
            background:#ffffff;
            border:1px solid #e0e7ed;
            border-radius:14px;
            box-shadow:0 4px 15px rgba(16,47,75,.04);
            overflow:hidden;
        }


        .form-card-header {
            padding:22px 24px;
            border-bottom:1px solid #e6edf2;
        }


        .form-card-header h2 {
            margin:0 0 6px;
            color:#102f4b;
            font-size:20px;
        }


        .form-card-header p {
            margin:0;
            color:#7890a4;
            font-size:14px;
        }


        .form-card-body {
            padding:25px;
        }


        /* =====================================================
           FORM GRID
        ===================================================== */

        .form-grid {
            display:grid;
            grid-template-columns:1fr 1fr;
            gap:20px;
        }


        .form-group {
            display:flex;
            flex-direction:column;
        }


        .form-group.full {
            grid-column:1 / -1;
        }


        .form-group label {
            margin-bottom:8px;
            color:#334f66;
            font-size:13px;
            font-weight:700;
        }


        .form-group label span {
            color:#b23b3b;
        }


        .form-group input,
        .form-group select {
            width:100%;
            box-sizing:border-box;
            padding:11px 13px;
            border:1px solid #dce5ec;
            border-radius:8px;
            background:#ffffff;
            color:#334f66;
            font-family:inherit;
            font-size:13px;
            outline:none;
        }


        .form-group input:focus,
        .form-group select:focus {
            border-color:#5f7f99;
            box-shadow:0 0 0 3px rgba(16,47,75,.06);
        }


        .form-help {
            margin-top:6px;
            color:#9aafbf;
            font-size:11px;
            line-height:1.5;
        }


        /* =====================================================
           FORM ACTION
        ===================================================== */

        .form-actions {
            display:flex;
            justify-content:flex-end;
            gap:10px;
            margin-top:25px;
            padding-top:20px;
            border-top:1px solid #e6edf2;
        }


        .btn-batal {
            display:inline-flex;
            align-items:center;
            justify-content:center;
            padding:11px 17px;
            border-radius:8px;
            background:#eef3f7;
            border:1px solid #dce5ec;
            color:#334f66;
            text-decoration:none;
            font-size:13px;
            font-weight:700;
        }


        .btn-batal:hover {
            background:#e1e9ef;
        }


        .btn-simpan {
            border:none;
            padding:11px 18px;
            border-radius:8px;
            background:#102f4b;
            color:#ffffff;
            font-family:inherit;
            font-size:13px;
            font-weight:700;
            cursor:pointer;
        }


        .btn-simpan:hover {
            background:#1c496b;
        }


        /* =====================================================
           RESPONSIVE
        ===================================================== */

        @media (max-width:700px) {

            .form-header {
                flex-direction:column;
            }


            .btn-kembali {
                width:100%;
            }


            .form-grid {
                grid-template-columns:1fr;
            }


            .form-group.full {
                grid-column:auto;
            }


            .form-actions {
                flex-direction:column;
            }


            .btn-batal,
            .btn-simpan {
                width:100%;
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


            <div class="voucher-form-page">


                <!-- HEADER -->

                <div class="form-header">


                    <div>

                        <h1>
                            Tambah Voucher
                        </h1>

                        <p>
                            Buat voucher atau promo baru untuk pelanggan.
                        </p>

                    </div>


                    <a
                        href="index.php"
                        class="btn-kembali"
                    >
                        ← Kembali
                    </a>


                </div>


                <!-- ERROR -->

                <?php if ($error !== '') : ?>

                    <div class="voucher-alert">

                        ⚠
                        <?= htmlspecialchars($error); ?>

                    </div>

                <?php endif; ?>


                <!-- FORM -->

                <div class="form-card">


                    <div class="form-card-header">

                        <h2>
                            Informasi Voucher
                        </h2>

                        <p>
                            Isi informasi promo sesuai kebutuhan toko.
                        </p>

                    </div>


                    <div class="form-card-body">


                        <form
                            method="POST"
                            autocomplete="off"
                        >


                            <div class="form-grid">


                                <!-- KODE -->

                                <div class="form-group">

                                    <label>
                                        Kode Voucher
                                        <span>*</span>
                                    </label>

                                    <input
                                        type="text"
                                        name="kode_voucher"
                                        value="<?= htmlspecialchars($kode_voucher); ?>"
                                        maxlength="50"
                                        placeholder="Contoh: HEMAT20"
                                        required
                                    >

                                    <div class="form-help">
                                        Kode akan otomatis diubah menjadi huruf kapital.
                                    </div>

                                </div>


                                <!-- NAMA -->

                                <div class="form-group">

                                    <label>
                                        Nama Voucher
                                        <span>*</span>
                                    </label>

                                    <input
                                        type="text"
                                        name="nama_voucher"
                                        value="<?= htmlspecialchars($nama_voucher); ?>"
                                        maxlength="150"
                                        placeholder="Contoh: Promo Hemat 20%"
                                        required
                                    >

                                </div>


                                <!-- JENIS DISKON -->

                                <div class="form-group">

                                    <label>
                                        Jenis Diskon
                                        <span>*</span>
                                    </label>

                                    <select
                                        name="jenis_diskon"
                                        id="jenis_diskon"
                                        required
                                    >

                                        <option
                                            value="Persen"
                                            <?= $jenis_diskon === 'Persen' ? 'selected' : ''; ?>
                                        >
                                            Persen (%)
                                        </option>

                                        <option
                                            value="Nominal"
                                            <?= $jenis_diskon === 'Nominal' ? 'selected' : ''; ?>
                                        >
                                            Nominal (Rp)
                                        </option>

                                    </select>

                                </div>


                                <!-- NILAI -->

                                <div class="form-group">

                                    <label>
                                        Nilai Diskon
                                        <span>*</span>
                                    </label>

                                    <input
                                        type="number"
                                        name="nilai_diskon"
                                        value="<?= htmlspecialchars($nilai_diskon); ?>"
                                        min="0.01"
                                        step="0.01"
                                        placeholder="Contoh: 20"
                                        required
                                    >

                                    <div
                                        class="form-help"
                                        id="nilai-help"
                                    >
                                        Untuk persen isi 20 untuk diskon 20%.
                                    </div>

                                </div>


                                <!-- MINIMAL -->

                                <div class="form-group">

                                    <label>
                                        Minimal Belanja
                                    </label>

                                    <input
                                        type="number"
                                        name="minimal_belanja"
                                        value="<?= htmlspecialchars($minimal_belanja); ?>"
                                        min="0"
                                        step="1000"
                                        placeholder="Contoh: 50000"
                                    >

                                    <div class="form-help">
                                        Isi 0 jika tidak ada minimal belanja.
                                    </div>

                                </div>


                                <!-- MAKSIMAL -->

                                <div class="form-group">

                                    <label>
                                        Maksimal Diskon
                                    </label>

                                    <input
                                        type="number"
                                        name="maksimal_diskon"
                                        value="<?= htmlspecialchars($maksimal_diskon); ?>"
                                        min="0"
                                        step="1000"
                                        placeholder="Contoh: 20000"
                                    >

                                    <div class="form-help">
                                        Kosongkan jika tidak ada batas maksimal diskon.
                                    </div>

                                </div>


                                <!-- MULAI -->

                                <div class="form-group">

                                    <label>
                                        Tanggal Mulai
                                        <span>*</span>
                                    </label>

                                    <input
                                        type="datetime-local"
                                        name="tanggal_mulai"
                                        value="<?= htmlspecialchars($tanggal_mulai); ?>"
                                        required
                                    >

                                </div>


                                <!-- BERAKHIR -->

                                <div class="form-group">

                                    <label>
                                        Tanggal Berakhir
                                        <span>*</span>
                                    </label>

                                    <input
                                        type="datetime-local"
                                        name="tanggal_berakhir"
                                        value="<?= htmlspecialchars($tanggal_berakhir); ?>"
                                        required
                                    >

                                </div>


                                <!-- KUOTA -->

                                <div class="form-group">

                                    <label>
                                        Kuota Penggunaan
                                    </label>

                                    <input
                                        type="number"
                                        name="kuota"
                                        value="<?= htmlspecialchars($kuota); ?>"
                                        min="0"
                                        step="1"
                                        placeholder="Contoh: 100"
                                    >

                                    <div class="form-help">
                                        Isi 0 jika voucher tidak memiliki batas penggunaan.
                                    </div>

                                </div>


                                <!-- STATUS -->

                                <div class="form-group">

                                    <label>
                                        Status
                                        <span>*</span>
                                    </label>

                                    <select
                                        name="status"
                                        required
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


                            </div>


                            <!-- ACTION -->

                            <div class="form-actions">


                                <a
                                    href="index.php"
                                    class="btn-batal"
                                >
                                    Batal
                                </a>


                                <button
                                    type="submit"
                                    class="btn-simpan"
                                >
                                    Simpan Voucher
                                </button>


                            </div>


                        </form>


                    </div>


                </div>


            </div>


        </section>


        <?php require_once "../includes/footer.php"; ?>


    </main>


</div>


<script src="../assets/js/admin.js"></script>


<script>

    const jenisDiskon =
        document.getElementById('jenis_diskon');

    const nilaiHelp =
        document.getElementById('nilai-help');


    function updateHelpDiskon() {

        if (jenisDiskon.value === 'Persen') {

            nilaiHelp.textContent =
                'Untuk persen isi 20 untuk diskon 20%.';

        } else {

            nilaiHelp.textContent =
                'Isi nominal diskon dalam rupiah, contoh 10000.';

        }

    }


    jenisDiskon.addEventListener(
        'change',
        updateHelpDiskon
    );


    updateHelpDiskon();

</script>


</body>

</html>