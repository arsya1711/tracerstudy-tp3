"use strict";

/*
|-------------------------------------------------------------------
| LOGIN AJAX + SWEETALERT2
|-------------------------------------------------------------------
| Script ini menangani validasi form login Metronic dan mengirim data
| login ke controller CI4 melalui AJAX.
| Alur kerja:
| 1. Validasi email dan password di sisi browser.
| 2. Jika valid, kirim FormData ke endpoint /login via axios.
| 3. Tampilkan SweetAlert2 untuk sukses atau gagal.
| 4. Redirect ke dashboard jika autentikasi berhasil.
|
| Tips Debugging:
| - Jika validasi tidak jalan, periksa plugin FormValidation pada bundle.
| - Jika AJAX tidak dikenali CI4, periksa header X-Requested-With.
*/
var KTSigninGeneral = (function () {
    var form;
    var submitButton;
    var validator;

    /*
    |-------------------------------------------------------------------
    | ALERT GAGAL LOGIN
    |-------------------------------------------------------------------
    | Menampilkan notifikasi error yang ramah untuk kegagalan validasi
    | atau autentikasi dari server.
    | Alur kerja: pesan dari server dipetakan lalu dikirim ke SweetAlert2.
    |
    | Tips Debugging:
    | - Jika pesan kosong, periksa properti message pada response JSON.
    */
    var showErrorAlert = function (message) {
        Swal.fire({
            text: message || "Login gagal. Silakan coba lagi.",
            icon: "error",
            buttonsStyling: false,
            confirmButtonText: "Tutup",
            customClass: {
                confirmButton: "btn btn-primary"
            }
        });
    };

    /*
    |-------------------------------------------------------------------
    | RESET STATUS TOMBOL SUBMIT
    |-------------------------------------------------------------------
    | Mengembalikan tombol login ke kondisi normal setelah proses AJAX
    | selesai dijalankan.
    | Alur kerja: indikator loading dihapus lalu tombol diaktifkan lagi.
    |
    | Tips Debugging:
    | - Jika tombol tetap disable, pastikan finally() terpanggil.
    */
    var resetSubmitState = function () {
        submitButton.removeAttribute("data-kt-indicator");
        submitButton.disabled = false;
    };

    /*
    |-------------------------------------------------------------------
    | PROSES LOGIN AJAX
    |-------------------------------------------------------------------
    | Mengirim data form ke backend dan memproses response JSON dari
    | controller LoginController.
    | Alur kerja:
    | 1. FormData dikirim via axios POST.
    | 2. Response sukses menampilkan SweetAlert2.
    | 3. Browser diarahkan ke URL dashboard dari server.
    |
    | Tips Debugging:
    | - Jika redirect kosong, periksa key redirect pada JSON sukses.
    */
    var submitWithAjax = function () {
        submitButton.setAttribute("data-kt-indicator", "on");
        submitButton.disabled = true;

        axios.post(form.getAttribute("action"), new FormData(form), {
            headers: {
                "X-Requested-With": "XMLHttpRequest",
                "Accept": "application/json"
            }
        })
            .then(function (response) {
                var responseData = response.data || {};

                Swal.fire({
                    text: responseData.message || "Login berhasil.",
                    icon: "success",
                    buttonsStyling: false,
                    confirmButtonText: "Lanjut",
                    customClass: {
                        confirmButton: "btn btn-primary"
                    }
                }).then(function () {
                    if (responseData.redirect) {
                        window.location.href = responseData.redirect;
                    }
                });
            })
            .catch(function (error) {
                var response = error.response || {};
                var responseData = response.data || {};
                var errorMessage = responseData.message || "Terjadi kesalahan saat memproses login.";

                if (response.status === 422 && responseData.errors) {
                    var validationMessages = Object.values(responseData.errors);
                    if (validationMessages.length > 0) {
                        errorMessage = validationMessages[0];
                    }
                }

                showErrorAlert(errorMessage);
            })
            .then(function () {
                resetSubmitState();
            });
    };

    return {
        init: function () {
            form = document.querySelector("#kt_sign_in_form");
            submitButton = document.querySelector("#kt_sign_in_submit");

            if (!form || !submitButton || typeof FormValidation === "undefined") {
                return;
            }

            /*
            |-------------------------------------------------------------------
            | VALIDASI CLIENT-SIDE METRONIC
            |-------------------------------------------------------------------
            | Menjalankan validasi form sebelum request AJAX dikirim sehingga
            | user mendapat feedback cepat langsung di browser.
            | Alur kerja: plugin FormValidation memeriksa email dan password.
            |
            | Tips Debugging:
            | - Jika pesan validasi tidak muncul, periksa selector .fv-row.
            */
            validator = FormValidation.formValidation(form, {
                fields: {
                    email: {
                        validators: {
                            regexp: {
                                regexp: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
                                message: "Format email tidak valid."
                            },
                            notEmpty: {
                                message: "Email wajib diisi."
                            }
                        }
                    },
                    password: {
                        validators: {
                            notEmpty: {
                                message: "Kata sandi wajib diisi."
                            }
                        }
                    }
                },
                plugins: {
                    trigger: new FormValidation.plugins.Trigger(),
                    bootstrap: new FormValidation.plugins.Bootstrap5({
                        rowSelector: ".fv-row",
                        eleInvalidClass: "",
                        eleValidClass: ""
                    })
                }
            });

            form.addEventListener("submit", function (event) {
                event.preventDefault();

                validator.validate().then(function (status) {
                    if (status === "Valid") {
                        submitWithAjax();
                        return;
                    }

                    showErrorAlert("Form login masih belum valid. Silakan periksa kembali email dan kata sandi Anda.");
                });
            });
        }
    };
})();

KTUtil.onDOMContentLoaded(function () {
    KTSigninGeneral.init();
});
