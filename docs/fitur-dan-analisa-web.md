# Fitur dan Analisa Kekurangan Web Tracer Study

Dokumen ini merangkum fitur yang tersedia pada web Sistem Informasi Tracer Study Alumni SMK Teratai Putih 3 serta analisa kekurangan yang masih dapat dikembangkan.

## Ringkasan Sistem

Web ini digunakan untuk mengelola data alumni, pengisian tracer study, pengajuan legalisir, notifikasi, master data, serta laporan/export data tracer. Sistem memiliki tiga role utama:

| Role | Fungsi Utama |
| --- | --- |
| Super Admin | Mengelola sistem secara keseluruhan, admin sekolah, master data, tracer, legalisir, dan laporan |
| Admin Sekolah | Mengelola data operasional sekolah seperti master data, tracer alumni, legalisir, dan laporan |
| Alumni | Melengkapi profil, mengisi tracer study, mengajukan legalisir, dan menerima notifikasi |

## Fitur Umum

| No | Fitur | Keterangan |
| --- | --- | --- |
| 1 | Landing Page | Halaman awal sistem yang menampilkan informasi tracer study, fitur utama, statistik ringkas, tombol login, dan tombol daftar alumni |
| 2 | Login | Pengguna dapat masuk menggunakan email dan password |
| 3 | Logout | Pengguna dapat keluar dari sistem dan session akan dihapus |
| 4 | Registrasi Alumni | Alumni dapat mendaftar akun melalui halaman daftar |
| 5 | Redirect Dashboard Berdasarkan Role | Setelah login, pengguna diarahkan ke dashboard sesuai role masing-masing |
| 6 | Hak Akses Role | Setiap role hanya dapat mengakses menu sesuai hak aksesnya |

## Fitur Super Admin

| No | Fitur | Keterangan |
| --- | --- | --- |
| 1 | Dashboard Super Admin | Menampilkan ringkasan jumlah alumni, tracer, legalisir, aktivitas alumni, dan data penting lainnya |
| 2 | Notifikasi Legalisir | Menampilkan informasi pengajuan legalisir yang perlu diproses |
| 3 | Kelola Admin Sekolah | Super Admin dapat menambah, mengubah, menghapus, mengaktifkan, dan menonaktifkan akun Admin Sekolah |
| 4 | Kelola Angkatan | Super Admin dapat menambah, mengubah, dan menghapus data angkatan |
| 5 | Kelola Kompetensi | Super Admin dapat menambah, mengubah, dan menghapus data kompetensi keahlian |
| 6 | Kelola Aktivitas Alumni | Super Admin dapat mengelola pilihan aktivitas alumni seperti Bekerja, Kuliah, Wirausaha, dan Mencari Kerja |
| 7 | Kelola Data Tracer Alumni | Super Admin dapat melihat, memfilter, mengubah, menghapus tracer, dan menghapus data alumni |
| 8 | Aktivasi Alumni | Super Admin dapat mengubah status akun alumni menjadi aktif atau menunggu aktivasi |
| 9 | Kelola Legalisir | Super Admin dapat melihat pengajuan legalisir, mengubah status, dan memberi catatan |
| 10 | Export Excel | Super Admin dapat mengekspor laporan tracer ke format Excel |
| 11 | Export PDF | Super Admin dapat mengekspor laporan tracer ke format PDF |

## Fitur Admin Sekolah

| No | Fitur | Keterangan |
| --- | --- | --- |
| 1 | Dashboard Admin Sekolah | Menampilkan ringkasan data alumni, tracer, legalisir, dan grafik aktivitas |
| 2 | Kelola Angkatan | Admin Sekolah dapat mengelola data angkatan |
| 3 | Kelola Kompetensi | Admin Sekolah dapat mengelola data kompetensi keahlian |
| 4 | Kelola Aktivitas Alumni | Admin Sekolah dapat mengelola master aktivitas alumni |
| 5 | Kelola Tracer Alumni | Admin Sekolah dapat melihat dan mengubah data tracer alumni |
| 6 | Kelola Legalisir | Admin Sekolah dapat memproses pengajuan legalisir alumni |
| 7 | Notifikasi Legalisir | Admin Sekolah mendapat informasi jika ada pengajuan legalisir baru |
| 8 | Export Excel dan PDF | Admin Sekolah dapat mengekspor data tracer alumni |

