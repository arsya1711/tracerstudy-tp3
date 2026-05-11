"use strict";

/*
|-------------------------------------------------------------------
| MODUL PELAMAR AJAX + SWEETALERT2 + DATATABLES
|-------------------------------------------------------------------
| Script ini menangani interaksi halaman manajemen pelamar menggunakan
| DataTables client-side, modal Metronic, dan fetch AJAX.
| Alur kerja:
| 1. Tabel diinisialisasi dengan search dan filter gabungan.
| 2. Form tambah dan edit divalidasi lalu dikirim via fetch().
| 3. Hapus satuan dan massal diproses dengan SweetAlert2.
| 4. Toolbar selected mengikuti checkbox yang sedang dipilih user.
|
| Tips Debugging:
| - Jika request AJAX gagal 419/403, periksa token CSRF dan session login.
| - Jika filter tidak bekerja, periksa selector data-kt-user-table-filter.
*/
var KTPelamar = (function () {
    var tableElement;
    var dataTable;
    var searchInput;
    var filterForm;
    var jenisFilterSelect;
    var statusFilterSelect;
    var currentJenisFilter = "";
    var currentStatusFilter = "";
    var addModalElement;
    var addModal;
    var addForm;
    var addValidator;
    var editModalElement;
    var editModal;
    var editForm;
    var editValidator;
    var toolbarBase;
    var toolbarSelected;
    var selectedCountElement;
    var headerCheckbox;

    const csrfToken = document.querySelector('meta[name="csrf-token"]')
        ? document.querySelector('meta[name="csrf-token"]').content
        : "";
    const csrfHeader = document.querySelector('meta[name="csrf-header-name"]')
        ? document.querySelector('meta[name="csrf-header-name"]').content
        : "X-CSRF-TOKEN";

    var csrfCookieName = "csrf_cookie_name";
    var pelamarConfig = window.pelamarConfig || {};
    var baseUrl = pelamarConfig.baseUrl
        ? pelamarConfig.baseUrl
        : window.location.origin;
    var urlSimpan = pelamarConfig.urlSimpan || (baseUrl.replace(/\/$/, "") + "/superadmin/pelamar/simpan");
    var urlUpdate = pelamarConfig.urlUpdate || (baseUrl.replace(/\/$/, "") + "/superadmin/pelamar/update");
    var urlHapus = pelamarConfig.urlHapus || (baseUrl.replace(/\/$/, "") + "/superadmin/pelamar/hapus");
    var urlHapusMassal = pelamarConfig.urlHapusMassal || (baseUrl.replace(/\/$/, "") + "/superadmin/pelamar/hapus-massal");

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
    */
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
    */
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
    | ALERT GENERIK DAN STATE FORM
    |-------------------------------------------------------------------
    | Helper ini dipakai bersama oleh modal tambah, edit, dan aksi tabel
    | agar perilaku alert dan state tombol tetap konsisten.
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
    | VALIDATOR, RESET, DAN MODAL ACTION
    |-------------------------------------------------------------------
    | Bagian ini menyiapkan validasi client-side serta helper close dan
    | discard yang dipakai dua modal pelamar.
    */
    var createValidator = function (formElement, mode) {
        if (!formElement || typeof FormValidation === "undefined") {
            return null;
        }

        var fields = {
            nama_lengkap: {
                validators: {
                    notEmpty: {
                        message: "Nama lengkap wajib diisi."
                    },
                    stringLength: {
                        max: 150,
                        message: "Nama lengkap maksimal 150 karakter."
                    }
                }
            },
            email: {
                validators: {
                    notEmpty: {
                        message: "Email wajib diisi."
                    },
                    emailAddress: {
                        message: "Format email tidak valid."
                    }
                }
            },
            jenis_pelamar: {
                validators: {
                    notEmpty: {
                        message: "Jenis pelamar wajib dipilih."
                    }
                }
            }
        };

        if (mode === "add") {
            fields.kata_sandi = {
                validators: {
                    notEmpty: {
                        message: "Password wajib diisi."
                    },
                    stringLength: {
                        min: 8,
                        message: "Password minimal 8 karakter."
                    }
                }
            };
        }

        return FormValidation.formValidation(formElement, {
            fields: fields,
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

    var resetFormState = function (formElement, validator) {
        if (!formElement) {
            return;
        }

        formElement.reset();

        if (validator && typeof validator.resetForm === "function") {
            validator.resetForm(true);
        }
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
    | PARSING NILAI FILTER DARI KOLOM TABEL
    |-------------------------------------------------------------------
    | Helper ini membaca teks bersih dari kolom badge jenis pelamar dan
    | status aktif agar filter DataTables dapat berjalan akurat.
    */
    var parseCellText = function (html) {
        var parser = document.createElement("div");
        parser.innerHTML = html || "";

        return (parser.textContent || parser.innerText || "").trim();
    };

    var parseJenisValue = function (html) {
        var text = parseCellText(html).toLowerCase();

        if (text === "alumni") {
            return "alumni";
        }

        if (text === "umum") {
            return "umum";
        }

        return "";
    };

    var parseStatusValue = function (html) {
        var text = parseCellText(html).toLowerCase();

        if (text === "aktif") {
            return "1";
        }

        if (text === "nonaktif") {
            return "0";
        }

        return "";
    };

    /*
    |-------------------------------------------------------------------
    | DATATABLES DAN FILTER GABUNGAN
    |-------------------------------------------------------------------
    | Tabel pelamar dipasang dengan pencarian global serta filter gabungan
    | jenis pelamar dan status aktif.
    */
    var initDataTable = function () {
        tableElement = document.querySelector("#kt_table_pelamar");
        searchInput = document.querySelector('[data-kt-user-table-filter="search"]');

        if (!tableElement || typeof $ === "undefined" || typeof $.fn.DataTable === "undefined") {
            return;
        }

        $.fn.dataTable.ext.search.push(function (settings, data) {
            if (!settings.nTable || settings.nTable.id !== "kt_table_pelamar") {
                return true;
            }

            var jenisValue = parseJenisValue(data[2]);
            var statusValue = parseStatusValue(data[4]);

            if (currentJenisFilter !== "" && jenisValue !== currentJenisFilter) {
                return false;
            }

            if (currentStatusFilter !== "" && statusValue !== currentStatusFilter) {
                return false;
            }

            return true;
        });

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

        $(tableElement).on("draw.dt", function () {
            updateSelectionToolbar();
            syncHeaderCheckboxState();
        });
    };

    var initFilters = function () {
        filterForm = document.querySelector('[data-kt-user-table-filter="form"]');

        if (!filterForm) {
            return;
        }

        jenisFilterSelect = filterForm.querySelector('[data-kt-user-table-filter="role"]');
        statusFilterSelect = filterForm.querySelector('[data-kt-user-table-filter="two-step"]');

        var filterButton = filterForm.querySelector('[data-kt-user-table-filter="filter"]');
        var resetButton = filterForm.querySelector('[data-kt-user-table-filter="reset"]');

        if (filterButton) {
            filterButton.addEventListener("click", function (event) {
                event.preventDefault();

                currentJenisFilter = jenisFilterSelect ? (jenisFilterSelect.value || "") : "";
                currentStatusFilter = statusFilterSelect ? (statusFilterSelect.value || "") : "";

                if (dataTable) {
                    dataTable.draw();
                }
            });
        }

        if (resetButton) {
            resetButton.addEventListener("click", function (event) {
                event.preventDefault();

                currentJenisFilter = "";
                currentStatusFilter = "";

                if (jenisFilterSelect) {
                    jenisFilterSelect.value = "";
                }

                if (statusFilterSelect) {
                    statusFilterSelect.value = "";
                }

                if (searchInput) {
                    searchInput.value = "";
                }

                if (typeof $ !== "undefined") {
                    if (jenisFilterSelect) {
                        $(jenisFilterSelect).val("").trigger("change");
                    }

                    if (statusFilterSelect) {
                        $(statusFilterSelect).val("").trigger("change");
                    }
                }

                if (dataTable) {
                    dataTable.search("").draw();
                }
            });
        }
    };

    /*
    |-------------------------------------------------------------------
    | MODAL TAMBAH, EDIT, DAN TOOLBAR SELECTED
    |-------------------------------------------------------------------
    | Bagian ini menghubungkan form modal dengan endpoint AJAX serta
    | menjaga toolbar bulk action mengikuti checkbox yang dicentang.
    */
    var populateEditForm = function (actionElement) {
        if (!actionElement || !editForm) {
            return;
        }

        editForm.querySelector('[name="id_pelamar"]').value = actionElement.getAttribute("data-id") || "";
        editForm.querySelector('[name="nama_lengkap"]').value = actionElement.getAttribute("data-nama") || "";
        editForm.querySelector('[name="email"]').value = actionElement.getAttribute("data-email") || "";
        editForm.querySelector('[name="jenis_pelamar"]').value = actionElement.getAttribute("data-jenis") || "alumni";
    };

    var initAddModal = function () {
        addModalElement = document.getElementById("kt_modal_tambah_pelamar");
        addForm = document.getElementById("kt_modal_tambah_pelamar_form");

        if (!addModalElement || !addForm || typeof bootstrap === "undefined") {
            return;
        }

        addModal = new bootstrap.Modal(addModalElement);
        addValidator = createValidator(addForm, "add");

        addModalElement.querySelector('[data-kt-pelamar-modal-action="close"]').addEventListener("click", function (event) {
            handleCloseAction(event, addModal);
        });

        addModalElement.querySelector('[data-kt-pelamar-modal-action="cancel"]').addEventListener("click", function (event) {
            handleDiscardAction(event, addModal, addForm, addValidator);
        });

        addForm.addEventListener("submit", function (event) {
            var submitButton = addModalElement.querySelector('[data-kt-pelamar-modal-action="submit"]');

            event.preventDefault();

            validateForm(addValidator, addForm).then(function (status) {
                if (status !== "Valid") {
                    showErrorAlert("Form pelamar masih belum valid. Silakan periksa kembali.");
                    return;
                }

                setSubmitState(submitButton, true);

                var formData = new FormData(addForm);

                formData.delete("jenis_pelamar");
                formData.append("jenis_pelamar", document.querySelector('input[name="jenis_pelamar"]:checked').value);

                requestJson(urlSimpan, "POST", formData)
                    .then(function (responseData) {
                        resetFormState(addForm, addValidator);
                        addModal.hide();
                        showSuccessAndReload(responseData.message || "Data pelamar berhasil disimpan.");
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

    var initEditModal = function () {
        editModalElement = document.getElementById("kt_modal_edit_pelamar");
        editForm = document.getElementById("kt_modal_edit_pelamar_form");

        if (!editModalElement || !editForm || typeof bootstrap === "undefined") {
            return;
        }

        editModal = new bootstrap.Modal(editModalElement);
        editValidator = createValidator(editForm, "edit");

        editModalElement.querySelector('[data-kt-pelamar-edit-modal-action="close"]').addEventListener("click", function (event) {
            handleCloseAction(event, editModal);
        });

        editModalElement.querySelector('[data-kt-pelamar-edit-modal-action="cancel"]').addEventListener("click", function (event) {
            handleDiscardAction(event, editModal, editForm, editValidator);
        });

        editForm.addEventListener("submit", function (event) {
            var submitButton = editModalElement.querySelector('[data-kt-pelamar-edit-modal-action="submit"]');
            var idPelamar = editForm.querySelector('[name="id_pelamar"]').value;

            event.preventDefault();

            if (!idPelamar) {
                showErrorAlert("Data pelamar yang akan diedit tidak valid.");
                return;
            }

            validateForm(editValidator, editForm).then(function (status) {
                if (status !== "Valid") {
                    showErrorAlert("Form pelamar masih belum valid. Silakan periksa kembali.");
                    return;
                }

                setSubmitState(submitButton, true);

                requestJson(urlUpdate.replace(/\/$/, "") + "/" + idPelamar, "POST", new FormData(editForm))
                    .then(function (responseData) {
                        resetFormState(editForm, editValidator);
                        editModal.hide();
                        showSuccessAndReload(responseData.message || "Data pelamar berhasil diperbarui.");
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

    var getRowCheckboxes = function () {
        if (!tableElement) {
            return [];
        }

        return Array.prototype.slice.call(tableElement.querySelectorAll("tbody .row-checkbox"));
    };

    var getCheckedRowCheckboxes = function () {
        return getRowCheckboxes().filter(function (checkbox) {
            return checkbox.checked;
        });
    };

    var updateSelectionToolbar = function () {
        var checkedCheckboxes = getCheckedRowCheckboxes();

        if (selectedCountElement) {
            selectedCountElement.textContent = checkedCheckboxes.length;
        }

        if (!toolbarBase || !toolbarSelected) {
            return;
        }

        if (checkedCheckboxes.length > 0) {
            toolbarBase.classList.add("d-none");
            toolbarSelected.classList.remove("d-none");
            return;
        }

        toolbarSelected.classList.add("d-none");
        toolbarBase.classList.remove("d-none");
    };

    var syncHeaderCheckboxState = function () {
        if (!headerCheckbox) {
            return;
        }

        var rowCheckboxes = getRowCheckboxes();
        var checkedCheckboxes = getCheckedRowCheckboxes();

        if (rowCheckboxes.length === 0) {
            headerCheckbox.checked = false;
            headerCheckbox.indeterminate = false;
            return;
        }

        headerCheckbox.checked = checkedCheckboxes.length === rowCheckboxes.length;
        headerCheckbox.indeterminate = checkedCheckboxes.length > 0 && checkedCheckboxes.length < rowCheckboxes.length;
    };

    var handleDeleteRow = function (actionElement, rowElement) {
        var idPelamar = actionElement ? actionElement.getAttribute("data-id") : "";
        var namaPelamar = actionElement ? (actionElement.getAttribute("data-nama") || "data ini") : "data ini";

        if (!idPelamar) {
            showErrorAlert("ID pelamar tidak ditemukan.");
            return;
        }

        Swal.fire({
            text: "Apakah Anda yakin ingin menghapus pelamar " + namaPelamar + "?",
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
                        text: namaPelamar + " tidak dihapus.",
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

            requestJson(urlHapus.replace(/\/$/, "") + "/" + idPelamar, "GET")
                .then(function (responseData) {
                    Swal.fire({
                        text: responseData.message || "Data pelamar berhasil dihapus.",
                        icon: "success",
                        buttonsStyling: false,
                        confirmButtonText: "Oke",
                        customClass: {
                            confirmButton: "btn btn-primary"
                        }
                    }).then(function () {
                        if (dataTable) {
                            dataTable.row($(rowElement)).remove().draw();
                        } else {
                            rowElement.remove();
                        }

                        updateSelectionToolbar();
                        syncHeaderCheckboxState();
                    });
                })
                .catch(function (error) {
                    showErrorAlert(error.message);
                });
        });
    };

    var handleBulkDelete = function () {
        var selectedCheckboxes = getCheckedRowCheckboxes();
        var selectedIds = selectedCheckboxes.map(function (checkbox) {
            return parseInt(checkbox.value, 10);
        }).filter(function (id) {
            return !isNaN(id) && id > 0;
        });

        if (selectedIds.length === 0) {
            showErrorAlert("Pilih minimal satu data pelamar terlebih dahulu.");
            return;
        }

        Swal.fire({
            text: "Apakah Anda yakin ingin menghapus " + selectedIds.length + " data pelamar terpilih?",
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

            var rowElements = selectedCheckboxes.map(function (checkbox) {
                return checkbox.closest("tr");
            });

            requestJson(urlHapusMassal, "POST", JSON.stringify({ ids: selectedIds }), { "Content-Type": "application/json" })
                .then(function (responseData) {
                    Swal.fire({
                        text: responseData.message || "Data pelamar berhasil dihapus secara massal.",
                        icon: "success",
                        buttonsStyling: false,
                        confirmButtonText: "Oke",
                        customClass: {
                            confirmButton: "btn btn-primary"
                        }
                    }).then(function () {
                        if (dataTable) {
                            dataTable.rows($(rowElements)).remove().draw(false);
                        } else {
                            rowElements.forEach(function (rowElement) {
                                if (rowElement) {
                                    rowElement.remove();
                                }
                            });
                        }

                        updateSelectionToolbar();
                        syncHeaderCheckboxState();
                    });
                })
                .catch(function (error) {
                    showErrorAlert(error.message);
                });
        });
    };

    var initBulkActions = function () {
        if (!tableElement) {
            tableElement = document.querySelector("#kt_table_pelamar");
        }

        if (!tableElement) {
            return;
        }

        toolbarBase = document.querySelector('[data-kt-user-table-toolbar="base"]');
        toolbarSelected = document.querySelector('[data-kt-user-table-toolbar="selected"]');
        selectedCountElement = document.querySelector('[data-kt-user-table-select="selected_count"]');
        headerCheckbox = tableElement.querySelector('thead input[data-kt-check="true"]');

        var deleteSelectedButton = document.querySelector('[data-kt-user-table-select="delete_selected"]');

        if (headerCheckbox) {
            headerCheckbox.addEventListener("change", function (event) {
                getRowCheckboxes().forEach(function (checkbox) {
                    checkbox.checked = event.target.checked;
                });

                updateSelectionToolbar();
                syncHeaderCheckboxState();
            });
        }

        tableElement.addEventListener("change", function (event) {
            if (!event.target.classList.contains("row-checkbox")) {
                return;
            }

            updateSelectionToolbar();
            syncHeaderCheckboxState();
        });

        if (deleteSelectedButton) {
            deleteSelectedButton.addEventListener("click", function (event) {
                event.preventDefault();
                handleBulkDelete();
            });
        }

        updateSelectionToolbar();
        syncHeaderCheckboxState();
    };

    var initTableActions = function () {
        if (!tableElement) {
            tableElement = document.querySelector("#kt_table_pelamar");
        }

        if (!tableElement) {
            return;
        }

        tableElement.addEventListener("click", function (event) {
            var editButton = event.target.closest('[data-action="edit-pelamar"]');
            var deleteButton = event.target.closest('[data-action="hapus-pelamar"]');
            var rowElement = event.target.closest("tr");

            if (editButton && rowElement && editModal) {
                event.preventDefault();
                populateEditForm(editButton);
                editModal.show();
                return;
            }

            if (deleteButton && rowElement) {
                event.preventDefault();
                handleDeleteRow(deleteButton, rowElement);
            }
        });
    };

    return {
        init: function () {
            syncCsrfToken();
            initDataTable();
            initFilters();
            initAddModal();
            initEditModal();
            initBulkActions();
            initTableActions();
        }
    };
})();

if (typeof KTUtil !== "undefined") {
    KTUtil.onDOMContentLoaded(function () {
        KTPelamar.init();
    });
} else {
    document.addEventListener("DOMContentLoaded", function () {
        KTPelamar.init();
    });
}
