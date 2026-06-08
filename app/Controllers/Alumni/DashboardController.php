<?php

namespace App\Controllers\Alumni;

use App\Controllers\BaseController;
use App\Models\AlumniModel;
use App\Models\PenggunaModel;
use App\Models\TracerAlumniModel;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;

class DashboardController extends BaseController
{
    protected AlumniModel $alumniModel;
    protected PenggunaModel $penggunaModel;
    protected TracerAlumniModel $tracerModel;
    protected $db;

    public function __construct()
    {
        $this->alumniModel = new AlumniModel();
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
            'onboarding' => $onboarding,
        ]);
    }

    public function profil(): string|RedirectResponse
    {
        return $this->index();
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

        return $this->responseAlumni('success', 'Profil alumni berhasil diperbarui.');
    }

    public function updateEmail(): ResponseInterface|RedirectResponse
    {
        $alumni = $this->ambilAlumniLogin();
        if ($alumni === null) {
            return $this->responseAlumni('error', 'Profil alumni belum ditemukan.', 404);
        }

        $email = strtolower(trim((string) $this->request->getPost('email')));
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->responseAlumni('error', 'Email belum valid.', 422);
        }

        $duplikat = $this->db->table('tb_pengguna')
            ->where('email', $email)
            ->where('id_pengguna !=', (int) $alumni['id_pengguna'])
            ->countAllResults();

        if ($duplikat > 0) {
            return $this->responseAlumni('error', 'Email sudah digunakan akun lain.', 422);
        }

        $this->penggunaModel->update((int) $alumni['id_pengguna'], ['email' => $email]);
        session()->set('email', $email);

        return $this->responseAlumni('success', 'Email berhasil diperbarui.');
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
            return $this->responseAlumni('error', 'Password minimal 8 karakter dan konfirmasi harus sama.', 422);
        }

        $this->penggunaModel->update((int) $alumni['id_pengguna'], [
            'kata_sandi' => password_hash($password, PASSWORD_DEFAULT),
        ]);

        return $this->responseAlumni('success', 'Password berhasil diperbarui.');
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

        $existing = $this->tracerModel->where('id_alumni', (int) $alumni['id_alumni'])->first();
        if ($existing !== null) {
            $this->tracerModel->update((int) $existing['id_tracer'], $payload);
        } else {
            $this->tracerModel->insert($payload);
        }

        return $this->responseAlumni('success', 'Data tracer study berhasil disimpan.');
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
                'title' => 'Isi tracer study',
                'description' => 'Kirim aktivitas setelah lulus untuk rekap tracer sekolah.',
                'done' => $tracer !== null,
                'url' => site_url('alumni/profil'),
                'button' => 'Isi Tracer',
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

    protected function responseAlumni(string $status, string $message, int $httpStatus = 200): ResponseInterface|RedirectResponse
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

        return redirect()->to(site_url('alumni/dashboard'))->with($status === 'success' ? 'sukses' : 'error', $message);
    }
}
