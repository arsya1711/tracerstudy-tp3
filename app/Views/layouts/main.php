<?php
/*
|-------------------------------------------------------------------
| LAYOUT MAIN
|-------------------------------------------------------------------
| Penjelasan fungsi kode ini: layout utama dashboard yang menyatukan
| partial head, sidebar, content, dan footer.
| Alur kerja: view dashboard melakukan extend ke file ini, lalu
| layout menyusun halaman lengkap dengan urutan partial Metronic
| dan area section content di tengah.
|
| Tips Debugging:
| - Jika halaman dashboard terpotong, periksa partial head, sidebar, dan footer semuanya ada.
| - Jika konten tidak tampil, periksa section content pada view dashboard.
*/
?>
<?= $this->include('partials/head') ?>
<?= $this->include('partials/sidebar') ?>
<?= $this->renderSection('content') ?>
<?= $this->include('partials/footer') ?>
