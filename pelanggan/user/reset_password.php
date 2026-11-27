<?php
session_start();
require_once __DIR__ . "/../../config/koneksi.php";

$base_url="/project_atk_jahit";
$token=trim($_GET["token"]??$_POST["token"]??"");

if($token===""){
    header("Location: lupa_password.php");
    exit;
}

$error="";
$success=false;
$token_data=null;

$stmt=mysqli_prepare(
    $conn,
    "SELECT id_reset,id_pelanggan,token,expires_at,used_at
     FROM password_reset_token WHERE token=? LIMIT 1"
);

if(!$stmt){
    die("Gagal memeriksa token reset password.");
}

mysqli_stmt_bind_param($stmt,"s",$token);
mysqli_stmt_execute($stmt);
$result=mysqli_stmt_get_result($stmt);
$token_data=mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if(!$token_data){
    header("Location: lupa_password.php");
    exit;
}

$token_valid=empty($token_data["used_at"])&&strtotime($token_data["expires_at"])>time();

if(!$token_valid){
    $error=!empty($token_data["used_at"])
        ?"Link reset password sudah digunakan."
        :"Link reset password sudah kedaluwarsa.";
}

if($_SERVER["REQUEST_METHOD"]==="POST"&&$token_valid){
    $password_baru=$_POST["password_baru"]??"";
    $konfirmasi_password=$_POST["konfirmasi_password"]??"";

    if($password_baru===""||$konfirmasi_password===""){
        $error="Password baru dan konfirmasi password wajib diisi.";
    }elseif(strlen($password_baru)<6){
        $error="Password baru minimal 6 karakter.";
    }elseif($password_baru!==$konfirmasi_password){
        $error="Konfirmasi password tidak cocok.";
    }else{
        $password_hash=password_hash($password_baru,PASSWORD_DEFAULT);

        try{
            mysqli_begin_transaction($conn);

            $stmt=mysqli_prepare(
                $conn,
                "SELECT id_reset,id_pelanggan,expires_at,used_at
                 FROM password_reset_token
                 WHERE token=? LIMIT 1 FOR UPDATE"
            );

            if(!$stmt)throw new Exception("Gagal memeriksa token.");

            mysqli_stmt_bind_param($stmt,"s",$token);
            mysqli_stmt_execute($stmt);
            $result=mysqli_stmt_get_result($stmt);
            $terkunci=mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);

            if(!$terkunci||!empty($terkunci["used_at"])||strtotime($terkunci["expires_at"])<=time()){
                throw new Exception("Link reset password sudah tidak berlaku.");
            }

            $stmt=mysqli_prepare(
                $conn,
                "UPDATE pelanggan SET password=? WHERE id_pelanggan=?"
            );

            if(!$stmt)throw new Exception("Gagal menyiapkan perubahan password.");

            mysqli_stmt_bind_param($stmt,"si",$password_hash,$terkunci["id_pelanggan"]);

            if(!mysqli_stmt_execute($stmt)||mysqli_stmt_affected_rows($stmt)!==1){
                mysqli_stmt_close($stmt);
                throw new Exception("Password gagal diperbarui.");
            }

            mysqli_stmt_close($stmt);

            $stmt=mysqli_prepare(
                $conn,
                "UPDATE password_reset_token
                 SET used_at=NOW()
                 WHERE id_reset=? AND used_at IS NULL"
            );

            if(!$stmt)throw new Exception("Gagal menyiapkan perubahan token.");

            mysqli_stmt_bind_param($stmt,"i",$terkunci["id_reset"]);

            if(!mysqli_stmt_execute($stmt)||mysqli_stmt_affected_rows($stmt)!==1){
                mysqli_stmt_close($stmt);
                throw new Exception("Token gagal diperbarui.");
            }

            mysqli_stmt_close($stmt);
            mysqli_commit($conn);

            $success=true;
            $token_valid=false;
            $error="";
        }catch(Throwable $e){
            mysqli_rollback($conn);
            $error=$e->getMessage();
            $token_valid=false;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Toko ATK & Alat Jahit</title>
    <link rel="stylesheet" href="<?= $base_url ?>/pelanggan/assets/css/pelanggan.css">
</head>
<body class="auth-page">
<div class="auth-card reset-auth-card">
    <a class="auth-logo" href="<?= $base_url ?>/pelanggan/index.php">
        <img src="<?= $base_url ?>/pelanggan/assets/images/logo.png" alt="Logo">
    </a>

    <?php if($success): ?>
        <div class="reset-status-icon success-icon">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12.5l4 4L19 6.5"></path></svg>
        </div>
        <h1>Password Berhasil Diubah</h1>
        <p class="muted">Password akun kamu sudah berhasil diperbarui.</p>
        <a href="<?= $base_url ?>/pelanggan/login.php" class="btn-primary full reset-main-button">Login Sekarang</a>

    <?php elseif($token_valid): ?>
        <div class="reset-status-icon">
            <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="10" width="14" height="10" rx="2"></rect><path d="M8 10V7a4 4 0 0 1 8 0v3"></path></svg>
        </div>
        <h1>Reset Password</h1>
        <p class="muted">Buat password baru untuk akun kamu.</p>

        <?php if($error!==""): ?>
            <div class="alert"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" class="reset-form">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

            <label for="resetPassword">Password Baru</label>
            <div class="password-field">
                <input type="password" name="password_baru" id="resetPassword" placeholder="Minimal 6 karakter" minlength="6" autocomplete="new-password" required>
                <button type="button" class="password-toggle" onclick="togglePassword('resetPassword',this)" aria-label="Tampilkan password" aria-pressed="false">
                    <svg class="eye-show" viewBox="0 0 24 24" aria-hidden="true"><path d="M2.5 12s3.5-5.5 9.5-5.5S21.5 12 21.5 12 18 17.5 12 17.5 2.5 12 2.5 12Z"></path><circle cx="12" cy="12" r="2.7"></circle></svg>
                    <svg class="eye-hide" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3l18 18"></path><path d="M10.6 6.7A10.8 10.8 0 0 1 12 6.5c6 0 9.5 5.5 9.5 5.5a18 18 0 0 1-3.2 3.7"></path><path d="M6.7 6.7A18.3 18.3 0 0 0 2.5 12S6 17.5 12 17.5c1.3 0 2.5-.3 3.5-.7"></path></svg>
                </button>
            </div>

            <label for="confirmPassword">Konfirmasi Password Baru</label>
            <div class="password-field">
                <input type="password" name="konfirmasi_password" id="confirmPassword" placeholder="Ulangi password baru" minlength="6" autocomplete="new-password" required>
                <button type="button" class="password-toggle" onclick="togglePassword('confirmPassword',this)" aria-label="Tampilkan password" aria-pressed="false">
                    <svg class="eye-show" viewBox="0 0 24 24" aria-hidden="true"><path d="M2.5 12s3.5-5.5 9.5-5.5S21.5 12 21.5 12 18 17.5 12 17.5 2.5 12 2.5 12Z"></path><circle cx="12" cy="12" r="2.7"></circle></svg>
                    <svg class="eye-hide" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3l18 18"></path><path d="M10.6 6.7A10.8 10.8 0 0 1 12 6.5c6 0 9.5 5.5 9.5 5.5a18 18 0 0 1-3.2 3.7"></path><path d="M6.7 6.7A18.3 18.3 0 0 0 2.5 12S6 17.5 12 17.5c1.3 0 2.5-.3 3.5-.7"></path></svg>
                </button>
            </div>

            <button type="submit" class="btn-primary full reset-main-button">Simpan Password Baru</button>
        </form>

        <a class="back-link" href="<?= $base_url ?>/pelanggan/login.php">← Kembali ke Login</a>

    <?php else: ?>
        <div class="reset-status-icon invalid-icon">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 8v5"></path><path d="M12 16.5h.01"></path><path d="M10.3 3.8 2.7 17a2 2 0 0 0 1.7 3h15.2a2 2 0 0 0 1.7-3L13.7 3.8a2 2 0 0 0-3.4 0Z"></path></svg>
        </div>
        <h1>Link Tidak Berlaku</h1>
        <p class="muted"><?= htmlspecialchars($error ?: "Silakan minta link reset password baru.") ?></p>
        <a href="lupa_password.php" class="btn-primary full reset-main-button">Minta Link Baru</a>
    <?php endif; ?>
</div>

<style>
.reset-auth-card{position:relative}
.reset-status-icon{width:58px;height:58px;margin:8px auto 20px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:#f5ead5;color:#102a43}
.reset-status-icon svg{width:27px;height:27px;fill:none;stroke:currentColor;stroke-width:1.7;stroke-linecap:round;stroke-linejoin:round}
.reset-status-icon.success-icon{background:#e8f5e9;color:#2f855a}
.reset-status-icon.invalid-icon{background:#fff4e5;color:#c97a00}
.reset-form{text-align:left}
.reset-form label{display:block;margin:16px 0 7px;color:#102a43;font-size:13px;font-weight:bold}
.reset-form label:first-of-type{margin-top:0}
.password-field{position:relative;width:100%;margin-bottom:18px}
.password-field input{width:100%;box-sizing:border-box;padding:12px 46px 12px 13px;margin-bottom:0!important}
.password-field input::-ms-reveal,.password-field input::-ms-clear{display:none}
.password-field input::-webkit-credentials-auto-fill-button{visibility:hidden;display:none!important;pointer-events:none}
.password-toggle{position:absolute;top:50%;right:8px;transform:translateY(-50%);width:34px;height:34px;display:flex;align-items:center;justify-content:center;padding:0;border:0;border-radius:6px;background:transparent;color:#829ab1;cursor:pointer}
.password-toggle:hover{background:#f0f4f8;color:#102a43}
.password-toggle:focus-visible{outline:2px solid #c7a56a;outline-offset:1px}
.password-toggle svg{width:19px;height:19px;fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round}
.password-toggle .eye-hide{display:none}
.password-toggle.active .eye-show{display:none}
.password-toggle.active .eye-hide{display:block}
.reset-main-button{display:flex;align-items:center;justify-content:center;text-decoration:none;margin-top:22px}
.reset-auth-card .back-link{display:block;margin-top:20px;text-align:center}
</style>

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