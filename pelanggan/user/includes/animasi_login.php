
<?php
/*
|--------------------------------------------------------------------------
| KOMPONEN ANIMASI SETELAH LOGIN
| Project: Toko ATK & Alat Jahit
|--------------------------------------------------------------------------
| File ini hanya berisi tampilan HTML animasi.
| CSS dan JavaScript disimpan di file terpisah.
|--------------------------------------------------------------------------
*/
?>

<div
    class="login-welcome-animation"
    id="loginWelcomeAnimation"
    role="status"
    aria-label="Selamat datang di Toko ATK dan Alat Jahit"
>
    <!-- Cahaya dekorasi -->
    <div
        class="login-welcome-glow glow-left"
        aria-hidden="true"
    ></div>

    <div
        class="login-welcome-glow glow-right"
        aria-hidden="true"
    ></div>

    <!-- Partikel cahaya -->
    <div
        class="login-welcome-particles"
        aria-hidden="true"
    >
        <span></span>
        <span></span>
        <span></span>
        <span></span>
        <span></span>
    </div>

    <!-- Konten utama -->
    <div class="login-welcome-content">

        <!-- Logo toko -->
        <div class="login-welcome-logo-wrap">
            <div
                class="login-welcome-logo-ring"
                aria-hidden="true"
            ></div>

            <div class="login-welcome-logo-frame">
                <img
                    src="/project_atk_jahit/pelanggan/assets/images/logo.png"
                    alt="Logo Toko ATK dan Alat Jahit"
                    class="login-welcome-logo"
                >
            </div>
        </div>

        <!-- Teks sambutan -->
        <p class="login-welcome-eyebrow">
            SELAMAT DATANG KEMBALI
        </p>

        <h1 class="login-welcome-title">
            Toko ATK <span>&amp; Alat Jahit</span>
        </h1>

        <p class="login-welcome-tagline">
            Semua kebutuhanmu, dalam satu tempat.
        </p>

        <!-- Garis animasi -->
        <div
            class="login-welcome-loader"
            role="presentation"
        >
            <span></span>
        </div>

    </div>
</div>
