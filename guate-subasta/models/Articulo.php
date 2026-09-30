<?php
class Articulo {
    public function __construct(private PDO $db) {}
    public function all(): array { return $this->db->query("SELECT * FROM articulos ORDER BY id DESC")->fetchAll(); }
    public function create(array $d): bool {
        $q=$this->db->prepare("INSERT INTO articulos(nombre,categoria,descripcion,estado_fisico,precio_base,certificado,creado_por)
        VALUES(?,?,?,?,?,?,?)");
        return $q->execute([$d['nombre'],$d['categoria'],$d['descripcion'],$d['estado_fisico'],$d['precio_base'],$d['certificado'],$d['creado_por']]);
    }
}
