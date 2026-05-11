"use strict";

/*
|--------------------------------------------------------------------------
| MODUL KOMPETENSI JAVASCRIPT
|--------------------------------------------------------------------------
| Penjelasan fungsi kode ini: file ini menangani DataTables client-side,
| AJAX tambah/edit/hapus, pencarian tabel, dan SweetAlert2 pada halaman
| Kompetensi Keahlian.
| Alur kerja: setelah halaman modul dimuat, script menginisialisasi
| tabel, modal tambah, modal edit, token CSRF, lalu memproses aksi
| pengguna tanpa reload halaman penuh.
|
| Tips Debugging:
| - Jika tabel tidak berubah setelah aksi AJAX, cek response JSON dan selector tabel.
| - Jika POST gagal 403, cek token CSRF di meta head dan payload fetch.
*/
(function () {
    var tableElement = document.getElementById("kt_kompetensi_table");

    if (!tableElement) {
        return;
    }

    var config = window.ktKompetensiConfig || {};
    var dataTable = null;
    var addModalElement = document.getElementById("kt_modal_tambah_kompetensi");
    var editModalElement = document.getElementById("kt_modal_edit_kompetensi");
    var addForm = document.getElementById("kt_modal_tambah_kompetensi_form");
    var editForm = document.getElementById("kt_modal_edit_kompetensi_form");
    var addModal = addModalElement ? new bootstrap.Modal(addModalElement) : null;
    var editModal = editModalElement ? new bootstrap.Modal(editModalElement) : null;

    /*
    |--------------------------------------------------------------------------
    | METHOD GET CSRF CONFIG
    |--------------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini mengambil token CSRF dan
    | nama field token dari meta tag yang sudah disisipkan di head.
    | Alur kerja: setiap request AJAX memanggil method ini sebelum
    | membuat payload agar token terbaru selalu ikut terkirim.
    |
    | Tips Debugging:
    | - Jika token kosong, cek meta csrf-token dan csrf-header-name di partial head.
    */
    function getCsrfConfig() {
        var csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
        var csrfHeaderMeta = document.querySelector('meta[name="csrf-header-name"]');

        return {
            token: csrfTokenMeta ? csrfTokenMeta.getAttribute("content") : "",
            fieldName: csrfHeaderMeta ? csrfHeaderMeta.getAttribute("content") : "",
        };
    }

    /*
    |--------------------------------------------------------------------------
    | METHOD UPDATE CSRF TOKEN
    |--------------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini memperbarui token CSRF di
    | meta head setiap kali server mengirim token baru pada response.
    | Alur kerja: setelah response JSON diterima, script memanggil
    | method ini agar request berikutnya memakai token yang masih valid.
    |
    | Tips Debugging:
    | - Jika request kedua gagal CSRF, cek response JSON mengandung csrfHash dan method ini terpanggil.
    */
    function updateCsrfToken(newToken) {
        var csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');

        if (csrfTokenMeta && newToken) {
            csrfTokenMeta.setAttribute("content", newToken);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | METHOD BUILD FORM DATA
    |--------------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini membangun FormData dari
    | sebuah form lalu menambahkan token CSRF yang dibutuhkan request.
    | Alur kerja: method menerima element form, membaca semua input,
    | menambahkan token CSRF, lalu mengembalikannya ke pemanggil fetch.
    |
    | Tips Debugging:
    | - Jika field form tidak terkirim, cek atribut name pada input form.
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
    |--------------------------------------------------------------------------
    | METHOD SHOW ERROR ALERT
    |--------------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini menampilkan SweetAlert2
    | error standar untuk pesan kegagalan validasi atau request AJAX.
    | Alur kerja: method dipanggil saat response bukan success atau
    | terjadi exception pada proses fetch.
    |
    | Tips Debugging:
    | - Jika popup error tidak muncul, cek library SweetAlert2 sudah termuat di footer.
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
    |--------------------------------------------------------------------------
    | METHOD VALIDATE FORM
    |--------------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini memeriksa input nama
    | kompetensi dan akronim agar tidak kosong sebelum request dikirim.
    | Alur kerja: saat tombol submit ditekan, method membaca nilai
    | input form dan mengembalikan hasil valid atau tidak.
    |
    | Tips Debugging:
    | - Jika form selalu dianggap kosong, cek selector name input pada modal.
    */
    function validateForm(form) {
        var namaInput = form.querySelector('[name="nama_kompetensi"]');
        var akronimInput = form.querySelector('[name="akronim"]');
        var nama = namaInput ? namaInput.value.trim() : "";
        var akronim = akronimInput ? akronimInput.value.trim() : "";

        if (!nama || !akronim) {
            showErrorAlert("Nama kompetensi dan akronim wajib diisi.");
            return false;
        }

        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | METHOD INIT TABLE
    |--------------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini mengaktifkan DataTables
    | client-side pada tabel kompetensi dan mengatur kolom yang tidak
    | bisa disortir.
    | Alur kerja: method membaca elemen tabel, lalu membangun instance
    | DataTables untuk dipakai oleh pencarian dan update baris.
    |
    | Tips Debugging:
    | - Jika DataTable tidak aktif, cek plugin datatables.bundle.js termuat.
    */
    function initTable() {
        dataTable = $(tableElement).DataTable({
            info: false,
            order: [],
            columnDefs: [
                { orderable: false, targets: 0 },
                { orderable: false, targets: 4 }
            ]
        });
    }

    /*
    |--------------------------------------------------------------------------
    | METHOD INIT SEARCH
    |--------------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini menghubungkan input
    | pencarian dengan instance DataTables modul Kompetensi.
    | Alur kerja: setiap karakter yang diketik user di input search
    | akan dipakai untuk memfilter baris tabel secara client-side.
    |
    | Tips Debugging:
    | - Jika search tidak merespons, cek atribut data-kt-kompetensi-filter pada input.
    */
    function initSearch() {
        var searchInput = document.querySelector('[data-kt-kompetensi-filter="search"]');

        if (!searchInput || !dataTable) {
            return;
        }

        searchInput.addEventListener("keyup", function (event) {
            dataTable.search(event.target.value).draw();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | METHOD BUILD ROW HTML
    |--------------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini membentuk HTML baris tabel
    | dari data kompetensi hasil response JSON server.
    | Alur kerja: setelah tambah data berhasil, method dipakai untuk
    | menyusun markup baris baru sebelum ditambahkan ke DataTable.
    |
    | Tips Debugging:
    | - Jika tombol edit/hapus di baris baru tidak jalan, cek atribut data-kt-kompetensi-table-filter pada HTML ini.
    */
    function buildRowHtml(row) {
        return [
            '<tr data-id="' + row.id_kompetensi + '" data-nama="' + escapeHtml(row.nama_kompetensi) + '" data-akronim="' + escapeHtml(row.akronim) + '">',
            '<td><div class="form-check form-check-sm form-check-custom form-check-solid"><input class="form-check-input form-check-input-row" type="checkbox" value="' + row.id_kompetensi + '" /></div></td>',
            '<td class="kompetensi-nama">' + escapeHtml(row.nama_kompetensi) + '</td>',
            '<td class="kompetensi-akronim"><span class="badge badge-light-primary">' + escapeHtml(row.akronim) + '</span></td>',
            '<td class="kompetensi-keterserapan">' + (row.keterserapan || 0) + '</td>',
            '<td class="text-end">',
            '<button type="button" class="btn btn-icon btn-active-light-primary w-30px h-30px me-3" data-kt-kompetensi-table-filter="edit_row">',
            '<i class="ki-duotone ki-setting-3 fs-3"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i>',
            '</button>',
            '<button type="button" class="btn btn-icon btn-active-light-primary w-30px h-30px" data-kt-kompetensi-table-filter="delete_row">',
            '<i class="ki-duotone ki-trash fs-3"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i>',
            '</button>',
            '</td>',
            '</tr>'
        ].join("");
    }

    /*
    |--------------------------------------------------------------------------
    | METHOD ESCAPE HTML
    |--------------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini membersihkan string
    | sebelum dimasukkan ke HTML tabel agar karakter spesial aman.
    | Alur kerja: method menerima teks biasa lalu mengembalikan versi
    | yang sudah diubah menjadi entity HTML.
    |
    | Tips Debugging:
    | - Jika karakter khusus tampil aneh, cek hasil escaping dari method ini.
    */
    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    /*
    |--------------------------------------------------------------------------
    | METHOD APPEND TABLE ROW
    |--------------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini menambahkan baris baru ke
    | DataTables setelah proses simpan berhasil tanpa reload halaman.
    | Alur kerja: method membangun HTML baris baru, menambahkannya ke
    | DataTable, lalu mengikat ulang aksi edit dan hapus pada baris itu.
    |
    | Tips Debugging:
    | - Jika baris baru tidak terlihat, cek instance DataTable aktif sebelum method ini dipanggil.
    */
    function appendTableRow(row) {
        var tempWrapper = document.createElement("tbody");
        tempWrapper.innerHTML = buildRowHtml(row);
        var rowElement = tempWrapper.querySelector("tr");
        var newRowNode = dataTable.row.add($(rowElement)).draw(false).node();
        bindRowActions(newRowNode);
    }

    /*
    |--------------------------------------------------------------------------
    | METHOD UPDATE TABLE ROW
    |--------------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini memperbarui isi satu baris
    | tabel setelah proses edit kompetensi berhasil.
    | Alur kerja: method mencari baris berdasarkan data-id, mengganti
    | teks nama, akronim, keterserapan, dan atribut dataset.
    |
    | Tips Debugging:
    | - Jika baris salah yang berubah, cek data-id pada elemen tr.
    */
    function updateTableRow(row) {
        var rowElement = tableElement.querySelector('tbody tr[data-id="' + row.id_kompetensi + '"]');

        if (!rowElement) {
            return;
        }

        rowElement.setAttribute("data-nama", row.nama_kompetensi);
        rowElement.setAttribute("data-akronim", row.akronim);
        rowElement.querySelector(".kompetensi-nama").textContent = row.nama_kompetensi;
        rowElement.querySelector(".kompetensi-akronim").innerHTML = '<span class="badge badge-light-primary">' + escapeHtml(row.akronim) + "</span>";
        rowElement.querySelector(".kompetensi-keterserapan").textContent = row.keterserapan || 0;
        dataTable.row($(rowElement)).invalidate().draw(false);
    }

    /*
    |--------------------------------------------------------------------------
    | METHOD INIT TAMBAH MODAL
    |--------------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini menangani aksi close,
    | discard, dan submit pada modal tambah kompetensi.
    | Alur kerja: method memasang listener ke tombol modal tambah,
    | menampilkan SweetAlert2 konfirmasi, lalu mengirim AJAX saat form valid.
    |
    | Tips Debugging:
    | - Jika modal tambah tidak bisa submit, cek id form tambah dan selector tombol submit.
    */
    function initTambahModal() {
        if (!addModalElement || !addForm) {
            return;
        }

        var closeButton = addModalElement.querySelector('[data-kt-kompetensi-modal-action="close"]');
        var cancelButton = addModalElement.querySelector('[data-kt-kompetensi-modal-action="cancel"]');
        var submitButton = addModalElement.querySelector('[data-kt-kompetensi-modal-action="submit"]');

        if (closeButton) {
            closeButton.addEventListener("click", function (event) {
                event.preventDefault();
                confirmCloseForm(addModal, addForm, "Apakah Anda yakin ingin menutup form tambah kompetensi?");
            });
        }

        if (cancelButton) {
            cancelButton.addEventListener("click", function (event) {
                event.preventDefault();
                confirmDiscardForm(addModal, addForm, "Apakah Anda yakin ingin membatalkan data kompetensi baru?");
            });
        }

        if (submitButton) {
            submitButton.addEventListener("click", function (event) {
                event.preventDefault();

                if (!validateForm(addForm)) {
                    return;
                }

                submitButton.setAttribute("data-kt-indicator", "on");
                submitButton.disabled = true;

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
                        submitButton.removeAttribute("data-kt-indicator");
                        submitButton.disabled = false;

                        if (response.status !== "success") {
                            showErrorAlert(response.message);
                            return;
                        }

                        appendTableRow(response.data);
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
                    })
                    .catch(function (error) {
                        submitButton.removeAttribute("data-kt-indicator");
                        submitButton.disabled = false;
                        updateCsrfToken(error.csrfHash);
                        showErrorAlert(error.message || "Data kompetensi gagal disimpan.");
                    });
            });
        }
    }

    /*
    |--------------------------------------------------------------------------
    | METHOD INIT EDIT MODAL
    |--------------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini menangani aksi close,
    | discard, dan submit pada modal edit kompetensi.
    | Alur kerja: method memasang listener ke tombol modal edit,
    | mengisi form dari baris tabel, lalu mengirim AJAX update ketika
    | form valid dan disetujui pengguna.
    |
    | Tips Debugging:
    | - Jika modal edit tidak terisi data, cek atribut data-id, data-nama, dan data-akronim pada tr.
    */
    function initEditModal() {
        if (!editModalElement || !editForm) {
            return;
        }

        var closeButton = editModalElement.querySelector('[data-kt-kompetensi-edit-modal-action="close"]');
        var cancelButton = editModalElement.querySelector('[data-kt-kompetensi-edit-modal-action="cancel"]');
        var submitButton = editModalElement.querySelector('[data-kt-kompetensi-edit-modal-action="submit"]');

        if (closeButton) {
            closeButton.addEventListener("click", function (event) {
                event.preventDefault();
                confirmCloseForm(editModal, editForm, "Apakah Anda yakin ingin menutup form edit kompetensi?");
            });
        }

        if (cancelButton) {
            cancelButton.addEventListener("click", function (event) {
                event.preventDefault();
                confirmDiscardForm(editModal, editForm, "Apakah Anda yakin ingin membatalkan perubahan kompetensi?");
            });
        }

        if (submitButton) {
            submitButton.addEventListener("click", function (event) {
                event.preventDefault();

                if (!validateForm(editForm)) {
                    return;
                }

                var idKompetensi = editForm.querySelector('[name="id_kompetensi"]').value;
                if (!idKompetensi) {
                    showErrorAlert("Data kompetensi yang akan diperbarui tidak ditemukan.");
                    return;
                }

                submitButton.setAttribute("data-kt-indicator", "on");
                submitButton.disabled = true;

                fetch(config.updateUrl + "/" + idKompetensi, {
                    method: "POST",
                    headers: {
                        "X-Requested-With": "XMLHttpRequest"
                    },
                    body: buildFormData(editForm)
                })
                    .then(parseJsonResponse)
                    .then(function (response) {
                        updateCsrfToken(response.csrfHash);
                        submitButton.removeAttribute("data-kt-indicator");
                        submitButton.disabled = false;

                        if (response.status !== "success") {
                            showErrorAlert(response.message);
                            return;
                        }

                        updateTableRow(response.data);
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
                    })
                    .catch(function (error) {
                        submitButton.removeAttribute("data-kt-indicator");
                        submitButton.disabled = false;
                        updateCsrfToken(error.csrfHash);
                        showErrorAlert(error.message || "Data kompetensi gagal diperbarui.");
                    });
            });
        }
    }

    /*
    |--------------------------------------------------------------------------
    | METHOD BIND ROW ACTIONS
    |--------------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini menghubungkan tombol edit
    | dan hapus pada satu baris tabel ke handler AJAX modul.
    | Alur kerja: method menerima elemen tr, memasang listener pada
    | tombol edit untuk membuka modal, dan tombol hapus untuk
    | menampilkan konfirmasi SweetAlert2.
    |
    | Tips Debugging:
    | - Jika tombol edit/hapus diam, cek selector data-kt-kompetensi-table-filter pada tombol.
    */
    function bindRowActions(rowElement) {
        var editButton = rowElement.querySelector('[data-kt-kompetensi-table-filter="edit_row"]');
        var deleteButton = rowElement.querySelector('[data-kt-kompetensi-table-filter="delete_row"]');

        if (editButton) {
            editButton.addEventListener("click", function (event) {
                event.preventDefault();
                populateEditForm(rowElement);
            });
        }

        if (deleteButton) {
            deleteButton.addEventListener("click", function (event) {
                event.preventDefault();
                handleDeleteRow(rowElement);
            });
        }
    }

    /*
    |--------------------------------------------------------------------------
    | METHOD BIND ALL ROW ACTIONS
    |--------------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini menghubungkan seluruh
    | baris tabel yang sudah ada saat halaman pertama kali dimuat.
    | Alur kerja: method melakukan iterasi ke semua tr di tbody lalu
    | memanggil bindRowActions() untuk masing-masing baris.
    |
    | Tips Debugging:
    | - Jika hanya baris lama yang aktif atau sebaliknya, cek method ini dan appendTableRow sama-sama memanggil bindRowActions.
    */
    function bindAllRowActions() {
        tableElement.querySelectorAll("tbody tr").forEach(function (rowElement) {
            bindRowActions(rowElement);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | METHOD POPULATE EDIT FORM
    |--------------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini mengisi modal edit dari
    | data yang tersimpan pada atribut tr tabel.
    | Alur kerja: saat tombol edit diklik, method membaca dataset baris,
    | mengisi input hidden, nama, dan akronim, lalu membuka modal edit.
    |
    | Tips Debugging:
    | - Jika input edit kosong, cek atribut data-id, data-nama, dan data-akronim pada tr.
    */
    function populateEditForm(rowElement) {
        if (!editForm || !editModal) {
            return;
        }

        editForm.querySelector('[name="id_kompetensi"]').value = rowElement.getAttribute("data-id") || "";
        editForm.querySelector('[name="nama_kompetensi"]').value = rowElement.getAttribute("data-nama") || "";
        editForm.querySelector('[name="akronim"]').value = rowElement.getAttribute("data-akronim") || "";
        editModal.show();
    }

    /*
    |--------------------------------------------------------------------------
    | METHOD HANDLE DELETE ROW
    |--------------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini menampilkan konfirmasi
    | hapus lalu menghapus baris tabel secara AJAX dan client-side.
    | Alur kerja: user klik tombol hapus, SweetAlert2 meminta
    | konfirmasi, lalu script memanggil endpoint hapus dan menghapus
    | baris dari DataTable jika server mengembalikan success.
    |
    | Tips Debugging:
    | - Jika baris tidak hilang padahal server sukses, cek data-id pada baris dan instance DataTable.
    */
    function handleDeleteRow(rowElement) {
        var idKompetensi = rowElement.getAttribute("data-id");
        var namaKompetensi = rowElement.getAttribute("data-nama");

        Swal.fire({
            text: "Apakah Anda yakin ingin menghapus kompetensi " + namaKompetensi + "?",
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

            fetch(config.hapusUrl + "/" + idKompetensi, {
                method: "GET",
                headers: {
                    "X-Requested-With": "XMLHttpRequest"
                }
            })
                .then(parseJsonResponse)
                .then(function (response) {
                    updateCsrfToken(response.csrfHash);

                    if (response.status !== "success") {
                        showErrorAlert(response.message);
                        return;
                    }

                    dataTable.row($(rowElement)).remove().draw(false);

                    Swal.fire({
                        text: response.message,
                        icon: "success",
                        buttonsStyling: false,
                        confirmButtonText: "Ok, mengerti!",
                        customClass: {
                            confirmButton: "btn btn-primary"
                        }
                    });
                })
                .catch(function (error) {
                    updateCsrfToken(error.csrfHash);
                    showErrorAlert(error.message || "Data kompetensi gagal dihapus.");
                });
        });
    }

    /*
    |--------------------------------------------------------------------------
    | METHOD CONFIRM CLOSE FORM
    |--------------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini menampilkan konfirmasi
    | SweetAlert2 ketika pengguna ingin menutup modal form.
    | Alur kerja: saat tombol close diklik, method menampilkan popup
    | warning, lalu menutup modal jika user menyetujui.
    |
    | Tips Debugging:
    | - Jika modal tidak menutup setelah konfirmasi, cek instance bootstrap.Modal yang dikirim.
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
    |--------------------------------------------------------------------------
    | METHOD CONFIRM DISCARD FORM
    |--------------------------------------------------------------------------
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
    |--------------------------------------------------------------------------
    | METHOD PARSE JSON RESPONSE
    |--------------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini membaca response fetch
    | dan melempar error terstruktur bila status HTTP bukan sukses.
    | Alur kerja: fetch memanggil method ini setelah menerima response,
    | lalu method mengubah body menjadi JSON untuk diproses berikutnya.
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
    bindAllRowActions();
})();
