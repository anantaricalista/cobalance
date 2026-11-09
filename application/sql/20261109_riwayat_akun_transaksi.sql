CREATE TABLE riwayat_akun_transaksi (
    id_perusahaan INT NOT NULL,
    id_transaksi INT NOT NULL,
    id_detail_transaksi INT NOT NULL,
    tipe_produk ENUM('barang', 'jasa') NOT NULL,
    id_akun_nilai INT NOT NULL,
    id_akun_pajak INT DEFAULT NULL,
    id_akun_persediaan INT DEFAULT NULL,
    id_akun_beban_pokok INT DEFAULT NULL,
    id_akun_penyelesaian INT NOT NULL,
    PRIMARY KEY (id_perusahaan, id_detail_transaksi),
    KEY idx_riwayat_akun_transaksi (id_perusahaan, id_transaksi),
    CONSTRAINT fk_riwayat_akun_detail FOREIGN KEY (id_perusahaan, id_detail_transaksi, id_transaksi)
        REFERENCES detail_transaksi (id_perusahaan, id_detail_transaksi, id_transaksi),
    CONSTRAINT fk_riwayat_akun_nilai FOREIGN KEY (id_perusahaan, id_akun_nilai)
        REFERENCES akun (id_perusahaan, id_akun),
    CONSTRAINT fk_riwayat_akun_pajak FOREIGN KEY (id_perusahaan, id_akun_pajak)
        REFERENCES akun (id_perusahaan, id_akun),
    CONSTRAINT fk_riwayat_akun_persediaan FOREIGN KEY (id_perusahaan, id_akun_persediaan)
        REFERENCES akun (id_perusahaan, id_akun),
    CONSTRAINT fk_riwayat_akun_beban_pokok FOREIGN KEY (id_perusahaan, id_akun_beban_pokok)
        REFERENCES akun (id_perusahaan, id_akun),
    CONSTRAINT fk_riwayat_akun_penyelesaian FOREIGN KEY (id_perusahaan, id_akun_penyelesaian)
        REFERENCES akun (id_perusahaan, id_akun)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
