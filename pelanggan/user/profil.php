<?php
session_start();
if (!isset($_SESSION["id_pelanggan"])) {
    header("Location: ../login.php");
    exit;
}
require_once __DIR__ . "/../../config/koneksi.php";
$id_pelanggan = (int)$_SESSION["id_pelanggan"];
if (empty($_SESSION["csrf_profil"])) {
    $_SESSION["csrf_profil"] = bin2hex(random_bytes(32));
}
$pesan = "";
$jenis_pesan = "";
$folder_foto = __DIR__ . "/../assets/images/profil/";
$url_foto = "/project_atk_jahit/pelanggan/assets/images/profil/";
if (!is_dir($folder_foto)) {
    mkdir($folder_foto, 0755, true);
}

/* PROSES UPLOAD DAN HAPUS FOTO */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $csrf = $_POST["csrf_profil"] ?? "";
    if (!hash_equals($_SESSION["csrf_profil"], $csrf)) {
        $pesan = "Permintaan tidak valid. Silakan muat ulang halaman.";
        $jenis_pesan = "error";
    } else {
        $aksi = $_POST["aksi"] ?? "upload";

        /* HAPUS FOTO */
        if ($aksi === "hapus") {
            $stmt = mysqli_prepare($conn, "SELECT foto_profil FROM pelanggan WHERE id_pelanggan = ? LIMIT 1");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "i", $id_pelanggan);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $data_foto = mysqli_fetch_assoc($result);
                mysqli_stmt_close($stmt);

                $stmt_update = mysqli_prepare($conn, "UPDATE pelanggan SET foto_profil = NULL WHERE id_pelanggan = ?");
                if ($stmt_update) {
                    mysqli_stmt_bind_param($stmt_update, "i", $id_pelanggan);
                    $berhasil = mysqli_stmt_execute($stmt_update);
                    mysqli_stmt_close($stmt_update);

                    if ($berhasil) {
                        if (!empty($data_foto["foto_profil"])) {
                            $file_lama = $folder_foto . basename($data_foto["foto_profil"]);
                            if (is_file($file_lama)) {
                                unlink($file_lama);
                            }
                        }
                        $_SESSION["foto_pelanggan"] = "";
                        $pesan = "Foto profil berhasil dihapus.";
                        $jenis_pesan = "success";
                    } else {
                        $pesan = "Foto profil gagal dihapus.";
                        $jenis_pesan = "error";
                    }
                } else {
                    $pesan = "Tidak dapat memproses penghapusan foto.";
                    $jenis_pesan = "error";
                }
            } else {
                $pesan = "Tidak dapat mengambil data foto profil.";
                $jenis_pesan = "error";
            }
        } elseif (isset($_FILES["foto_profil"])) {
            /* UPLOAD ATAU GANTI FOTO */
            $file = $_FILES["foto_profil"];

            if ($file["error"] === UPLOAD_ERR_NO_FILE) {
                $pesan = "Silakan pilih foto terlebih dahulu.";
                $jenis_pesan = "error";
            } elseif ($file["error"] !== UPLOAD_ERR_OK) {
                $pesan = "Foto gagal diupload.";
                $jenis_pesan = "error";
            } elseif ($file["size"] > 2 * 1024 * 1024) {
                $pesan = "Ukuran foto maksimal 2 MB.";
                $jenis_pesan = "error";
            } else {
                $info_gambar = @getimagesize($file["tmp_name"]);
                $tipe_diizinkan = [
                    "image/jpeg" => "jpg",
                    "image/png" => "png",
                    "image/webp" => "webp"
                ];

                if ($info_gambar === false || !isset($tipe_diizinkan[$info_gambar["mime"]])) {
                    $pesan = "Format foto hanya JPG, PNG, atau WEBP.";
                    $jenis_pesan = "error";
                } else {
                    $stmt = mysqli_prepare($conn, "SELECT foto_profil FROM pelanggan WHERE id_pelanggan = ? LIMIT 1");
                    $data_lama = null;

                    if ($stmt) {
                        mysqli_stmt_bind_param($stmt, "i", $id_pelanggan);
                        mysqli_stmt_execute($stmt);
                        $result = mysqli_stmt_get_result($stmt);
                        $data_lama = mysqli_fetch_assoc($result);
                        mysqli_stmt_close($stmt);
                    }

                    if (!$data_lama) {
                        $pesan = "Data pelanggan tidak ditemukan.";
                        $jenis_pesan = "error";
                    } else {
                        $ekstensi = $tipe_diizinkan[$info_gambar["mime"]];
                        $nama_file = "profil_" . $id_pelanggan . "_" . bin2hex(random_bytes(8)) . "." . $ekstensi;
                        $tujuan = $folder_foto . $nama_file;

                        if (move_uploaded_file($file["tmp_name"], $tujuan)) {
                            $stmt_update = mysqli_prepare($conn, "UPDATE pelanggan SET foto_profil = ? WHERE id_pelanggan = ?");
                            $berhasil = false;

                            if ($stmt_update) {
                                mysqli_stmt_bind_param($stmt_update, "si", $nama_file, $id_pelanggan);
                                $berhasil = mysqli_stmt_execute($stmt_update);
                                mysqli_stmt_close($stmt_update);
                            }

                            if ($berhasil) {
                                if (!empty($data_lama["foto_profil"])) {
                                    $file_lama = $folder_foto . basename($data_lama["foto_profil"]);
                                    if (is_file($file_lama)) {
                                        unlink($file_lama);
                                    }
                                }
                                $_SESSION["foto_pelanggan"] = $nama_file;
                                $pesan = "Foto profil berhasil diperbarui.";
                                $jenis_pesan = "success";
                            } else {
                                if (is_file($tujuan)) {
                                    unlink($tujuan);
                                }
                                $pesan = "Foto gagal disimpan ke database.";
                                $jenis_pesan = "error";
                            }
                        } else {
                            $pesan = "Foto gagal disimpan ke server.";
                            $jenis_pesan = "error";
                        }
                    }
                }
            }
        }
    }
}

