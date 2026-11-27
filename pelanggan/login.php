
<?php
session_start();

require_once __DIR__ . "/../config/koneksi.php";

$base_url = "/project_atk_jahit";

if (isset($_SESSION["id_pelanggan"])) {
    header("Location: {$base_url}/pelanggan/user/index.php");
    exit;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($email === "" || $password === "") {
        $error = "Email dan password wajib diisi.";
    } else {
        $stmt = mysqli_prepare(
            $conn,
            "SELECT id_pelanggan, nama, email, password
             FROM pelanggan
             WHERE email = ?
             LIMIT 1"
        );

        if (!$stmt) {
            $error = "Terjadi kesalahan. Silakan coba lagi.";
        } else {
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);

            $result = mysqli_stmt_get_result($stmt);
            $pelanggan = mysqli_fetch_assoc($result);

            mysqli_stmt_close($stmt);

            if (
                $pelanggan &&
                password_verify($password, $pelanggan["password"])
            ) {
                session_regenerate_id(true);

                $_SESSION["id_pelanggan"] =
                    $pelanggan["id_pelanggan"];

                $_SESSION["nama_pelanggan"] =
                    $pelanggan["nama"];

                $_SESSION["email_pelanggan"] =
                    $pelanggan["email"];

                // Penanda animasi hanya setelah login berhasil.
                $_SESSION["show_login_animation"] = true;

                header(
                    "Location: {$base_url}/pelanggan/user/index.php"
                );
                exit;
            }

            $error = "Email atau password salah.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login - Toko ATK & Alat Jahit</title>

    <link
        rel="stylesheet"
        href="<?= $base_url ?>/pelanggan/assets/css/pelanggan.css"
    >
</head>

<body class="auth-page">

<div class="auth-card">

    <a
        class="auth-logo"
        href="<?= $base_url ?>/pelanggan/index.php"
    >
        <img
            src="<?= $base_url ?>/pelanggan/assets/images/logo.png"
            alt="Logo Toko ATK & Alat Jahit"
        >
    </a>

    <h1>Login Pelanggan</h1>
    <p class="muted">Masuk untuk melanjutkan.</p>

    <?php if ($error !== ""): ?>
        <div class="alert">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST">

        <label for="loginEmail">Email</label>

        <input
            type="email"
            name="email"
            id="loginEmail"
            placeholder="Masukkan email"
            autocomplete="email"
            required
        >

        <label for="loginPassword">Password</label>

        <div class="password-field">

            <input
                type="password"
                name="password"
                id="loginPassword"
                placeholder="Masukkan password"
                autocomplete="current-password"
                required
            >

            <button
                type="button"
                class="password-toggle"
                onclick="togglePassword('loginPassword', this)"
                aria-label="Tampilkan password"
                aria-pressed="false"
            >
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

        <div class="forgot-password-login">
            <a href="<?= $base_url ?>/pelanggan/user/lupa_password.php">
                Lupa Password?
            </a>
        </div>

        <button class="btn-primary full" type="submit">
            Login
        </button>

    </form>

    <p class="auth-bottom">
        Belum punya akun?
        <a href="<?= $base_url ?>/pelanggan/register.php">
            Daftar sekarang
        </a>
    </p>

    <a
        class="back-link"
        href="<?= $base_url ?>/pelanggan/index.php"
    >
        ← Kembali ke toko
    </a>

</div>

<style>
.password-field {
    position: relative;
    width: 100%;
    margin-bottom: 18px;
}

.password-field input {
    width: 100%;
    box-sizing: border-box;
    padding-right: 46px;
    margin-bottom: 0 !important;
}

.password-field input::-ms-reveal,
.password-field input::-ms-clear {
    display: none;
}

.password-field input::-webkit-credentials-auto-fill-button {
    visibility: hidden;
    display: none !important;
    pointer-events: none;
}

.password-toggle {
    position: absolute;
    top: 50%;
    right: 8px;
    transform: translateY(-50%);
    width: 34px;
    height: 34px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0;
    border: 0;
    border-radius: 6px;
    background: transparent;
    color: #829ab1;
    cursor: pointer;
}

.password-toggle:hover {
    background: #f0f4f8;
    color: #102a43;
}

.password-toggle:focus-visible {
    outline: 2px solid #c7a56a;
    outline-offset: 1px;
}

.password-toggle svg {
    width: 19px;
    height: 19px;
    fill: none;
    stroke: currentColor;
    stroke-width: 1.8;
    stroke-linecap: round;
    stroke-linejoin: round;
}

.password-toggle .eye-hide {
    display: none;
}

.password-toggle.active .eye-show {
    display: none;
}

.password-toggle.active .eye-hide {
    display: block;
}

.forgot-password-login {
    display: flex;
    justify-content: flex-end;
    margin: 0 0 18px;
}

.forgot-password-login a {
    color: #102a43;
    font-size: 12px;
    font-weight: bold;
    text-decoration: none;
}

.forgot-password-login a:hover {
    color: #c7a56a;
}
</style>

<script>
function togglePassword(inputId, button) {
    const input = document.getElementById(inputId);

    if (!input) {
        return;
    }

    const tampilkan = input.type === "password";

    input.type = tampilkan ? "text" : "password";

    button.classList.toggle("active", tampilkan);

    button.setAttribute(
        "aria-label",
        tampilkan ? "Sembunyikan password" : "Tampilkan password"
    );

    button.setAttribute(
        "aria-pressed",
        tampilkan ? "true" : "false"
    );
}
</script>

</body>
</html>
