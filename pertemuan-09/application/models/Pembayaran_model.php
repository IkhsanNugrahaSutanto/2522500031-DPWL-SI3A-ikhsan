<?php
// Model pembayaran DP: dipakai Pembayaran_controller (penyewa)
// dan Verifikasi_controller (admin).
class Pembayaran_model
{
    private mysqli $db;

    public function __construct()
    {
        global $db;
        $this->db = $db;
    }

    /* ------------------------------------------------------------------
     * Utilitas query
     * ---------------------------------------------------------------- */

    private function jalankan(string $sql, string $tipe = '', array $param = []): mysqli_stmt
    {
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            throw new RuntimeException('Query gagal disiapkan.');
        }
        if ($tipe !== '') {
            $stmt->bind_param($tipe, ...$param);
        }
        if (!$stmt->execute()) {
            $stmt->close();
            throw new RuntimeException('Query gagal dijalankan.');
        }
        return $stmt;
    }

    private function ambilSemua(string $sql, string $tipe = '', array $param = []): array
    {
        $stmt = $this->jalankan($sql, $tipe, $param);
        $hasil = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $hasil;
    }

    private function ambilSatu(string $sql, string $tipe = '', array $param = []): ?array
    {
        $baris = $this->ambilSemua($sql, $tipe, $param);
        return $baris[0] ?? null;
    }

    /** Menjalankan UPDATE dan mengembalikan jumlah baris terpengaruh. */
    private function ubah(string $sql, string $tipe, array $param): int
    {
        $stmt = $this->jalankan($sql, $tipe, $param);
        $n = $stmt->affected_rows;
        $stmt->close();
        return $n;
    }

    private const KOLOM = "b.kode_bayar, b.kode_sewa, b.tgl_wkt_byr, b.ft_byr,
        b.mtode_byr, b.jns_byr, b.nominal, b.sttus_byr, b.tgl_wkt_ver,
        s.tgl_mulai, s.tgl_selesai, s.wkt_ambil, s.status_sewa, s.user_plg";

    /* ------------------------------------------------------------------
     * Sisi penyewa
     * ---------------------------------------------------------------- */

    public function getDaftarPenyewa(string $userPlg): array
    {
        return $this->ambilSemua(
            'SELECT ' . self::KOLOM . "
             FROM t_bayar b
             INNER JOIN t_sewa s ON s.kode_sewa = b.kode_sewa
             WHERE s.user_plg = ? AND b.jns_byr = 'dp'
             ORDER BY b.tgl_wkt_byr DESC, b.kode_bayar DESC",
            's',
            [$userPlg]
        );
    }

    /** Hanya mengembalikan data jika pembayaran milik penyewa tersebut. */
    public function getDetailPenyewa(string $kodeBayar, string $userPlg): ?array
    {
        return $this->ambilSatu(
            'SELECT ' . self::KOLOM . "
             FROM t_bayar b
             INNER JOIN t_sewa s ON s.kode_sewa = b.kode_sewa
             WHERE b.kode_bayar = ? AND s.user_plg = ? AND b.jns_byr = 'dp'
             LIMIT 1",
            'ss',
            [$kodeBayar, $userPlg]
        );
    }

    public function getKendaraan(string $kodeSewa): array
    {
        return $this->ambilSemua(
            'SELECT p.no_plat, m.nm_mobil, p.hrg_sewa, p.n_hari
             FROM t_pilih p
             INNER JOIN t_mobil m ON m.no_plat = p.no_plat
             WHERE p.kode_sewa = ?
             ORDER BY p.no_plat ASC',
            's',
            [$kodeSewa]
        );
    }

    /**
     * Menyimpan nama berkas bukti pada t_bayar.
     * Syarat: milik penyewa, DP, status pending, belum diverifikasi,
     * dan transaksi sewa masih 'diajukan'.
     *
     * @return string|null nama berkas lama (untuk dihapus oleh pemanggil)
     * @throws InvalidArgumentException pelanggaran aturan bisnis
     */
    public function simpanBukti(string $kodeBayar, string $userPlg, string $namaBerkas): ?string
    {
        $this->db->begin_transaction();
        try {
            $baris = $this->ambilSatu(
                "SELECT b.ft_byr, b.sttus_byr, b.tgl_wkt_ver, s.status_sewa
                 FROM t_bayar b
                 INNER JOIN t_sewa s ON s.kode_sewa = b.kode_sewa
                 WHERE b.kode_bayar = ? AND s.user_plg = ? AND b.jns_byr = 'dp'
                 LIMIT 1 FOR UPDATE",
                'ss',
                [$kodeBayar, $userPlg]
            );
            if ($baris === null) {
                throw new InvalidArgumentException('Data pembayaran tidak ditemukan.');
            }
            if (
                $baris['sttus_byr'] !== 'pending'
                || $baris['tgl_wkt_ver'] !== null
                || $baris['status_sewa'] !== 'diajukan'
            ) {
                throw new InvalidArgumentException(
                    'Pembayaran ini sudah diproses atau transaksinya tidak lagi menunggu pembayaran, '
                    . 'sehingga bukti tidak dapat diubah.'
                );
            }

            $n = $this->ubah(
                "UPDATE t_bayar
                 SET ft_byr = ?, tgl_wkt_byr = NOW()
                 WHERE kode_bayar = ? AND sttus_byr = 'pending' AND tgl_wkt_ver IS NULL",
                'ss',
                [$namaBerkas, $kodeBayar]
            );
            if ($n !== 1) {
                throw new RuntimeException('Data pembayaran gagal diperbarui.');
            }

            $this->db->commit();
            return $baris['ft_byr'] !== null && $baris['ft_byr'] !== '' ? $baris['ft_byr'] : null;
        } catch (Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /* ------------------------------------------------------------------
     * Sisi admin
     * ---------------------------------------------------------------- */

    /** @param string|null $status pending|valid|invalid, null = semua */
    public function getDaftarAdmin(?string $status = null): array
    {
        $sql = 'SELECT ' . self::KOLOM . ", p.nm_lngkp, p.no_hp
                FROM t_bayar b
                INNER JOIN t_sewa s ON s.kode_sewa = b.kode_sewa
                INNER JOIN t_penyewa p ON p.user_plg = s.user_plg
                WHERE b.jns_byr = 'dp' AND b.mtode_byr = 'transfer'";
        $tipe = '';
        $param = [];
        if (in_array($status, ['pending', 'valid', 'invalid'], true)) {
            $sql .= ' AND b.sttus_byr = ?';
            $tipe = 's';
            $param = [$status];
        }
        $sql .= ' ORDER BY (b.sttus_byr = \'pending\') DESC, b.tgl_wkt_byr DESC, b.kode_bayar DESC';
        return $this->ambilSemua($sql, $tipe, $param);
    }

    public function hitungPerStatus(): array
    {
        $hasil = ['pending' => 0, 'valid' => 0, 'invalid' => 0];
        $baris = $this->ambilSemua(
            "SELECT sttus_byr, COUNT(*) AS jml FROM t_bayar
             WHERE jns_byr = 'dp' AND mtode_byr = 'transfer'
             GROUP BY sttus_byr"
        );
        foreach ($baris as $b) {
            $hasil[$b['sttus_byr']] = (int) $b['jml'];
        }
        return $hasil;
    }

    public function getDetailAdmin(string $kodeBayar): ?array
    {
        return $this->ambilSatu(
            'SELECT ' . self::KOLOM . ", p.nm_lngkp, p.no_hp, p.nik,
                (SELECT COALESCE(SUM(t.hrg_sewa * t.n_hari), 0)
                 FROM t_pilih t WHERE t.kode_sewa = b.kode_sewa) AS total_sewa
             FROM t_bayar b
             INNER JOIN t_sewa s ON s.kode_sewa = b.kode_sewa
             INNER JOIN t_penyewa p ON p.user_plg = s.user_plg
             WHERE b.kode_bayar = ? AND b.jns_byr = 'dp'
             LIMIT 1",
            's',
            [$kodeBayar]
        );
    }

    /**
     * Admin menerima ('valid') atau menolak ('invalid') pembayaran DP.
     * t_bayar dan t_sewa diubah dalam satu transaksi database:
     *   valid   -> t_bayar.sttus_byr = valid,   t_sewa.status_sewa = disetujui
     *   invalid -> t_bayar.sttus_byr = invalid, t_sewa.status_sewa = ditolak
     *
     * @throws InvalidArgumentException pelanggaran aturan bisnis
     */
    public function verifikasi(string $kodeBayar, string $keputusan): void
    {
        if (!in_array($keputusan, ['valid', 'invalid'], true)) {
            throw new InvalidArgumentException('Keputusan verifikasi tidak valid.');
        }
        $statusSewaBaru = $keputusan === 'valid' ? 'disetujui' : 'ditolak';

        $this->db->begin_transaction();
        try {
            $baris = $this->ambilSatu(
                "SELECT b.kode_sewa, b.ft_byr, b.sttus_byr, b.tgl_wkt_ver, s.status_sewa
                 FROM t_bayar b
                 INNER JOIN t_sewa s ON s.kode_sewa = b.kode_sewa
                 WHERE b.kode_bayar = ? AND b.jns_byr = 'dp'
                 LIMIT 1 FOR UPDATE",
                's',
                [$kodeBayar]
            );
            if ($baris === null) {
                throw new InvalidArgumentException('Data pembayaran tidak ditemukan.');
            }
            if ($baris['sttus_byr'] !== 'pending' || $baris['tgl_wkt_ver'] !== null) {
                throw new InvalidArgumentException('Pembayaran ini sudah diverifikasi sebelumnya.');
            }
            if ($baris['status_sewa'] !== 'diajukan') {
                throw new InvalidArgumentException(
                    'Status transaksi sewa bukan "diajukan", sehingga pembayaran tidak dapat diverifikasi.'
                );
            }
            if ($keputusan === 'valid' && trim((string) $baris['ft_byr']) === '') {
                throw new InvalidArgumentException(
                    'Penyewa belum mengunggah bukti pembayaran, sehingga pembayaran belum dapat diterima.'
                );
            }

            $n = $this->ubah(
                "UPDATE t_bayar
                 SET sttus_byr = ?, tgl_wkt_ver = NOW()
                 WHERE kode_bayar = ? AND sttus_byr = 'pending'",
                'ss',
                [$keputusan, $kodeBayar]
            );
            if ($n !== 1) {
                throw new RuntimeException('Status pembayaran gagal diperbarui.');
            }

            $n = $this->ubah(
                "UPDATE t_sewa
                 SET status_sewa = ?
                 WHERE kode_sewa = ? AND status_sewa = 'diajukan'",
                'ss',
                [$statusSewaBaru, $baris['kode_sewa']]
            );
            if ($n !== 1) {
                throw new RuntimeException('Status transaksi sewa gagal diperbarui.');
            }

            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }
}
