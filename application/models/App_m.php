<?php

class App_M extends CI_Model
{
    function Check_Login()
    {
        $logged_app = $this->session->userdata("logged_app");
        $log_absen = $this->session->userdata("log_absen");
        if ($logged_app == FALSE) {
            redirect(site_url('auth'), "refresh");
        }
    }

    function statusAbsen($id_user)
    {
        if (date('l') == 'Sunday' || date('l') == 'Saturday') {
            return 1;
        } else {
            $absensi = $this->db
                ->get_where('t_absensi', array('tgl_absensi like' => date('Y-m-d') . '%', 'id_user' => $id_user))
                ->result_array();
            return count($absensi);
        }
    }

    function checkMenu($nama_menu = "")
    {
        if ($_SESSION['id_jabatan'] == 0) {
            $this->db->from("s_menu_akses a");
            $this->db->join("s_menu b", "b.id=a.id_menu", "left");
            $this->db->join("m_jabatan c", "c.id_jabatan=a.id_jabatan", "left");
            // $this->db->where("a.id_jabatan", $this->session->userdata("id_jabatan"));
            $this->db->where("b.nama", $nama_menu);
            $query = $this->db->get();

        } else {
            $this->db->from("s_menu_akses a");
            $this->db->join("s_menu b", "b.id=a.id_menu", "left");
            $this->db->join("m_jabatan c", "c.id_jabatan=a.id_jabatan", "left");
            $this->db->where("a.id_jabatan", $this->session->userdata("id_jabatan"));
            $this->db->where("b.nama", $nama_menu);
            $query = $this->db->get();

            if ($query->num_rows() == 0) {
                redirect(site_url() . 'auth/do_Logout');
            }
        }
    }

    function Check_Logout()
    {
        $this->session->sess_destroy();
        redirect(site_url(), "refresh");
    }

    function EncryptPasswd($value)
    {
        $salt = '#*seCrEt!@-*%';
        $str = do_hash($salt . $value);
        $str = do_hash($salt . $str, 'md5');
        return $str;
    }

    function getMenubyUser()
    {
        $menu = array();
        $w_menu['parent_id'] = 0;
        //Get menu untuk Super user.
        if ($_SESSION['id_jabatan'] == 0) {
            $menu = $this->db->order_by('urutan', 'ASC')->get_where('s_menu', $w_menu)->result_array();
            if (count($menu) > 0) {
                for ($i = 0; $i < count($menu); $i++) {
                    $w_child['parent_id'] = $menu[$i]['id'];
                    $menu[$i]['child'] = $this->db->order_by('urutan', 'ASC')->get_where('s_menu', $w_child)->result_array();
                }
            } else {
                redirect(base_url('logout'));
            }
        } else {
            $w_menu['aktif'] = 't';
            $w_menu['id_jabatan'] = $_SESSION['id_jabatan'];
            $menu = $this->db
                ->order_by('urutan', 'ASC')
                ->join('s_menu m', 'm.id=mu.id_menu')
                ->get_where('s_menu_akses mu', $w_menu)
                ->result_array();
            if (count($menu) > 0) {
                for ($i = 0; $i < count($menu); $i++) {
                    $w_child['parent_id'] = $menu[$i]['id'];
                    $w_child['aktif'] = 't';
                    $w_child['id_jabatan'] = $_SESSION['id_jabatan'];
                    $menu[$i]['child'] = $this->db
                        ->order_by('urutan', 'ASC')
                        ->join('s_menu m', 'm.id=mu.id_menu')
                        ->get_where('s_menu_akses mu', $w_child)
                        ->result_array();
                }
            } else {
                redirect(base_url('logout'));
            }
        }
        return $menu;
    }

    function getMenuParentbyChild($child = '')
    {
        $c = $this->db->get_where('s_menu', array('nama' => $child))->result_array();
        if ($c[0]['parent_id'] != 0) {
            $p = $this->db->get_where('s_menu', array('id' => $c[0]['parent_id']))->result_array()[0];
            return $p['nama'];
        } else {
            return '';
        }
    }


    function getDataComboBoxMenu($table = '', $show = '', $ph = "", $id_label = "", $id_selected = "", $filter = array(), $label_set = '')
    {
        $options = array();
        $items = array();
        $filter['aktif'] = 't';
        $this->db->order_by($show, "asc");
        $query = $this->db->get_where($table, $filter);
        if ($query->num_rows() > 0) {
            $i = 0;
            foreach ($query->result() as $row) {
                $i++;
                if ($i == 1) {
                    $items[""] = "";
                }
                $items[$row->$label_set] = $row->$show;
            }
            $options = $items;
        }
        return form_dropdown($id_label, $options, $id_selected, 'id ="' . $id_label . '" Class="form-control form-control-sm select2" style="width: 100%;" data-placeholder="Pilih ' . $ph . '"');
    }

