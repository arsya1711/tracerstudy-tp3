"use strict";

/*
|-------------------------------------------------------------------
| MODUL KERJASAMA JAVASCRIPT
|-------------------------------------------------------------------
| Penjelasan fungsi kode ini: file ini menangani DataTables
| client-side, AJAX penuh untuk ambil/tambah/edit/hapus data,
| auto-generate slug, pencarian tabel, dan SweetAlert2 pada halaman
| master jenis Kerjasama.
| Alur kerja: setelah halaman dimuat, script menginisialisasi tabel,
| modal tambah, modal edit, token CSRF, lalu semua interaksi user
| diproses melalui fetch tanpa reload halaman penuh.
|
| Tips Debugging:
| - Jika tabel kosong, cek request AJAX GET ke endpoint index mengembalikan JSON success.
| - Jika slug tidak terisi otomatis, cek input name nama_kerjasama dan slug_kerjasama pada form.
*/
(function () {
    var tableElement = document.getElementById("kt_kerjasama_table");

    if (!tableElement) {
        return;
    }

    var config = window.ktKerjasamaConfig || {};
    var dataTable = null;
    var addModalElement = document.getElementById("kt_modal_tambah_kerjasama");
    var editModalElement = document.getElementById("kt_modal_edit_kerjasama");
    var addForm = document.getElementById("kt_modal_tambah_kerjasama_form");
    var editForm = document.getElementById("kt_modal_edit_kerjasama_form");
    var addModal = addModalElement ? new bootstrap.Modal(addModalElement) : null;
    var editModal = editModalElement ? new bootstrap.Modal(editModalElement) : null;

    /*
    |-------------------------------------------------------------------
    | METHOD GET CSRF CONFIG
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini mengambil token CSRF dan
    | nama field token dari meta tag yang tersedia di head layout.
    | Alur kerja: setiap request AJAX memanggil method ini sebelum
    | membuat payload agar token terbaru selalu ikut dikirim.
    |
    | Tips Debugging:
    | - Jika token kosong, cek meta csrf-token dan csrf-header-name di partial head.
    */
    function getCsrfConfig() {
        var csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
        var csrfHeaderMeta = document.querySelector('meta[name="csrf-header-name"]');

        return {
            token: csrfTokenMeta ? csrfTokenMeta.getAttribute("content") : "",
            fieldName: csrfHeaderMeta ? csrfHeaderMeta.getAttribute("content") : ""
        };
    }

    /*
    |-------------------------------------------------------------------
    | METHOD UPDATE CSRF TOKEN
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini memperbarui token CSRF di
    | meta head setiap kali server mengirim csrfHash baru.
    | Alur kerja: setelah response JSON diterima, script memanggil
    | method ini agar request berikutnya memakai token valid terbaru.
    |
    | Tips Debugging:
    | - Jika request kedua gagal CSRF, cek method ini terpanggil setelah response sukses maupun error.
    */
    function updateCsrfToken(newToken) {
        var csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');

        if (csrfTokenMeta && newToken) {
            csrfTokenMeta.setAttribute("content", newToken);
        }
    }

    /*
    |-------------------------------------------------------------------
    | METHOD BUILD FORM DATA
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini membangun FormData dari
    | form lalu menambahkan token CSRF untuk request AJAX.
    | Alur kerja: method menerima elemen form, membaca semua input,
    | menambahkan token, lalu mengembalikan FormData untuk fetch.
    |
    | Tips Debugging:
    | - Jika field form tidak ikut terkirim, cek atribut name pada input modal.
    */
    function buildFormData(form) {
        var csrf = getCsrfConfig();
        var formData = new FormData(form);

        if (csrf.fieldName && csrf.token) {
            formData.append(csrf.fieldName, csrf.token);
        }

        return formData;
    }

    /*
    |-------------------------------------------------------------------
    | METHOD SHOW ERROR ALERT
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini menampilkan SweetAlert2
    | untuk error validasi atau kegagalan request AJAX.
    | Alur kerja: method dipanggil saat response bukan success atau
    | terjadi exception selama proses fetch.
    |
    | Tips Debugging:
    | - Jika popup tidak tampil, cek SweetAlert2 sudah termuat di footer layout.
    */
    function showErrorAlert(message) {
        Swal.fire({
            text: message || "Terjadi kesalahan. Silakan coba lagi.",
            icon: "error",
            buttonsStyling: false,
            confirmButtonText: "Ok, mengerti!",
            customClass: {
                confirmButton: "btn btn-primary"
            }
        });
    }

    /*
    |-------------------------------------------------------------------
    | METHOD EXTRACT ERROR MESSAGE
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini mengambil pesan error
    | yang paling relevan dari response JSON validasi atau server.
    | Alur kerja: saat response gagal diterima, method memprioritaskan
    | pesan validator lalu fallback ke message umum dari server.
    |
    | Tips Debugging:
    | - Jika popup hanya menampilkan pesan umum, cek key errors pada JSON response controller.
    */
    function extractErrorMessage(payload, fallbackMessage) {
        if (payload && payload.errors) {
            var daftarError = Object.values(payload.errors).filter(function (item) {
                return Boolean(item);
            });

            if (daftarError.length > 0) {
                return daftarError[0];
            }
        }

        if (payload && payload.message) {
            return payload.message;
        }

        return fallbackMessage;
    }

    /*
    |-------------------------------------------------------------------
    | METHOD SLUGIFY
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini mengubah teks biasa
    | menjadi slug url-friendly dengan huruf kecil dan tanda hubung.
    | Alur kerja: method membersihkan karakter non alfanumerik,
    | mengganti spasi dengan dash, lalu merapikan dash berlebih.
    |
    | Tips Debugging:
    | - Jika slug berisi spasi atau simbol aneh, cek regex pembersihan pada method ini.
    */
    function slugify(value) {
        return String(value || "")
            .toLowerCase()
            .trim()
            .replace(/[^a-z0-9\s-]/g, "")
            .replace(/\s+/g, "-")
            .replace(/-+/g, "-")
            .replace(/^-|-$/g, "");
    }

    /*
    |-------------------------------------------------------------------
    | METHOD VALIDATE FORM
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini memeriksa input nama
    | dan slug kerjasama sebelum request dikirim ke server.
    | Alur kerja: saat submit ditekan, method membaca nilai input,
    | memastikan tidak kosong, memeriksa panjang nama, dan memvalidasi
    | format slug alpha dash.
    |
    | Tips Debugging:
    | - Jika form selalu invalid, cek input name nama_kerjasama dan slug_kerjasama pada modal.
    */
    function validateForm(form) {
        var namaInput = form.querySelector('[name="nama_kerjasama"]');
        var slugInput = form.querySelector('[name="slug_kerjasama"]');
        var nama = namaInput ? namaInput.value.trim() : "";
        var slug = slugInput ? slugInput.value.trim() : "";

        if (!nama) {
            showErrorAlert("Nama kerjasama wajib diisi.");
            return false;
        }

        if (nama.length > 150) {
            showErrorAlert("Nama kerjasama maksimal 150 karakter.");
            return false;
        }

        if (!slug) {
            showErrorAlert("Slug kerjasama wajib diisi.");
            return false;
        }

        if (!/^[a-zA-Z0-9_-]+$/.test(slug)) {
            showErrorAlert("Slug kerjasama hanya boleh berisi huruf, angka, dash, atau underscore.");
            return false;
        }

        return true;
    }

    /*
    |-------------------------------------------------------------------
    | METHOD INIT TABLE
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini mengaktifkan DataTables
    | client-side pada tabel kerjasama.
    | Alur kerja: method membaca elemen tabel lalu membangun instance
    | DataTables untuk dipakai pencarian dan render data AJAX.
    |
    | Tips Debugging:
    | - Jika DataTable tidak aktif, cek datatables.bundle.js termuat.
    */
    function initTable() {
        dataTable = $(tableElement).DataTable({
            info: false,
            order: [],
            pageLength: 10,
            columnDefs: [
                { orderable: false, targets: 0 },
                { orderable: false, targets: 5 }
            ]
        });
    }

    /*
    |-------------------------------------------------------------------
    | METHOD INIT SEARCH
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini menghubungkan input
    | pencarian dengan DataTables modul Kerjasama.
    | Alur kerja: setiap karakter yang diketik user pada input search
    | dipakai untuk memfilter tabel secara client-side.
    |
    | Tips Debugging:
    | - Jika search tidak bekerja, cek atribut data-kt-kerjasama-filter pada input.
    */
    function initSearch() {
        var searchInput = document.querySelector('[data-kt-kerjasama-filter="search"]');

        if (!searchInput || !dataTable) {
            return;
        }

        searchInput.addEventListener("keyup", function (event) {
            dataTable.search(event.target.value).draw();
        });
    }

    /*
    |-------------------------------------------------------------------
    | METHOD ESCAPE HTML
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini membersihkan karakter
    | khusus sebelum teks dimasukkan ke HTML tabel.
    | Alur kerja: method menerima string biasa lalu mengubahnya
    | menjadi entity HTML yang aman.
    |
    | Tips Debugging:
    | - Jika karakter tampil aneh, cek hasil escaping method ini.
    */
    function escapeHtml(value) {
        return String(value || "")
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    /*
    |-------------------------------------------------------------------
    | METHOD ESCAPE ATTRIBUTE
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini menyiapkan string untuk
    | aman dipasang pada atribut HTML data-*.
    | Alur kerja: method melakukan escape HTML dan mengubah newline
    | menjadi entity agar tetap aman saat dibaca ulang oleh DOM.
    |
    | Tips Debugging:
    | - Jika textarea edit kehilangan line break, cek konversi newline pada method ini.
    */
    function escapeAttribute(value) {
        return escapeHtml(value).replace(/\r?\n/g, "&#10;");
    }

    /*
    |-------------------------------------------------------------------
    | METHOD BUILD DESKRIPSI HTML
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini membentuk isi kolom
    | deskripsi agar tampil rapi pada tabel.
    | Alur kerja: jika deskripsi kosong method menampilkan placeholder,
    | sedangkan jika ada isi method menjaga karakter aman dan line break.
    |
    | Tips Debugging:
    | - Jika deskripsi tidak tampil, cek field deskripsi pada data response JSON.
    */
    function buildDeskripsiHtml(deskripsi) {
        var teks = String(deskripsi || "").trim();

        if (!teks) {
            return '<span class="text-muted">Tidak ada deskripsi.</span>';
        }

        return escapeHtml(teks).replace(/\r?\n/g, "<br>");
    }

    /*
    |-------------------------------------------------------------------
    | METHOD BUILD SLUG BADGE HTML
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini membentuk tampilan badge
    | slug agar lebih mudah dibaca pada tabel.
    | Alur kerja: slug dari server dibersihkan dulu lalu dibungkus
    | dalam badge Metronic berwarna lembut.
    |
    | Tips Debugging:
    | - Jika badge slug kosong, cek field slug_kerjasama pada response JSON.
    */
    function buildSlugBadgeHtml(slug) {
        return '<span class="badge badge-light-primary">' + escapeHtml(slug) + "</span>";
    }

    /*
    |-------------------------------------------------------------------
    | METHOD BUILD ROW HTML
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini membangun HTML satu baris
    | tabel dari data kerjasama hasil response JSON server.
    | Alur kerja: setelah data list diterima, method dipakai untuk
    | menyusun markup baris sebelum dimasukkan ke DataTable.
    |
    | Tips Debugging:
    | - Jika tombol edit/hapus tidak aktif, cek atribut data-kt-kerjasama-table-filter pada HTML ini.
    */
    function buildRowHtml(row) {
        var deskripsi = row.deskripsi || "";

        return [
            '<tr data-id="' + row.id_kerjasama + '" data-nama="' + escapeAttribute(row.nama_kerjasama) + '" data-slug="' + escapeAttribute(row.slug_kerjasama) + '" data-deskripsi="' + escapeAttribute(deskripsi) + '">',
            '<td><div class="form-check form-check-sm form-check-custom form-check-solid"><input class="form-check-input form-check-input-row" type="checkbox" value="' + row.id_kerjasama + '" /></div></td>',
            '<td class="kerjasama-nama">' + escapeHtml(row.nama_kerjasama) + '</td>',
            '<td class="kerjasama-slug">' + buildSlugBadgeHtml(row.slug_kerjasama) + '</td>',
            '<td class="kerjasama-deskripsi">' + buildDeskripsiHtml(deskripsi) + '</td>',
            '<td class="kerjasama-jumlah-mou">' + (row.jumlah_mou || 0) + '</td>',
            '<td class="text-end">',
            '<button type="button" class="btn btn-icon btn-active-light-primary w-30px h-30px me-3" data-kt-kerjasama-table-filter="edit_row">',
            '<i class="ki-duotone ki-setting-3 fs-3"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i>',
            '</button>',
            '<button type="button" class="btn btn-icon btn-active-light-primary w-30px h-30px" data-kt-kerjasama-table-filter="delete_row">',
            '<i class="ki-duotone ki-trash fs-3"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i>',
            '</button>',
            '</td>',
            '</tr>'
        ].join("");
    }

    /*
    |-------------------------------------------------------------------
    | METHOD RENDER TABLE DATA
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini me-render seluruh data
    | kerjasama ke DataTables secara client-side.
    | Alur kerja: method membersihkan isi tabel lama, membangun node
    | tr baru dari response JSON, lalu menggambar ulang DataTable.
    |
    | Tips Debugging:
    | - Jika data dobel, cek method ini selalu memanggil clear() sebelum add().
    */
    function renderTableData(rows) {
        var rowNodes = [];

        dataTable.clear();

        rows.forEach(function (row) {
            var tempWrapper = document.createElement("tbody");
            tempWrapper.innerHTML = buildRowHtml(row);
            rowNodes.push(tempWrapper.querySelector("tr"));
        });

        if (rowNodes.length > 0) {
            dataTable.rows.add($(rowNodes));
        }

        dataTable.draw(false);
    }

    /*
    |-------------------------------------------------------------------
    | METHOD TOGGLE SUBMIT STATE
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini mengatur indikator loading
    | dan status disabled pada tombol submit modal.
    | Alur kerja: sebelum fetch dimulai tombol dinonaktifkan, lalu
    | setelah response diterima tombol dikembalikan normal.
    |
    | Tips Debugging:
    | - Jika spinner tidak hilang, cek method ini terpanggil pada blok then dan catch.
    */
    function toggleSubmitState(button, isLoading) {
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
    | METHOD FETCH KERJASAMA
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini mengambil seluruh data
    | kerjasama aktif dari endpoint index berbasis AJAX JSON.
    | Alur kerja: script mengirim GET dengan header AJAX, menerima
    | response JSON, memperbarui CSRF, lalu merender isi DataTable.
    |
    | Tips Debugging:
    | - Jika fetch gagal, cek config.indexUrl dan controller index mengembalikan JSON saat request AJAX.
    */
    function fetchKerjasama() {
        return fetch(config.indexUrl, {
            method: "GET",
            headers: {
                "X-Requested-With": "XMLHttpRequest"
            }
        })
            .then(parseJsonResponse)
            .then(function (response) {
                updateCsrfToken(response.csrfHash);
                renderTableData(response.data || []);
                return response;
            })
            .catch(function (error) {
                updateCsrfToken(error.csrfHash);
                showErrorAlert(extractErrorMessage(error, "Data kerjasama gagal dimuat."));
                throw error;
            });
    }

    /*
    |-------------------------------------------------------------------
    | METHOD SET AUTO SLUG MODE
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini menandai apakah form masih
    | memakai slug otomatis atau slug sudah diubah manual oleh user.
    | Alur kerja: nilai mode disimpan pada data attribute form agar
    | sinkronisasi nama ke slug bisa dikontrol.
    |
    | Tips Debugging:
    | - Jika slug tidak ikut berubah, cek data-auto-slug pada form masih bernilai true.
    */
    function setAutoSlugMode(form, isAuto) {
        if (!form) {
            return;
        }

        form.setAttribute("data-auto-slug", isAuto ? "true" : "false");
    }

    /*
    |-------------------------------------------------------------------
    | METHOD GET AUTO SLUG MODE
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini membaca status sinkronisasi
    | slug otomatis pada form tambah atau edit.
    | Alur kerja: data attribute form dicek lalu dikembalikan sebagai boolean.
    |
    | Tips Debugging:
    | - Jika slug terasa mengunci manual input, cek nilai default method ini.
    */
    function getAutoSlugMode(form) {
        return form && form.getAttribute("data-auto-slug") !== "false";
    }

    /*
    |-------------------------------------------------------------------
    | METHOD BIND SLUG GENERATOR
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini menghubungkan input nama
    | dengan slug otomatis yang url-friendly pada form modal.
    | Alur kerja: selama mode auto aktif, perubahan nama langsung
    | menghasilkan slug baru; jika user mengubah slug manual maka
    | mode auto dimatikan.
    |
    | Tips Debugging:
    | - Jika slug tidak berubah saat nama diketik, cek listener input pada method ini.
    */
    function bindSlugGenerator(form) {
        if (!form) {
            return;
        }

        var namaInput = form.querySelector('[name="nama_kerjasama"]');
        var slugInput = form.querySelector('[name="slug_kerjasama"]');

        if (!namaInput || !slugInput) {
            return;
        }

        namaInput.addEventListener("input", function () {
            if (!getAutoSlugMode(form)) {
                return;
            }

            slugInput.value = slugify(namaInput.value);
        });

        slugInput.addEventListener("input", function () {
            var slugManual = slugify(slugInput.value);

            if (slugInput.value !== slugManual) {
                slugInput.value = slugManual;
            }

            if (slugManual === "" || slugManual === slugify(namaInput.value)) {
                setAutoSlugMode(form, true);
                return;
            }

            setAutoSlugMode(form, false);
        });
    }

    /*
    |-------------------------------------------------------------------
    | METHOD INIT TAMBAH MODAL
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini menangani aksi close,
    | discard, dan submit pada modal tambah kerjasama.
    | Alur kerja: method memasang listener ke form dan tombol modal,
    | lalu mengirim AJAX simpan saat form valid.
    |
    | Tips Debugging:
    | - Jika modal tambah tidak submit, cek id form tambah dan tombol submit.
    */
    function initTambahModal() {
        if (!addModalElement || !addForm) {
            return;
        }

        var closeButton = addModalElement.querySelector('[data-kt-kerjasama-modal-action="close"]');
        var cancelButton = addModalElement.querySelector('[data-kt-kerjasama-modal-action="cancel"]');
        var submitButton = addModalElement.querySelector('[data-kt-kerjasama-modal-action="submit"]');

        setAutoSlugMode(addForm, true);
        bindSlugGenerator(addForm);

        if (closeButton) {
            closeButton.addEventListener("click", function (event) {
                event.preventDefault();
                confirmCloseForm(addModal, addForm, "Apakah Anda yakin ingin menutup form tambah kerjasama?");
            });
        }

        if (cancelButton) {
            cancelButton.addEventListener("click", function (event) {
                event.preventDefault();
                confirmDiscardForm(addModal, addForm, "Apakah Anda yakin ingin membatalkan data kerjasama baru?");
            });
        }

        addForm.addEventListener("submit", function (event) {
            event.preventDefault();

            if (!validateForm(addForm)) {
                return;
            }

            toggleSubmitState(submitButton, true);

            fetch(config.simpanUrl, {
                method: "POST",
                headers: {
                    "X-Requested-With": "XMLHttpRequest"
                },
                body: buildFormData(addForm)
            })
                .then(parseJsonResponse)
                .then(function (response) {
                    updateCsrfToken(response.csrfHash);

                    if (response.status !== "success") {
                        showErrorAlert(extractErrorMessage(response, "Data kerjasama gagal disimpan."));
                        return null;
                    }

                    return fetchKerjasama().then(function () {
                        addForm.reset();
                        setAutoSlugMode(addForm, true);
                        addModal.hide();

                        Swal.fire({
                            text: response.message,
                            icon: "success",
                            buttonsStyling: false,
                            confirmButtonText: "Ok, mengerti!",
                            customClass: {
                                confirmButton: "btn btn-primary"
                            }
                        });
                    });
                })
                .catch(function (error) {
                    updateCsrfToken(error.csrfHash);
                    showErrorAlert(extractErrorMessage(error, "Data kerjasama gagal disimpan."));
                })
                .finally(function () {
                    toggleSubmitState(submitButton, false);
                });
        });
    }

    /*
    |-------------------------------------------------------------------
    | METHOD INIT EDIT MODAL
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini menangani aksi close,
    | discard, dan submit pada modal edit kerjasama.
    | Alur kerja: method memasang listener ke form modal edit, lalu
    | mengirim AJAX update saat data valid dan id tersedia.
    |
    | Tips Debugging:
    | - Jika modal edit kosong, cek atribut data-id, data-nama, data-slug, dan data-deskripsi pada tr.
    */
    function initEditModal() {
        if (!editModalElement || !editForm) {
            return;
        }

        var closeButton = editModalElement.querySelector('[data-kt-kerjasama-edit-modal-action="close"]');
        var cancelButton = editModalElement.querySelector('[data-kt-kerjasama-edit-modal-action="cancel"]');
        var submitButton = editModalElement.querySelector('[data-kt-kerjasama-edit-modal-action="submit"]');

        setAutoSlugMode(editForm, true);
        bindSlugGenerator(editForm);

        if (closeButton) {
            closeButton.addEventListener("click", function (event) {
                event.preventDefault();
                confirmCloseForm(editModal, editForm, "Apakah Anda yakin ingin menutup form edit kerjasama?");
            });
        }

        if (cancelButton) {
            cancelButton.addEventListener("click", function (event) {
                event.preventDefault();
                confirmDiscardForm(editModal, editForm, "Apakah Anda yakin ingin membatalkan perubahan kerjasama?");
            });
        }

        editForm.addEventListener("submit", function (event) {
            event.preventDefault();

            if (!validateForm(editForm)) {
                return;
            }

            var idKerjasama = editForm.querySelector('[name="id_kerjasama"]').value;
            if (!idKerjasama) {
                showErrorAlert("Data kerjasama yang akan diperbarui tidak ditemukan.");
                return;
            }

            toggleSubmitState(submitButton, true);

            fetch(config.updateUrl + "/" + idKerjasama, {
                method: "POST",
                headers: {
                    "X-Requested-With": "XMLHttpRequest"
                },
                body: buildFormData(editForm)
            })
                .then(parseJsonResponse)
                .then(function (response) {
                    updateCsrfToken(response.csrfHash);

                    if (response.status !== "success") {
                        showErrorAlert(extractErrorMessage(response, "Data kerjasama gagal diperbarui."));
                        return null;
                    }

                    return fetchKerjasama().then(function () {
                        editModal.hide();

                        Swal.fire({
                            text: response.message,
                            icon: "success",
                            buttonsStyling: false,
                            confirmButtonText: "Ok, mengerti!",
                            customClass: {
                                confirmButton: "btn btn-primary"
                            }
                        });
                    });
                })
                .catch(function (error) {
                    updateCsrfToken(error.csrfHash);
                    showErrorAlert(extractErrorMessage(error, "Data kerjasama gagal diperbarui."));
                })
                .finally(function () {
                    toggleSubmitState(submitButton, false);
                });
        });
    }

    /*
    |-------------------------------------------------------------------
    | METHOD INIT TABLE ACTIONS
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini menghubungkan tombol edit
    | dan hapus pada seluruh baris tabel menggunakan event delegation.
    | Alur kerja: script menangkap klik di tabel, mencari tombol aksi,
    | lalu memproses row yang sesuai tanpa perlu bind ulang tiap refresh.
    |
    | Tips Debugging:
    | - Jika tombol edit/hapus diam, cek atribut data-kt-kerjasama-table-filter pada button baris.
    */
    function initTableActions() {
        tableElement.addEventListener("click", function (event) {
            var editButton = event.target.closest('[data-kt-kerjasama-table-filter="edit_row"]');
            var deleteButton = event.target.closest('[data-kt-kerjasama-table-filter="delete_row"]');
            var rowElement = event.target.closest("tr");

            if (!rowElement) {
                return;
            }

            if (editButton) {
                event.preventDefault();
                populateEditForm(rowElement);
                return;
            }

            if (deleteButton) {
                event.preventDefault();
                handleDeleteRow(rowElement);
            }
        });
    }

    /*
    |-------------------------------------------------------------------
    | METHOD POPULATE EDIT FORM
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini mengisi modal edit dari
    | data yang disimpan pada atribut tr tabel.
    | Alur kerja: saat tombol edit diklik, method membaca dataset baris,
    | mengisi input hidden, nama, slug, dan deskripsi, lalu membuka modal edit.
    |
    | Tips Debugging:
    | - Jika input edit kosong, cek atribut data-id, data-nama, data-slug, dan data-deskripsi pada tr.
    */
    function populateEditForm(rowElement) {
        if (!editForm || !editModal) {
            return;
        }

        editForm.querySelector('[name="id_kerjasama"]').value = rowElement.getAttribute("data-id") || "";
        editForm.querySelector('[name="nama_kerjasama"]').value = rowElement.getAttribute("data-nama") || "";
        editForm.querySelector('[name="slug_kerjasama"]').value = rowElement.getAttribute("data-slug") || "";
        editForm.querySelector('[name="deskripsi"]').value = rowElement.getAttribute("data-deskripsi") || "";
        setAutoSlugMode(editForm, true);
        editModal.show();
    }

    /*
    |-------------------------------------------------------------------
    | METHOD HANDLE DELETE ROW
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini menampilkan konfirmasi
    | hapus lalu menghapus data kerjasama secara AJAX.
    | Alur kerja: user klik tombol hapus, SweetAlert2 meminta
    | konfirmasi, lalu script memanggil endpoint hapus dan refresh
    | isi DataTable jika server mengembalikan success.
    |
    | Tips Debugging:
    | - Jika data tidak hilang setelah hapus, cek endpoint hapus dan fetchKerjasama() terpanggil lagi.
    */
    function handleDeleteRow(rowElement) {
        var idKerjasama = rowElement.getAttribute("data-id");
        var namaKerjasama = rowElement.getAttribute("data-nama");

        Swal.fire({
            text: "Apakah Anda yakin ingin menghapus kerjasama " + namaKerjasama + "?",
            icon: "warning",
            showCancelButton: true,
            buttonsStyling: false,
            confirmButtonText: "Ya, hapus!",
            cancelButtonText: "Batal",
            customClass: {
                confirmButton: "btn fw-bold btn-danger",
                cancelButton: "btn fw-bold btn-active-light-primary"
            }
        }).then(function (result) {
            if (!result.isConfirmed) {
                return;
            }

            fetch(config.hapusUrl + "/" + idKerjasama, {
                method: "GET",
                headers: {
                    "X-Requested-With": "XMLHttpRequest"
                }
            })
                .then(parseJsonResponse)
                .then(function (response) {
                    updateCsrfToken(response.csrfHash);

                    if (response.status !== "success") {
                        showErrorAlert(extractErrorMessage(response, "Data kerjasama gagal dihapus."));
                        return null;
                    }

                    return fetchKerjasama().then(function () {
                        Swal.fire({
                            text: response.message,
                            icon: "success",
                            buttonsStyling: false,
                            confirmButtonText: "Ok, mengerti!",
                            customClass: {
                                confirmButton: "btn btn-primary"
                            }
                        });
                    });
                })
                .catch(function (error) {
                    updateCsrfToken(error.csrfHash);
                    showErrorAlert(extractErrorMessage(error, "Data kerjasama gagal dihapus."));
                });
        });
    }

    /*
    |-------------------------------------------------------------------
    | METHOD CONFIRM CLOSE FORM
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini menampilkan konfirmasi
    | saat pengguna ingin menutup modal form.
    | Alur kerja: ketika tombol close diklik, method menampilkan popup
    | warning lalu menutup modal jika user menyetujui.
    |
    | Tips Debugging:
    | - Jika modal tidak tertutup setelah konfirmasi, cek instance bootstrap.Modal yang dikirim.
    */
    function confirmCloseForm(modalInstance, form, message) {
        Swal.fire({
            text: message,
            icon: "warning",
            showCancelButton: true,
            buttonsStyling: false,
            confirmButtonText: "Ya, tutup!",
            cancelButtonText: "Tidak",
            customClass: {
                confirmButton: "btn btn-primary",
                cancelButton: "btn btn-active-light"
            }
        }).then(function (result) {
            if (result.isConfirmed) {
                form.reset();
                setAutoSlugMode(form, true);
                modalInstance.hide();
            }
        });
    }

    /*
    |-------------------------------------------------------------------
    | METHOD CONFIRM DISCARD FORM
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini menampilkan konfirmasi
    | saat pengguna ingin membatalkan isi form modal.
    | Alur kerja: saat tombol discard ditekan, method meminta
    | persetujuan, lalu me-reset form dan menutup modal jika disetujui.
    |
    | Tips Debugging:
    | - Jika form tidak ter-reset, cek method reset() pada element form.
    */
    function confirmDiscardForm(modalInstance, form, message) {
        Swal.fire({
            text: message,
            icon: "warning",
            showCancelButton: true,
            buttonsStyling: false,
            confirmButtonText: "Ya, discard!",
            cancelButtonText: "Tidak",
            customClass: {
                confirmButton: "btn btn-primary",
                cancelButton: "btn btn-active-light"
            }
        }).then(function (result) {
            if (result.isConfirmed) {
                form.reset();
                setAutoSlugMode(form, true);
                modalInstance.hide();
            }
        });
    }

    /*
    |-------------------------------------------------------------------
    | METHOD PARSE JSON RESPONSE
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini membaca response fetch
    | dan melempar error terstruktur jika status HTTP bukan sukses.
    | Alur kerja: fetch memanggil method ini setelah menerima
    | response, lalu method mengubah body menjadi JSON.
    |
    | Tips Debugging:
    | - Jika error parsing JSON, cek endpoint controller mengembalikan JSON valid.
    */
    function parseJsonResponse(response) {
        return response.json().then(function (json) {
            if (!response.ok) {
                throw json;
            }

            return json;
        });
    }

    initTable();
    initSearch();
    initTambahModal();
    initEditModal();
    initTableActions();
    fetchKerjasama();
})();
