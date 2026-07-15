<?php
/*
|-------------------------------------------------------------------
| LAYOUT AUTH
|-------------------------------------------------------------------
| Penjelasan fungsi kode ini: layout wrapper utama untuk seluruh
| halaman autentikasi yang memuat struktur HTML Metronic, asset CSS,
| asset JS, area ilustrasi kiri, dan placeholder konten form di sisi
| kanan.
| Alur kerja: view auth yang melakukan extend ke file ini hanya perlu
| mengirim section content, lalu CI4 merender section tersebut di area
| body kanan tanpa mengulang shell HTML Metronic.
|
| Tips Debugging:
| - Jika CSS/JS tidak termuat, periksa path assets di public/assets.
| - Jika form login tampil dobel atau layout pecah, periksa view child hanya berisi konten form tanpa wrapper halaman penuh.
*/
?>
<!DOCTYPE html>
<html lang="id">
<!--begin::Head-->
<head>
    <base href="../../../"/>
    <title><?= esc($title ?? 'Login - Sistem Tracer Study') ?></title>
    <meta charset="utf-8" />
    <meta name="description" content="Halaman login Sistem Tracer Study." />
    <meta name="keywords" content="metronic, bootstrap, login, tracer study, alumni" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta property="og:locale" content="id_ID" />
    <meta property="og:type" content="article" />
    <meta property="og:title" content="Login - Sistem Tracer Study" />
    <meta property="og:url" content="<?= current_url() ?>" />
    <meta property="og:site_name" content="Sistem Tracer Study" />
    <link rel="canonical" href="<?= current_url() ?>" />
    <link rel="icon" type="image/svg+xml" href="<?= base_url('assets/media/logos/logo-smk-teratai-putih-3.svg') ?>" />
    <!--begin::Fonts(mandatory for all pages)-->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700" />
    <!--end::Fonts-->
    <!--begin::Global Stylesheets Bundle(mandatory for all pages)-->
    <link href="<?= base_url('assets/plugins/global/plugins.bundle.css') ?>" rel="stylesheet" type="text/css" />
    <link href="<?= base_url('assets/css/style.bundle.css') ?>" rel="stylesheet" type="text/css" />
    <!--end::Global Stylesheets Bundle-->
    <script>
        // Frame-busting to prevent site from being loaded within a frame without permission (click-jacking)
        if (window.top != window.self) { window.top.location.replace(window.self.location.href); }
    </script>
