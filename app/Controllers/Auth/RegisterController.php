<?php

namespace App\Controllers\Auth;

use App\Controllers\BaseController;
use App\Models\AlumniModel;
use App\Models\PelamarModel;
use App\Models\PenggunaModel;
use App\Models\PeranModel;
use CodeIgniter\HTTP\RedirectResponse;
use Config\Database;
use RuntimeException;

/*
|-------------------------------------------------------------------
| REGISTER CONTROLLER
|-------------------------------------------------------------------
| Controller ini menangani pendaftaran mandiri untuk pelamar umum
| dan alumni. Akun yang dibuat langsung bisa login, tetapi akses
| menu pelamar tetap menunggu persetujuan admin BKK.
*/
class RegisterController extends BaseController
{
    protected PenggunaModel $penggunaModel;
    protected PelamarModel $pelamarModel;
    protected PeranModel $peranModel;
    protected AlumniModel $alumniModel;
    protected \CodeIgniter\Database\BaseConnection $db;

    public function __construct()
    {
        $this->penggunaModel = new PenggunaModel();
        $this->pelamarModel  = new PelamarModel();
        $this->peranModel    = new PeranModel();
        $this->alumniModel   = new AlumniModel();
        $this->db            = Database::connect();
    }

    public function index(): string|RedirectResponse
    {
        if (session()->get('pengguna_login') === true) {
            return redirect()->to($this->dashboardUrl((string) session()->get('slug_peran')));
        }

        return view('auth/register', [
            'title' => 'Daftar Pelamar - Sistem Tracer Study & BKK',
        ]);
    }

    public function store(): RedirectResponse
    {
        $payload = [
            'nama_lengkap'          => trim((string) $this->request->getPost('nama_lengkap')),
            'email'                 => strtolower(trim((string) $this->request->getPost('email'))),
            'nomor_telepon'         => trim((string) $this->request->getPost('nomor_telepon')),
            'jenis_pelamar'         => trim((string) $this->request->getPost('jenis_pelamar')),
            'password'              => (string) $this->request->getPost('password'),
            'password_confirmation' => (string) $this->request->getPost('password_confirmation'),
        ];

        if (! $this->validateData($payload, [
            'nama_lengkap'          => 'required|min_length[3]|max_length[150]',
            'email'                 => 'required|valid_email|max_length[190]|is_unique[tb_pengguna.email]',
            'nomor_telepon'         => 'permit_empty|min_length[8]|max_length[30]|regex_match[/^[0-9+().\\s-]+$/]',
            'jenis_pelamar'         => 'required|in_list[umum,alumni]',
            'password'              => 'required|min_length[8]|max_length[72]',
            'password_confirmation' => 'required|matches[password]',
        ], [
            'email' => [
                'is_unique' => 'Email sudah terdaftar. Silakan gunakan email lain atau masuk dengan akun tersebut.',
            ],
            'nomor_telepon' => [
                'regex_match' => 'Nomor telepon hanya boleh berisi angka, spasi, tanda plus, kurung, titik, atau strip.',
            ],
            'password_confirmation' => [
                'matches' => 'Konfirmasi password belum sama.',
            ],
        ])) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Data pendaftaran belum valid.')
                ->with('errors', $this->validator->getErrors());
        }

        $slugPeran = $payload['jenis_pelamar'] === 'alumni' ? 'pelamar_alumni' : 'pelamar_umum';
        $peran = $this->peranModel->cariBySlug($slugPeran);

        if ($peran === null) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Peran pelamar belum tersedia. Silakan hubungi admin BKK.');
        }

        try {
            $this->db->transException(true)->transStart();

            $idPengguna = (int) $this->penggunaModel->insert([
                'id_peran'      => (int) $peran['id_peran'],
                'nama_lengkap'  => $payload['nama_lengkap'],
                'email'         => $payload['email'],
                'kata_sandi'    => password_hash($payload['password'], PASSWORD_DEFAULT),
                'nomor_telepon' => $payload['nomor_telepon'] !== '' ? $payload['nomor_telepon'] : null,
                'status_aktif'  => 1,
            ], true);

            if ($idPengguna <= 0) {
                throw new RuntimeException('Akun pengguna gagal dibuat.');
            }

            $idPelamar = (int) $this->pelamarModel->insert([
                'id_pengguna'        => $idPengguna,
                'account_id'         => $this->pelamarModel->generateAccountId(),
                'status_pendaftaran' => 'menunggu_aktivasi',
                'terdaftar_pada'     => date('Y-m-d H:i:s'),
                'diaktivasi_oleh'    => null,
                'diaktivasi_pada'    => null,
            ], true);

            if ($idPelamar <= 0) {
                throw new RuntimeException('Profil pelamar gagal dibuat.');
            }

            if ($slugPeran === 'pelamar_alumni') {
                $this->alumniModel->insert([
                    'id_pelamar' => $idPelamar,
                ]);
            }

            $this->db->transComplete();
        } catch (\Throwable $th) {
            $this->db->transRollback();

            return redirect()->back()
                ->withInput()
                ->with('error', 'Pendaftaran gagal diproses. ' . $th->getMessage());
        }

        return redirect()->to(site_url('login'))
            ->with('sukses', 'Pendaftaran berhasil. Silakan login, lalu tunggu persetujuan admin BKK untuk membuka menu pelamar.');
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
}
