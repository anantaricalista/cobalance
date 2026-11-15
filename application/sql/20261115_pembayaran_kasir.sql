CREATE TABLE pembayaran_kasir (
    id_pembayaran_kasir INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_perusahaan INT NOT NULL,
    id_transaksi INT NOT NULL,
    id_pengguna INT NOT NULL,
    kunci_permintaan CHAR(32) NOT NULL,
    payload_hash CHAR(64) NOT NULL,
    metode ENUM('tunai','kartu','transfer','qris') NOT NULL,
    dibayar DECIMAL(15,2) NOT NULL,
    kembalian DECIMAL(15,2) NOT NULL,
    PRIMARY KEY (id_pembayaran_kasir),
    UNIQUE KEY uq_kasir_permintaan (id_perusahaan, kunci_permintaan),
    UNIQUE KEY uq_kasir_transaksi (id_perusahaan, id_transaksi)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
