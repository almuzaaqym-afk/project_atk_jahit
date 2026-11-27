<?php
session_start();
if (!isset($_SESSION["id_pelanggan"])) {
    header("Location: ../login.php");
    exit;
}
require_once __DIR__ . "/../../config/koneksi.php";
$id_pelanggan = (int)$_SESSION["id_pelanggan"];
$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $password_lama = $_POST["password_lama"] ?? "";
    $password_baru = $_POST["password_baru"] ?? "";
    $konfirmasi_password = $_POST["konfirmasi_password"] ?? "";

    if ($password_lama === "" || $password_baru === "" || $konfirmasi_password === "") {
        $error = "Semua password wajib diisi.";
    } elseif (strlen($password_baru) < 6) {
        $error = "Password baru minimal 6 karakter.";
    } elseif ($password_baru !== $konfirmasi_password) {
        $error = "Konfirmasi password tidak cocok.";
    } elseif ($password_lama === $password_baru) {
        $error = "Password baru harus berbeda dari password lama.";
    } else {
        $stmt = mysqli_prepare($conn, "SELECT password FROM pelanggan WHERE id_pelanggan = ? LIMIT 1");
        if (!$stmt) {
            $error = "Gagal memeriksa password. Silakan coba lagi.";
        } else {
            mysqli_stmt_bind_param($stmt, "i", $id_pelanggan);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $pelanggan = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);

            if (!$pelanggan) {
                $error = "Data pelanggan tidak ditemukan.";
            } elseif (!password_verify($password_lama, $pelanggan["password"])) {
                $error = "Password lama salah.";
            } else {
                $password_hash = password_hash($password_baru, PASSWORD_DEFAULT);
                $stmt = mysqli_prepare($conn, "UPDATE pelanggan SET password = ? WHERE id_pelanggan = ?");
                if (!$stmt) {
                    $error = "Password gagal diubah. Silakan coba lagi.";
                } else {
                    mysqli_stmt_bind_param($stmt, "si", $password_hash, $id_pelanggan);
                    if (mysqli_stmt_execute($stmt)) {
                        $success = "Password berhasil diubah.";
                    } else {
                        $error = "Password gagal diubah. Silakan coba lagi.";
                    }
                    mysqli_stmt_close($stmt);
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
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<meta name="theme-color" content="#102a43">
<title>Ubah Password | Toko ATK &amp; Alat Jahit</title>
<link rel="stylesheet" href="/project_atk_jahit/pelanggan/assets/css/pelanggan.css">
<style>
*{box-sizing:border-box}
html{width:100%;min-width:0;max-width:100%;overflow-x:clip;-webkit-text-size-adjust:100%}
body{width:100%;min-width:0;max-width:100%;margin:0;overflow-x:clip}
main{width:100%;min-width:0;max-width:100%}
.user-welcome{display:block;width:100%;max-width:1200px;margin:0 auto;padding:38px 20px 60px}
.password-card{display:block;width:100%;max-width:600px;min-width:0;margin:28px auto 0;padding:30px;background:#fff;border:1px solid #e5e7eb;border-radius:14px;box-shadow:0 4px 18px rgba(16,42,67,.04)}
.password-card form{display:block;width:100%;min-width:0;margin:0}
.password-card label{display:block;width:100%;margin:18px 0 8px;color:#102a43;font-size:13px;font-weight:700;line-height:1.5}
.password-card label:first-of-type{margin-top:0}
.password-field{position:relative;display:block;width:100%;min-width:0;margin:0 0 20px}
.password-field input{display:block;width:100%;min-width:0;height:46px;margin:0!important;padding:11px 48px 11px 13px;border:1px solid #d9e2ec;border-radius:8px;outline:none;background:#fff;color:#334e68;font-family:inherit;font-size:16px;line-height:1.4;box-sizing:border-box}
.password-field input:focus{border-color:#102a43;box-shadow:0 0 0 2px rgba(16,42,67,.08)}
.password-field input::-ms-reveal,.password-field input::-ms-clear{display:none}
.password-field input::-webkit-credentials-auto-fill-button{visibility:hidden;display:none!important;pointer-events:none}
.password-toggle{position:absolute;top:50%;right:6px;transform:translateY(-50%);display:flex;align-items:center;justify-content:center;width:36px;height:36px;padding:0;border:0;border-radius:7px;background:transparent;color:#829ab1;cursor:pointer}
.password-toggle:hover{background:#f0f4f8;color:#102a43}
.password-toggle:focus-visible{outline:2px solid #c7a56a;outline-offset:1px}
.password-toggle svg{display:block;width:19px;height:19px;fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round}
.password-toggle .eye-hide{display:none}
.password-toggle.active .eye-show{display:none}
.password-toggle.active .eye-hide{display:block}
.password-actions{display:flex;align-items:stretch;gap:12px;width:100%;min-width:0;margin-top:25px}
.password-actions a,.password-actions button{display:inline-flex;align-items:center;justify-content:center;flex:1;min-width:0;min-height:44px;margin:0;padding:11px 12px;border-radius:8px;font-family:inherit;font-size:13px;font-weight:700;line-height:1.5;text-align:center;text-decoration:none;white-space:normal;overflow-wrap:anywhere;cursor:pointer}
.password-actions button{border:0}
.forgot-password{display:flex;justify-content:center;align-items:center;flex-wrap:wrap;gap:5px 7px;margin-top:22px;padding-top:18px;border-top:1px solid #e5e7eb;font-size:13px;line-height:1.6;text-align:center}
.forgot-password span{color:#627d98}
.forgot-password a{color:#102a43;font-weight:700;text-decoration:none}
.forgot-password a:hover{color:#c7a56a}
.password-card .alert,.password-card .success{width:100%;margin:0 0 18px;padding:12px 14px;border-radius:8px;font-size:13px;line-height:1.6;overflow-wrap:anywhere}
.password-card .alert{background:#fff0f0;border:1px solid #f3caca;color:#c53030}
.password-card .success{background:#eefbf3;border:1px solid #c9ecd8;color:#26734d}
@media(max-width:768px){
    .user-welcome{width:100%;padding:28px 16px 45px}
    .password-card{width:100%;max-width:560px;margin-top:23px;padding:25px 22px}
    .password-actions{gap:10px}
}
@media(max-width:600px){
    .user-welcome{width:100%;padding:24px 15px 38px}
    .password-card{width:100%;max-width:100%;margin:20px 0 0;padding:22px 18px;border-radius:12px;box-shadow:none}
    .password-card label{margin:17px 0 8px;font-size:13px}
    .password-field{margin-bottom:18px}
    .password-field input{width:100%;height:46px;padding:11px 46px 11px 12px;font-size:16px}
    .password-toggle{right:4px;width:36px;height:36px}
    .password-actions{display:flex;flex-direction:column;gap:10px;margin-top:22px}
    .password-actions a,.password-actions button{display:flex;flex:none;width:100%;max-width:100%;min-height:44px;padding:11px 14px;font-size:13px}
    .forgot-password{display:flex;flex-direction:column;gap:4px;margin-top:20px;padding-top:16px;font-size:12px}
    .password-card .alert,.password-card .success{padding:11px 12px;font-size:12px}
}
@media(max-width:380px){
    .user-welcome{padding:22px 12px 32px}
    .password-card{padding:20px 14px}
    .password-card h1{font-size:24px;line-height:1.3;overflow-wrap:anywhere}
    .password-card .muted{font-size:12px;line-height:1.6}
    .password-field input{padding-left:11px;padding-right:44px}
    .password-actions a,.password-actions button{font-size:12px}
}
</style>
</head>
<body>
<?php require_once __DIR__ . "/includes/navbar.php"; ?>
<main>
<section class="section user-welcome">
    <span class="eyebrow">AKUN SAYA</span>
    <h1>Ubah Password</h1>
    <p class="muted">Gunakan password yang kuat dan mudah kamu ingat.</p>

    <div class="password-card">
        <?php if ($error !== ""): ?>
            <div class="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        <?php if ($success !== ""): ?>
            <div class="success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <form method="POST">
            <label for="passwordLama">Password Lama</label>
            <div class="password-field">
                <input type="password" name="password_lama" id="passwordLama" placeholder="Masukkan password lama" autocomplete="current-password" required>
                <button type="button" class="password-toggle" onclick="togglePassword('passwordLama',this)" aria-label="Tampilkan password" aria-pressed="false">
                    <svg class="eye-show" viewBox="0 0 24 24" aria-hidden="true"><path d="M2.5 12s3.5-5.5 9.5-5.5S21.5 12 21.5 12 18 17.5 12 17.5 2.5 12 2.5 12Z"></path><circle cx="12" cy="12" r="2.7"></circle></svg>
                    <svg class="eye-hide" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3l18 18"></path><path d="M10.6 6.7A10.8 10.8 0 0 1 12 6.5c6 0 9.5 5.5 9.5 5.5a18 18 0 0 1-3.2 3.7"></path><path d="M6.7 6.7A18.3 18.3 0 0 0 2.5 12S6 17.5 12 17.5c1.3 0 2.5-.3 3.5-.7"></path></svg>
                </button>
            </div>

            <label for="passwordBaru">Password Baru</label>
            <div class="password-field">
                <input type="password" name="password_baru" id="passwordBaru" placeholder="Minimal 6 karakter" minlength="6" autocomplete="new-password" required>
                <button type="button" class="password-toggle" onclick="togglePassword('passwordBaru',this)" aria-label="Tampilkan password" aria-pressed="false">
                    <svg class="eye-show" viewBox="0 0 24 24" aria-hidden="true"><path d="M2.5 12s3.5-5.5 9.5-5.5S21.5 12 21.5 12 18 17.5 12 17.5 2.5 12 2.5 12Z"></path><circle cx="12" cy="12" r="2.7"></circle></svg>
                    <svg class="eye-hide" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3l18 18"></path><path d="M10.6 6.7A10.8 10.8 0 0 1 12 6.5c6 0 9.5 5.5 9.5 5.5a18 18 0 0 1-3.2 3.7"></path><path d="M6.7 6.7A18.3 18.3 0 0 0 2.5 12S6 17.5 12 17.5c1.3 0 2.5-.3 3.5-.7"></path></svg>
                </button>
            </div>

            <label for="konfirmasiPassword">Konfirmasi Password Baru</label>
            <div class="password-field">
                <input type="password" name="konfirmasi_password" id="konfirmasiPassword" placeholder="Ulangi password baru" minlength="6" autocomplete="new-password" required>
                <button type="button" class="password-toggle" onclick="togglePassword('konfirmasiPassword',this)" aria-label="Tampilkan password" aria-pressed="false">
                    <svg class="eye-show" viewBox="0 0 24 24" aria-hidden="true"><path d="M2.5 12s3.5-5.5 9.5-5.5S21.5 12 21.5 12 18 17.5 12 17.5 2.5 12 2.5 12Z"></path><circle cx="12" cy="12" r="2.7"></circle></svg>
                    <svg class="eye-hide" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3l18 18"></path><path d="M10.6 6.7A10.8 10.8 0 0 1 12 6.5c6 0 9.5 5.5 9.5 5.5a18 18 0 0 1-3.2 3.7"></path><path d="M6.7 6.7A18.3 18.3 0 0 0 2.5 12S6 17.5 12 17.5c1.3 0 2.5-.3 3.5-.7"></path></svg>
                </button>
            </div>

            <div class="password-actions">
                <button type="submit" class="btn-primary">Simpan Password</button>
                <a href="profil.php" class="btn-outline">Kembali ke Profil</a>
            </div>
        </form>

        <div class="forgot-password">
            <span>Lupa password lama?</span>
            <a href="lupa_password.php">Lupa Password?</a>
        </div>
    </div>
</section>
</main>
<?php require_once __DIR__ . "/includes/footer.php"; ?>
<script>
function togglePassword(inputId,button){
    const input=document.getElementById(inputId);
    if(!input)return;
    const tampilkan=input.type==="password";
    input.type=tampilkan?"text":"password";
    button.classList.toggle("active",tampilkan);
    button.setAttribute("aria-label",tampilkan?"Sembunyikan password":"Tampilkan password");
    button.setAttribute("aria-pressed",tampilkan?"true":"false");
}
</script>
</body>
</html>