## Fitur Alumni

| No | Fitur | Keterangan |
| --- | --- | --- |
| 1 | Dashboard Alumni | Menampilkan status akun, kelengkapan profil, status tracer, dan status legalisir |
| 2 | Profil Alumni | Alumni dapat melihat dan memperbarui data profil pribadi |
| 3 | Update Email | Alumni dapat mengubah email akun |
| 4 | Update Password | Alumni dapat mengubah password akun |
| 5 | Isi Tracer Study | Alumni dapat mengisi data tracer berdasarkan aktivitas setelah lulus |
| 6 | Edit Tracer Study | Alumni dapat memperbarui data tracer yang sudah pernah diisi |
| 7 | Hapus Tracer Study | Alumni dapat menghapus data tracer miliknya |
| 8 | Pengajuan Legalisir | Alumni dapat mengajukan legalisir dokumen |
| 9 | Riwayat Legalisir | Alumni dapat melihat riwayat dan status pengajuan legalisir |
| 10 | Catatan Admin | Alumni dapat melihat catatan admin pada pengajuan legalisir |
| 11 | Notifikasi | Alumni dapat menerima notifikasi terkait status pengajuan atau informasi sistem |

## Fitur Tracer Study

| No | Aktivitas | Data yang Dicatat |
| --- | --- | --- |
| 1 | Bekerja | Posisi kerja, nama instansi, bidang instansi, alamat instansi, tahun mulai kerja, kesesuaian kompetensi, dan penghasilan |
| 2 | Kuliah | Universitas, program studi, dan status kuliah |
| 3 | Wirausaha | Nama usaha, bidang usaha, modal awal, penghasilan usaha, dan kesesuaian kompetensi |
| 4 | Mencari Kerja | Rencana ke depan atau informasi terkait pencarian kerja |

## Fitur Legalisir

| No | Fitur | Keterangan |
| --- | --- | --- |
| 1 | Pengajuan Legalisir | Alumni dapat mengajukan legalisir melalui sistem |
| 2 | Status Pengajuan | Status dapat berupa diajukan, diproses, disetujui, ditolak, atau selesai |
| 3 | Catatan Admin | Admin dapat menambahkan catatan pada pengajuan |
| 4 | Notifikasi Admin | Admin mendapat pemberitahuan jika ada pengajuan baru |
| 5 | Notifikasi Alumni | Alumni mendapat informasi perubahan status pengajuan |

## Fitur Laporan dan Export

| No | Fitur | Keterangan |
| --- | --- | --- |
| 1 | Filter Data Tracer | Data tracer dapat difilter berdasarkan pencarian, angkatan, kompetensi, aktivitas, dan status |
| 2 | Tabel Data Tracer | Menampilkan data alumni dan status tracer dalam bentuk tabel |
| 3 | Grafik Aktivitas | Menampilkan ringkasan aktivitas alumni setelah lulus |
| 4 | Export Excel | Menghasilkan laporan tracer dalam format Excel |
| 5 | Export PDF | Menghasilkan laporan tracer dalam format PDF |

## Analisa Kekurangan Web

