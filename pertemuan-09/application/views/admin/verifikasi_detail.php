<?php
require APPPATH . 'views/templates/header.php';
require APPPATH . 'views/templates/sidebar.php';

$bayar = $bayar ?? [];
$kendaraan = $kendaraan ?? [];
$buktiAda = $buktiAda ?? false;
$flash = $flash ?? null;
$token = $token ?? '';
$e = static fn($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$rp = static fn($n): string => 'Rp ' . number_format((int) $n, 0, ',', '.');
$tgl = static fn($d): string => $d ? date('d/m/Y H:i', strtotime((string) $d)) : '-';
$labelStatus = [
    'pending' => ['label-warning', 'Menunggu verifikasi'],
    'valid' => ['label-success', 'Valid'],
    'invalid' => ['label-danger', 'Ditolak'],
];
[$kelas, $teks] = $labelStatus[$bayar['sttus_byr']] ?? ['label-default', $bayar['sttus_byr']];
$kodeUrl = rawurlencode(str_replace('/', '~', (string) $bayar['kode_bayar']));
$bisaVerifikasi = $bayar['sttus_byr'] === 'pending'
    && empty($bayar['tgl_wkt_ver'])
    && $bayar['status_sewa'] === 'diajukan';
$dpSeharusnya = (int) round(((int) $bayar['total_sewa'] * 30) / 100);
?>
<div class="content-wrapper">
  <section class="content-header">
    <h1>Periksa Pembayaran <small><?= $e($bayar['kode_bayar']) ?></small></h1>
    <ol class="breadcrumb">
      <li><a href="<?= $e(site_url('admin')) ?>"><i class="fa fa-dashboard"></i> Dashboard</a></li>
      <li><a href="<?= $e(site_url('admin/verifikasi')) ?>">Verifikasi Pembayaran</a></li>
      <li class="active">Detail</li>
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

    <div class="row">
      <div class="col-md-6">
        <div class="box box-primary">
          <div class="box-header with-border"><h3 class="box-title">Data Pembayaran</h3></div>
          <div class="box-body">
            <dl class="dl-horizontal">
              <dt>Kode pembayaran</dt><dd><?= $e($bayar['kode_bayar']) ?></dd>
              <dt>Kode sewa</dt><dd><?= $e($bayar['kode_sewa']) ?></dd>
              <dt>Penyewa</dt><dd><?= $e($bayar['nm_lngkp']) ?> (<?= $e($bayar['user_plg']) ?>)</dd>
              <dt>No. HP</dt><dd><?= $e($bayar['no_hp']) ?></dd>
              <dt>Periode sewa</dt>
              <dd><?= $e(date('d/m/Y', strtotime($bayar['tgl_mulai']))) ?> &ndash;
                  <?= $e(date('d/m/Y', strtotime($bayar['tgl_selesai']))) ?>,
                  ambil <?= $e(substr($bayar['wkt_ambil'], 0, 5)) ?></dd>
              <dt>Total sewa</dt><dd><?= $e($rp($bayar['total_sewa'])) ?></dd>
              <dt>Nominal DP</dt>
              <dd>
                <strong><?= $e($rp($bayar['nominal'])) ?></strong>
                <?php if ((int) $bayar['nominal'] !== $dpSeharusnya): ?>
                  <br><small class="text-red">
                    <i class="fa fa-warning"></i> Tidak sama dengan 30% total sewa (<?= $e($rp($dpSeharusnya)) ?>).
                  </small>
                <?php else: ?>
                  <br><small class="text-muted">Sesuai 30% total sewa.</small>
                <?php endif; ?>
              </dd>
              <dt>Metode</dt><dd><?= $e(ucfirst($bayar['mtode_byr'])) ?></dd>
              <dt>Waktu unggah</dt><dd><?= $e($tgl($bayar['tgl_wkt_byr'])) ?></dd>
              <dt>Status bayar</dt><dd><span class="label <?= $e($kelas) ?>"><?= $e($teks) ?></span></dd>
              <dt>Status sewa</dt><dd><?= $e(ucfirst($bayar['status_sewa'])) ?></dd>
              <dt>Waktu verifikasi</dt><dd><?= $e($tgl($bayar['tgl_wkt_ver'])) ?></dd>
            </dl>
          </div>
        </div>

        <div class="box">
          <div class="box-header with-border"><h3 class="box-title">Kendaraan</h3></div>
          <div class="box-body table-responsive no-padding">
            <table class="table table-striped">
              <thead><tr><th>No. Polisi</th><th>Kendaraan</th><th>Harga/hari</th><th>Hari</th></tr></thead>
              <tbody>
              <?php foreach ($kendaraan as $k): ?>
                <tr>
                  <td><?= $e($k['no_plat']) ?></td>
                  <td><?= $e($k['nm_mobil']) ?></td>
                  <td><?= $e($rp($k['hrg_sewa'])) ?></td>
                  <td><?= (int) $k['n_hari'] ?></td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="col-md-6">
        <div class="box box-warning">
          <div class="box-header with-border"><h3 class="box-title">Bukti Pembayaran</h3></div>
          <div class="box-body text-center">
            <?php if ($buktiAda): ?>
              <a href="<?= $e(site_url('admin/verifikasi/bukti/' . $kodeUrl)) ?>" target="_blank" rel="noopener">
                <img class="img-responsive img-thumbnail center-block" style="max-height:480px"
                     src="<?= $e(site_url('admin/verifikasi/bukti/' . $kodeUrl)) ?>"
                     alt="Bukti pembayaran <?= $e($bayar['kode_bayar']) ?>">
              </a>
              <p class="text-muted" style="margin-top:8px">Klik gambar untuk membuka ukuran penuh.</p>
            <?php elseif (!empty($bayar['ft_byr'])): ?>
              <div class="alert alert-danger">Berkas bukti tercatat di database tetapi tidak ditemukan pada folder penyimpanan.</div>
            <?php else: ?>
              <div class="alert alert-warning">Penyewa belum mengunggah bukti pembayaran.</div>
            <?php endif; ?>
          </div>
        </div>

        <div class="box">
          <div class="box-header with-border"><h3 class="box-title">Keputusan</h3></div>
          <div class="box-body">
            <?php if ($bisaVerifikasi): ?>
              <p>Periksa bukti dan nominal, lalu putuskan. Keputusan tidak dapat diubah setelah disimpan.</p>
              <form method="post" class="pull-left" style="margin-right:8px"
                    action="<?= $e(site_url('admin/verifikasi/terima/' . $kodeUrl)) ?>"
                    onsubmit="return confirm('Terima pembayaran ini? Transaksi sewa akan berstatus disetujui.');">
                <input type="hidden" name="csrf_token" value="<?= $e($token) ?>">
                <button type="submit" class="btn btn-success" <?= $buktiAda ? '' : 'disabled title="Bukti belum tersedia"' ?>>
                  <i class="fa fa-check"></i> Terima
                </button>
              </form>
              <form method="post"
                    action="<?= $e(site_url('admin/verifikasi/tolak/' . $kodeUrl)) ?>"
                    onsubmit="return confirm('Tolak pembayaran ini? Transaksi sewa akan berstatus ditolak.');">
                <input type="hidden" name="csrf_token" value="<?= $e($token) ?>">
                <button type="submit" class="btn btn-danger"><i class="fa fa-times"></i> Tolak</button>
              </form>
              <div class="clearfix"></div>
            <?php else: ?>
              <div class="alert alert-info" style="margin-bottom:0">
                Pembayaran ini sudah diproses atau transaksi sewanya tidak lagi berstatus diajukan.
              </div>
            <?php endif; ?>
          </div>
        </div>

        <a href="<?= $e(site_url('admin/verifikasi')) ?>" class="btn btn-default">
          <i class="fa fa-arrow-left"></i> Kembali
        </a>
      </div>
    </div>
  </section>
</div>
<?php require APPPATH . 'views/templates/footer.php'; ?>
