"use strict";

/*
|-------------------------------------------------------------------
| MODUL ANGKATAN JAVASCRIPT
|-------------------------------------------------------------------
| Penjelasan fungsi kode ini: file ini menangani DataTables
| client-side, AJAX tambah/edit/hapus, pencarian tabel, dan
| SweetAlert2 pada halaman Angkatan.
| Alur kerja: setelah halaman dimuat, script menginisialisasi tabel,
| modal tambah, modal edit, token CSRF, lalu memproses aksi pengguna
| tanpa reload halaman penuh.
|
| Tips Debugging:
| - Jika tabel tidak berubah setelah aksi AJAX, cek response JSON dan selector tabel.
| - Jika request POST gagal 403, cek token CSRF di meta head dan payload fetch.
*/
(function () {
    var tableElement = document.getElementById("kt_angkatan_table");

    if (!tableElement) {
        return;
    }

    var config = window.ktAngkatanConfig || {};
    var dataTable = null;
    var addModalElement = document.getElementById("kt_modal_tambah_angkatan");
    var editModalElement = document.getElementById("kt_modal_edit_angkatan");
    var addForm = document.getElementById("kt_modal_tambah_angkatan_form");
    var editForm = document.getElementById("kt_modal_edit_angkatan_form");
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
    | METHOD VALIDATE FORM
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini memeriksa input tahun
    | lulus sebelum request dikirim ke server.
    | Alur kerja: saat submit ditekan, method membaca nilai
    | tahun_lulus, memastikan panjang 4 digit, lalu mengembalikan
    | true atau false.
    |
    | Tips Debugging:
    | - Jika form selalu invalid, cek input name tahun_lulus pada modal tambah dan edit.
    */
    function validateForm(form) {
        var tahunInput = form.querySelector('[name="tahun_lulus"]');
        var tahun = tahunInput ? tahunInput.value.trim() : "";

        if (!tahun || tahun.length !== 4) {
            showErrorAlert("Tahun lulus wajib diisi 4 digit.");
            return false;
        }

        return true;
    }

    /*
    |-------------------------------------------------------------------
    | METHOD INIT TABLE
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini mengaktifkan DataTables
    | client-side pada tabel angkatan.
    | Alur kerja: method membaca elemen tabel lalu membangun instance
    | DataTables untuk dipakai pencarian dan update baris.
    |
    | Tips Debugging:
    | - Jika DataTable tidak aktif, cek datatables.bundle.js termuat.
    */
    function initTable() {
        dataTable = $(tableElement).DataTable({
            info: false,
            order: [],
            columnDefs: [
                { orderable: false, targets: 0 },
                { orderable: false, targets: 3 }
            ]
        });
    }

    /*
    |-------------------------------------------------------------------
    | METHOD INIT SEARCH
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini menghubungkan input
    | pencarian dengan DataTables modul Angkatan.
    | Alur kerja: setiap karakter yang diketik user pada input search
    | dipakai untuk memfilter tabel secara client-side.
    |
    | Tips Debugging:
    | - Jika search tidak bekerja, cek atribut data-kt-angkatan-filter pada input.
    */
    function initSearch() {
        var searchInput = document.querySelector('[data-kt-angkatan-filter="search"]');

        if (!searchInput || !dataTable) {
            return;
        }

        searchInput.addEventListener("keyup", function (event) {
            dataTable.search(event.target.value).draw();
        });
    }

    /*
    |-------------------------------------------------------------------
    | METHOD BUILD ROW HTML
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini membangun HTML satu baris
    | tabel dari data angkatan hasil response JSON server.
    | Alur kerja: setelah simpan berhasil, method dipakai untuk
    | menyusun baris baru sebelum ditambahkan ke DataTable.
    |
    | Tips Debugging:
    | - Jika tombol edit/hapus pada baris baru tidak aktif, cek atribut data-kt-angkatan-table-filter pada HTML ini.
    */
    function buildRowHtml(row) {
        return [
            '<tr data-id="' + row.id_angkatan + '" data-tahun="' + escapeHtml(row.tahun_lulus) + '">',
            '<td><div class="form-check form-check-sm form-check-custom form-check-solid"><input class="form-check-input form-check-input-row" type="checkbox" value="' + row.id_angkatan + '" /></div></td>',
            '<td class="angkatan-tahun">' + escapeHtml(row.tahun_lulus) + '</td>',
            '<td class="angkatan-jumlah-siswa">' + (row.jumlah_siswa || 0) + '</td>',
            '<td class="text-end">',
            '<button type="button" class="btn btn-icon btn-active-light-primary w-30px h-30px me-3" data-kt-angkatan-table-filter="edit_row">',
            '<i class="ki-duotone ki-setting-3 fs-3"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i>',
            '</button>',
            '<button type="button" class="btn btn-icon btn-active-light-primary w-30px h-30px" data-kt-angkatan-table-filter="delete_row">',
            '<i class="ki-duotone ki-trash fs-3"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i>',
            '</button>',
            '</td>',
            '</tr>'
        ].join("");
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
        return String(value)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    /*
    |-------------------------------------------------------------------
    | METHOD APPEND TABLE ROW
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini menambahkan baris baru ke
    | DataTable setelah proses simpan berhasil.
    | Alur kerja: method membuat HTML tr baru, menambahkannya ke
    | DataTable, lalu mengikat ulang aksi edit dan hapus.
    |
    | Tips Debugging:
    | - Jika baris baru tidak muncul, cek instance DataTable aktif saat method dipanggil.
    */
    function appendTableRow(row) {
        var tempWrapper = document.createElement("tbody");
        tempWrapper.innerHTML = buildRowHtml(row);
        var rowElement = tempWrapper.querySelector("tr");
        var newRowNode = dataTable.row.add($(rowElement)).draw(false).node();
        bindRowActions(newRowNode);
    }

    /*
    |-------------------------------------------------------------------
    | METHOD UPDATE TABLE ROW
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini memperbarui isi satu baris
    | tabel setelah edit angkatan berhasil.
    | Alur kerja: method mencari baris berdasarkan data-id, lalu
    | mengganti nilai tahun dan jumlah siswa di tabel.
    |
    | Tips Debugging:
    | - Jika baris tidak ikut berubah, cek data-id pada elemen tr.
    */
    function updateTableRow(row) {
        var rowElement = tableElement.querySelector('tbody tr[data-id="' + row.id_angkatan + '"]');

        if (!rowElement) {
            return;
        }

        rowElement.setAttribute("data-tahun", row.tahun_lulus);
        rowElement.querySelector(".angkatan-tahun").textContent = row.tahun_lulus;
        rowElement.querySelector(".angkatan-jumlah-siswa").textContent = row.jumlah_siswa || 0;
        dataTable.row($(rowElement)).invalidate().draw(false);
    }

    /*
    |-------------------------------------------------------------------
    | METHOD INIT TAMBAH MODAL
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini menangani aksi close,
    | discard, dan submit pada modal tambah angkatan.
    | Alur kerja: method memasang listener ke tombol modal tambah,
    | menampilkan SweetAlert2 konfirmasi, lalu mengirim AJAX saat
    | form valid.
    |
    | Tips Debugging:
    | - Jika modal tambah tidak submit, cek id form tambah dan tombol submit.
    */
    function initTambahModal() {
        if (!addModalElement || !addForm) {
            return;
        }

        var closeButton = addModalElement.querySelector('[data-kt-angkatan-modal-action="close"]');
        var cancelButton = addModalElement.querySelector('[data-kt-angkatan-modal-action="cancel"]');
        var submitButton = addModalElement.querySelector('[data-kt-angkatan-modal-action="submit"]');

        if (closeButton) {
            closeButton.addEventListener("click", function (event) {
                event.preventDefault();
                confirmCloseForm(addModal, addForm, "Apakah Anda yakin ingin menutup form tambah angkatan?");
            });
        }

        if (cancelButton) {
            cancelButton.addEventListener("click", function (event) {
                event.preventDefault();
                confirmDiscardForm(addModal, addForm, "Apakah Anda yakin ingin membatalkan data angkatan baru?");
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
                        showErrorAlert(error.message || "Data angkatan gagal disimpan.");
                    });
            });
        }
    }

    /*
    |-------------------------------------------------------------------
    | METHOD INIT EDIT MODAL
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini menangani aksi close,
    | discard, dan submit pada modal edit angkatan.
    | Alur kerja: method memasang listener ke tombol modal edit,
    | mengisi form dari baris tabel, lalu mengirim AJAX update saat
    | form valid.
    |
    | Tips Debugging:
    | - Jika modal edit kosong, cek atribut data-id dan data-tahun pada tr.
    */
    function initEditModal() {
        if (!editModalElement || !editForm) {
            return;
        }

        var closeButton = editModalElement.querySelector('[data-kt-angkatan-edit-modal-action="close"]');
        var cancelButton = editModalElement.querySelector('[data-kt-angkatan-edit-modal-action="cancel"]');
        var submitButton = editModalElement.querySelector('[data-kt-angkatan-edit-modal-action="submit"]');

        if (closeButton) {
            closeButton.addEventListener("click", function (event) {
                event.preventDefault();
                confirmCloseForm(editModal, editForm, "Apakah Anda yakin ingin menutup form edit angkatan?");
            });
        }

        if (cancelButton) {
            cancelButton.addEventListener("click", function (event) {
                event.preventDefault();
                confirmDiscardForm(editModal, editForm, "Apakah Anda yakin ingin membatalkan perubahan angkatan?");
            });
        }

        if (submitButton) {
            submitButton.addEventListener("click", function (event) {
                event.preventDefault();

                if (!validateForm(editForm)) {
                    return;
                }

                var idAngkatan = editForm.querySelector('[name="id_angkatan"]').value;
                if (!idAngkatan) {
                    showErrorAlert("Data angkatan yang akan diperbarui tidak ditemukan.");
                    return;
                }

                submitButton.setAttribute("data-kt-indicator", "on");
                submitButton.disabled = true;

                fetch(config.updateUrl + "/" + idAngkatan, {
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
                        showErrorAlert(error.message || "Data angkatan gagal diperbarui.");
                    });
            });
        }
    }

    /*
    |-------------------------------------------------------------------
    | METHOD BIND ROW ACTIONS
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini menghubungkan tombol edit
    | dan hapus pada satu baris tabel ke handler AJAX modul.
    | Alur kerja: method menerima elemen tr, lalu memasang listener
    | untuk membuka modal edit atau menghapus data.
    |
    | Tips Debugging:
    | - Jika tombol edit/hapus diam, cek selector data-kt-angkatan-table-filter pada tombol.
    */
    function bindRowActions(rowElement) {
        var editButton = rowElement.querySelector('[data-kt-angkatan-table-filter="edit_row"]');
        var deleteButton = rowElement.querySelector('[data-kt-angkatan-table-filter="delete_row"]');

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
    |-------------------------------------------------------------------
    | METHOD BIND ALL ROW ACTIONS
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini menghubungkan semua baris
    | tabel yang sudah ada saat halaman pertama kali dimuat.
    | Alur kerja: method melakukan iterasi ke seluruh tr di tbody lalu
    | memanggil bindRowActions() untuk setiap baris.
    |
    | Tips Debugging:
    | - Jika hanya baris baru atau lama yang aktif, cek method ini dan appendTableRow sama-sama memanggil bindRowActions.
    */
    function bindAllRowActions() {
        tableElement.querySelectorAll("tbody tr").forEach(function (rowElement) {
            bindRowActions(rowElement);
        });
    }

    /*
    |-------------------------------------------------------------------
    | METHOD POPULATE EDIT FORM
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: method ini mengisi modal edit dari
    | data yang disimpan pada atribut tr tabel.
    | Alur kerja: saat tombol edit diklik, method membaca dataset baris,
    | mengisi id dan tahun lulus, lalu membuka modal edit.
    |
    | Tips Debugging:
    | - Jika input edit kosong, cek atribut data-id dan data-tahun pada tr.
    */
    function populateEditForm(rowElement) {
        if (!editForm || !editModal) {
            return;
        }

        editForm.querySelector('[name="id_angkatan"]').value = rowElement.getAttribute("data-id") || "";
        editForm.querySelector('[name="tahun_lulus"]').value = rowElement.getAttribute("data-tahun") || "";
        editModal.show();
    }

    /*
    |-------------------------------------------------------------------
    | METHOD HANDLE DELETE ROW
    |-------------------------------------------------------------------
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
        var idAngkatan = rowElement.getAttribute("data-id");
        var tahunLulus = rowElement.getAttribute("data-tahun");

        Swal.fire({
            text: "Apakah Anda yakin ingin menghapus angkatan " + tahunLulus + "?",
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

            fetch(config.hapusUrl + "/" + idAngkatan, {
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
                    showErrorAlert(error.message || "Data angkatan gagal dihapus.");
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
    bindAllRowActions();
})();