| No | Kekurangan | Dampak | Rekomendasi |
| --- | --- | --- | --- |
| 1 | Belum ada halaman khusus Data Alumni yang terpisah dari halaman tracer | Pengelolaan data alumni masih menyatu dengan menu tracer sehingga kurang eksplisit jika dibandingkan dengan istilah "Data Alumni" di skripsi | Tambahkan menu Data Alumni khusus, atau jelaskan pada skripsi bahwa data alumni dikelola melalui menu Data Tracer Alumni |
| 2 | Aktivasi alumni masih dilakukan melalui edit data pada halaman tracer | Proses verifikasi alumni belum terasa sebagai workflow khusus | Buat halaman atau tab khusus "Alumni Menunggu Aktivasi" agar admin lebih mudah menyetujui alumni baru |
| 3 | Field database `relevan_jurusan` masih memakai istilah lama | Secara tampilan sudah menjadi kompetensi, tetapi nama field di database masih kurang konsisten | Pada pengembangan berikutnya, rename field menjadi `relevan_kompetensi` melalui migration |
| 4 | Export Excel dibuat menggunakan format HTML `.xls` | File dapat dibuka di Excel, tetapi belum menggunakan format `.xlsx` modern | Gunakan library spreadsheet seperti PhpSpreadsheet jika ingin export lebih profesional |
| 5 | Export PDF masih sederhana | Tampilan PDF sudah berfungsi, tetapi layout dan pagination masih dapat dibuat lebih rapi | Gunakan library PDF seperti Dompdf, TCPDF, atau mPDF |
| 6 | Belum ada fitur upload dokumen legalisir | Admin hanya menerima pengajuan, belum ada lampiran dokumen dari alumni | Tambahkan upload file ijazah/transkrip jika kebutuhan legalisir ingin lebih lengkap |
| 7 | Belum ada riwayat perubahan status legalisir | Sistem hanya menyimpan status terakhir, belum menyimpan log perubahan lengkap | Tambahkan tabel riwayat status legalisir |
| 8 | Notifikasi masih bersifat internal web | Pengguna harus membuka sistem untuk melihat notifikasi | Tambahkan email notification atau WhatsApp gateway jika dibutuhkan |
| 9 | Belum ada fitur lupa password/reset password aktif | Jika pengguna lupa password, admin kemungkinan masih perlu membantu manual | Aktifkan fitur reset password melalui email |
| 10 | Validasi unik tracer per alumni perlu dipastikan pada level database | Jika tidak ada unique constraint, secara teori satu alumni bisa memiliki lebih dari satu data tracer | Tambahkan unique index pada `tb_tracer_alumni.id_alumni` jika aturan bisnisnya satu alumni satu tracer |
| 11 | Hak akses hapus alumni untuk Admin Sekolah perlu dikaji ulang | Admin Sekolah dapat memiliki akses besar terhadap data alumni | Pertimbangkan agar hapus alumni hanya untuk Super Admin |
| 12 | Belum ada audit log aktivitas pengguna | Perubahan penting tidak tercatat sebagai log aktivitas sistem | Tambahkan audit log untuk login, update data, hapus data, dan perubahan status |
| 13 | Dokumentasi user manual belum lengkap | Pengguna baru mungkin perlu panduan penggunaan sistem | Buat user manual untuk Super Admin, Admin Sekolah, dan Alumni |
| 14 | Belum ada pengujian otomatis | Kualitas sistem masih bergantung pada pengujian manual | Tambahkan unit test atau feature test untuk login, registrasi, tracer, legalisir, dan export |
| 15 | Tampilan landing page masih menggunakan statistik umum | Landing page belum menampilkan informasi profil sekolah yang lebih lengkap | Tambahkan profil singkat sekolah, kontak, dan informasi tujuan tracer study |

## Prioritas Pengembangan Selanjutnya

| Prioritas | Pengembangan | Alasan |
| --- | --- | --- |
| Tinggi | Buat halaman khusus aktivasi alumni | Agar proses persetujuan alumni baru lebih jelas dan mudah diuji |
| Tinggi | Tambahkan halaman Data Alumni khusus | Agar fitur sesuai dengan istilah yang umum dipakai dalam skripsi dan dokumentasi |
| Sedang | Perbaiki export menjadi `.xlsx` dan PDF library | Agar laporan terlihat lebih profesional |
| Sedang | Tambahkan upload dokumen legalisir | Agar fitur legalisir lebih lengkap |
| Sedang | Tambahkan riwayat status legalisir | Agar proses pengajuan lebih transparan |
| Rendah | Tambahkan email/WhatsApp notification | Berguna, tetapi tidak wajib untuk kebutuhan skripsi saat ini |
| Rendah | Tambahkan audit log dan automated test | Baik untuk pengembangan jangka panjang |

## Kesimpulan

Secara umum, web sudah memenuhi kebutuhan utama Sistem Informasi Tracer Study Alumni, yaitu pengelolaan alumni, tracer study, legalisir, notifikasi, master data, dan laporan/export. Web ini sudah layak digunakan sebagai implementasi skripsi, dengan catatan beberapa kekurangan di atas dapat dijelaskan sebagai batasan sistem atau rekomendasi pengembangan pada bagian penutup skripsi.
