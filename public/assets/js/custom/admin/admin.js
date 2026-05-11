"use strict";

/*
|-------------------------------------------------------------------
| MODUL MANAJEMEN ADMIN
|-------------------------------------------------------------------
| Script ini menangani tabel admin berbasis DataTables server-side,
| filter jenis admin, modal tambah/edit, aktivasi, nonaktifkan, dan
| hapus massal tanpa reload penuh.
*/
var KTAdminList = (function () {
    var config = window.ktAdminConfig || {};
    var tableElement;
    var dataTable;
    var searchInput;
    var filterForm;
    var toolbarBase;
    var toolbarSelected;
    var selectedCountElement;
    var headerCheckbox;
    var addModalElement;
    var addModal;
    var addForm;
    var editModalElement;
    var editModal;
    var editForm;

    var csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
    var csrfNameMeta = document.querySelector('meta[name="csrf-header-name"]');

    function getCsrfName() {
        return csrfNameMeta ? csrfNameMeta.getAttribute("content") : "csrf_test_name";
    }

    function getCsrfHash() {
        return csrfTokenMeta ? csrfTokenMeta.getAttribute("content") : "";
    }

    function setCsrfHash(hash) {
        if (csrfTokenMeta && hash) {
            csrfTokenMeta.setAttribute("content", hash);
        }
    }

    function escapeHtml(text) {
        return String(text || "")
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function formatDateTime(value) {
        if (!value) {
            return "";
        }

        var parsed = new Date(String(value).replace(" ", "T"));
        if (Number.isNaN(parsed.getTime())) {
            return String(value);
        }

        return parsed.toLocaleString("id-ID", {
            year: "numeric",
            month: "short",
            day: "2-digit",
            hour: "2-digit",
            minute: "2-digit"
        });
    }

    function showAlert(options) {
        if (typeof Swal === "undefined") {
            window.alert(options.text || "");
            return Promise.resolve({ isConfirmed: true });
        }

        return Swal.fire(options);
    }

    function showError(message) {
        return showAlert({
            text: message || "Terjadi kesalahan pada server.",
            icon: "error",
            buttonsStyling: false,
            confirmButtonText: "Tutup",
            customClass: {
                confirmButton: "btn btn-primary"
            }
        });
    }

    function showSuccess(message) {
        return showAlert({
            text: message || "Proses berhasil.",
            icon: "success",
            buttonsStyling: false,
            confirmButtonText: "Oke",
            customClass: {
                confirmButton: "btn btn-primary"
            }
        });
    }

    function setSubmitState(button, loading) {
        if (!button) {
            return;
        }

        if (loading) {
            button.setAttribute("data-kt-indicator", "on");
            button.disabled = true;
            return;
        }

        button.removeAttribute("data-kt-indicator");
        button.disabled = false;
    }

    function requestJson(url, method, body) {
        var headers = {
            "X-Requested-With": "XMLHttpRequest",
            "Accept": "application/json"
        };

        if (body instanceof FormData) {
            body.delete(getCsrfName());
            body.append(getCsrfName(), getCsrfHash());
            body.set("csrf_token", getCsrfHash());
        }

        return fetch(url, {
            method: method,
            headers: headers,
            body: body || null,
            credentials: "same-origin"
        }).then(function (response) {
            if (response.redirected && response.url) {
                window.location.href = response.url;
                throw new Error("Sesi Anda telah berakhir. Silakan login kembali.");
            }

            return response.json().then(function (json) {
                setCsrfHash(json.csrfHash || "");

                if (!response.ok || !json || json.status !== "success") {
                    throw new Error(json && json.message ? json.message : "Terjadi kesalahan pada server.");
                }

                return json;
            });
        });
    }

    function initSelect2() {
        if (typeof $ === "undefined" || typeof $.fn.select2 === "undefined") {
            return;
        }

        $('[data-kt-select2="true"]').each(function () {
            var hideSearch = $(this).data("hide-search") === true;
            var allowClear = $(this).data("allow-clear") === true;

            $(this).select2({
                minimumResultsForSearch: hideSearch ? Infinity : 0,
                allowClear: allowClear,
                dropdownParent: $(this).closest(".modal, .menu")
            });
        });
    }

    function isCompanyRole(role) {
        return role === "admin_dudi" || role === "admin_perusahaan";
    }

    function getSelectedRole(formElement) {
        var checked = formElement ? formElement.querySelector('input[name="jenis_admin"]:checked') : null;
        return checked ? checked.value : "";
    }

    function toggleCompanyField(formElement) {
        var field = formElement ? formElement.querySelector("[data-admin-company-field]") : null;
        var select = field ? field.querySelector('select[name="id_perusahaan"]') : null;
        var shouldShow = isCompanyRole(getSelectedRole(formElement));

        if (!field) {
            return;
        }

        if (shouldShow) {
            field.classList.remove("d-none");
            return;
        }

        field.classList.add("d-none");

        if (select) {
            select.value = "";

            if (typeof $ !== "undefined" && typeof $.fn.select2 !== "undefined") {
                $(select).trigger("change");
            }
        }
    }

    function setPhotoPreview(formElement, imageUrl) {
        var imageInput = formElement ? formElement.querySelector('[data-kt-admin-photo-input="true"]') : null;
        var wrapper;
        var placeholderImage;
        var finalImage = imageUrl || "";

        if (!imageInput) {
            return;
        }

        wrapper = imageInput.querySelector(".image-input-wrapper");
        placeholderImage = imageInput.getAttribute("data-image-input-placeholder") || "";
        imageInput.setAttribute("data-image-input-initial", finalImage);
        imageInput.classList.remove("image-input-empty");

        if (wrapper) {
            wrapper.style.backgroundImage = "url('" + (finalImage || placeholderImage) + "')";
        }

        if (!finalImage) {
            imageInput.classList.add("image-input-empty");
        }
    }

    function resetPhotoInput(formElement) {
        var imageInput = formElement ? formElement.querySelector('[data-kt-admin-photo-input="true"]') : null;
        var removeInput;
        var fileInput;

        if (!imageInput) {
            return;
        }

        removeInput = imageInput.querySelector('input[name="foto_remove"]');
        fileInput = imageInput.querySelector('input[type="file"][name="foto"]');

        setPhotoPreview(formElement, imageInput.getAttribute("data-image-input-initial") || "");

        if (removeInput) {
            removeInput.value = "";
        }

        if (fileInput) {
            fileInput.value = "";
        }
    }

    function bindPhotoInputs() {
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
                    imageInput.classList.remove("image-input-empty");
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

    function resetForm(formElement) {
        if (!formElement) {
            return;
        }

        formElement.reset();
        resetPhotoInput(formElement);
        toggleCompanyField(formElement);

        formElement.querySelectorAll("select").forEach(function (select) {
            if (typeof $ !== "undefined" && typeof $.fn.select2 !== "undefined") {
                $(select).val("").trigger("change");
            }
        });
    }

    function validateForm(formElement, isEdit) {
        var namaInput = formElement.querySelector('[name="nama_lengkap"]');
        var emailInput = formElement.querySelector('[name="email"]');
        var passwordInput = formElement.querySelector('[name="kata_sandi"]');
        var companySelect = formElement.querySelector('[name="id_perusahaan"]');
        var role = getSelectedRole(formElement);

        if (!namaInput || !namaInput.value.trim()) {
            showError("Nama lengkap admin wajib diisi.");
            return false;
        }

        if (!emailInput || !emailInput.value.trim()) {
            showError("Email admin wajib diisi.");
            return false;
        }

        if (emailInput.reportValidity && !emailInput.reportValidity()) {
            return false;
        }

        if (!role) {
            showError("Jenis admin wajib dipilih.");
            return false;
        }

        if (!isEdit && (!passwordInput || passwordInput.value.length < 8)) {
            showError("Kata sandi minimal 8 karakter.");
            return false;
        }

        if (isEdit && passwordInput && passwordInput.value !== "" && passwordInput.value.length < 8) {
            showError("Kata sandi baru minimal 8 karakter.");
            return false;
        }

        if (config.perusahaanAvailable && isCompanyRole(role) && (!companySelect || !companySelect.value)) {
            showError("Pilih perusahaan untuk admin DUDI.");
            return false;
        }

        return true;
    }

    function renderJenisBadge(row) {
        var label = row.nama_peran || row.slug_peran || "-";
        var className = "badge-light-primary";

        if (row.slug_peran === "admin_dudi" || row.slug_peran === "admin_perusahaan") {
            className = "badge-light-info";
        }

        return '<span class="badge ' + className + '">' + escapeHtml(label) + "</span>";
    }

    function renderStatusBadge(statusAktif) {
        if (String(statusAktif) === "1") {
            return '<span class="badge badge-light-success">Aktif</span>';
        }

        return '<span class="badge badge-light-danger">Nonaktif</span>';
    }

    function renderLastLogin(value) {
        if (!value) {
            return '<span class="badge badge-light-warning">Belum login</span>';
        }

        return '<span class="badge badge-light-info">' + escapeHtml(formatDateTime(value)) + "</span>";
    }

    function renderAdminCell(row) {
        return '' +
            '<div class="d-flex align-items-center">' +
                '<div class="symbol symbol-circle symbol-50px overflow-hidden me-3">' +
                    '<div class="symbol-label">' +
                        '<img src="' + escapeHtml(row.foto_url || config.defaultFoto || "") + '" alt="foto admin" class="w-100" />' +
                    "</div>" +
                "</div>" +
                '<div class="d-flex flex-column">' +
                    '<span class="text-gray-800 fw-bold mb-1">' + escapeHtml(row.nama_lengkap || "-") + "</span>" +
                    '<span class="text-muted">' + escapeHtml(row.email || "-") + "</span>" +
                "</div>" +
            "</div>";
    }

    function renderCompanyCell(row) {
        var value = String(row.nama_perusahaan || "").trim();

        if (value === "" || value === "-") {
            return '<span class="text-muted">-</span>';
        }

        return '<span class="fw-semibold text-gray-800">' + escapeHtml(value) + "</span>";
    }

    function renderActions(row) {
        var isActive = String(row.status_aktif) === "1";
        var activateButton =
            '<button type="button" class="btn btn-icon ' + (isActive ? 'btn-light-secondary disabled' : 'btn-active-light-success') + ' w-30px h-30px me-2" data-action="aktivasi-admin" title="' + (isActive ? 'Admin sudah aktif' : 'Aktifkan admin') + '"' + (isActive ? ' disabled' : '') + '>' +
                '<i class="ki-duotone ki-check-circle fs-3"><span class="path1"></span><span class="path2"></span></i>' +
            "</button>";
        var editButton =
            '<button type="button" class="btn btn-icon btn-active-light-warning w-30px h-30px me-2" data-action="edit-admin" title="Edit admin">' +
                '<i class="ki-duotone ki-pencil fs-3"><span class="path1"></span><span class="path2"></span></i>' +
            "</button>";
        var disableButton =
            '<button type="button" class="btn btn-icon ' + (isActive ? 'btn-active-light-danger' : 'btn-light-secondary disabled') + ' w-30px h-30px" data-action="hapus-admin" title="' + (isActive ? 'Nonaktifkan admin' : 'Admin sudah nonaktif') + '"' + (isActive ? '' : ' disabled') + '>' +
                '<i class="ki-duotone ki-trash fs-3"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i>' +
            "</button>";

        return '<div class="text-end">' + activateButton + editButton + disableButton + "</div>";
    }

    function getFilterValue(selector) {
        var element = document.querySelector(selector);
        return element ? element.value : "";
    }

    function getRowDataFromAction(buttonElement) {
        var row = dataTable.row($(buttonElement).closest("tr"));

        if (!row.data() && $(buttonElement).closest("tr").hasClass("child")) {
            row = dataTable.row($(buttonElement).closest("tr").prev());
        }

        return row.data();
    }

    function getRowCheckboxes() {
        return tableElement ? Array.from(tableElement.querySelectorAll('tbody .form-check-input-row')) : [];
    }

    function getSelectedIds() {
        return getRowCheckboxes().filter(function (checkbox) {
            return checkbox.checked;
        }).map(function (checkbox) {
            return checkbox.value;
        });
    }

    function updateSelectedToolbar() {
        var selectedIds = getSelectedIds();

        if (selectedCountElement) {
            selectedCountElement.textContent = String(selectedIds.length);
        }

        if (!toolbarBase || !toolbarSelected) {
            return;
        }

        if (selectedIds.length > 0) {
            toolbarBase.classList.add("d-none");
            toolbarSelected.classList.remove("d-none");
            return;
        }

        toolbarSelected.classList.add("d-none");
        toolbarBase.classList.remove("d-none");
    }

    function syncHeaderCheckboxState() {
        var rowCheckboxes = getRowCheckboxes();
        var checkedCount = rowCheckboxes.filter(function (checkbox) {
            return checkbox.checked;
        }).length;

        if (!headerCheckbox) {
            return;
        }

        if (rowCheckboxes.length === 0) {
            headerCheckbox.checked = false;
            headerCheckbox.indeterminate = false;
            return;
        }

        headerCheckbox.checked = checkedCount === rowCheckboxes.length;
        headerCheckbox.indeterminate = checkedCount > 0 && checkedCount < rowCheckboxes.length;
    }

    function reloadTable() {
        if (dataTable) {
            dataTable.ajax.reload(null, false);
        }
    }

    function initDataTable() {
        tableElement = document.querySelector("#kt_table_admin");
        searchInput = document.querySelector('[data-kt-admin-table-filter="search"]');
        filterForm = document.querySelector('[data-kt-admin-table-filter="form"]');
        toolbarBase = document.querySelector('[data-kt-admin-table-toolbar="base"]');
        toolbarSelected = document.querySelector('[data-kt-admin-table-toolbar="selected"]');
        selectedCountElement = document.querySelector('[data-kt-admin-table-select="selected_count"]');
        headerCheckbox = tableElement ? tableElement.querySelector("thead .form-check-input") : null;

        if (!tableElement || typeof $ === "undefined" || typeof $.fn.DataTable === "undefined") {
            return;
        }

        dataTable = $(tableElement).DataTable({
            processing: true,
            serverSide: true,
            searching: true,
            order: [[5, "desc"]],
            ajax: {
                url: config.indexUrl,
                type: "POST",
                data: function (d) {
                    d.jenis_admin = getFilterValue('[data-kt-admin-table-filter="jenis"]');
                    d[getCsrfName()] = getCsrfHash();
                    d.csrf_token = getCsrfHash();
                },
                dataSrc: function (json) {
                    setCsrfHash(json.csrfHash || "");
                    return json.data || [];
                },
                error: function (xhr) {
                    var response = xhr.responseJSON || {};
                    setCsrfHash(response.csrfHash || "");
                    showError(response.message || "Data admin gagal dimuat.");
                }
            },
            columns: [
                {
                    data: "id_pengguna",
                    orderable: false,
                    searchable: false,
                    render: function (data) {
                        return '' +
                            '<div class="form-check form-check-sm form-check-custom form-check-solid me-3">' +
                                '<input class="form-check-input form-check-input-row" type="checkbox" value="' + escapeHtml(data) + '" />' +
                            "</div>";
                    }
                },
                {
                    data: null,
                    render: function (data, type, row) {
                        return renderAdminCell(row);
                    }
                },
                {
                    data: null,
                    render: function (data, type, row) {
                        return renderJenisBadge(row);
                    }
                },
                {
                    data: null,
                    render: function (data, type, row) {
                        return renderCompanyCell(row);
                    }
                },
                {
                    data: "status_aktif",
                    render: function (data) {
                        return renderStatusBadge(data);
                    }
                },
                {
                    data: "terakhir_login",
                    render: function (data) {
                        return renderLastLogin(data);
                    }
                },
                {
                    data: null,
                    orderable: false,
                    searchable: false,
                    className: "text-end",
                    render: function (data, type, row) {
                        return renderActions(row);
                    }
                }
            ],
            drawCallback: function () {
                if (headerCheckbox) {
                    headerCheckbox.checked = false;
                    headerCheckbox.indeterminate = false;
                }

                updateSelectedToolbar();
            }
        });

        if (searchInput) {
            searchInput.addEventListener("keyup", function (event) {
                dataTable.search(event.target.value).draw();
            });
        }
    }

    function initFilters() {
        if (!filterForm || !dataTable) {
            return;
        }

        var applyButton = filterForm.querySelector('[data-kt-admin-table-filter="filter"]');
        var resetButton = filterForm.querySelector('[data-kt-admin-table-filter="reset"]');

        if (applyButton) {
            applyButton.addEventListener("click", function (event) {
                event.preventDefault();
                reloadTable();
            });
        }

        if (resetButton) {
            resetButton.addEventListener("click", function (event) {
                event.preventDefault();

                filterForm.querySelectorAll("select").forEach(function (select) {
                    select.value = "";

                    if (typeof $ !== "undefined" && typeof $.fn.select2 !== "undefined") {
                        $(select).trigger("change");
                    }
                });

                reloadTable();
            });
        }
    }

    function fillEditForm(row) {
        var companySelect;

        if (!editForm || !row) {
            return;
        }

        resetForm(editForm);
        editForm.querySelector('[name="id_pengguna"]').value = row.id_pengguna || "";
        editForm.querySelector('[name="nama_lengkap"]').value = row.nama_lengkap || "";
        editForm.querySelector('[name="email"]').value = row.email || "";
        editForm.querySelector('[name="nomor_telepon"]').value = row.nomor_telepon || "";
        editForm.querySelectorAll('input[name="jenis_admin"]').forEach(function (radio) {
            radio.checked = radio.value === row.slug_peran;
        });

        companySelect = editForm.querySelector('select[name="id_perusahaan"]');

        if (companySelect) {
            companySelect.value = row.id_perusahaan || "";

            if (typeof $ !== "undefined" && typeof $.fn.select2 !== "undefined") {
                $(companySelect).trigger("change");
            }
        }

        setPhotoPreview(editForm, row.foto_url || "");
        toggleCompanyField(editForm);
    }

    function submitForm(formElement, modalInstance, url, submitButton, isEdit) {
        var formData;
        var role = getSelectedRole(formElement);

        if (!validateForm(formElement, isEdit)) {
            return;
        }

        formData = new FormData(formElement);
        formData.set("jenis_admin", role);

        if (!isCompanyRole(role)) {
            formData.delete("id_perusahaan");
        }

        setSubmitState(submitButton, true);

        requestJson(url, "POST", formData)
            .then(function (response) {
                modalInstance.hide();
                showSuccess(response.message).then(function () {
                    reloadTable();
                });
            })
            .catch(function (error) {
                showError(error.message);
            })
            .finally(function () {
                setSubmitState(submitButton, false);
            });
    }

    function initAddModal() {
        addModalElement = document.getElementById("kt_modal_tambah_admin");
        addForm = document.getElementById("kt_modal_tambah_admin_form");

        if (!addModalElement || !addForm || typeof bootstrap === "undefined") {
            return;
        }

        addModal = new bootstrap.Modal(addModalElement);

        addModalElement.querySelector('[data-kt-admin-modal-action="close"]').addEventListener("click", function () {
            addModal.hide();
        });

        addModalElement.querySelector('[data-kt-admin-modal-action="cancel"]').addEventListener("click", function (event) {
            event.preventDefault();
            addModal.hide();
        });

        addForm.querySelectorAll('input[name="jenis_admin"]').forEach(function (radio) {
            radio.addEventListener("change", function () {
                toggleCompanyField(addForm);
            });
        });

        addForm.addEventListener("submit", function (event) {
            var submitButton = addModalElement.querySelector('[data-kt-admin-modal-action="submit"]');

            event.preventDefault();
            submitForm(addForm, addModal, config.simpanUrl, submitButton, false);
        });

        addModalElement.addEventListener("hidden.bs.modal", function () {
            resetForm(addForm);
            setPhotoPreview(addForm, "");
        });

        resetForm(addForm);
    }

    function initEditModal() {
        editModalElement = document.getElementById("kt_modal_edit_admin");
        editForm = document.getElementById("kt_modal_edit_admin_form");

        if (!editModalElement || !editForm || typeof bootstrap === "undefined") {
            return;
        }

        editModal = new bootstrap.Modal(editModalElement);

        editModalElement.querySelector('[data-kt-admin-edit-modal-action="close"]').addEventListener("click", function () {
            editModal.hide();
        });

        editModalElement.querySelector('[data-kt-admin-edit-modal-action="cancel"]').addEventListener("click", function (event) {
            event.preventDefault();
            editModal.hide();
        });

        editForm.querySelectorAll('input[name="jenis_admin"]').forEach(function (radio) {
            radio.addEventListener("change", function () {
                toggleCompanyField(editForm);
            });
        });

        editForm.addEventListener("submit", function (event) {
            var submitButton = editModalElement.querySelector('[data-kt-admin-edit-modal-action="submit"]');
            var idPengguna = editForm.querySelector('[name="id_pengguna"]').value;

            event.preventDefault();

            if (!idPengguna) {
                showError("Data admin tidak valid.");
                return;
            }

            submitForm(editForm, editModal, config.updateUrl.replace(/\/$/, "") + "/" + idPengguna, submitButton, true);
        });

        editModalElement.addEventListener("hidden.bs.modal", function () {
            resetForm(editForm);
            setPhotoPreview(editForm, "");
        });

        resetForm(editForm);
    }

    function handleActivate(row) {
        showAlert({
            text: "Aktifkan akun admin " + (row.nama_lengkap || "ini") + "?",
            icon: "warning",
            showCancelButton: true,
            buttonsStyling: false,
            confirmButtonText: "Ya, aktivasi",
            cancelButtonText: "Batal",
            customClass: {
                confirmButton: "btn btn-success",
                cancelButton: "btn btn-light"
            }
        }).then(function (result) {
            if (!result.isConfirmed) {
                return;
            }

            requestJson(config.aktivasiUrl.replace(/\/$/, "") + "/" + row.id_pengguna, "GET")
                .then(function (response) {
                    showSuccess(response.message).then(function () {
                        reloadTable();
                    });
                })
                .catch(function (error) {
                    showError(error.message);
                });
        });
    }

    function handleDeactivate(row) {
        showAlert({
            text: "Nonaktifkan akun admin " + (row.nama_lengkap || "ini") + "?",
            icon: "warning",
            showCancelButton: true,
            buttonsStyling: false,
            confirmButtonText: "Ya, nonaktifkan",
            cancelButtonText: "Batal",
            customClass: {
                confirmButton: "btn btn-danger",
                cancelButton: "btn btn-light"
            }
        }).then(function (result) {
            if (!result.isConfirmed) {
                return;
            }

            requestJson(config.hapusUrl.replace(/\/$/, "") + "/" + row.id_pengguna, "GET")
                .then(function (response) {
                    showSuccess(response.message).then(function () {
                        reloadTable();
                    });
                })
                .catch(function (error) {
                    showError(error.message);
                });
        });
    }

    function handleBulkDelete() {
        var selectedIds = getSelectedIds();
        var formData;

        if (selectedIds.length === 0) {
            showError("Pilih minimal satu admin terlebih dahulu.");
            return;
        }

        showAlert({
            text: "Hapus permanen " + selectedIds.length + " admin terpilih?",
            icon: "warning",
            showCancelButton: true,
            buttonsStyling: false,
            confirmButtonText: "Ya, hapus",
            cancelButtonText: "Batal",
            customClass: {
                confirmButton: "btn btn-danger",
                cancelButton: "btn btn-light"
            }
        }).then(function (result) {
            if (!result.isConfirmed) {
                return;
            }

            formData = new FormData();
            selectedIds.forEach(function (id) {
                formData.append("ids[]", id);
            });

            requestJson(config.hapusMassalUrl, "POST", formData)
                .then(function (response) {
                    showSuccess(response.message).then(function () {
                        reloadTable();
                    });
                })
                .catch(function (error) {
                    showError(error.message);
                });
        });
    }

    function initTableActions() {
        var bulkDeleteButton;

        if (!tableElement) {
            return;
        }

        tableElement.addEventListener("change", function (event) {
            var target = event.target;

            if (target === headerCheckbox) {
                getRowCheckboxes().forEach(function (checkbox) {
                    checkbox.checked = target.checked;
                });
            }

            if (target.matches(".form-check-input-row") || target === headerCheckbox) {
                updateSelectedToolbar();
                syncHeaderCheckboxState();
            }
        });

        tableElement.addEventListener("click", function (event) {
            var activateButton = event.target.closest('[data-action="aktivasi-admin"]');
            var editButton = event.target.closest('[data-action="edit-admin"]');
            var deactivateButton = event.target.closest('[data-action="hapus-admin"]');
            var row;

            if (!activateButton && !editButton && !deactivateButton) {
                return;
            }

            row = getRowDataFromAction(activateButton || editButton || deactivateButton);
            if (!row) {
                return;
            }

            if (activateButton) {
                handleActivate(row);
                return;
            }

            if (editButton) {
                fillEditForm(row);
                editModal.show();
                return;
            }

            if (deactivateButton) {
                handleDeactivate(row);
            }
        });

        bulkDeleteButton = document.querySelector('[data-kt-admin-table-select="delete_selected"]');
        if (bulkDeleteButton) {
            bulkDeleteButton.addEventListener("click", handleBulkDelete);
        }
    }

    return {
        init: function () {
            initSelect2();
            bindPhotoInputs();
            initDataTable();
            initFilters();
            initAddModal();
            initEditModal();
            initTableActions();
        }
    };
}());

if (typeof KTUtil !== "undefined") {
    KTUtil.onDOMContentLoaded(function () {
        KTAdminList.init();
    });
} else {
    document.addEventListener("DOMContentLoaded", function () {
        KTAdminList.init();
    });
}
