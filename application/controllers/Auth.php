<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Auth extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->helper('url');
        $this->load->library('session');
    }

    public function login()
    {
        if ($this->session->userdata('user')) {
            redirect(base_url('index.php/dashboard'));
        }

        $error = null;
        if ($this->input->post('username')) {
            $username = $this->input->post('username');
            $password = $this->input->post('password');

            $query = $this->db->get_where('users', ['username' => $username]);
            $user = $query->row_array();

            if ($user && password_verify($password, $user['password'])) {
                $this->session->set_userdata('user', [
                    'id'       => $user['id'],
                    'username' => $user['username'],
                    'nama'     => $user['nama'],
                    'role'     => $user['role'],
                ]);
                redirect(base_url('index.php/dashboard'));
            }
            $error = 'Username atau password salah.';
        }

        $data['error'] = $error;
        $data['nama_rs'] = $this->get_setting('nama_rs', 'SIMRS');
        $this->load->view('auth/login', $data);
    }

    public function logout()
    {
        $this->session->unset_userdata('user');
        $this->session->sess_destroy();
        redirect(base_url('index.php/auth/login'));
    }

    private function get_setting($key, $default = '')
    {
        $query = $this->db->get_where('pengaturan', ['kunci' => $key]);
        $row = $query->row_array();
        return $row ? $row['nilai'] : $default;
    }
}
