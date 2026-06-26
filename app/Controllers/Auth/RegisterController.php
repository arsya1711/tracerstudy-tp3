<?php

namespace App\Controllers\Auth;

use App\Controllers\BaseController;
use App\Models\AlumniModel;
use App\Models\NotifikasiModel;
use App\Models\PenggunaModel;
use App\Models\PeranModel;
use CodeIgniter\HTTP\RedirectResponse;
use Config\Database;
use RuntimeException;

/*
|-------------------------------------------------------------------
| REGISTER CONTROLLER
|-------------------------------------------------------------------
| Controller ini menangani pendaftaran mandiri alumni. Akun yang dibuat
| langsung bisa login, tetapi akses profil lengkap tetap menunggu
| persetujuan admin sekolah.
*/
class RegisterController extends BaseController
{
    protected $penggunaModel;
    protected $peranModel;
    protected $alumniModel;
    protected $notifikasiModel;
    protected $db;

    public function __construct()
    {
        $this->penggunaModel = new PenggunaModel();
        $this->peranModel    = new PeranModel();
        $this->alumniModel   = new AlumniModel();
        $this->notifikasiModel = new NotifikasiModel();
        $this->db            = Database::connect();
    }

    public function index()
    {
        if (session()->get('pengguna_login') === true) {
            return redirect()->to($this->dashboardUrl((string) session()->get('slug_peran')));
        }

        return view('auth/register', [
            'title'             => 'Daftar Alumni - Sistem Tracer Study',
            'daftar_angkatan'   => $this->ambilAngkatanAktif(),
            'daftar_kompetensi' => $this->ambilKompetensiAktif(),
        ]);
    }

    public function store(): RedirectResponse
    {
        $payload = [
            'nama_lengkap'          => trim((string) $this->request->getPost('nama_lengkap')),
            'email'                 => strtolower(trim((string) $this->request->getPost('email'))),
            'nomor_telepon'         => trim((string) $this->request->getPost('nomor_telepon')),
            'jenis_alumni'          => 'alumni',
            'nis'                   => trim((string) $this->request->getPost('nis')),
            'id_angkatan'           => (int) ($this->request->getPost('id_angkatan') ?? 0),
            'id_kompetensi'         => (int) ($this->request->getPost('id_kompetensi') ?? 0),
            'jenis_kelamin'          => trim((string) $this->request->getPost('jenis_kelamin')),
            'tempat_lahir'           => trim((string) $this->request->getPost('tempat_lahir')),
            'tanggal_lahir'          => trim((string) $this->request->getPost('tanggal_lahir')),
            'alamat'                 => trim((string) $this->request->getPost('alamat')),
            'password'              => (string) $this->request->getPost('password'),
            'password_confirmation' => (string) $this->request->getPost('password_confirmation'),
        ];

        /*
        |-------------------------------------------------------------------
        | VALIDASI PENDAFTARAN PELAMAR
        |-------------------------------------------------------------------
        | Blok ini memvalidasi data dasar alumni dan menambahkan
        | validasi akademik wajib.
        | Alur kerja: form mengirim jenis_alumni, controller menentukan
        | apakah field NIS, kompetensi, dan tahun lulus wajib diisi, lalu
        | semua error dikembalikan ke halaman daftar.
        |
        | Tips Debugging:
        | - Jika alumni bisa daftar tanpa NIS, cek kondisi jenis_alumni.
        | - Jika validasi tahun lulus gagal, pastikan value option adalah id_angkatan.
        */
        $rules = [
            'nama_lengkap'          => 'required|min_length[3]|max_length[150]',
            'email'                 => 'required|valid_email|max_length[190]|is_unique[tb_pengguna.email]',
            'nomor_telepon'         => 'permit_empty|min_length[8]|max_length[30]|regex_match[/^[0-9+().\\s-]+$/]',
            'jenis_alumni'          => 'required|in_list[alumni]',
            'nis'                   => 'required|max_length[20]',
            'id_angkatan'           => 'required|integer|greater_than[0]',
            'id_kompetensi'         => 'required|integer|greater_than[0]',
            'jenis_kelamin'          => 'required|in_list[Laki-laki,Perempuan]',
            'tempat_lahir'           => 'required|min_length[2]|max_length[100]',
            'tanggal_lahir'          => 'required|valid_date[Y-m-d]',
            'alamat'                 => 'required|min_length[5]',
            'password'              => 'required|min_length[8]|max_length[72]',
            'password_confirmation' => 'required|matches[password]',
        ];

        if (! $this->validateData($payload, $rules, [
            'email' => [
                'is_unique' => 'Email sudah terdaftar. Silakan gunakan email lain atau masuk dengan akun tersebut.',
            ],
            'nomor_telepon' => [
                'regex_match' => 'Nomor telepon hanya boleh berisi angka, spasi, tanda plus, kurung, titik, atau strip.',
            ],
            'password_confirmation' => [
                'matches' => 'Konfirmasi password belum sama.',
            ],
            'nis' => [
                'required' => 'NIS wajib diisi untuk alumni.',
            ],
            'id_angkatan' => [
                'required'     => 'Tahun lulus wajib dipilih untuk alumni.',
                'greater_than' => 'Tahun lulus wajib dipilih untuk alumni.',
            ],
            'id_kompetensi' => [
                'required'     => 'Kompetensi keahlian wajib dipilih untuk alumni.',
                'greater_than' => 'Kompetensi keahlian wajib dipilih untuk alumni.',
            ],
            'jenis_kelamin' => [
                'required' => 'Jenis kelamin wajib dipilih.',
                'in_list'  => 'Jenis kelamin belum valid.',
            ],
            'tempat_lahir' => [
                'required' => 'Tempat lahir wajib diisi.',
            ],
            'tanggal_lahir' => [
                'required'   => 'Tanggal lahir wajib diisi.',
                'valid_date' => 'Tanggal lahir belum valid.',
            ],
            'alamat' => [
                'required' => 'Alamat wajib diisi.',
            ],
        ])) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Data pendaftaran belum valid.')
                ->with('errors', $this->validator->getErrors());
        }

