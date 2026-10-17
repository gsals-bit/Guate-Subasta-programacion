<?php
class Puja {
    public function __construct(private PDO $db) {}

    public function realizar(int $subastaId, int $usuarioId, float $monto): array {
        try {
            // Actualizacion de estados vencidos antes de procesar la puja
            $this->db->query("UPDATE subastas SET estado='activa' WHERE estado='programada' AND fecha_inicio <= NOW() AND fecha_fin > NOW()");
            $this->db->query("UPDATE subastas SET estado='cerrada' WHERE estado IN ('programada','activa') AND fecha_fin <= NOW()");

            $this->db->beginTransaction();

            // Obtenemos la subasta junto con el creador del artículo y validamos horario con el reloj de MySQL
            $q = $this->db->prepare("
                SELECT s.*, a.creado_por, a.nombre AS articulo_nombre,
                       (NOW() >= s.fecha_inicio AND NOW() <= s.fecha_fin) AS en_horario
                FROM subastas s
                JOIN articulos a ON a.id = s.articulo_id
                WHERE s.id = ?
                FOR UPDATE
            ");
            $q->execute([$subastaId]);
            $s = $q->fetch();

            if (!$s) {
                throw new Exception('Subasta no encontrada.');
            }
            if ((int)$s['creado_por'] === $usuarioId) {
                throw new Exception('No puedes realizar pujas en un artículo que tú mismo registraste.');
            }
            if (in_array($s['estado'], ['cerrada', 'finalizada', 'cancelada'], true) || !$s['en_horario']) {
                throw new Exception('La subasta no está en horario activo o ya finalizó.');
            }

            $min = (float)$s['monto_actual'] + (float)$s['incremento_minimo'];
            if ($monto < $min) {
                throw new Exception('La puja mínima permitida es Q ' . number_format($min, 2));
            }

            // Actualizar monto de la subasta
            $this->db->prepare("UPDATE subastas SET monto_actual=?, estado='activa' WHERE id=?")
                     ->execute([$monto, $subastaId]);

            // Registrar la puja
            $this->db->prepare("INSERT INTO pujas(subasta_id, usuario_id, monto) VALUES(?,?,?)")
                     ->execute([$subastaId, $usuarioId, $monto]);

            // Registrar en auditoria
            $detalleAuditoria = "Subasta #{$subastaId} ({$s['articulo_nombre']}) - Monto: Q " . number_format($monto, 2);
            $this->db->prepare("INSERT INTO auditoria(usuario_id, evento, detalle) VALUES(?,?,?)")
                     ->execute([$usuarioId, 'PUJA', $detalleAuditoria]);

            $this->db->commit();
            return [true, 'Puja registrada exitosamente.'];
        } catch(Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return [false, $e->getMessage()];
        }
    }

    public function historialUsuario(int $uid): array {
        $q = $this->db->prepare("
            SELECT p.*, a.nombre AS articulo, a.categoria, s.estado, s.monto_actual, s.fecha_fin
            FROM pujas p
            JOIN subastas s ON s.id = p.subasta_id
            JOIN articulos a ON a.id = s.articulo_id
            WHERE p.usuario_id = ?
            ORDER BY p.fecha DESC
        ");
        $q->execute([$uid]);
        return $q->fetchAll();
    }

    public function historialSubasta(int $sid): array {
        $q = $this->db->prepare("
            SELECT p.*, u.nombre AS usuario
            FROM pujas p
            JOIN usuarios u ON u.id = p.usuario_id
            WHERE p.subasta_id = ?
            ORDER BY p.monto DESC, p.fecha DESC
        ");
        $q->execute([$sid]);
        return $q->fetchAll();
    }
}