-- Pastikan tidak ada role user, NULL, atau nilai role tidak valid sebelum menjalankan.
-- Mode ketat mencegah konversi diam-diam jika masih ada nilai yang tidak cocok.
SET @role_user_sql_mode = @@SESSION.sql_mode;
SET SESSION sql_mode = CONCAT_WS(',', @@SESSION.sql_mode, 'STRICT_ALL_TABLES');

ALTER TABLE pengguna
    MODIFY role ENUM('admin', 'kasir', 'penjualan', 'purchasing',
        'gudang', 'keuangan', 'akuntansi', 'manajer') NOT NULL;

SET SESSION sql_mode = @role_user_sql_mode;
