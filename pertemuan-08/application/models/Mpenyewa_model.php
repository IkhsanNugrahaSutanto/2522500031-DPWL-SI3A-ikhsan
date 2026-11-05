<?php
class Mpenyewa_model
{
    private mysqli $db;
    public function __construct() { global $db; $this->db = $db; }

    public function getAll(): array
    {
        $stmt = $this->db->prepare("SELECT user_plg, nik, nm_lngkp, no_hp, almt_lngkp, ft_ktp, ft_sim_a FROM t_penyewa ORDER BY nm_lngkp ASC");
        $stmt->execute(); $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); $stmt->close();
        return $rows;
    }
    public function find(string $username): ?array
    {
        $stmt = $this->db->prepare("SELECT user_plg, sandi, nik, nm_lngkp, no_hp, almt_lngkp, ft_ktp, ft_sim_a FROM t_penyewa WHERE user_plg=? LIMIT 1");
        $stmt->bind_param('s', $username); $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc(); $stmt->close(); return $row ?: null;
    }
    public function create(array $d): bool
    {
        $stmt = $this->db->prepare("INSERT INTO t_penyewa (user_plg,sandi,nik,nm_lngkp,no_hp,almt_lngkp,ft_ktp,ft_sim_a) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->bind_param('ssssssss',$d['user_plg'],$d['sandi'],$d['nik'],$d['nm_lngkp'],$d['no_hp'],$d['almt_lngkp'],$d['ft_ktp'],$d['ft_sim_a']);
        $ok=$stmt->execute(); $stmt->close(); return $ok;
    }
    public function update(string $username, array $d, ?string $hash): bool
    {
        if ($hash !== null) {
            $stmt=$this->db->prepare("UPDATE t_penyewa SET sandi=?, nik=?, nm_lngkp=?, no_hp=?, almt_lngkp=?, ft_ktp=?, ft_sim_a=? WHERE user_plg=?");
            $stmt->bind_param('ssssssss',$hash,$d['nik'],$d['nm_lngkp'],$d['no_hp'],$d['almt_lngkp'],$d['ft_ktp'],$d['ft_sim_a'],$username);
        } else {
            $stmt=$this->db->prepare("UPDATE t_penyewa SET nik=?, nm_lngkp=?, no_hp=?, almt_lngkp=?, ft_ktp=?, ft_sim_a=? WHERE user_plg=?");
            $stmt->bind_param('sssssss',$d['nik'],$d['nm_lngkp'],$d['no_hp'],$d['almt_lngkp'],$d['ft_ktp'],$d['ft_sim_a'],$username);
        }
        $ok=$stmt->execute(); $stmt->close(); return $ok;
    }
    public function delete(string $username): bool
    {
        $stmt=$this->db->prepare("DELETE FROM t_penyewa WHERE user_plg=?"); $stmt->bind_param('s',$username);
        $ok=$stmt->execute() && $stmt->affected_rows>0; $stmt->close(); return $ok;
    }
}
