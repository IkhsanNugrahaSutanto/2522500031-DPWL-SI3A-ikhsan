<?php
require APPPATH.'/views/templates/header.php';
require APPPATH.'/views/templates/sidebar.php';
$penyewa=$penyewa??[]; $mode=$mode??null; $selected=$penyewa_dipilih??[];
$isForm=in_array($mode,['tambah','ubah'],true); $edit=$mode==='ubah';
$e=static fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');
$action=$edit?site_url('admin/penyewa/ubah/'.rawurlencode((string)($selected['user_plg']??''))):site_url('admin/penyewa/tambah');
?>
<div class="content-wrapper">
<section class="content-header"><h1>Manajemen Penyewa <small>Kelola seluruh data penyewa</small></h1><ol class="breadcrumb"><li><a href="<?= $e(site_url('admin')) ?>"><i class="fa fa-dashboard"></i> Dashboard</a></li><li class="active">Manajemen Penyewa</li></ol></section>
<section class="content">
<?php if(!empty($flash)): ?><div class="alert <?= ($flash['type']??'')==='success'?'alert-success':'alert-danger' ?> alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><?= $e($flash['message']??'') ?></div><?php endif; ?>
<div class="box"><div class="box-header"><h3 class="box-title">Data Penyewa</h3><div class="box-tools"><a class="btn btn-primary btn-sm" href="<?= $e(site_url('admin/penyewa/tambah')) ?>"><i class="fa fa-plus"></i> Tambah Penyewa</a></div></div>
<div class="box-body table-responsive"><table class="table table-bordered table-striped js-data-table"><thead><tr><th>No</th><th>Username</th><th>NIK</th><th>Nama Lengkap</th><th>No. HP</th><th>Alamat</th><th>Foto KTP</th><th>Foto SIM A</th><th>Aksi</th></tr></thead><tbody>
<?php foreach($penyewa as $i=>$p): ?><tr>
<td><?= (int)$i+1 ?></td><td><?= $e($p['user_plg']) ?></td><td><?= $e($p['nik']) ?></td><td><?= $e($p['nm_lngkp']) ?></td><td><?= $e($p['no_hp']) ?></td><td><?= $e($p['almt_lngkp']) ?></td>
<?php foreach(['ft_ktp'=>'KTP','ft_sim_a'=>'SIM A'] as $field=>$label): ?><td><?php if(!empty($p[$field])): ?><a target="_blank" rel="noopener" href="<?= $e(base_url('uploads/penyewa/'.rawurlencode($p[$field]))) ?>"><img src="<?= $e(base_url('uploads/penyewa/'.rawurlencode($p[$field]))) ?>" alt="<?= $e($label) ?>" style="width:75px;height:50px;object-fit:cover"></a><?php else: ?><span class="text-muted">Belum ada foto</span><?php endif; ?></td><?php endforeach; ?>
<td><a class="btn btn-warning btn-xs" href="<?= $e(site_url('admin/penyewa/ubah/'.rawurlencode($p['user_plg']))) ?>"><i class="fa fa-edit"></i> Ubah</a>
<form method="post" style="display:inline" action="<?= $e(site_url('admin/penyewa/hapus/'.rawurlencode($p['user_plg']))) ?>" onsubmit="return confirm('Hapus data penyewa ini?');"><button class="btn btn-danger btn-xs" type="submit"><i class="fa fa-trash"></i> Hapus</button></form></td></tr><?php endforeach; ?>
</tbody></table></div></div>
<?php if($error!==null): ?><div class="alert alert-danger"><?= $e($error) ?></div><?php endif; ?>
<?php if($isForm): ?><div class="box box-primary"><div class="box-header with-border"><h3 class="box-title"><?= $edit?'Ubah Data Penyewa':'Tambah Penyewa' ?></h3></div>
<form method="post" enctype="multipart/form-data" action="<?= $e($action) ?>"><div class="box-body">
<div class="form-group"><label>Username</label><input class="form-control" name="user_plg" maxlength="15" pattern="[A-Za-z0-9._-]{1,15}" required value="<?= $e($selected['user_plg']??'') ?>" <?= $edit?'readonly':'' ?>></div>
<div class="form-group"><label><?= $edit?'Password Baru (kosongkan jika tidak diubah)':'Password' ?></label><input class="form-control" type="password" name="password" minlength="8" maxlength="72" <?= $edit?'':'required' ?> autocomplete="new-password"></div>
<div class="form-group"><label>NIK</label><input class="form-control" name="nik" minlength="16" maxlength="16" pattern="[0-9]{16}" inputmode="numeric" required value="<?= $e($selected['nik']??'') ?>"></div>
<div class="form-group"><label>Nama Lengkap</label><input class="form-control" name="nm_lngkp" maxlength="100" required value="<?= $e($selected['nm_lngkp']??'') ?>"></div>
<div class="form-group"><label>Nomor HP</label><input class="form-control" name="no_hp" maxlength="15" required value="<?= $e($selected['no_hp']??'') ?>"></div>
<div class="form-group"><label>Alamat Lengkap</label><textarea class="form-control" name="almt_lngkp" maxlength="300" rows="3" required><?= $e($selected['almt_lngkp']??'') ?></textarea></div>
<?php foreach(['ft_ktp'=>'Foto KTP','ft_sim_a'=>'Foto SIM A'] as $field=>$label): ?><div class="form-group"><label><?= $e($label) ?></label><input class="form-control" type="file" name="<?= $e($field) ?>" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"><p class="help-block">JPEG, PNG, WEBP; maksimal 5 MB. Kosongkan saat edit untuk mempertahankan foto lama.</p><?php if(!empty($selected[$field])): ?><img src="<?= $e(base_url('uploads/penyewa/'.rawurlencode($selected[$field]))) ?>" style="width:150px;height:100px;object-fit:cover" alt="<?= $e($label) ?> saat ini"><?php endif; ?></div><?php endforeach; ?>
</div><div class="box-footer"><button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Simpan</button> <a href="<?= $e(site_url('admin/penyewa')) ?>" class="btn btn-default">Batal</a></div></form></div><?php endif; ?>
</section></div>
<?php require APPPATH.'/views/templates/footer.php'; ?>
