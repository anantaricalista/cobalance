CREATE TABLE saldo_awal_koreksi (
    id_saldo_awal_koreksi INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_perusahaan INT NOT NULL,
    id_transaksi INT NOT NULL,
    alasan VARCHAR(1000) NOT NULL,
    data_sebelum LONGTEXT NOT NULL,
    data_sesudah LONGTEXT NOT NULL,
    dampak LONGTEXT NOT NULL,
    id_pengguna INT NOT NULL,
    dibuat_pada DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_saldo_awal_koreksi),
    KEY idx_saldo_awal_koreksi_transaksi (id_perusahaan, id_transaksi, id_saldo_awal_koreksi),
    KEY idx_saldo_awal_koreksi_pengguna (id_perusahaan, id_pengguna)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
