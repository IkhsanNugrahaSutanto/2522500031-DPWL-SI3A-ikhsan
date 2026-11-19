<?php
// Controller sisi penyewa: melihat info pembayaran DP dan mengunggah bukti.
// Hak akses: hanya penyewa yang login, dan hanya pembayaran miliknya sendiri.
class Pembayaran_controller extends Controller
{
    private Pembayaran_model $model;
    private const SESI_TOKEN = 'csrf_pembayaran';

    public function __construct()
    {
        require_once APPPATH . 'models/Pembayaran_model.php';
        require_once APPPATH . 'models/Transaksi_model.php';
        require_once APPPATH . 'helpers/flash_helper.php';
        require_once APPPATH . 'helpers/bukti_helper.php';
        $this->model = new Pembayaran_model();
    }

    /* GET pembayaran : daftar pembayaran DP milik penyewa */
    public function index(): void
    {
        $userPlg = $this->wajibLoginPenyewa();
        $this->wajibMetode(['GET']);

        $this->view('penyewa/pembayaran', [
            'daftarPembayaran' => $this->model->getDaftarPenyewa($userPlg),
            'flash' => get_flash_message(),
            'jumlahKeranjang' => $this->jumlahKeranjang($userPlg),
        ]);
    }

    /* GET pembayaran/detail/{kode~bayar} : informasi DP + form unggah */
    public function detail(string $kode = ''): void
    {
        $userPlg = $this->wajibLoginPenyewa();
        $this->wajibMetode(['GET']);

        $kodeBayar = $this->kodeDariUrl($kode);
        $pembayaran = $kodeBayar === '' ? null : $this->model->getDetailPenyewa($kodeBayar, $userPlg);
        if ($pembayaran === null) {
            set_flash_message('danger', 'Data pembayaran tidak ditemukan.');
            header('Location: ' . site_url('pembayaran'));
            exit;
        }

        $this->view('penyewa/pembayaran_detail', [
            'pembayaran' => $pembayaran,
            'daftarKendaraan' => $this->model->getKendaraan($pembayaran['kode_sewa']),
            'flash' => get_flash_message(),
            'token' => $this->buatToken(),
            'jumlahKeranjang' => $this->jumlahKeranjang($userPlg),
        ]);
    }

    /* POST pembayaran/upload/{kode~bayar} : simpan bukti pembayaran */
    public function upload(string $kode = ''): void
    {
        $userPlg = $this->wajibLoginPenyewa();
        $this->wajibMetode(['POST']);

        $kodeBayar = $this->kodeDariUrl($kode);
        $kembali = site_url('pembayaran/detail/' . $this->kodeKeUrl($kodeBayar));

        if (!$this->tokenValid((string) ($_POST['csrf_token'] ?? ''))) {
            set_flash_message('danger', 'Sesi formulir tidak valid. Muat ulang halaman lalu coba lagi.');
            header('Location: ' . $kembali);
            exit;
        }

        // Cek kepemilikan dan status sebelum menyimpan berkas apa pun.
        $pembayaran = $kodeBayar === '' ? null : $this->model->getDetailPenyewa($kodeBayar, $userPlg);
        if ($pembayaran === null) {
            set_flash_message('danger', 'Data pembayaran tidak ditemukan.');
            header('Location: ' . site_url('pembayaran'));
            exit;
        }
        if (
            $pembayaran['sttus_byr'] !== 'pending'
            || $pembayaran['tgl_wkt_ver'] !== null
            || $pembayaran['status_sewa'] !== 'diajukan'
        ) {
            set_flash_message('danger', 'Pembayaran ini sudah diproses. Bukti tidak dapat diubah.');
            header('Location: ' . $kembali);
            exit;
        }

        // 1) simpan berkas ke direktori bukti pembayaran
        $unggah = simpan_bukti_bayar('ft_byr');
        if (!$unggah['success']) {
            set_flash_message('danger', (string) $unggah['error']);
            header('Location: ' . $kembali);
            exit;
        }
        $namaBaru = (string) $unggah['filename'];

        // 2) perbarui data pembayaran; bila gagal, berkas baru dibuang
        try {
            $namaLama = $this->model->simpanBukti($kodeBayar, $userPlg, $namaBaru);
        } catch (InvalidArgumentException $e) {
            hapus_bukti_bayar($namaBaru);
            set_flash_message('danger', $e->getMessage());
            header('Location: ' . $kembali);
            exit;
        } catch (Throwable $e) {
            hapus_bukti_bayar($namaBaru);
            error_log('Unggah bukti pembayaran gagal: ' . $e->getMessage());
            set_flash_message('danger', 'Bukti pembayaran gagal disimpan. Silakan coba lagi.');
            header('Location: ' . $kembali);
            exit;
        }

        // 3) berkas lama dihapus hanya setelah database berhasil diperbarui
        if ($namaLama !== null && $namaLama !== $namaBaru) {
            hapus_bukti_bayar($namaLama);
        }

        unset($_SESSION[self::SESI_TOKEN]);
        set_flash_message('success', 'Bukti pembayaran berhasil diunggah. Menunggu verifikasi admin.');
        header('Location: ' . $kembali);
        exit;
    }

    /* GET pembayaran/bukti/{kode~bayar} : menampilkan gambar bukti (hanya pemilik) */
    public function bukti(string $kode = ''): void
    {
        $userPlg = $this->wajibLoginPenyewa();
        $this->wajibMetode(['GET']);

        $kodeBayar = $this->kodeDariUrl($kode);
        $pembayaran = $kodeBayar === '' ? null : $this->model->getDetailPenyewa($kodeBayar, $userPlg);
        if ($pembayaran === null) {
            http_response_code(404);
            exit('Bukti pembayaran tidak ditemukan.');
        }
        kirim_bukti_bayar($pembayaran['ft_byr']);
    }

    /* ----- Pendukung ----- */

    private function wajibLoginPenyewa(): string
    {
        $userPlg = trim((string) ($_SESSION['penyewa_user_plg'] ?? ''));
        if ($userPlg === '') {
            $_SESSION['flash_penyewa_nav'] = 'Silakan login terlebih dahulu untuk melihat pembayaran.';
            header('Location: ' . site_url('auth/login-penyewa'));
            exit;
        }
        return $userPlg;
    }

    private function jumlahKeranjang(string $userPlg): int
    {
        try {
            return (new Transaksi_model())->getJumlahMobilKeranjang($userPlg);
        } catch (Throwable $e) {
            return 0;
        }
    }

    private function wajibMetode(array $diizinkan): void
    {
        $metode = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        if (!in_array($metode, $diizinkan, true)) {
            http_response_code(405);
            header('Allow: ' . implode(', ', $diizinkan));
            exit('Metode request tidak diizinkan.');
        }
    }

    private function kodeKeUrl(string $kode): string
    {
        return rawurlencode(str_replace('/', '~', $kode));
    }

    private function kodeDariUrl(string $kode): string
    {
        return trim(str_replace('~', '/', $kode));
    }

    private function buatToken(): string
    {
        if (empty($_SESSION[self::SESI_TOKEN])) {
            $_SESSION[self::SESI_TOKEN] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::SESI_TOKEN];
    }

    private function tokenValid(string $token): bool
    {
        $sesi = (string) ($_SESSION[self::SESI_TOKEN] ?? '');
        return $sesi !== '' && $token !== '' && hash_equals($sesi, $token);
    }
}
