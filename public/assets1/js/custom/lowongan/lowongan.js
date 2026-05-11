"use strict";

/*
|-------------------------------------------------------------------
| MODUL LIST LOWONGAN AJAX + SWEETALERT2 + DATATABLES
|-------------------------------------------------------------------
| Script ini menangani interaksi halaman list lowongan dengan
| DataTables client-side dan aksi AJAX untuk tutup/hapus data.
| Alur kerja:
| 1. Tabel diinisialisasi dengan pencarian client-side.
| 2. Tombol tutup memproses update status via AJAX.
| 3. Tombol hapus memproses soft delete via AJAX.
|
| Tips Debugging:
| - Jika request AJAX gagal 419/403, periksa token CSRF dan session login.
| - Jika tombol aksi tidak bekerja, periksa data-id dan data-posisi pada row.
*/
var KTLowongan = (function () {
    var tableElement;
    var dataTable;
    var searchInput;
    var filterForm;
    var perusahaanFilterInput;
    var statusFilterInput;
    var customFilterHandler;

    const csrfToken = document.querySelector('meta[name="csrf-token"]')
        ? document.querySelector('meta[name="csrf-token"]').content
        : "";
    const csrfHeader = document.querySelector('meta[name="csrf-header-name"]')
        ? document.querySelector('meta[name="csrf-header-name"]').content
        : "X-CSRF-TOKEN";

    var csrfCookieName = "csrf_cookie_name";
    var lowonganConfig = window.lowonganConfig || {};
    var urlTutup = lowonganConfig.urlTutup || "";
    var urlHapus = lowonganConfig.urlHapus || "";

    var getCookieValue = function (name) {
        var cookieString = document.cookie || "";
        var cookies = cookieString.split(";");
        var i;

        for (i = 0; i < cookies.length; i += 1) {
            var cookie = cookies[i].trim();

            if (cookie.indexOf(name + "=") === 0) {
                return decodeURIComponent(cookie.substring(name.length + 1));
            }
        }

        return null;
    };

    var getCsrfToken = function () {
        var cookieToken = getCookieValue(csrfCookieName);
        var tokenMeta = document.querySelector('meta[name="csrf-token"]');

        return cookieToken || (tokenMeta ? tokenMeta.content : csrfToken);
    };

    var syncCsrfToken = function () {
        var tokenMeta = document.querySelector('meta[name="csrf-token"]');
        var freshToken = getCookieValue(csrfCookieName);

        if (tokenMeta && freshToken) {
            tokenMeta.setAttribute("content", freshToken);
        }
    };

    var buildFetchOptions = function (method) {
        var currentToken = getCsrfToken();
        var headers = {
            "X-Requested-With": "XMLHttpRequest",
            "Accept": "application/json"
        };

        headers[csrfHeader] = currentToken;
        headers["X-CSRF-TOKEN"] = currentToken;

        return {
            method: method,
            headers: headers,
            credentials: "same-origin"
        };
    };

    var requestJson = function (url, method) {
        return fetch(url, buildFetchOptions(method))
            .then(function (response) {
                syncCsrfToken();

                if (response.redirected && response.url) {
                    window.location.href = response.url;
                    throw new Error("Sesi Anda telah berakhir. Silakan login kembali.");
                }

                var contentType = response.headers.get("content-type") || "";

                if (contentType.indexOf("application/json") === -1) {
                    throw new Error("Respons server tidak valid.");
                }

                return response.json().then(function (responseData) {
                    if (!response.ok || !responseData || responseData.status !== "success") {
                        throw new Error(responseData && responseData.message ? responseData.message : "Terjadi kesalahan pada server.");
                    }

                    return responseData;
                });
            })
            .catch(function (error) {
                syncCsrfToken();
                throw error;
            });
    };

    var showErrorAlert = function (message) {
        Swal.fire({
            text: message || "Terjadi kesalahan saat memproses data.",
            icon: "error",
            buttonsStyling: false,
            confirmButtonText: "Tutup",
            customClass: {
                confirmButton: "btn btn-primary"
            }
        });
    };

    var initDataTable = function () {
        tableElement = document.querySelector("#kt_lowongan_table");
        searchInput = document.querySelector('[data-kt-lowongan-filter="search"]');

        if (!tableElement || typeof $ === "undefined" || typeof $.fn.DataTable === "undefined") {
            return;
        }

        dataTable = $(tableElement).DataTable({
            info: false,
            order: [],
            columnDefs: [
                { orderable: false, targets: 0 },
                { orderable: false, targets: 6 }
            ]
        });

        if (searchInput) {
            searchInput.addEventListener("keyup", function (event) {
                dataTable.search(event.target.value).draw();
            });
        }

        customFilterHandler = function (settings, data, dataIndex) {
            var rowNode;
            var rowPerusahaan;
            var rowStatus;
            var selectedPerusahaan = perusahaanFilterInput ? String(perusahaanFilterInput.value || "").trim().toLowerCase() : "";
            var selectedStatus = statusFilterInput ? String(statusFilterInput.value || "").trim().toLowerCase() : "";

            if (!tableElement || settings.nTable !== tableElement) {
                return true;
            }

            rowNode = dataTable.row(dataIndex).node();

            if (!rowNode) {
                return true;
            }

            rowPerusahaan = String(rowNode.getAttribute("data-perusahaan") || "").trim().toLowerCase();
            rowStatus = String(rowNode.getAttribute("data-status") || "").trim().toLowerCase();

            if (selectedPerusahaan !== "" && rowPerusahaan !== selectedPerusahaan) {
                return false;
            }

            if (selectedStatus !== "" && rowStatus !== selectedStatus) {
                return false;
            }

            return true;
        };

        $.fn.dataTable.ext.search.push(customFilterHandler);
    };

    var applyStatusFilter = function () {
        if (!dataTable) {
            return;
        }

        dataTable.draw();
    };

    var resetStatusFilter = function () {
        if (perusahaanFilterInput) {
            perusahaanFilterInput.value = "";
        }

        if (statusFilterInput) {
            statusFilterInput.value = "";
        }

        if (dataTable) {
            dataTable.draw();
        }
    };

    var initFilters = function () {
        filterForm = document.querySelector('[data-kt-lowongan-table-filter="form"]');

        if (!filterForm) {
            return;
        }

        perusahaanFilterInput = filterForm.querySelector('[data-kt-lowongan-table-filter="perusahaan"]');
        statusFilterInput = filterForm.querySelector('[data-kt-lowongan-table-filter="status"]');

        filterForm.querySelector('[data-kt-lowongan-table-filter="filter"]').addEventListener("click", function (event) {
            event.preventDefault();
            applyStatusFilter();
        });

        filterForm.querySelector('[data-kt-lowongan-table-filter="reset"]').addEventListener("click", function (event) {
            event.preventDefault();
            resetStatusFilter();
        });
    };

    var handleTutupRow = function (rowElement) {
        var idLowongan = rowElement.getAttribute("data-id");
        var posisi = rowElement.getAttribute("data-posisi") || "lowongan ini";

        if (!idLowongan) {
            showErrorAlert("ID lowongan tidak ditemukan.");
            return;
        }

        Swal.fire({
            text: "Apakah Anda yakin ingin menutup lowongan " + posisi + "?",
            icon: "warning",
            showCancelButton: true,
            buttonsStyling: false,
            confirmButtonText: "Ya, tutup",
            cancelButtonText: "Batal",
            customClass: {
                confirmButton: "btn fw-bold btn-info",
                cancelButton: "btn fw-bold btn-active-light-primary"
            }
        }).then(function (result) {
            if (!result.isConfirmed) {
                if (result.dismiss === Swal.DismissReason.cancel) {
                    Swal.fire({
                        text: posisi + " tidak ditutup.",
                        icon: "info",
                        buttonsStyling: false,
                        confirmButtonText: "Oke",
                        customClass: {
                            confirmButton: "btn btn-primary"
                        }
                    });
                }

                return;
            }

            requestJson(urlTutup.replace(/\/$/, "") + "/" + idLowongan, "GET")
                .then(function (responseData) {
                    Swal.fire({
                        text: responseData.message || "Lowongan berhasil ditutup.",
                        icon: "success",
                        buttonsStyling: false,
                        confirmButtonText: "Oke",
                        customClass: {
                            confirmButton: "btn btn-primary"
                        }
                    }).then(function () {
                        window.location.reload();
                    });
                })
                .catch(function (error) {
                    showErrorAlert(error.message);
                });
        });
    };

    var handleDeleteRow = function (rowElement) {
        var idLowongan = rowElement.getAttribute("data-id");
        var posisi = rowElement.getAttribute("data-posisi") || "lowongan ini";

        if (!idLowongan) {
            showErrorAlert("ID lowongan tidak ditemukan.");
            return;
        }

        Swal.fire({
            text: "Apakah Anda yakin ingin menghapus " + posisi + "?",
            icon: "warning",
            showCancelButton: true,
            buttonsStyling: false,
            confirmButtonText: "Ya, hapus",
            cancelButtonText: "Batal",
            customClass: {
                confirmButton: "btn fw-bold btn-danger",
                cancelButton: "btn fw-bold btn-active-light-primary"
            }
        }).then(function (result) {
            if (!result.isConfirmed) {
                if (result.dismiss === Swal.DismissReason.cancel) {
                    Swal.fire({
                        text: posisi + " tidak dihapus.",
                        icon: "info",
                        buttonsStyling: false,
                        confirmButtonText: "Oke",
                        customClass: {
                            confirmButton: "btn btn-primary"
                        }
                    });
                }

                return;
            }

            requestJson(urlHapus.replace(/\/$/, "") + "/" + idLowongan, "GET")
                .then(function (responseData) {
                    Swal.fire({
                        text: responseData.message || "Lowongan berhasil dihapus.",
                        icon: "success",
                        buttonsStyling: false,
                        confirmButtonText: "Oke",
                        customClass: {
                            confirmButton: "btn btn-primary"
                        }
                    }).then(function () {
                        if (dataTable && typeof $ !== "undefined") {
                            dataTable.row($(rowElement)).remove().draw();
                            return;
                        }

                        rowElement.remove();
                    });
                })
                .catch(function (error) {
                    showErrorAlert(error.message);
                });
        });
    };

    var initTableActions = function () {
        if (!tableElement) {
            tableElement = document.querySelector("#kt_lowongan_table");
        }

        if (!tableElement) {
            return;
        }

        tableElement.addEventListener("click", function (event) {
            var tutupButton = event.target.closest('[data-action="tutup-lowongan"]');
            var hapusButton = event.target.closest('[data-action="hapus-lowongan"]');
            var rowElement = event.target.closest("tr");

            if (!rowElement) {
                return;
            }

            if (tutupButton) {
                handleTutupRow(rowElement);
                return;
            }

            if (hapusButton) {
                handleDeleteRow(rowElement);
            }
        });
    };

    return {
        init: function () {
            syncCsrfToken();
            initDataTable();
            initFilters();
            initTableActions();
        }
    };
})();

if (typeof KTUtil !== "undefined") {
    KTUtil.onDOMContentLoaded(function () {
        KTLowongan.init();
    });
} else {
    document.addEventListener("DOMContentLoaded", function () {
        KTLowongan.init();
    });
}
