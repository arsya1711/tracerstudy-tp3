# Implementasi Sistem Informasi Tracer Study Berbasis Website untuk Pemetaan Karier Lulusan pada SMK Teratai Putih Global 3 Bekasi

Arsya Riza Arrahman<br>
Program Studi Sistem Informasi, Fakultas Teknik dan Informatika, Universitas Bina Sarana Informatika<br>
Email: arsyariza2@gmail.com

## Abstrak

Pendataan alumni merupakan kebutuhan penting bagi sekolah untuk mengetahui perkembangan lulusan setelah menyelesaikan pendidikan. Informasi alumni dapat digunakan sebagai bahan evaluasi, penyusunan laporan, serta pendukung kebutuhan akreditasi. Pada SMK Teratai Putih Global 3 Bekasi, pendataan alumni dan tracer study sebelumnya masih dilakukan melalui media yang belum terintegrasi secara penuh, seperti formulir daring dan komunikasi melalui aplikasi pesan. Kondisi tersebut menyebabkan data alumni belum tersimpan secara terpusat, pembaruan data belum dapat dilakukan secara mandiri oleh alumni, dan penyusunan laporan masih membutuhkan pengolahan ulang. Penelitian ini bertujuan untuk mengimplementasikan Sistem Informasi Tracer Study berbasis website yang dapat membantu sekolah mengelola data alumni, memetakan aktivitas lulusan, menyediakan laporan, serta mendukung layanan pengajuan legalisir ijazah. Metode pengembangan yang digunakan adalah Rapid Application Development (RAD), yang meliputi tahap perencanaan kebutuhan, desain pengguna, pembangunan sistem, serta pengujian dan penerapan. Sistem dikembangkan menggunakan PHP dengan framework CodeIgniter 4, MySQL sebagai basis data, dan Bootstrap sebagai pendukung tampilan antarmuka. Hasil implementasi menunjukkan bahwa sistem menyediakan fitur registrasi dan aktivasi alumni, pengelolaan profil, pengisian tracer study, pengajuan legalisir ijazah, notifikasi, pengelolaan data master, laporan, grafik, serta export laporan. Pengujian menggunakan metode Black Box Testing menunjukkan bahwa fungsi utama sistem berjalan sesuai dengan hasil yang diharapkan.

**Kata kunci:** sistem informasi, tracer study, alumni, legalisir ijazah, website

## Abstract

Alumni data management is an important need for schools to identify graduate conditions after completing their education. Alumni information can support evaluation, reporting, and accreditation preparation. At SMK Teratai Putih Global 3 Bekasi, alumni data collection and tracer study activities were previously conducted through separate media, such as online forms and messaging applications. This condition caused alumni data to be less centralized, data updates could not be performed independently by alumni, and report preparation still required repeated data processing. This study aims to implement a web-based Tracer Study Information System to assist the school in managing alumni data, mapping graduate activities, preparing reports, and supporting diploma legalization services. The system was developed using the Rapid Application Development (RAD) method, consisting of requirements planning, user design, system construction, and testing and implementation. The application was built using PHP with the CodeIgniter 4 framework, MySQL as the database, and Bootstrap for the user interface. The implementation results show that the system provides alumni registration and activation, alumni profile management, tracer study submission, diploma legalization request, notification, master data management, reports, charts, and report export features. Black Box Testing results indicate that the main functions of the system operate according to the expected outcomes.

**Keywords:** information system, tracer study, alumni, diploma legalization, website

## Pendahuluan

Perkembangan teknologi informasi mendorong lembaga pendidikan untuk mengelola data secara lebih cepat, terstruktur, dan mudah diakses. Sekolah tidak hanya membutuhkan media penyampaian informasi, tetapi juga memerlukan sistem yang mampu mendukung proses pencatatan, pengolahan, pencarian, dan penyajian data. Dalam lingkungan pendidikan, sistem informasi berbasis web dapat membantu proses administrasi agar lebih efisien dan transparan (Rukmana et al., 2025).

SMK Teratai Putih Global 3 Bekasi memiliki kebutuhan untuk mengetahui kondisi alumni setelah lulus. Informasi mengenai alumni diperlukan untuk melihat ketercapaian lulusan dalam dunia kerja, pendidikan lanjutan, wirausaha, maupun kondisi alumni yang masih mencari pekerjaan. Data tersebut dapat digunakan sekolah sebagai bahan evaluasi, pendukung kebutuhan akreditasi, serta dasar peningkatan mutu lulusan.

