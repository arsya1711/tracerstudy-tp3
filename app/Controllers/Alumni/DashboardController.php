<?php

namespace App\Controllers\Alumni;

use App\Controllers\BaseController;
use App\Models\AlumniModel;
use App\Models\AktivitasModel;
use App\Models\AngkatanModel;
use App\Models\KompetensiModel;
use App\Models\PengajuanLegalisirModel;
use App\Models\PenggunaModel;
use App\Models\TracerAlumniModel;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;

class DashboardController extends BaseController
{
    protected AlumniModel $alumniModel;
    protected AktivitasModel $aktivitasModel;
    protected AngkatanModel $angkatanModel;
    protected KompetensiModel $kompetensiModel;
    protected PengajuanLegalisirModel $legalisirModel;
    protected PenggunaModel $penggunaModel;
    protected TracerAlumniModel $tracerModel;
    protected $db;

    public function __construct()
    {
        $this->alumniModel = new AlumniModel();
        $this->aktivitasModel = new AktivitasModel();
        $this->angkatanModel = new AngkatanModel();
        $this->kompetensiModel = new KompetensiModel();
        $this->legalisirModel = new PengajuanLegalisirModel();
        $this->penggunaModel = new PenggunaModel();
        $this->tracerModel = new TracerAlumniModel();
        $this->db = db_connect();
    }

    public function index(): string|RedirectResponse
    {
        $alumni = $this->ambilAlumniLogin();
        if ($alumni === null) {
            return redirect()->to(site_url('logout'))->with('error', 'Profil alumni belum ditemukan.');
        }

        $tracerTerakhir = $this->tracerModel->ambilTerakhirByAlumni((int) $alumni['id_alumni']);
        $onboarding = $this->bangunChecklist($alumni, $tracerTerakhir);

        return view('alumni/dashboard', [
            'title' => 'Dashboard Alumni - Sistem Tracer Study',
            'alumni' => $alumni,
            'isAlumni' => true,
            'tracerTerakhir' => $tracerTerakhir,
            'legalisirTerbaru' => $this->legalisirModel->ambilTerbaruByAlumni((int) $alumni['id_alumni']),
            'onboarding' => $onboarding,
        ]);
    }

    public function profil(): string|RedirectResponse
    {
        $alumni = $this->ambilAlumniLogin();
        if ($alumni === null) {
            return redirect()->to(site_url('logout'))->with('error', 'Profil alumni belum ditemukan.');
        }

        return view('alumni/profil/index', [
            'title' => 'Profil Alumni - Sistem Tracer Study',
            'alumni' => $alumni,
            'tracerTerakhir' => $this->tracerModel->ambilTerakhirByAlumni((int) $alumni['id_alumni']),
            'daftarAngkatan' => $this->angkatanModel->where('status_aktif', 1)
                ->orderBy('tahun_lulus', 'DESC')
                ->findAll(),
            'daftarKompetensi' => $this->kompetensiModel->where('status_aktif', 1)
                ->orderBy('nama_kompetensi', 'ASC')
                ->findAll(),
        ]);
    }

    public function tracer(): string|RedirectResponse
    {
        $alumni = $this->ambilAlumniLogin();
        if ($alumni === null) {
            return redirect()->to(site_url('logout'))->with('error', 'Profil alumni belum ditemukan.');
        }

        return view('alumni/tracer/index', [
            'title' => 'Tracer - Sistem Tracer Study',
            'alumni' => $alumni,
            'tracer' => $this->tracerModel->ambilTerakhirByAlumni((int) $alumni['id_alumni']),
            'daftarAktivitas' => $this->ambilAktivitasUtamaTracer(),
            'penghasilanOptions' => $this->penghasilanOptions(),
        ]);
    }

    public function updateDetail(int $idAlumni): ResponseInterface|RedirectResponse
    {
        $alumni = $this->ambilAlumniLogin();
        if ($alumni === null || (int) $alumni['id_alumni'] !== $idAlumni) {
            return $this->responseAlumni('error', 'Akses profil alumni ditolak.', 403);
        }

        $this->penggunaModel->update((int) $alumni['id_pengguna'], [
            'nama_lengkap' => trim((string) $this->request->getPost('nama_lengkap')) ?: $alumni['nama_lengkap'],
            'nomor_telepon' => trim((string) $this->request->getPost('nomor_telepon')) ?: null,
        ]);

        $this->alumniModel->update($idAlumni, [
            'jenis_kelamin' => trim((string) $this->request->getPost('jenis_kelamin')) ?: null,
            'tempat_lahir' => trim((string) $this->request->getPost('tempat_lahir')) ?: null,
            'tanggal_lahir' => trim((string) $this->request->getPost('tanggal_lahir')) ?: null,
            'alamat' => trim((string) $this->request->getPost('alamat')) ?: null,
            'nis' => trim((string) $this->request->getPost('nis')) ?: $alumni['nis'],
            'nisn' => trim((string) $this->request->getPost('nisn')) ?: null,
            'no_ijazah' => trim((string) $this->request->getPost('no_ijazah')) ?: null,
            'id_angkatan' => (int) ($this->request->getPost('id_angkatan') ?: $alumni['id_angkatan']),
            'id_kompetensi' => (int) ($this->request->getPost('id_kompetensi') ?: $alumni['id_kompetensi']),
        ]);

        return $this->responseAlumni('success', 'Profil alumni berhasil diperbarui.', 200, site_url('alumni/profil'));
    }

