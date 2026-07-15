"use strict";

/*
|-------------------------------------------------------------------
| MODUL AKTIVITAS JAVASCRIPT
|-------------------------------------------------------------------
| Penjelasan fungsi kode ini: file ini menangani DataTables
| client-side, AJAX penuh untuk ambil/tambah/edit/hapus data,
| pencarian tabel, dan SweetAlert2 pada halaman Aktivitas alumni.
| Alur kerja: setelah halaman dimuat, script menginisialisasi tabel,
| modal tambah, modal edit, token CSRF, lalu semua interaksi user
| diproses melalui fetch tanpa reload halaman penuh.
|
| Tips Debugging:
| - Jika tabel kosong, cek request AJAX GET ke endpoint index mengembalikan JSON success.
| - Jika POST gagal 403, cek token CSRF pada meta head dan payload FormData.
*/
(function () {
    var tableElement = document.getElementById("kt_aktivitas_table");

    if (!tableElement) {
        return;
    }

    var config = window.ktAktivitasConfig || {};
    var dataTable = null;
    var addModalElement = document.getElementById("kt_modal_tambah_aktivitas");
    var editModalElement = document.getElementById("kt_modal_edit_aktivitas");
    var addForm = document.getElementById("kt_modal_tambah_aktivitas_form");
    var editForm = document.getElementById("kt_modal_edit_aktivitas_form");
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
        var formData = new FormData(form || undefined);

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
    | METHOD VALIDATE FORM
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini memeriksa input nama
    | aktivitas sebelum request dikirim ke server.
    | Alur kerja: saat submit ditekan, method membaca nilai
    | nama_aktivitas, memastikan tidak kosong dan maksimal 100
    | karakter, lalu mengembalikan true atau false.
    |
    | Tips Debugging:
    | - Jika form selalu invalid, cek input name nama_aktivitas pada modal.
    */
    function validateForm(form) {
        var namaInput = form.querySelector('[name="nama_aktivitas"]');
        var nama = namaInput ? namaInput.value.trim() : "";

        if (!nama) {
            showErrorAlert("Nama aktivitas wajib diisi.");
            return false;
        }

        if (nama.length > 100) {
            showErrorAlert("Nama aktivitas maksimal 100 karakter.");
            return false;
        }

        return true;
    }

    /*
    |-------------------------------------------------------------------
    | METHOD INIT TABLE
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini mengaktifkan DataTables
    | client-side pada tabel aktivitas.
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
                { orderable: false, targets: 4 }
            ]
        });
    }

    /*
    |-------------------------------------------------------------------
    | METHOD INIT SEARCH
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini menghubungkan input
    | pencarian dengan DataTables modul Aktivitas.
    | Alur kerja: setiap karakter yang diketik user pada input search
    | dipakai untuk memfilter tabel secara client-side.
    |
    | Tips Debugging:
    | - Jika search tidak bekerja, cek atribut data-kt-aktivitas-filter pada input.
    */
    function initSearch() {
        var searchInput = document.querySelector('[data-kt-aktivitas-filter="search"]');

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
    | METHOD BUILD KETERANGAN HTML
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini membentuk isi kolom
    | keterangan agar tampil rapi pada tabel.
    | Alur kerja: jika keterangan kosong method menampilkan placeholder,
    | sedangkan jika ada isi method menjaga karakter aman dan line break.
    |
    | Tips Debugging:
    | - Jika keterangan tidak tampil, cek field keterangan pada data response JSON.
    */
    function buildKeteranganHtml(keterangan) {
        var teks = String(keterangan || "").trim();

        if (!teks) {
            return '<span class="text-muted">Tidak ada keterangan.</span>';
        }

        return escapeHtml(teks).replace(/\r?\n/g, "<br>");
    }

    /*
    |-------------------------------------------------------------------
    | METHOD BUILD ROW HTML
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini membangun HTML satu baris
    | tabel dari data aktivitas hasil response JSON server.
    | Alur kerja: setelah data list diterima, method dipakai untuk
    | menyusun markup baris sebelum dimasukkan ke DataTable.
    |
    | Tips Debugging:
    | - Jika tombol edit/hapus tidak aktif, cek atribut data-kt-aktivitas-table-filter pada HTML ini.
    */
    function buildRowHtml(row) {
        var keterangan = row.keterangan || "";

        return [
            '<tr data-id="' + row.id_aktivitas + '" data-nama="' + escapeAttribute(row.nama_aktivitas) + '" data-keterangan="' + escapeAttribute(keterangan) + '">',
            '<td><div class="form-check form-check-sm form-check-custom form-check-solid"><input class="form-check-input form-check-input-row" type="checkbox" value="' + row.id_aktivitas + '" /></div></td>',
            '<td class="aktivitas-nama">' + escapeHtml(row.nama_aktivitas) + '</td>',
            '<td class="aktivitas-keterangan">' + buildKeteranganHtml(keterangan) + '</td>',
            '<td class="aktivitas-jumlah-alumni">' + (row.jumlah_alumni || 0) + '</td>',
            '<td class="text-end">',
            '<button type="button" class="btn btn-icon btn-active-light-primary w-30px h-30px me-3" data-kt-aktivitas-table-filter="edit_row">',
            '<i class="ki-duotone ki-setting-3 fs-3"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i>',
            '</button>',
            '<button type="button" class="btn btn-icon btn-active-light-primary w-30px h-30px" data-kt-aktivitas-table-filter="delete_row">',
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
    | aktivitas ke DataTables secara client-side.
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
    | METHOD FETCH AKTIVITAS
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini mengambil seluruh data
    | aktivitas aktif dari endpoint index berbasis AJAX JSON.
    | Alur kerja: script mengirim GET dengan header AJAX, menerima
    | response JSON, memperbarui CSRF, lalu merender isi DataTable.
    |
    | Tips Debugging:
    | - Jika fetch gagal, cek config.indexUrl dan controller index mengembalikan JSON saat request AJAX.
    */
    function fetchAktivitas() {
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
                showErrorAlert(extractErrorMessage(error, "Data aktivitas gagal dimuat."));
                throw error;
            });
    }

    /*
    |-------------------------------------------------------------------
    | METHOD INIT TAMBAH MODAL
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini menangani aksi close,
    | discard, dan submit pada modal tambah aktivitas.
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

        var closeButton = addModalElement.querySelector('[data-kt-aktivitas-modal-action="close"]');
        var cancelButton = addModalElement.querySelector('[data-kt-aktivitas-modal-action="cancel"]');
        var submitButton = addModalElement.querySelector('[data-kt-aktivitas-modal-action="submit"]');

        if (closeButton) {
            closeButton.addEventListener("click", function (event) {
                event.preventDefault();
                confirmCloseForm(addModal, addForm, "Apakah Anda yakin ingin menutup form tambah aktivitas?");
            });
        }

        if (cancelButton) {
            cancelButton.addEventListener("click", function (event) {
                event.preventDefault();
                confirmDiscardForm(addModal, addForm, "Apakah Anda yakin ingin membatalkan data aktivitas baru?");
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
                        showErrorAlert(extractErrorMessage(response, "Data aktivitas gagal disimpan."));
                        return null;
                    }

                    return fetchAktivitas().then(function () {
                        addForm.reset();
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
                    showErrorAlert(extractErrorMessage(error, "Data aktivitas gagal disimpan."));
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
    | discard, dan submit pada modal edit aktivitas.
    | Alur kerja: method memasang listener ke form modal edit, lalu
    | mengirim AJAX update saat data valid dan id tersedia.
    |
    | Tips Debugging:
    | - Jika modal edit kosong, cek atribut data-id, data-nama, dan data-keterangan pada tr.
    */
    function initEditModal() {
        if (!editModalElement || !editForm) {
            return;
        }

        var closeButton = editModalElement.querySelector('[data-kt-aktivitas-edit-modal-action="close"]');
        var cancelButton = editModalElement.querySelector('[data-kt-aktivitas-edit-modal-action="cancel"]');
        var submitButton = editModalElement.querySelector('[data-kt-aktivitas-edit-modal-action="submit"]');

        if (closeButton) {
            closeButton.addEventListener("click", function (event) {
                event.preventDefault();
                confirmCloseForm(editModal, editForm, "Apakah Anda yakin ingin menutup form edit aktivitas?");
            });
        }

        if (cancelButton) {
            cancelButton.addEventListener("click", function (event) {
                event.preventDefault();
                confirmDiscardForm(editModal, editForm, "Apakah Anda yakin ingin membatalkan perubahan aktivitas?");
            });
        }

        editForm.addEventListener("submit", function (event) {
            event.preventDefault();

            if (!validateForm(editForm)) {
                return;
            }

            var idAktivitas = editForm.querySelector('[name="id_aktivitas"]').value;
            if (!idAktivitas) {
                showErrorAlert("Data aktivitas yang akan diperbarui tidak ditemukan.");
                return;
            }

            toggleSubmitState(submitButton, true);

            fetch(config.updateUrl + "/" + idAktivitas, {
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
                        showErrorAlert(extractErrorMessage(response, "Data aktivitas gagal diperbarui."));
                        return null;
                    }

                    return fetchAktivitas().then(function () {
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
                    showErrorAlert(extractErrorMessage(error, "Data aktivitas gagal diperbarui."));
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
    | - Jika tombol edit/hapus diam, cek atribut data-kt-aktivitas-table-filter pada button baris.
    */
    function initTableActions() {
        tableElement.addEventListener("click", function (event) {
            var editButton = event.target.closest('[data-kt-aktivitas-table-filter="edit_row"]');
            var deleteButton = event.target.closest('[data-kt-aktivitas-table-filter="delete_row"]');
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
    | mengisi input hidden, nama, dan keterangan, lalu membuka modal edit.
    |
    | Tips Debugging:
    | - Jika input edit kosong, cek atribut data-id, data-nama, dan data-keterangan pada tr.
    */
    function populateEditForm(rowElement) {
        if (!editForm || !editModal) {
            return;
        }

        editForm.querySelector('[name="id_aktivitas"]').value = rowElement.getAttribute("data-id") || "";
        editForm.querySelector('[name="nama_aktivitas"]').value = rowElement.getAttribute("data-nama") || "";
        editForm.querySelector('[name="keterangan"]').value = rowElement.getAttribute("data-keterangan") || "";
        editModal.show();
    }

    /*
    |-------------------------------------------------------------------
    | METHOD HANDLE DELETE ROW
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini menampilkan konfirmasi
    | hapus lalu menghapus data aktivitas secara AJAX.
    | Alur kerja: user klik tombol hapus, SweetAlert2 meminta
    | konfirmasi, lalu script memanggil endpoint hapus dan refresh
    | isi DataTable jika server mengembalikan success.
    |
    | Tips Debugging:
    | - Jika data tidak hilang setelah hapus, cek endpoint hapus dan fetchAktivitas() terpanggil lagi.
    */
    function handleDeleteRow(rowElement) {
        var idAktivitas = rowElement.getAttribute("data-id");
        var namaAktivitas = rowElement.getAttribute("data-nama");

        Swal.fire({
            text: "Apakah Anda yakin ingin menghapus aktivitas " + namaAktivitas + "?",
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

            fetch(config.hapusUrl + "/" + idAktivitas, {
                method: "POST",
                headers: {
                    "X-Requested-With": "XMLHttpRequest"
                },
                body: buildFormData()
            })
                .then(parseJsonResponse)
                .then(function (response) {
                    updateCsrfToken(response.csrfHash);

                    if (response.status !== "success") {
                        showErrorAlert(extractErrorMessage(response, "Data aktivitas gagal dihapus."));
                        return null;
                    }

                    return fetchAktivitas().then(function () {
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
                    showErrorAlert(extractErrorMessage(error, "Data aktivitas gagal dihapus."));
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
    fetchAktivitas();
})();
