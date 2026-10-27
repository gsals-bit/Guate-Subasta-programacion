<?php
class Articulo {
    public function __construct(private PDO $db) {}

    public function all(?int $ownerId = null): array {
        // Sincronizar estados de subastas antes de consultar
        $this->db->query("UPDATE subastas SET estado='activa' WHERE estado='programada' AND fecha_inicio <= NOW() AND fecha_fin > NOW()");
        $this->db->query("UPDATE subastas SET estado='cerrada' WHERE estado IN ('programada','activa') AND fecha_fin <= NOW()");

        $sql = "
            SELECT a.*,
                   (SELECT s.id FROM subastas s WHERE s.articulo_id = a.id ORDER BY s.id DESC LIMIT 1) AS subasta_id,
                   (SELECT s.estado FROM subastas s WHERE s.articulo_id = a.id ORDER BY s.id DESC LIMIT 1) AS subasta_estado,
                   (SELECT s.fecha_fin FROM subastas s WHERE s.articulo_id = a.id ORDER BY s.id DESC LIMIT 1) AS subasta_fecha_fin
            FROM articulos a
            " . ($ownerId !== null ? " WHERE a.creado_por = ? AND a.archivado=0 " : " WHERE a.archivado=0 ") . " ORDER BY a.id DESC";
        $q = $this->db->prepare($sql);
        $q->execute($ownerId !== null ? [$ownerId] : []);
        return $q->fetchAll();
    }

    public function disponiblesParaSubasta(int $ownerId): array {
        $q = $this->db->prepare("
            SELECT a.* FROM articulos a
            WHERE a.creado_por = ? AND a.archivado=0 AND NOT EXISTS (
                SELECT 1 FROM subastas s
                WHERE s.articulo_id = a.id
                AND s.estado IN ('programada', 'activa')
                AND s.fecha_fin > NOW()
            )
            ORDER BY a.id DESC
        ");
        $q->execute([$ownerId]);
        return $q->fetchAll();
    }

    public function create(array $d): bool {
        $q = $this->db->prepare("INSERT INTO articulos(nombre,categoria,descripcion,estado_fisico,precio_base,modelo,imagen,creado_por,ubicacion_nombre,ubicacion_lat,ubicacion_lng)
        VALUES(?,?,?,?,?,?,?,?,?,?,?)");
        return $q->execute([
            $d['nombre'], $d['categoria'], $d['descripcion'], $d['estado_fisico'],
            $d['precio_base'], $d['modelo'], $d['imagen'], $d['creado_por'], $d['ubicacion_nombre'] ?: null, $d['ubicacion_lat'], $d['ubicacion_lng']
        ]);
    }

    public function find(int $id): ?array {
        $q = $this->db->prepare("SELECT * FROM articulos WHERE id = ?");
        $q->execute([$id]);
        return $q->fetch() ?: null;
    }

    public function eliminar(int $id): array {
        $q = $this->db->prepare("SELECT COUNT(*) FROM subastas WHERE articulo_id = ? AND estado IN ('programada','activa') AND fecha_fin > NOW()");
        $q->execute([$id]);
        if ($q->fetchColumn() > 0) {
            return [false, 'No se puede eliminar el artículo porque tiene una subasta en curso.'];
        }
        $compras = $this->db->prepare("SELECT COUNT(*) FROM compras_tokens c JOIN subastas s ON s.id=c.subasta_id WHERE s.articulo_id=?");
        $compras->execute([$id]);
        if ($compras->fetchColumn() > 0) return [false, 'El artículo tiene una compra de prueba registrada y no puede eliminarse.'];
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