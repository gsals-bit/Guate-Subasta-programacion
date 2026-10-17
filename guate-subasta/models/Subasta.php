<?php
class Subasta {
    public function __construct(private PDO $db) {}

    public function actualizarEstados(): void {
        // Pasa a activa las programadas que ya iniciaron
        $this->db->query("UPDATE subastas SET estado='activa' WHERE estado='programada' AND fecha_inicio <= NOW() AND fecha_fin > NOW()");
        // Pasa a cerrada las que ya vencieron por fecha
        $this->db->query("UPDATE subastas SET estado='cerrada' WHERE estado IN ('programada','activa') AND fecha_fin <= NOW()");
    }

    public function activas(): array {
        $this->actualizarEstados();
        // Incluye total de pujas y el usuario con la oferta más alta (líder/ganador)
        return $this->db->query("
            SELECT s.*, a.nombre AS articulo, a.descripcion, a.precio_base, a.imagen, a.categoria, a.creado_por,
                   (SELECT COUNT(*) FROM pujas p WHERE p.subasta_id = s.id) AS total_pujas,
                   (SELECT u.nombre FROM pujas p JOIN usuarios u ON u.id = p.usuario_id WHERE p.subasta_id = s.id ORDER BY p.monto DESC, p.fecha ASC LIMIT 1) AS lider_nombre
            FROM subastas s
            JOIN articulos a ON a.id = s.articulo_id
            ORDER BY FIELD(s.estado, 'activa', 'programada', 'cerrada'), s.fecha_fin ASC
        ")->fetchAll();
    }

    public function find(int $id): ?array {
        $this->actualizarEstados();
        $q = $this->db->prepare("
            SELECT s.*, a.nombre AS articulo, a.descripcion, a.precio_base, a.imagen, a.categoria, a.certificado, a.creado_por
            FROM subastas s
            JOIN articulos a ON a.id = s.articulo_id
            WHERE s.id = ?
        ");
        $q->execute([$id]);
        return $q->fetch() ?: null;
    }

    public function crear(int $articulo, string $inicio, string $fin, float $inc): bool {
        if (strtotime($fin) <= strtotime($inicio)) {
            return false;
        }
        // Evitar duplicar subasta activa o programada para el mismo artículo
        $chk = $this->db->prepare("SELECT COUNT(*) FROM subastas WHERE articulo_id = ? AND estado IN ('programada','activa') AND fecha_fin > NOW()");
        $chk->execute([$articulo]);
        if ($chk->fetchColumn() > 0) {
            return false;
        }

        $q = $this->db->prepare("
            INSERT INTO subastas(articulo_id, fecha_inicio, fecha_fin, incremento_minimo, monto_actual, estado)
            SELECT id, ?, ?, ?, precio_base, 'programada' FROM articulos WHERE id = ?
        ");
        $ok = $q->execute([$inicio, $fin, $inc, $articulo]);
        $this->actualizarEstados();
        return $ok;
    }

    public function eliminarCerrada(int $id): array {
        $this->actualizarEstados();
        $subasta = $this->find($id);
        if (!$subasta) {
            return [false, 'La subasta no existe.'];
        }
        if ($subasta['estado'] !== 'cerrada' && strtotime($subasta['fecha_fin']) > time()) {
            return [false, 'No puedes eliminar una subasta que aún está activa o programada.'];
        }

        try {
            $this->db->beginTransaction();
            $delPujas = $this->db->prepare("DELETE FROM pujas WHERE subasta_id = ?");
            $delPujas->execute([$id]);

            $delSubasta = $this->db->prepare("DELETE FROM subastas WHERE id = ?");
            $delSubasta->execute([$id]);

            $this->db->commit();
            return [true, 'Subasta cerrada eliminada correctamente.'];
        } catch (Exception $e) {
            $this->db->rollBack();
            return [false, 'Error al eliminar la subasta: ' . $e->getMessage()];
        }
    }
}