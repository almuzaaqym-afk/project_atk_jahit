<?php
session_start();

require_once __DIR__ . "/../../config/koneksi.php";

$base_url="/project_atk_jahit";
$token=trim($_GET["token"]??"");

if($token===""){
    header("Location: lupa_password.php");
    exit;
}

$stmt=mysqli_prepare(
    $conn,
    "SELECT
        pr.id_reset,
        pr.id_pelanggan,
        pr.token,
        pr.expires_at,
        pr.used_at,
        p.nama,
        p.email
     FROM password_reset_token pr
     INNER JOIN pelanggan p
        ON p.id_pelanggan=pr.id_pelanggan
     WHERE pr.token=?
     LIMIT 1"
);

mysqli_stmt_bind_param($stmt,"s",$token);
mysqli_stmt_execute($stmt);

$result=mysqli_stmt_get_result($stmt);
$data=mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if(!$data){
    header("Location: lupa_password.php");
    exit;
}

$link_valid=true;

if(!empty($data["used_at"])){
    $link_valid=false;
}

if(strtotime($data["expires_at"])<time()){
    $link_valid=false;
}

$nama=trim($data["nama"]??"Pelanggan");
$email=trim($data["email"]??"");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Reset Password - Toko ATK & Alat Jahit</title>
    <link rel="stylesheet" href="<?= $base_url ?>/pelanggan/assets/css/pelanggan.css">
</head>

<body class="auth-page email-page">

<div class="email-wrapper">

    <div class="email-top-logo">
        <img
            src="<?= $base_url ?>/pelanggan/assets/images/logo.png"
            alt="Logo"
        >
        <strong>Toko ATK & Alat Jahit</strong>
    </div>

    <div class="email-card">

        <?php if(!$link_valid): ?>

            <div class="email-invalid">

                <div class="invalid-icon">
                    <svg viewBox="0 0 24 24">
                        <path d="M12 8v5"></path>
                        <path d="M12 16.5h.01"></path>
                        <path d="M10.3 3.8 2.7 17a2 2 0 0 0 1.7 3h15.2a2 2 0 0 0 1.7-3L13.7 3.8a2 2 0 0 0-3.4 0Z"></path>
                    </svg>
                </div>

                <h1>Link Tidak Berlaku</h1>

                <p>
                    Link reset password sudah digunakan atau sudah kedaluwarsa.
                </p>

                <a
                    href="lupa_password.php"
                    class="btn-primary"
                >
                    Minta Link Baru
                </a>

            </div>

        <?php else: ?>

            <div class="email-header">

                <div class="email-sender">

                    <div class="email-logo">
                        <svg viewBox="0 0 24 24">
                            <rect x="3" y="5" width="18" height="14" rx="2"></rect>
                            <path d="M3 7l9 6 9-6"></path>
                        </svg>
                    </div>

                    <div>
                        <strong>Toko ATK & Alat Jahit</strong>
                        <small>Pusat Akun</small>
                    </div>

                </div>

                <span class="email-badge">
                    Baru
                </span>

            </div>

            <div class="email-line"></div>

            <div class="email-info">

                <div>
                    <span>Kepada</span>
                    <strong><?= htmlspecialchars($email) ?></strong>
                </div>

                <div>
                    <span>Subjek</span>
                    <strong>Reset Password Akun</strong>
                </div>

            </div>

            <div class="email-line"></div>

            <div class="email-body">

                <h1>Reset Password</h1>

                <p>
                    Halo <strong><?= htmlspecialchars($nama) ?></strong>,
                </p>

                <p>
                    Kami menerima permintaan untuk mengatur ulang password
                    akun Toko ATK & Alat Jahit kamu.
                </p>

                <p>
                    Klik tombol berikut untuk membuat password baru.
                </p>

                <div class="email-action">

                    <a
                        href="reset_password.php?token=<?= urlencode($token) ?>"
                        class="email-reset-button"
                    >
                        Reset Password
                    </a>

                </div>

                <div class="email-notice">
                    Link reset password berlaku selama 30 menit.
                </div>

                <p>
                    Terima kasih,<br>
                    <strong>Toko ATK & Alat Jahit</strong>
                </p>

            </div>

            <div class="email-footer">
                Pesan dikirim ke <?= htmlspecialchars($email) ?>
            </div>

        <?php endif; ?>

    </div>

    <a
        href="<?= $base_url ?>/pelanggan/login.php"
        class="email-back"
    >
        ← Kembali ke Login
    </a>

</div>

<style>
.email-page{
    padding:30px 15px;
}

