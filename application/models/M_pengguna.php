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
        $this->db->where('p.username !=', 'system');
        $query = $this->db->get();
        return $query->row();
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
