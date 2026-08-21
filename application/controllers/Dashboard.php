<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Dashboard extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->helper('url');
        $this->load->library('session');
        $this->check_login();
    }

    public function index()
    {
        $data['totalPasien'] = $this->db->count_all('pasien');
        $data['totalDokter'] = $this->db->count_all_results('dokter', ['aktif' => 1]);
        $data['totalObat'] = $this->db->count_all('obat');
        $data['kunjunganHari'] = $this->db->where('DATE(tanggal)', date('Y-m-d'))->count_all_results('pendaftaran');
        $data['antrianMenunggu'] = $this->db->where('status', 'menunggu')->count_all_results('pendaftaran');
        $data['tagihanBelum'] = $this->db->select_sum('total')->where('status', 'belum')->get('tagihan')->row()->total ?: 0;

        $this->load->view('templates/header', $data);
        $this->load->view('dashboard/index', $data);
        $this->load->view('templates/footer');
    }

    private function check_login()
    {
        if (!$this->session->userdata('user')) {
            redirect(base_url('index.php/auth/login'));
        }
    }
}