</head>
<!--end::Head-->
<!--begin::Body-->
<body id="kt_body" class="app-blank bgi-size-cover bgi-attachment-fixed bgi-position-center">
    <!--begin::Theme mode setup on page load-->
    <script>
        var defaultThemeMode = "light";
        var themeMode;
        if ( document.documentElement ) {
            if ( document.documentElement.hasAttribute("data-bs-theme-mode")) {
                themeMode = document.documentElement.getAttribute("data-bs-theme-mode");
            } else {
                if ( localStorage.getItem("data-bs-theme") !== null ) {
                    themeMode = localStorage.getItem("data-bs-theme");
                } else {
                    themeMode = defaultThemeMode;
                }
            }
            if (themeMode === "system") {
                themeMode = window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light";
            }
            document.documentElement.setAttribute("data-bs-theme", themeMode);
        }
    </script>
    <!--end::Theme mode setup on page load-->
    <!--begin::Root-->
    <div class="d-flex flex-column flex-root" id="kt_app_root">
        <!--begin::Page bg image-->
        <style>
            body { background-image: url('<?= base_url('assets/media/auth/bg10.jpeg') ?>'); }
            html, body { width: 100%; max-width: 100%; overflow-x: hidden; }
            [data-bs-theme="dark"] body { background-image: url('<?= base_url('assets/media/auth/bg10-dark.jpeg') ?>'); }
            .auth-page-body,
            .auth-card,
            .auth-content {
                min-width: 0;
                max-width: 100%;
            }
            .auth-card {
                box-shadow: 0 24px 70px rgba(15, 23, 42, 0.08);
                border: 1px solid rgba(226, 232, 240, 0.72);
            }
            .auth-login-hero {
                padding: 10px 8px 0;
            }
            .auth-school-logo {
                width: 116px;
                height: 116px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 26px;
                background: linear-gradient(180deg, #f8fbff 0%, #eef6ff 100%);
                border: 1px solid #dbeafe;
                box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.9), 0 14px 30px rgba(37, 99, 235, 0.12);
            }
            .auth-school-logo img {
                width: 82px;
                height: 82px;
                object-fit: contain;
            }
            .auth-register-callout {
                padding: 14px 16px;
                border-radius: 14px;
                background: #f8fafc;
                border: 1px solid #e2e8f0;
            }
            .auth-input {
                border: 1px solid #dbe3ef !important;
                background-color: #fbfdff !important;
            }
            .auth-input:focus {
                border-color: #3b82f6 !important;
                box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.1);
            }
            @media (max-width: 575.98px) {
                .auth-page-body {
                    width: 100%;
                    max-width: 100vw;
                    padding: 12px !important;
                    align-items: flex-start !important;
                    overflow: hidden;
                }
                .auth-card {
                    width: calc(100vw - 24px) !important;
                    max-width: calc(100vw - 24px) !important;
                    flex: 0 1 auto;
                    padding: 20px !important;
                }
                .auth-content {
                    width: 100% !important;
                    max-width: 100% !important;
                }
                .auth-card form,
                .auth-card .fv-row,
                .auth-card .alert > div {
                    width: 100%;
                    min-width: 0;
                    max-width: 100%;
                    overflow-wrap: anywhere;
                }
                .auth-card .alert,
                .auth-card .form-control,
                .auth-card .form-select,
                .auth-card .input-group {
                    min-width: 0;
                    max-width: 100%;
                }
                .auth-card .alert > i {
                    flex: 0 0 auto;
                }
                .auth-school-logo {
                    width: 96px;
                    height: 96px;
                    border-radius: 22px;
                }
                .auth-school-logo img {
                    width: 70px;
                    height: 70px;
                }
            }
        </style>
        <!--end::Page bg image-->
        <!--begin::Authentication - Sign-in -->
        <div class="d-flex flex-column flex-lg-row flex-column-fluid">
            <!--begin::Body-->
            <div class="auth-page-body d-flex flex-column-fluid justify-content-center align-items-center p-12">
                <!--begin::Wrapper-->
                <div class="bg-body auth-card d-flex flex-column flex-center rounded-4 w-md-600px p-10">
                    <!--begin::Content-->
                    <div class="auth-content d-flex flex-center flex-column align-items-stretch h-lg-100 w-md-400px">
                        <!--begin::Wrapper-->
                        <div class="d-flex flex-center flex-column flex-column-fluid pb-15 pb-lg-20">
                            <?= $this->renderSection('content') ?>
                        </div>
                        <!--end::Wrapper-->
                    </div>
                    <!--end::Content-->
                </div>
                <!--end::Wrapper-->
            </div>
            <!--end::Body-->
        </div>
        <!--end::Authentication - Sign-in-->
    </div>
    <!--end::Root-->
    <!--begin::Javascript-->
    <script>var hostUrl = "assets/";</script>
    <!--begin::Global Javascript Bundle(mandatory for all pages)-->
    <script src="<?= base_url('assets/plugins/global/plugins.bundle.js') ?>"></script>
    <script src="<?= base_url('assets/js/scripts.bundle.js') ?>"></script>
    <!--end::Global Javascript Bundle-->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-password-toggle]').forEach(function (button) {
                button.addEventListener('click', function () {
                    var wrapper = button.closest('.input-group') || button.parentElement;
                    var input = wrapper ? wrapper.querySelector('[data-password-input]') : null;

                    if (! input) {
                        return;
                    }

                    var visible = input.type === 'text';
                    input.type = visible ? 'password' : 'text';
                    button.textContent = visible ? 'Lihat' : 'Sembunyikan';
                });
            });
        });
    </script>
    <!--begin::Custom Javascript(used for this page only)-->
    <script src="<?= base_url('assets/js/custom/authentication/sign-in/general.js') ?>"></script>
    <!--end::Custom Javascript-->
    <!--end::Javascript-->
</body>
<!--end::Body-->
</html>