Berdasarkan kondisi sistem berjalan, proses pendataan alumni dan pengumpulan data tracer study masih dilakukan melalui media yang belum terintegrasi secara penuh, seperti Google Form dan WhatsApp. Cara tersebut dapat membantu pengumpulan data, tetapi masih memiliki keterbatasan karena data tersebar, proses pembaruan data belum berjalan mandiri, dan penyusunan laporan masih membutuhkan pengolahan ulang. Selain itu, layanan pengajuan legalisir ijazah juga belum terhubung dengan sistem pendataan alumni, sehingga alumni harus melakukan komunikasi terpisah dengan pihak sekolah untuk mengajukan permohonan dan mengetahui status pengajuan.

Permasalahan tersebut menunjukkan perlunya Sistem Informasi Tracer Study berbasis website yang dapat menjadi media terpusat untuk mengelola data alumni dan memetakan karier lulusan. Sistem yang dikembangkan tidak hanya berfokus pada pengisian tracer study, tetapi juga dilengkapi fitur pengajuan legalisir ijazah, laporan, grafik, notifikasi, dan pembagian hak akses pengguna. Dengan adanya sistem ini, sekolah diharapkan dapat mengelola data alumni secara lebih efektif dan memperoleh informasi pemetaan karier lulusan secara lebih cepat.

## Metode Penelitian

Penelitian ini menggunakan metode Rapid Application Development (RAD). Metode RAD dipilih karena mendukung pengembangan sistem secara cepat melalui tahapan yang terarah dan melibatkan kebutuhan pengguna. RAD sesuai digunakan pada pengembangan aplikasi yang membutuhkan proses analisis, desain, pembangunan, dan pengujian dalam waktu relatif singkat dengan tetap memperhatikan masukan pengguna.

Tahapan pengembangan sistem dalam penelitian ini terdiri dari empat tahap. Tahap pertama adalah perencanaan kebutuhan, yaitu pengumpulan informasi melalui observasi dan wawancara terhadap kebutuhan sekolah dalam pengelolaan data alumni, tracer study, laporan, dan legalisir ijazah. Pada tahap ini juga ditentukan kebutuhan pengguna, kebutuhan fungsional, kebutuhan nonfungsional, serta perangkat pendukung sistem.

Tahap kedua adalah desain pengguna. Pada tahap ini dilakukan perancangan proses sistem menggunakan UML, meliputi use case diagram, activity diagram, dan sequence diagram. Selain itu, dilakukan perancangan basis data menggunakan Entity Relationship Diagram (ERD) dan Logical Record Structure (LRS) untuk menggambarkan hubungan data yang digunakan dalam sistem.

Tahap ketiga adalah pembangunan sistem. Sistem dikembangkan menggunakan PHP dengan framework CodeIgniter 4, MySQL sebagai basis data, dan Bootstrap sebagai pendukung tampilan antarmuka. Sistem dibangun dengan tiga peran utama, yaitu Super Admin, Admin Sekolah, dan Alumni. Setiap peran memiliki hak akses yang berbeda sesuai kebutuhan pengelolaan data.

Tahap keempat adalah pengujian dan penerapan. Pengujian dilakukan menggunakan Black Box Testing dengan berfokus pada fungsi sistem tanpa melihat struktur internal kode program. Pengujian meliputi fitur landing page, login, registrasi alumni, aktivasi alumni, profil alumni, tracer study, pengajuan legalisir, kelola pengajuan legalisir, notifikasi, master data, laporan tracer, export Excel, export PDF, manajemen admin sekolah, hak akses, dan logout.

## Hasil dan Pembahasan

Hasil penelitian berupa Sistem Informasi Tracer Study berbasis website pada SMK Teratai Putih Global 3 Bekasi. Sistem ini dirancang untuk membantu sekolah dalam mengelola data alumni, memperoleh informasi aktivitas lulusan, menyajikan laporan pemetaan karier, serta mendukung layanan administrasi alumni berupa pengajuan legalisir ijazah.

Sistem memiliki tiga jenis pengguna, yaitu Super Admin, Admin Sekolah, dan Alumni. Alumni dapat melakukan registrasi akun, login, mengelola profil, mengisi tracer study, mengajukan legalisir ijazah, melihat status pengajuan, menerima notifikasi, dan logout. Admin Sekolah dapat mengelola data alumni, melakukan aktivasi akun alumni, mengelola master data, memproses pengajuan legalisir, melihat laporan, melakukan export laporan, dan membuka notifikasi. Super Admin memiliki kewenangan tambahan untuk mengelola akun Admin Sekolah.

Fitur registrasi dan aktivasi alumni menjadi bagian penting dalam sistem. Setelah alumni melakukan registrasi, akun tidak langsung aktif. Admin Sekolah atau Super Admin perlu memeriksa dan mengaktifkan akun terlebih dahulu. Fitur ini digunakan untuk mencegah pengguna yang bukan alumni mengakses fitur utama seperti dashboard alumni, profil alumni, tracer study, pengajuan legalisir, dan notifikasi.

