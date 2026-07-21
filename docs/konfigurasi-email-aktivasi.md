# Konfigurasi Email Aktivasi Alumni

Sistem mengirim notifikasi kepada admin setelah pendaftaran alumni berhasil disimpan. Kegagalan pengiriman email tidak membatalkan pendaftaran; notifikasi di dalam aplikasi tetap dibuat dan kegagalan SMTP dicatat pada log aplikasi.

Tambahkan konfigurasi berikut ke file `.env` pada server:

```ini
email.fromEmail = 'alamat-pengirim@gmail.com'
email.fromName = 'Tracer Study SMK Teratai Putih 3'
email.adminRecipients = 'alamat-admin@gmail.com'
email.protocol = 'smtp'
email.SMTPHost = 'smtp.gmail.com'
email.SMTPUser = 'alamat-pengirim@gmail.com'
email.SMTPPass = 'app-password-google'
email.SMTPPort = 587
email.SMTPCrypto = 'tls'
```

`email.adminRecipients` dapat berisi lebih dari satu alamat yang dipisahkan koma. Jika dikosongkan, sistem menggunakan alamat email akun Super Admin dan Admin Sekolah yang aktif. Akun demo dan alamat `.local` tidak dijadikan penerima otomatis.

Untuk Gmail, aktifkan verifikasi dua langkah lalu gunakan **App Password**. Jangan memakai password utama akun dan jangan memasukkan kredensial SMTP ke repository.

## Alur Aktivasi

1. Alumni mengisi formulir pendaftaran.
2. Sistem membuat akun nonaktif dengan status `menunggu_aktivasi`.
3. Admin menerima notifikasi aplikasi dan email tanpa informasi password alumni.
4. Admin membuka menu **Data Tracer Alumni**, memfilter **Menunggu Aktivasi**, lalu memeriksa data alumni.
5. Admin menekan **Aktifkan Akun**. Sistem mengaktifkan akun dan mencatat admin serta waktu verifikasi.
6. Sistem mengirim email aktivasi kepada alumni dan membuat notifikasi yang tampil saat alumni login.
7. Alumni dapat login setelah aktivasi berhasil.

Email aktivasi hanya berisi pemberitahuan dan tautan login. Password alumni tidak pernah disertakan. Akun demo tidak menerima email keluar agar alamat dummy tidak terkirim ke pihak lain.
