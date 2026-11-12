<?php
defined('BASEPATH') or exit('No direct script access allowed');

class M_perusahaan_system extends CI_Model
{
    public function daftar()
    {
        return $this->db->query("
            SELECT p.id_perusahaan, p.nama_perusahaan, p.email, p.telepon,
                u.username, u.nama_lengkap
            FROM perusahaan p
            LEFT JOIN pengguna u ON u.id_perusahaan = p.id_perusahaan AND u.role = 'admin'
            ORDER BY p.id_perusahaan DESC
        ")->result();
    }

    public function buat($input, $password_hash)
    {
        $debug = $this->db->db_debug;
        $this->db->db_debug = false;
        if (!$this->db->trans_begin()) {
            $this->db->db_debug = $debug;
            throw new RuntimeException('Tidak dapat memulai transaksi database.');
        }
        try {
            $this->query('SELECT admin_perusahaan_slot FROM pengguna LIMIT 1');
            $template = $this->query('SELECT id_perusahaan FROM perusahaan WHERE id_perusahaan = ? FOR UPDATE', array(1));
            if (!$template->row()) {
                throw new DomainException('Perusahaan template ID 1 tidak ditemukan.');
            }
            $accounts = $this->query('SELECT id_akun, kode_akun, nama_akun, tipe_akun, id_akun_induk
                FROM akun WHERE id_perusahaan = ? ORDER BY id_akun FOR UPDATE', array(1))->result();
            if (!$accounts) {
                throw new DomainException('Master akun perusahaan template ID 1 kosong.');
            }
            $this->validate_hierarchy($accounts);

            if ($this->query('SELECT id_pengguna FROM pengguna WHERE username = ? LIMIT 1', array($input['username']))->row()) {
                throw new DomainException('Username sudah digunakan. Pilih username lain.');
            }
            $this->write('perusahaan', array(
                'nama_perusahaan' => $input['nama_perusahaan'],
                'alamat' => $input['alamat'] === '' ? null : $input['alamat'],
                'email' => $input['email'] === '' ? null : $input['email'],
                'telepon' => $input['telepon'] === '' ? null : $input['telepon']
            ));
            $company = $this->db->insert_id();
            if (!$company) {
                throw new RuntimeException('ID perusahaan tidak tersedia.');
            }
            $admin = array(
                'id_perusahaan' => $company,
                'nama_lengkap' => $input['nama_admin'],
                'username' => $input['username'],
                'password' => $password_hash,
                'role' => 'admin'
            );
            if (!$this->db->insert('pengguna', $admin)) {
                $error = $this->db->error();
                if ((int) $error['code'] === 1062) {
                    throw new DomainException('Username sudah digunakan. Pilih username lain.');
                }
                throw new RuntimeException('Gagal menyimpan admin perusahaan (kode ' . $error['code'] . ').');
            }
            $map = array();
            foreach ($accounts as $account) {
                $this->write('akun', array(
                    'id_perusahaan' => $company,
                    'kode_akun' => $account->kode_akun,
                    'nama_akun' => $account->nama_akun,
                    'tipe_akun' => $account->tipe_akun,
                    'id_akun_induk' => null
                ));
                $map[$account->id_akun] = $this->db->insert_id();
                if (!$map[$account->id_akun]) {
                    throw new RuntimeException('ID akun baru tidak tersedia.');
                }
            }
            foreach ($accounts as $account) {
                if ($account->id_akun_induk !== null && (string) $account->id_akun_induk !== '0') {
                    $updated = $this->db->where('id_perusahaan', $company)
                        ->where('id_akun', $map[$account->id_akun])
                        ->update('akun', array('id_akun_induk' => $map[$account->id_akun_induk]));
                    if (!$updated) {
                        throw new RuntimeException('Gagal menghubungkan hierarki akun.');
                    }
                }
            }
            if (!$this->db->trans_status() || !$this->db->trans_commit()) {
                throw new RuntimeException('Gagal menyelesaikan transaksi database.');
            }
        } catch (Throwable $e) {
            if (!$this->db->trans_rollback()) {
                throw new RuntimeException('Rollback database gagal.', 0, $e);
            }
            throw $e;
        } finally {
            $this->db->db_debug = $debug;
        }
    }

    private function validate_hierarchy($accounts)
    {
        $parents = array();
        foreach ($accounts as $account) {
            $parents[$account->id_akun] = $account->id_akun_induk;
        }
        foreach ($parents as $id => $parent) {
            $seen = array($id => true);
            while ($parent !== null && (string) $parent !== '0') {
                if (!array_key_exists($parent, $parents) || isset($seen[$parent])) {
                    throw new DomainException('Hierarki akun template tidak valid. Periksa akun induk perusahaan ID 1.');
                }
                $seen[$parent] = true;
                $parent = $parents[$parent];
            }
        }
    }

    private function query($sql, $params = array())
    {
        $result = $this->db->query($sql, $params);
        if (!$result) {
            $error = $this->db->error();
            throw new RuntimeException('Query database gagal (kode ' . $error['code'] . ').');
        }
        return $result;
    }

    private function write($table, $data)
    {
        if (!$this->db->insert($table, $data)) {
            $error = $this->db->error();
            throw new RuntimeException('Gagal menyimpan ' . $table . ' (kode ' . $error['code'] . ').');
        }
    }
}