/* AMBIL DATA PELANGGAN */
$stmt = mysqli_prepare($conn, "SELECT id_pelanggan, nama, email, foto_profil, created_at FROM pelanggan WHERE id_pelanggan = ? LIMIT 1");
if (!$stmt) {
    die("Gagal mengambil data pelanggan.");
}
mysqli_stmt_bind_param($stmt, "i", $id_pelanggan);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$pelanggan = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$pelanggan) {
    session_unset();
    session_destroy();
    header("Location: ../login.php");
    exit;
}

$nama = trim($pelanggan["nama"] ?? "Pelanggan");
$inisial = strtoupper(substr($nama, 0, 1));
$foto_profil = trim($pelanggan["foto_profil"] ?? "");
$foto_url = $foto_profil !== "" ? $url_foto . rawurlencode($foto_profil) : "";
$_SESSION["nama_pelanggan"] = $nama;
$_SESSION["foto_pelanggan"] = $foto_profil;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<meta name="theme-color" content="#102a43">
<title>Profil Pelanggan | Toko ATK &amp; Alat Jahit</title>
<style>
*{box-sizing:border-box}
html,body{margin:0;padding:0;width:100%;max-width:100%;overflow-x:hidden}
body{min-width:0}
.user-welcome{width:100%;max-width:1200px;margin:0 auto;padding:38px 20px 60px}
.profile-card{width:100%;max-width:800px;margin:30px auto 0;background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:30px}
.profile-header{display:flex;align-items:center;gap:22px;padding-bottom:25px;border-bottom:1px solid #e5e7eb}
.profile-photo-area{display:flex;flex-direction:column;align-items:center;gap:12px;flex-shrink:0;max-width:100%}
.profile-avatar,.profile-photo{width:96px;height:96px;border-radius:50%}
.profile-avatar{display:flex;align-items:center;justify-content:center;background:#102a43;color:#fff;font-size:30px;font-weight:700}
.profile-photo{object-fit:cover;border:3px solid #e5e7eb}
.profile-photo-actions{display:flex;flex-direction:column;align-items:center;gap:10px;width:100%}
.profile-upload-form,.profile-delete-form{width:175px;max-width:100%;margin:0}
.profile-upload-button,.profile-save-photo,.btn-hapus-foto{display:flex;align-items:center;justify-content:center;width:100%;min-height:37px;padding:9px 12px;border-radius:7px;font-family:inherit;font-size:12px;font-weight:600;text-align:center;cursor:pointer;transition:background .2s,border-color .2s}
.profile-upload-button{background:#102a43;color:#fff;border:1px solid #102a43}
.profile-upload-button:hover{background:#183b56}
.profile-upload-button.is-loading{opacity:.7;pointer-events:none}
.profile-upload-button input{display:none}
.btn-hapus-foto{background:#fff;color:#c53030;border:1px solid #e5b5b5}
.btn-hapus-foto:hover{background:#fff5f5;border-color:#c53030}
.photo-hint{margin:0;color:#829ab1;text-align:center;font-size:10px;line-height:1.6}
.profile-header-text{min-width:0}
.profile-header-text h2{color:#102a43;margin:0 0 6px;font-size:24px;overflow-wrap:anywhere}
.profile-header-text p{color:#627d98;font-size:13px;line-height:1.6;margin:0}
.profile-info{margin-top:25px}
.profile-item{display:flex;justify-content:space-between;align-items:center;gap:20px;padding:16px 0;border-bottom:1px solid #edf1f5}
.profile-item span{color:#627d98;font-size:13px}
.profile-item strong{color:#102a43;font-size:14px;text-align:right;overflow-wrap:anywhere;word-break:break-word}
.profile-actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:28px}
.profile-actions a{display:inline-flex;align-items:center;justify-content:center;min-height:42px;padding:11px 17px;text-align:center;text-decoration:none;border-radius:8px;font-size:13px;font-weight:700}
.profile-actions .btn-primary{background:#102a43;color:#fff}
.profile-actions .btn-outline{border:1px solid #102a43;color:#102a43;background:#fff}
.profile-actions .btn-primary:hover{background:#183b56}
.profile-actions .btn-outline:hover{background:#f2f5f8}
.profil-alert{width:100%;max-width:800px;margin:20px auto 0;padding:13px 15px;border-radius:8px;font-size:13px;line-height:1.6;overflow-wrap:anywhere}
.profil-alert-success{background:#eefbf3;border:1px solid #c9ecd8;color:#26734d}
.profil-alert-error{background:#fff0f0;border:1px solid #f3caca;color:#c53030}
@media(max-width:700px){
    .user-welcome{padding:28px 15px 45px}
    .profile-card{padding:23px;margin-top:22px}
    .profile-header{gap:18px}
    .profile-avatar,.profile-photo{width:85px;height:85px}
    .profile-header-text h2{font-size:21px}
}
@media(max-width:600px){
    .user-welcome{padding:25px 14px 38px}
    .profile-card{padding:23px 17px;border-radius:12px}
    .profile-header{align-items:center;flex-direction:column;text-align:center;gap:18px}
    .profile-photo-area{width:100%;gap:12px}
    .profile-avatar,.profile-photo{width:84px;height:84px}
    .profile-photo-actions{gap:11px;margin-top:2px}
    .profile-upload-form,.profile-delete-form{width:100%;max-width:190px}
    .profile-upload-button,.btn-hapus-foto{min-height:38px;padding:9px 12px;font-size:12px}
    .photo-hint{font-size:10px;margin-top:1px}
    .profile-header-text{width:100%}
    .profile-header-text h2{font-size:20px}
    .profile-header-text p{font-size:12px}
    .profile-item{display:block;padding:14px 0}
    .profile-item span{display:block;margin-bottom:6px}
    .profile-item strong{display:block;text-align:left;font-size:14px}
    .profile-actions{flex-direction:column;gap:10px;margin-top:22px}
    .profile-actions a{width:100%;min-height:42px}
    .profil-alert{margin-top:16px;font-size:12px}
}
@media(max-width:360px){
    .profile-card{padding:20px 14px}
    .profile-upload-form,.profile-delete-form{max-width:175px}
    .profile-header-text h2{font-size:19px}
}
</style>
</head>
<body>
<?php require_once __DIR__ . "/includes/navbar.php"; ?>
<main>
<section class="section user-welcome">
    <span class="eyebrow">AKUN SAYA</span>
    <h1>Profil Pelanggan</h1>
    <p class="muted">Kelola informasi akun pelanggan kamu.</p>

    <?php if ($pesan !== ""): ?>
        <div class="profil-alert <?= $jenis_pesan === "success" ? "profil-alert-success" : "profil-alert-error"; ?>"><?= htmlspecialchars($pesan, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <div class="profile-card">
        <div class="profile-header">
            <div class="profile-photo-area">
                <?php if ($foto_url !== ""): ?>
                    <img src="<?= htmlspecialchars($foto_url, ENT_QUOTES, 'UTF-8'); ?>" alt="Foto Profil" class="profile-photo">
                <?php else: ?>
                    <div class="profile-avatar"><?= htmlspecialchars($inisial, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>

                <div class="profile-photo-actions">
                    <form method="POST" enctype="multipart/form-data" class="profile-upload-form" id="profileUploadForm">
                        <input type="hidden" name="csrf_profil" value="<?= htmlspecialchars($_SESSION["csrf_profil"], ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="aksi" value="upload">
                        <label for="foto_profil" class="profile-upload-button" id="profileUploadLabel">
                            <?= $foto_url !== "" ? "Ganti Foto" : "Upload Foto"; ?>
                            <input type="file" id="foto_profil" name="foto_profil" accept="image/jpeg,image/png,image/webp">
                        </label>
                    </form>

                    <?php if ($foto_url !== ""): ?>
                        <form method="POST" class="profile-delete-form" id="profileDeleteForm">
                            <input type="hidden" name="csrf_profil" value="<?= htmlspecialchars($_SESSION["csrf_profil"], ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="aksi" value="hapus">
                            <button type="submit" class="btn-hapus-foto" onclick="return confirm('Hapus foto profil?');">Hapus Foto</button>
                        </form>
                    <?php endif; ?>
                </div>
                <p class="photo-hint">JPG, PNG, atau WEBP. Maksimal 2 MB.</p>
            </div>

            <div class="profile-header-text">
                <h2><?= htmlspecialchars($nama, ENT_QUOTES, 'UTF-8'); ?></h2>
                <p>Pelanggan Toko ATK &amp; Alat Jahit</p>
            </div>
        </div>

        <div class="profile-info">
            <div class="profile-item"><span>Nama</span><strong><?= htmlspecialchars($pelanggan["nama"] ?? "-", ENT_QUOTES, 'UTF-8'); ?></strong></div>
            <div class="profile-item"><span>Email</span><strong><?= htmlspecialchars($pelanggan["email"] ?? "-", ENT_QUOTES, 'UTF-8'); ?></strong></div>
            <div class="profile-item"><span>Terdaftar Sejak</span><strong><?= !empty($pelanggan["created_at"]) ? date("d-m-Y", strtotime($pelanggan["created_at"])) : "-"; ?></strong></div>
        </div>

        <div class="profile-actions">
            <a href="ubah_password.php" class="btn-primary">Ubah Password</a>
            <a href="../pages/katalog.php" class="btn-outline">Belanja Produk</a>
        </div>
    </div>
</section>
</main>

<script>
const fotoProfil = document.getElementById("foto_profil");
const uploadForm = document.getElementById("profileUploadForm");
const uploadLabel = document.getElementById("profileUploadLabel");

if (fotoProfil && uploadForm && uploadLabel) {
    fotoProfil.addEventListener("change", function () {
        if (!this.files || this.files.length === 0) return;

        const file = this.files[0];
        const tipeDiizinkan = ["image/jpeg", "image/png", "image/webp"];

        if (!tipeDiizinkan.includes(file.type)) {
            alert("Format foto hanya JPG, PNG, atau WEBP.");
            this.value = "";
            return;
        }

        if (file.size > 2 * 1024 * 1024) {
            alert("Ukuran foto maksimal 2 MB.");
            this.value = "";
            return;
        }

        uploadLabel.childNodes[0].textContent = "Menyimpan foto...";
        uploadLabel.classList.add("is-loading");
        uploadForm.submit();
    });
}
</script>

<?php require_once __DIR__ . "/includes/footer.php"; ?>
</body>
</html>