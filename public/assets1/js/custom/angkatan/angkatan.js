"use strict";

/*
|-------------------------------------------------------------------
| MODUL ANGKATAN AJAX + SWEETALERT2 + DATATABLES
|-------------------------------------------------------------------
| Script ini menangani interaksi halaman master tahun angkatan
| menggunakan DataTables client-side, modal Metronic, dan fetch AJAX.
| Alur kerja:
| 1. Tabel diinisialisasi dengan pencarian client-side.
| 2. Form tambah dan edit divalidasi lalu dikirim via fetch().
| 3. SweetAlert2 dipakai untuk close, discard, sukses, dan gagal.
| 4. Hapus data dilakukan dengan soft delete dan row dihapus dari tabel.
|
| Tips Debugging:
| - Jika request AJAX gagal 419/403, periksa token CSRF dan session login.
| - Jika modal tidak bereaksi, periksa id modal dan bootstrap bundle.
*/
var KTAngkatan = (function () {
    var tableElement;
    var dataTable;
    var searchInput;
    var addModalElement;
    var addModal;
    var addForm;
    var addValidator;
    var editModalElement;
    var editModal;
    var editForm;
    var editValidator;

    const csrfToken = document.querySelector('meta[name="csrf-token"]')
        ? document.querySelector('meta[name="csrf-token"]').content
        : "";
    const csrfHeader = document.querySelector('meta[name="csrf-header-name"]')
        ? document.querySelector('meta[name="csrf-header-name"]').content
        : "X-CSRF-TOKEN";

    var csrfCookieName = "csrf_cookie_name";
    var angkatanConfig = window.angkatanConfig || {};
    var baseUrl = angkatanConfig.baseUrl
        ? angkatanConfig.baseUrl
        : window.location.origin;
    var urlSimpan = angkatanConfig.urlSimpan || (baseUrl.replace(/\/$/, "") + "/superadmin/angkatan/simpan");
    var urlUpdate = angkatanConfig.urlUpdate || (baseUrl.replace(/\/$/, "") + "/superadmin/angkatan/update");
    var urlHapus = angkatanConfig.urlHapus || (baseUrl.replace(/\/$/, "") + "/superadmin/angkatan/hapus");

    /*
    |-------------------------------------------------------------------
    | PENGAMBILAN TOKEN CSRF TERBARU
    |-------------------------------------------------------------------
    | Method ini menjaga token fetch tetap mengikuti nilai terbaru pada
    | cookie CI4 jika token diregenerasi setelah request sebelumnya.
    | Alur kerja: baca cookie, fallback ke meta tag, lalu pakai di fetch.
    |
    | Tips Debugging:
    | - Jika token kosong, periksa meta csrf-token pada partial head.
    */
    var getCsrfToken = function () {
        var cookieToken = getCookieValue(csrfCookieName);
        var tokenMeta = document.querySelector('meta[name="csrf-token"]');

        return cookieToken || (tokenMeta ? tokenMeta.content : csrfToken);
    };

    /*
    |-------------------------------------------------------------------
    | PEMBARUAN META TOKEN CSRF
    |-------------------------------------------------------------------
    | Menyinkronkan meta token dengan cookie terbaru agar request AJAX
    | berikutnya tetap memakai token yang valid.
    | Alur kerja: cookie dibaca lalu nilai content meta diperbarui.
    |
    | Tips Debugging:
    | - Jika token tidak berubah, periksa nama cookie pada Security.php.
    */
    var syncCsrfToken = function () {
        var tokenMeta = document.querySelector('meta[name="csrf-token"]');
        var freshToken = getCookieValue(csrfCookieName);

        if (tokenMeta && freshToken) {
            tokenMeta.setAttribute("content", freshToken);
        }
    };

    /*
    |-------------------------------------------------------------------
    | PEMBACAAN COOKIE SEDERHANA
    |-------------------------------------------------------------------
    | Helper ini mengambil nilai cookie berdasarkan nama untuk kebutuhan
    | sinkronisasi token CSRF.
    | Alur kerja: document.cookie dipecah lalu dicocokkan satu per satu.
    |
    | Tips Debugging:
    | - Jika selalu null, periksa apakah cookie csrf di-set oleh server.
    */
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

    /*
    |-------------------------------------------------------------------
    | KONFIGURASI FETCH AJAX
    |-------------------------------------------------------------------
    | Menyiapkan header default AJAX termasuk token CSRF CI4 pada header
    | dan body form agar kompatibel dengan konfigurasi keamanan project.
    | Alur kerja:
    | 1. Token terbaru diambil.
    | 2. Header AJAX dan CSRF dipasang.
    | 3. Jika body FormData, token turut disisipkan ke body.
    |
    | Tips Debugging:
    | - Jika backend menganggap non-AJAX, periksa header X-Requested-With.
    */
    var buildFetchOptions = function (method, body) {
        var currentToken = getCsrfToken();
        var headers = {
            "X-Requested-With": "XMLHttpRequest",
            "Accept": "application/json"
        };

        headers[csrfHeader] = currentToken;
        headers["X-CSRF-TOKEN"] = currentToken;

        if (body instanceof FormData) {
            body.delete(csrfHeader);
            body.append(csrfHeader, currentToken);
        }

        var options = {
            method: method,
            headers: headers,
            credentials: "same-origin"
        };

        if (body) {
            options.body = body;
        }

        return options;
    };

    /*
    |-------------------------------------------------------------------
    | REQUEST JSON TERSTANDAR
    |-------------------------------------------------------------------
    | Method ini mengirim fetch ke backend lalu memastikan response JSON
    | sukses atau gagal bisa diproses secara konsisten.
    | Alur kerja:
    | 1. fetch() dijalankan dengan header standar.
    | 2. Response login redirect dideteksi lalu browser diarahkan ulang.
    | 3. Payload JSON sukses/gagal dikembalikan atau dilempar sebagai error.
    |
    | Tips Debugging:
    | - Jika response bukan JSON, periksa apakah route mengembalikan HTML.
    */
    var requestJson = function (url, method, body) {
        return fetch(url, buildFetchOptions(method, body))
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

    /*
    |-------------------------------------------------------------------
    | ALERT ERROR GENERIK
    |-------------------------------------------------------------------
    | Menampilkan notifikasi error standar ketika request gagal atau
    | validasi client-side belum terpenuhi.
    | Alur kerja: pesan error dipetakan lalu dikirim ke SweetAlert2.
    */
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

    /*
    |-------------------------------------------------------------------
    | ALERT SUKSES DAN RELOAD HALAMAN
    |-------------------------------------------------------------------
    | Setelah tambah atau edit berhasil, halaman direfresh agar DataTables
    | client-side memuat ulang baris dari server secara bersih.
    | Alur kerja: SweetAlert sukses tampil lalu browser reload.
    |
    | Tips Debugging:
    | - Jika data tidak berubah setelah submit, periksa response backend.
    */
    var showSuccessAndReload = function (message) {
        Swal.fire({
            text: message,
            icon: "success",
            buttonsStyling: false,
            confirmButtonText: "Oke",
            customClass: {
                confirmButton: "btn btn-primary"
            }
        }).then(function () {
            window.location.reload();
        });
    };

    /*
    |-------------------------------------------------------------------
    | RESET STATUS TOMBOL SUBMIT
    |-------------------------------------------------------------------
    | Mengembalikan tombol submit modal ke keadaan normal setelah request
    | AJAX selesai dijalankan.
    | Alur kerja: indikator loading dihapus lalu tombol diaktifkan lagi.
    */
    var setSubmitState = function (button, isLoading) {
        if (!button) {
            return;
        }

        if (isLoading) {
            button.setAttribute("data-kt-indicator", "on");
            button.disabled = true;
            return;
        }

        button.removeAttribute("data-kt-indicator");
        button.disabled = false;
    };

    /*
    |-------------------------------------------------------------------
    | VALIDATOR FORM METRONIC
    |-------------------------------------------------------------------
    | Menyiapkan validasi client-side agar user mendapat umpan balik lebih
    | cepat sebelum data dikirim ke backend.
    | Alur kerja: plugin FormValidation diinisialisasi untuk field angkatan.
    |
    | Tips Debugging:
    | - Jika validator null, periksa bundle FormValidation pada assets.
    */
    var createValidator = function (formElement) {
        if (!formElement || typeof FormValidation === "undefined") {
            return null;
        }

        return FormValidation.formValidation(formElement, {
            fields: {
                tahun_lulus: {
                    validators: {
                        notEmpty: {
                            message: "Tahun lulus wajib diisi."
                        },
                        stringLength: {
                            min: 4,
                            max: 4,
                            message: "Tahun lulus harus 4 digit."
                        },
                        regexp: {
                            regexp: /^[1-9][0-9]{3}$/,
                            message: "Tahun lulus harus berupa angka valid."
                        }
                    }
                },
                keterangan: {
                    validators: {
                        stringLength: {
                            max: 100,
                            message: "Keterangan maksimal 100 karakter."
                        }
                    }
                }
            },
            plugins: {
                trigger: new FormValidation.plugins.Trigger(),
                bootstrap: new FormValidation.plugins.Bootstrap5({
                    rowSelector: ".fv-row",
                    eleInvalidClass: "",
                    eleValidClass: ""
                })
            }
        });
    };

    /*
    |-------------------------------------------------------------------
    | EKSEKUSI VALIDASI FORM
    |-------------------------------------------------------------------
    | Menjalankan validator Metronic jika tersedia, lalu fallback ke
    | validasi bawaan browser bila validator plugin belum termuat.
    | Alur kerja: validator.validate() atau reportValidity() dipanggil.
    */
    var validateForm = function (validator, formElement) {
        if (validator) {
            return validator.validate();
        }

        return Promise.resolve(formElement.reportValidity() ? "Valid" : "Invalid");
    };

    /*
    |-------------------------------------------------------------------
    | RESET FORM DAN VALIDATOR
    |-------------------------------------------------------------------
    | Membersihkan field form modal agar saat dibuka lagi tidak membawa
    | data atau state validasi dari aksi sebelumnya.
    | Alur kerja: form direset lalu plugin validasi dibersihkan.
    */
    var resetFormState = function (formElement, validator) {
        if (!formElement) {
            return;
        }

        formElement.reset();

        if (validator && typeof validator.resetForm === "function") {
            validator.resetForm(true);
        }
    };

    /*
    |-------------------------------------------------------------------
    | DIALOG CLOSE MODAL
    |-------------------------------------------------------------------
    | Menampilkan konfirmasi saat user menutup modal dengan tombol X.
    | Alur kerja: jika disetujui modal ditutup, jika batal user tetap di modal.
    */
    var handleCloseAction = function (event, modalInstance) {
        event.preventDefault();

        if (!modalInstance) {
            return;
        }

        Swal.fire({
            text: "Apakah Anda yakin ingin menutup?",
            icon: "warning",
            showCancelButton: true,
            buttonsStyling: false,
            confirmButtonText: "Ya, tutup",
            cancelButtonText: "Tidak, kembali",
            customClass: {
                confirmButton: "btn btn-primary",
                cancelButton: "btn btn-active-light"
            }
        }).then(function (result) {
            if (result.isConfirmed) {
                modalInstance.hide();
            }
        });
    };

    /*
    |-------------------------------------------------------------------
    | DIALOG DISCARD MODAL
    |-------------------------------------------------------------------
    | Menangani pembatalan form dengan SweetAlert2 agar user tidak
    | kehilangan input secara tidak sengaja.
    | Alur kerja:
    | 1. Konfirmasi discard ditampilkan.
    | 2. Jika setuju, form direset lalu modal ditutup.
    | 3. Jika batal, tampilkan pesan bahwa form belum dibatalkan.
    */
    var handleDiscardAction = function (event, modalInstance, formElement, validator) {
        event.preventDefault();

        Swal.fire({
            text: "Apakah Anda yakin ingin membatalkan?",
            icon: "warning",
            showCancelButton: true,
            buttonsStyling: false,
            confirmButtonText: "Ya, batalkan",
            cancelButtonText: "Tidak, kembali",
            customClass: {
                confirmButton: "btn btn-primary",
                cancelButton: "btn btn-active-light"
            }
        }).then(function (result) {
            if (result.isConfirmed) {
                resetFormState(formElement, validator);
                modalInstance.hide();
                return;
            }

            if (result.dismiss === Swal.DismissReason.cancel) {
                Swal.fire({
                    text: "Form Anda belum dibatalkan!",
                    icon: "error",
                    buttonsStyling: false,
                    confirmButtonText: "Oke",
                    customClass: {
                        confirmButton: "btn btn-primary"
                    }
                });
            }
        });
    };

    /*
    |-------------------------------------------------------------------
    | PENGISIAN FORM EDIT DARI BARIS TABEL
    |-------------------------------------------------------------------
    | Saat tombol edit diklik, field modal edit diisi otomatis dari
    | data attribute baris angkatan yang dipilih.
    | Alur kerja: dataset row dibaca lalu dimasukkan ke input form edit.
    |
    | Tips Debugging:
    | - Jika field kosong, periksa data-id, data-tahun, dan data-keterangan.
    */
    var populateEditForm = function (rowElement) {
        if (!rowElement || !editForm) {
            return;
        }

        editForm.querySelector('[name="id_angkatan"]').value = rowElement.getAttribute("data-id") || "";
        editForm.querySelector('[name="tahun_lulus"]').value = rowElement.getAttribute("data-tahun") || "";
        editForm.querySelector('[name="keterangan"]').value = rowElement.getAttribute("data-keterangan") || "";
    };

    /*
    |-------------------------------------------------------------------
    | INISIALISASI DATATABLES
    |-------------------------------------------------------------------
    | Menjadikan tabel angkatan dapat dicari dan diurutkan secara
    | client-side dengan pengecualian kolom checkbox dan aksi.
    | Alur kerja: DataTables dipasang lalu input search dihubungkan.
    |
    | Tips Debugging:
    | - Jika tabel tidak berubah, periksa selector #kt_angkatan_table.
    */
    var initDataTable = function () {
        tableElement = document.querySelector("#kt_angkatan_table");
        searchInput = document.querySelector('[data-kt-angkatan-filter="search"]');

        if (!tableElement || typeof $ === "undefined" || typeof $.fn.DataTable === "undefined") {
            return;
        }

        dataTable = $(tableElement).DataTable({
            info: false,
            order: [],
            columnDefs: [
                { orderable: false, targets: 0 },
                { orderable: false, targets: 4 }
            ]
        });

        if (searchInput) {
            searchInput.addEventListener("keyup", function (event) {
                dataTable.search(event.target.value).draw();
            });
        }
    };

    /*
    |-------------------------------------------------------------------
    | MODAL TAMBAH ANGKATAN
    |-------------------------------------------------------------------
    | Bagian ini memasang seluruh event modal tambah mulai dari close,
    | discard, validasi, sampai submit AJAX.
    | Alur kerja: event button dan submit form dihubungkan ke helper AJAX.
    */
    var initAddModal = function () {
        addModalElement = document.getElementById("kt_modal_tambah_angkatan");
        addForm = document.getElementById("kt_modal_tambah_angkatan_form");

        if (!addModalElement || !addForm || typeof bootstrap === "undefined") {
            return;
        }

        addModal = new bootstrap.Modal(addModalElement);
        addValidator = createValidator(addForm);

        addModalElement.querySelector('[data-kt-angkatan-modal-action="close"]').addEventListener("click", function (event) {
            handleCloseAction(event, addModal);
        });

        addModalElement.querySelector('[data-kt-angkatan-modal-action="cancel"]').addEventListener("click", function (event) {
            handleDiscardAction(event, addModal, addForm, addValidator);
        });

        addForm.addEventListener("submit", function (event) {
            var submitButton = addModalElement.querySelector('[data-kt-angkatan-modal-action="submit"]');

            event.preventDefault();

            validateForm(addValidator, addForm).then(function (status) {
                if (status !== "Valid") {
                    showErrorAlert("Form angkatan masih belum valid. Silakan periksa kembali.");
                    return;
                }

                setSubmitState(submitButton, true);

                requestJson(
                    urlSimpan,
                    "POST",
                    new FormData(addForm)
                )
                    .then(function (responseData) {
                        resetFormState(addForm, addValidator);
                        addModal.hide();
                        showSuccessAndReload(responseData.message || "Data angkatan berhasil disimpan.");
                    })
                    .catch(function (error) {
                        showErrorAlert(error.message);
                    })
                    .finally(function () {
                        setSubmitState(submitButton, false);
                    });
            });
        });

        addModalElement.addEventListener("hidden.bs.modal", function () {
            resetFormState(addForm, addValidator);
        });
    };

    /*
    |-------------------------------------------------------------------
    | MODAL EDIT ANGKATAN
    |-------------------------------------------------------------------
    | Bagian ini menyiapkan event modal edit agar data baris terpilih bisa
    | diubah dan dikirim ke backend melalui AJAX.
    | Alur kerja: form dipopulasi dari row, lalu submit POST ke endpoint edit.
    */
    var initEditModal = function () {
        editModalElement = document.getElementById("kt_modal_edit_angkatan");
        editForm = document.getElementById("kt_modal_edit_angkatan_form");

        if (!editModalElement || !editForm || typeof bootstrap === "undefined") {
            return;
        }

        editModal = new bootstrap.Modal(editModalElement);
        editValidator = createValidator(editForm);

        editModalElement.querySelector('[data-kt-angkatan-edit-modal-action="close"]').addEventListener("click", function (event) {
            handleCloseAction(event, editModal);
        });

        editModalElement.querySelector('[data-kt-angkatan-edit-modal-action="cancel"]').addEventListener("click", function (event) {
            handleDiscardAction(event, editModal, editForm, editValidator);
        });

        editForm.addEventListener("submit", function (event) {
            var submitButton = editModalElement.querySelector('[data-kt-angkatan-edit-modal-action="submit"]');
            var idAngkatan = editForm.querySelector('[name="id_angkatan"]').value;

            event.preventDefault();

            if (!idAngkatan) {
                showErrorAlert("Data angkatan yang akan diedit tidak valid.");
                return;
            }

            validateForm(editValidator, editForm).then(function (status) {
                if (status !== "Valid") {
                    showErrorAlert("Form angkatan masih belum valid. Silakan periksa kembali.");
                    return;
                }

                setSubmitState(submitButton, true);

                requestJson(
                    urlUpdate.replace(/\/$/, "") + "/" + idAngkatan,
                    "POST",
                    new FormData(editForm)
                )
                    .then(function (responseData) {
                        resetFormState(editForm, editValidator);
                        editModal.hide();
                        showSuccessAndReload(responseData.message || "Data angkatan berhasil diperbarui.");
                    })
                    .catch(function (error) {
                        showErrorAlert(error.message);
                    })
                    .finally(function () {
                        setSubmitState(submitButton, false);
                    });
            });
        });

        editModalElement.addEventListener("hidden.bs.modal", function () {
            resetFormState(editForm, editValidator);
        });
    };

    /*
    |-------------------------------------------------------------------
    | EVENT EDIT DAN HAPUS BARIS
    |-------------------------------------------------------------------
    | Event delegation dipakai agar tombol aksi pada setiap baris tabel
    | tetap bekerja dengan baik bersama DataTables.
    | Alur kerja:
    | 1. Klik edit mem-populate modal dan membukanya.
    | 2. Klik hapus memunculkan konfirmasi lalu menghapus row aktif.
    |
    | Tips Debugging:
    | - Jika tombol tidak aktif, periksa data-kt-angkatan-table-filter.
    */
    var initTableActions = function () {
        if (!tableElement) {
            tableElement = document.querySelector("#kt_angkatan_table");
        }

        if (!tableElement) {
            return;
        }

        tableElement.addEventListener("click", function (event) {
            var editButton = event.target.closest('[data-kt-angkatan-table-filter="edit_row"]');
            var deleteButton = event.target.closest('[data-kt-angkatan-table-filter="delete_row"]');
            var rowElement = event.target.closest("tr");

            if (editButton && rowElement && editModal) {
                populateEditForm(rowElement);
                editModal.show();
                return;
            }

            if (deleteButton && rowElement) {
                handleDeleteRow(rowElement);
            }
        });
    };

    /*
    |-------------------------------------------------------------------
    | PROSES HAPUS DATA ANGKATAN
    |-------------------------------------------------------------------
    | Menjalankan soft delete setelah user mengonfirmasi penghapusan dari
    | dialog SweetAlert2, lalu menghapus baris dari DataTables.
    | Alur kerja:
    | 1. Tahun dan id row diambil dari data attribute.
    | 2. Fetch GET dikirim ke endpoint hapus.
    | 3. Jika sukses, row dihapus dari DataTables tanpa reload halaman.
    */
    var handleDeleteRow = function (rowElement) {
        var idAngkatan = rowElement.getAttribute("data-id");
        var tahunLulus = rowElement.getAttribute("data-tahun") || "data ini";

        if (!idAngkatan) {
            showErrorAlert("ID angkatan tidak ditemukan.");
            return;
        }

        Swal.fire({
            text: "Apakah Anda yakin ingin menghapus tahun lulus " + tahunLulus + "?",
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
                        text: "Tahun lulus " + tahunLulus + " tidak dihapus.",
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

            requestJson(
                urlHapus.replace(/\/$/, "") + "/" + idAngkatan,
                "GET"
            )
                .then(function (responseData) {
                    Swal.fire({
                        text: responseData.message || "Data angkatan berhasil dihapus.",
                        icon: "success",
                        buttonsStyling: false,
                        confirmButtonText: "Oke",
                        customClass: {
                            confirmButton: "btn btn-primary"
                        }
                    }).then(function () {
                        if (dataTable) {
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

    return {
        init: function () {
            syncCsrfToken();
            initDataTable();
            initAddModal();
            initEditModal();
            initTableActions();
        }
    };
})();

if (typeof KTUtil !== "undefined") {
    KTUtil.onDOMContentLoaded(function () {
        KTAngkatan.init();
    });
} else {
    document.addEventListener("DOMContentLoaded", function () {
        KTAngkatan.init();
    });
}
