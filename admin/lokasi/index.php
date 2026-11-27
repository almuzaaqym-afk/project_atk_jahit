<?php

session_start();

if (!isset($_SESSION['id_admin'])) {
    header("Location: ../login.php");
    exit;
}

require_once "../../config/koneksi.php";
require_once "../includes/hak_akses.php";

cekAkses('lokasi');

$pesan = "";
$error = "";
$search = trim($_GET['search'] ?? '');
$search_escaped = mysqli_real_escape_string($conn, $search);

/* =========================================================
   NOTIFIKASI
========================================================= */

if (isset($_GET['status'])) {
    switch ($_GET['status']) {
        case 'berhasil':
            $pesan = "Lokasi / rak berhasil ditambahkan.";
            break;

        case 'edit':
            $pesan = "Lokasi / rak berhasil diperbarui.";
            break;

        case 'hapus':
            $pesan = "Lokasi / rak berhasil dihapus.";
            break;

        case 'digunakan':
            $error = "Rak tidak dapat dihapus karena masih digunakan oleh produk atau varian.";
            break;

        case 'tidak_ditemukan':
            $error = "Lokasi / rak tidak ditemukan.";
            break;

        case 'gagal':
            $error = "Lokasi / rak gagal diproses.";
            break;

        case 'kode_digunakan':
            $error = "Kode rak tersebut sudah digunakan.";
            break;
    }
}

/* =========================================================
   PENCARIAN LOKASI / RAK
========================================================= */

$where_search = "";

if ($search !== '') {
    $where_search = "WHERE (
        lr.kode_rak LIKE '%$search_escaped%'
        OR lr.nama_lokasi LIKE '%$search_escaped%'
        OR lr.keterangan LIKE '%$search_escaped%'
    )";
}

/* =========================================================
   DATA LOKASI / RAK
   Hitung produk yang memakai rak melalui:
   - Lokasi pada produk utama
   - Lokasi pada varian produk
========================================================= */

$query = mysqli_query(
    $conn,
    "SELECT
        lr.id_lokasi,
        lr.kode_rak,
        lr.nama_lokasi,
        lr.keterangan,
        lr.created_at,
        (
            SELECT COUNT(DISTINCT p.id_produk)
            FROM produk p
            WHERE
                p.lokasi_rak = lr.kode_rak
                OR EXISTS (
                    SELECT 1
                    FROM produk_varian pv
                    WHERE
                        pv.id_produk = p.id_produk
                        AND pv.lokasi_rak = lr.kode_rak
                )
        ) AS jumlah_produk
     FROM lokasi_rak lr
     $where_search
     ORDER BY lr.kode_rak ASC"
);

$data_lokasi = [];

if ($query) {
    while ($row = mysqli_fetch_assoc($query)) {
        $data_lokasi[] = $row;
    }
}

/* =========================================================
   BANGUN BARIS TABEL
========================================================= */

ob_start();

if (empty($data_lokasi)):
?>

<tr>
    <td colspan="6" class="lokasi-empty">
        <div class="lokasi-empty-icon">⌖</div>

        <?= $search !== ''
            ? 'Lokasi / rak yang dicari tidak ditemukan.'
            : 'Belum ada lokasi / rak.'; ?>
    </td>
</tr>

<?php else: ?>

    <?php $no = 1; ?>

    <?php foreach ($data_lokasi as $lokasi): ?>

        <?php
        $id_lokasi = (int)$lokasi['id_lokasi'];
        $jumlah_produk = (int)$lokasi['jumlah_produk'];
        ?>

        <tr>
            <td>
                <span class="lokasi-no">
                    <?= $no++; ?>
                </span>
            </td>

            <td>
                <span class="kode-rak">
                    <?= htmlspecialchars(
                        $lokasi['kode_rak'],
                        ENT_QUOTES,
                        'UTF-8'
                    ); ?>
                </span>
            </td>

            <td>
                <span class="nama-lokasi">
                    <?= htmlspecialchars(
                        $lokasi['nama_lokasi'],
                        ENT_QUOTES,
                        'UTF-8'
                    ); ?>
                </span>
            </td>

            <td>
                <span class="keterangan-lokasi">
                    <?= !empty($lokasi['keterangan'])
                        ? htmlspecialchars(
                            $lokasi['keterangan'],
                            ENT_QUOTES,
                            'UTF-8'
                        )
                        : '-'; ?>
                </span>
            </td>

            <td>
                <span class="jumlah-produk">
                    <?= $jumlah_produk; ?> Produk
                </span>
            </td>

            <td>
                <div class="lokasi-actions">

                    <a
                        href="edit.php?id=<?= $id_lokasi; ?>"
                        class="btn-edit-lokasi"
                    >
                        Edit
                    </a>

                    <?php if ($jumlah_produk === 0): ?>

                        <form
                            method="POST"
                            action="hapus.php"
                            class="form-hapus-lokasi"
                            onsubmit="return konfirmasiHapusRak(this);"
                        >
                            <input
                                type="hidden"
                                name="id_lokasi"
                                value="<?= $id_lokasi; ?>"
                            >

                            <button
                                type="submit"
                                name="hapus_lokasi"
                                value="1"
                                class="btn-hapus-lokasi"
                            >
                                Hapus
                            </button>
                        </form>

                    <?php else: ?>

                        <span class="lokasi-digunakan">
                            Digunakan
                        </span>

                    <?php endif; ?>

                </div>
            </td>
        </tr>

    <?php endforeach; ?>

