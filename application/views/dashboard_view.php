<style>
	.dashboard-page {
		color: #263248;
	}

	.dashboard-page .dashboard-heading {
		display: flex;
		align-items: flex-end;
		justify-content: space-between;
		gap: 16px;
		margin: 4px 0 24px;
	}

	.dashboard-page .dashboard-heading h2 {
		margin: 0 0 6px;
		color: #183153;
		font-size: 28px;
		font-weight: 700;
	}

	.dashboard-page .dashboard-heading p,
	.dashboard-page .dashboard-period {
		margin: 0;
		color: #6b7b90;
	}

	.dashboard-page .dashboard-card {
		position: relative;
		display: block;
		min-height: 158px;
		margin-bottom: 20px;
		padding: 21px;
		overflow: hidden;
		border: 1px solid #e1e8f0;
		border-radius: 10px;
		background: #fff;
		box-shadow: 0 4px 14px rgba(27, 49, 79, .05);
		color: inherit;
		transition: box-shadow .15s ease, transform .15s ease;
	}

	.dashboard-page a.dashboard-card:hover,
	.dashboard-page a.dashboard-card:focus {
		color: inherit;
		text-decoration: none;
		box-shadow: 0 8px 20px rgba(27, 49, 79, .11);
		transform: translateY(-2px);
	}

	.dashboard-page .dashboard-card:before {
		position: absolute;
		top: 0;
		bottom: 0;
		left: 0;
		width: 4px;
		background: #4a93a5;
		content: "";
	}

	.dashboard-page .dashboard-card.card-green:before { background: #35a77a; }
	.dashboard-page .dashboard-card.card-orange:before { background: #e7a43b; }
	.dashboard-page .dashboard-card.card-purple:before { background: #7d72c5; }

	.dashboard-page .dashboard-card .card-label {
		display: block;
		margin-bottom: 13px;
		color: #687a91;
		font-size: 13px;
		font-weight: 600;
	}

	.dashboard-page .dashboard-card .card-value {
		display: block;
		color: #183153;
		font-size: 24px;
		font-weight: 700;
		line-height: 1.3;
		overflow-wrap: anywhere;
	}

	.dashboard-page .dashboard-card .card-note {
		display: block;
		margin-top: 8px;
		color: #8492a5;
		font-size: 12px;
	}

	.dashboard-page .dashboard-panel {
		margin-bottom: 22px;
		border: 1px solid #e1e8f0;
		border-radius: 10px;
		background: #fff;
		box-shadow: 0 4px 14px rgba(27, 49, 79, .04);
	}

	.dashboard-page .dashboard-panel-heading {
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: 12px;
		padding: 17px 20px;
		border-bottom: 1px solid #edf1f6;
	}

	.dashboard-page .dashboard-panel-heading h3 {
		margin: 0;
		color: #243b5a;
		font-size: 17px;
		font-weight: 700;
	}

	.dashboard-page .dashboard-panel-body {
		padding: 18px 20px;
	}

	.dashboard-page .dashboard-panel .table {
		margin: 0;
	}

	.dashboard-page .dashboard-panel .table th {
		border-top: 0;
		color: #718198;
		font-size: 11px;
		letter-spacing: .04em;
		text-transform: uppercase;
	}

	.dashboard-page .dashboard-panel .table td {
		vertical-align: middle;
	}

	.dashboard-page .dashboard-monthly {
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: 12px;
		padding: 14px 0;
		border-bottom: 1px solid #edf1f6;
	}

	.dashboard-page .dashboard-monthly:first-child { padding-top: 0; }
	.dashboard-page .dashboard-monthly:last-child { padding-bottom: 0; border-bottom: 0; }
	.dashboard-page .dashboard-monthly span { color: #687a91; }
	.dashboard-page .dashboard-monthly strong { color: #183153; }

	.dashboard-page .dashboard-shortcuts {
		display: flex;
		flex-wrap: wrap;
		gap: 9px;
	}

	.dashboard-page .dashboard-shortcuts .btn {
		border-radius: 6px;
		font-weight: 600;
	}

	.dashboard-page .dashboard-empty {
		padding: 30px 15px;
		color: #718198;
		text-align: center;
	}

	@media (max-width: 767px) {
		.dashboard-page .dashboard-heading {
			align-items: flex-start;
			flex-direction: column;
			margin-bottom: 18px;
		}

		.dashboard-page .dashboard-card { min-height: 140px; }
		.dashboard-page .dashboard-panel-heading,
		.dashboard-page .dashboard-panel-body { padding-right: 14px; padding-left: 14px; }
	}
</style>

<?php
$currency = function ($amount) {
	return 'Rp ' . format_nominal($amount);
};
$transaction_labels = array(
	'penjualan' => 'Penjualan',
	'pembelian' => 'Pembelian',
	'penerimaan_piutang' => 'Penerimaan Piutang',
	'pembayaran_utang' => 'Pembayaran Utang',
	'kas_masuk' => 'Kas Masuk',
	'kas_keluar' => 'Kas Keluar',
	'transfer_kas' => 'Transfer Kas/Bank',
	'retur_penjualan' => 'Retur Penjualan',
	'retur_pembelian' => 'Retur Pembelian',
	'saldo_awal' => 'Saldo Awal',
	'saldo_awal_piutang' => 'Saldo Awal Piutang',
	'saldo_awal_utang' => 'Saldo Awal Utang',
	'saldo_awal_persediaan' => 'Saldo Awal Persediaan',
	'penyesuaian_akuntansi' => 'Penyesuaian Akuntansi',
	'penyesuaian_persediaan' => 'Penyesuaian Persediaan',
	'setoran_modal' => 'Setoran Modal',
	'prive' => 'Prive',
	'perolehan_aset' => 'Perolehan Aset',
	'penyusutan_aset' => 'Penyusutan Aset',
	'pelepasan_aset' => 'Pelepasan Aset'
);
?>

<div class="dashboard-page">
	<div class="dashboard-heading">
		<div>
			<h2>Selamat datang, <?= html_escape($user['nama_lengkap'] ?: $user['username']) ?></h2>
			<p><?= html_escape($user['nama_perusahaan']) ?> · Ringkasan posisi keuangan dan aktivitas usaha</p>
		</div>
		<div class="dashboard-period">Data per <?= date('d/m/Y', strtotime($today)) ?></div>
	</div>

	<div class="row">
		<div class="col-sm-6 col-lg-3">
			<a class="dashboard-card" href="<?= site_url('kas_bank') ?>">
				<span class="card-label">Saldo Kas &amp; Bank</span>
				<strong class="card-value"><?= $currency($summary['cash']) ?></strong>
				<span class="card-note">Saldo jurnal akun kas dan bank hingga hari ini</span>
			</a>
		</div>
		<div class="col-sm-6 col-lg-3">
			<a class="dashboard-card card-green" href="<?= site_url('penerimaan_piutang') ?>">
				<span class="card-label">Piutang Belum Lunas</span>
				<strong class="card-value"><?= $currency($summary['receivable']) ?></strong>
				<span class="card-note">Setelah penerimaan dan retur penjualan terposting</span>
			</a>
		</div>
		<div class="col-sm-6 col-lg-3">
			<a class="dashboard-card card-orange" href="<?= site_url('pembayaran_utang') ?>">
				<span class="card-label">Utang Belum Lunas</span>
				<strong class="card-value"><?= $currency($summary['payable']) ?></strong>
				<span class="card-note">Setelah pembayaran dan retur pembelian terposting</span>
			</a>
		</div>
		<div class="col-sm-6 col-lg-3">
			<a class="dashboard-card card-purple" href="<?= site_url('jurnal') ?>">
				<span class="card-label">Transaksi Diposting</span>
				<strong class="card-value"><?= number_format((int) $summary['monthly']->transaction_count, 0, ',', '.') ?></strong>
				<span class="card-note">Bulan berjalan · <?= date('m/Y', strtotime($today)) ?></span>
			</a>
		</div>
	</div>

	<div class="row">
		<div class="col-md-4">
			<section class="dashboard-panel">
				<div class="dashboard-panel-heading">
					<h3>Aktivitas Bulan Ini</h3>
					<span class="text-muted"><?= date('m/Y', strtotime($today)) ?></span>
				</div>
				<div class="dashboard-panel-body">
					<div class="dashboard-monthly">
						<span>Penjualan bersih</span>
						<strong><?= $currency($summary['monthly']->sales) ?></strong>
					</div>
					<div class="dashboard-monthly">
						<span>Pembelian bersih</span>
						<strong><?= $currency($summary['monthly']->purchases) ?></strong>
					</div>
					<div class="dashboard-monthly">
						<span>Jumlah transaksi terposting</span>
						<strong><?= number_format((int) $summary['monthly']->transaction_count, 0, ',', '.') ?></strong>
					</div>
				</div>
			</section>
		</div>
		<div class="col-md-8">
			<section class="dashboard-panel">
				<div class="dashboard-panel-heading">
					<h3>Transaksi Terbaru</h3>
					<a href="<?= site_url('jurnal') ?>">Lihat jurnal <span aria-hidden="true">&rarr;</span></a>
				</div>
				<?php if (empty($summary['recent_transactions'])): ?>
					<div class="dashboard-empty">
						<p><strong>Belum ada transaksi terposting.</strong></p>
						<p class="small">Transaksi yang sudah diposting akan muncul di sini.</p>
					</div>
				<?php else: ?>
					<div class="table-responsive">
						<table class="table table-hover">
							<thead>
								<tr>
									<th>Tanggal</th>
									<th>Nomor</th>
									<th>Jenis</th>
									<th>Pihak</th>
									<th class="text-right">Total</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($summary['recent_transactions'] as $transaction): ?>
									<tr>
										<td><?= date('d/m/Y', strtotime($transaction->tanggal)) ?></td>
										<td><?= html_escape($transaction->nomor_transaksi) ?></td>
										<td>
											<?= html_escape(isset($transaction_labels[$transaction->jenis_transaksi])
												? $transaction_labels[$transaction->jenis_transaksi]
												: $transaction->jenis_transaksi) ?>
										</td>
										<td><?= html_escape($transaction->pihak) ?></td>
										<td class="text-right"><?= $currency($transaction->total) ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php endif; ?>
			</section>
		</div>
	</div>

	<section class="dashboard-panel">
		<div class="dashboard-panel-heading">
			<h3>Akses Cepat</h3>
			<span class="text-muted">Pintasan ke aktivitas utama</span>
		</div>
		<div class="dashboard-panel-body dashboard-shortcuts">
			<a class="btn btn-primary" href="<?= site_url('penjualan') ?>"><span class="glyphicon glyphicon-shopping-cart" aria-hidden="true"></span> Penjualan</a>
			<a class="btn btn-default" href="<?= site_url('pembelian') ?>"><span class="glyphicon glyphicon-inbox" aria-hidden="true"></span> Pembelian</a>
			<a class="btn btn-default" href="<?= site_url('kas_bank') ?>"><span class="glyphicon glyphicon-credit-card" aria-hidden="true"></span> Kas &amp; Bank</a>
			<a class="btn btn-default" href="<?= site_url('jurnal') ?>"><span class="glyphicon glyphicon-book" aria-hidden="true"></span> Jurnal</a>
			<a class="btn btn-default" href="<?= site_url('neraca') ?>"><span class="glyphicon glyphicon-stats" aria-hidden="true"></span> Neraca</a>
			<a class="btn btn-default" href="<?= site_url('laba_rugi') ?>"><span class="glyphicon glyphicon-signal" aria-hidden="true"></span> Laba Rugi</a>
		</div>
	</section>
</div>
