<?php
session_start();

require_once __DIR__ . "/../../config/koneksi.php";

$base_url="/project_atk_jahit";
$error="";
$email_value="";

if($_SERVER["REQUEST_METHOD"]==="POST"){
    $email=trim($_POST["email"]??"");
    $email_value=$email;

    if($email===""){
        $error="Email wajib diisi.";
    }elseif(!filter_var($email,FILTER_VALIDATE_EMAIL)){
        $error="Format email tidak valid.";
    }else{
        $stmt=mysqli_prepare(
            $conn,
            "SELECT id_pelanggan,email
             FROM pelanggan
             WHERE email=?
             LIMIT 1"
        );

        mysqli_stmt_bind_param($stmt,"s",$email);
        mysqli_stmt_execute($stmt);

        $result=mysqli_stmt_get_result($stmt);
        $pelanggan=mysqli_fetch_assoc($result);

        mysqli_stmt_close($stmt);

        if(!$pelanggan){
            $error="Email tidak ditemukan.";
        }else{
            $token=bin2hex(random_bytes(32));
            $expires_at=date("Y-m-d H:i:s",time()+1800);

            $stmt=mysqli_prepare(
                $conn,
                "DELETE FROM password_reset_token
                 WHERE id_pelanggan=?
                 OR expires_at<NOW()"
            );

            mysqli_stmt_bind_param(
                $stmt,
                "i",
                $pelanggan["id_pelanggan"]
            );

            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            $stmt=mysqli_prepare(
                $conn,
                "INSERT INTO password_reset_token
                (id_pelanggan,token,expires_at)
                VALUES(?,?,?)"
            );

            mysqli_stmt_bind_param(
                $stmt,
                "iss",
                $pelanggan["id_pelanggan"],
                $token,
                $expires_at
            );

            if(mysqli_stmt_execute($stmt)){
                mysqli_stmt_close($stmt);

                header(
                    "Location: email_simulasi.php?token=".urlencode($token)
                );
                exit;
            }

            mysqli_stmt_close($stmt);
            $error="Gagal membuat link reset password.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - Toko ATK & Alat Jahit</title>
    <link rel="stylesheet" href="<?= $base_url ?>/pelanggan/assets/css/pelanggan.css">
</head>

<body class="auth-page">

<div class="auth-card forgot-auth-card">

    <a class="auth-logo" href="<?= $base_url ?>/pelanggan/index.php">
        <img
            src="<?= $base_url ?>/pelanggan/assets/images/logo.png"
            alt="Logo"
        >
    </a>

    <h1>Lupa Password</h1>

    <p class="muted">
        Masukkan email yang terdaftar pada akun kamu.
    </p>

    <?php if($error): ?>
        <div class="alert">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <div class="forgot-page-icon">
        <svg viewBox="0 0 24 24" aria-hidden="true">
            <rect x="4" y="5" width="16" height="14" rx="2"></rect>
            <path d="M4 7l8 6 8-6"></path>
        </svg>
    </div>

    <form method="POST">

        <label>Email</label>

        <input
            type="email"
            name="email"
            value="<?= htmlspecialchars($email_value) ?>"
            placeholder="Masukkan email"
            autocomplete="email"
            required
        >

        <button
            type="submit"
            class="btn-primary full"
        >
            Kirim Link Reset
        </button>

    </form>

    <a
        class="back-link"
        href="<?= $base_url ?>/pelanggan/login.php"
    >
        ← Kembali ke Login
    </a>

</div>

<style>
.forgot-auth-card{
    position:relative;
}

.forgot-page-icon{
    width:58px;
    height:58px;
    margin:8px auto 22px;
    border-radius:50%;
    background:#f5ead5;
    color:#102a43;
    display:flex;
    align-items:center;
    justify-content:center;
}

.forgot-page-icon svg{
    width:27px;
    height:27px;
    fill:none;
    stroke:currentColor;
    stroke-width:1.7;
    stroke-linecap:round;
    stroke-linejoin:round;
}

.forgot-auth-card form{
    text-align:left;
}

.forgot-auth-card form button{
    margin-top:18px;
}

.forgot-auth-card .back-link{
    display:block;
    margin-top:20px;
    text-align:center;
}

@media(max-width:480px){
    .forgot-auth-card{
        width:calc(100% - 30px);
        box-sizing:border-box;
    }
}
</style>

</body>
</html>