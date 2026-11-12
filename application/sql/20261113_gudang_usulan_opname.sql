CREATE TABLE usulan_opname (
    id_usulan_opname INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_perusahaan INT NOT NULL,
    id_produk INT NOT NULL,
    id_pengguna INT NOT NULL,
    jumlah_sistem DECIMAL(18,4) NOT NULL,
    jumlah_fisik DECIMAL(18,4) NOT NULL,
    catatan VARCHAR(255) NOT NULL,
    status ENUM('menunggu','diproses','ditolak') NOT NULL DEFAULT 'menunggu',
    dibuat_pada DATETIME NOT NULL,
    PRIMARY KEY (id_usulan_opname),
    KEY idx_usulan_opname_perusahaan (id_perusahaan, status, id_usulan_opname),
    KEY idx_usulan_opname_produk (id_perusahaan, id_produk)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