        $slugPeran = 'alumni';
        $peran = $this->peranModel->cariBySlug($slugPeran);

        if ($peran === null) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Peran alumni belum tersedia. Silakan hubungi admin sekolah.');
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

            $idAlumni = (int) $this->alumniModel->insert([
                'id_pengguna'         => $idPengguna,
                'nis'                 => $payload['nis'],
                'id_angkatan'         => $payload['id_angkatan'],
                'id_kompetensi'       => $payload['id_kompetensi'],
                'jenis_kelamin'        => $payload['jenis_kelamin'],
                'tempat_lahir'         => $payload['tempat_lahir'],
                'tanggal_lahir'        => $payload['tanggal_lahir'],
                'alamat'               => $payload['alamat'],
                'status_verifikasi'   => 'menunggu_aktivasi',
                'status_pendaftaran'  => 'menunggu_aktivasi',
                'terdaftar_pada'      => date('Y-m-d H:i:s'),
            ], true);

            if ($idAlumni <= 0) {
                throw new RuntimeException('Profil alumni gagal dibuat.');
            }

            $this->db->transComplete();
        } catch (\Throwable $th) {
            $this->db->transRollback();

            return redirect()->back()
                ->withInput()
                ->with('error', 'Pendaftaran gagal diproses. ' . $th->getMessage());
        }

        $this->kirimNotifikasiPendaftaranAlumni($payload, $idAlumni);

        return redirect()->to(site_url('login'))
            ->with('sukses', 'Pendaftaran berhasil. Silakan login, lalu tunggu persetujuan admin sekolah untuk membuka menu alumni.');
    }

    /*
    |-------------------------------------------------------------------
    | NOTIFIKASI PENDAFTARAN PELAMAR BARU
    |-------------------------------------------------------------------
    | Method ini mengirim notifikasi kepada Super Admin dan Admin
    | Sekolah ketika ada alumni baru yang mendaftar mandiri.
    | Alur kerja: setelah transaksi registrasi berhasil, sistem mencari
    | user dengan role superadmin dan admin_sekolah, lalu membuat baris
    | tb_notifikasi agar badge/bell header langsung muncul.
    |
    | Tips Debugging:
    | - Jika notifikasi tidak masuk, cek tabel tb_notifikasi dan role user.
    | - Jika hanya Super Admin yang menerima, cek akun admin_sekolah aktif.
    | - Jika target halaman salah, cek route tracer superadmin dan admin sekolah.
    */
    protected function kirimNotifikasiPendaftaranAlumni(array $payload, int $idAlumni): void
    {
        if ($idAlumni <= 0) {
            return;
        }

        $judul = 'Alumni baru mendaftar';
        $pesan = (string) ($payload['nama_lengkap'] ?? 'Alumni')
            . ' mendaftar sebagai alumni tracer study.';

        try {
            $this->notifikasiModel->buatUntukPengguna(
                $this->ambilIdPenggunaByRole(['superadmin']),
                'alumni_baru',
                $judul,
                $pesan,
                'superadmin/tracer'
            );

            $this->notifikasiModel->buatUntukPengguna(
                $this->ambilIdPenggunaByRole(['admin_sekolah']),
                'alumni_baru',
                $judul,
                $pesan,
                'admin-sekolah/tracer'
            );
        } catch (\Throwable $th) {
            log_message('error', 'Notifikasi alumni baru gagal dibuat: ' . $th->getMessage());
        }
    }

    protected function ambilIdPenggunaByRole(array $slugPeran): array
    {
        if (! $this->db->tableExists('tb_pengguna') || ! $this->db->tableExists('tb_peran')) {
            return [];
        }

        return array_map(
            static function (array $row): int {
                return (int) ($row['id_pengguna'] ?? 0);
            },
            $this->db->table('tb_pengguna u')
                ->select('u.id_pengguna')
                ->join('tb_peran r', 'r.id_peran = u.id_peran', 'inner')
                ->whereIn('r.slug_peran', $slugPeran)
                ->where('u.status_aktif', 1)
                ->get()
                ->getResultArray()
        );
    }

    /*
    |-------------------------------------------------------------------
    | MASTER DATA FORM ALUMNI
    |-------------------------------------------------------------------
    | Method helper ini mengambil master angkatan dan kompetensi aktif
    | untuk dropdown pada halaman daftar publik.
    | Alur kerja: index() memanggil helper ini, lalu view register
    | menampilkan opsi akademik alumni.
    |
    | Tips Debugging:
    | - Jika dropdown kosong, cek tabel tb_angkatan/tb_kompetensi.
    | - Jika error table not found, pastikan migration/master data sudah dijalankan.
    */
    protected function ambilAngkatanAktif(): array
    {
        if (! $this->db->tableExists('tb_angkatan')) {
            return [];
        }

        return $this->db->table('tb_angkatan')
            ->where('status_aktif', 1)
            ->orderBy('tahun_lulus', 'DESC')
            ->get()
            ->getResultArray();
    }

    protected function ambilKompetensiAktif(): array
    {
        if (! $this->db->tableExists('tb_kompetensi')) {
            return [];
        }

        return $this->db->table('tb_kompetensi')
            ->where('status_aktif', 1)
            ->orderBy('nama_kompetensi', 'ASC')
            ->get()
            ->getResultArray();
    }

    protected function dashboardUrl(string $slugPeran): string
    {
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
}
