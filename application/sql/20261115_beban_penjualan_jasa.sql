-- Jalankan satu kali setelah mencadangkan database.
ALTER TABLE transaksi MODIFY jenis_transaksi ENUM(
    'penjualan','pembelian','penerimaan_piutang','pembayaran_utang',
    'kas_masuk','kas_keluar','transfer_kas','retur_penjualan','retur_pembelian',
    'saldo_awal','saldo_awal_piutang','saldo_awal_utang','saldo_awal_persediaan',
    'penyesuaian_akuntansi','penyesuaian_persediaan','setoran_modal','prive',
    'perolehan_aset','penyusutan_aset','pelepasan_aset','beban_penjualan_jasa'
) NOT NULL;
