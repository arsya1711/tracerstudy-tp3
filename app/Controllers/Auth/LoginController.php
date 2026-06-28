<?php

namespace App\Controllers\Auth;

use App\Controllers\BaseController;
use App\Models\PenggunaModel;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;

/*
|-------------------------------------------------------------------
| LOGIN CONTROLLER
|-------------------------------------------------------------------
| Penjelasan fungsi kode ini: controller ini menangani halaman login,
| proses autentikasi, penyimpanan session login, dan logout pengguna.
| Alur kerja: user membuka /login untuk melihat form, mengirim email
| dan password ke authenticate(), lalu controller mencocokkan data
| pengguna dan mengarahkan user ke dashboard berdasarkan slug_peran.
|
| Tips Debugging:
| - Jika login selalu gagal, periksa nama field form harus email dan password.
| - Jika redirect salah, periksa nilai slug_peran hasil query model.
*/
class LoginController extends BaseController
{
    protected $penggunaModel;

    /*
    |-------------------------------------------------------------------
    | CONSTRUCTOR
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: menyiapkan instance PenggunaModel
    | yang dipakai oleh seluruh method autentikasi.
    | Alur kerja: CI4 memanggil constructor saat controller dibuat,
    | lalu model siap dipakai oleh index(), authenticate(), dan logout().
    |
    | Tips Debugging:
    | - Jika model tidak terbaca, periksa namespace App\Models\PenggunaModel.
    */
    public function __construct()
    {
        $this->penggunaModel = new PenggunaModel();
    }

    /*
    |-------------------------------------------------------------------
    | METHOD INDEX
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: menampilkan halaman login untuk
    | pengguna yang belum login dan melakukan redirect untuk session
    | yang sudah aktif.
    | Alur kerja: method memeriksa session pengguna_login, jika true
    | maka redirect berdasarkan slug_peran. Jika belum login, view
    | auth/login ditampilkan.
    |
    | Tips Debugging:
    | - Jika halaman login tidak tampil, periksa file app/Views/auth/login.php.
    | - Jika user yang sudah login tetap melihat form, periksa session pengguna_login.
    */
    public function index()
    {
        $this->simpanRedirectSetelahLogin();

        if (session()->get('pengguna_login') === true) {
            return $this->redirectBySlug((string) session()->get('slug_peran'));
        }

        return view('auth/login', [
            'title' => 'Login - Sistem Tracer Study',
        ]);
    }

    /*
    |-------------------------------------------------------------------
    | METHOD AUTHENTICATE
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: memvalidasi email dan password,
    | mengambil data pengguna dari database, lalu membuat session
    | login jika kredensial benar.
    | Alur kerja: method validasi input, memanggil cariByEmail(),
    | memeriksa status_aktif, memverifikasi password hash dengan
    | password_verify(), menyimpan session, mengupdate terakhir_login,
    | lalu redirect berdasarkan slug_peran.
    |
    | Tips Debugging:
    | - Jika muncul "Email atau password salah", periksa hasil cariByEmail() dan kolom kata_sandi di database.
    | - Jika login sukses tapi tidak pindah halaman yang benar, periksa mapping redirect pada method redirectBySlug().
    */
    public function authenticate()
    {
        $validasi = $this->validate([
            'email'    => 'required|valid_email',
            'password' => 'required',
        ]);

        if (! $validasi) {
            if ($this->request->isAJAX()) {
                return $this->response
                    ->setStatusCode(422)
                    ->setJSON([
                        'status'  => 'validation_error',
                        'message' => 'Email atau password salah',
                        'errors'  => $this->validator->getErrors(),
                    ]);
            }

            return redirect()->back()->withInput()->with('error', 'Email atau password salah');
        }

        $email    = strtolower(trim((string) $this->request->getPost('email')));
        $password = (string) $this->request->getPost('password');
        $user     = $this->penggunaModel->cariByEmail($email);

        if ($user === null || (int) $user['status_aktif'] !== 1 || ! password_verify($password, $user['kata_sandi'])) {
            if ($this->request->isAJAX()) {
                return $this->response
                    ->setStatusCode(401)
                    ->setJSON([
                        'status'  => 'auth_error',
                        'message' => 'Email atau password salah',
                    ]);
            }

            return redirect()->back()->withInput()->with('error', 'Email atau password salah');
        }

        if (($user['slug_peran'] ?? '') === 'alumni' && ($user['status_pendaftaran'] ?? '') !== 'aktif') {
            $message = 'Akun alumni belum diaktifkan oleh admin sekolah.';

            if ($this->request->isAJAX()) {
                return $this->response
                    ->setStatusCode(403)
                    ->setJSON([
                        'status'  => 'activation_required',
                        'message' => $message,
                    ]);
            }

            return redirect()->back()->withInput()->with('error', $message);
        }

        session()->set([
            'pengguna_login' => true,
            'id_pengguna'    => $user['id_pengguna'],
            'nama_lengkap'   => $user['nama_lengkap'],
            'nama_peran'     => $user['nama_peran'],
            'email'          => $user['email'],
            'slug_peran'     => $user['slug_peran'],
            'status_pendaftaran' => $user['status_pendaftaran'] ?? null,
        ]);

        $this->penggunaModel->update($user['id_pengguna'], [
            'terakhir_login' => date('Y-m-d H:i:s'),
        ]);

        if ($this->request->isAJAX()) {
            session()->setFlashdata('sukses', 'Login berhasil');

            return $this->response->setJSON([
                'status'   => 'success',
                'message'  => 'Login berhasil',
                'redirect' => $this->getRedirectUrl($user['slug_peran']),
            ]);
        }

        return $this->redirectBySlug($user['slug_peran'])->with('sukses', 'Login berhasil');
    }