    public function updateEmail(): ResponseInterface|RedirectResponse
    {
        $alumni = $this->ambilAlumniLogin();
        if ($alumni === null) {
            return $this->responseAlumni('error', 'Profil alumni belum ditemukan.', 404);
        }

        $email = strtolower(trim((string) $this->request->getPost('email')));
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->responseAlumni('error', 'Email belum valid.', 422, site_url('alumni/profil'));
        }

        $duplikat = $this->db->table('tb_pengguna')
            ->where('email', $email)
            ->where('id_pengguna !=', (int) $alumni['id_pengguna'])
            ->countAllResults();

        if ($duplikat > 0) {
            return $this->responseAlumni('error', 'Email sudah digunakan akun lain.', 422, site_url('alumni/profil'));
        }

        $this->penggunaModel->update((int) $alumni['id_pengguna'], ['email' => $email]);
        session()->set('email', $email);

        return $this->responseAlumni('success', 'Email berhasil diperbarui.', 200, site_url('alumni/profil'));
    }

    public function updatePassword(): ResponseInterface|RedirectResponse
    {
        $alumni = $this->ambilAlumniLogin();
        if ($alumni === null) {
            return $this->responseAlumni('error', 'Profil alumni belum ditemukan.', 404);
        }

        $password = (string) $this->request->getPost('password');
        $konfirmasi = (string) $this->request->getPost('password_confirmation');
        if (strlen($password) < 8 || $password !== $konfirmasi) {
            return $this->responseAlumni('error', 'Password minimal 8 karakter dan konfirmasi harus sama.', 422, site_url('alumni/profil'));
        }

        $this->penggunaModel->update((int) $alumni['id_pengguna'], [
            'kata_sandi' => password_hash($password, PASSWORD_DEFAULT),
        ]);

        return $this->responseAlumni('success', 'Password berhasil diperbarui.', 200, site_url('alumni/profil'));
    }

    public function simpanTracer(): ResponseInterface|RedirectResponse
    {
        $alumni = $this->ambilAlumniLogin();
        if ($alumni === null) {
            return $this->responseAlumni('error', 'Profil alumni belum ditemukan.', 404);
        }

        $idAktivitas = (int) $this->request->getPost('id_aktivitas');
        if ($idAktivitas <= 0) {
            return $this->responseAlumni('error', 'Aktivitas setelah lulus wajib dipilih.', 422);
        }

        $kuliahPernah = (string) $this->request->getPost('kuliah_pernah');
        if (! in_array($kuliahPernah, ['0', '1'], true)) {
            return $this->responseAlumni('error', 'Riwayat kuliah wajib dipilih.', 422);
        }

        $payload = [
            'id_alumni' => (int) $alumni['id_alumni'],
            'id_aktivitas' => $idAktivitas,
            'status' => 'terkirim',
            'posisi_kerja' => trim((string) $this->request->getPost('posisi_kerja')) ?: null,
            'nama_instansi' => trim((string) $this->request->getPost('nama_instansi')) ?: null,
            'bidang_instansi' => trim((string) $this->request->getPost('bidang_instansi')) ?: null,
            'alamat_instansi' => trim((string) $this->request->getPost('alamat_instansi')) ?: null,
            'tahun_mulai_kerja' => trim((string) $this->request->getPost('tahun_mulai_kerja')) ?: null,
            'relevan_jurusan' => $this->request->getPost('relevan_jurusan') !== null ? (int) $this->request->getPost('relevan_jurusan') : null,
            'penghasilan_range' => trim((string) $this->request->getPost('penghasilan_range')) ?: null,
            'universitas' => trim((string) $this->request->getPost('universitas')) ?: null,
            'program_studi' => trim((string) $this->request->getPost('program_studi')) ?: null,
            'status_kuliah' => trim((string) $this->request->getPost('status_kuliah')) ?: null,
            'nama_usaha' => trim((string) $this->request->getPost('nama_usaha')) ?: null,
            'bidang_usaha' => trim((string) $this->request->getPost('bidang_usaha')) ?: null,
            'modal_awal' => trim((string) $this->request->getPost('modal_awal')) ?: null,
            'penghasilan_usaha' => trim((string) $this->request->getPost('penghasilan_usaha')) ?: null,
            'rencana_kedepan' => trim((string) $this->request->getPost('rencana_kedepan')) ?: null,
        ];

        if ($kuliahPernah !== '1') {
            $payload['universitas'] = null;
            $payload['program_studi'] = null;
            $payload['status_kuliah'] = null;
        }

        $existing = $this->tracerModel->where('id_alumni', (int) $alumni['id_alumni'])->first();
        if ($existing !== null) {
            $this->tracerModel->update((int) $existing['id_tracer'], $payload);
        } else {
            $this->tracerModel->insert($payload);
        }

        return $this->responseAlumni('success', 'Data tracer study berhasil disimpan.');
    }

    public function hapusTracer(): ResponseInterface|RedirectResponse
    {
        $alumni = $this->ambilAlumniLogin();
        if ($alumni === null) {
            return $this->responseAlumni('error', 'Profil alumni belum ditemukan.', 404);
        }

        $tracer = $this->tracerModel->where('id_alumni', (int) $alumni['id_alumni'])->first();
        if ($tracer === null) {
            return $this->responseAlumni('error', 'Data tracer belum tersedia.', 404);
        }

        $this->tracerModel->delete((int) $tracer['id_tracer']);

        return $this->responseAlumni('success', 'Data tracer berhasil dihapus.');
    }

    protected function ambilAktivitasUtamaTracer(): array
    {
        $aktivitas = $this->aktivitasModel->where('status_aktif', 1)
            ->orderBy('nama_aktivitas', 'ASC')
            ->findAll();

        return array_values(array_filter($aktivitas, static function (array $item): bool {
            $nama = strtolower((string) ($item['nama_aktivitas'] ?? ''));

            return ! str_contains($nama, 'kuliah') && ! str_contains($nama, 'studi');
        }));
    }

    protected function ambilAlumniLogin(): ?array
    {
        if ((string) session()->get('slug_peran') !== 'alumni') {
            return null;
        }

        return $this->alumniModel->ambilLengkapByPengguna((int) session()->get('id_pengguna'));
    }

    protected function bangunChecklist(array $alumni, ?array $tracer): array
    {
        $profilLengkap = trim((string) ($alumni['nis'] ?? '')) !== ''
            && ! empty($alumni['id_angkatan'])
            && ! empty($alumni['id_kompetensi']);

        $steps = [
            [
                'key' => 'akun',
                'title' => 'Akun alumni aktif',
                'description' => 'Akun alumni sudah terhubung ke sistem tracer study.',
                'done' => true,
                'url' => site_url('alumni/dashboard'),
                'button' => 'Dashboard',
            ],
            [
                'key' => 'profil',
                'title' => 'Lengkapi profil alumni',
                'description' => 'Pastikan data akademik, angkatan, dan kompetensi sudah sesuai.',
                'done' => $profilLengkap,
                'url' => site_url('alumni/profil'),
                'button' => 'Lengkapi Profil',
            ],
            [
                'key' => 'tracer',
                'title' => 'Tracer',
                'description' => 'Kirim aktivitas setelah lulus untuk rekap tracer sekolah.',
                'done' => $tracer !== null,
                'url' => site_url('alumni/tracer'),
                'button' => 'Tracer',
            ],
        ];

        $selesai = count(array_filter($steps, static fn (array $step): bool => (bool) $step['done']));
        $next = null;
        foreach ($steps as $step) {
            if (! $step['done']) {
                $next = $step;
                break;
            }
        }

        return [
            'steps' => $steps,
            'next_step' => $next,
            'ready' => $selesai === count($steps),
            'progress' => [
                'total' => count($steps),
                'selesai' => $selesai,
                'persen' => (int) round(($selesai / count($steps)) * 100),
            ],
        ];
    }

    protected function responseAlumni(string $status, string $message, int $httpStatus = 200, ?string $redirectUrl = null): ResponseInterface|RedirectResponse
    {
        if ($this->request->isAJAX()) {
            return $this->response
                ->setStatusCode($httpStatus)
                ->setJSON([
                    'status' => $status,
                    'message' => $message,
                    'csrfHash' => csrf_hash(),
                ]);
        }

        return redirect()->to($redirectUrl ?? site_url('alumni/tracer'))->with($status === 'success' ? 'sukses' : 'error', $message);
    }

    protected function penghasilanOptions(): array
    {
        return [
            '< Rp1.000.000',
            'Rp1.000.000 - Rp2.999.999',
            'Rp3.000.000 - Rp4.999.999',
            'Rp5.000.000 - Rp7.499.999',
            '>= Rp7.500.000',
        ];
    }
}
