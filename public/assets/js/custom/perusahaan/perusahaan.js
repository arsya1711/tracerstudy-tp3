"use strict";

/*
|-------------------------------------------------------------------
| MODUL DATA DUDI
|-------------------------------------------------------------------
| Script ini menangani tabel DUDI berbasis DataTables server-side,
| filter kota, modal tambah/edit, preview logo, dan hapus data.
*/
var KTPerusahaanList = (function () {
    var config = window.ktPerusahaanConfig || {};
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

    function setLogoPreview(formElement, imageUrl) {
        var imageInput = formElement ? formElement.querySelector('[data-kt-perusahaan-logo-input="true"]') : null;
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

    function resetLogoInput(formElement) {
        var imageInput = formElement ? formElement.querySelector('[data-kt-perusahaan-logo-input="true"]') : null;
        var removeInput;
        var fileInput;

        if (!imageInput) {
            return;
        }

        removeInput = imageInput.querySelector('input[name="logo_remove"]');
        fileInput = imageInput.querySelector('input[type="file"][name="logo"]');

        setLogoPreview(formElement, imageInput.getAttribute("data-image-input-initial") || "");

        if (removeInput) {
            removeInput.value = "";
        }

        if (fileInput) {
            fileInput.value = "";
        }
    }

    function bindLogoInputs() {
        document.querySelectorAll('[data-kt-perusahaan-logo-input="true"]').forEach(function (imageInput) {
            var fileInput;
            var removeInput;
            var removeButton;
            var cancelButton;

            if (imageInput.getAttribute("data-perusahaan-logo-bound") === "1") {
                return;
            }

            fileInput = imageInput.querySelector('input[type="file"][name="logo"]');
            removeInput = imageInput.querySelector('input[name="logo_remove"]');
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

            imageInput.setAttribute("data-perusahaan-logo-bound", "1");
        });
    }

    function resetForm(formElement) {
        if (!formElement) {
            return;
        }

        formElement.reset();
        resetLogoInput(formElement);
    }

    function validateForm(formElement) {
        var namaInput = formElement.querySelector('[name="nama_perusahaan"]');
        var emailInput = formElement.querySelector('[name="email"]');
        var kerjasamaChecked = formElement.querySelectorAll('input[name="id_kerjasama[]"]:checked');

        if (!namaInput || !namaInput.value.trim()) {
            showError("Nama DUDI wajib diisi.");
            return false;
        }

        if (emailInput && emailInput.value.trim() !== "" && emailInput.reportValidity && !emailInput.reportValidity()) {
            return false;
        }

        if (!kerjasamaChecked.length) {
            showError("Minimal satu jenis kerjasama wajib dipilih.");
            return false;
        }

        return true;
    }

    function renderPerusahaanCell(row) {
        var email = String(row.email || "").trim();

        return '' +
            '<div class="d-flex align-items-center">' +
                '<div class="symbol symbol-circle symbol-50px overflow-hidden me-3">' +
                    '<div class="symbol-label bg-light">' +
                        '<img src="' + escapeHtml(row.logo_url || config.blankLogoUrl || "") + '" alt="logo dudi" class="w-100" />' +
                    "</div>" +
                "</div>" +
                '<div class="d-flex flex-column">' +
                    '<span class="text-gray-800 fw-bold mb-1">' + escapeHtml(row.nama_perusahaan || "-") + "</span>" +
                    '<span class="text-muted">' + escapeHtml(email !== "" ? email : "Email belum diisi") + "</span>" +
                "</div>" +
            "</div>";
    }

    function renderAlamatCell(value) {
        var text = String(value || "").trim();

        if (text === "" || text === "-") {
            return '<span class="text-muted">-</span>';
        }

        return '<span class="text-gray-700">' + escapeHtml(text) + "</span>";
    }

    function getKerjasamaBadgeClass(slug, index) {
        var map = {
            pkl: "badge-light-primary",
            "kunjungan-industri": "badge-light-info",
            "penguji-ukk": "badge-light-success",
            sinkronisasi: "badge-light-warning",
            rekrutmen: "badge-light-danger",
            pelatihan: "badge-light-dark"
        };
        var fallback = [
            "badge-light-primary",
            "badge-light-info",
            "badge-light-success",
            "badge-light-warning",
            "badge-light-danger",
            "badge-light-dark"
        ];

        if (slug && map[slug]) {
            return map[slug];
        }

        return fallback[index % fallback.length];
    }

    function getKerjasamaShortLabel(slug, name) {
        var map = {
            pkl: "PKL",
            rekrutmen: "Rekrutmen",
            "penguji-ukk": "Penguji UKK",
            sinkronisasi: "Sinkronisasi",
            "kunjungan-industri": "Kunjungan Industri",
            pelatihan: "Pelatihan"
        };

        if (slug && map[slug]) {
            return map[slug];
        }

        if (slug) {
            return slug
                .split("-")
                .map(function (part) {
                    if (!part) {
                        return "";
                    }

                    return part.charAt(0).toUpperCase() + part.slice(1);
                })
                .join(" ");
        }

        return name || "-";
    }

    function renderKerjasamaCell(row) {
        var names = Array.isArray(row.kerjasama_nama) ? row.kerjasama_nama : [];
        var slugs = Array.isArray(row.kerjasama_slug) ? row.kerjasama_slug : [];
        var badges;
        var rows = [];
        var index;

        if (!names.length) {
            return '<span class="text-muted">Belum ada</span>';
        }

        badges = names.map(function (name, badgeIndex) {
            var slug = slugs[badgeIndex] || "";

            return '<span class="badge ' + getKerjasamaBadgeClass(slug, badgeIndex) + '">' + escapeHtml(getKerjasamaShortLabel(slug, name)) + '</span>';
        });

        for (index = 0; index < badges.length; index += 2) {
            rows.push(
                '<div class="d-flex flex-wrap gap-2">' +
                    badges.slice(index, index + 2).join("") +
                '</div>'
            );
        }

        return '<div class="d-flex flex-column gap-2">' + rows.join("") + '</div>';
    }

    function renderActions() {
        return '' +
            '<div class="text-end">' +
                '<button type="button" class="btn btn-icon btn-active-light-warning w-30px h-30px me-2" data-action="edit-perusahaan" title="Edit DUDI">' +
                    '<i class="ki-duotone ki-pencil fs-3"><span class="path1"></span><span class="path2"></span></i>' +
                "</button>" +
                '<button type="button" class="btn btn-icon btn-active-light-danger w-30px h-30px" data-action="hapus-perusahaan" title="Hapus DUDI">' +
                    '<i class="ki-duotone ki-trash fs-3"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i>' +
                "</button>" +
            "</div>";
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
        tableElement = document.querySelector("#kt_table_perusahaan");
        searchInput = document.querySelector('[data-kt-perusahaan-table-filter="search"]');
        filterForm = document.querySelector('[data-kt-perusahaan-table-filter="form"]');
        toolbarBase = document.querySelector('[data-kt-perusahaan-table-toolbar="base"]');
        toolbarSelected = document.querySelector('[data-kt-perusahaan-table-toolbar="selected"]');
        selectedCountElement = document.querySelector('[data-kt-perusahaan-table-select="selected_count"]');
        headerCheckbox = tableElement ? tableElement.querySelector("thead .form-check-input") : null;

        if (!tableElement || typeof $ === "undefined" || typeof $.fn.DataTable === "undefined") {
            return;
        }

        dataTable = $(tableElement).DataTable({
            processing: true,
            serverSide: true,
            searching: true,
            order: [[1, "asc"]],
            ajax: {
                url: config.indexUrl,
                type: "POST",
                data: function (d) {
                    d.kota = getFilterValue('[data-kt-perusahaan-table-filter="kota"]');
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
                    showError(response.message || "Data DUDI gagal dimuat.");
                }
            },
            columns: [
                {
                    data: "id_perusahaan",
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
                        return renderPerusahaanCell(row);
                    }
                },
                {
                    data: "no_telepon",
                    render: function (data) {
                        return renderAlamatCell(data);
                    }
                },
                {
                    data: "kota",
                    render: function (data) {
                        return renderAlamatCell(data);
                    }
                },
                {
                    data: "alamat",
                    render: function (data) {
                        return renderAlamatCell(data);
                    }
                },
                {
                    data: null,
                    orderable: false,
                    searchable: false,
                    render: function (data, type, row) {
                        return renderKerjasamaCell(row);
                    }
                },
                {
                    data: null,
                    orderable: false,
                    searchable: false,
                    className: "text-end",
                    render: function () {
                        return renderActions();
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

        var applyButton = filterForm.querySelector('[data-kt-perusahaan-table-filter="filter"]');
        var resetButton = filterForm.querySelector('[data-kt-perusahaan-table-filter="reset"]');

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
        if (!editForm || !row) {
            return;
        }

        resetForm(editForm);
        editForm.querySelector('[name="id_perusahaan"]').value = row.id_perusahaan || "";
        editForm.querySelector('[name="nama_perusahaan"]').value = row.nama_perusahaan || "";
        editForm.querySelector('[name="email"]').value = row.email || "";
        editForm.querySelector('[name="no_telepon"]').value = row.no_telepon && row.no_telepon !== "-" ? row.no_telepon : "";
        editForm.querySelector('[name="kota"]').value = row.kota && row.kota !== "-" ? row.kota : "";
        editForm.querySelector('[name="alamat"]').value = row.alamat && row.alamat !== "-" ? row.alamat : "";
        editForm.querySelectorAll('input[name="id_kerjasama[]"]').forEach(function (checkbox) {
            checkbox.checked = Array.isArray(row.kerjasama_ids) && row.kerjasama_ids.indexOf(parseInt(checkbox.value, 10)) !== -1;
        });
        setLogoPreview(editForm, row.logo_url || "");
    }

    function submitForm(formElement, modalInstance, url, submitButton) {
        var formData;

        if (!validateForm(formElement)) {
            return;
        }

        formData = new FormData(formElement);
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
        addModalElement = document.getElementById("kt_modal_tambah_perusahaan");
        addForm = document.getElementById("kt_modal_tambah_perusahaan_form");

        if (!addModalElement || !addForm || typeof bootstrap === "undefined") {
            return;
        }

        addModal = new bootstrap.Modal(addModalElement);

        addModalElement.querySelector('[data-kt-perusahaan-modal-action="close"]').addEventListener("click", function () {
            addModal.hide();
        });

        addModalElement.querySelector('[data-kt-perusahaan-modal-action="cancel"]').addEventListener("click", function (event) {
            event.preventDefault();
            addModal.hide();
        });

        addForm.addEventListener("submit", function (event) {
            var submitButton = addModalElement.querySelector('[data-kt-perusahaan-modal-action="submit"]');

            event.preventDefault();
            submitForm(addForm, addModal, config.simpanUrl, submitButton);
        });

        addModalElement.addEventListener("hidden.bs.modal", function () {
            resetForm(addForm);
            setLogoPreview(addForm, "");
        });

        resetForm(addForm);
    }

    function initEditModal() {
        editModalElement = document.getElementById("kt_modal_edit_perusahaan");
        editForm = document.getElementById("kt_modal_edit_perusahaan_form");

        if (!editModalElement || !editForm || typeof bootstrap === "undefined") {
            return;
        }

        editModal = new bootstrap.Modal(editModalElement);

        editModalElement.querySelector('[data-kt-perusahaan-edit-modal-action="close"]').addEventListener("click", function () {
            editModal.hide();
        });

        editModalElement.querySelector('[data-kt-perusahaan-edit-modal-action="cancel"]').addEventListener("click", function (event) {
            event.preventDefault();
            editModal.hide();
        });

        editForm.addEventListener("submit", function (event) {
            var submitButton = editModalElement.querySelector('[data-kt-perusahaan-edit-modal-action="submit"]');
            var idPerusahaan = editForm.querySelector('[name="id_perusahaan"]').value;

            event.preventDefault();

            if (!idPerusahaan) {
                showError("Data DUDI tidak valid.");
                return;
            }

            submitForm(editForm, editModal, config.updateUrl.replace(/\/$/, "") + "/" + idPerusahaan, submitButton);
        });

        editModalElement.addEventListener("hidden.bs.modal", function () {
            resetForm(editForm);
            setLogoPreview(editForm, "");
        });

        resetForm(editForm);
    }

    function handleDelete(row) {
        showAlert({
            text: "Hapus data DUDI " + (row.nama_perusahaan || "ini") + "?",
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

            requestJson(config.hapusUrl.replace(/\/$/, "") + "/" + row.id_perusahaan, "GET")
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
            showError("Pilih minimal satu DUDI terlebih dahulu.");
            return;
        }

        showAlert({
            text: "Hapus " + selectedIds.length + " data DUDI terpilih?",
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
            var editButton = event.target.closest('[data-action="edit-perusahaan"]');
            var deleteButton = event.target.closest('[data-action="hapus-perusahaan"]');
            var row;

            if (!editButton && !deleteButton) {
                return;
            }

            row = getRowDataFromAction(editButton || deleteButton);
            if (!row) {
                return;
            }

            if (editButton) {
                fillEditForm(row);
                editModal.show();
                return;
            }

            if (deleteButton) {
                handleDelete(row);
            }
        });

        bulkDeleteButton = document.querySelector('[data-kt-perusahaan-table-select="delete_selected"]');
        if (bulkDeleteButton) {
            bulkDeleteButton.addEventListener("click", handleBulkDelete);
        }
    }

    return {
        init: function () {
            initSelect2();
            bindLogoInputs();
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
        KTPerusahaanList.init();
    });
} else {
    document.addEventListener("DOMContentLoaded", function () {
        KTPerusahaanList.init();
    });
}
