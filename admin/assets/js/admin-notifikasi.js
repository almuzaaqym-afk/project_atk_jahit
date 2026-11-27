(function () {

    "use strict";


    /* =========================================================
       CONFIG
    ========================================================= */

    const config =
        window.adminNotificationConfig || {};


    const BASE_URL =
        "/project_atk_jahit/admin/";


    const csEndpoint =
        config.csEndpoint ||
        BASE_URL + "cs/notifikasi.php";


    const chatEndpoint =
        config.chatEndpoint ||
        BASE_URL + "pesanan/notifikasi_chat.php";


    const csUrl =
        config.csUrl ||
        BASE_URL + "cs/index.php";


    const chatUrl =
        config.chatUrl ||
        BASE_URL + "pesanan/index.php";


    /* =========================================================
       ELEMENT BADGE
    ========================================================= */

    const csBadge =
        document.getElementById(
            "csNotificationBadge"
        );


    const chatBadge =
        document.getElementById(
            "chatNotificationBadge"
        );


    /* =========================================================
       DATA PESANAN BELUM DIBALAS
    ========================================================= */

    let pesananBelumDibalas = [];


    /* =========================================================
       SET BADGE
    ========================================================= */

    function setBadge(
        element,
        total
    ) {

        if (!element) {
            return;
        }


        const jumlah =
            Number(total) || 0;


        if (jumlah <= 0) {

            element.hidden = true;

            element.textContent = "0";

            return;

        }


        element.hidden = false;


        element.textContent =
            jumlah > 99
                ? "99+"
                : String(jumlah);

    }


    /* =========================================================
       AMBIL ID PESANAN DARI URL
    ========================================================= */

    function getIdPesananFromUrl(
        href
    ) {

        try {

            const url =
                new URL(
                    href,
                    window.location.origin
                );


            return Number(
                url.searchParams.get("id")
            ) || 0;

        } catch (error) {

            return 0;

        }

    }


    /* =========================================================
       CEK PESANAN BELUM DIBALAS
    ========================================================= */

    function isPesananBelumDibalas(
        idPesanan
    ) {

        return pesananBelumDibalas.some(
            function (item) {

                return (
                    Number(item.id_pesanan) ===
                    Number(idPesanan)
                );

            }
        );

    }


    /* =========================================================
       BUAT DOT MERAH
    ========================================================= */

    function createOrderDot() {

        const dot =
            document.createElement("span");


        dot.className =
            "notification-order-dot";


        dot.setAttribute(
            "aria-label",
            "Pesan pelanggan belum dibalas"
        );


        dot.title =
            "Ada pesan pelanggan yang belum dibalas";


        return dot;

    }


    /* =========================================================
       UPDATE SIDEBAR PESANAN
    ========================================================= */

    function updateSidebarPesanan() {

        const sidebarLink =
            document.querySelector(
                '.sidebar a[href$="pesanan/index.php"]'
            );


        if (!sidebarLink) {
            return;
        }


        let badge =
            sidebarLink.querySelector(
                ".sidebar-pesanan-badge"
            );


        const total =
            pesananBelumDibalas.length;


        if (total <= 0) {

            if (badge) {
                badge.remove();
            }

            return;

        }


        if (!badge) {

            badge =
                document.createElement("span");


            badge.className =
                "sidebar-pesanan-badge";


            sidebarLink.appendChild(
                badge
            );

        }


        badge.textContent =
            total > 99
                ? "99+"
                : String(total);

    }


    /* =========================================================
       UPDATE NAVBAR CS
    ========================================================= */

    function updateNavbarCS() {

        if (!csBadge) {
            return;
        }


        const parentLink =
            csBadge.closest("a");


        if (parentLink) {

            parentLink.href =
                csUrl;

        }

    }


    /* =========================================================
       UPDATE NAVBAR PESANAN
    ========================================================= */

    function updateNavbarPesanan() {

        if (!chatBadge) {
            return;
        }


        const parentLink =
            chatBadge.closest("a");


        if (parentLink) {

            parentLink.href =
                chatUrl;

        }


        setBadge(
            chatBadge,
            pesananBelumDibalas.length
        );

    }


    /* =========================================================
       UPDATE TITIK DI INDEX PESANAN
    ========================================================= */

    function updatePesananIndex() {

        const detailLinks =
            document.querySelectorAll(
                '.pesanan-table a[href*="detail.php?id="]'
            );


        detailLinks.forEach(
            function (link) {

                const idPesanan =
                    getIdPesananFromUrl(
                        link.href
                    );


                const row =
                    link.closest("tr");


                if (!row) {
                    return;
                }


                const nomorPesanan =
                    row.querySelector(
                        ".nomor-pesanan"
                    );


                if (!nomorPesanan) {
                    return;
                }


                let dot =
                    nomorPesanan.querySelector(
                        ".notification-order-dot"
                    );


                const unread =
                    isPesananBelumDibalas(
                        idPesanan
                    );


                if (unread) {

                    if (!dot) {

                        dot =
                            createOrderDot();


                        nomorPesanan.appendChild(
                            dot
                        );

                    }

                } else {

                    if (dot) {
                        dot.remove();
                    }

                }

            }
        );

    }


    /* =========================================================
       UPDATE TITIK DI DETAIL PESANAN
    ========================================================= */

    function updatePesananDetail() {

        const chatButton =
            document.querySelector(
                ".btn-chat-admin"
            );


        if (!chatButton) {
            return;
        }


        const idPesanan =
            getIdPesananFromUrl(
                window.location.href
            );


        let dot =
            chatButton.querySelector(
                ".notification-order-dot"
            );


        const unread =
            isPesananBelumDibalas(
                idPesanan
            );


        if (unread) {

            if (!dot) {

                dot =
                    createOrderDot();


                chatButton.appendChild(
                    dot
                );

            }

        } else {

            if (dot) {
                dot.remove();
            }

        }

    }


    /* =========================================================
       UPDATE SEMUA PESANAN
    ========================================================= */

    function updateSemuaPesanan() {

        updateNavbarPesanan();

        updateSidebarPesanan();

        updatePesananIndex();

        updatePesananDetail();

    }


    /* =========================================================
       LOAD CUSTOMER SERVICE
    ========================================================= */

    async function loadCustomerService() {

        try {

            const response =
                await fetch(
                    csEndpoint,
                    {
                        method: "GET",
                        cache: "no-store"
                    }
                );


            if (!response.ok) {
                return;
            }


            const data =
                await response.json();


            if (
                !data ||
                !data.success
            ) {

                return;

            }


            setBadge(
                csBadge,
                data.menunggu ?? 0
            );


            updateNavbarCS();

        } catch (error) {

            // Abaikan error polling.

        }

    }


    /* =========================================================
       LOAD LIVE CHAT PESANAN
    ========================================================= */

    async function loadPesananChat() {

        try {

            const response =
                await fetch(
                    chatEndpoint,
                    {
                        method: "GET",
                        cache: "no-store"
                    }
                );


            if (!response.ok) {
                return;
            }


            const data =
                await response.json();


            if (
                !data ||
                !data.success
            ) {

                return;

            }


            pesananBelumDibalas =
                Array.isArray(
                    data.pesanan
                )
                    ? data.pesanan
                    : [];


            updateSemuaPesanan();

        } catch (error) {

            // Abaikan error polling.

        }

    }


    /* =========================================================
       LOAD SEMUA
    ========================================================= */

    function loadAll() {

        loadCustomerService();

        loadPesananChat();

    }


    /* =========================================================
       LOAD PERTAMA
    ========================================================= */

    loadAll();


    /* =========================================================
       POLLING 3 DETIK
    ========================================================= */

    setInterval(
        loadAll,
        3000
    );


    /* =========================================================
       SAAT TAB KEMBALI AKTIF
    ========================================================= */

    document.addEventListener(
        "visibilitychange",
        function () {

            if (!document.hidden) {

                loadAll();

            }

        }
    );


})();