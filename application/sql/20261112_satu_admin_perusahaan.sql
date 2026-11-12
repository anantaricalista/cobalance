ALTER TABLE pengguna
    ADD COLUMN admin_perusahaan_slot TINYINT
        GENERATED ALWAYS AS (CASE WHEN role = 'admin' THEN 1 ELSE NULL END) STORED,
    ADD UNIQUE KEY uq_satu_admin_perusahaan (id_perusahaan, admin_perusahaan_slot);