Fitur tracer study digunakan untuk mengelompokkan kondisi alumni setelah lulus berdasarkan aktivitas, seperti bekerja, kuliah, wirausaha, atau mencari kerja. Data tersebut dapat membantu sekolah melihat pemetaan karier lulusan berdasarkan angkatan dan kompetensi keahlian. Informasi yang sebelumnya harus dikumpulkan dari media terpisah dapat disimpan dalam satu sistem sehingga lebih mudah dicari dan diolah.

Fitur pengajuan legalisir ijazah memungkinkan alumni mengajukan permohonan secara daring. Alumni dapat mengisi jenis dokumen dan keperluan legalisir, kemudian memantau status pengajuan melalui sistem. Admin Sekolah dapat memperbarui status pengajuan menjadi diajukan, diproses, selesai, atau ditolak dengan catatan tertentu. Fitur ini membuat layanan administrasi alumni menjadi lebih tertata karena proses pengajuan dan statusnya terdokumentasi di dalam sistem.

Sistem juga menyediakan laporan dan grafik tracer study. Admin dapat melihat rekap data alumni, memfilter laporan berdasarkan angkatan, kompetensi keahlian, aktivitas alumni, status tracer, dan status akun. Laporan dapat diekspor dalam format Excel dan PDF sehingga dapat mendukung kebutuhan evaluasi, pelaporan internal, dan persiapan akreditasi sekolah.

Pengujian dilakukan menggunakan Black Box Testing terhadap fitur utama sistem. Hasil pengujian menunjukkan bahwa fungsi login, registrasi alumni, aktivasi alumni, profil alumni, tracer study, pengajuan legalisir, pengelolaan pengajuan legalisir, notifikasi, master data, laporan, export, manajemen admin, hak akses, dan logout berjalan sesuai dengan hasil yang diharapkan. Dengan demikian, sistem dapat digunakan sebagai media pengelolaan data alumni dan pemetaan karier lulusan secara terpusat.

## Kesimpulan

Berdasarkan hasil implementasi dan pengujian, Sistem Informasi Tracer Study berbasis website pada SMK Teratai Putih Global 3 Bekasi dapat membantu sekolah dalam mengelola data alumni secara lebih terpusat. Sistem menyediakan fitur pengisian tracer study yang dapat digunakan untuk mengetahui kondisi alumni setelah lulus, seperti bekerja, kuliah, wirausaha, atau mencari kerja.

Sistem juga menyediakan fitur pengajuan legalisir ijazah secara daring sehingga alumni dapat mengajukan permohonan dan memantau status pengajuan melalui sistem. Pembagian hak akses berdasarkan peran Super Admin, Admin Sekolah, dan Alumni membuat pengelolaan data dapat dilakukan sesuai kewenangan masing-masing. Fitur aktivasi alumni membantu membatasi akses pengguna sebelum diverifikasi oleh admin.

Laporan dan grafik tracer study yang tersedia pada sistem dapat membantu sekolah memperoleh informasi pemetaan karier lulusan berdasarkan angkatan, kompetensi keahlian, aktivitas setelah lulus, dan status pengisian tracer study. Hasil pengujian Black Box Testing menunjukkan bahwa fitur utama sistem berjalan sesuai dengan fungsi yang dirancang.

## Daftar Pustaka

Ali, I. (2024). *Aplikasi Web*. Widina.

Arifah, F. N., Gunawan, N., Farisi, A., Tobing, R. B., Mose, Y., Zakaria, M., Frisnawati, E., Anggraeni, A. F., Hanita, F., Suradi, A., & Kusuma, I. (2023). *Konsep Sistem Informasi: Konsep dan Penerapan*.

Gunawan, A., Ningsih, S., & Lantana, D. A. (2023). *Pengantar Basis Data*. Litnus.

Pramadhana, D., Febrianti, K. A., Farismana, R., & Ghozali, A. L. (2023). Design of online certificate legalisation information system at Indramayu State Polytechnic. *Antivirus: Jurnal Ilmiah Teknik Informatika, 17*(2), 261-270. https://doi.org/10.35457/antivirus.v17i2.3253

Rukmana, M. I. I., Susanto, A., & Tofik, W. (2025). Implementasi metode SSADM pada perancangan sistem informasi layanan administrasi sekolah berbasis web di Dinas Pendidikan dan Kebudayaan Kabupaten Situbondo. *Saturnus: Jurnal Teknologi dan Sistem Informasi, 3*(4), 52-64. https://doi.org/10.61132/saturnus.v3i4.1074

Sari, Y. N., & Mukmin, C. (2022). Pengembangan sistem informasi tracer study pada SMK Muhammadiyah 1 Palembang. *Journal of Information Technology Ampera, 3*(2).

Tonggiroh, M., Pardosi, V. B. A., Basiroh, & Nugroho, F. (2024). *Rekayasa Perangkat Lunak*.
