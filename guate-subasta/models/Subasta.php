<?php
class Subasta {
    public function __construct(private PDO $db) {}
    public function activas(): array {
        return $this->db->query("SELECT s.*,a.nombre articulo,a.descripcion,a.precio_base,a.imagen,a.categoria
          FROM subastas s JOIN articulos a ON a.id=s.articulo_id
          WHERE s.estado IN ('programada','activa') ORDER BY s.fecha_fin ASC")->fetchAll();
    }
    public function find(int $id): ?array {
        $q=$this->db->prepare("SELECT s.*,a.nombre articulo,a.descripcion,a.precio_base,a.imagen,a.categoria,a.certificado
          FROM subastas s JOIN articulos a ON a.id=s.articulo_id WHERE s.id=?");
        $q->execute([$id]); return $q->fetch() ?: null;
    }
    public function crear(int $articulo,string $inicio,string $fin,float $inc): bool {
        $q=$this->db->prepare("INSERT INTO subastas(articulo_id,fecha_inicio,fecha_fin,incremento_minimo,monto_actual,estado)
        SELECT id,?,?,?,precio_base,'programada' FROM articulos WHERE id=?");
        return $q->execute([$inicio,$fin,$inc,$articulo]);
    }
}