    function getDataComboBox($table = '', $show = '', $ph = "", $id_label = "", $id_selected = "", $filter = array())
    {
        $options = array();
        $items = array();
        // $filter['aktif'] = 't';
        $this->db->order_by($show, "asc");
        $query = $this->db->get_where($table, $filter);
        if ($query->num_rows() > 0) {
            $i = 0;
            foreach ($query->result() as $row) {
                $i++;
                if ($i == 1) {
                    $items[""] = "";
                }
                $items[$row->$id_label] = $row->$show;
            }
            $options = $items;
        }
        return form_dropdown($id_label, $options, $id_selected, 'id ="' . $id_label . '" Class="form-control form-control-sm select2" style="width: 100%;" data-placeholder="Pilih ' . $ph . '"');
    }

    function getDataMultyComboBox($table = '', $show = '', $ph = "", $id_label = "", $id_selected = "", $filter = array())
    {
        $options = array();
        $items = array();
        $filter['aktif'] = 't';
        $this->db->order_by($show, "asc");
        $query = $this->db->get_where($table, $filter);
        if ($query->num_rows() > 0) {
            $i = 0;
            foreach ($query->result() as $row) {
                $i++;
                if ($i == 1) {
                    $items[""] = "";
                }
                $items[$row->id] = $row->$show;
            }
            $options = $items;
        }
        return form_dropdown($id_label, $options, $id_selected, 'id ="' . $id_label . '" Class="form-control form-control-sm select2" multiple="multiple" style="width: 100%;" data-placeholder="Pilih ' . $ph . '"');
    }

    public function getListFilter($table = '', $filter_field = '', $filter_id = '')
    {
        $filter[$filter_field] = $filter_id;
        $query = $this->db->order_by('nama', 'asc')->get_where($table, $filter)->result_array();

        return $query;
    }

    public function convertRp($angka = '')
    {
        if ($angka > 0) {
            $hasil = "Rp. " . number_format($angka, 0, ',', '.');
        } else {
            $hasil = "-";
        }
        return $hasil;
    }
    public function convertK($angka = '')
    {
        $hasil = number_format($angka / 1000, 0, '', '.') . 'K';
        ;

        return $hasil;
    }

    function tglIndo($tanggal)
    {
        $bulan = array(
            1 => 'Januari',
            'Februari',
            'Maret',
            'April',
            'Mei',
            'Juni',
            'Juli',
            'Agustus',
            'September',
            'Oktober',
            'November',
            'Desember'
        );
        $pecahkan = explode('-', $tanggal);

        return $pecahkan[2] . '-' . $bulan[(int) $pecahkan[1]] . '-' . $pecahkan[0];
    }

    function bulanIndo($bulan)
    {
        $bulan_indo = array(
            '01' => 'Januari',
            '02' => 'Februari',
            '03' => 'Maret',
            '04' => 'April',
            '05' => 'Mei',
            '06' => 'Juni',
            '07' => 'Juli',
            '08' => 'Agustus',
            '09' => 'September',
            '10' => 'Oktober',
            '11' => 'November',
            '12' => 'Desember'
        );
        return $bulan_indo[$bulan];
    }

    public function upload_berkas($id, $username, $name)
    {
        $data = array(
            'is_success' => TRUE,
            'message' => null
        );
        $fileName = $_FILES[$id]["name"]; // The file name
        $fileTmpLoc = $_FILES[$id]["tmp_name"]; // File in the PHP tmp folder
        $fileType = $_FILES[$id]["type"]; // The type of file it is
        $fileSize = $_FILES[$id]["size"]; // File size in bytes
        if ($fileTmpLoc) { // if file exist
            $extension_tmp = explode('/', $fileType);
            $extension = end($extension_tmp);
            $extension2_tmp = explode('.', $fileName);
            $extension2 = end($extension2_tmp);
            $permitted = true;
            $reason = '';
            $date = getdate();
            $new_file_name = $name . '_' . $username . '_' . sha1($id . $date['mday'] . '-' . $date['mon'] . '-' . $date['year'] . ':' . $date['hours'] . ':' . $date['minutes'] . ':' . $date['seconds']) . '.' . $extension2;
            //
            //			if($fileSize > 1000000){
            //				$data['is_success'] = FALSE;
            //				$data['message'] = 'File tidak boleh lebih dari 1.0 Mb';
            //			}
            //
            //			if($extension != 'jpg' && $extension != 'png' && $extension != 'jpeg' && $extension != 'pneg'){
            //				$data['is_success'] = FALSE;
            //				$data['message'] = "File bertype '".$extension."' tidak diperbolehkan";
            //			}


            if ($permitted) {
                if (move_uploaded_file($fileTmpLoc, "./assets/dokumen/$id/" . $new_file_name)) {
                    $data['is_success'] = TRUE;
                    $data['file_name'] = $new_file_name;
                } else {
                    $data['is_success'] = FALSE;
                    $data['message'] = 'move_uploaded_file function failed';
                }
            }
        }

        return $data;
    }

}
