<?php
/** Comprobante academico de compras con tarjeta ficticia; NO documento FEL. */
class FacturaDemo {
    public function __construct(private PDO $db) {}

    public function obtener(int $subastaId, int $usuarioId, bool $admin=false): ?array {
        if ($subastaId <= 0 || $usuarioId <= 0) return null;
        $q=$this->db->prepare("SELECT s.id AS subasta_id, s.fecha_fin, a.nombre AS articulo,
            a.descripcion, a.categoria, a.modelo, a.estado_fisico,
            u.nombre AS comprador, u.email AS comprador_email,
            op.id AS orden_id, op.usuario_id AS comprador_id,
            op.monto AS total, op.comision, op.referencia_demo,
            pg.id AS pago_id, pg.fecha AS fecha_pago, pg.metodo
          FROM ordenes_pago op
          JOIN subastas s ON s.id=op.subasta_id
          JOIN articulos a ON a.id=s.articulo_id
          JOIN usuarios u ON u.id=op.usuario_id
          JOIN pagos pg ON pg.orden_id=op.id
          WHERE op.subasta_id=? AND op.estado='pagado'
            AND pg.metodo='TARJETA_DEMO' AND pg.estado='aprobado'
          ORDER BY pg.id DESC LIMIT 1");
        $q->execute([$subastaId]);
        $fila=$q->fetch();
        if (!$fila || (!$admin && (int)$fila['comprador_id']!==$usuarioId)) return null;
        return $fila;
    }

    /** Vista publica: datos minimos, sin correo ni identidad del comprador. */
    public function verificar(string $referencia): ?array {
        if (!preg_match('/^TARJETA-DEMO-[A-F0-9]{10,}$/', $referencia)) return null;
        $q=$this->db->prepare("SELECT op.referencia_demo, op.monto AS total, pg.fecha AS fecha_pago,
              a.nombre AS articulo
          FROM ordenes_pago op
          JOIN pagos pg ON pg.orden_id=op.id
          JOIN subastas s ON s.id=op.subasta_id
          JOIN articulos a ON a.id=s.articulo_id
          WHERE op.referencia_demo=? AND op.estado='pagado'
            AND pg.metodo='TARJETA_DEMO' AND pg.estado='aprobado'
          ORDER BY pg.id DESC LIMIT 1");
        $q->execute([$referencia]);
        return $q->fetch() ?: null;
    }
}
