<?php

namespace App\Controllers;

use App\Models\PenggunaModel;
use CodeIgniter\HTTP\RedirectResponse;

class ProfilAkunController extends BaseController
{
    protected PenggunaModel $penggunaModel;

    public function __construct()
    {
        $this->penggunaModel = new PenggunaModel();
    }

    public function index(): string|RedirectResponse
    {
        $akun = $this->ambilAkunSaatIni();
        if ($akun === null) {
            return redirect()->to('/login')->with('error', 'Data akun tidak ditemukan. Silakan login kembali.');
        }

        return view('profil_akun/index', [
            'title' => 'Profil Akun - Sistem Tracer Study',
            'akun'  => $akun,
        ]);
    }

    public function update(): RedirectResponse
    {
        $akun = $this->ambilAkunSaatIni();
        if ($akun === null) {
            return redirect()->to('/login')->with('error', 'Data akun tidak ditemukan. Silakan login kembali.');
        }

        $idPengguna = (int) $akun['id_pengguna'];
        $payload = [
            'nama_lengkap'  => trim((string) $this->request->getPost('nama_lengkap')),
            'email'         => strtolower(trim((string) $this->request->getPost('email'))),
            'nomor_telepon' => trim((string) $this->request->getPost('nomor_telepon')),
        ];

        if (! $this->validateData($payload, [
            'nama_lengkap'  => 'required|min_length[3]|max_length[150]',
            'email'         => 'required|valid_email|max_length[150]|is_unique[tb_pengguna.email,id_pengguna,' . $idPengguna . ']',
            'nomor_telepon' => 'permit_empty|min_length[8]|max_length[30]|regex_match[/^[0-9+().\-\s]+$/]',
        ], [
            'nama_lengkap' => [
                'required'   => 'Nama lengkap wajib diisi.',
                'min_length' => 'Nama lengkap minimal 3 karakter.',
                'max_length' => 'Nama lengkap maksimal 150 karakter.',
            ],
            'email' => [
                'required'    => 'Email wajib diisi.',
                'valid_email' => 'Format email tidak valid.',
                'max_length'  => 'Email maksimal 150 karakter.',
                'is_unique'   => 'Email tersebut sudah digunakan akun lain.',
            ],
            'nomor_telepon' => [
                'min_length'  => 'Nomor telepon minimal 8 karakter.',
                'max_length'  => 'Nomor telepon maksimal 30 karakter.',
                'regex_match' => 'Nomor telepon hanya boleh berisi angka dan tanda telepon yang valid.',
            ],
        ])) {
            return redirect()->back()->withInput()
                ->with('errors_profil', $this->validator->getErrors())
                ->with('error', 'Data profil belum valid.');
        }

        $emailBerubah = $payload['email'] !== strtolower((string) $akun['email']);
        if ($emailBerubah) {
            $passwordSaatIni = (string) $this->request->getPost('password_saat_ini');
            if ($passwordSaatIni === '' || ! password_verify($passwordSaatIni, (string) $akun['kata_sandi'])) {
                return redirect()->back()->withInput()
                    ->with('errors_profil', ['password_saat_ini' => 'Password saat ini tidak sesuai.'])
                    ->with('error', 'Email belum dapat diubah.');
            }
        }

        $this->penggunaModel->update($idPengguna, $payload);
        session()->set([
            'nama_lengkap' => $payload['nama_lengkap'],
            'email'        => $payload['email'],
        ]);

        return redirect()->to('/profil-akun')->with('sukses', 'Profil akun berhasil diperbarui.');
    }

    public function updatePassword(): RedirectResponse
    {
        $akun = $this->ambilAkunSaatIni();
        if ($akun === null) {
            return redirect()->to('/login')->with('error', 'Data akun tidak ditemukan. Silakan login kembali.');
        }

        $payload = [
            'password_saat_ini'     => (string) $this->request->getPost('password_saat_ini'),
            'password_baru'         => (string) $this->request->getPost('password_baru'),
            'konfirmasi_password'   => (string) $this->request->getPost('konfirmasi_password'),
        ];

        if (! $this->validateData($payload, [
            'password_saat_ini'   => 'required',
            'password_baru'       => 'required|min_length[8]|max_length[72]',
            'konfirmasi_password' => 'required|matches[password_baru]',
        ], [
            'password_saat_ini' => [
                'required' => 'Password saat ini wajib diisi.',
            ],
            'password_baru' => [
                'required'   => 'Password baru wajib diisi.',
                'min_length' => 'Password baru minimal 8 karakter.',
                'max_length' => 'Password baru maksimal 72 karakter.',
            ],
            'konfirmasi_password' => [
                'required' => 'Konfirmasi password wajib diisi.',
                'matches'  => 'Konfirmasi password tidak sama dengan password baru.',
            ],
        ])) {
            return redirect()->back()
                ->with('errors_password', $this->validator->getErrors())
                ->with('error', 'Password belum dapat diperbarui.');
        }

        if (! password_verify($payload['password_saat_ini'], (string) $akun['kata_sandi'])) {
            return redirect()->back()
                ->with('errors_password', ['password_saat_ini' => 'Password saat ini tidak sesuai.'])
                ->with('error', 'Password belum dapat diperbarui.');
        }

        if (password_verify($payload['password_baru'], (string) $akun['kata_sandi'])) {
            return redirect()->back()
                ->with('errors_password', ['password_baru' => 'Password baru harus berbeda dari password saat ini.'])
                ->with('error', 'Password belum dapat diperbarui.');
        }

        $this->penggunaModel->update((int) $akun['id_pengguna'], [
            'kata_sandi' => password_hash($payload['password_baru'], PASSWORD_DEFAULT),
        ]);

        return redirect()->to('/profil-akun')->with('sukses', 'Password akun berhasil diperbarui.');
    }

    protected function ambilAkunSaatIni(): ?array
    {
        $idPengguna = (int) session()->get('id_pengguna');
        if ($idPengguna <= 0) {
            return null;
        }

        return $this->penggunaModel
            ->select('tb_pengguna.*, tb_peran.nama_peran, tb_peran.slug_peran')
            ->join('tb_peran', 'tb_peran.id_peran = tb_pengguna.id_peran', 'left')
            ->where('tb_pengguna.id_pengguna', $idPengguna)
            ->first();
    }
}
