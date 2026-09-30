<?php
class Usuario {
    public function __construct(private PDO $db) {}
    public function findByEmail(string $email): ?array {
        $q=$this->db->prepare("SELECT u.*, r.nombre rol FROM usuarios u JOIN roles r ON r.id=u.rol_id WHERE u.email=? AND u.activo=1");
        $q->execute([$email]); return $q->fetch() ?: null;
    }
    public function create(string $nombre,string $email,string $password): bool {
        $rol=$this->db->query("SELECT id FROM roles WHERE nombre='Cliente'")->fetchColumn();
        $q=$this->db->prepare("INSERT INTO usuarios(nombre,email,password_hash,rol_id) VALUES(?,?,?,?)");
        return $q->execute([$nombre,$email,password_hash($password,PASSWORD_DEFAULT),$rol]);
    }
    public function all(): array {
        return $this->db->query("SELECT u.id,u.nombre,u.email,r.nombre rol,u.activo,u.creado_en FROM usuarios u JOIN roles r ON r.id=u.rol_id ORDER BY u.id DESC")->fetchAll();
    }
}