    /*
    |-------------------------------------------------------------------
    | METHOD LOGOUT
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: menghapus session login pengguna
    | lalu mengarahkan kembali ke halaman login.
    | Alur kerja: method memanggil session()->destroy(), lalu CI4
    | mengirim redirect response ke endpoint /login.
    |
    | Tips Debugging:
    | - Jika setelah logout user masih dianggap login, periksa session browser benar-benar terhapus.
    | - Jika tombol logout 404, periksa route GET /logout di app/Config/Routes.php.
    */
    public function logout(): RedirectResponse
    {
        session()->destroy();

        return redirect()->to('/login')->with('sukses', 'Berhasil logout');
    }

    /*
    |-------------------------------------------------------------------
    | METHOD REDIRECT BY SLUG
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: menentukan tujuan redirect dashboard
    | berdasarkan slug_peran pengguna yang sudah login.
    | Alur kerja: slug dibaca dari hasil query atau session, lalu
    | switch mengembalikan redirect response ke URL dashboard terkait.
    |
    | Tips Debugging:
    | - Jika role tertentu diarahkan ke halaman salah, periksa nilai slug_peran di tabel tb_peran.
    | - Jika 404 setelah login, periksa route dashboard tujuan sudah didaftarkan.
    */
    protected function redirectBySlug(string $slugPeran): RedirectResponse
    {
        if ($this->getRedirectUrl($slugPeran) === site_url('login')) {
            return redirect()->to('/login')->with('error', 'Peran pengguna tidak dikenali');
        }

        return redirect()->to($this->getRedirectUrl($slugPeran));
    }

    /*
    |-------------------------------------------------------------------
    | METHOD GET REDIRECT URL
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: mengubah slug_peran menjadi URL
    | dashboard tujuan dalam format yang bisa dipakai oleh response
    | redirect biasa maupun response JSON AJAX.
    | Alur kerja: method menerima slug, lalu switch mengembalikan URL
    | absolut sesuai role pengguna yang berhasil login.
    |
    | Tips Debugging:
    | - Jika tombol lanjut tidak mengarah ke dashboard, periksa nilai redirect dari method ini.
    | - Jika role baru belum punya tujuan, tambahkan mapping slug pada switch di bawah.
    */
    protected function getRedirectUrl(string $slugPeran): string
    {
        $redirectSetelahLogin = $this->ambilRedirectSetelahLogin($slugPeran);

        if ($redirectSetelahLogin !== null) {
            return $redirectSetelahLogin;
        }

        switch ($slugPeran) {
            case 'superadmin':
                return site_url('dashboard/superadmin');

            case 'admin_sekolah':
                return site_url('admin-sekolah/dashboard');

            case 'alumni':
                return site_url('alumni/dashboard');

            default:
                return site_url('login');
        }
    }

    /*
    |-------------------------------------------------------------------
    | REDIRECT SETELAH LOGIN DARI HALAMAN PUBLIK
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: menyimpan tujuan internal halaman alumni
    | ketika user perlu login lebih dulu.
    */
    protected function simpanRedirectSetelahLogin(): void
    {
        $redirect = trim((string) $this->request->getGet('redirect'));

        if ($redirect === '' || preg_match('#^https?://#i', $redirect) === 1) {
            return;
        }

        $path = ltrim($redirect, '/');

        if (str_starts_with($path, 'alumni/')) {
            session()->set('redirect_setelah_login', $path);
        }
    }

    protected function ambilRedirectSetelahLogin(string $slugPeran): ?string
    {
        $path = trim((string) session()->get('redirect_setelah_login'));

        if ($path === '') {
            return null;
        }

        session()->remove('redirect_setelah_login');

        if ($slugPeran !== 'alumni') {
            return null;
        }

        return site_url($path);
    }
}
