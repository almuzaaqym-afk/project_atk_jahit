document.addEventListener("DOMContentLoaded", function () {

    const sidebar = document.getElementById("adminSidebar");
    const toggle = document.getElementById("sidebarToggle");

    if (!sidebar || !toggle) {
        return;
    }


    /* =========================================
       CEK STATUS SIDEBAR
    ========================================= */

    const sidebarClosed =
        localStorage.getItem("adminSidebarClosed") === "true";

    if (sidebarClosed) {
        sidebar.classList.add("sidebar-collapsed");
    }


    /* =========================================
       TOMBOL HAMBURGER
    ========================================= */

    toggle.addEventListener("click", function () {

        sidebar.classList.toggle("sidebar-collapsed");

        const isClosed =
            sidebar.classList.contains("sidebar-collapsed");

        localStorage.setItem(
            "adminSidebarClosed",
            isClosed
        );

    });


    /* =========================================
       KLIK MENU
       SIDEBAR OTOMATIS TUTUP
    ========================================= */

    const menuLinks = sidebar.querySelectorAll(
        ".sidebar-menu a:not(.logout-link)"
    );

    menuLinks.forEach(function (link) {

        link.addEventListener("click", function () {

            sidebar.classList.add("sidebar-collapsed");

            localStorage.setItem(
                "adminSidebarClosed",
                "true"
            );

        });

    });

});