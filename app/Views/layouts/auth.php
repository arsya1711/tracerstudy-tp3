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
<html lang="en">
<!--begin::Head-->
<head>
    <base href="../../../"/>
    <title><?= esc($title ?? 'Login - Sistem Tracer Study') ?></title>
    <meta charset="utf-8" />
    <meta name="description" content="Halaman login Sistem Tracer Study." />
    <meta name="keywords" content="metronic, bootstrap, login, tracer study, alumni" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta property="og:locale" content="en_US" />
    <meta property="og:type" content="article" />
    <meta property="og:title" content="Login - Sistem Tracer Study" />
    <meta property="og:url" content="<?= current_url() ?>" />
    <meta property="og:site_name" content="Sistem Tracer Study" />
    <link rel="canonical" href="<?= current_url() ?>" />
    <link rel="shortcut icon" href="<?= base_url('assets/media/logos/favicon.ico') ?>" />
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
            [data-bs-theme="dark"] body { background-image: url('<?= base_url('assets/media/auth/bg10-dark.jpeg') ?>'); }
        </style>
        <!--end::Page bg image-->
        <!--begin::Authentication - Sign-in -->
        <div class="d-flex flex-column flex-lg-row flex-column-fluid">
            <!--begin::Aside-->
            <div class="d-flex flex-lg-row-fluid">
                <!--begin::Content-->
                <div class="d-flex flex-column flex-center pb-0 pb-lg-10 p-10 w-100">
                    <!--begin::Image-->
                    <img class="theme-light-show mx-auto mw-100 w-150px w-lg-300px mb-10 mb-lg-20" src="<?= base_url('assets/media/auth/agency.png') ?>" alt="Ilustrasi autentikasi" />
                    <img class="theme-dark-show mx-auto mw-100 w-150px w-lg-300px mb-10 mb-lg-20" src="<?= base_url('assets/media/auth/agency-dark.png') ?>" alt="Ilustrasi autentikasi dark" />
                    <!--end::Image-->
                    <!--begin::Title-->
                    <h1 class="text-gray-800 fs-2qx fw-bold text-center mb-7">Fast, Efficient and Productive</h1>
                    <!--end::Title-->
                    <!--begin::Text-->
                    <div class="text-gray-600 fs-base text-center fw-semibold">
                        Sistem ini membantu proses tracer study dan pengelolaan
                        <br />bursa kerja khusus sekolah secara terintegrasi.
                    </div>
                    <!--end::Text-->
                </div>
                <!--end::Content-->
            </div>
            <!--begin::Aside-->
            <!--begin::Body-->
            <div class="d-flex flex-column-fluid flex-lg-row-auto justify-content-center justify-content-lg-end p-12">
                <!--begin::Wrapper-->
                <div class="bg-body d-flex flex-column flex-center rounded-4 w-md-600px p-10">
                    <!--begin::Content-->
                    <div class="d-flex flex-center flex-column align-items-stretch h-lg-100 w-md-400px">
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
