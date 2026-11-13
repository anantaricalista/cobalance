-- Jalankan satu kali setelah backup database.
-- Mencatat jurnal pemindahan Saldo Laba (Rugi) Berjalan ke Saldo Laba (Rugi) Ditahan
-- dan baris jurnal yang sudah tercakup di dalamnya.
CREATE TABLE pemindahan_laba (
    id_pemindahan_laba INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_perusahaan INT NOT NULL,
    tahun SMALLINT UNSIGNED NOT NULL,
    id_transaksi INT NOT NULL,
    jenis ENUM('awal', 'selisih') NOT NULL,
    id_akun_berjalan INT NOT NULL,
    id_akun_ditahan INT NOT NULL,
    jumlah DECIMAL(15,2) NOT NULL COMMENT 'Positif untuk laba, negatif untuk rugi',
    id_pengguna INT NOT NULL,
    dibuat_pada DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_pemindahan_laba),
    UNIQUE KEY uq_pemindahan_laba_transaksi (id_perusahaan, id_transaksi),
    KEY idx_pemindahan_laba_tahun (id_perusahaan, tahun),
    CONSTRAINT fk_pemindahan_laba_transaksi FOREIGN KEY (id_perusahaan, id_transaksi)
        REFERENCES transaksi (id_perusahaan, id_transaksi)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE pemindahan_laba_detail (
    id_perusahaan INT NOT NULL,
    id_transaksi INT NOT NULL,
    id_detail INT NOT NULL,
    PRIMARY KEY (id_perusahaan, id_transaksi, id_detail),
    KEY idx_pemindahan_laba_detail (id_perusahaan, id_detail),
    CONSTRAINT fk_pemindahan_laba_detail_transaksi FOREIGN KEY (id_perusahaan, id_transaksi)
        REFERENCES transaksi (id_perusahaan, id_transaksi),
    CONSTRAINT fk_pemindahan_laba_detail_jurnal FOREIGN KEY (id_detail)
        REFERENCES detail_jurnal (id_detail)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
