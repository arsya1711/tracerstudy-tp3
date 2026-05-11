"use strict";

/*
|-------------------------------------------------------------------
| MODUL DETAIL PELAMAR AJAX + SWEETALERT2
|-------------------------------------------------------------------
| Script ini menangani interaksi halaman detail pelamar untuk riwayat
| kerja, edit detail identitas, tracer study, upload berkas, kartu
| anggota digital, dan modal keamanan.
| Alur kerja:
| 1. Form detail dan riwayat kerja divalidasi lalu dikirim via AJAX.
| 2. Form tracer dinamis mengikuti aktivitas yang dipilih pengguna.
| 3. Upload dan hapus berkas diproses dengan fetch + SweetAlert2.
| 4. QR code kartu anggota dibuat saat tab terkait dibuka.
| 5. Modal keamanan dijaga agar tidak submit kosong ke halaman.
|
| Tips Debugging:
| - Jika request AJAX gagal 419/403, periksa token CSRF dan session login.
| - Jika QR tidak muncul, periksa library qrcode.min.js di halaman detail.
*/
var KTPelamarDetail = (function () {
    var editDetailModalElement;
    var editDetailModal;
    var editDetailForm;
    var editDetailValidator;
    var addRiwayatModalElement;
    var addRiwayatModal;
    var addRiwayatForm;
    var addRiwayatValidator;
    var editRiwayatModalElement;
    var editRiwayatModal;
    var editRiwayatForm;
    var editRiwayatValidator;
    var uploadInput;
    var selectedBerkasId = "";
    var lamaranTable;
    var qrGenerated = false;

    const csrfToken = document.querySelector('meta[name="csrf-token"]')
        ? document.querySelector('meta[name="csrf-token"]').content
        : "";
    const csrfHeader = document.querySelector('meta[name="csrf-header-name"]')
        ? document.querySelector('meta[name="csrf-header-name"]').content
        : "X-CSRF-TOKEN";

    var csrfCookieName = "csrf_cookie_name";
    var detailConfig = window.pelamarDetailConfig || {};
    var baseUrl = detailConfig.baseUrl ? detailConfig.baseUrl : window.location.origin;
    var urlUpdateDetail = detailConfig.urlUpdateDetail || (baseUrl.replace(/\/$/, "") + "/superadmin/pelamar/update-detail/" + (detailConfig.pelamarId || ""));
    var urlSimpanRiwayat = detailConfig.urlSimpanRiwayat || (baseUrl.replace(/\/$/, "") + "/superadmin/pelamar/simpan-riwayat-kerja");
    var urlUpdateRiwayat = detailConfig.urlUpdateRiwayat || (baseUrl.replace(/\/$/, "") + "/superadmin/pelamar/update-riwayat-kerja");
    var urlHapusRiwayat = detailConfig.urlHapusRiwayat || (baseUrl.replace(/\/$/, "") + "/superadmin/pelamar/hapus-riwayat-kerja");
    var urlSimpanTracer = detailConfig.urlSimpanTracer || (baseUrl.replace(/\/$/, "") + "/superadmin/pelamar/simpan-tracer");
    var urlUploadBerkas = detailConfig.urlUploadBerkas || (baseUrl.replace(/\/$/, "") + "/superadmin/pelamar/upload-berkas");
    var urlHapusBerkas = detailConfig.urlHapusBerkas || (baseUrl.replace(/\/$/, "") + "/superadmin/pelamar/hapus-berkas");
    var accountId = detailConfig.accountId || "";

    // Safe localStorage wrapper to prevent Tracking Prevention errors
    var safeStorage = (function () {
        try {
            var testKey = "__storage_test__";
            localStorage.setItem(testKey, testKey);
            localStorage.removeItem(testKey);
            return localStorage;
        } catch (e) {
            return {
                getItem: function (key) { return null; },
                setItem: function (key, value) {},
                removeItem: function (key) {}
            };
        }
    })();

    /*
    |-------------------------------------------------------------------
    | TOKEN, COOKIE, DAN FETCH AJAX
    |-------------------------------------------------------------------
    | Bagian ini menyiapkan utilitas CSRF dan helper fetch agar seluruh
    | request detail pelamar konsisten dengan modul sebelumnya.
    | Alur kerja: token dibaca dari cookie/meta lalu disisipkan ke request.
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

    var buildFetchOptions = function (method, body, extraHeaders) {
        var currentToken = getCsrfToken();
        var headers = {
            "X-Requested-With": "XMLHttpRequest",
            "Accept": "application/json"
        };

        headers[csrfHeader] = currentToken;
        headers["X-CSRF-TOKEN"] = currentToken;

        if (extraHeaders) {
            Object.keys(extraHeaders).forEach(function (key) {
                headers[key] = extraHeaders[key];
            });
        }

        if (body instanceof FormData) {
            body.delete(csrfHeader);
            body.append(csrfHeader, currentToken);
        }

        return {
            method: method,
            headers: headers,
            body: body || undefined,
            credentials: "same-origin"
        };
    };

    var requestJson = function (url, method, body, extraHeaders) {
        return fetch(url, buildFetchOptions(method, body, extraHeaders))
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
    | ALERT DAN BANTUAN FORM
    |-------------------------------------------------------------------
    | Helper ini dipakai bersama oleh seluruh interaksi detail pelamar.
    | Alur kerja: alert, indikator loading, validasi, dan reset form disatukan.
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

    var showInfoAlert = function (message) {
        Swal.fire({
            text: message,
            icon: "info",
            buttonsStyling: false,
            confirmButtonText: "Oke",
            customClass: {
                confirmButton: "btn btn-primary"
            }
        });
    };

    var showSuccessAndReload = function (message, tabIdToPreserve) {
        // Save active tab to localStorage sebelum reload
        if (tabIdToPreserve) {
            safeStorage.setItem('pelamar_detail_active_tab', tabIdToPreserve);
        } else {
            var activeTab = document.querySelector('.nav-link.active');
            if (activeTab && activeTab.getAttribute('href')) {
                safeStorage.setItem('pelamar_detail_active_tab', activeTab.getAttribute('href'));
            }
        }

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

    var createRiwayatValidator = function (formElement) {
        if (!formElement || typeof FormValidation === "undefined") {
            return null;
        }

        return FormValidation.formValidation(formElement, {
            fields: {
                nama_perusahaan: {
                    validators: {
                        notEmpty: { message: "Nama perusahaan wajib diisi." },
                        stringLength: { max: 150, message: "Nama perusahaan maksimal 150 karakter." }
                    }
                },
                jabatan: {
                    validators: {
                        notEmpty: { message: "Jabatan wajib diisi." },
                        stringLength: { max: 100, message: "Jabatan maksimal 100 karakter." }
                    }
                },
                tanggal_mulai: {
                    validators: {
                        notEmpty: { message: "Tanggal mulai wajib diisi." }
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

    var createDetailValidator = function (formElement) {
        if (!formElement || typeof FormValidation === "undefined") {
            return null;
        }

        return FormValidation.formValidation(formElement, {
            fields: {
                nama_lengkap: {
                    validators: {
                        notEmpty: { message: "Nama lengkap wajib diisi." },
                        stringLength: { max: 150, message: "Nama lengkap maksimal 150 karakter." }
                    }
                },
                nomor_telepon: {
                    validators: {
                        stringLength: { max: 20, message: "Nomor telepon maksimal 20 karakter." }
                    }
                },
                nomer_nik: {
                    validators: {
                        stringLength: { max: 20, message: "NIK maksimal 20 karakter." }
                    }
                },
                tempat_lahir: {
                    validators: {
                        stringLength: { max: 100, message: "Tempat lahir maksimal 100 karakter." }
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

    var validateForm = function (validator, formElement) {
        if (validator) {
            return validator.validate();
        }

        return Promise.resolve(formElement.reportValidity() ? "Valid" : "Invalid");
    };

    var toggleTanggalSelesai = function (formElement, isChecked) {
        if (!formElement) {
            return;
        }

        var wrapper = formElement.querySelector(".riwayat-tanggal-selesai-wrapper");
        var input = formElement.querySelector('[name="tanggal_selesai"]');

        if (!wrapper || !input) {
            return;
        }

        if (isChecked) {
            wrapper.classList.add("d-none");
            input.value = "";
            return;
        }

        wrapper.classList.remove("d-none");
    };

    var resetImageInputState = function (formElement) {
        if (!formElement) {
            return;
        }

        var imageInput = formElement.querySelector('[data-kt-detail-photo-input="true"]');
        var imageWrapper;
        var initialImage;
        var placeholderImage;
        var removeInput;
        var fileInput;

        if (!imageInput) {
            return;
        }

        imageWrapper = imageInput.querySelector(".image-input-wrapper");
        initialImage = imageInput.getAttribute("data-image-input-initial") || "";
        placeholderImage = imageInput.getAttribute("data-image-input-placeholder") || "";
        removeInput = imageInput.querySelector('input[name="foto_remove"]');
        fileInput = imageInput.querySelector('input[type="file"][name="foto"]');

        if (imageWrapper) {
            imageWrapper.style.backgroundImage = "url('" + (initialImage || placeholderImage) + "')";
        }

        imageInput.classList.remove("image-input-changed", "image-input-empty");

        if (!initialImage) {
            imageInput.classList.add("image-input-empty");
        }

        if (removeInput) {
            removeInput.value = "";
        }

        if (fileInput) {
            fileInput.value = "";
        }
    };

    var resetFormState = function (formElement, validator) {
        if (!formElement) {
            return;
        }

        formElement.reset();
        resetImageInputState(formElement);

        if (validator && typeof validator.resetForm === "function") {
            validator.resetForm(true);
        }

        toggleTanggalSelesai(formElement, false);
    };

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
                if (modalInstance) {
                    modalInstance.hide();
                }
            }
        });
    };

    /*
    |-------------------------------------------------------------------
    | INISIALISASI DATATABLE RIWAYAT LAMARAN
    |-------------------------------------------------------------------
    | Tabel riwayat lamaran dijadikan DataTables client-side agar lebih
    | mudah dipindai dan diurutkan.
    | Alur kerja: plugin DataTables dipasang jika tabel tersedia.
    */
    var initLamaranTable = function () {
        var tableElement = document.querySelector("#kt_table_riwayat_lamaran");
        var placeholderCell;

        if (!tableElement || typeof $ === "undefined" || typeof $.fn.DataTable === "undefined") {
            return;
        }

        placeholderCell = tableElement.querySelector("tbody td[colspan]");

        if (placeholderCell) {
            return;
        }

        try {
            lamaranTable = $(tableElement).DataTable({
                info: false,
                order: [],
                columnDefs: [
                    { orderable: false, targets: 5 }
                ]
            });
        } catch (error) {
            console.error("Gagal menginisialisasi DataTable riwayat lamaran:", error);
        }
    };

    /*
    |-------------------------------------------------------------------
    | MODAL EDIT DETAIL PELAMAR
    |-------------------------------------------------------------------
    | Bagian ini menangani modal edit identitas pelamar pada sidebar
    | agar perubahan data pengguna dan biodata pelamar bisa disimpan
    | tanpa pindah halaman.
    | Alur kerja: modal memakai validator, SweetAlert, lalu submit AJAX.
    */
    var initEditDetailModal = function () {
        editDetailModalElement = document.getElementById("kt_modal_edit_detail_pelamar");
        editDetailForm = document.getElementById("kt_modal_edit_detail_pelamar_form");

        if (!editDetailModalElement || !editDetailForm || typeof bootstrap === "undefined") {
            return;
        }

        editDetailModal = new bootstrap.Modal(editDetailModalElement);
        editDetailValidator = createDetailValidator(editDetailForm);

        editDetailModalElement.querySelector('[data-kt-pelamar-detail-modal-action="close-edit-detail"]').addEventListener("click", function (event) {
            handleCloseAction(event, editDetailModal);
        });

        editDetailModalElement.querySelector('[data-kt-pelamar-detail-modal-action="cancel-edit-detail"]').addEventListener("click", function (event) {
            handleDiscardAction(event, editDetailModal, editDetailForm, editDetailValidator);
        });

        editDetailForm.addEventListener("submit", function (event) {
            var submitButton = editDetailModalElement.querySelector('[data-kt-pelamar-detail-modal-action="submit-edit-detail"]');

            event.preventDefault();

            validateForm(editDetailValidator, editDetailForm).then(function (status) {
                if (status !== "Valid") {
                    showErrorAlert("Form detail pelamar masih belum valid. Silakan periksa kembali.");
                    return;
                }

                setSubmitState(submitButton, true);

                requestJson(urlUpdateDetail, "POST", new FormData(editDetailForm))
                    .then(function (responseData) {
                        editDetailModal.hide();
                        showSuccessAndReload(responseData.message || "Detail pelamar berhasil diperbarui.");
                    })
                    .catch(function (error) {
                        showErrorAlert(error.message);
                    })
                    .finally(function () {
                        setSubmitState(submitButton, false);
                    });
            });
        });

        editDetailModalElement.addEventListener("hidden.bs.modal", function () {
            resetFormState(editDetailForm, editDetailValidator);
        });
    };

    /*
    |-------------------------------------------------------------------
    | MODAL TAMBAH DAN EDIT RIWAYAT KERJA
    |-------------------------------------------------------------------
    | Bagian ini menyiapkan seluruh event modal riwayat kerja mulai dari
    | toggle checkbox, populate form, validasi, sampai submit AJAX.
    | Alur kerja: modal dihubungkan ke helper umum dan endpoint backend.
    */
    var bindMasihBekerjaToggle = function (formElement) {
        if (!formElement) {
            return;
        }

        var checkbox = formElement.querySelector('[name="masih_bekerja"]');

        if (!checkbox) {
            return;
        }

        checkbox.addEventListener("change", function (event) {
            toggleTanggalSelesai(formElement, event.target.checked);
        });
    };

    var initAddRiwayatModal = function () {
        addRiwayatModalElement = document.getElementById("kt_modal_tambah_riwayat");
        addRiwayatForm = document.getElementById("kt_modal_tambah_riwayat_form");

        if (!addRiwayatModalElement || !addRiwayatForm || typeof bootstrap === "undefined") {
            return;
        }

        addRiwayatModal = new bootstrap.Modal(addRiwayatModalElement);
        addRiwayatValidator = createRiwayatValidator(addRiwayatForm);
        bindMasihBekerjaToggle(addRiwayatForm);

        addRiwayatModalElement.querySelector('[data-kt-pelamar-detail-modal-action="close-add-riwayat"]').addEventListener("click", function (event) {
            handleCloseAction(event, addRiwayatModal);
        });

        addRiwayatModalElement.querySelector('[data-kt-pelamar-detail-modal-action="cancel-add-riwayat"]').addEventListener("click", function (event) {
            handleDiscardAction(event, addRiwayatModal, addRiwayatForm, addRiwayatValidator);
        });

        addRiwayatForm.addEventListener("submit", function (event) {
            var submitButton = addRiwayatModalElement.querySelector('[data-kt-pelamar-detail-modal-action="submit-add-riwayat"]');

            event.preventDefault();

            validateForm(addRiwayatValidator, addRiwayatForm).then(function (status) {
                if (status !== "Valid") {
                    showErrorAlert("Form riwayat kerja masih belum valid. Silakan periksa kembali.");
                    return;
                }

                setSubmitState(submitButton, true);

                requestJson(urlSimpanRiwayat, "POST", new FormData(addRiwayatForm))
                    .then(function (responseData) {
                        resetFormState(addRiwayatForm, addRiwayatValidator);
                        addRiwayatModal.hide();
                        showSuccessAndReload(responseData.message || "Riwayat kerja berhasil disimpan.", "#kt_user_view_overview_tab");
                    })
                    .catch(function (error) {
                        showErrorAlert(error.message);
                    })
                    .finally(function () {
                        setSubmitState(submitButton, false);
                    });
            });
        });

        addRiwayatModalElement.addEventListener("hidden.bs.modal", function () {
            resetFormState(addRiwayatForm, addRiwayatValidator);
        });
    };

    var populateEditRiwayatForm = function (rowElement) {
        if (!rowElement || !editRiwayatForm) {
            return;
        }

        var tanggalSelesai = rowElement.getAttribute("data-selesai") || "";
        var checkbox = editRiwayatForm.querySelector('[name="masih_bekerja"]');
        var masihBekerja = tanggalSelesai === "" || rowElement.getAttribute("data-masih_bekerja") === "1";

        editRiwayatForm.querySelector('[name="id_riwayat"]').value = rowElement.getAttribute("data-id") || "";
        editRiwayatForm.querySelector('[name="nama_perusahaan"]').value = rowElement.getAttribute("data-perusahaan") || "";
        editRiwayatForm.querySelector('[name="posisi_jabatan"]').value = rowElement.getAttribute("data-posisi") || "";
        editRiwayatForm.querySelector('[name="bidang_usaha"]').value = rowElement.getAttribute("data-bidang") || "";
        editRiwayatForm.querySelector('[name="lokasi"]').value = rowElement.getAttribute("data-lokasi") || "";
        editRiwayatForm.querySelector('[name="tanggal_mulai"]').value = rowElement.getAttribute("data-mulai") || "";
        editRiwayatForm.querySelector('[name="tanggal_selesai"]').value = tanggalSelesai;
        editRiwayatForm.querySelector('[name="keterangan"]').value = rowElement.getAttribute("data-keterangan") || "";

        if (checkbox) {
            checkbox.checked = masihBekerja;
            toggleTanggalSelesai(editRiwayatForm, masihBekerja);
        }
    };

    var initEditRiwayatModal = function () {
        editRiwayatModalElement = document.getElementById("kt_modal_edit_riwayat");
        editRiwayatForm = document.getElementById("kt_modal_edit_riwayat_form");

        if (!editRiwayatModalElement || !editRiwayatForm || typeof bootstrap === "undefined") {
            return;
        }

        editRiwayatModal = new bootstrap.Modal(editRiwayatModalElement);
        editRiwayatValidator = createRiwayatValidator(editRiwayatForm);
        bindMasihBekerjaToggle(editRiwayatForm);

        editRiwayatModalElement.querySelector('[data-kt-pelamar-detail-modal-action="close-edit-riwayat"]').addEventListener("click", function (event) {
            handleCloseAction(event, editRiwayatModal);
        });

        editRiwayatModalElement.querySelector('[data-kt-pelamar-detail-modal-action="cancel-edit-riwayat"]').addEventListener("click", function (event) {
            handleDiscardAction(event, editRiwayatModal, editRiwayatForm, editRiwayatValidator);
        });

        editRiwayatForm.addEventListener("submit", function (event) {
            var submitButton = editRiwayatModalElement.querySelector('[data-kt-pelamar-detail-modal-action="submit-edit-riwayat"]');
            var idRiwayat = editRiwayatForm.querySelector('[name="id_riwayat"]').value;

            event.preventDefault();

            if (!idRiwayat) {
                showErrorAlert("Data riwayat kerja yang akan diedit tidak valid.");
                return;
            }

            validateForm(editRiwayatValidator, editRiwayatForm).then(function (status) {
                if (status !== "Valid") {
                    showErrorAlert("Form riwayat kerja masih belum valid. Silakan periksa kembali.");
                    return;
                }

                setSubmitState(submitButton, true);

                requestJson(urlUpdateRiwayat.replace(/\/$/, "") + "/" + idRiwayat, "POST", new FormData(editRiwayatForm))
                    .then(function (responseData) {
                        resetFormState(editRiwayatForm, editRiwayatValidator);
                        editRiwayatModal.hide();
                        showSuccessAndReload(responseData.message || "Riwayat kerja berhasil diperbarui.", "#kt_user_view_overview_tab");
                    })
                    .catch(function (error) {
                        showErrorAlert(error.message);
                    })
                    .finally(function () {
                        setSubmitState(submitButton, false);
                    });
            });
        });

        editRiwayatModalElement.addEventListener("hidden.bs.modal", function () {
            resetFormState(editRiwayatForm, editRiwayatValidator);
        });
    };

    /*
    |-------------------------------------------------------------------
    | AKSI RIWAYAT KERJA DAN BERKAS
    |-------------------------------------------------------------------
    | Event delegation dipakai untuk tombol edit/hapus riwayat dan
    | upload/hapus berkas agar semua aksi tetap bekerja setelah reload.
    | Alur kerja: klik dibaca lalu diarahkan ke modal atau fetch AJAX.
    */
    var handleDeleteRiwayat = function (rowElement) {
        var idRiwayat = rowElement ? rowElement.getAttribute("data-id") : "";
        var namaPerusahaan = rowElement ? (rowElement.getAttribute("data-perusahaan") || "riwayat ini") : "riwayat ini";

        if (!idRiwayat) {
            showErrorAlert("ID riwayat kerja tidak ditemukan.");
            return;
        }

        Swal.fire({
            text: "Apakah Anda yakin ingin menghapus riwayat kerja di " + namaPerusahaan + "?",
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
                return;
            }

            requestJson(urlHapusRiwayat.replace(/\/$/, "") + "/" + idRiwayat, "GET")
                .then(function (responseData) {
                    showSuccessAndReload(responseData.message || "Riwayat kerja berhasil dihapus.", "#kt_user_view_overview_tab");
                })
                .catch(function (error) {
                    showErrorAlert(error.message);
                });
        });
    };

    var initRiwayatActions = function () {
        document.addEventListener("click", function (event) {
            var editButton = event.target.closest('[data-action="edit-riwayat"]');
            var deleteButton = event.target.closest('[data-action="hapus-riwayat"]');
            var rowElement = event.target.closest('[data-id][data-perusahaan]');

            if (editButton && rowElement && editRiwayatModal) {
                event.preventDefault();
                populateEditRiwayatForm(rowElement);
                editRiwayatModal.show();
                return;
            }

            if (deleteButton && rowElement) {
                event.preventDefault();
                handleDeleteRiwayat(rowElement);
            }
        });
    };

    var initBerkasActions = function () {
        uploadInput = document.getElementById("kt_input_upload_berkas");

        document.addEventListener("click", function (event) {
            var uploadButton = event.target.closest('[data-action="upload-berkas"]');
            var deleteButton = event.target.closest('[data-action="hapus-berkas"]');

            if (uploadButton && uploadInput) {
                event.preventDefault();
                selectedBerkasId = uploadButton.getAttribute("data-id") || "";
                uploadInput.value = "";
                uploadInput.click();
                return;
            }

            if (deleteButton) {
                event.preventDefault();

                var idBerkas = deleteButton.getAttribute("data-id") || "";

                if (!idBerkas) {
                    showErrorAlert("ID berkas tidak ditemukan.");
                    return;
                }

                Swal.fire({
                    text: "Apakah Anda yakin ingin menghapus berkas ini?",
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
                        return;
                    }

                    requestJson(urlHapusBerkas.replace(/\/$/, "") + "/" + idBerkas, "GET")
                        .then(function (responseData) {
                            showSuccessAndReload(responseData.message || "Berkas berhasil dihapus.");
                        })
                        .catch(function (error) {
                            showErrorAlert(error.message);
                        });
                });
            }
        });

        if (!uploadInput) {
            return;
        }

        uploadInput.addEventListener("change", function (event) {
            var file = event.target.files && event.target.files[0] ? event.target.files[0] : null;
            var formData;

            if (!selectedBerkasId || !file) {
                return;
            }

            formData = new FormData();
            formData.append("id_berkas", selectedBerkasId);
            formData.append("file", file);

            requestJson(urlUploadBerkas, "POST", formData)
                .then(function (responseData) {
                    showSuccessAndReload(responseData.message || "Berkas berhasil diunggah.");
                })
                .catch(function (error) {
                    showErrorAlert(error.message);
                })
                .finally(function () {
                    selectedBerkasId = "";
                    uploadInput.value = "";
                });
        });
    };

    /*
    |-------------------------------------------------------------------
    | MODAL EDIT TRACER ALUMNI
    |-------------------------------------------------------------------
    | Bagian ini menangani modal tracer alumni agar section form dinamis
    | selalu sesuai aktivitas yang dipilih dan data tracer terkini bisa
    | disimpan melalui AJAX.
    | Alur kerja:
    | 1. Modal dibuka lalu section yang sesuai ditampilkan.
    | 2. Perubahan radio aktivitas mengganti section form dinamis.
    | 3. Submit mengirim FormData ke endpoint simpan-tracer/{idPelamar}.
    */
    var toggleTracerForm = function (slug) {
        ["bekerja", "kuliah", "wirausaha", "belum_bekerja"].forEach(function (sectionSlug) {
            var sectionElement = document.getElementById("kt_tracer_form_" + sectionSlug);

            if (sectionElement) {
                sectionElement.classList.add("d-none");
            }
        });

        if (!slug) {
            return;
        }

        var activeSection = document.getElementById("kt_tracer_form_" + slug);

        if (activeSection) {
            activeSection.classList.remove("d-none");
        }
    };

    var initTracerModal = function () {
        var tracerModalElement = document.getElementById("kt_modal_edit_tracer");
        var tracerForm = document.getElementById("kt_modal_edit_tracer_form");
        var tracerModal;
        var closeButton;
        var cancelButton;
        var submitButton;
        var tracerRadios;

        if (!tracerModalElement || !tracerForm || typeof bootstrap === "undefined") {
            return;
        }

        tracerModal = new bootstrap.Modal(tracerModalElement);
        closeButton = tracerModalElement.querySelector('[data-kt-tracer-modal-action="close"]');
        cancelButton = tracerModalElement.querySelector('[data-kt-tracer-modal-action="cancel"]');
        submitButton = tracerModalElement.querySelector('[data-kt-tracer-modal-action="submit"]');
        tracerRadios = tracerModalElement.querySelectorAll('input[name="id_aktivitas"]');

        tracerModalElement.addEventListener("shown.bs.modal", function () {
            var checked = tracerModalElement.querySelector('input[name="id_aktivitas"]:checked');

            toggleTracerForm(checked ? checked.dataset.slug : "");
        });

        tracerRadios.forEach(function (radio) {
            radio.addEventListener("change", function () {
                toggleTracerForm(this.dataset.slug || "");
            });
        });

        if (closeButton) {
            closeButton.addEventListener("click", function (event) {
                handleCloseAction(event, tracerModal);
            });
        }

        if (cancelButton) {
            cancelButton.addEventListener("click", function (event) {
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
                        tracerForm.reset();
                        tracerModal.hide();
                        return;
                    }

                    showInfoAlert("Form belum dibatalkan");
                });
            });
        }

        tracerForm.addEventListener("submit", function (event) {
            var idPelamarElement = document.querySelector("[data-id-pelamar]");
            var idPelamar = idPelamarElement ? idPelamarElement.dataset.idPelamar : "";
            var checkedAktivitas = tracerModalElement.querySelector('input[name="id_aktivitas"]:checked');
            var tracerUrl;

            event.preventDefault();

            if (!idPelamar) {
                showErrorAlert("ID pelamar tidak ditemukan.");
                return;
            }

            if (!checkedAktivitas) {
                showErrorAlert("Silakan pilih kegiatan saat ini.");
                return;
            }

            tracerUrl = urlSimpanTracer.replace(/\/$/, "") + "/" + idPelamar;
            setSubmitState(submitButton, true);

            requestJson(tracerUrl, "POST", new FormData(tracerForm))
                .then(function (responseData) {
                    showSuccessAndReload(responseData.message || "Data tracer alumni berhasil disimpan.");
                })
                .catch(function (error) {
                    showErrorAlert(error.message);
                })
                .finally(function () {
                    setSubmitState(submitButton, false);
                });
        });

        tracerModalElement.addEventListener("hidden.bs.modal", function () {
            tracerForm.reset();
            toggleTracerForm("");
        });
    };

    /*
    |-------------------------------------------------------------------
    | MODAL KEAMANAN
    |-------------------------------------------------------------------
    | Modal update email dan password dihalangi dari submit default agar
    | halaman tidak me-reload saat endpoint backend khusus belum tersedia.
    | Alur kerja: close/discard memakai SweetAlert, submit menampilkan info.
    */
    var initSecurityModals = function () {
        var modalPairs = [
            {
                element: document.getElementById("kt_modal_update_email"),
                form: document.getElementById("kt_modal_update_email_form"),
                closeSelector: '[data-kt-pelamar-detail-modal-action="close-update-email"]',
                cancelSelector: '[data-kt-pelamar-detail-modal-action="cancel-update-email"]'
            },
            {
                element: document.getElementById("kt_modal_update_password"),
                form: document.getElementById("kt_modal_update_password_form"),
                closeSelector: '[data-kt-pelamar-detail-modal-action="close-update-password"]',
                cancelSelector: '[data-kt-pelamar-detail-modal-action="cancel-update-password"]'
            }
        ];

        if (typeof bootstrap === "undefined") {
            return;
        }

        modalPairs.forEach(function (config) {
            if (!config.element || !config.form) {
                return;
            }

            var modalInstance = new bootstrap.Modal(config.element);
            var closeButton = config.element.querySelector(config.closeSelector);
            var cancelButton = config.element.querySelector(config.cancelSelector);

            if (closeButton) {
                closeButton.addEventListener("click", function (event) {
                    handleCloseAction(event, modalInstance);
                });
            }

            if (cancelButton) {
                cancelButton.addEventListener("click", function (event) {
                    handleDiscardAction(event, modalInstance, config.form, null);
                });
            }

            config.form.addEventListener("submit", function (event) {
                event.preventDefault();
                showInfoAlert("Endpoint perubahan email dan password belum disiapkan pada task ini.");
            });

            config.element.addEventListener("hidden.bs.modal", function () {
                config.form.reset();
            });
        });
    };

    /*
    |-------------------------------------------------------------------
    | KARTU ANGGOTA DIGITAL
    |-------------------------------------------------------------------
    | QR code dibuat saat tab kartu anggota dibuka, dan tombol unduh
    | memakai html2canvas untuk menyimpan kartu sebagai PNG.
    | Alur kerja: render QR satu kali lalu card di-capture saat diminta.
    */
    var renderQrCode = function () {
        var qrContainer = document.getElementById("kt_qrcode");

        if (qrGenerated || !qrContainer || typeof QRCode === "undefined" || !accountId) {
            return;
        }

        qrContainer.innerHTML = "";

        new QRCode(qrContainer, {
            text: accountId,
            width: 80,
            height: 80
        });

        qrGenerated = true;
    };

    var initKartuAnggota = function () {
        var tabTrigger = document.querySelector('[href="#kt_tab_kartu_anggota"]');
        var downloadButton = document.getElementById("kt_unduh_kartu");
        var cardElement = document.getElementById("kt_kartu_anggota");

        if (tabTrigger) {
            tabTrigger.addEventListener("shown.bs.tab", function () {
                renderQrCode();
            });
        }

        if (downloadButton && cardElement) {
            downloadButton.addEventListener("click", function () {
                if (typeof html2canvas === "undefined") {
                    showErrorAlert("Library unduh kartu belum termuat.");
                    return;
                }

                renderQrCode();

                html2canvas(cardElement).then(function (canvas) {
                    var link = document.createElement("a");
                    link.download = "kartu-" + accountId + ".png";
                    link.href = canvas.toDataURL();
                    link.click();
                }).catch(function () {
                    showErrorAlert("Kartu gagal diunduh.");
                });
            });
        }
    };

    var restoreActiveTab = function () {
        var savedTabId = safeStorage.getItem('pelamar_detail_active_tab');

        if (savedTabId) {
            var tabLink = document.querySelector('a.nav-link[href="' + savedTabId + '"]');
            if (tabLink) {
                // Gunakan Bootstrap's Tab API untuk switch ke tab yang disimpan
                if (typeof bootstrap !== "undefined") {
                    var tab = new bootstrap.Tab(tabLink);
                    tab.show();
                }
            }
            // Hapus dari localStorage setelah digunakan
            safeStorage.removeItem('pelamar_detail_active_tab');
        }
    };

    return {
        init: function () {
            restoreActiveTab();
            syncCsrfToken();
            initEditDetailModal();
            initAddRiwayatModal();
            initEditRiwayatModal();
            initRiwayatActions();
            initTracerModal();
            initBerkasActions();
            initSecurityModals();
            initKartuAnggota();
            initLamaranTable();
        }
    };
})();

if (typeof KTUtil !== "undefined") {
    KTUtil.onDOMContentLoaded(function () {
        KTPelamarDetail.init();
    });
} else {
    document.addEventListener("DOMContentLoaded", function () {
        KTPelamarDetail.init();
    });
}
