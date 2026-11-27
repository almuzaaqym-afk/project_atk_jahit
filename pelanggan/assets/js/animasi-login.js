
/* =====================================================
   ANIMASI SETELAH LOGIN
   Toko ATK & Alat Jahit
===================================================== */

(function () {
    "use strict";

    function initLoginWelcomeAnimation() {
        const animation = document.querySelector(
            ".login-welcome-animation"
        );

        // Jangan melakukan apa pun jika animasi tidak digunakan.
        if (!animation) {
            return;
        }

        // Hindari animasi berjalan berulang pada elemen yang sama.
        if (animation.dataset.initialized === "true") {
            return;
        }

        animation.dataset.initialized = "true";

        const prefersReducedMotion =
            window.matchMedia &&
            window.matchMedia("(prefers-reduced-motion: reduce)").matches;

        // Tunggu konten animasi selesai ditampilkan.
        const displayDuration = prefersReducedMotion ? 700 : 3200;

        window.setTimeout(function () {
            // Mulai transisi keluar.
            animation.classList.add("is-leaving");

            // Hapus elemen setelah transisi selesai.
            window.setTimeout(function () {
                if (animation.parentNode) {
                    animation.parentNode.removeChild(animation);
                }
            }, prefersReducedMotion ? 100 : 850);
        }, displayDuration);
    }

    // Aman digunakan jika script dimuat sebelum atau sesudah DOM siap.
    if (document.readyState === "loading") {
        document.addEventListener(
            "DOMContentLoaded",
            initLoginWelcomeAnimation,
            { once: true }
        );
    } else {
        initLoginWelcomeAnimation();
    }
})();
