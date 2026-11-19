<?php
require APPPATH . 'views/templates/header.php';
require APPPATH . 'views/templates/sidebar.php';

$daftar = $daftar ?? [];
$jumlah = $jumlah ?? ['pending' => 0, 'valid' => 0, 'invalid' => 0];
$filter = $filter ?? '';
$flash = $flash ?? null;
$e = static fn($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$rp = static fn($n): string => 'Rp ' . number_format((int) $n, 0, ',', '.');
$labelStatus = [
    'pending' => ['label-warning', 'Menunggu verifikasi'],
    'valid' => ['label-success', 'Valid'],
    'invalid' => ['label-danger', 'Ditolak'],
];
$labelSewa = [
    'diajukan' => 'label-warning', 'disetujui' => 'label-info', 'ditolak' => 'label-danger',
    'aktif' => 'label-primary', 'selesai' => 'label-success', 'batal' => 'label-default',
];
$tab = [
    '' => ['Semua', array_sum($jumlah)],
    'pending' => ['Menunggu', $jumlah['pending']],
    'valid' => ['Valid', $jumlah['valid']],
    'invalid' => ['Ditolak', $jumlah['invalid']],
];
?>
<div class="content-wrapper">
  <section class="content-header">
    <h1>Verifikasi Pembayaran <small>Pembayaran DP oleh penyewa</small></h1>
    <ol class="breadcrumb">
      <li><a href="<?= $e(site_url('admin')) ?>"><i class="fa fa-dashboard"></i> Dashboard</a></li>
      <li class="active">Verifikasi Pembayaran</li>
    </ol>
  </section>

  <section class="content">
    <?php if (!empty($flash)): ?>
      <div class="alert alert-<?= (($flash['type'] ?? '') === 'success') ? 'success' : 'danger' ?> alert-dismissible">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
        <i class="fa <?= (($flash['type'] ?? '') === 'success') ? 'fa-check' : 'fa-warning' ?>"></i>
        <?= $e($flash['message'] ?? '') ?>
      </div>
    <?php endif; ?>

    <div class="box">
      <div class="box-header with-border">
        <h3 class="box-title">Data Pembayaran DP</h3>
        <div class="box-tools">
          <div class="btn-group">
            <?php foreach ($tab as $kunci => [$nama, $n]): ?>
              <a href="<?= $e(site_url('admin/verifikasi') . ($kunci === '' ? '' : '?status=' . $kunci)) ?>"
                 class="btn btn-sm <?= $filter === $kunci ? 'btn-primary' : 'btn-default' ?>">
                <?= $e($nama) ?> (<?= (int) $n ?>)
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      <div class="box-body">
        <div class="table-responsive">
          <table class="table table-bordered table-striped js-data-table">
            <thead>
              <tr>
                <th>No</th>
                <th>Kode Bayar</th>
                <th>Kode Sewa</th>
                <th>Penyewa</th>
                <th>Nominal DP</th>
                <th>Waktu Unggah</th>
                <th>Bukti</th>
                <th>Status Bayar</th>
                <th>Status Sewa</th>
                <th class="no-sort">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($daftar as $i => $row): ?>
                <?php
                $kodeUrl = rawurlencode(str_replace('/', '~', $row['kode_bayar']));
                [$kelas, $teks] = $labelStatus[$row['sttus_byr']] ?? ['label-default', $row['sttus_byr']];
                ?>
                <tr>
                  <td><?= $i + 1 ?></td>
                  <td><?= $e($row['kode_bayar']) ?></td>
                  <td><?= $e($row['kode_sewa']) ?></td>
                  <td><?= $e($row['nm_lngkp']) ?><br><small class="text-muted"><?= $e($row['user_plg']) ?></small></td>
                  <td><?= $e($rp($row['nominal'])) ?></td>
                  <td><?= $e(date('d/m/Y H:i', strtotime($row['tgl_wkt_byr']))) ?></td>
                  <td>
                    <?php if (!empty($row['ft_byr'])): ?>
                      <span class="label label-success">Sudah diunggah</span>
                    <?php else: ?>
                      <span class="label label-default">Belum diunggah</span>
                    <?php endif; ?>
                  </td>
                  <td><span class="label <?= $e($kelas) ?>"><?= $e($teks) ?></span></td>
                  <td>
                    <span class="label <?= $e($labelSewa[$row['status_sewa']] ?? 'label-default') ?>">
                      <?= $e(ucfirst($row['status_sewa'])) ?>
                    </span>
                  </td>
                  <td>
                    <a href="<?= $e(site_url('admin/verifikasi/detail/' . $kodeUrl)) ?>"
                       class="btn btn-info btn-sm" title="Periksa">
                      <i class="fa fa-search"></i> <?= $row['sttus_byr'] === 'pending' ? 'Periksa' : 'Detail' ?>
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </section>
</div>
<?php require APPPATH . 'views/templates/footer.php'; ?>
