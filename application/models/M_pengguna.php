<?php
defined('BASEPATH') or exit('No direct script access allowed');

class M_pengguna extends CI_Model
{

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    // Ambil data pengguna berdasarkan username
    public function get_by_username($username)
    {
        $this->db->select('p.*, perusahaan.nama_perusahaan');
        $this->db->from('pengguna p');
        $this->db->join('perusahaan', 'p.id_perusahaan = perusahaan.id_perusahaan', 'left');
        $this->db->where('p.username', $username);
        $query = $this->db->get();
        return $query->row();
    }

    public function is_system_admin($id)
    {
        return $this->db->where('id_pengguna', $id)
            ->where('username', 'system')
            ->where('id_perusahaan', null)
            ->where('role', 'admin')
            ->count_all_results('pengguna') === 1;
    }

    public function user_list($company, $search, $start, $length, $order, $direction)
    {
        $columns = array('nama_lengkap', 'username', 'role');
        $order_by = isset($columns[$order]) ? $columns[$order] : 'nama_lengkap';
        $this->db->select('id_pengguna, nama_lengkap, username, role')
            ->from('pengguna')
            ->where('id_perusahaan', $company);
        if ($search !== '') {
            $this->db->group_start()
                ->like('nama_lengkap', $search)
                ->or_like('username', $search)
                ->group_end();
        }
        $this->db->order_by($order_by, $direction === 'desc' ? 'desc' : 'asc');
        if ($length !== -1) {
            $this->db->limit($length, $start);
        }
        return $this->db->get()->result();
    }

    public function user_count($company, $search = '')
    {
        $this->db->from('pengguna')->where('id_perusahaan', $company);
        if ($search !== '') {
            $this->db->group_start()
                ->like('nama_lengkap', $search)
                ->or_like('username', $search)
                ->group_end();
        }
        return $this->db->count_all_results();
    }

    public function get_company_user($company, $id)
    {
        return $this->db->select('id_pengguna, nama_lengkap, username, role')
            ->where('id_perusahaan', $company)
            ->where('id_pengguna', $id)
            ->get('pengguna')->row();
    }

    public function save_company_user($company, $data, $id = null)
    {
        $debug = $this->db->db_debug;
        $this->db->db_debug = false;
        if (!$this->db->trans_begin()) {
            $this->db->db_debug = $debug;
            throw new RuntimeException('Tidak dapat memulai transaksi database.');
        }

        try {
            $company_row = $this->db->query(
                'SELECT id_perusahaan FROM perusahaan WHERE id_perusahaan = ? FOR UPDATE',
                array($company)
            );
            if (!$company_row || !$company_row->row()) {
                throw new DomainException('Perusahaan tidak ditemukan.');
            }

            $target = null;
            if ($id !== null) {
                $query = $this->db->query(
                    'SELECT id_pengguna, username, role FROM pengguna
                    WHERE id_perusahaan = ? AND id_pengguna = ? FOR UPDATE',
                    array($company, $id)
                );
                if (!$query) {
                    throw new RuntimeException('Tidak dapat membaca pengguna.');
                }
                $target = $query->row();
                if (!$target) {
                    throw new DomainException('Pengguna tidak ditemukan pada perusahaan ini.');
                }
            }

            $duplicate = $this->db->query(
                'SELECT id_pengguna FROM pengguna WHERE username = ? AND (? IS NULL OR id_pengguna != ?) LIMIT 1',
                array($data['username'], $id, $id)
            );
            if (!$duplicate) {
                throw new RuntimeException('Tidak dapat memeriksa username.');
            }
            if ($duplicate->row()) {
                throw new DomainException('Username sudah digunakan.');
            }

            $admins = $this->db->query(
                "SELECT id_pengguna FROM pengguna
                WHERE id_perusahaan = ? AND role = 'admin' FOR UPDATE",
                array($company)
            );
            if (!$admins) {
                throw new RuntimeException('Tidak dapat memeriksa admin perusahaan.');
            }
            $admin_rows = $admins->result();
            if (!$admin_rows && $data['role'] !== 'admin') {
                throw new DomainException('Perusahaan harus memiliki satu admin. Pilih role admin untuk pengguna ini.');
            }
            if ($data['role'] === 'user' && $target && $target->role === 'admin' && count($admin_rows) <= 1) {
                throw new DomainException('Perusahaan harus memiliki satu admin. Tetapkan admin pengganti sebelum mengubah role ini.');
            }

            if ($data['role'] === 'admin') {
                $demote = $this->db->query(
                    "UPDATE pengguna SET role = 'user'
                    WHERE id_perusahaan = ? AND role = 'admin' AND id_pengguna != ?",
                    array($company, $id === null ? 0 : $id)
                );
                if (!$demote) {
                    throw new RuntimeException('Gagal memindahkan role admin.');
                }
            }

            if ($id === null) {
                $data['id_perusahaan'] = $company;
                $success = $this->db->insert('pengguna', $data);
            } else {
                $success = $this->db->where('id_perusahaan', $company)
                    ->where('id_pengguna', $id)
                    ->update('pengguna', $data);
            }
            if (!$success) {
                $error = $this->db->error();
                if ((int) $error['code'] === 1062) {
                    throw new DomainException('Username sudah digunakan atau admin perusahaan sudah ditetapkan.');
                }
                throw new RuntimeException('Gagal menyimpan pengguna (kode ' . $error['code'] . ').');
            }
            if (!$this->db->trans_status() || !$this->db->trans_commit()) {
                throw new RuntimeException('Gagal menyelesaikan perubahan pengguna.');
            }
        } catch (Throwable $e) {
            if (!$this->db->trans_rollback()) {
                throw new RuntimeException('Rollback perubahan pengguna gagal.', 0, $e);
            }
            throw $e;
        } finally {
            $this->db->db_debug = $debug;
        }
    }

