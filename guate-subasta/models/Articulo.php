<?php
class Articulo {
    public function __construct(private PDO $db) {}

    public function all(): array {
        return $this->db->query("SELECT * FROM articulos ORDER BY id DESC")->fetchAll();
    }

    public function disponiblesParaSubasta(): array {
        return $this->db->query("
            SELECT a.* FROM articulos a
            WHERE NOT EXISTS (
                SELECT 1 FROM subastas s
                WHERE s.articulo_id = a.id
                AND s.estado IN ('programada', 'activa')
                AND s.fecha_fin > NOW()
            )
            ORDER BY a.id DESC
        ")->fetchAll();
    }

   public function create(array $d): bool {
        $q = $this->db->prepare("INSERT INTO articulos(nombre,categoria,descripcion,estado_fisico,precio_base,modelo,creado_por)
        VALUES(?,?,?,?,?,?,?)");
        return $q->execute([$d['nombre'],$d['categoria'],$d['descripcion'],$d['estado_fisico'],$d['precio_base'],$d['modelo'],$d['creado_por']]);
    }

    public function eliminar(int $id): array {
        // Verifica si tiene subastas activas o programadas
        $q = $this->db->prepare("SELECT COUNT(*) FROM subastas WHERE articulo_id = ? AND estado IN ('programada','activa') AND fecha_fin > NOW()");
        $q->execute([$id]);
        if ($q->fetchColumn() > 0) {
            return [false, 'No se puede eliminar el artículo porque tiene una subasta en curso.'];
        }
        try {
            $this->db->beginTransaction();
            $this->db->prepare("DELETE FROM pujas WHERE subasta_id IN (SELECT id FROM subastas WHERE articulo_id = ?)")->execute([$id]);
            $this->db->prepare("DELETE FROM subastas WHERE articulo_id = ?")->execute([$id]);
            $this->db->prepare("DELETE FROM articulos WHERE id = ?")->execute([$id]);
            $this->db->commit();
            return [true, 'Artículo y sus registros cerrados fueron eliminados.'];
        } catch (Exception $e) {
            $this->db->rollBack();
            return [false, 'Error al eliminar artículo: ' . $e->getMessage()];
        }
    }
}