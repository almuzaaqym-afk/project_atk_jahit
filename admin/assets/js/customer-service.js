(function () {
    "use strict";

    /*
    |--------------------------------------------------------------------------
    | MENCEGAH SCRIPT BERJALAN GANDA
    |--------------------------------------------------------------------------
    */

    if (window.__customerServiceInitialized) {
        return;
    }

    window.__customerServiceInitialized = true;

    /*
    |--------------------------------------------------------------------------
    | BADGE ANTREAN CS - BERJALAN DI SEMUA HALAMAN ADMIN YANG BERHAK
    |--------------------------------------------------------------------------
    */

    const csBadge = document.getElementById("csQueueBadge");

    if (csBadge) {
        const badgeEndpoint =
            "/project_atk_jahit/admin/cs/notifikasi.php?badge_only=1";

        let badgeLoading = false;

        async function updateCSBadge() {
            if (badgeLoading) {
                return;
            }

            badgeLoading = true;

            try {
                const response = await fetch(badgeEndpoint, {
                    method: "GET",
                    cache: "no-store",
                    headers: {
                        "X-Requested-With": "XMLHttpRequest"
                    }
                });

                if (!response.ok) {
                    return;
                }

                const data = await response.json();

                if (!data || !data.success) {
                    return;
                }

                const jumlah = Math.max(
                    0,
                    Number(data.waiting ?? data.menunggu ?? 0)
                );

                csBadge.textContent = String(jumlah);

                csBadge.style.display =
                    jumlah > 0 ? "inline-flex" : "none";

            } catch (error) {
                console.error(
                    "Gagal memperbarui badge Customer Service:",
                    error
                );
            } finally {
                badgeLoading = false;
            }
        }

        updateCSBadge();

        setInterval(updateCSBadge, 5000);
    }

    /*
    |--------------------------------------------------------------------------
    | KODE CUSTOMER SERVICE YANG SUDAH ADA
    |--------------------------------------------------------------------------
    */

    const page = document.getElementById("csPage");

    if (!page) {
        return;
    }

    const endpoint =
        page.dataset.endpoint ||
        "notifikasi.php";

    /*
     * Mulai dari sini, pertahankan semua kode lama kamu:
     * - csWaitingNumber
     * - csServingNumber
     * - csDoneNumber
     * - csTableBody
     * - renderTable()
     * - loadCustomerService()
     * - polling pesan
     *
     * Jangan menyalin deklarasi const page dan const endpoint
     * yang lama lagi karena sudah ada di atas.
     */

    // LANJUTKAN KODE LAMA DARI BAGIAN SETELAH const endpoint.


    const waitingNumber =
        document.getElementById(
            "csWaitingNumber"
        );


    const servingNumber =
        document.getElementById(
            "csServingNumber"
        );


    const doneNumber =
        document.getElementById(
            "csDoneNumber"
        );


    const tableBody =
        document.getElementById(
            "csTableBody"
        );


    /* =========================================================
       ESCAPE HTML
    ========================================================= */

    function escapeHtml(value) {

        const div =
            document.createElement(
                "div"
            );


        div.textContent =
            value ?? "";


        return div.innerHTML;

    }


    /* =========================================================
       STATUS
    ========================================================= */

    function statusHtml(
        status
    ) {

        if (
            status === "Menunggu"
        ) {

            return `
                <span class="cs-status cs-status-waiting">
                    ⏳ Menunggu
                </span>
            `;

        }


        if (
            status === "Dilayani"
        ) {

            return `
                <span class="cs-status cs-status-serving">
                    🟢 Dilayani
                </span>
            `;

        }


        return `
            <span class="cs-status cs-status-done">
                ✓ Selesai
            </span>
        `;

    }


    /* =========================================================
       ACTION
    ========================================================= */

    function actionHtml(
        chat
    ) {

        const id =
            Number(
                chat.id_chat || 0
            );


        const status =
            chat.status_chat || "";


        if (
            status === "Menunggu"
        ) {

            return `
                <a
                    href="layani.php?id=${id}"
                    class="cs-btn cs-btn-serve"
                >
                    Layani Chat
                </a>
            `;

        }


        if (
            status === "Dilayani"
        ) {

            return `
                <a
                    href="chat.php?id=${id}"
                    class="cs-btn cs-btn-open"
                >
                    Buka Chat
                </a>
            `;

        }


        return `
            <a
                href="chat.php?id=${id}"
                class="cs-btn cs-btn-open"
            >
                Lihat Chat
            </a>
        `;

    }


    /* =========================================================
       RENDER TABLE
    ========================================================= */

    function renderTable(
        chats
    ) {

        if (!tableBody) {
            return;
        }


        if (
            !Array.isArray(chats) ||
            chats.length === 0
        ) {

            tableBody.innerHTML = `
                <tr>
                    <td colspan="6">
                        <div class="cs-empty">
                            <div class="cs-empty-icon">
                                💬
                            </div>

                            <div class="cs-empty-title">
                                Belum ada percakapan
                            </div>

                            <div class="cs-empty-text">
                                Antrian Customer Service akan muncul di sini.
                            </div>
                        </div>
                    </td>
                </tr>
            `;

            return;

        }


        let html = "";


        chats.forEach(
            function (
                chat,
                index
            ) {

                const nama =
                    (
                        chat.nama ||
                        "Pelanggan"
                    ).trim() ||
                    "Pelanggan";


                const inisial =
                    nama
                        .substring(
                            0,
                            1
                        )
                        .toUpperCase();


                const pesan =
                    chat.pesan_terakhir ||
                    "";


                const waktu =
                    chat.waktu_pesan ||
                    "-";


                const idChat =
                    Number(
                        chat.id_chat ||
                        0
                    );


                html += `

                    <tr>

                        <td data-label="No">
                            ${index + 1}
                        </td>


                        <td data-label="Pelanggan">

                            <div class="cs-customer">

                                <div class="cs-customer-avatar">
                                    ${escapeHtml(inisial)}
                                </div>

                                <div>

                                    <div class="cs-customer-name">
                                        ${escapeHtml(nama)}
                                    </div>

                                    <div class="cs-customer-id">
                                        Chat #${idChat}
                                    </div>

                                </div>

                            </div>

                        </td>


                        <td data-label="Status">
                            ${statusHtml(chat.status_chat)}
                        </td>


                        <td data-label="Pesan Terakhir">

                            ${
                                pesan
                                    ? `
                                        <div class="cs-last-message">
                                            ${escapeHtml(pesan)}
                                        </div>
                                    `
                                    : `
                                        <div class="cs-no-message">
                                            Belum ada pesan
                                        </div>
                                    `
                            }

                        </td>


                        <td data-label="Waktu">
                            ${escapeHtml(waktu)}
                        </td>


                        <td data-label="Aksi">

                            <div class="cs-actions">

                                ${actionHtml(chat)}

                            </div>

                        </td>

                    </tr>

                `;

            }
        );


        tableBody.innerHTML =
            html;

    }


    /* =========================================================
       LOAD CUSTOMER SERVICE
    ========================================================= */

    async function loadCustomerService() {

        try {

            const response =
                await fetch(
                    endpoint,
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


            /* STATISTIK */

            if (waitingNumber) {

                waitingNumber.textContent =
                    Number(
                        data.waiting ??
                        data.menunggu ??
                        0
                    );

            }


            if (servingNumber) {

                servingNumber.textContent =
                    Number(
                        data.serving ??
                        data.dilayani ??
                        0
                    );

            }


            if (doneNumber) {

                doneNumber.textContent =
                    Number(
                        data.done ??
                        data.selesai ??
                        0
                    );

            }


            /* TABLE */

            renderTable(
                data.chats || []
            );


        } catch (error) {

            // Abaikan error polling.

        }

    }


    /* =========================================================
       LOAD PERTAMA
    ========================================================= */

    loadCustomerService();


    /* =========================================================
       POLLING 3 DETIK
    ========================================================= */

    setInterval(
        loadCustomerService,
        3000
    );


    /* =========================================================
       TAB KEMBALI AKTIF
    ========================================================= */

    document.addEventListener(
        "visibilitychange",
        function () {

            if (
                !document.hidden
            ) {

                loadCustomerService();

            }

        }
    );


})();