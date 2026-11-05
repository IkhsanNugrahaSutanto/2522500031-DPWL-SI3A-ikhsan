<?php
class Mpenyewa_controller extends Controller
{
    private Mpenyewa_model $model;
    public function __construct()
    {
        require_once APPPATH.'models/Mpenyewa_model.php';
        require_once APPPATH.'helpers/auth_helper.php';
        require_once APPPATH.'helpers/flash_helper.php';
        $this->model = new Mpenyewa_model();
    }
    public function index(): void
    {
        require_admin_login();
        $this->render(null);
    }
    private function render(?string $mode, ?array $selected=null, ?string $error=null): void
    {
        $this->view('admin/penyewa_view', [
            'username'=>$_SESSION['admin_username'],
            'penyewa'=>$this->model->getAll(),
            'flash'=>$mode === null ? get_flash_message() : null,
            'mode'=>$mode, 'penyewa_dipilih'=>$selected, 'error'=>$error
        ]);
    }
    private function input(): array
    {
        return [
            'user_plg'=>trim((string)($_POST['user_plg']??'')),
            'nik'=>trim((string)($_POST['nik']??'')),
            'nm_lngkp'=>trim((string)($_POST['nm_lngkp']??'')),
            'no_hp'=>trim((string)($_POST['no_hp']??'')),
            'almt_lngkp'=>trim((string)($_POST['almt_lngkp']??'')),
            'ft_ktp'=>null, 'ft_sim_a'=>null
        ];
    }
    private function validate(array $d, string $password, bool $edit): ?string
    {
        if (!preg_match('/\A[A-Za-z0-9._-]{1,15}\z/',$d['user_plg'])) return 'Username wajib diisi, maksimal 15 karakter dan hanya boleh berisi huruf, angka, titik, garis bawah, atau tanda hubung.';
        if (!$edit && ($password==='' || strlen($password)<8 || strlen($password)>72)) return 'Password wajib diisi dengan panjang 8 sampai 72 byte.';
        if ($edit && $password!=='' && (strlen($password)<8 || strlen($password)>72)) return 'Password baru harus 8 sampai 72 byte.';
        if (!preg_match('/\A[0-9]{16}\z/',$d['nik'])) return 'NIK harus tepat 16 digit angka.';
        if ($d['nm_lngkp']==='' || mb_strlen($d['nm_lngkp'])>100 || !preg_match("/\A[\p{L}\p{M}]+(?:[ .'-][\p{L}\p{M}]+)*\z/u",$d['nm_lngkp'])) return 'Nama lengkap tidak valid (maksimal 100 karakter).';
        if ($d['no_hp']==='' || strlen($d['no_hp'])>15 || !preg_match('/\A[0-9+(). -]+\z/',$d['no_hp']) || !preg_match('/[0-9]/',$d['no_hp'])) return 'Nomor HP tidak valid (maksimal 15 karakter).';
        if ($d['almt_lngkp']==='' || mb_strlen($d['almt_lngkp'])>300 || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/',$d['almt_lngkp'])) return 'Alamat wajib diisi dan maksimal 300 karakter.';
        return null;
    }
    private function upload(string $field): array
    {
        if (!isset($_FILES[$field]) || ($_FILES[$field]['error']??UPLOAD_ERR_NO_FILE)===UPLOAD_ERR_NO_FILE) return ['ok'=>true,'name'=>null,'error'=>null];
        $f=$_FILES[$field];
        if (($f['error']??-1)!==UPLOAD_ERR_OK || !isset($f['tmp_name'],$f['size']) || !is_uploaded_file($f['tmp_name']) || (int)$f['size']<1 || (int)$f['size']>5*1024*1024) return ['ok'=>false,'name'=>null,'error'=>'Unggah foto gagal. Pastikan ukuran file maksimal 5 MB.'];
        $mime=(new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
        $types=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
        if (!isset($types[$mime]) || @getimagesize($f['tmp_name'])===false) return ['ok'=>false,'name'=>null,'error'=>'Foto harus berupa JPEG, PNG, atau WEBP yang valid.'];
        $dir=dirname(APPPATH).DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.'penyewa'.DIRECTORY_SEPARATOR;
        if (!is_dir($dir) && !mkdir($dir,0755,true) && !is_dir($dir)) return ['ok'=>false,'name'=>null,'error'=>'Folder upload tidak dapat dibuat.'];
        if (!is_writable($dir)) return ['ok'=>false,'name'=>null,'error'=>'Folder upload tidak dapat ditulis.'];
        $name=$field.'_'.date('YmdHis').'_'.bin2hex(random_bytes(8)).'.'.$types[$mime];
        if (!move_uploaded_file($f['tmp_name'],$dir.$name)) return ['ok'=>false,'name'=>null,'error'=>'File foto gagal disimpan.'];
        return ['ok'=>true,'name'=>$name,'error'=>null];
    }
    private function deletePhotos(array $names): void
    {
        $dir=dirname(APPPATH).DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.'penyewa'.DIRECTORY_SEPARATOR;
        foreach ($names as $name) {
            if (is_string($name) && preg_match('/^ft_(ktp|sim_a)_\d{14}_[a-f0-9]{16}\.(jpg|png|webp)$/',$name) && is_file($dir.$name)) @unlink($dir.$name);
        }
    }
    private function saveUploads(array &$d, ?array $old=null): array
    {
        $new=[]; $oldToDelete=[];
        foreach (['ft_ktp','ft_sim_a'] as $field) {
            $u=$this->upload($field);
            if (!$u['ok']) { $this->deletePhotos($new); return ['ok'=>false,'error'=>$u['error']]; }
            if ($u['name']!==null) {
                $new[]=$u['name']; $d[$field]=$u['name'];
                if (!empty($old[$field])) $oldToDelete[]=$old[$field];
            } else $d[$field]=$old[$field]??null;
        }
        return ['ok'=>true,'new'=>$new,'old'=>$oldToDelete];
    }
    public function tambah(): void
    {
        require_admin_login();
        if ($_SERVER['REQUEST_METHOD']==='GET') { $this->render('tambah'); return; }
        if ($_SERVER['REQUEST_METHOD']!=='POST') { http_response_code(405); header('Allow: GET, POST'); exit('Method Not Allowed'); }
        $d=$this->input(); $pw=(string)($_POST['password']??'');
        $err=$this->validate($d,$pw,false);
        if ($err!==null) { $this->render('tambah',$d,$err); return; }
        if ($this->model->find($d['user_plg'])!==null) { $this->render('tambah',$d,'Username sudah digunakan.'); return; }
        $uploads=$this->saveUploads($d);
        if (!$uploads['ok']) { $this->render('tambah',$d,$uploads['error']); return; }
        $hash=password_hash($pw,PASSWORD_DEFAULT);
        try { $ok=$hash!==false && $this->model->create(array_merge($d,['sandi'=>$hash])); } catch(Throwable $e) { error_log($e->getMessage()); $ok=false; }
        if (!$ok) { $this->deletePhotos($uploads['new']); $this->render('tambah',$d,'Data penyewa gagal disimpan. Pastikan username belum digunakan.'); return; }
        set_flash_message('success','Data penyewa berhasil ditambahkan.');
        header('Location: '.site_url('admin/penyewa')); exit;
    }
    public function ubah(string $username): void
    {
        require_admin_login(); $username=rawurldecode($username); $old=$this->model->find($username);
        if (!$old) { set_flash_message('danger','Data penyewa tidak ditemukan.'); header('Location: '.site_url('admin/penyewa')); exit; }
        if ($_SERVER['REQUEST_METHOD']==='GET') { $this->render('ubah',$old); return; }
        if ($_SERVER['REQUEST_METHOD']!=='POST') { http_response_code(405); header('Allow: GET, POST'); exit('Method Not Allowed'); }
        $d=$this->input(); $d['user_plg']=$username; $pw=(string)($_POST['password']??'');
        $err=$this->validate($d,$pw,true);
        if ($err!==null) { $this->render('ubah',array_merge($old,$d),$err); return; }
        $uploads=$this->saveUploads($d,$old);
        if (!$uploads['ok']) { $this->render('ubah',array_merge($old,$d),$uploads['error']); return; }
        $hash=$pw===''?null:password_hash($pw,PASSWORD_DEFAULT);
        try { $ok=$this->model->update($username,$d,$hash); } catch(Throwable $e) { error_log($e->getMessage()); $ok=false; }
        if (!$ok) { $this->deletePhotos($uploads['new']); $this->render('ubah',array_merge($old,$d),'Data penyewa gagal diperbarui.'); return; }
        $this->deletePhotos($uploads['old']);
        set_flash_message('success','Data penyewa berhasil diperbarui.');
        header('Location: '.site_url('admin/penyewa')); exit;
    }
    public function hapus(string $username): void
    {
        require_admin_login();
        if ($_SERVER['REQUEST_METHOD']!=='POST') { http_response_code(405); header('Allow: POST'); exit('Method Not Allowed'); }
        $username=rawurldecode($username); $old=$this->model->find($username);
        if (!$old) { set_flash_message('danger','Data penyewa tidak ditemukan.'); header('Location: '.site_url('admin/penyewa')); exit; }
        try { $ok=$this->model->delete($username); } catch(Throwable $e) { error_log($e->getMessage()); $ok=false; }
        if (!$ok) { set_flash_message('danger','Data penyewa gagal dihapus. Periksa apakah data masih dipakai transaksi.'); header('Location: '.site_url('admin/penyewa')); exit; }
        $this->deletePhotos([$old['ft_ktp']??null,$old['ft_sim_a']??null]);
        set_flash_message('success','Data penyewa berhasil dihapus.');
        header('Location: '.site_url('admin/penyewa')); exit;
    }
}
