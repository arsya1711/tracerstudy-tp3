"use strict";

/*
|-------------------------------------------------------------------
| MODUL PERUSAHAAN AJAX + SWEETALERT2 + DATATABLES
|-------------------------------------------------------------------
| Script ini menangani interaksi halaman master perusahaan dengan
| DataTables client-side, modal Metronic, preview logo, dan fetch AJAX.
| Alur kerja:
| 1. Tabel diinisialisasi dengan pencarian client-side.
| 2. Form tambah dan edit divalidasi lalu dikirim via fetch().
| 3. Preview logo ditampilkan sebelum form disubmit.
| 4. Hapus data dilakukan melalui AJAX lalu row dihapus dari tabel.
|
| Tips Debugging:
| - Jika request AJAX gagal 419/403, periksa token CSRF dan session login.
| - Jika preview logo tidak tampil, periksa selector input dan FileReader.
*/
var KTPerusahaan = (function () {
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
    var addLogoInput;
    var editLogoInput;
    var addLogoPreview;
    var editLogoPreview;

    const csrfToken = document.querySelector('meta[name="csrf-token"]')
        ? document.querySelector('meta[name="csrf-token"]').content
        : "";
    const csrfHeader = document.querySelector('meta[name="csrf-header-name"]')
        ? document.querySelector('meta[name="csrf-header-name"]').content
        : "X-CSRF-TOKEN";

    var csrfCookieName = "csrf_cookie_name";
    var perusahaanConfig = window.perusahaanConfig || {};
    var baseUrl = perusahaanConfig.baseUrl
        ? perusahaanConfig.baseUrl
        : window.location.origin;
    var urlSimpan = perusahaanConfig.urlSimpan || (baseUrl.replace(/\/$/, "") + "/superadmin/perusahaan/simpan");
    var urlUpdate = perusahaanConfig.urlUpdate || (baseUrl.replace(/\/$/, "") + "/superadmin/perusahaan/update");
    var urlHapus = perusahaanConfig.urlHapus || (baseUrl.replace(/\/$/, "") + "/superadmin/perusahaan/hapus");
    var blankLogoUrl = perusahaanConfig.blankLogoUrl || (baseUrl.replace(/\/$/, "") + "/assets/media/svg/files/blank-image.svg");

    /*
    |-------------------------------------------------------------------
    | PENGAMBILAN TOKEN CSRF TERBARU
    |-------------------------------------------------------------------
    | Method ini menjaga token fetch tetap mengikuti nilai terbaru pada
    | cookie CI4 jika token diregenerasi setelah request sebelumnya.
    | Alur kerja: baca cookie, fallback ke meta tag, lalu pakai di fetch.
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
    | Alur kerja: plugin FormValidation diinisialisasi untuk field utama.
    */
    var createValidator = function (formElement) {
        if (!formElement || typeof FormValidation === "undefined") {
            return null;
        }

        return FormValidation.formValidation(formElement, {
            fields: {
                nama_perusahaan: {
                    validators: {
                        notEmpty: {
                            message: "Nama perusahaan wajib diisi."
                        },
                        stringLength: {
                            max: 150,
                            message: "Nama perusahaan maksimal 150 karakter."
                        }
                    }
                },
                bidang_usaha: {
                    validators: {
                        stringLength: {
                            max: 100,
                            message: "Bidang usaha maksimal 100 karakter."
                        }
                    }
                },
                kota: {
                    validators: {
                        stringLength: {
                            max: 100,
                            message: "Kota maksimal 100 karakter."
                        }
                    }
                },
                no_telepon: {
                    validators: {
                        stringLength: {
                            max: 20,
                            message: "Nomor telepon maksimal 20 karakter."
                        }
                    }
                },
                email: {
                    validators: {
                        emailAddress: {
                            message: "Format email perusahaan tidak valid."
                        }
                    }
                },
                website: {
                    validators: {
                        stringLength: {
                            max: 150,
                            message: "Website maksimal 150 karakter."
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
    | VALIDASI MINIMAL SATU KERJASAMA
    |-------------------------------------------------------------------
    | Helper ini memastikan form perusahaan memiliki setidaknya satu
    | checkbox kerjasama yang dipilih sebelum request dikirim.
    | Alur kerja: checkbox bernama id_kerjasama[] dihitung dari form aktif.
    */
    var hasSelectedKerjasama = function (formElement) {
        if (!formElement) {
            return false;
        }

        return formElement.querySelectorAll('input[name="id_kerjasama[]"]:checked').length > 0;
    };

    /*
    |-------------------------------------------------------------------
    | SET NILAI CHECKBOX KERJASAMA
    |-------------------------------------------------------------------
    | Helper ini dipakai untuk memulihkan pilihan checkbox kerjasama
    | saat modal edit dibuka dari data row yang dipilih.
    | Alur kerja: semua checkbox direset lalu id yang cocok ditandai.
    */
    var setKerjasamaSelection = function (formElement, selectedIds) {
        if (!formElement) {
            return;
        }

        var checkedIds = Array.isArray(selectedIds) ? selectedIds : [];

        formElement.querySelectorAll('input[name="id_kerjasama[]"]').forEach(function (checkbox) {
            checkbox.checked = checkedIds.indexOf(String(checkbox.value)) !== -1;
        });
    };

    /*
    |-------------------------------------------------------------------
    | RESOLUSI URL LOGO RELATIF
    |-------------------------------------------------------------------
    | Helper ini mengubah path logo relatif dari database menjadi URL
    | absolut yang bisa dipakai oleh elemen preview di browser.
    | Alur kerja: slash dirapikan lalu digabung dengan baseUrl.
    */
    var resolveLogoUrl = function (relativePath) {
        if (!relativePath) {
            return blankLogoUrl;
        }

        if (/^https?:\/\//i.test(relativePath)) {
            return relativePath;
        }

        return baseUrl.replace(/\/$/, "") + "/" + String(relativePath).replace(/^\/+/, "");
    };

    /*
    |-------------------------------------------------------------------
    | RESET PREVIEW LOGO
    |-------------------------------------------------------------------
    | Mengembalikan preview logo modal ke placeholder default ketika form
    | direset atau modal ditutup.
    | Alur kerja: elemen image diarahkan ke blankLogoUrl.
    */
    var resetLogoPreview = function (previewElement, relativePath) {
        if (!previewElement) {
            return;
        }

        previewElement.src = resolveLogoUrl(relativePath || "");
    };

    /*
    |-------------------------------------------------------------------
    | BIND PREVIEW LOGO SAAT FILE DIPILIH
    |-------------------------------------------------------------------
    | Menampilkan preview gambar logo sebelum form dikirim ke server.
    | Alur kerja: FileReader membaca file lalu hasilnya dipasang ke img.
    */
    var bindLogoPreview = function (inputElement, previewElement) {
        if (!inputElement || !previewElement) {
            return;
        }

        inputElement.addEventListener("change", function () {
            var file = this.files[0];

            if (!file) {
                return;
            }

            var reader = new FileReader();

            reader.onload = function (event) {
                previewElement.src = event.target.result;
            };

            reader.readAsDataURL(file);
        });
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
    var handleDiscardAction = function (event, modalInstance, formElement, validator, previewElement, logoPath) {
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
                resetLogoPreview(previewElement, logoPath || "");
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
    | data attribute baris perusahaan yang dipilih.
    | Alur kerja: dataset row dibaca lalu dimasukkan ke input form edit.
    */
    var populateEditForm = function (rowElement) {
        if (!rowElement || !editForm) {
            return;
        }

        var selectedKerjasamaIds = (rowElement.getAttribute("data-kerjasama-ids") || "")
            .split(",")
            .map(function (id) {
                return id.trim();
            })
            .filter(function (id) {
                return id !== "";
            });

        editForm.querySelector('[name="id_perusahaan"]').value = rowElement.getAttribute("data-id") || "";
        editForm.querySelector('[name="nama_perusahaan"]').value = rowElement.getAttribute("data-nama") || "";
        editForm.querySelector('[name="bidang_usaha"]').value = rowElement.getAttribute("data-bidang") || "";
        editForm.querySelector('[name="alamat"]').value = rowElement.getAttribute("data-alamat") || "";
        editForm.querySelector('[name="kota"]').value = rowElement.getAttribute("data-kota") || "";
        editForm.querySelector('[name="no_telepon"]').value = rowElement.getAttribute("data-telepon") || "";
        editForm.querySelector('[name="email"]').value = rowElement.getAttribute("data-email") || "";
        editForm.querySelector('[name="website"]').value = rowElement.getAttribute("data-website") || "";
        setKerjasamaSelection(editForm, selectedKerjasamaIds);
        resetLogoPreview(editLogoPreview, rowElement.getAttribute("data-logo") || "");
    };

    /*
    |-------------------------------------------------------------------
    | INISIALISASI DATATABLES
    |-------------------------------------------------------------------
    | Menjadikan tabel perusahaan dapat dicari dan diurutkan secara
    | client-side dengan pengecualian kolom checkbox dan aksi.
    | Alur kerja: DataTables dipasang lalu input search dihubungkan.
    */
    var initDataTable = function () {
        tableElement = document.querySelector("#kt_perusahaan_table");
        searchInput = document.querySelector('[data-kt-perusahaan-filter="search"]');

        if (!tableElement || typeof $ === "undefined" || typeof $.fn.DataTable === "undefined") {
            return;
        }

        dataTable = $(tableElement).DataTable({
            info: false,
            order: [],
            columnDefs: [
                { orderable: false, targets: 0 },
                { orderable: false, targets: 5 }
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
    | MODAL TAMBAH PERUSAHAAN
    |-------------------------------------------------------------------
    | Bagian ini memasang seluruh event modal tambah mulai dari close,
    | discard, validasi, sampai submit AJAX.
    | Alur kerja: event button dan submit form dihubungkan ke helper AJAX.
    */
    var initAddModal = function () {
        addModalElement = document.getElementById("kt_modal_tambah_perusahaan");
        addForm = document.getElementById("kt_modal_tambah_perusahaan_form");
        addLogoPreview = document.getElementById("kt_logo_preview");

        if (!addModalElement || !addForm || typeof bootstrap === "undefined") {
            return;
        }

        addLogoInput = addForm.querySelector('input[name="logo"]');
        addModal = new bootstrap.Modal(addModalElement);
        addValidator = createValidator(addForm);

        bindLogoPreview(addLogoInput, addLogoPreview);

        addModalElement.querySelector('[data-kt-perusahaan-modal-action="close"]').addEventListener("click", function (event) {
            handleCloseAction(event, addModal);
        });

        addModalElement.querySelector('[data-kt-perusahaan-modal-action="cancel"]').addEventListener("click", function (event) {
            handleDiscardAction(event, addModal, addForm, addValidator, addLogoPreview, "");
        });

        addForm.addEventListener("submit", function (event) {
            var submitButton = addModalElement.querySelector('[data-kt-perusahaan-modal-action="submit"]');

            event.preventDefault();

            validateForm(addValidator, addForm).then(function (status) {
                if (status !== "Valid") {
                    showErrorAlert("Form perusahaan masih belum valid. Silakan periksa kembali.");
                    return;
                }

                if (!hasSelectedKerjasama(addForm)) {
                    showErrorAlert("Minimal satu jenis kerjasama wajib dipilih.");
                    return;
                }

                setSubmitState(submitButton, true);

                requestJson(urlSimpan, "POST", new FormData(addForm))
                    .then(function (responseData) {
                        resetFormState(addForm, addValidator);
                        resetLogoPreview(addLogoPreview, "");
                        addModal.hide();
                        showSuccessAndReload(responseData.message || "Data perusahaan berhasil disimpan.");
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
            resetLogoPreview(addLogoPreview, "");
        });
    };

    /*
    |-------------------------------------------------------------------
    | MODAL EDIT PERUSAHAAN
    |-------------------------------------------------------------------
    | Bagian ini menyiapkan event modal edit agar data baris terpilih bisa
    | diubah dan dikirim ke backend melalui AJAX.
    | Alur kerja: form dipopulasi dari row, lalu submit POST ke endpoint edit.
    */
    var initEditModal = function () {
        editModalElement = document.getElementById("kt_modal_edit_perusahaan");
        editForm = document.getElementById("kt_modal_edit_perusahaan_form");
        editLogoPreview = document.getElementById("kt_logo_preview_edit");

        if (!editModalElement || !editForm || typeof bootstrap === "undefined") {
            return;
        }

        editLogoInput = editForm.querySelector('input[name="logo"]');
        editModal = new bootstrap.Modal(editModalElement);
        editValidator = createValidator(editForm);

        bindLogoPreview(editLogoInput, editLogoPreview);

        editModalElement.querySelector('[data-kt-perusahaan-edit-modal-action="close"]').addEventListener("click", function (event) {
            handleCloseAction(event, editModal);
        });

        editModalElement.querySelector('[data-kt-perusahaan-edit-modal-action="cancel"]').addEventListener("click", function (event) {
            handleDiscardAction(
                event,
                editModal,
                editForm,
                editValidator,
                editLogoPreview,
                editForm.getAttribute("data-current-logo") || ""
            );
        });

        editForm.addEventListener("submit", function (event) {
            var submitButton = editModalElement.querySelector('[data-kt-perusahaan-edit-modal-action="submit"]');
            var idPerusahaan = editForm.querySelector('[name="id_perusahaan"]').value;

            event.preventDefault();

            if (!idPerusahaan) {
                showErrorAlert("Data perusahaan yang akan diedit tidak valid.");
                return;
            }

            validateForm(editValidator, editForm).then(function (status) {
                if (status !== "Valid") {
                    showErrorAlert("Form perusahaan masih belum valid. Silakan periksa kembali.");
                    return;
                }

                if (!hasSelectedKerjasama(editForm)) {
                    showErrorAlert("Minimal satu jenis kerjasama wajib dipilih.");
                    return;
                }

                setSubmitState(submitButton, true);

                requestJson(
                    urlUpdate.replace(/\/$/, "") + "/" + idPerusahaan,
                    "POST",
                    new FormData(editForm)
                )
                    .then(function (responseData) {
                        resetFormState(editForm, editValidator);
                        resetLogoPreview(editLogoPreview, "");
                        editForm.setAttribute("data-current-logo", "");
                        editModal.hide();
                        showSuccessAndReload(responseData.message || "Data perusahaan berhasil diperbarui.");
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
            resetLogoPreview(editLogoPreview, "");
            editForm.setAttribute("data-current-logo", "");
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
    */
    var initTableActions = function () {
        if (!tableElement) {
            tableElement = document.querySelector("#kt_perusahaan_table");
        }

        if (!tableElement) {
            return;
        }

        tableElement.addEventListener("click", function (event) {
            var editButton = event.target.closest('[data-action="edit-perusahaan"]');
            var deleteButton = event.target.closest('[data-action="hapus-perusahaan"]');
            var rowElement = event.target.closest("tr");

            if (editButton && rowElement && editModal) {
                populateEditForm(rowElement);
                editForm.setAttribute("data-current-logo", rowElement.getAttribute("data-logo") || "");
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
    | PROSES HAPUS DATA PERUSAHAAN
    |-------------------------------------------------------------------
    | Menjalankan soft delete setelah user mengonfirmasi penghapusan dari
    | dialog SweetAlert2, lalu menghapus baris dari DataTables.
    | Alur kerja:
    | 1. Nama dan id row diambil dari data attribute.
    | 2. Fetch GET dikirim ke endpoint hapus.
    | 3. Jika sukses, row dihapus dari DataTables tanpa reload halaman.
    */
    var handleDeleteRow = function (rowElement) {
        var idPerusahaan = rowElement.getAttribute("data-id");
        var namaPerusahaan = rowElement.getAttribute("data-nama") || "data ini";

        if (!idPerusahaan) {
            showErrorAlert("ID perusahaan tidak ditemukan.");
            return;
        }

        Swal.fire({
            text: "Apakah Anda yakin ingin menghapus " + namaPerusahaan + "?",
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
                        text: namaPerusahaan + " tidak dihapus.",
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

            requestJson(urlHapus.replace(/\/$/, "") + "/" + idPerusahaan, "GET")
                .then(function (responseData) {
                    Swal.fire({
                        text: responseData.message || "Data perusahaan berhasil dihapus.",
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
        KTPerusahaan.init();
    });
} else {
    document.addEventListener("DOMContentLoaded", function () {
        KTPerusahaan.init();
    });
}
