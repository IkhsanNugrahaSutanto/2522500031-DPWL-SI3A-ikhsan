<?php
// Controller sisi admin: master verifikasi pembayaran DP penyewa.
// Hak akses: hanya admin yang login (require_admin_login).
class Verifikasi_controller extends Controller
{
    private Pembayaran_model $model;
    private const SESI_TOKEN = 'csrf_verifikasi';

    public function __construct()
    {
        require_once APPPATH . 'models/Pembayaran_model.php';
        require_once APPPATH . 'helpers/auth_helper.php';
        require_once APPPATH . 'helpers/flash_helper.php';
        require_once APPPATH . 'helpers/bukti_helper.php';
        $this->model = new Pembayaran_model();
    }

    /* GET admin/verifikasi[?status=pending|valid|invalid] */
    public function index(): void
    {
        require_admin_login();
        $this->wajibMetode(['GET']);

        $status = (string) ($_GET['status'] ?? '');
        if (!in_array($status, ['pending', 'valid', 'invalid'], true)) {
            $status = '';
        }

        $this->view('admin/verifikasi_view', [
            'username' => $_SESSION['admin_username'],
            'menu_aktif' => 'verifikasi',
            'daftar' => $this->model->getDaftarAdmin($status === '' ? null : $status),
            'jumlah' => $this->model->hitungPerStatus(),
            'filter' => $status,
            'flash' => get_flash_message(),
        ]);
    }

    /* GET admin/verifikasi/detail/{kode~bayar} */
    public function detail(string $kode = ''): void
    {
        require_admin_login();
        $this->wajibMetode(['GET']);

        $kodeBayar = $this->kodeDariUrl($kode);
        $bayar = $kodeBayar === '' ? null : $this->model->getDetailAdmin($kodeBayar);
        if ($bayar === null) {
            set_flash_message('danger', 'Data pembayaran tidak ditemukan.');
            header('Location: ' . site_url('admin/verifikasi'));
            exit;
        }

        $this->view('admin/verifikasi_detail', [
            'username' => $_SESSION['admin_username'],
            'menu_aktif' => 'verifikasi',
            'bayar' => $bayar,
            'kendaraan' => $this->model->getKendaraan($bayar['kode_sewa']),
            'buktiAda' => bukti_bayar_ada($bayar['ft_byr']),
            'flash' => get_flash_message(),
            'token' => $this->buatToken(),
        ]);
    }

    /* GET admin/verifikasi/bukti/{kode~bayar} : gambar bukti untuk diperiksa admin */
    public function bukti(string $kode = ''): void
    {
        require_admin_login();
        $this->wajibMetode(['GET']);

        $kodeBayar = $this->kodeDariUrl($kode);
        $bayar = $kodeBayar === '' ? null : $this->model->getDetailAdmin($kodeBayar);
        if ($bayar === null) {
            http_response_code(404);
            exit('Bukti pembayaran tidak ditemukan.');
        }
        kirim_bukti_bayar($bayar['ft_byr']);
    }

    /* POST admin/verifikasi/terima/{kode~bayar} */
    public function terima(string $kode = ''): void
    {
        $this->proses($kode, 'valid');
    }

    /* POST admin/verifikasi/tolak/{kode~bayar} */
    public function tolak(string $kode = ''): void
    {
        $this->proses($kode, 'invalid');
    }

    private function proses(string $kode, string $keputusan): void
    {
        require_admin_login();
        $this->wajibMetode(['POST']);

        $kodeBayar = $this->kodeDariUrl($kode);
        $kembali = site_url('admin/verifikasi/detail/' . rawurlencode(str_replace('/', '~', $kodeBayar)));

        if (!$this->tokenValid((string) ($_POST['csrf_token'] ?? ''))) {
            set_flash_message('danger', 'Sesi tidak valid. Muat ulang halaman lalu coba lagi.');
            header('Location: ' . $kembali);
            exit;
        }

        try {
            $this->model->verifikasi($kodeBayar, $keputusan);
            unset($_SESSION[self::SESI_TOKEN]); // token sekali pakai
            if ($keputusan === 'valid') {
                set_flash_message(
                    'success',
                    'Pembayaran ' . $kodeBayar . ' diterima. Status pembayaran menjadi valid '
                    . 'dan transaksi sewa menjadi disetujui.'
                );
            } else {
                set_flash_message(
                    'success',
                    'Pembayaran ' . $kodeBayar . ' ditolak. Status pembayaran menjadi invalid '
                    . 'dan transaksi sewa menjadi ditolak.'
                );
            }
        } catch (InvalidArgumentException $e) {
            set_flash_message('danger', $e->getMessage());
        } catch (Throwable $e) {
            error_log('Verifikasi pembayaran gagal: ' . $e->getMessage());
            set_flash_message('danger', 'Verifikasi gagal. Tidak ada data yang berubah.');
        }

        header('Location: ' . $kembali);
        exit;
    }

    /* ----- Pendukung ----- */

    private function wajibMetode(array $diizinkan): void
    {
        $metode = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        if (!in_array($metode, $diizinkan, true)) {
            http_response_code(405);
            header('Allow: ' . implode(', ', $diizinkan));
            exit('Metode request tidak diizinkan.');
        }
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