    public function delete_company_user($company, $id, $actor_id)
    {
        $debug = $this->db->db_debug;
        $this->db->db_debug = false;
        if (!$this->db->trans_begin()) {
            $this->db->db_debug = $debug;
            throw new RuntimeException('Tidak dapat memulai transaksi database.');
        }

        try {
            $company_row = $this->db->query(
                'SELECT id_perusahaan FROM perusahaan WHERE id_perusahaan = ? FOR UPDATE',
                array($company)
            );
            if (!$company_row || !$company_row->row()) {
                throw new DomainException('Perusahaan tidak ditemukan.');
            }
            $query = $this->db->query(
                'SELECT id_pengguna, role FROM pengguna
                WHERE id_perusahaan = ? AND id_pengguna = ? FOR UPDATE',
                array($company, $id)
            );
            if (!$query) {
                throw new RuntimeException('Tidak dapat membaca pengguna.');
            }
            $user = $query->row();
            if (!$user) {
                throw new DomainException('Pengguna tidak ditemukan pada perusahaan ini.');
            }
            if ($user->role === 'admin' && (int) $user->id_pengguna === (int) $actor_id) {
                throw new DomainException('Admin perusahaan tidak dapat menghapus akunnya sendiri. Tetapkan admin pengganti dan masuk dengan akun tersebut terlebih dahulu.');
            }
            if ($user->role === 'admin') {
                $admin_query = $this->db->query(
                    "SELECT COUNT(*) AS total FROM pengguna WHERE id_perusahaan = ? AND role = 'admin'",
                    array($company)
                );
                if (!$admin_query) {
                    throw new RuntimeException('Tidak dapat memeriksa admin perusahaan.');
                }
                $admins = $admin_query->row();
                if ((int) $admins->total <= 1) {
                    throw new DomainException('Admin terakhir perusahaan tidak dapat dihapus.');
                }
            }
            $deleted = $this->db->where('id_perusahaan', $company)
                ->where('id_pengguna', $id)->delete('pengguna');
            if (!$deleted) {
                $error = $this->db->error();
                throw new RuntimeException('Gagal menghapus pengguna (kode ' . $error['code'] . ').');
            }
            if (!$this->db->trans_status() || !$this->db->trans_commit()) {
                throw new RuntimeException('Gagal menyelesaikan penghapusan pengguna.');
            }
        } catch (Throwable $e) {
            if (!$this->db->trans_rollback()) {
                throw new RuntimeException('Rollback penghapusan pengguna gagal.', 0, $e);
            }
            throw $e;
        } finally {
            $this->db->db_debug = $debug;
        }
    }

    public function upgrade_password($id, $old_hash, $new_hash)
    {
        $result = $this->db->query(
            'UPDATE pengguna SET password = ? WHERE id_pengguna = ? AND BINARY password = BINARY ?',
            array($new_hash, $id, $old_hash)
        );
        return $result && $this->db->affected_rows() === 1;
    }

    public function allow_login_attempt($ip)
    {
        if (random_int(1, 100) === 1) {
            $cleanup = $this->db->query(
                'DELETE FROM login_attempt WHERE window_start < DATE_SUB(UTC_TIMESTAMP(), INTERVAL 1 DAY)'
            );
            if (!$cleanup) {
                show_error('Perlindungan login tidak tersedia. Hubungi administrator.', 503);
                return false;
            }
        }

        $updated = $this->db->query("
            INSERT INTO login_attempt (ip_address, window_start, attempts)
            VALUES (?, UTC_TIMESTAMP(), 1)
            ON DUPLICATE KEY UPDATE
                attempts = IF(window_start <= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 15 MINUTE), 1, LEAST(attempts + 1, 11)),
                window_start = IF(window_start <= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 15 MINUTE), UTC_TIMESTAMP(), window_start)
        ", array($ip));
        if (!$updated) {
            show_error('Perlindungan login tidak tersedia. Hubungi administrator.', 503);
            return false;
        }
        $query = $this->db->query('SELECT attempts FROM login_attempt WHERE ip_address = ?', array($ip));
        if (!$query || !$query->row()) {
            show_error('Perlindungan login tidak tersedia. Hubungi administrator.', 503);
            return false;
        }
        return (int) $query->row()->attempts <= 10;
    }
}
