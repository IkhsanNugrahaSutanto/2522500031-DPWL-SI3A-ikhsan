<?php
class Home extends Controller
{
    public function index(): void
    {
        $data = [
            'judul' => 'Fondasi MVC DPWL',
            'pesan' => 'Request telah melewati front controller, Router, Controller, dan View.'
        ];
        $this->view('home/index', $data);
    }

    public function info(string $topik = 'mvc'): void
    {
        $this->view('home/info', ['topik' => $topik]);
    }

    public function project(string $nim = '2522500031'): void
    {
        $data = [
            'title' => 'Detail project',
            'nim'   => $nim,
            'nama'  => 'Ikhsan Nugraha Sutanto',
            'kelas' => 'SI3A'
        ];
        $this->view('home/project', $data);
    }
}