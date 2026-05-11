"use strict";

/*
|-------------------------------------------------------------------
| MODUL DETAIL PELAMAR
|-------------------------------------------------------------------
| Script ini menangani edit detail pelamar, tambah/edit/hapus
| riwayat kerja, serta upload dan hapus berkas pada halaman detail.
| Alur kerja: form dikirim via AJAX ke endpoint controller, lalu
| halaman direfresh setelah server mengembalikan respons sukses.
|
| Tips Debugging:
| - Jika aksi GET ditolak, cek header X-Requested-With terkirim.
| - Jika form upload gagal, cek token CSRF dan ukuran file.
*/
(function () {
    var config = window.pelamarDetailConfig || {};
    var csrfHeaderMeta = document.querySelector('meta[name="csrf-header-name"]');
    var csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
    var qrGenerated = false;
    var accountId = config.accountId || '';
    var activeTabStorageKey = "pelamar-detail-active-tab:" + (config.pelamarId || "default");

    var getCsrfHeaderName = function () {
        return csrfHeaderMeta ? csrfHeaderMeta.getAttribute("content") : "csrf_test_name";
    };

    var getCsrfToken = function () {
        return csrfTokenMeta ? csrfTokenMeta.getAttribute("content") : "";
    };

    var setCsrfToken = function (token) {
        if (csrfTokenMeta && token) {
            csrfTokenMeta.setAttribute("content", token);
        }
    };

    var buildOptions = function (method, body) {
        var headers = {
            "X-Requested-With": "XMLHttpRequest",
            "Accept": "application/json"
        };
        var csrfHeader = getCsrfHeaderName();
        var csrfToken = getCsrfToken();

        headers[csrfHeader] = csrfToken;
        headers["X-CSRF-TOKEN"] = csrfToken;

        if (body instanceof FormData) {
            if (body.has(csrfHeader)) {
                body.delete(csrfHeader);
            }

            body.append(csrfHeader, csrfToken);
        }

        return {
            method: method,
            headers: headers,
            body: body || undefined,
            credentials: "same-origin"
        };
    };

    var requestJson = function (url, method, body) {
        return fetch(url, buildOptions(method, body))
            .then(function (response) {
                return response.json().then(function (json) {
                    if (json && json.csrfHash) {
                        setCsrfToken(json.csrfHash);
                    }

                    if (!response.ok || !json || json.status !== "success") {
                        throw new Error(json && json.message ? json.message : "Terjadi kesalahan pada server.");
                    }

                    return json;
                });
            });
    };

    var showAlert = function (icon, text, callback) {
        if (typeof Swal === "undefined") {
            window.alert(text);

            if (typeof callback === "function") {
                callback();
            }

            return;
        }

        Swal.fire({
            text: text,
            icon: icon,
            buttonsStyling: false,
            confirmButtonText: "Oke",
            customClass: {
                confirmButton: "btn btn-primary"
            }
        }).then(function () {
            if (typeof callback === "function") {
                callback();
            }
        });
    };

    var confirmAction = function (text, callback) {
        if (typeof Swal === "undefined") {
            if (window.confirm(text)) {
                callback();
            }

            return;
        }

        Swal.fire({
            text: text,
            icon: "warning",
            showCancelButton: true,
            buttonsStyling: false,
            confirmButtonText: "Ya",
            cancelButtonText: "Batal",
            customClass: {
                confirmButton: "btn btn-danger",
                cancelButton: "btn btn-light"
            }
        }).then(function (result) {
            if (result.isConfirmed) {
                callback();
            }
        });
    };

    var getActiveTabTarget = function () {
        var activeTab = document.querySelector('.nav-link[data-bs-toggle="tab"].active');

        return activeTab ? (activeTab.getAttribute("href") || "") : "";
    };

    var persistActiveTab = function (target) {
        if (!target || typeof window.sessionStorage === "undefined") {
            return;
        }

        window.sessionStorage.setItem(activeTabStorageKey, target);
    };

    var showTab = function (target) {
        var tabTrigger;

        if (!target || typeof bootstrap === "undefined") {
            return;
        }

        tabTrigger = document.querySelector('.nav-link[data-bs-toggle="tab"][href="' + target + '"]');

        if (!tabTrigger) {
            return;
        }

        bootstrap.Tab.getOrCreateInstance(tabTrigger).show();
    };

    var reloadPage = function (message, targetTab) {
        persistActiveTab(targetTab || getActiveTabTarget());

        showAlert("success", message, function () {
            window.location.reload();
        });
    };

    var getModalInstance = function (elementId) {
        var element = document.getElementById(elementId);

        if (!element || typeof bootstrap === "undefined") {
            return null;
        }

        return bootstrap.Modal.getOrCreateInstance(element);
    };

    var closeModal = function (elementId) {
        var modal = getModalInstance(elementId);

        if (modal) {
            modal.hide();
        }
    };

    var bindResetButton = function (modalSelector, buttonSelector, formSelector) {
        var modalElement = document.querySelector(modalSelector);
        var button = modalElement ? modalElement.querySelector(buttonSelector) : null;
        var form = document.querySelector(formSelector);

        if (!button || !form) {
            return;
        }

        button.addEventListener("click", function (event) {
            event.preventDefault();
            form.reset();
        });
    };

    var initTabPersistence = function () {
        var savedTab = "";
        var tabTriggers = document.querySelectorAll('.nav-link[data-bs-toggle="tab"]');

        tabTriggers.forEach(function (tabTrigger) {
            tabTrigger.addEventListener("shown.bs.tab", function (event) {
                persistActiveTab(event.target.getAttribute("href") || "");
            });
        });

        if (typeof window.sessionStorage === "undefined") {
            return;
        }

        savedTab = window.sessionStorage.getItem(activeTabStorageKey) || "";

        if (savedTab) {
            showTab(savedTab);
        }
    };

    var handleDetailSubmit = function () {
        var form = document.getElementById("kt_modal_update_details_form");

        if (!form) {
            return;
        }

        form.addEventListener("submit", function (event) {
            var formData;
            var submitUrl;

            event.preventDefault();
            formData = new FormData(form);
            submitUrl = config.updateUrl.replace(/\/$/, "") + "/" + config.pelamarId;

            requestJson(submitUrl, "POST", formData)
                .then(function (response) {
                    closeModal("kt_modal_update_details");
                    reloadPage(response.message || "Detail pelamar berhasil diperbarui.");
                })
                .catch(function (error) {
                    showAlert("error", error.message);
                });
        });
    };

    var handleRiwayatSubmit = function () {
        var addForm = document.getElementById("kt_modal_add_schedule_form");
        var editForm = document.getElementById("kt_modal_edit_riwayat_form");

        if (addForm) {
            addForm.addEventListener("submit", function (event) {
                event.preventDefault();

                requestJson(config.simpanRiwayatUrl, "POST", new FormData(addForm))
                    .then(function (response) {
                        closeModal("kt_modal_add_schedule");
                        reloadPage(response.message || "Riwayat kerja berhasil ditambahkan.");
                    })
                    .catch(function (error) {
                        showAlert("error", error.message);
                    });
            });
        }

        if (editForm) {
            editForm.addEventListener("submit", function (event) {
                var idRiwayat = editForm.querySelector('[name="id_riwayat"]').value;
                var submitUrl;

                event.preventDefault();

                if (!idRiwayat) {
                    showAlert("error", "ID riwayat kerja tidak ditemukan.");
                    return;
                }

                submitUrl = config.updateRiwayatUrl.replace(/\/$/, "") + "/" + idRiwayat;

                requestJson(submitUrl, "POST", new FormData(editForm))
                    .then(function (response) {
                        closeModal("kt_modal_edit_riwayat");
                        reloadPage(response.message || "Riwayat kerja berhasil diperbarui.");
                    })
                    .catch(function (error) {
                        showAlert("error", error.message);
                    });
            });
        }
    };

    var handleBerkasSubmit = function () {
        var form = document.getElementById("kt_modal_upload_berkas_form");

        if (!form) {
            return;
        }

        form.addEventListener("submit", function (event) {
            event.preventDefault();

            requestJson(config.uploadBerkasUrl, "POST", new FormData(form))
                .then(function (response) {
                    closeModal("kt_modal_upload_berkas");
                    reloadPage(response.message || "Berkas berhasil diunggah.");
                })
                .catch(function (error) {
                    showAlert("error", error.message);
                });
        });
    };

    var handleUpdateEmail = function () {
        var form = document.getElementById("kt_modal_update_email_form");
        var modal = document.getElementById("kt_modal_update_email");

        if (!form || !modal) {
            return;
        }

        var handleDiscard = function (event) {
            event.preventDefault();
            confirmAction("Apakah Anda yakin ingin membatalkan?", function () {
                form.reset();
                closeModal("kt_modal_update_email");
            });
        };

        modal.querySelector('[data-kt-users-modal-action="close"]').addEventListener("click", handleDiscard);
        modal.querySelector('[data-kt-users-modal-action="cancel"]').addEventListener("click", handleDiscard);

        form.addEventListener("submit", function (event) {
            event.preventDefault();

            var submitButton = form.querySelector('[data-kt-users-modal-action="submit"]');
            submitButton.setAttribute("data-kt-indicator", "on");
            submitButton.disabled = true;

            requestJson(config.updateEmailUrl, "POST", new FormData(form))
                .then(function (response) {
                    closeModal("kt_modal_update_email");
                    reloadPage(response.message || "Email berhasil diperbarui.");
                })
                .catch(function (error) {
                    showAlert("error", error.message);
                })
                .finally(function () {
                    submitButton.removeAttribute("data-kt-indicator");
                    submitButton.disabled = false;
                });
        });
    };

    var handleUpdatePassword = function () {
        var form = document.getElementById("kt_modal_update_password_form");
        var modal = document.getElementById("kt_modal_update_password");

        if (!form || !modal) {
            return;
        }

        var handleDiscard = function (event) {
            event.preventDefault();
            confirmAction("Apakah Anda yakin ingin membatalkan?", function () {
                form.reset();
                closeModal("kt_modal_update_password");
            });
        };

        modal.querySelector('[data-kt-users-modal-action="close"]').addEventListener("click", handleDiscard);
        modal.querySelector('[data-kt-users-modal-action="cancel"]').addEventListener("click", handleDiscard);

        form.addEventListener("submit", function (event) {
            event.preventDefault();

            var passwordBaru = form.querySelector('[name="password_baru"]').value;
            var konfirmasiPassword = form.querySelector('[name="konfirmasi_password_baru"]').value;

            if (passwordBaru !== konfirmasiPassword) {
                showAlert("error", "Password baru dan konfirmasi password tidak cocok.");
                return;
            }

            if (passwordBaru.length < 8) {
                showAlert("error", "Password minimal 8 karakter.");
                return;
            }

            var submitButton = form.querySelector('[data-kt-users-modal-action="submit"]');
            submitButton.setAttribute("data-kt-indicator", "on");
            submitButton.disabled = true;

            requestJson(config.updatePasswordUrl, "POST", new FormData(form))
                .then(function (response) {
                    closeModal("kt_modal_update_password");
                    reloadPage(response.message || "Password berhasil diperbarui.");
                })
                .catch(function (error) {
                    showAlert("error", error.message);
                })
                .finally(function () {
                    submitButton.removeAttribute("data-kt-indicator");
                    submitButton.disabled = false;
                });
        });
    };

    var handleTracerSubmit = function () {
        var form = document.getElementById("kt_modal_edit_tracer_form");
        var modal = document.getElementById("kt_modal_edit_tracer");
        var clickedSubmitButton = null;

        if (!form || !modal) {
            return;
        }

        var handleDiscard = function (event) {
            event.preventDefault();
            confirmAction("Apakah Anda yakin ingin membatalkan?", function () {
                form.reset();
                closeModal("kt_modal_edit_tracer");
            });
        };

        modal.querySelector('[data-kt-tracer-modal-action="close"]').addEventListener("click", handleDiscard);
        modal.querySelector('[data-kt-tracer-modal-action="cancel"]').addEventListener("click", handleDiscard);

        form.querySelectorAll('[data-kt-tracer-modal-action="submit"]').forEach(function (button) {
            button.addEventListener("click", function () {
                clickedSubmitButton = button;
            });
        });

        form.addEventListener("submit", function (event) {
            event.preventDefault();

            var submitButton = clickedSubmitButton || event.submitter || form.querySelector('[data-kt-tracer-modal-action="submit"]');
            var statusField = form.querySelector('[name="status_tracer"]');

            if (statusField && submitButton && submitButton.getAttribute("data-tracer-status")) {
                statusField.value = submitButton.getAttribute("data-tracer-status");
            }

            submitButton.setAttribute("data-kt-indicator", "on");
            submitButton.disabled = true;

            requestJson(config.simpanTracerUrl, "POST", new FormData(form))
                .then(function (response) {
                    closeModal("kt_modal_edit_tracer");
                    reloadPage(response.message || "Tracer study berhasil disimpan.");
                })
                .catch(function (error) {
                    showAlert("error", error.message);
                })
                .finally(function () {
                    submitButton.removeAttribute("data-kt-indicator");
                    submitButton.disabled = false;
                    clickedSubmitButton = null;
                });
        });

        var activityRadios = form.querySelectorAll('input[name="id_aktivitas"]');
        var formBekerja = document.getElementById("kt_tracer_form_bekerja");
        var formKuliah = document.getElementById("kt_tracer_form_kuliah");
        var formWirausaha = document.getElementById("kt_tracer_form_wirausaha");
        var formBelumBekerja = document.getElementById("kt_tracer_form_belum_bekerja");

        var toggleTracerForms = function () {
            var checked = form.querySelector('input[name="id_aktivitas"]:checked');
            var slug = checked ? checked.getAttribute("data-slug") : "";

            if (formBekerja) formBekerja.classList.toggle("d-none", slug !== "bekerja");
            if (formKuliah) formKuliah.classList.toggle("d-none", slug !== "kuliah");
            if (formWirausaha) formWirausaha.classList.toggle("d-none", slug !== "wirausaha");
            if (formBelumBekerja) formBelumBekerja.classList.toggle("d-none", slug !== "belum_bekerja");
        };

        activityRadios.forEach(function (radio) {
            radio.addEventListener("change", toggleTracerForms);
        });

        toggleTracerForms();
    };

    var populateEditRiwayat = function (row) {
        var form = document.getElementById("kt_modal_edit_riwayat_form");
        var masihBekerja = false;

        if (!form || !row) {
            return;
        }

        form.querySelector('[name="id_riwayat"]').value = row.getAttribute("data-id") || "";
        form.querySelector('[name="nama_perusahaan"]').value = row.getAttribute("data-perusahaan") || "";
        form.querySelector('[name="posisi_jabatan"]').value = row.getAttribute("data-posisi") || "";
        form.querySelector('[name="bidang_usaha"]').value = row.getAttribute("data-bidang") || "";
        form.querySelector('[name="lokasi"]').value = row.getAttribute("data-lokasi") || "";
        form.querySelector('[name="tanggal_mulai"]').value = row.getAttribute("data-mulai") || "";
        form.querySelector('[name="tanggal_selesai"]').value = row.getAttribute("data-selesai") || "";
        masihBekerja = row.getAttribute("data-masih_bekerja") === "1";
        form.querySelector('[name="masih_bekerja"]').checked = masihBekerja;
        form.querySelector('[name="tanggal_selesai"]').disabled = masihBekerja;
        form.querySelector('[name="keterangan"]').value = row.getAttribute("data-keterangan") || "";
    };

    var bindMasihBekerjaToggle = function (formId) {
        var form = document.getElementById(formId);
        var checkbox;
        var tanggalSelesai;

        if (!form) {
            return;
        }

        checkbox = form.querySelector('[name="masih_bekerja"]');
        tanggalSelesai = form.querySelector('[name="tanggal_selesai"]');

        if (!checkbox || !tanggalSelesai) {
            return;
        }

        checkbox.addEventListener("change", function () {
            tanggalSelesai.disabled = checkbox.checked;

            if (checkbox.checked) {
                tanggalSelesai.value = "";
            }
        });

        form.addEventListener("reset", function () {
            window.setTimeout(function () {
                tanggalSelesai.disabled = checkbox.checked;
            }, 0);
        });
    };

    var initProfileDatepickers = function () {
        var inputs = document.querySelectorAll('[data-kt-profile-datepicker="true"]');

        if (typeof flatpickr === "undefined") {
            return;
        }

        inputs.forEach(function (input) {
            flatpickr(input, {
                dateFormat: "Y-m-d",
                altInput: true,
                altFormat: "d M Y",
                allowInput: true
            });
        });
    };

    var prepareUploadModal = function (button) {
        var form = document.getElementById("kt_modal_upload_berkas_form");
        var modalTitle = document.querySelector("#kt_modal_upload_berkas .modal-header h2");
        var jenisField;
        var jenisWrapper;
        var jenisNama = "";
        var isSpecificTypeUpload = false;

        if (!form) {
            return;
        }

        form.reset();
        jenisField = form.querySelector('[name="id_jenis_berkas"]');
        jenisWrapper = jenisField ? jenisField.closest(".fv-row") : null;
        form.querySelector('[name="id_berkas"]').value = button ? (button.getAttribute("data-id") || "") : "";

        if (jenisField) {
            jenisField.value = "";
        }

        if (button && button.getAttribute("data-jenis-id") && jenisField) {
            jenisField.value = button.getAttribute("data-jenis-id");
            isSpecificTypeUpload = true;
        }

        if (button) {
            jenisNama = button.getAttribute("data-jenis-nama") || "";
        }

        if (jenisWrapper) {
            jenisWrapper.classList.toggle("d-none", isSpecificTypeUpload);
        }

        if (modalTitle) {
            if (button && button.getAttribute("data-action") === "ganti-berkas") {
                modalTitle.textContent = jenisNama ? "Ganti Dokumen Profil - " + jenisNama : "Ganti Dokumen Profil";
            } else if (button) {
                modalTitle.textContent = jenisNama ? "Upload Dokumen Profil - " + jenisNama : "Upload Dokumen Profil";
            } else {
                modalTitle.textContent = "Upload Dokumen Profil";
            }
        }
    };

    var handleActionButtons = function () {
        document.addEventListener("click", function (event) {
            var editRiwayatButton = event.target.closest('[data-action="edit-riwayat"]');
            var hapusRiwayatButton = event.target.closest('[data-action="hapus-riwayat"]');
            var tambahBerkasButton = event.target.closest('[data-action="tambah-berkas"]');
            var gantiBerkasButton = event.target.closest('[data-action="ganti-berkas"]');
            var uploadBerkasButton = event.target.closest('[data-action="upload-berkas"]');
            var hapusBerkasButton = event.target.closest('[data-action="hapus-berkas"]');

            if (editRiwayatButton) {
                populateEditRiwayat(editRiwayatButton.closest('[data-id]'));
                return;
            }

            if (hapusRiwayatButton) {
                confirmAction("Hapus riwayat kerja ini?", function () {
                    var row = hapusRiwayatButton.closest('[data-id]');
                    var id = row ? row.getAttribute("data-id") : "";

                    requestJson(
                        config.hapusRiwayatUrl.replace(/\/$/, "") + "/" + id,
                        config.hapusRiwayatMethod || "GET"
                    )
                        .then(function (response) {
                            reloadPage(response.message || "Riwayat kerja berhasil dihapus.");
                        })
                        .catch(function (error) {
                            showAlert("error", error.message);
                        });
                });

                return;
            }

            if (tambahBerkasButton) {
                prepareUploadModal(null);
                return;
            }

            if (gantiBerkasButton) {
                prepareUploadModal(gantiBerkasButton);
                return;
            }

            if (uploadBerkasButton) {
                prepareUploadModal(uploadBerkasButton);
                return;
            }

            if (hapusBerkasButton) {
                confirmAction("Hapus berkas ini?", function () {
                    var id = hapusBerkasButton.getAttribute("data-id") || "";

                    requestJson(
                        config.hapusBerkasUrl.replace(/\/$/, "") + "/" + id,
                        config.hapusBerkasMethod || "GET"
                    )
                        .then(function (response) {
                            reloadPage(response.message || "Berkas berhasil dihapus.");
                        })
                        .catch(function (error) {
                            showAlert("error", error.message);
                        });
                });
            }
        });
    };

    var init = function () {
        initTabPersistence();
        bindResetButton("#kt_modal_update_details", '[data-kt-pelamar-detail-modal-action="cancel-edit-detail"]', "#kt_modal_update_details_form");
        bindResetButton("#kt_modal_add_schedule", '[data-kt-pelamar-detail-modal-action="cancel-add-riwayat"]', "#kt_modal_add_schedule_form");
        bindResetButton("#kt_modal_edit_riwayat", '[data-kt-pelamar-detail-modal-action="cancel-edit-riwayat"]', "#kt_modal_edit_riwayat_form");
        bindResetButton("#kt_modal_upload_berkas", '[data-kt-pelamar-detail-modal-action="cancel-upload-berkas"]', "#kt_modal_upload_berkas_form");
        handleDetailSubmit();
        handleRiwayatSubmit();
        handleBerkasSubmit();
        handleUpdateEmail();
        handleUpdatePassword();
        handleTracerSubmit();
        handleActionButtons();
        bindMasihBekerjaToggle("kt_modal_add_schedule_form");
        bindMasihBekerjaToggle("kt_modal_edit_riwayat_form");
        initProfileDatepickers();
        initKartuAnggota();
    };

    var renderQrCode = function () {
        var qrContainer = document.getElementById("kt_qrcode");
        if (qrGenerated || !qrContainer || typeof QRCode === "undefined" || !accountId) {
            return;
        }
        qrContainer.innerHTML = "";
        new QRCode(qrContainer, {
            text: accountId,
            width: 80,
            height: 80
        });
        qrGenerated = true;
    };

    var initKartuAnggota = function () {
        var tabTrigger = document.querySelector('[href="#kt_tab_kartu_anggota"]');
        var downloadButton = document.getElementById("kt_unduh_kartu");
        var cardElement = document.getElementById("kt_kartu_anggota");

        if (tabTrigger) {
            tabTrigger.addEventListener("shown.bs.tab", function () {
                renderQrCode();
            });
        }

        if (downloadButton && cardElement) {
            downloadButton.addEventListener("click", function () {
                if (typeof html2canvas === "undefined") {
                    showAlert("error", "Library unduh kartu belum termuat.");
                    return;
                }
                renderQrCode();
                html2canvas(cardElement).then(function (canvas) {
                    var link = document.createElement("a");
                    link.download = "kartu-" + accountId + ".png";
                    link.href = canvas.toDataURL();
                    link.click();
                }).catch(function () {
                    showAlert("error", "Kartu gagal diunduh.");
                });
            });
        }
    };

    if (typeof KTUtil !== "undefined") {
        KTUtil.onDOMContentLoaded(init);
        return;
    }

    document.addEventListener("DOMContentLoaded", init);
})();
