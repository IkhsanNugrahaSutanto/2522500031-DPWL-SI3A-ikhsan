<?php
// Helper bukti pembayaran: penyimpanan, validasi, penyajian, dan penghapusan berkas.
// Direktori penyimpanan ditentukan pada config.php: $config['upload_bukti_bayar'].

const BUKTI_BAYAR_MAKS_BYTE = 5 * 1024 * 1024; // 5 MB

function bukti_bayar_dir(): string
{
    global $config;
    $relatif = trim((string) ($config['upload_bukti_bayar'] ?? 'uploads/bayar/'), '/\\');
    return FCPATH . str_replace('/', DIRECTORY_SEPARATOR, $relatif) . DIRECTORY_SEPARATOR;
}

function bukti_bayar_nama_valid(string $nama): bool
{
    return preg_match('/\Aft_byr_\d{10}_[a-f0-9]{8,32}\.(jpg|jpeg|png|webp)\z/', $nama) === 1;
}

/**
 * Memvalidasi dan memindahkan berkas unggahan ke direktori bukti pembayaran.
 * @return array{success:bool, filename:?string, error:?string}
 */
function simpan_bukti_bayar(string $field = 'ft_byr'): array
{
    $gagal = static fn(string $pesan): array => [
        'success' => false, 'filename' => null, 'error' => $pesan,
    ];

    if (
        !isset($_FILES[$field])
        || !is_array($_FILES[$field])
        || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE
    ) {
        return $gagal('Pilih berkas bukti pembayaran terlebih dahulu.');
    }

    $file = $_FILES[$field];
    $kodeError = $file['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($kodeError === UPLOAD_ERR_INI_SIZE || $kodeError === UPLOAD_ERR_FORM_SIZE) {
        return $gagal('Ukuran berkas melebihi batas. Maksimal 5 MB.');
    }
    if ($kodeError !== UPLOAD_ERR_OK) {
        return $gagal('Unggah berkas gagal. Silakan coba lagi.');
    }

    $ukuran = (int) ($file['size'] ?? 0);
    if (
        !isset($file['tmp_name'])
        || !is_uploaded_file($file['tmp_name'])
        || $ukuran < 1
        || $ukuran > BUKTI_BAYAR_MAKS_BYTE
    ) {
        return $gagal('Berkas harus berukuran lebih dari 0 byte dan maksimal 5 MB.');
    }

    $diizinkan = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!isset($diizinkan[$mime]) || @getimagesize($file['tmp_name']) === false) {
        return $gagal('Format berkas harus gambar JPEG, PNG, atau WEBP yang valid.');
    }

    $dir = bukti_bayar_dir();
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        return $gagal('Folder penyimpanan bukti pembayaran tidak dapat dibuat.');
    }
    if (!is_writable($dir)) {
        return $gagal('Folder penyimpanan bukti pembayaran tidak dapat ditulis.');
    }

    try {
        $nama = 'ft_byr_' . time() . '_' . bin2hex(random_bytes(7)) . '.' . $diizinkan[$mime];
    } catch (Throwable $e) {
        return $gagal('Nama berkas gagal dibuat.');
    }

    if (!move_uploaded_file($file['tmp_name'], $dir . $nama)) {
        return $gagal('Berkas gagal dipindahkan ke folder penyimpanan.');
    }

    return ['success' => true, 'filename' => $nama, 'error' => null];
}

function hapus_bukti_bayar(?string $nama): void
{
    if ($nama === null || $nama === '' || !bukti_bayar_nama_valid($nama)) {
        return;
    }
    $path = bukti_bayar_dir() . $nama;
    if (is_file($path)) {
        @unlink($path);
    }
}

function bukti_bayar_ada(?string $nama): bool
{
    return $nama !== null && $nama !== ''
        && bukti_bayar_nama_valid($nama)
        && is_file(bukti_bayar_dir() . $nama);
}

/** Mengirim gambar bukti ke browser. Hak akses harus sudah dicek oleh controller. */
function kirim_bukti_bayar(?string $nama): void
{
    if (!bukti_bayar_ada($nama)) {
        http_response_code(404);
        exit('Bukti pembayaran tidak ditemukan.');
    }
    $path = bukti_bayar_dir() . $nama;
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($path);
    if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
        http_response_code(404);
        exit('Bukti pembayaran tidak ditemukan.');
    }
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($path));
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: private, no-store');
    readfile($path);
    exit;
}
