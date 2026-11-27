
// ======================================================
// DASHBOARD ADMIN
// Animasi statistik dan efek interaksi kartu
// ======================================================

(function () {
    "use strict";

    function formatAngka(nilai) {
        return new Intl.NumberFormat("id-ID", {
            maximumFractionDigits: 0
        }).format(nilai);
    }

    function animasikanAngka(elemen) {
        if (!elemen || elemen.dataset.animated === "true") {
            return;
        }

        const teksAsli = elemen.textContent.trim();
        const nilaiAkhir = Number(teksAsli.replace(/[^\d]/g, ""));

        if (!Number.isFinite(nilaiAkhir) || nilaiAkhir < 0) {
            return;
        }

        elemen.dataset.animated = "true";

        const awalan = /^\s*Rp\s*/i.test(teksAsli) ? "Rp " : "";
        const durasi = 1000;
        const waktuMulai = performance.now();

        function langkah(waktuSekarang) {
            const progres = Math.min(
                (waktuSekarang - waktuMulai) / durasi,
                1
            );

            // Efek ease-out agar angka melambat saat mendekati nilai akhir.
            const easing = 1 - Math.pow(1 - progres, 3);
            const nilaiSekarang = Math.round(nilaiAkhir * easing);

            elemen.textContent =
                awalan + formatAngka(nilaiSekarang);

            if (progres < 1) {
                requestAnimationFrame(langkah);
            } else {
                elemen.textContent =
                    awalan + formatAngka(nilaiAkhir);
            }
        }

        requestAnimationFrame(langkah);
    }

    function initDashboard() {
        const dashboard = document.querySelector(".store-dashboard");

        if (!dashboard) {
            return;
        }

        // Animasi angka statistik ketika dashboard pertama dibuka.
        const statistik = dashboard.querySelectorAll(
            ".store-stat-number"
        );

        statistik.forEach(function (elemen, indeks) {
            window.setTimeout(function () {
                animasikanAngka(elemen);
            }, indeks * 120);
        });

        // Efek fokus keyboard untuk kartu statistik.
        const kartu = dashboard.querySelectorAll(".store-stat");

        kartu.forEach(function (elemen) {
            elemen.addEventListener("mouseenter", function () {
                elemen.classList.add("is-hovered");
            });

            elemen.addEventListener("mouseleave", function () {
                elemen.classList.remove("is-hovered");
            });
        });
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", initDashboard, {
            once: true
        });
    } else {
        initDashboard();
    }
})();
