<?php
session_start();
require_once __DIR__ . "/../config/koneksi.php";

$base_url="/project_atk_jahit";

if(isset($_SESSION["id_pelanggan"])){
    header("Location: {$base_url}/pelanggan/user/index.php");
    exit;
}

$error="";
$success="";
$nama_value="";
$email_value="";

if($_SERVER["REQUEST_METHOD"]==="POST"){
    $nama=trim($_POST["nama"]??"");
    $email=trim($_POST["email"]??"");
    $password=$_POST["password"]??"";

    $nama_value=$nama;
    $email_value=$email;

    if($nama===""||$email===""||$password===""){
        $error="Semua data wajib diisi.";
    }elseif(!filter_var($email,FILTER_VALIDATE_EMAIL)){
        $error="Format email tidak valid.";
    }else{
        $stmt=mysqli_prepare(
            $conn,
            "SELECT id_pelanggan FROM pelanggan WHERE email=? LIMIT 1"
        );

        if(!$stmt){
            $error="Gagal memeriksa email. Silakan coba lagi.";
        }else{
            mysqli_stmt_bind_param($stmt,"s",$email);
            mysqli_stmt_execute($stmt);
            $result=mysqli_stmt_get_result($stmt);
            $email_terdaftar=mysqli_num_rows($result)>0;
            mysqli_stmt_close($stmt);

            if($email_terdaftar){
                $error="Email sudah terdaftar.";
            }else{
                $hash=password_hash($password,PASSWORD_DEFAULT);
                $stmt=mysqli_prepare(
                    $conn,
                    "INSERT INTO pelanggan (nama,email,password) VALUES (?,?,?)"
                );

                if(!$stmt){
                    $error="Pendaftaran gagal. Silakan coba lagi.";
                }else{
                    mysqli_stmt_bind_param($stmt,"sss",$nama,$email,$hash);

                    if(mysqli_stmt_execute($stmt)){
                        $success="Pendaftaran berhasil. Silakan login.";
                        $nama_value="";
                        $email_value="";
                    }else{
                        $error="Pendaftaran gagal. Silakan coba lagi.";
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar - Toko ATK & Alat Jahit</title>
    <link rel="stylesheet" href="<?= $base_url ?>/pelanggan/assets/css/pelanggan.css">
</head>
<body class="auth-page">
<div class="auth-card">
    <a class="auth-logo" href="<?= $base_url ?>/pelanggan/index.php">
        <img src="<?= $base_url ?>/pelanggan/assets/images/logo.png" alt="Logo">
    </a>

    <h1>Daftar Pelanggan</h1>
    <p class="muted">Buat akun pelanggan baru.</p>

    <?php if($error!==""): ?>
        <div class="alert"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if($success!==""): ?>
        <div class="success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="POST">
        <label for="registerNama">Nama</label>
        <input type="text" name="nama" id="registerNama" placeholder="Masukkan nama" value="<?= htmlspecialchars($nama_value) ?>" autocomplete="name" required>

        <label for="registerEmail">Email</label>
        <input type="email" name="email" id="registerEmail" placeholder="Masukkan email" value="<?= htmlspecialchars($email_value) ?>" autocomplete="email" required>

        <label for="registerPassword">Password</label>
        <div class="password-field">
            <input type="password" name="password" id="registerPassword" placeholder="Masukkan password" autocomplete="new-password" required>
            <button type="button" class="password-toggle" onclick="togglePassword('registerPassword',this)" aria-label="Tampilkan password" aria-pressed="false">
                <svg class="eye-show" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M2.5 12s3.5-5.5 9.5-5.5S21.5 12 21.5 12 18 17.5 12 17.5 2.5 12 2.5 12Z"></path>
                    <circle cx="12" cy="12" r="2.7"></circle>
                </svg>
                <svg class="eye-hide" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M3 3l18 18"></path>
                    <path d="M10.6 6.7A10.8 10.8 0 0 1 12 6.5c6 0 9.5 5.5 9.5 5.5a18 18 0 0 1-3.2 3.7"></path>
                    <path d="M6.7 6.7A18.3 18.3 0 0 0 2.5 12S6 17.5 12 17.5c1.3 0 2.5-.3 3.5-.7"></path>
                </svg>
            </button>
        </div>

        <button class="btn-primary full register-submit" type="submit">Daftar</button>
    </form>

    <p class="auth-bottom">
        Sudah punya akun?
        <a href="<?= $base_url ?>/pelanggan/login.php">Login</a>
    </p>

    <a class="back-link" href="<?= $base_url ?>/pelanggan/index.php">← Kembali ke toko</a>
</div>

<style>
.password-field{position:relative;width:100%;margin-bottom:18px}
.password-field input{width:100%;box-sizing:border-box;padding-right:46px;margin-bottom:0!important}
.password-field input::-ms-reveal,.password-field input::-ms-clear{display:none}
.password-field input::-webkit-credentials-auto-fill-button{visibility:hidden;display:none!important;pointer-events:none}
.password-toggle{position:absolute;top:50%;right:8px;transform:translateY(-50%);width:34px;height:34px;display:flex;align-items:center;justify-content:center;padding:0;border:0;border-radius:6px;background:transparent;color:#829ab1;cursor:pointer}
.password-toggle:hover{background:#f0f4f8;color:#102a43}
.password-toggle:focus-visible{outline:2px solid #c7a56a;outline-offset:1px}
.password-toggle svg{width:19px;height:19px;fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round}
.password-toggle .eye-hide{display:none}
.password-toggle.active .eye-show{display:none}
.password-toggle.active .eye-hide{display:block}
.register-submit{margin-top:4px}
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