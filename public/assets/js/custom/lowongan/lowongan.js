"use strict";

/*
|-------------------------------------------------------------------
| MODUL DATA LOWONGAN
|-------------------------------------------------------------------
| Script ini menangani tabel lowongan berbasis DataTables server-side,
| filter DUDI/status, modal tambah/edit, preview flyer, dan hapus data.
|
| Alur kerja:
| 1. Inisialisasi select2, modal, dan komponen flyer.
| 2. Data lowongan dimuat dari backend melalui AJAX.
| 3. Aksi tambah, edit, dan hapus diproses tanpa reload halaman penuh.
|
| Tips Debugging:
| - Jika tabel tidak tampil, pastikan DataTables dan config URL termuat.
| - Jika modal edit kosong, periksa payload row dari endpoint index().
*/
var KTLowonganList = (function () {
    var config = window.ktLowonganConfig || {};
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

    /*
    |-------------------------------------------------------------------
    | HELPER CSRF
    |-------------------------------------------------------------------
    | Fungsi kecil ini memastikan setiap request AJAX membawa token CSRF
    | terbaru agar request yang dilindungi tetap diterima server.
    |
    | Tips Debugging:
    | - Jika muncul 403 CSRF mismatch, cek meta csrf-token di layout utama.
    */
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

    /*
    |-------------------------------------------------------------------
    | HELPER TAMPILAN UMUM
    |-------------------------------------------------------------------
    | Helper ini dipakai ulang untuk escaping HTML, popup notifikasi,
    | dan indikator loading tombol agar perilaku antarfungsi konsisten.
    */
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

    /*
    |-------------------------------------------------------------------
    | HELPER REQUEST AJAX
    |-------------------------------------------------------------------
    | Seluruh komunikasi ke controller dipusatkan di sini supaya logika
    | CSRF, redirect sesi habis, dan parsing JSON tidak berulang.
    |
    | Tips Debugging:
    | - Jika response bukan JSON, periksa controller apakah mengembalikan
    |   halaman error HTML karena exception yang belum tertangani.
    */
    function requestJson(url, method, body) {
        var headers = {
            "X-Requested-With": "XMLHttpRequest",
            "Accept": "application/json"
        };

        if (body instanceof FormData) {
            body.delete(getCsrfName());
            body.append(getCsrfName(), getCsrfHash());
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

    /*
    |-------------------------------------------------------------------
    | INISIALISASI SELECT2
    |-------------------------------------------------------------------
    | Select2 dipakai untuk mempercantik select filter dan form modal
    | agar pengalaman pengguna seragam dengan modul manajemen lain.
    */
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

    /*
    |-------------------------------------------------------------------
    | PREVIEW FLYER LOWONGAN
    |-------------------------------------------------------------------
    | Fungsi ini mengatur thumbnail flyer di form tambah/edit supaya user
    | langsung melihat gambar aktif, gambar baru, atau placeholder default.
    |
    | Tips Debugging:
    | - Jika preview tidak berubah, cek selector data-kt-lowongan-flyer-input.
    */
    function setFlyerPreview(formElement, imageUrl) {
        var imageInput = formElement ? formElement.querySelector('[data-kt-lowongan-flyer-input="true"]') : null;
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

    /*
    |-------------------------------------------------------------------
    | RESET KOMPONEN FLYER
    |-------------------------------------------------------------------
    | Saat modal ditutup atau form direset, state input file dan flag
    | hapus dikembalikan ke posisi semula agar data lama tidak bocor.
    */
    function resetFlyerInput(formElement) {
        var imageInput = formElement ? formElement.querySelector('[data-kt-lowongan-flyer-input="true"]') : null;
        var removeInput;
        var fileInput;

        if (!imageInput) {
            return;
        }

        removeInput = imageInput.querySelector('input[name="flyer_remove"]');
        fileInput = imageInput.querySelector('input[type="file"][name="flyer_lowongan"]');

        setFlyerPreview(formElement, imageInput.getAttribute("data-image-input-initial") || "");

        if (removeInput) {
            removeInput.value = "";
        }

        if (fileInput) {
            fileInput.value = "";
        }
    }

    /*
    |-------------------------------------------------------------------
    | EVENT BINDING FLYER
    |-------------------------------------------------------------------
    | Binding dilakukan satu kali per komponen agar tombol change/cancel/
    | remove pada Metronic tetap sinkron dengan hidden input backend.
    */
    function bindFlyerInputs() {
        document.querySelectorAll('[data-kt-lowongan-flyer-input="true"]').forEach(function (imageInput) {
            var fileInput;
            var removeInput;
            var removeButton;
            var cancelButton;

            if (imageInput.getAttribute("data-lowongan-flyer-bound") === "1") {
                return;
            }

            fileInput = imageInput.querySelector('input[type="file"][name="flyer_lowongan"]');
            removeInput = imageInput.querySelector('input[name="flyer_remove"]');
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

            imageInput.setAttribute("data-lowongan-flyer-bound", "1");
        });
    }

    /*
    |-------------------------------------------------------------------
    | HELPER RESET FORM
    |-------------------------------------------------------------------
    | Blok ini menjaga agar modal tambah dan edit selalu dibuka dalam
    | keadaan bersih, termasuk select2 dan preview flyer.
    */
    function resetSelect2Value(formElement, selector, value) {
        var element = formElement.querySelector(selector);

        if (!element) {
            return;
        }

        element.value = value;

        if (typeof $ !== "undefined" && typeof $.fn.select2 !== "undefined") {
            $(element).trigger("change");
        }
    }

    function resetForm(formElement) {
        if (!formElement) {
            return;
        }

        formElement.reset();
        formElement.querySelectorAll("select").forEach(function (select) {
            resetSelect2Value(formElement, '[name="' + select.name + '"]', select.querySelector("option[selected]") ? select.value : "");
        });
        resetFlyerInput(formElement);
    }

    /*
    |-------------------------------------------------------------------
    | VALIDASI DASAR FORM
    |-------------------------------------------------------------------
    | Validasi frontend ini memeriksa field inti agar user mendapat
    | umpan balik cepat sebelum request dikirim ke backend.
    |
    | Tips Debugging:
    | - Jika submit berhenti tanpa request, cek field wajib di blok ini.
    */
    function validateForm(formElement) {
        var perusahaanInput = formElement.querySelector('[name="id_perusahaan"]');
        var judulInput = formElement.querySelector('[name="judul_lowongan"]');
        var posisiInput = formElement.querySelector('[name="posisi"]');

        if (!perusahaanInput || !perusahaanInput.value) {
            showError("DUDI wajib dipilih.");
            return false;
        }

        if (!judulInput || !judulInput.value.trim()) {
            showError("Judul lowongan wajib diisi.");
            return false;
        }

        if (!posisiInput || !posisiInput.value.trim()) {
            showError("Posisi wajib diisi.");
            return false;
        }

        return true;
    }

    /*
    |-------------------------------------------------------------------
    | FORMAT TAMPILAN BARIS TABEL
    |-------------------------------------------------------------------
    | Fungsi-fungsi berikut menyusun isi kolom tabel agar lebih rapi:
    | DUDI tampil ringkas, judul memakai thumbnail flyer, posisi memakai
    | badge kecil, dan kualifikasi dipotong agar tabel tidak berat.
    */
    function formatDate(value) {
        var date;

        if (!value) {
            return "";
        }

        date = new Date(String(value).replace(" ", "T"));

        if (Number.isNaN(date.getTime())) {
            return String(value);
        }

        return date.toLocaleDateString("id-ID", {
            day: "2-digit",
            month: "short",
            year: "numeric"
        });
    }

    function renderMetaBadge(text, badgeClass) {
        var value = String(text || "").trim();

        if (!value) {
            return "";
        }

        return '<span class="badge ' + (badgeClass || "badge-light-primary") + '">' + escapeHtml(value) + "</span>";
    }

    function renderDudiCell(row) {
        return '' +
            '<div class="d-flex flex-column">' +
                '<span class="text-gray-800 fw-bold mb-1">' + escapeHtml(row.nama_perusahaan || "-") + "</span>" +
                '<span class="text-muted fs-7">' + escapeHtml(row.lokasi_kerja || "Lokasi belum diisi") + "</span>" +
            "</div>";
    }

    function renderJudulCell(row) {
        var batasLamaran = formatDate(row.batas_lamaran);
        var tayangHingga = formatDate(row.tayang_hingga);
        var helper = batasLamaran ? "Batas lamaran " + batasLamaran : "Batas lamaran belum diatur";
        var metaBadges = "";

        if (tayangHingga) {
            metaBadges += renderMetaBadge("Tayang s.d. " + tayangHingga, "badge-light-info");
        }

        return '' +
            '<div class="kt-lowongan-item">' +
                '<div class="kt-lowongan-thumb">' +
                    '<img src="' + escapeHtml(row.flyer_url || config.blankFlyerUrl || "") + '" alt="' + escapeHtml(row.judul_lowongan || "Flyer Lowongan") + '">' +
                "</div>" +
                '<div class="kt-lowongan-content">' +
                    '<span class="kt-lowongan-title">' + escapeHtml(row.judul_lowongan || "-") + "</span>" +
                    '<span class="kt-lowongan-helper">' + escapeHtml(helper) + "</span>" +
                    '<div class="kt-lowongan-meta">' + metaBadges + "</div>" +
                "</div>" +
            "</div>";
    }

    function renderPosisiCell(row) {
        var jenisPekerjaan = {
            fulltime: "Full Time",
            parttime: "Part Time",
            magang: "Magang",
            kontrak: "Kontrak",
            freelance: "Freelance"
        };
        var sistemKerja = {
            onsite: "Onsite",
            remote: "Remote",
            hybrid: "Hybrid"
        };

        return '' +
            '<div class="d-flex flex-column">' +
                '<span class="text-gray-800 fw-bold mb-1">' + escapeHtml(row.posisi || "-") + "</span>" +
                '<div class="kt-lowongan-badges">' +
                    renderMetaBadge(jenisPekerjaan[row.jenis_pekerjaan] || row.jenis_pekerjaan || "-", "badge-light-primary") +
                    renderMetaBadge(sistemKerja[row.sistem_kerja] || row.sistem_kerja || "-", "badge-light-warning") +
                "</div>" +
            "</div>";
    }

    function renderKualifikasiCell(value) {
        var text = String(value || "").trim();
        var shortText;

        if (!text) {
            return '<span class="text-muted">Belum diisi</span>';
        }

        shortText = text.length > 90 ? text.substring(0, 90) + "..." : text;

        return '<span class="text-gray-700 kt-lowongan-clamp" title="' + escapeHtml(text) + '">' + escapeHtml(shortText) + "</span>";
    }

    function renderPemostingCell(row) {
        var tanggal = formatDate(row.dibuat_pada || row.diperbarui_pada);
        var helper = tanggal ? "Diposting " + tanggal : "Tanggal posting belum ada";

        return '' +
            '<div class="d-flex flex-column">' +
                '<span class="text-gray-800 fw-bold mb-1">' + escapeHtml(row.pemosting_nama || "System") + "</span>" +
                '<span class="text-muted fs-7">' + escapeHtml(helper) + "</span>" +
            "</div>";
    }

    function renderStatusBadge(status) {
        var normalized = String(status || "").toLowerCase();
        var map = {
            draft: ["badge-light-secondary", "Draft"],
            aktif: ["badge-light-success", "Aktif"],
            ditutup: ["badge-light-warning", "Ditutup"],
            kadaluarsa: ["badge-light-danger", "Kadaluarsa"]
        };
        var selected = map[normalized] || ["badge-light-secondary", normalized || "-"];

        return '<span class="badge ' + selected[0] + '">' + escapeHtml(selected[1]) + "</span>";
    }

    function renderActions() {
        return '' +
            '<div class="text-end">' +
                '<button type="button" class="btn btn-icon btn-active-light-warning w-30px h-30px me-2" data-action="edit-lowongan" title="Edit Lowongan">' +
                    '<i class="ki-duotone ki-pencil fs-3"><span class="path1"></span><span class="path2"></span></i>' +
                "</button>" +
                '<button type="button" class="btn btn-icon btn-active-light-danger w-30px h-30px" data-action="hapus-lowongan" title="Hapus Lowongan">' +
                    '<i class="ki-duotone ki-trash fs-3"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i>' +
                "</button>" +
            "</div>";
    }

    /*
    |-------------------------------------------------------------------
    | HELPER SELEKSI BARIS
    |-------------------------------------------------------------------
    | Blok ini mengelola checkbox header, checkbox per baris, dan toolbar
    | hapus massal agar perilaku tabel sama dengan modul admin/DUDI.
    */
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

    /*
    |-------------------------------------------------------------------
    | INISIALISASI DATATABLES
    |-------------------------------------------------------------------
    | Tabel lowongan dimuat secara server-side untuk menjaga performa
    | saat data bertambah dan agar filter/search tetap ringan.
    |
    | Tips Debugging:
    | - Jika kolom tidak sinkron, cek urutan columns dengan data JSON.
    */
    function reloadTable() {
        if (dataTable) {
            dataTable.ajax.reload(null, false);
        }
    }

    function initDataTable() {
        tableElement = document.querySelector("#kt_table_lowongan");
        searchInput = document.querySelector('[data-kt-lowongan-table-filter="search"]');
        filterForm = document.querySelector('[data-kt-lowongan-table-filter="form"]');
        toolbarBase = document.querySelector('[data-kt-lowongan-table-toolbar="base"]');
        toolbarSelected = document.querySelector('[data-kt-lowongan-table-toolbar="selected"]');
        selectedCountElement = document.querySelector('[data-kt-lowongan-table-select="selected_count"]');
        headerCheckbox = tableElement ? tableElement.querySelector("thead .form-check-input") : null;

        if (!tableElement || typeof $ === "undefined" || typeof $.fn.DataTable === "undefined") {
            return;
        }

        dataTable = $(tableElement).DataTable({
            processing: true,
            serverSide: true,
            searching: true,
            order: [[2, "asc"]],
            ajax: {
                url: config.indexUrl,
                type: "POST",
                data: function (d) {
                    d.id_perusahaan = getFilterValue('[data-kt-lowongan-table-filter="perusahaan"]');
                    d.status = getFilterValue('[data-kt-lowongan-table-filter="status"]');
                    d[getCsrfName()] = getCsrfHash();
                },
                dataSrc: function (json) {
                    setCsrfHash(json.csrfHash || "");
                    return json.data || [];
                },
                error: function (xhr) {
                    var response = xhr.responseJSON || {};
                    setCsrfHash(response.csrfHash || "");
                    showError(response.message || "Data lowongan gagal dimuat.");
                }
            },
            columns: [
                {
                    data: "id_lowongan",
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
                        return renderDudiCell(row);
                    }
                },
                {
                    data: null,
                    render: function (data, type, row) {
                        return renderJudulCell(row);
                    }
                },
                {
                    data: null,
                    render: function (data, type, row) {
                        return renderPosisiCell(row);
                    }
                },
                {
                    data: "kualifikasi",
                    render: function (data) {
                        return renderKualifikasiCell(data);
                    }
                },
                {
                    data: null,
                    render: function (data, type, row) {
                        return renderPemostingCell(row);
                    }
                },
                {
                    data: "status",
                    render: function (data) {
                        return renderStatusBadge(data);
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

    /*
    |-------------------------------------------------------------------
    | FILTER TABEL
    |-------------------------------------------------------------------
    | Filter DUDI dan status dipisahkan dari search umum agar admin
    | bisa mempersempit data dengan lebih cepat sesuai kebutuhan.
    */
    function initFilters() {
        if (!filterForm || !dataTable) {
            return;
        }

        var applyButton = filterForm.querySelector('[data-kt-lowongan-table-filter="filter"]');
        var resetButton = filterForm.querySelector('[data-kt-lowongan-table-filter="reset"]');

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

    /*
    |-------------------------------------------------------------------
    | HELPER PENGISIAN FORM EDIT
    |-------------------------------------------------------------------
    | Data dari baris tabel ditransformasikan kembali ke format input
    | form, termasuk konversi datetime MySQL ke datetime-local HTML.
    */
    function toDatetimeLocal(value) {
        var text = String(value || "").trim();

        if (!text) {
            return "";
        }

        return text.replace(" ", "T").substring(0, 16);
    }

    function fillEditForm(row) {
        if (!editForm || !row) {
            return;
        }

        resetForm(editForm);
        editForm.querySelector('[name="id_lowongan"]').value = row.id_lowongan || "";
        editForm.querySelector('[name="judul_lowongan"]').value = row.judul_lowongan || "";
        editForm.querySelector('[name="posisi"]').value = row.posisi || "";
        editForm.querySelector('[name="kualifikasi"]').value = row.kualifikasi || "";
        editForm.querySelector('[name="deskripsi_pekerjaan"]').value = row.deskripsi_pekerjaan || "";
        editForm.querySelector('[name="jumlah_kebutuhan"]').value = row.jumlah_kebutuhan || 1;
        editForm.querySelector('[name="pengalaman_min"]').value = row.pengalaman_min || "";
        editForm.querySelector('[name="rentang_gaji"]').value = row.rentang_gaji || "";
        editForm.querySelector('[name="lokasi_kerja"]').value = row.lokasi_kerja || "";
        editForm.querySelector('[name="batas_lamaran"]').value = row.batas_lamaran || "";
        editForm.querySelector('[name="tayang_hingga"]').value = toDatetimeLocal(row.tayang_hingga);

        resetSelect2Value(editForm, '[name="id_perusahaan"]', row.id_perusahaan || "");
        resetSelect2Value(editForm, '[name="jenis_pekerjaan"]', row.jenis_pekerjaan || "fulltime");
        resetSelect2Value(editForm, '[name="sistem_kerja"]', row.sistem_kerja || "onsite");
        resetSelect2Value(editForm, '[name="status"]', row.status || "draft");
        resetSelect2Value(editForm, '[name="pendidikan_min"]', row.pendidikan_min || "");
        setFlyerPreview(editForm, row.flyer_url || "");
    }

    /*
    |-------------------------------------------------------------------
    | SUBMIT FORM AJAX
    |-------------------------------------------------------------------
    | Blok ini dipakai bersama oleh modal tambah dan edit agar perilaku
    | submit, loading state, dan notifikasi tetap seragam.
    */
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

    /*
    |-------------------------------------------------------------------
    | MODAL TAMBAH LOWONGAN
    |-------------------------------------------------------------------
    | Inisialisasi modal tambah mengatur tombol tutup, submit, reset,
    | dan default value untuk select yang punya nilai bawaan.
    */
    function initAddModal() {
        addModalElement = document.getElementById("kt_modal_tambah_lowongan");
        addForm = document.getElementById("kt_modal_tambah_lowongan_form");

        if (!addModalElement || !addForm || typeof bootstrap === "undefined") {
            return;
        }

        addModal = new bootstrap.Modal(addModalElement);

        addModalElement.querySelector('[data-kt-lowongan-modal-action="close"]').addEventListener("click", function () {
            addModal.hide();
        });

        addModalElement.querySelector('[data-kt-lowongan-modal-action="cancel"]').addEventListener("click", function (event) {
            event.preventDefault();
            addModal.hide();
        });

        addForm.addEventListener("submit", function (event) {
            var submitButton = addModalElement.querySelector('[data-kt-lowongan-modal-action="submit"]');

            event.preventDefault();
            submitForm(addForm, addModal, config.simpanUrl, submitButton);
        });

        addModalElement.addEventListener("hidden.bs.modal", function () {
            resetForm(addForm);
            setFlyerPreview(addForm, "");
            resetSelect2Value(addForm, '[name="jenis_pekerjaan"]', "fulltime");
            resetSelect2Value(addForm, '[name="sistem_kerja"]', "onsite");
            resetSelect2Value(addForm, '[name="status"]', "draft");
        });

        resetForm(addForm);
        resetSelect2Value(addForm, '[name="jenis_pekerjaan"]', "fulltime");
        resetSelect2Value(addForm, '[name="sistem_kerja"]', "onsite");
        resetSelect2Value(addForm, '[name="status"]', "draft");
    }

    /*
    |-------------------------------------------------------------------
    | MODAL EDIT LOWONGAN
    |-------------------------------------------------------------------
    | Modal edit menggunakan pola yang sama dengan modal tambah, tetapi
    | ditambah proses fillEditForm agar data lama langsung muncul.
    */
    function initEditModal() {
        editModalElement = document.getElementById("kt_modal_edit_lowongan");
        editForm = document.getElementById("kt_modal_edit_lowongan_form");

        if (!editModalElement || !editForm || typeof bootstrap === "undefined") {
            return;
        }

        editModal = new bootstrap.Modal(editModalElement);

        editModalElement.querySelector('[data-kt-lowongan-edit-modal-action="close"]').addEventListener("click", function () {
            editModal.hide();
        });

        editModalElement.querySelector('[data-kt-lowongan-edit-modal-action="cancel"]').addEventListener("click", function (event) {
            event.preventDefault();
            editModal.hide();
        });

        editForm.addEventListener("submit", function (event) {
            var submitButton = editModalElement.querySelector('[data-kt-lowongan-edit-modal-action="submit"]');
            var idLowongan = editForm.querySelector('[name="id_lowongan"]').value;

            event.preventDefault();

            if (!idLowongan) {
                showError("Data lowongan tidak valid.");
                return;
            }

            submitForm(editForm, editModal, config.updateUrl.replace(/\/$/, "") + "/" + idLowongan, submitButton);
        });

        editModalElement.addEventListener("hidden.bs.modal", function () {
            resetForm(editForm);
            setFlyerPreview(editForm, "");
        });

        resetForm(editForm);
    }

    /*
    |-------------------------------------------------------------------
    | AKSI HAPUS DATA
    |-------------------------------------------------------------------
    | Aksi hapus satuan dan massal dipisahkan ke helper tersendiri agar
    | konfirmasi dan request AJAX lebih mudah dipelihara.
    */
    function handleDelete(row) {
        showAlert({
            text: "Hapus lowongan " + (row.judul_lowongan || "ini") + "?",
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

            requestJson(config.hapusUrl.replace(/\/$/, "") + "/" + row.id_lowongan, "GET")
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
            showError("Pilih minimal satu lowongan terlebih dahulu.");
            return;
        }

        showAlert({
            text: "Hapus " + selectedIds.length + " lowongan terpilih?",
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

    /*
    |-------------------------------------------------------------------
    | EVENT AKSI TABEL
    |-------------------------------------------------------------------
    | Menghubungkan interaksi checkbox, tombol edit, dan tombol hapus
    | dengan data row yang sedang aktif di DataTables.
    */
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
            var editButton = event.target.closest('[data-action="edit-lowongan"]');
            var deleteButton = event.target.closest('[data-action="hapus-lowongan"]');
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

        bulkDeleteButton = document.querySelector('[data-kt-lowongan-table-select="delete_selected"]');
        if (bulkDeleteButton) {
            bulkDeleteButton.addEventListener("click", handleBulkDelete);
        }
    }

    return {
        init: function () {
            /*
            |-------------------------------------------------------------------
            | BOOTSTRAP MODUL LOWONGAN
            |-------------------------------------------------------------------
            | Seluruh komponen frontend diinisialisasi dari sini agar urutan
            | pemanggilan tetap jelas dan mudah ditelusuri saat debugging.
            */
            initSelect2();
            bindFlyerInputs();
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
        KTLowonganList.init();
    });
} else {
    document.addEventListener("DOMContentLoaded", function () {
        KTLowonganList.init();
    });
}
