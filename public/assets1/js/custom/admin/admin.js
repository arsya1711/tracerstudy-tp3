"use strict";

/*
|-------------------------------------------------------------------
| MODUL ADMIN AJAX + SWEETALERT2 + DATATABLES
|-------------------------------------------------------------------
| Script ini menangani pencarian tabel, modal tambah/edit, toggle
| perusahaan, dan hapus data admin via AJAX.
*/
var KTAdmin = (function () {
    var tableElement;
    var dataTable;
    var searchInput;
    var filterForm;
    var roleFilterSelect;
    var statusFilterSelect;
    var currentRoleFilter = "";
    var currentStatusFilter = "";
    var addModalElement;
    var addModal;
    var addForm;
    var addValidator;
    var editModalElement;
    var editModal;
    var editForm;
    var editValidator;
    var headerCheckbox;
    var toolbarBase;
    var toolbarSelected;
    var selectedCountElement;

    const csrfToken = document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').content : "";
    const csrfHeader = document.querySelector('meta[name="csrf-header-name"]') ? document.querySelector('meta[name="csrf-header-name"]').content : "X-CSRF-TOKEN";

    var csrfCookieName = "csrf_cookie_name";
    var adminConfig = window.adminConfig || {};
    var baseUrl = adminConfig.baseUrl ? adminConfig.baseUrl : window.location.origin;
    var urlSimpan = adminConfig.urlSimpan || (baseUrl.replace(/\/$/, "") + "/superadmin/admin/simpan");
    var urlUpdate = adminConfig.urlUpdate || (baseUrl.replace(/\/$/, "") + "/superadmin/admin/update");
    var urlHapus = adminConfig.urlHapus || (baseUrl.replace(/\/$/, "") + "/superadmin/admin/hapus");

    /*
    |-------------------------------------------------------------------
    | HELPER CSRF DAN AJAX
    |-------------------------------------------------------------------
    | Helper ini membaca cookie token, menyinkronkan meta tag, dan
    | membangun fetch AJAX agar sesuai dengan proteksi CI4.
    */
    function getCookieValue(name) {
        var cookies = (document.cookie || "").split(";");
        var i;

        for (i = 0; i < cookies.length; i += 1) {
            var cookie = cookies[i].trim();

            if (cookie.indexOf(name + "=") === 0) {
                return decodeURIComponent(cookie.substring(name.length + 1));
            }
        }

        return null;
    }

    function getCsrfToken() {
        var cookieToken = getCookieValue(csrfCookieName);
        var tokenMeta = document.querySelector('meta[name="csrf-token"]');

        return cookieToken || (tokenMeta ? tokenMeta.content : csrfToken);
    }

    function syncCsrfToken() {
        var tokenMeta = document.querySelector('meta[name="csrf-token"]');
        var freshToken = getCookieValue(csrfCookieName);

        if (tokenMeta && freshToken) {
            tokenMeta.setAttribute("content", freshToken);
        }
    }

    function buildFetchOptions(method, body, extraHeaders) {
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
            credentials: "same-origin",
            body: body || undefined
        };
    }

    function requestJson(url, method, body, extraHeaders) {
        return fetch(url, buildFetchOptions(method, body, extraHeaders))
            .then(function (response) {
                syncCsrfToken();

                if (response.redirected && response.url) {
                    window.location.href = response.url;
                    throw new Error("Sesi Anda telah berakhir. Silakan login kembali.");
                }

                if ((response.headers.get("content-type") || "").indexOf("application/json") === -1) {
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
    }

    function showErrorAlert(message) {
        Swal.fire({
            text: message || "Terjadi kesalahan saat memproses data.",
            icon: "error",
            buttonsStyling: false,
            confirmButtonText: "Tutup",
            customClass: { confirmButton: "btn btn-primary" }
        });
    }

    function showSuccessAndReload(message) {
        Swal.fire({
            text: message,
            icon: "success",
            buttonsStyling: false,
            confirmButtonText: "Oke",
            customClass: { confirmButton: "btn btn-primary" }
        }).then(function () {
            window.location.reload();
        });
    }

    function setSubmitState(button, isLoading) {
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
    }

    /*
    |-------------------------------------------------------------------
    | VALIDATOR DAN STATE FORM
    |-------------------------------------------------------------------
    | Bagian ini menyiapkan validasi client-side serta reset form
    | untuk modal tambah dan edit admin.
    */
    function createValidator(formElement, mode) {
        if (!formElement || typeof FormValidation === "undefined") {
            return null;
        }

        var fields = {
            nama_lengkap: {
                validators: {
                    notEmpty: { message: "Nama lengkap wajib diisi." },
                    stringLength: { max: 150, message: "Nama lengkap maksimal 150 karakter." }
                }
            },
            email: {
                validators: {
                    notEmpty: { message: "Email wajib diisi." },
                    emailAddress: { message: "Format email tidak valid." }
                }
            },
            id_peran: {
                validators: {
                    notEmpty: { message: "Peran wajib dipilih." }
                }
            }
        };

        if (mode === "add") {
            fields.kata_sandi = {
                validators: {
                    notEmpty: { message: "Password wajib diisi." },
                    stringLength: { min: 8, message: "Password minimal 8 karakter." }
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
    }

    function validateForm(validator, formElement) {
        if (validator) {
            return validator.validate();
        }

        return Promise.resolve(formElement.reportValidity() ? "Valid" : "Invalid");
    }

    function resetFormState(formElement, validator) {
        if (!formElement) {
            return;
        }

        formElement.reset();
        resetImageInputState(formElement);

        if (validator && typeof validator.resetForm === "function") {
            validator.resetForm(true);
        }
    }

    /*
    |-------------------------------------------------------------------
    | IMAGE INPUT FOTO ADMIN
    |-------------------------------------------------------------------
    | Helper ini menjaga preview foto pada modal tambah dan edit tetap
    | sinkron saat reset, populate, upload baru, atau remove.
    */
    function resetImageInputState(formElement) {
        var imageInput = formElement ? formElement.querySelector('[data-kt-admin-photo-input="true"]') : null;
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
    }

    function setImageInputInitial(formElement, imageUrl) {
        var imageInput = formElement ? formElement.querySelector('[data-kt-admin-photo-input="true"]') : null;
        var imageWrapper;
        var placeholderImage;
        var finalImage = imageUrl || "";

        if (!imageInput) {
            return;
        }

        imageWrapper = imageInput.querySelector(".image-input-wrapper");
        placeholderImage = imageInput.getAttribute("data-image-input-placeholder") || "";
        imageInput.setAttribute("data-image-input-initial", finalImage);

        if (imageWrapper) {
            imageWrapper.style.backgroundImage = "url('" + (finalImage || placeholderImage) + "')";
        }

        imageInput.classList.remove("image-input-changed", "image-input-empty");

        if (!finalImage) {
            imageInput.classList.add("image-input-empty");
        }
    }

    function bindPhotoInputHandlers() {
        document.querySelectorAll('[data-kt-admin-photo-input="true"]').forEach(function (imageInput) {
            var fileInput;
            var removeInput;
            var removeButton;
            var cancelButton;

            if (imageInput.getAttribute("data-admin-photo-bound") === "1") {
                return;
            }

            fileInput = imageInput.querySelector('input[type="file"][name="foto"]');
            removeInput = imageInput.querySelector('input[name="foto_remove"]');
            removeButton = imageInput.querySelector('[data-kt-image-input-action="remove"]');
            cancelButton = imageInput.querySelector('[data-kt-image-input-action="cancel"]');

            if (fileInput && removeInput) {
                fileInput.addEventListener("change", function () {
                    removeInput.value = "";
                });
            }

            if (removeButton && removeInput) {
                removeButton.addEventListener("click", function () {
                    removeInput.value = "1";
                });
            }

            if (cancelButton && removeInput) {
                cancelButton.addEventListener("click", function () {
                    removeInput.value = "";
                });
            }

            imageInput.setAttribute("data-admin-photo-bound", "1");
        });
    }

    function handleCloseAction(event, modalInstance) {
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
    }

    function handleDiscardAction(event, modalInstance, formElement, validator, callback) {
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

                if (typeof callback === "function") {
                    callback();
                }

                modalInstance.hide();
                return;
            }

            if (result.dismiss === Swal.DismissReason.cancel) {
                Swal.fire({
                    text: "Form Anda belum dibatalkan!",
                    icon: "error",
                    buttonsStyling: false,
                    confirmButtonText: "Oke",
                    customClass: { confirmButton: "btn btn-primary" }
                });
            }
        });
    }

    /*
    |-------------------------------------------------------------------
    | HELPER FILTER DAN TOOLBAR SELECTED
    |-------------------------------------------------------------------
    | Helper ini membaca isi cell tabel untuk filter DataTables serta
    | mengelola visibilitas selected toolbar seperti modul pelamar.
    */
    function parseCellText(html) {
        var parser = document.createElement("div");

        parser.innerHTML = html || "";

        return (parser.textContent || parser.innerText || "").trim();
    }

    function parseRoleValue(html) {
        var text = parseCellText(html).toLowerCase();

        if (text.indexOf("bkk") !== -1) {
            return "admin_bkk";
        }

        if (text.indexOf("sekolah") !== -1) {
            return "admin_sekolah";
        }

        if (text.indexOf("perusahaan") !== -1) {
            return "admin_perusahaan";
        }

        return "";
    }

    function parseStatusValue(html) {
        var text = parseCellText(html).toLowerCase();

        if (text === "aktif") {
            return "1";
        }

        if (text === "nonaktif") {
            return "0";
        }

        return "";
    }

    /*
    |-------------------------------------------------------------------
    | DATATABLES DAN SEARCH
    |-------------------------------------------------------------------
    | Tabel admin memakai DataTables dengan search global dan kolom
    | checkbox serta aksi yang tidak sortable.
    */
    function initDataTable() {
        tableElement = document.querySelector("#kt_admin_table");
        searchInput = document.querySelector('[data-kt-admin-filter="search"]');

        if (!tableElement || typeof $ === "undefined" || typeof $.fn.DataTable === "undefined") {
            return;
        }

        $.fn.dataTable.ext.search.push(function (settings, data) {
            var roleValue;
            var statusValue;

            if (!settings.nTable || settings.nTable.id !== "kt_admin_table") {
                return true;
            }

            roleValue = parseRoleValue(data[2]);
            statusValue = parseStatusValue(data[5]);

            if (currentRoleFilter !== "" && roleValue !== currentRoleFilter) {
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
    }

    function initFilters() {
        var filterButton;
        var resetButton;

        filterForm = document.querySelector('[data-kt-admin-filter="form"]');

        if (!filterForm) {
            return;
        }

        roleFilterSelect = filterForm.querySelector('[data-kt-admin-filter="role"]');
        statusFilterSelect = filterForm.querySelector('[data-kt-admin-filter="two-step"]');
        filterButton = filterForm.querySelector('[data-kt-admin-filter="filter"]');
        resetButton = filterForm.querySelector('[data-kt-admin-filter="reset"]');

        if (filterButton) {
            filterButton.addEventListener("click", function (event) {
                event.preventDefault();

                currentRoleFilter = roleFilterSelect ? (roleFilterSelect.value || "") : "";
                currentStatusFilter = statusFilterSelect ? (statusFilterSelect.value || "") : "";

                if (dataTable) {
                    dataTable.draw();
                }
            });
        }

        if (resetButton) {
            resetButton.addEventListener("click", function (event) {
                event.preventDefault();

                currentRoleFilter = "";
                currentStatusFilter = "";

                if (roleFilterSelect) {
                    roleFilterSelect.value = "";
                }

                if (statusFilterSelect) {
                    statusFilterSelect.value = "";
                }

                if (searchInput) {
                    searchInput.value = "";
                }

                if (typeof $ !== "undefined") {
                    if (roleFilterSelect) {
                        $(roleFilterSelect).val("").trigger("change");
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
    }

    /*
    |-------------------------------------------------------------------
    | TOGGLE FIELD PERUSAHAAN
    |-------------------------------------------------------------------
    | Field perusahaan hanya tampil untuk role admin_perusahaan pada
    | modal tambah dan edit.
    */
    function togglePerusahaanField(fieldId, show) {
        var field = document.getElementById(fieldId);

        if (!field) {
            return;
        }

        if (show) {
            field.classList.remove("d-none");
            return;
        }

        field.classList.add("d-none");
        field.querySelector("select").value = "";
    }

    function syncPerusahaanFieldTambah() {
        var checked = document.querySelector('#kt_modal_tambah_admin input[name="id_peran"]:checked');

        togglePerusahaanField("kt_field_perusahaan_tambah", !!checked && checked.dataset.slug === "admin_perusahaan");
    }

    function syncPerusahaanFieldEdit() {
        var checked = document.querySelector('#kt_modal_edit_admin input[name="id_peran"]:checked');

        togglePerusahaanField("kt_field_perusahaan_edit", !!checked && checked.dataset.slug === "admin_perusahaan");
    }

    function initPeranToggleTambah() {
        document.querySelectorAll('#kt_modal_tambah_admin input[name="id_peran"]').forEach(function (radio) {
            radio.addEventListener("change", function () {
                const field = document.getElementById("kt_field_perusahaan_tambah");

                if (this.dataset.slug === "admin_perusahaan") {
                    field.classList.remove("d-none");
                } else {
                    field.classList.add("d-none");
                    field.querySelector("select").value = "";
                }
            });
        });

        syncPerusahaanFieldTambah();
    }

    function initPeranToggleEdit() {
        document.querySelectorAll('#kt_modal_edit_admin input[name="id_peran"]').forEach(function (radio) {
            radio.addEventListener("change", function () {
                const field = document.getElementById("kt_field_perusahaan_edit");

                if (this.dataset.slug === "admin_perusahaan") {
                    field.classList.remove("d-none");
                } else {
                    field.classList.add("d-none");
                    field.querySelector("select").value = "";
                }
            });
        });

        syncPerusahaanFieldEdit();
    }

    /*
    |-------------------------------------------------------------------
    | POPULATE MODAL EDIT
    |-------------------------------------------------------------------
    | Saat tombol edit diklik, form diisi dari data attribute baris.
    | Jika admin perusahaan, option perusahaan saat ini disuntikkan
    | bila belum ada di dropdown.
    */
    function cleanupInjectedEditOptions() {
        if (!editForm) {
            return;
        }

        Array.prototype.slice.call(editForm.querySelectorAll('option[data-current-linked="true"]')).forEach(function (option) {
            option.remove();
        });
    }

    function ensureEditCompanyOption(rowElement, companyId) {
        var selectElement = editForm ? editForm.querySelector("#kt_select_perusahaan_edit") : null;
        var companyCell = rowElement && rowElement.children.length > 3 ? rowElement.children[3] : null;
        var companyName = companyCell ? (companyCell.textContent || "").trim() : "";

        if (!selectElement || !companyId || selectElement.querySelector('option[value="' + companyId + '"]') || !companyName || companyName === "-") {
            return;
        }

        var option = new Option(companyName, companyId, true, true);
        option.setAttribute("data-current-linked", "true");
        selectElement.add(option);
    }

    function populateEditForm(rowElement) {
        var id = rowElement.getAttribute("data-id") || "";
        var nama = rowElement.getAttribute("data-nama") || "";
        var email = rowElement.getAttribute("data-email") || "";
        var idPeran = rowElement.getAttribute("data-peran") || "";
        var slug = rowElement.getAttribute("data-slug") || "";
        var idPerusahaan = rowElement.getAttribute("data-perusahaan") || "";
        var fotoUrl = rowElement.getAttribute("data-foto-url") || "";
        var fieldPerusahaan = document.getElementById("kt_field_perusahaan_edit");
        var selectPerusahaan = editForm ? editForm.querySelector("#kt_select_perusahaan_edit") : null;

        cleanupInjectedEditOptions();
        resetFormState(editForm, editValidator);

        editForm.querySelector('[name="id_pengguna"]').value = id;
        editForm.querySelector('[name="nama_lengkap"]').value = nama;
        editForm.querySelector('[name="email"]').value = email;
        setImageInputInitial(editForm, fotoUrl);

        Array.prototype.slice.call(editForm.querySelectorAll('input[name="id_peran"]')).forEach(function (radio) {
            radio.checked = radio.value === idPeran;
        });

        if (slug === "admin_perusahaan") {
            ensureEditCompanyOption(rowElement, idPerusahaan);
            fieldPerusahaan.classList.remove("d-none");

            if (selectPerusahaan) {
                selectPerusahaan.value = idPerusahaan;
            }
        } else {
            fieldPerusahaan.classList.add("d-none");

            if (selectPerusahaan) {
                selectPerusahaan.value = "";
            }
        }
    }

    function validateCompanyByRole(formElement, label) {
        var checked = formElement.querySelector('input[name="id_peran"]:checked');
        var select = formElement.querySelector('select[name="id_perusahaan"]');

        if (!checked) {
            showErrorAlert("Peran admin wajib dipilih.");
            return false;
        }

        if (checked.dataset.slug !== "admin_perusahaan") {
            if (select) {
                select.value = "";
            }

            return true;
        }

        if (!select || !select.value) {
            showErrorAlert("Perusahaan wajib dipilih untuk admin perusahaan pada form " + label + ".");
            return false;
        }

        return true;
    }

    /*
    |-------------------------------------------------------------------
    | MODAL TAMBAH DAN EDIT
    |-------------------------------------------------------------------
    | Kedua modal memakai pola validasi, discard, close, dan submit
    | AJAX yang seragam.
    */
    function initAddModal() {
        addModalElement = document.getElementById("kt_modal_tambah_admin");
        addForm = document.getElementById("kt_modal_tambah_admin_form");

        if (!addModalElement || !addForm || typeof bootstrap === "undefined") {
            return;
        }

        addModal = new bootstrap.Modal(addModalElement);
        addValidator = createValidator(addForm, "add");

        addModalElement.querySelector('[data-kt-admin-modal-action="close"]').addEventListener("click", function (event) {
            handleCloseAction(event, addModal);
        });

        addModalElement.querySelector('[data-kt-admin-modal-action="cancel"]').addEventListener("click", function (event) {
            handleDiscardAction(event, addModal, addForm, addValidator, syncPerusahaanFieldTambah);
        });

        addForm.addEventListener("submit", function (event) {
            var submitButton = addModalElement.querySelector('[data-kt-admin-modal-action="submit"]');
            var checked = addForm.querySelector('input[name="id_peran"]:checked');
            var formData;

            event.preventDefault();

            validateForm(addValidator, addForm).then(function (status) {
                if (status !== "Valid") {
                    showErrorAlert("Form admin masih belum valid. Silakan periksa kembali.");
                    return;
                }

                if (!validateCompanyByRole(addForm, "tambah")) {
                    return;
                }

                setSubmitState(submitButton, true);
                formData = new FormData(addForm);
                formData.delete("id_peran");
                formData.append("id_peran", checked ? checked.value : "");

                if (!checked || checked.dataset.slug !== "admin_perusahaan") {
                    formData.delete("id_perusahaan");
                }

                requestJson(urlSimpan, "POST", formData)
                    .then(function (responseData) {
                        resetFormState(addForm, addValidator);
                        syncPerusahaanFieldTambah();
                        addModal.hide();
                        showSuccessAndReload(responseData.message || "Data admin berhasil disimpan.");
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
            setImageInputInitial(addForm, "");
            syncPerusahaanFieldTambah();
        });

        setImageInputInitial(addForm, "");
        initPeranToggleTambah();
    }

    function initEditModal() {
        editModalElement = document.getElementById("kt_modal_edit_admin");
        editForm = document.getElementById("kt_modal_edit_admin_form");

        if (!editModalElement || !editForm || typeof bootstrap === "undefined") {
            return;
        }

        editModal = new bootstrap.Modal(editModalElement);
        editValidator = createValidator(editForm, "edit");

        editModalElement.querySelector('[data-kt-admin-edit-modal-action="close"]').addEventListener("click", function (event) {
            handleCloseAction(event, editModal);
        });

        editModalElement.querySelector('[data-kt-admin-edit-modal-action="cancel"]').addEventListener("click", function (event) {
            handleDiscardAction(event, editModal, editForm, editValidator, function () {
                cleanupInjectedEditOptions();
                syncPerusahaanFieldEdit();
            });
        });

        editForm.addEventListener("submit", function (event) {
            var submitButton = editModalElement.querySelector('[data-kt-admin-edit-modal-action="submit"]');
            var checked = editForm.querySelector('input[name="id_peran"]:checked');
            var idPengguna = editForm.querySelector('[name="id_pengguna"]').value;
            var formData;

            event.preventDefault();

            if (!idPengguna) {
                showErrorAlert("Data admin yang akan diedit tidak valid.");
                return;
            }

            validateForm(editValidator, editForm).then(function (status) {
                if (status !== "Valid") {
                    showErrorAlert("Form admin masih belum valid. Silakan periksa kembali.");
                    return;
                }

                if (!validateCompanyByRole(editForm, "edit")) {
                    return;
                }

                setSubmitState(submitButton, true);
                formData = new FormData(editForm);
                formData.delete("id_peran");
                formData.append("id_peran", checked ? checked.value : "");

                if (!checked || checked.dataset.slug !== "admin_perusahaan") {
                    formData.delete("id_perusahaan");
                }

                requestJson(urlUpdate.replace(/\/$/, "") + "/" + idPengguna, "POST", formData)
                    .then(function (responseData) {
                        cleanupInjectedEditOptions();
                        resetFormState(editForm, editValidator);
                        syncPerusahaanFieldEdit();
                        editModal.hide();
                        showSuccessAndReload(responseData.message || "Data admin berhasil diperbarui.");
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
            cleanupInjectedEditOptions();
            resetFormState(editForm, editValidator);
            setImageInputInitial(editForm, "");
            syncPerusahaanFieldEdit();
        });

        setImageInputInitial(editForm, "");
        initPeranToggleEdit();
    }

    /*
    |-------------------------------------------------------------------
    | CHECKBOX DAN HAPUS DATA
    |-------------------------------------------------------------------
    | Bagian ini menangani checkbox tabel dan proses hapus satu baris
    | tanpa reload halaman.
    */
    function getRowCheckboxes() {
        return tableElement ? Array.prototype.slice.call(tableElement.querySelectorAll("tbody .row-checkbox")) : [];
    }

    function getCheckedRowCheckboxes() {
        return getRowCheckboxes().filter(function (checkbox) {
            return checkbox.checked;
        });
    }

    function updateSelectionToolbar() {
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
    }

    function syncHeaderCheckboxState() {
        var rowCheckboxes;
        var checkedCheckboxes;

        if (!headerCheckbox) {
            return;
        }

        rowCheckboxes = getRowCheckboxes();
        checkedCheckboxes = getCheckedRowCheckboxes();

        if (rowCheckboxes.length === 0) {
            headerCheckbox.checked = false;
            headerCheckbox.indeterminate = false;
            return;
        }

        headerCheckbox.checked = checkedCheckboxes.length === rowCheckboxes.length;
        headerCheckbox.indeterminate = checkedCheckboxes.length > 0 && checkedCheckboxes.length < rowCheckboxes.length;
    }

    function handleBulkDelete() {
        var selectedCheckboxes = getCheckedRowCheckboxes();
        var rowElements = selectedCheckboxes.map(function (checkbox) {
            return checkbox.closest("tr");
        });

        if (rowElements.length === 0) {
            showErrorAlert("Pilih minimal satu data admin terlebih dahulu.");
            return;
        }

        Swal.fire({
            text: "Apakah Anda yakin ingin menghapus " + rowElements.length + " data admin terpilih?",
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

            Promise.all(rowElements.map(function (rowElement) {
                return requestJson(urlHapus.replace(/\/$/, "") + "/" + rowElement.getAttribute("data-id"), "GET");
            }))
                .then(function () {
                    Swal.fire({
                        text: "Data admin berhasil dihapus.",
                        icon: "success",
                        buttonsStyling: false,
                        confirmButtonText: "Oke",
                        customClass: { confirmButton: "btn btn-primary" }
                    }).then(function () {
                        if (dataTable) {
                            dataTable.rows($(rowElements)).remove().draw(false);
                        } else {
                            rowElements.forEach(function (rowElement) {
                                rowElement.remove();
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
    }

    function handleDeleteRow(rowElement) {
        var idAdmin = rowElement ? (rowElement.getAttribute("data-id") || "") : "";
        var namaAdmin = rowElement ? (rowElement.getAttribute("data-nama") || "data ini") : "data ini";

        if (!idAdmin) {
            showErrorAlert("ID admin tidak ditemukan.");
            return;
        }

        Swal.fire({
            text: "Apakah Anda yakin ingin menghapus admin " + namaAdmin + "?",
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
                        text: namaAdmin + " tidak dihapus.",
                        icon: "info",
                        buttonsStyling: false,
                        confirmButtonText: "Oke",
                        customClass: { confirmButton: "btn btn-primary" }
                    });
                }

                return;
            }

            requestJson(urlHapus.replace(/\/$/, "") + "/" + idAdmin, "GET")
                .then(function (responseData) {
                    Swal.fire({
                        text: responseData.message || "Data admin berhasil dihapus.",
                        icon: "success",
                        buttonsStyling: false,
                        confirmButtonText: "Oke",
                        customClass: { confirmButton: "btn btn-primary" }
                    }).then(function () {
                        if (dataTable) {
                            dataTable.row($(rowElement)).remove().draw(false);
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
    }

    function initTableActions() {
        var deleteSelectedButton;

        if (!tableElement) {
            tableElement = document.querySelector("#kt_admin_table");
        }

        if (!tableElement) {
            return;
        }

        headerCheckbox = tableElement.querySelector('thead input[data-kt-check="true"]');
        toolbarBase = document.querySelector('[data-kt-admin-table-toolbar="base"]');
        toolbarSelected = document.querySelector('[data-kt-admin-table-toolbar="selected"]');
        selectedCountElement = document.querySelector('[data-kt-admin-table-select="selected_count"]');
        deleteSelectedButton = document.querySelector('[data-kt-admin-table-select="delete_selected"]');

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
            if (event.target.classList.contains("row-checkbox")) {
                updateSelectionToolbar();
                syncHeaderCheckboxState();
            }
        });

        if (deleteSelectedButton) {
            deleteSelectedButton.addEventListener("click", function (event) {
                event.preventDefault();
                handleBulkDelete();
            });
        }

        tableElement.addEventListener("click", function (event) {
            var editButton = event.target.closest('[data-action="edit-admin"]');
            var deleteButton = event.target.closest('[data-action="hapus-admin"]');
            var rowElement = event.target.closest("tr");

            if (editButton && rowElement && editModal) {
                event.preventDefault();
                populateEditForm(rowElement);
                editModal.show();
                return;
            }

            if (deleteButton && rowElement) {
                event.preventDefault();
                handleDeleteRow(rowElement);
            }
        });

        updateSelectionToolbar();
    }

    return {
        init: function () {
            syncCsrfToken();
            bindPhotoInputHandlers();
            initDataTable();
            initFilters();
            initAddModal();
            initEditModal();
            initTableActions();
        }
    };
})();

if (typeof KTUtil !== "undefined") {
    KTUtil.onDOMContentLoaded(function () {
        KTAdmin.init();
    });
} else {
    document.addEventListener("DOMContentLoaded", function () {
        KTAdmin.init();
    });
}
