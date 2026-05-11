"use strict";

/*
|-------------------------------------------------------------------
| MODUL DAFTAR PELAMAR
|-------------------------------------------------------------------
| Script ini mengelola halaman tabel pelamar Super Admin berbasis
| DataTables server-side, filter Metronic, modal tambah/edit, aktivasi,
| hapus satuan, dan hapus massal.
| Alur kerja:
| 1. DataTables memuat data dari endpoint AJAX CI4.
| 2. Filter, search, dan aksi tombol memanggil endpoint JSON.
| 3. Modal tambah dan edit dikirim memakai FormData tanpa reload penuh.
|
| Tips Debugging:
| - Jika request 403, cek token CSRF meta dan response csrfHash dari server.
| - Jika tombol aksi tidak bekerja, cek selector data-action pada render kolom aksi.
*/
var KTPelamarList = (function () {
    var config = window.ktPelamarConfig || {};
    var tableElement;
    var dataTable;
    var searchInput;
    var filterForm;
    var toolbarBase;
    var toolbarSelected;
    var selectedCountElement;
    var addModalElement;
    var addModal;
    var addForm;
    var editModalElement;
    var editModal;
    var editForm;

    var csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
    var csrfNameMeta = document.querySelector('meta[name="csrf-header-name"]');

    var getCsrfName = function () {
        return csrfNameMeta ? csrfNameMeta.getAttribute("content") : "csrf_test_name";
    };

    var getCsrfHash = function () {
        return csrfTokenMeta ? csrfTokenMeta.getAttribute("content") : "";
    };

    var setCsrfHash = function (hash) {
        if (csrfTokenMeta && hash) {
            csrfTokenMeta.setAttribute("content", hash);
        }
    };

    var formatDateTime = function (value) {
        if (!value) {
            return "-";
        }

        var parsed = new Date(value.replace(" ", "T"));
        if (Number.isNaN(parsed.getTime())) {
            return value;
        }

        return parsed.toLocaleString("id-ID", {
            year: "numeric",
            month: "short",
            day: "2-digit",
            hour: "2-digit",
            minute: "2-digit"
        });
    };

    var escapeHtml = function (text) {
        return String(text || "")
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    };

    var showAlert = function (options) {
        if (typeof Swal === "undefined") {
            window.alert(options.text || "");
            return Promise.resolve({ isConfirmed: true });
        }

        return Swal.fire(options);
    };

    var showError = function (message) {
        return showAlert({
            text: message || "Terjadi kesalahan pada server.",
            icon: "error",
            buttonsStyling: false,
            confirmButtonText: "Tutup",
            customClass: {
                confirmButton: "btn btn-primary"
            }
        });
    };

    var showSuccess = function (message) {
        return showAlert({
            text: message || "Proses berhasil.",
            icon: "success",
            buttonsStyling: false,
            confirmButtonText: "Oke",
            customClass: {
                confirmButton: "btn btn-primary"
            }
        });
    };

    var setSubmitState = function (button, loading) {
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
    };

    var requestJson = function (url, method, body) {
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
        })
            .then(function (response) {
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
    };

    var getFilterValue = function (selector) {
        var element = document.querySelector(selector);
        return element ? element.value : "";
    };

    var renderJenisBadge = function (slugPeran) {
        if (slugPeran === "pelamar_alumni") {
            return '<span class="badge badge-light-success">Pelamar Alumni</span>';
        }

        return '<span class="badge badge-light-primary">Pelamar Umum</span>';
    };

    var renderStatusPendaftaranBadge = function (status) {
        var map = {
            menunggu_aktivasi: "warning",
            aktif: "success",
            terdaftar: "info"
        };

        var label = String(status || "-").replace(/_/g, " ");
        label = label.charAt(0).toUpperCase() + label.slice(1);

        return '<span class="badge badge-light-' + (map[status] || "secondary") + '">' + escapeHtml(label) + "</span>";
    };

    var renderStatusAkunBadge = function (statusAktif) {
        if (String(statusAktif) === "1") {
            return '<span class="badge badge-light-success">Aktif</span>';
        }

        return '<span class="badge badge-light-danger">Nonaktif</span>';
    };

    var renderTerakhirLoginBadge = function (terakhirLogin) {
        if (!terakhirLogin) {
            return '<span class="badge badge-light-danger">Belum login</span>';
        }

        return '<span class="badge badge-light-info">' + escapeHtml(formatDateTime(terakhirLogin)) + "</span>";
    };

    var renderPelamarCell = function (row) {
        return '' +
            '<div class="d-flex align-items-center">' +
                '<div class="symbol symbol-circle symbol-50px overflow-hidden me-3">' +
                    '<a href="javascript:void(0)">' +
                        '<div class="symbol-label">' +
                            '<img src="' + escapeHtml(row.foto_url || config.defaultFoto || "") + '" alt="foto" class="w-100" />' +
                        "</div>" +
                    "</a>" +
                "</div>" +
                '<div class="d-flex flex-column">' +
                    '<a href="javascript:void(0)" class="text-gray-800 text-hover-primary mb-1">' + escapeHtml(row.nama_lengkap) + "</a>" +
                    '<span>' + escapeHtml(row.email) + "</span>" +
                "</div>" +
            "</div>";
    };

    var renderActions = function (row) {
        var detailUrl = (config.detailUrl || "").replace(/\/$/, "") + "/" + row.id_pelamar;
        var canActivate = row.status_pendaftaran !== "aktif" || String(row.status_aktif) !== "1";
        var rejectTitle = row.status_pendaftaran === "menunggu_aktivasi" ? "Tolak Akses" : "Nonaktifkan Akun";
        var aktivasiButton =
            '<button type="button" class="btn btn-icon ' + (canActivate ? 'btn-active-light-success' : 'btn-light-secondary disabled') + ' w-30px h-30px me-2" data-action="aktivasi-pelamar" data-id="' + escapeHtml(row.id_pelamar) + '" title="' + (canActivate ? 'Setujui / Aktivasi Akun' : 'Sudah Aktif') + '"' + (canActivate ? '' : ' disabled') + '>' +
                '<i class="ki-duotone ki-check-circle fs-3"><span class="path1"></span><span class="path2"></span></i>' +
            "</button>";

        return '' +
            '<div class="text-end">' +
                aktivasiButton +
                '<a href="' + escapeHtml(detailUrl) + '" class="btn btn-icon btn-active-light-primary w-30px h-30px me-2" title="Detail">' +
                    '<i class="ki-duotone ki-eye fs-3"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>' +
                "</a>" +
                '<button type="button" class="btn btn-icon btn-active-light-warning w-30px h-30px me-2" data-action="edit-pelamar" data-id="' + escapeHtml(row.id_pelamar) + '" data-nama="' + escapeHtml(row.nama_lengkap) + '" data-email="' + escapeHtml(row.email) + '" data-jenis="' + escapeHtml(row.slug_peran) + '" title="Edit">' +
                    '<i class="ki-duotone ki-pencil fs-3"><span class="path1"></span><span class="path2"></span></i>' +
                "</button>" +
                '<button type="button" class="btn btn-icon btn-active-light-danger w-30px h-30px" data-action="hapus-pelamar" data-id="' + escapeHtml(row.id_pelamar) + '" data-nama="' + escapeHtml(row.nama_lengkap) + '" title="' + rejectTitle + '">' +
                    '<i class="ki-duotone ki-cross-circle fs-3"><span class="path1"></span><span class="path2"></span></i>' +
                "</button>" +
            "</div>";
    };

    var resetImageInput = function (modalElement) {
        if (!modalElement) {
            return;
        }

        var wrapper = modalElement.querySelector(".image-input-wrapper");
        if (wrapper) {
            wrapper.style.backgroundImage = 'url("' + (config.defaultFoto || "") + '")';
        }

        var fileInput = modalElement.querySelector('input[type="file"][name="foto"]');
        if (fileInput) {
            fileInput.value = "";
        }
    };

    var resetForm = function (formElement, modalElement) {
        if (!formElement) {
            return;
        }

        formElement.reset();
        resetImageInput(modalElement);
    };

    var getRowDataFromAction = function (buttonElement) {
        var row = dataTable.row($(buttonElement).closest("tr"));

        if (!row.data() && $(buttonElement).closest("tr").hasClass("child")) {
            row = dataTable.row($(buttonElement).closest("tr").prev());
        }

        return row.data();
    };

    var updateSelectedToolbar = function () {
        if (!toolbarBase || !toolbarSelected || !selectedCountElement) {
            return;
        }

        var checkedRows = tableElement.querySelectorAll('tbody .form-check-input-row:checked');
        var totalChecked = checkedRows.length;

        if (totalChecked > 0) {
            toolbarBase.classList.add("d-none");
            toolbarSelected.classList.remove("d-none");
            selectedCountElement.textContent = totalChecked;
        } else {
            toolbarSelected.classList.add("d-none");
            toolbarBase.classList.remove("d-none");
            selectedCountElement.textContent = "0";
        }
    };

    var getSelectedIds = function () {
        return Array.from(tableElement.querySelectorAll('tbody .form-check-input-row:checked'))
            .map(function (checkbox) {
                return checkbox.value;
            });
    };

    var initDataTable = function () {
        tableElement = document.querySelector("#kt_table_pelamar");
        searchInput = document.querySelector('[data-kt-user-table-filter="search"]');
        filterForm = document.querySelector('[data-kt-user-table-filter="form"]');
        toolbarBase = document.querySelector('[data-kt-user-table-toolbar="base"]');
        toolbarSelected = document.querySelector('[data-kt-user-table-toolbar="selected"]');
        selectedCountElement = document.querySelector('[data-kt-user-table-select="selected_count"]');

        if (!tableElement || typeof $ === "undefined" || typeof $.fn.DataTable === "undefined") {
            return;
        }

        dataTable = $(tableElement).DataTable({
            processing: true,
            serverSide: true,
            searching: true,
            order: [[7, "desc"]],
            ajax: {
                url: config.indexUrl,
                type: "POST",
                data: function (d) {
                    d.jenis_pelamar = getFilterValue('[data-kt-user-table-filter="jenis"]');
                    d.status_aktif = getFilterValue('[data-kt-user-table-filter="status_akun"]');
                    d.status_pendaftaran = getFilterValue('[data-kt-user-table-filter="status_daftar"]');
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
                    showError(response.message || "Data pelamar gagal dimuat.");
                }
            },
            columns: [
                {
                    data: "id_pelamar",
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
                    data: "account_id",
                    render: function (data) {
                        return '<span class="fw-bold text-gray-800">' + escapeHtml(data) + "</span>";
                    }
                },
                {
                    data: null,
                    render: function (data, type, row) {
                        return renderPelamarCell(row);
                    }
                },
                {
                    data: "slug_peran",
                    render: function (data) {
                        return renderJenisBadge(data);
                    }
                },
                {
                    data: "status_pendaftaran",
                    render: function (data) {
                        return renderStatusPendaftaranBadge(data);
                    }
                },
                {
                    data: "terakhir_login",
                    render: function (data) {
                        return renderTerakhirLoginBadge(data);
                    }
                },
                {
                    data: "status_aktif",
                    render: function (data) {
                        return renderStatusAkunBadge(data);
                    }
                },
                {
                    data: "terdaftar_pada",
                    render: function (data) {
                        return escapeHtml(formatDateTime(data));
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
                updateSelectedToolbar();
            }
        });

        if (searchInput) {
            searchInput.addEventListener("keyup", function (event) {
                dataTable.search(event.target.value).draw();
            });
        }
    };

    var initSelect2 = function () {
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
    };

    var initFilters = function () {
        if (!filterForm || !dataTable) {
            return;
        }

        var applyButton = filterForm.querySelector('[data-kt-user-table-filter="filter"]');
        var resetButton = filterForm.querySelector('[data-kt-user-table-filter="reset"]');
        var reviewButton = document.querySelector('[data-kt-user-table-filter="review"]');

        if (applyButton) {
            applyButton.addEventListener("click", function (event) {
                event.preventDefault();
                dataTable.ajax.reload();
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

                dataTable.ajax.reload();
            });
        }

        if (reviewButton) {
            reviewButton.addEventListener("click", function (event) {
                var statusSelect = filterForm.querySelector('[data-kt-user-table-filter="status_daftar"]');

                event.preventDefault();

                if (statusSelect) {
                    statusSelect.value = "menunggu_aktivasi";
                    if (typeof $ !== "undefined" && typeof $.fn.select2 !== "undefined") {
                        $(statusSelect).trigger("change");
                    }
                }

                dataTable.ajax.reload();
            });
        }
    };

    var initAddModal = function () {
        addModalElement = document.getElementById("kt_modal_tambah_pelamar");
        addForm = document.getElementById("kt_modal_tambah_pelamar_form");

        if (!addModalElement || !addForm || typeof bootstrap === "undefined") {
            return;
        }

        addModal = new bootstrap.Modal(addModalElement);

        addModalElement.querySelector('[data-kt-pelamar-modal-action="close"]').addEventListener("click", function () {
            addModal.hide();
        });

        addModalElement.querySelector('[data-kt-pelamar-modal-action="cancel"]').addEventListener("click", function (event) {
            event.preventDefault();
            resetForm(addForm, addModalElement);
            addModal.hide();
        });

        addForm.addEventListener("submit", function (event) {
            var submitButton = addModalElement.querySelector('[data-kt-pelamar-modal-action="submit"]');
            var checkedJenis = addForm.querySelector('input[name="jenis_pelamar"]:checked');

            event.preventDefault();

            if (!checkedJenis) {
                showError("Jenis pelamar wajib dipilih.");
                return;
            }

            setSubmitState(submitButton, true);

            var formData = new FormData(addForm);
            formData.append("jenis_pelamar", checkedJenis.value);

            requestJson(config.simpanUrl, "POST", formData)
                .then(function (response) {
                    resetForm(addForm, addModalElement);
                    addModal.hide();
                    showSuccess(response.message).then(function () {
                        dataTable.ajax.reload(null, false);
                    });
                })
                .catch(function (error) {
                    showError(error.message);
                })
                .finally(function () {
                    setSubmitState(submitButton, false);
                });
        });

        addModalElement.addEventListener("hidden.bs.modal", function () {
            resetForm(addForm, addModalElement);
        });
    };

    var initEditModal = function () {
        editModalElement = document.getElementById("kt_modal_edit_pelamar");
        editForm = document.getElementById("kt_modal_edit_pelamar_form");

        if (!editModalElement || !editForm || typeof bootstrap === "undefined") {
            return;
        }

        editModal = new bootstrap.Modal(editModalElement);

        editModalElement.querySelector('[data-kt-pelamar-edit-modal-action="close"]').addEventListener("click", function () {
            editModal.hide();
        });

        editModalElement.querySelector('[data-kt-pelamar-edit-modal-action="cancel"]').addEventListener("click", function (event) {
            event.preventDefault();
            resetForm(editForm, editModalElement);
            editModal.hide();
        });

        editForm.addEventListener("submit", function (event) {
            var submitButton = editModalElement.querySelector('[data-kt-pelamar-edit-modal-action="submit"]');
            var idPelamar = editForm.querySelector('[name="id_pelamar"]').value;

            event.preventDefault();

            if (!idPelamar) {
                showError("ID pelamar tidak valid.");
                return;
            }

            setSubmitState(submitButton, true);

            requestJson(config.updateUrl.replace(/\/$/, "") + "/" + idPelamar, "POST", new FormData(editForm))
                .then(function (response) {
                    editModal.hide();
                    showSuccess(response.message).then(function () {
                        dataTable.ajax.reload(null, false);
                    });
                })
                .catch(function (error) {
                    showError(error.message);
                })
                .finally(function () {
                    setSubmitState(submitButton, false);
                });
        });

        editModalElement.addEventListener("hidden.bs.modal", function () {
            resetForm(editForm, editModalElement);
        });
    };

    var handleEdit = function (buttonElement, row) {
        if (!editForm || !editModal) {
            return;
        }

        editForm.querySelector('[name="id_pelamar"]').value = row.id_pelamar || "";
        editForm.querySelector('[name="nama_lengkap"]').value = row.nama_lengkap || "";
        editForm.querySelector('[name="email"]').value = row.email || "";
        editForm.querySelector('[name="status_pendaftaran"]').value = row.status_pendaftaran || "menunggu_aktivasi";

        var jenisPelamar = buttonElement.getAttribute("data-jenis") || row.slug_peran || "pelamar_umum";
        var radioValue = jenisPelamar === "pelamar_alumni" ? "alumni" : "umum";

        editForm.querySelectorAll('[name="jenis_pelamar"]').forEach(function (radio) {
            radio.checked = radio.value === radioValue;
        });

        if (typeof $ !== "undefined" && typeof $.fn.select2 !== "undefined") {
            $(editForm.querySelector('[name="status_pendaftaran"]')).trigger("change");
        }

        var wrapper = editModalElement.querySelector(".image-input-wrapper");
        if (wrapper) {
            wrapper.style.backgroundImage = 'url("' + escapeHtml(row.foto_url || config.defaultFoto || "") + '")';
        }

        editModal.show();
    };

    var handleAktivasi = function (row) {
        showAlert({
            text: "Setujui dan aktifkan akun pelamar " + (row.nama_lengkap || "ini") + "?",
            icon: "warning",
            showCancelButton: true,
            buttonsStyling: false,
            confirmButtonText: "Ya, setujui",
            cancelButtonText: "Batal",
            customClass: {
                confirmButton: "btn btn-success",
                cancelButton: "btn btn-light"
            }
        }).then(function (result) {
            if (!result.isConfirmed) {
                return;
            }

            requestJson(config.aktivasiUrl.replace(/\/$/, "") + "/" + row.id_pelamar, "GET")
                .then(function (response) {
                    showSuccess(response.message).then(function () {
                        dataTable.ajax.reload(null, false);
                    });
                })
                .catch(function (error) {
                    showError(error.message);
                });
        });
    };

    var handleDelete = function (row) {
        var isPendingReview = row.status_pendaftaran === "menunggu_aktivasi";
        showAlert({
            text: (isPendingReview ? "Tolak akses pelamar " : "Nonaktifkan akun pelamar ") + (row.nama_lengkap || "ini") + "?",
            icon: "warning",
            showCancelButton: true,
            buttonsStyling: false,
            confirmButtonText: isPendingReview ? "Ya, tolak" : "Ya, nonaktifkan",
            cancelButtonText: "Batal",
            customClass: {
                confirmButton: "btn btn-danger",
                cancelButton: "btn btn-light"
            }
        }).then(function (result) {
            if (!result.isConfirmed) {
                return;
            }

            requestJson(config.hapusUrl.replace(/\/$/, "") + "/" + row.id_pelamar, "GET")
                .then(function (response) {
                    showSuccess(response.message).then(function () {
                        dataTable.ajax.reload(null, false);
                    });
                })
                .catch(function (error) {
                    showError(error.message);
                });
        });
    };

    var initTableActions = function () {
        if (!tableElement) {
            return;
        }

        tableElement.addEventListener("click", function (event) {
            var aktivasiButton = event.target.closest('[data-action="aktivasi-pelamar"]');
            var editButton = event.target.closest('[data-action="edit-pelamar"]');
            var deleteButton = event.target.closest('[data-action="hapus-pelamar"]');

            if (!aktivasiButton && !editButton && !deleteButton) {
                return;
            }

            var targetButton = aktivasiButton || editButton || deleteButton;
            var row = getRowDataFromAction(targetButton);

            if (!row) {
                return;
            }

            if (aktivasiButton) {
                handleAktivasi(row);
                return;
            }

            if (editButton) {
                handleEdit(editButton, row);
                return;
            }

            if (deleteButton) {
                handleDelete(row);
            }
        });

        tableElement.addEventListener("change", function (event) {
            var masterCheckbox = event.target.closest('thead .form-check-input');
            var rowCheckbox = event.target.closest('.form-check-input-row');

            if (masterCheckbox) {
                tableElement.querySelectorAll('tbody .form-check-input-row').forEach(function (checkbox) {
                    checkbox.checked = masterCheckbox.checked;
                });
            }

            if (rowCheckbox || masterCheckbox) {
                updateSelectedToolbar();
            }
        });
    };

    var initBulkDelete = function () {
        var deleteSelectedButton = document.querySelector('[data-kt-user-table-select="delete_selected"]');

        if (!deleteSelectedButton) {
            return;
        }

        deleteSelectedButton.addEventListener("click", function () {
            var ids = getSelectedIds();

            if (ids.length === 0) {
                showError("Pilih minimal satu pelamar.");
                return;
            }

            showAlert({
                text: "Hapus permanen " + ids.length + " akun pelamar terpilih?",
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

                var formData = new FormData();
                formData.append(getCsrfName(), getCsrfHash());
                formData.append("csrf_token", getCsrfHash());

                ids.forEach(function (id) {
                    formData.append("ids[]", id);
                });

                requestJson(config.hapusMassalUrl, "POST", formData)
                    .then(function (response) {
                        showSuccess(response.message).then(function () {
                            dataTable.ajax.reload(null, false);
                        });
                    })
                    .catch(function (error) {
                        showError(error.message);
                    });
            });
        });
    };

    return {
        init: function () {
            initSelect2();
            initDataTable();
            initFilters();
            initAddModal();
            initEditModal();
            initTableActions();
            initBulkDelete();
        }
    };
})();

if (typeof KTUtil !== "undefined") {
    KTUtil.onDOMContentLoaded(function () {
        KTPelamarList.init();
    });
} else {
    document.addEventListener("DOMContentLoaded", function () {
        KTPelamarList.init();
    });
}