.email-wrapper{
    width:100%;
    max-width:680px;
    margin:auto;
}

.email-top-logo{
    display:flex;
    align-items:center;
    justify-content:center;
    gap:10px;
    margin-bottom:18px;
    color:#102a43;
    font-size:15px;
}

.email-top-logo img{
    width:38px;
    height:38px;
    object-fit:contain;
}

.email-card{
    background:#fff;
    border:1px solid #e5e7eb;
    border-radius:14px;
    overflow:hidden;
    box-shadow:0 8px 25px rgba(16,42,67,.07);
}

.email-header{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:15px;
    padding:20px 24px;
}

.email-sender{
    display:flex;
    align-items:center;
    gap:12px;
}

.email-logo{
    width:42px;
    height:42px;
    min-width:42px;
    border-radius:50%;
    background:#f5ead5;
    color:#102a43;
    display:flex;
    align-items:center;
    justify-content:center;
}

.email-logo svg{
    width:21px;
    height:21px;
    fill:none;
    stroke:currentColor;
    stroke-width:1.7;
    stroke-linecap:round;
    stroke-linejoin:round;
}

.email-sender strong{
    display:block;
    color:#102a43;
    font-size:14px;
}

.email-sender small{
    display:block;
    margin-top:2px;
    color:#627d98;
    font-size:11px;
}

.email-badge{
    padding:5px 10px;
    border-radius:20px;
    background:#edf7ed;
    color:#2f855a;
    font-size:10px;
    font-weight:bold;
}

.email-line{
    height:1px;
    background:#e5e7eb;
}

.email-info{
    padding:16px 24px;
}

.email-info>div{
    display:flex;
    gap:15px;
    margin-bottom:8px;
}

.email-info>div:last-child{
    margin-bottom:0;
}

.email-info span{
    width:55px;
    color:#829ab1;
    font-size:11px;
}

.email-info strong{
    color:#334e68;
    font-size:12px;
    word-break:break-word;
}

.email-body{
    padding:28px 24px;
    color:#334e68;
    font-size:13px;
    line-height:1.7;
}

.email-body h1{
    margin:0 0 20px;
    color:#102a43;
    font-size:21px;
}

.email-body p{
    margin:0 0 15px;
}

.email-action{
    text-align:center;
    margin:28px 0;
}

.email-reset-button{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    min-width:190px;
    padding:12px 22px;
    border-radius:8px;
    background:#102a43;
    color:#fff!important;
    text-decoration:none;
    font-size:13px;
    font-weight:bold;
}

.email-reset-button:hover{
    background:#183b56;
}

.email-notice{
    margin:20px 0;
    padding:11px 13px;
    background:#f7f9fc;
    border-radius:7px;
    color:#627d98;
    font-size:11px;
}

.email-footer{
    padding:13px 24px;
    background:#f7f9fc;
    border-top:1px solid #e5e7eb;
    color:#829ab1;
    font-size:10px;
}

.email-invalid{
    padding:45px 25px;
    text-align:center;
}

.invalid-icon{
    width:58px;
    height:58px;
    margin:0 auto 16px;
    border-radius:50%;
    background:#fff4e5;
    color:#c97a00;
    display:flex;
    align-items:center;
    justify-content:center;
}

.invalid-icon svg{
    width:27px;
    height:27px;
    fill:none;
    stroke:currentColor;
    stroke-width:1.7;
    stroke-linecap:round;
    stroke-linejoin:round;
}

.email-invalid h1{
    margin:0 0 10px;
    color:#102a43;
    font-size:20px;
}

.email-invalid p{
    max-width:400px;
    margin:0 auto 22px;
    color:#627d98;
    font-size:13px;
    line-height:1.6;
}

.email-invalid .btn-primary{
    text-decoration:none;
}

.email-back{
    display:block;
    margin-top:18px;
    text-align:center;
    color:#102a43;
    text-decoration:none;
    font-size:12px;
    font-weight:bold;
}

.email-back:hover{
    color:#c7a56a;
}

@media(max-width:600px){
    .email-page{
        padding:20px 12px;
    }

    .email-header{
        padding:16px;
    }

    .email-info{
        padding:14px 16px;
    }

    .email-body{
        padding:22px 16px;
    }

    .email-footer{
        padding:12px 16px;
    }

    .email-badge{
        display:none;
    }

    .email-info>div{
        align-items:flex-start;
        gap:10px;
    }

    .email-info span{
        width:50px;
    }

    .email-reset-button{
        width:100%;
    }
}
</style>

</body>
</html>