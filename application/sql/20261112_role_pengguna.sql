ALTER TABLE pengguna
    MODIFY role ENUM('admin', 'user', 'kasir', 'penjualan', 'purchasing',
        'gudang', 'keuangan', 'akuntansi', 'manajer') DEFAULT 'user';
