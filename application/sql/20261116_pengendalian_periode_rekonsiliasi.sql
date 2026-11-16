CREATE TABLE periode_akuntansi (
    id_periode INT NOT NULL AUTO_INCREMENT,
    id_perusahaan INT NOT NULL,
    periode DATE NOT NULL,
    status ENUM('terbuka', 'ditutup') NOT NULL DEFAULT 'terbuka',
    catatan VARCHAR(500) DEFAULT NULL,
    id_pengguna_ubah INT NOT NULL,
    diperbarui_pada DATETIME NOT NULL,
    PRIMARY KEY (id_periode),
    UNIQUE KEY uq_periode_perusahaan (id_perusahaan, periode),
    KEY idx_periode_status (id_perusahaan, status, periode),
    CONSTRAINT fk_periode_perusahaan FOREIGN KEY (id_perusahaan) REFERENCES perusahaan (id_perusahaan)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE periode_akuntansi_riwayat (
    id_riwayat BIGINT NOT NULL AUTO_INCREMENT,
    id_perusahaan INT NOT NULL,
    periode DATE NOT NULL,
    aksi ENUM('ditutup', 'dibuka_kembali') NOT NULL,
    alasan VARCHAR(500) DEFAULT NULL,
    id_pengguna INT NOT NULL,
    dibuat_pada DATETIME NOT NULL,
    PRIMARY KEY (id_riwayat),
    KEY idx_riwayat_periode (id_perusahaan, periode, dibuat_pada),
    CONSTRAINT fk_periode_riwayat_perusahaan FOREIGN KEY (id_perusahaan) REFERENCES perusahaan (id_perusahaan)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE akun_kas_bank (
    id_perusahaan INT NOT NULL,
    id_akun INT NOT NULL,
    jenis ENUM('rekening_bank', 'kas_tunai') NOT NULL,
    aktif TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id_perusahaan, id_akun),
    KEY idx_akun_kas_bank_aktif (id_perusahaan, aktif, jenis),
    CONSTRAINT fk_klasifikasi_akun FOREIGN KEY (id_perusahaan, id_akun) REFERENCES akun (id_perusahaan, id_akun)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE rekonsiliasi_bank (
    id_rekonsiliasi BIGINT NOT NULL AUTO_INCREMENT,
    id_perusahaan INT NOT NULL,
    id_akun INT NOT NULL,
    periode DATE NOT NULL,
    saldo_awal DECIMAL(15,2) NOT NULL,
    saldo_akhir DECIMAL(15,2) NOT NULL,
    status ENUM('draft', 'selesai') NOT NULL DEFAULT 'draft',
    id_pengguna_ubah INT NOT NULL,
    diperbarui_pada DATETIME NOT NULL,
    PRIMARY KEY (id_rekonsiliasi),
    UNIQUE KEY uq_rekonsiliasi_akun_periode (id_perusahaan, id_akun, periode),
    KEY idx_rekonsiliasi_status (id_perusahaan, periode, status),
    CONSTRAINT fk_rekonsiliasi_akun FOREIGN KEY (id_perusahaan, id_akun) REFERENCES akun_kas_bank (id_perusahaan, id_akun)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE mutasi_rekening_bank (
    id_mutasi_rekening BIGINT NOT NULL AUTO_INCREMENT,
    id_rekonsiliasi BIGINT NOT NULL,
    tanggal DATE NOT NULL,
    keterangan VARCHAR(255) NOT NULL,
    arah ENUM('masuk', 'keluar') NOT NULL,
    jumlah DECIMAL(15,2) NOT NULL,
    id_detail_jurnal INT DEFAULT NULL,
    PRIMARY KEY (id_mutasi_rekening),
    UNIQUE KEY uq_mutasi_cocok_detail (id_detail_jurnal),
    KEY idx_mutasi_rekonsiliasi (id_rekonsiliasi, tanggal),
    CONSTRAINT fk_mutasi_rekonsiliasi FOREIGN KEY (id_rekonsiliasi) REFERENCES rekonsiliasi_bank (id_rekonsiliasi),
    CONSTRAINT fk_mutasi_jurnal FOREIGN KEY (id_detail_jurnal) REFERENCES detail_jurnal (id_detail) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DELIMITER $$

CREATE TRIGGER cobalance_transaksi_bi
BEFORE INSERT ON transaksi
FOR EACH ROW
BEGIN
    IF EXISTS (
        SELECT 1 FROM periode_akuntansi p
        WHERE p.id_perusahaan = NEW.id_perusahaan
          AND p.periode >= DATE_FORMAT(NEW.tanggal, '%Y-%m-01')
          AND p.status = 'ditutup'
    ) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Periode transaksi sudah ditutup; minta Admin membuka kembali periode.';
    END IF;
END$$

CREATE TRIGGER cobalance_transaksi_bu
BEFORE UPDATE ON transaksi
FOR EACH ROW
BEGIN
    IF EXISTS (
        SELECT 1 FROM periode_akuntansi p
        WHERE p.id_perusahaan = OLD.id_perusahaan
          AND p.periode >= DATE_FORMAT(OLD.tanggal, '%Y-%m-01')
          AND p.status = 'ditutup'
    ) OR EXISTS (
        SELECT 1 FROM periode_akuntansi p
        WHERE p.id_perusahaan = NEW.id_perusahaan
          AND p.periode >= DATE_FORMAT(NEW.tanggal, '%Y-%m-01')
          AND p.status = 'ditutup'
    ) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Transaksi berada pada periode yang ditutup; minta Admin membuka kembali periode.';
    END IF;
END$$

CREATE TRIGGER cobalance_transaksi_bd
BEFORE DELETE ON transaksi
FOR EACH ROW
BEGIN
    IF EXISTS (
        SELECT 1 FROM periode_akuntansi p
        WHERE p.id_perusahaan = OLD.id_perusahaan
          AND p.periode >= DATE_FORMAT(OLD.tanggal, '%Y-%m-01')
          AND p.status = 'ditutup'
    ) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Transaksi berada pada periode yang ditutup; minta Admin membuka kembali periode.';
    END IF;
END$$

CREATE TRIGGER cobalance_jurnal_bi
BEFORE INSERT ON jurnal_transaksi
FOR EACH ROW
BEGIN
    IF EXISTS (
        SELECT 1 FROM periode_akuntansi p
        WHERE p.id_perusahaan = NEW.id_perusahaan
          AND p.periode >= DATE_FORMAT(NEW.tanggal, '%Y-%m-01')
          AND p.status = 'ditutup'
    ) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Tanggal jurnal berada pada periode yang ditutup; minta Admin membuka kembali periode.';
    END IF;
END$$

CREATE TRIGGER cobalance_jurnal_bu
BEFORE UPDATE ON jurnal_transaksi
FOR EACH ROW
BEGIN
    IF EXISTS (
        SELECT 1 FROM periode_akuntansi p
        WHERE p.id_perusahaan = OLD.id_perusahaan
          AND p.periode >= DATE_FORMAT(OLD.tanggal, '%Y-%m-01')
          AND p.status = 'ditutup'
    ) OR EXISTS (
        SELECT 1 FROM periode_akuntansi p
        WHERE p.id_perusahaan = NEW.id_perusahaan
          AND p.periode >= DATE_FORMAT(NEW.tanggal, '%Y-%m-01')
          AND p.status = 'ditutup'
    ) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Jurnal berada pada periode yang ditutup; minta Admin membuka kembali periode.';
    END IF;
END$$

CREATE TRIGGER cobalance_jurnal_bd
BEFORE DELETE ON jurnal_transaksi
FOR EACH ROW
BEGIN
    IF EXISTS (
        SELECT 1 FROM periode_akuntansi p
        WHERE p.id_perusahaan = OLD.id_perusahaan
          AND p.periode >= DATE_FORMAT(OLD.tanggal, '%Y-%m-01')
          AND p.status = 'ditutup'
    ) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Jurnal berada pada periode yang ditutup; minta Admin membuka kembali periode.';
    END IF;
END$$

CREATE TRIGGER cobalance_detail_jurnal_bi
BEFORE INSERT ON detail_jurnal
FOR EACH ROW
BEGIN
    IF EXISTS (
        SELECT 1 FROM jurnal_transaksi j
        JOIN periode_akuntansi p ON p.id_perusahaan = j.id_perusahaan
            AND p.periode >= DATE_FORMAT(j.tanggal, '%Y-%m-01')
            AND p.status = 'ditutup'
        WHERE j.id_jurnal = NEW.id_jurnal
    ) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Detail jurnal berada pada periode yang ditutup; minta Admin membuka kembali periode.';
    END IF;
END$$

CREATE TRIGGER cobalance_detail_jurnal_bu
BEFORE UPDATE ON detail_jurnal
FOR EACH ROW
BEGIN
    IF EXISTS (
        SELECT 1 FROM jurnal_transaksi j
        JOIN periode_akuntansi p ON p.id_perusahaan = j.id_perusahaan
            AND p.periode >= DATE_FORMAT(j.tanggal, '%Y-%m-01')
            AND p.status = 'ditutup'
        WHERE j.id_jurnal IN (OLD.id_jurnal, NEW.id_jurnal)
    ) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Detail jurnal berada pada periode yang ditutup; minta Admin membuka kembali periode.';
    END IF;
END$$

CREATE TRIGGER cobalance_detail_jurnal_bd
BEFORE DELETE ON detail_jurnal
FOR EACH ROW
BEGIN
    IF EXISTS (
        SELECT 1 FROM jurnal_transaksi j
        JOIN periode_akuntansi p ON p.id_perusahaan = j.id_perusahaan
            AND p.periode >= DATE_FORMAT(j.tanggal, '%Y-%m-01')
            AND p.status = 'ditutup'
        WHERE j.id_jurnal = OLD.id_jurnal
    ) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Detail jurnal berada pada periode yang ditutup; minta Admin membuka kembali periode.';
    END IF;
END$$

CREATE TRIGGER cobalance_transaksi_jurnal_bi
BEFORE INSERT ON transaksi_jurnal
FOR EACH ROW
BEGIN
    IF EXISTS (
        SELECT 1 FROM jurnal_transaksi j
        JOIN periode_akuntansi p ON p.id_perusahaan=j.id_perusahaan
            AND p.periode>=DATE_FORMAT(j.tanggal,'%Y-%m-01') AND p.status='ditutup'
        WHERE j.id_perusahaan=NEW.id_perusahaan AND j.id_jurnal=NEW.id_jurnal
    ) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Relasi transaksi dan jurnal berada pada periode yang ditutup.';
    END IF;
END$$

CREATE TRIGGER cobalance_transaksi_jurnal_bu
BEFORE UPDATE ON transaksi_jurnal
FOR EACH ROW
BEGIN
    IF EXISTS (
        SELECT 1 FROM jurnal_transaksi j
        JOIN periode_akuntansi p ON p.id_perusahaan=j.id_perusahaan
            AND p.periode>=DATE_FORMAT(j.tanggal,'%Y-%m-01') AND p.status='ditutup'
        WHERE j.id_perusahaan=OLD.id_perusahaan AND j.id_jurnal=OLD.id_jurnal
    ) OR EXISTS (
        SELECT 1 FROM jurnal_transaksi j
        JOIN periode_akuntansi p ON p.id_perusahaan=j.id_perusahaan
            AND p.periode>=DATE_FORMAT(j.tanggal,'%Y-%m-01') AND p.status='ditutup'
        WHERE j.id_perusahaan=NEW.id_perusahaan AND j.id_jurnal=NEW.id_jurnal
    ) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Relasi transaksi dan jurnal berada pada periode yang ditutup.';
    END IF;
END$$

CREATE TRIGGER cobalance_transaksi_jurnal_bd
BEFORE DELETE ON transaksi_jurnal
FOR EACH ROW
BEGIN
    IF EXISTS (
        SELECT 1 FROM jurnal_transaksi j
        JOIN periode_akuntansi p ON p.id_perusahaan=j.id_perusahaan
            AND p.periode>=DATE_FORMAT(j.tanggal,'%Y-%m-01') AND p.status='ditutup'
        WHERE j.id_perusahaan=OLD.id_perusahaan AND j.id_jurnal=OLD.id_jurnal
    ) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Relasi transaksi dan jurnal berada pada periode yang ditutup.';
    END IF;
END$$

CREATE TRIGGER cobalance_mutasi_persediaan_bi
BEFORE INSERT ON mutasi_persediaan
FOR EACH ROW
BEGIN
    IF EXISTS (
        SELECT 1 FROM transaksi_jurnal tj
        JOIN jurnal_transaksi j ON j.id_perusahaan=tj.id_perusahaan AND j.id_jurnal=tj.id_jurnal
        JOIN periode_akuntansi p ON p.id_perusahaan=j.id_perusahaan
            AND p.periode>=DATE_FORMAT(j.tanggal,'%Y-%m-01') AND p.status='ditutup'
        WHERE tj.id_perusahaan=NEW.id_perusahaan AND tj.id_transaksi=NEW.id_transaksi
          AND tj.jenis_jurnal=CASE WHEN NEW.jenis_mutasi IN ('pembalik_masuk','pembalik_keluar')
              THEN 'pembalik' ELSE 'posting' END
    ) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Mutasi persediaan berada pada periode yang ditutup.';
    END IF;
END$$

CREATE TRIGGER cobalance_mutasi_persediaan_bu
BEFORE UPDATE ON mutasi_persediaan
FOR EACH ROW
BEGIN
    IF EXISTS (
        SELECT 1 FROM transaksi_jurnal tj
        JOIN jurnal_transaksi j ON j.id_perusahaan=tj.id_perusahaan AND j.id_jurnal=tj.id_jurnal
        JOIN periode_akuntansi p ON p.id_perusahaan=j.id_perusahaan
            AND p.periode>=DATE_FORMAT(j.tanggal,'%Y-%m-01') AND p.status='ditutup'
        WHERE tj.id_perusahaan=OLD.id_perusahaan AND tj.id_transaksi=OLD.id_transaksi
          AND tj.jenis_jurnal=CASE WHEN OLD.jenis_mutasi IN ('pembalik_masuk','pembalik_keluar')
              THEN 'pembalik' ELSE 'posting' END
    ) OR EXISTS (
        SELECT 1 FROM transaksi_jurnal tj
        JOIN jurnal_transaksi j ON j.id_perusahaan=tj.id_perusahaan AND j.id_jurnal=tj.id_jurnal
        JOIN periode_akuntansi p ON p.id_perusahaan=j.id_perusahaan
            AND p.periode>=DATE_FORMAT(j.tanggal,'%Y-%m-01') AND p.status='ditutup'
        WHERE tj.id_perusahaan=NEW.id_perusahaan AND tj.id_transaksi=NEW.id_transaksi
          AND tj.jenis_jurnal=CASE WHEN NEW.jenis_mutasi IN ('pembalik_masuk','pembalik_keluar')
              THEN 'pembalik' ELSE 'posting' END
    ) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Mutasi persediaan berada pada periode yang ditutup.';
    END IF;
END$$

CREATE TRIGGER cobalance_mutasi_persediaan_bd
BEFORE DELETE ON mutasi_persediaan
FOR EACH ROW
BEGIN
    IF EXISTS (
        SELECT 1 FROM transaksi_jurnal tj
        JOIN jurnal_transaksi j ON j.id_perusahaan=tj.id_perusahaan AND j.id_jurnal=tj.id_jurnal
        JOIN periode_akuntansi p ON p.id_perusahaan=j.id_perusahaan
            AND p.periode>=DATE_FORMAT(j.tanggal,'%Y-%m-01') AND p.status='ditutup'
        WHERE tj.id_perusahaan=OLD.id_perusahaan AND tj.id_transaksi=OLD.id_transaksi
          AND tj.jenis_jurnal=CASE WHEN OLD.jenis_mutasi IN ('pembalik_masuk','pembalik_keluar')
              THEN 'pembalik' ELSE 'posting' END
    ) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Mutasi persediaan berada pada periode yang ditutup.';
    END IF;
END$$

CREATE TRIGGER cobalance_penyusutan_aset_bi
BEFORE INSERT ON penyusutan_aset
FOR EACH ROW
BEGIN
    IF EXISTS (
        SELECT 1 FROM transaksi_jurnal tj
        JOIN jurnal_transaksi j ON j.id_perusahaan=tj.id_perusahaan AND j.id_jurnal=tj.id_jurnal
        JOIN periode_akuntansi p ON p.id_perusahaan=j.id_perusahaan
            AND p.periode>=DATE_FORMAT(j.tanggal,'%Y-%m-01') AND p.status='ditutup'
        WHERE tj.id_perusahaan=NEW.id_perusahaan AND tj.id_transaksi=NEW.id_transaksi
    ) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Penyusutan aset berada pada periode yang ditutup.';
    END IF;
END$$

CREATE TRIGGER cobalance_penyusutan_aset_bu
BEFORE UPDATE ON penyusutan_aset
FOR EACH ROW
BEGIN
    IF EXISTS (
        SELECT 1 FROM transaksi_jurnal tj
        JOIN jurnal_transaksi j ON j.id_perusahaan=tj.id_perusahaan AND j.id_jurnal=tj.id_jurnal
        JOIN periode_akuntansi p ON p.id_perusahaan=j.id_perusahaan
            AND p.periode>=DATE_FORMAT(j.tanggal,'%Y-%m-01') AND p.status='ditutup'
        WHERE tj.id_perusahaan=OLD.id_perusahaan AND tj.id_transaksi=OLD.id_transaksi
    ) OR EXISTS (
        SELECT 1 FROM transaksi_jurnal tj
        JOIN jurnal_transaksi j ON j.id_perusahaan=tj.id_perusahaan AND j.id_jurnal=tj.id_jurnal
        JOIN periode_akuntansi p ON p.id_perusahaan=j.id_perusahaan
            AND p.periode>=DATE_FORMAT(j.tanggal,'%Y-%m-01') AND p.status='ditutup'
        WHERE tj.id_perusahaan=NEW.id_perusahaan AND tj.id_transaksi=NEW.id_transaksi
    ) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Penyusutan aset berada pada periode yang ditutup.';
    END IF;
END$$

CREATE TRIGGER cobalance_penyusutan_aset_bd
BEFORE DELETE ON penyusutan_aset
FOR EACH ROW
BEGIN
    IF EXISTS (
        SELECT 1 FROM transaksi_jurnal tj
        JOIN jurnal_transaksi j ON j.id_perusahaan=tj.id_perusahaan AND j.id_jurnal=tj.id_jurnal
        JOIN periode_akuntansi p ON p.id_perusahaan=j.id_perusahaan
            AND p.periode>=DATE_FORMAT(j.tanggal,'%Y-%m-01') AND p.status='ditutup'
        WHERE tj.id_perusahaan=OLD.id_perusahaan AND tj.id_transaksi=OLD.id_transaksi
    ) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Penyusutan aset berada pada periode yang ditutup.';
    END IF;
END$$

CREATE TRIGGER cobalance_pelepasan_aset_bi
BEFORE INSERT ON pelepasan_aset
FOR EACH ROW
BEGIN
    IF EXISTS (
        SELECT 1 FROM transaksi_jurnal tj
        JOIN jurnal_transaksi j ON j.id_perusahaan=tj.id_perusahaan AND j.id_jurnal=tj.id_jurnal
        JOIN periode_akuntansi p ON p.id_perusahaan=j.id_perusahaan
            AND p.periode>=DATE_FORMAT(j.tanggal,'%Y-%m-01') AND p.status='ditutup'
        WHERE tj.id_perusahaan=NEW.id_perusahaan AND tj.id_transaksi=NEW.id_transaksi
    ) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Pelepasan aset berada pada periode yang ditutup.';
    END IF;
END$$

CREATE TRIGGER cobalance_pelepasan_aset_bu
BEFORE UPDATE ON pelepasan_aset
FOR EACH ROW
BEGIN
    IF EXISTS (
        SELECT 1 FROM transaksi_jurnal tj
        JOIN jurnal_transaksi j ON j.id_perusahaan=tj.id_perusahaan AND j.id_jurnal=tj.id_jurnal
        JOIN periode_akuntansi p ON p.id_perusahaan=j.id_perusahaan
            AND p.periode>=DATE_FORMAT(j.tanggal,'%Y-%m-01') AND p.status='ditutup'
        WHERE tj.id_perusahaan=OLD.id_perusahaan AND tj.id_transaksi=OLD.id_transaksi
    ) OR EXISTS (
        SELECT 1 FROM transaksi_jurnal tj
        JOIN jurnal_transaksi j ON j.id_perusahaan=tj.id_perusahaan AND j.id_jurnal=tj.id_jurnal
        JOIN periode_akuntansi p ON p.id_perusahaan=j.id_perusahaan
            AND p.periode>=DATE_FORMAT(j.tanggal,'%Y-%m-01') AND p.status='ditutup'
        WHERE tj.id_perusahaan=NEW.id_perusahaan AND tj.id_transaksi=NEW.id_transaksi
    ) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Pelepasan aset berada pada periode yang ditutup.';
    END IF;
END$$

CREATE TRIGGER cobalance_pelepasan_aset_bd
BEFORE DELETE ON pelepasan_aset
FOR EACH ROW
BEGIN
    IF EXISTS (
        SELECT 1 FROM transaksi_jurnal tj
        JOIN jurnal_transaksi j ON j.id_perusahaan=tj.id_perusahaan AND j.id_jurnal=tj.id_jurnal
        JOIN periode_akuntansi p ON p.id_perusahaan=j.id_perusahaan
            AND p.periode>=DATE_FORMAT(j.tanggal,'%Y-%m-01') AND p.status='ditutup'
        WHERE tj.id_perusahaan=OLD.id_perusahaan AND tj.id_transaksi=OLD.id_transaksi
    ) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Pelepasan aset berada pada periode yang ditutup.';
    END IF;
END$$

CREATE TRIGGER cobalance_aset_tetap_bi
BEFORE INSERT ON aset_tetap
FOR EACH ROW
BEGIN
    IF EXISTS (
        SELECT 1 FROM transaksi_jurnal tj
        JOIN jurnal_transaksi j ON j.id_perusahaan=tj.id_perusahaan AND j.id_jurnal=tj.id_jurnal
        JOIN periode_akuntansi p ON p.id_perusahaan=j.id_perusahaan
            AND p.periode>=DATE_FORMAT(j.tanggal,'%Y-%m-01') AND p.status='ditutup'
        WHERE tj.id_perusahaan=NEW.id_perusahaan AND tj.id_transaksi=NEW.id_transaksi_perolehan
    ) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Aset tetap berasal dari periode yang ditutup.';
    END IF;
END$$

CREATE TRIGGER cobalance_aset_tetap_bu
BEFORE UPDATE ON aset_tetap
FOR EACH ROW
BEGIN
    IF (NOT (OLD.tanggal_perolehan <=> NEW.tanggal_perolehan)
        OR NOT (OLD.tanggal_mulai_penyusutan <=> NEW.tanggal_mulai_penyusutan)
        OR NOT (OLD.nilai_perolehan <=> NEW.nilai_perolehan)
        OR NOT (OLD.nilai_residu <=> NEW.nilai_residu)
        OR NOT (OLD.umur_manfaat_bulan <=> NEW.umur_manfaat_bulan)
        OR NOT (OLD.metode_penyusutan <=> NEW.metode_penyusutan)
        OR NOT (OLD.id_akun_aset <=> NEW.id_akun_aset)
        OR NOT (OLD.id_akun_akumulasi_penyusutan <=> NEW.id_akun_akumulasi_penyusutan)
        OR NOT (OLD.id_akun_beban_penyusutan <=> NEW.id_akun_beban_penyusutan)
        OR NOT (OLD.akumulasi_penyusutan_awal <=> NEW.akumulasi_penyusutan_awal)
        OR NOT (OLD.id_transaksi_perolehan <=> NEW.id_transaksi_perolehan))
       AND EXISTS (
        SELECT 1 FROM transaksi_jurnal tj
        JOIN jurnal_transaksi j ON j.id_perusahaan=tj.id_perusahaan AND j.id_jurnal=tj.id_jurnal
        JOIN periode_akuntansi p ON p.id_perusahaan=j.id_perusahaan
            AND p.periode>=DATE_FORMAT(j.tanggal,'%Y-%m-01') AND p.status='ditutup'
        WHERE tj.id_perusahaan=OLD.id_perusahaan AND tj.id_transaksi=OLD.id_transaksi_perolehan
    ) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Nilai atau akun aset berasal dari periode yang ditutup.';
    END IF;
END$$

CREATE TRIGGER cobalance_aset_tetap_bd
BEFORE DELETE ON aset_tetap
FOR EACH ROW
BEGIN
    IF EXISTS (
        SELECT 1 FROM transaksi_jurnal tj
        JOIN jurnal_transaksi j ON j.id_perusahaan=tj.id_perusahaan AND j.id_jurnal=tj.id_jurnal
        JOIN periode_akuntansi p ON p.id_perusahaan=j.id_perusahaan
            AND p.periode>=DATE_FORMAT(j.tanggal,'%Y-%m-01') AND p.status='ditutup'
        WHERE tj.id_perusahaan=OLD.id_perusahaan AND tj.id_transaksi=OLD.id_transaksi_perolehan
    ) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Aset tetap berasal dari periode yang ditutup.';
    END IF;
END$$

DELIMITER ;
