<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/*
|-------------------------------------------------------------------
| AUTH FILTER
|-------------------------------------------------------------------
| Penjelasan fungsi kode ini: filter ini menjaga route yang hanya
| boleh diakses pengguna yang sudah login.
| Alur kerja: CI4 memanggil filter ini sebelum controller pada route
| yang memakai alias auth dijalankan. Jika session pengguna_login
| tidak ada, user diarahkan ke halaman /login.
|
| Tips Debugging:
| - Jika route proteksi tetap bisa diakses tanpa login, periksa alias auth di app/Config/Filters.php.
| - Jika user selalu dilempar ke /login, periksa session pengguna_login sudah di-set saat authenticate().
*/
class AuthFilter implements FilterInterface
{
    /*
    |-------------------------------------------------------------------
    | METHOD BEFORE
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: memeriksa session login sebelum
    | request diteruskan ke controller tujuan.
    | Alur kerja: method dijalankan lebih dulu, lalu jika session
    | pengguna_login tidak bernilai true, request dihentikan dan
    | redirect ke /login dikembalikan.
    |
    | Tips Debugging:
    | - Jika selalu redirect ke /login padahal sudah login, periksa key session harus pengguna_login.
    | - Jika filter tidak terasa jalan, periksa route sudah memakai filter auth.
    */
    public function before(RequestInterface $request, $arguments = null)
    {
        if (! session()->get('pengguna_login')) {
            return redirect()->to('/login');
        }

        $user = $this->ambilPenggunaAktif();
        if ($user === null) {
            session()->destroy();

            if (method_exists($request, 'isAJAX') && $request->isAJAX()) {
                return service('response')
                    ->setStatusCode(401)
                    ->setJSON([
                        'status' => 'error',
                        'message' => 'Sesi berakhir atau akun sudah nonaktif. Silakan login ulang.',
                        'csrfHash' => csrf_hash(),
                    ]);
            }

            return redirect()->to('/login')->with('error', 'Sesi berakhir atau akun sudah nonaktif. Silakan login ulang.');
        }

        $this->sinkronSessionPengguna($user);

        $allowedRoles = is_array($arguments) ? array_filter(array_map('strval', $arguments)) : [];
        if ($allowedRoles === []) {
            return $this->pastikanAksesPelamarDisetujui($request, (string) ($user['slug_peran'] ?? ''), (string) ($user['status_pendaftaran'] ?? ''));
        }

        $slugPeran = (string) ($user['slug_peran'] ?? '');
        if (! in_array($slugPeran, $allowedRoles, true)) {
            if (method_exists($request, 'isAJAX') && $request->isAJAX()) {
                return service('response')
                    ->setStatusCode(403)
                    ->setJSON([
                        'status' => 'error',
                        'message' => 'Akses ditolak.',
                        'csrfHash' => csrf_hash(),
                    ]);
            }

            return redirect()->to($this->dashboardUrl($slugPeran))->with('error', 'Akses ditolak.');
        }

        return $this->pastikanAksesPelamarDisetujui($request, $slugPeran, (string) ($user['status_pendaftaran'] ?? ''));
    }

    protected function ambilPenggunaAktif(): ?array
    {
        $idPengguna = (int) session()->get('id_pengguna');
        if ($idPengguna <= 0) {
            return null;
        }

        $db = db_connect();
        if (! $db->tableExists('tb_pengguna') || ! $db->tableExists('tb_peran')) {
            return null;
        }

        $builder = $db->table('tb_pengguna u')
            ->select('u.id_pengguna, u.nama_lengkap, u.email, u.status_aktif, r.nama_peran, r.slug_peran')
            ->join('tb_peran r', 'r.id_peran = u.id_peran', 'left')
            ->where('u.id_pengguna', $idPengguna);

        if ($db->tableExists('tb_pelamar')) {
            $builder
                ->select('p.status_pendaftaran')
                ->join('tb_pelamar p', 'p.id_pengguna = u.id_pengguna', 'left');
        }

        $user = $builder->get()->getRowArray();
        if ($user === null || (int) ($user['status_aktif'] ?? 0) !== 1) {
            return null;
        }

        return $user;
    }

    protected function sinkronSessionPengguna(array $user): void
    {
        session()->set([
            'id_pengguna'         => (int) ($user['id_pengguna'] ?? session()->get('id_pengguna')),
            'nama_lengkap'        => $user['nama_lengkap'] ?? session()->get('nama_lengkap'),
            'nama_peran'          => $user['nama_peran'] ?? session()->get('nama_peran'),
            'email'               => $user['email'] ?? session()->get('email'),
            'slug_peran'          => $user['slug_peran'] ?? session()->get('slug_peran'),
            'status_pendaftaran'  => $user['status_pendaftaran'] ?? null,
        ]);
    }

    protected function pastikanAksesPelamarDisetujui(RequestInterface $request, string $slugPeran, string $statusPendaftaran)
    {
        if (! in_array($slugPeran, ['pelamar_umum', 'pelamar_alumni'], true)) {
            return null;
        }

        if ($this->isPelamarDashboardRequest()) {
            return null;
        }

        if ($statusPendaftaran === 'aktif') {
            return null;
        }

        if (method_exists($request, 'isAJAX') && $request->isAJAX()) {
            return service('response')
                ->setStatusCode(403)
                ->setJSON([
                    'status' => 'error',
                    'message' => 'Akun kamu masih menunggu persetujuan admin BKK.',
                    'csrfHash' => csrf_hash(),
                ]);
        }

        return redirect()->to(site_url('pelamar/dashboard'))
            ->with('error', 'Akun kamu masih menunggu persetujuan admin BKK. Saat ini hanya dashboard yang dapat diakses.');
    }

    protected function isPelamarDashboardRequest(): bool
    {
        $path = trim(uri_string(), '/');

        return in_array($path, [
            'pelamar/dashboard',
            'dashboard/pelamar-umum',
            'dashboard/pelamar-alumni',
        ], true);
    }

    protected function dashboardUrl(string $slugPeran): string
    {
        return match ($slugPeran) {
            'superadmin' => site_url('dashboard/superadmin'),
            'admin_sekolah' => site_url('admin-sekolah/dashboard'),
            'admin_dudi', 'admin_perusahaan' => site_url('admin-dudi/dashboard'),
            'pelamar_umum', 'pelamar_alumni' => site_url('pelamar/dashboard'),
            default => site_url('login'),
        };
    }

    /*
    |-------------------------------------------------------------------
    | METHOD AFTER
    |-------------------------------------------------------------------
    | Penjelasan fungsi kode ini: menyediakan hook setelah controller
    | selesai dijalankan, tetapi saat ini belum dipakai.
    | Alur kerja: CI4 memanggil method ini setelah response dari
    | controller dibuat dan sebelum dikirim ke browser.
    |
    | Tips Debugging:
    | - Jika ingin menambah logika sesudah request, lakukan pada method ini.
    */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Tidak ada proses tambahan setelah controller.
    }
}
