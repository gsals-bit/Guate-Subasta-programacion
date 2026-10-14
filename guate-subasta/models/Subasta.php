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
        // Incluimos también las cerradas para que se puedan visualizar y eliminar
        return $this->db->query("SELECT s.*,a.nombre articulo,a.descripcion,a.precio_base,a.imagen,a.categoria,a.creado_por
          FROM subastas s JOIN articulos a ON a.id=s.articulo_id
          ORDER BY FIELD(s.estado, 'activa', 'programada', 'cerrada'), s.fecha_fin ASC")->fetchAll();
    }

    public function find(int $id): ?array {
        $this->actualizarEstados();
        $q=$this->db->prepare("SELECT s.*,a.nombre articulo,a.descripcion,a.precio_base,a.imagen,a.categoria,a.certificado,a.creado_por
          FROM subastas s JOIN articulos a ON a.id=s.articulo_id WHERE s.id=?");
        $q->execute([$id]); return $q->fetch() ?: null;
    }

    public function crear(int $articulo,string $inicio,string $fin,float $inc): bool {
        $q=$this->db->prepare("INSERT INTO subastas(articulo_id,fecha_inicio,fecha_fin,incremento_minimo,monto_actual,estado)
        SELECT id,?,?,?,precio_base,'programada' FROM articulos WHERE id=?");
        return $q->execute([$inicio,$fin,$inc,$articulo]);
    }

    public function eliminarCerrada(int $id): array {
        $this->actualizarEstados();
        $subasta = $this->find($id);
        if (!$subasta) {
            return [false, 'La subasta no existe.'];
        }
        // Verificamos si está cerrada o si su fecha límite ya pasó
        if ($subasta['estado'] !== 'cerrada' && strtotime($subasta['fecha_fin']) > time()) {
            return [false, 'No puedes eliminar una subasta que aún está activa o programada.'];
        }

        try {
            $this->db->beginTransaction();
            // 1. Eliminamos primero las pujas para evitar el bloqueo de llave foránea en MySQL
            $delPujas = $this->db->prepare("DELETE FROM pujas WHERE subasta_id = ?");
            $delPujas->execute([$id]);

            // 2. Eliminamos la subasta
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