<?php endif; ?>

<?php

$rows_html = ob_get_clean();
$rows_hash = hash('sha256', $rows_html);

/* =========================================================
   MODE SINKRONISASI JSON
   Hanya mengirim data tabel.
   Navbar dan sidebar tidak dimuat ulang.
========================================================= */

if (isset($_GET['_live_lokasi'])) {
    header("Content-Type: application/json; charset=UTF-8");
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Pragma: no-cache");

    echo json_encode([
        'success' => true,
        'rows_html' => $rows_html,
        'rows_hash' => $rows_hash
    ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);

    exit;
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

<title>Lokasi / Rak - Toko ATK &amp; Alat Jahit</title>

<link
    rel="stylesheet"
    href="../assets/css/admin.css"
>

<style>

.lokasi-page{width:100%}

.lokasi-page-header{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:20px;
    margin-bottom:28px
}

.lokasi-page-header h1{
    margin:0 0 7px;
    color:#102f4b;
    font-size:28px;
    font-weight:700
}

.lokasi-page-header p{
    margin:0;
    color:#7890a4;
    font-size:14px
}

.lokasi-header-actions{
    display:flex;
    align-items:center;
    gap:10px;
    flex-wrap:wrap
}

.lokasi-search-form{
    display:flex;
    align-items:center;
    gap:7px
}

.lokasi-search-input{
    width:270px;
    height:42px;
    padding:0 13px;
    border:1px solid #dce5ec;
    border-radius:8px;
    outline:none;
    color:#334f66;
    font-size:13px;
    background:#fff;
    box-sizing:border-box
}

.lokasi-search-input:focus{
    border-color:#315b78;
    box-shadow:0 0 0 3px rgba(49,91,120,.08)
}

.btn-search-lokasi,
.btn-clear-lokasi{
    height:42px;
    padding:0 13px;
    border-radius:8px;
    border:none;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    font-size:12px;
    font-weight:700;
    text-decoration:none;
    cursor:pointer;
    box-sizing:border-box
}

.btn-search-lokasi{
    background:#102f4b;
    color:#fff
}

.btn-search-lokasi:hover{
    background:#1c496b
}

.btn-clear-lokasi{
    background:#eef3f7;
    color:#506d84
}

.btn-clear-lokasi:hover{
    background:#e3ebf0
}

.btn-tambah-lokasi{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:7px;
    height:42px;
    padding:0 17px;
    border-radius:8px;
    background:#102f4b;
    color:#fff;
    font-size:13px;
    font-weight:600;
    text-decoration:none;
    white-space:nowrap;
    transition:.2s
}

.btn-tambah-lokasi:hover{
    background:#1c496b;
    transform:translateY(-1px)
}

.lokasi-alert{
    padding:13px 16px;
    border-radius:9px;
    margin-bottom:22px;
    font-size:13px;
    opacity:1;
    transform:translateY(0);
    transition:opacity .35s ease, transform .35s ease
}

.lokasi-alert.is-hiding{
    opacity:0;
    transform:translateY(-8px)
}

.lokasi-alert.success{
    background:#eaf7ee;
    border:1px solid #b9dfc5;
    color:#26733d
}

.lokasi-alert.error{
    background:#fff0f0;
    border:1px solid #f0c2c2;
    color:#b52b2b
}

.lokasi-card{
    background:#fff;
    border:1px solid #e0e7ed;
    border-radius:14px;
    overflow:hidden;
    box-shadow:0 4px 15px rgba(16,47,75,.04)
}

.lokasi-card-header{
    padding:20px 24px;
    border-bottom:1px solid #e6edf2;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:20px;
    flex-wrap:wrap
}

.lokasi-card-header h2{
    margin:0 0 5px;
    color:#102f4b;
    font-size:18px;
    font-weight:600
}

.lokasi-card-header p{
    margin:0;
    color:#7890a4;
    font-size:13px
}

.lokasi-search-info{
    padding:13px 24px;
    background:#f8fafc;
    border-bottom:1px solid #e6edf2;
    color:#7890a4;
    font-size:12px
}

.lokasi-search-info strong{
    color:#102f4b
}

.lokasi-table-wrapper{
    width:100%;
    overflow-x:auto
}

.lokasi-table{
    width:100%;
    min-width:850px;
    border-collapse:collapse
}

.lokasi-table th{
    padding:13px 18px;
    background:#f7f9fb;
    border-bottom:1px solid #e3e9ee;
    color:#7890a4;
    text-align:left;
    font-size:11px;
    font-weight:bold;
    text-transform:uppercase;
    letter-spacing:.4px;
    white-space:nowrap
}

.lokasi-table td{
    padding:15px 18px;
    border-bottom:1px solid #edf1f4;
    color:#405b70;
    font-size:13px;
    vertical-align:middle
}

.lokasi-table tbody tr:last-child td{
    border-bottom:none
}

.lokasi-table tbody tr:hover{
    background:#fafcfd
}

.lokasi-no{
    color:#7890a4;
    font-weight:600
}

.kode-rak{
    display:inline-flex;
    align-items:center;
    padding:5px 10px;
    background:#edf3f7;
    color:#245b83;
    border-radius:6px;
    font-size:12px;
    font-weight:700
}

.nama-lokasi{
    color:#102f4b;
    font-weight:600
}

.keterangan-lokasi{
    color:#7890a4;
    line-height:1.5
}

.jumlah-produk{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    min-width:70px;
    padding:5px 10px;
    background:#edf3f7;
    color:#315b78;
    border-radius:20px;
    font-size:12px;
    font-weight:600
}

.lokasi-actions{
    display:flex;
    align-items:center;
    gap:7px;
    white-space:nowrap
}

.form-hapus-lokasi{
    display:inline
}

.btn-edit-lokasi{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    height:32px;
    padding:0 11px;
    background:#eef4f8;
    border:1px solid #d6e2ea;
    border-radius:7px;
    color:#245b83;
    font-size:12px;
    font-weight:600;
    text-decoration:none;
    transition:.2s
}

.btn-edit-lokasi:hover{
    background:#e1ebf1
}

.btn-hapus-lokasi{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    height:32px;
    padding:0 11px;
    background:#fff0f0;
    border:1px solid #f3d0d0;
    border-radius:7px;
    color:#c62828;
    font-family:inherit;
    font-size:12px;
    font-weight:600;
    cursor:pointer;
    transition:.2s
}

.btn-hapus-lokasi:hover{
    background:#ffe1e1
}

.lokasi-digunakan{
    color:#9aa9b7;
    font-size:12px
}

.lokasi-empty{
    padding:55px 20px!important;
    text-align:center;
    color:#8a9dac!important
}

.lokasi-empty-icon{
    margin-bottom:10px;
    color:#b5c4cf;
    font-size:30px
}

@media(max-width:1000px){

    .lokasi-page-header{
        align-items:stretch
    }

    .lokasi-header-actions{
        width:100%;
        flex-direction:column;
        align-items:stretch
    }

    .lokasi-search-form{
        width:100%
    }

    .lokasi-search-input{
        flex:1;
        width:auto
    }

}

@media(max-width:700px){

    .lokasi-page-header{
        align-items:stretch;
        flex-direction:column
    }

    .lokasi-search-form{
        flex-wrap:wrap
    }

    .lokasi-search-input{
        width:100%;
        flex:none
    }

    .btn-search-lokasi,
    .btn-clear-lokasi{
        flex:1
    }

    .btn-tambah-lokasi{
        width:100%
    }

    .lokasi-card-header{
        padding:18px
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

            <div class="lokasi-page">

                <div class="lokasi-page-header">

                    <div>
                        <h1>Lokasi / Rak</h1>
                        <p>Kelola lokasi penyimpanan dan rak produk toko.</p>
                    </div>

                    <div class="lokasi-header-actions">

                        <form
                            method="GET"
                            action="index.php"
                            class="lokasi-search-form"
                        >

                            <input
                                type="text"
                                name="search"
                                class="lokasi-search-input"
                                placeholder="Cari kode, lokasi, keterangan..."
                                value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>"
                            >

                            <button
                                type="submit"
                                class="btn-search-lokasi"
                            >
                                Cari
                            </button>

                            <?php if ($search !== ''): ?>

                                <a
                                    href="index.php"
                                    class="btn-clear-lokasi"
                                >
                                    Reset
                                </a>

                            <?php endif; ?>

                        </form>

                        <a
                            href="tambah.php"
                            class="btn-tambah-lokasi"
                        >
                            <span>+</span>
                            Tambah Rak
                        </a>

                    </div>

                </div>

                <?php if ($pesan !== ""): ?>

                    <div class="lokasi-alert success">
                        <?= htmlspecialchars($pesan, ENT_QUOTES, 'UTF-8'); ?>
                    </div>

                <?php endif; ?>

                <?php if ($error !== ""): ?>

                    <div class="lokasi-alert error">
                        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                    </div>

                <?php endif; ?>

                <div class="lokasi-card">

                    <div class="lokasi-card-header">

                        <div>
                            <h2>Daftar Lokasi / Rak</h2>
                            <p>Daftar rak yang digunakan untuk menyimpan produk.</p>
                        </div>

                    </div>

                    <?php if ($search !== ''): ?>

                        <div class="lokasi-search-info">
                            Hasil pencarian untuk
                            <strong>
                                "<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>"
                            </strong>
                        </div>

                    <?php endif; ?>

                    <div class="lokasi-table-wrapper">

                        <table class="lokasi-table">

                            <thead>
                                <tr>
                                    <th width="65">No</th>
                                    <th width="130">Kode Rak</th>
                                    <th>Nama Lokasi</th>
                                    <th>Keterangan</th>
                                    <th width="150">Jumlah Produk</th>
                                    <th width="170">Aksi</th>
                                </tr>
                            </thead>

                            <tbody id="lokasiTableBody">
                                <?= $rows_html; ?>
                            </tbody>

                        </table>

                    </div>

                </div>

            </div>

        </main>

        <?php include "../includes/footer.php"; ?>

    </div>

</div>

<script src="../assets/js/admin.js"></script>

<script>
/* =========================================================
   KONFIRMASI HAPUS RAK
========================================================= */

function konfirmasiHapusRak(form) {
    const inputId = form.querySelector('input[name="id_lokasi"]');

    if (!inputId) {
        return false;
    }

    const baris = form.closest("tr");
    const kodeRak = baris
        ? (baris.querySelector(".kode-rak")?.textContent.trim() || "")
        : "";

    return confirm(
        'Yakin ingin menghapus rak ' + kodeRak + '?'
    );
}

/* =========================================================
   NOTIFIKASI OTOMATIS HILANG SETELAH 10 DETIK
========================================================= */

(function () {
    const notifikasi = document.querySelectorAll(".lokasi-alert");

    notifikasi.forEach(function (elemen) {
        setTimeout(function () {
            elemen.classList.add("is-hiding");

            setTimeout(function () {
                elemen.remove();
            }, 400);
        }, 10000);
    });

    /* Hilangkan status dari URL agar notifikasi tidak muncul lagi
       saat halaman dimuat ulang secara manual. */
    try {
        const url = new URL(window.location.href);

        url.searchParams.delete("status");

        window.history.replaceState(
            {},
            document.title,
            url.pathname + url.search + url.hash
        );
    } catch (error) {
        console.warn("Status URL tidak dapat dibersihkan:", error);
    }
})();

/* =========================================================
   SINKRONISASI DATA LOKASI / RAK
   Interval 4 detik, tanpa reload navbar dan sidebar.
========================================================= */

(function () {
    const tbody = document.getElementById("lokasiTableBody");

    if (!tbody) {
        return;
    }

    let sedangMemuat = false;

    let hashBarisTerakhir = <?= json_encode($rows_hash); ?>;

    async function sinkronLokasi() {
        if (
            sedangMemuat ||
            document.visibilityState !== "visible"
        ) {
            return;
        }

        sedangMemuat = true;

        try {
            const url = new URL(window.location.href);

            url.searchParams.set("_live_lokasi", "1");
            url.searchParams.set("_", Date.now().toString());

            const response = await fetch(url.toString(), {
                method: "GET",
                credentials: "same-origin",
                cache: "no-store",
                headers: {
                    "Accept": "application/json",
                    "X-Requested-With": "XMLHttpRequest"
                }
            });

            if (!response.ok) {
                return;
            }

            const contentType =
                response.headers.get("content-type") || "";

            if (!contentType.includes("application/json")) {
                return;
            }

            const data = await response.json();

            if (
                !data.success ||
                typeof data.rows_html !== "string" ||
                !data.rows_hash
            ) {
                return;
            }

            if (data.rows_hash !== hashBarisTerakhir) {
                const wrapper = tbody.closest(".lokasi-table-wrapper");

                const posisiScroll = wrapper
                    ? wrapper.scrollLeft
                    : 0;

                tbody.innerHTML = data.rows_html;

                hashBarisTerakhir = data.rows_hash;

                if (wrapper) {
                    wrapper.scrollLeft = posisiScroll;
                }
            }

        } catch (error) {
            console.error(
                "Gagal menyinkronkan lokasi / rak:",
                error
            );

        } finally {
            sedangMemuat = false;
        }
    }

    setInterval(sinkronLokasi, 4000);

})();

</script>

</body>
</html>