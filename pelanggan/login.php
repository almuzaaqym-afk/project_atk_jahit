<?php
session_start();
require_once "../config/koneksi.php";

if (isset($_SESSION['id_pelanggan'])) {
    header("Location: user/index.php");
    exit;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($email === "" || $password === "") {
        $error = "Email dan password wajib diisi.";
    } else {
        $email_safe = mysqli_real_escape_string($conn, $email);
        $query = mysqli_query($conn, "SELECT * FROM pelanggan WHERE email='$email_safe' LIMIT 1");
        $pelanggan = mysqli_fetch_assoc($query);

        if ($pelanggan && password_verify($password, $pelanggan["password"])) {
            $_SESSION["id_pelanggan"] = $pelanggan["id_pelanggan"];
            $_SESSION["nama_pelanggan"] = $pelanggan["nama"];
            $_SESSION["email_pelanggan"] = $pelanggan["email"];
            header("Location: user/index.php");
            exit;
        }

        $error = "Email atau password salah.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - Toko ATK & Alat Jahit</title>
<link rel="stylesheet" href="assets/css/pelanggan.css">
</head>
<body class="auth-page">
<div class="auth-card">
    <a class="auth-logo" href="index.php">
        <img src="assets/images/logo.png" alt="Logo">
    </a>
    <h1>Login Pelanggan</h1>
    <p class="muted">Masuk untuk melanjutkan.</p>

    <?php if ($error): ?>
        <div class="alert"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <label>Email</label>
        <input type="email" name="email" placeholder="Masukkan email" required>

        <label>Password</label>
        <input type="password" name="password" placeholder="Masukkan password" required>

        <button class="btn-primary full" type="submit">Login</button>
    </form>

    <p class="auth-bottom">
        Belum punya akun? <a href="register.php">Daftar sekarang</a>
    </p>
    <a class="back-link" href="index.php">← Kembali ke toko</a>
</div>
</body>
</html>
