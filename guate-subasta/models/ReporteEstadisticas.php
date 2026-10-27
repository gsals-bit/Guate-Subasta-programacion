<?php
/** Reportes de demostracion. Todas las cifras proceden de MySQL. */
class ReporteEstadisticas {
    public static function filas(PDO $db): array {
        return $db->query("SELECT s.id, s.estado, s.monto_actual, a.nombre AS articulo,
            (SELECT COUNT(*) FROM pujas p WHERE p.subasta_id=s.id AND p.cancelada=0 AND p.estado <> 'cancelada') AS total_pujas,
            (SELECT COUNT(*) FROM pujas p WHERE p.subasta_id=s.id AND (p.cancelada=1 OR p.estado='cancelada')) AS canceladas,
            (SELECT COALESCE(SUM(pg.monto),0) FROM ordenes_pago op
               JOIN pagos pg ON pg.orden_id=op.id
               WHERE op.subasta_id=s.id AND pg.metodo='TARJETA_DEMO' AND pg.estado='aprobado') AS cobrado_demo
          FROM subastas s JOIN articulos a ON a.id=s.articulo_id ORDER BY s.id DESC")->fetchAll();
    }

    public static function pagosDiarios(PDO $db, int $dias=7): array {
        $dias = min(31, max(1, $dias));
        $q=$db->prepare("SELECT DATE(pg.fecha) fecha, COALESCE(SUM(pg.monto),0) total
            FROM pagos pg WHERE pg.metodo='TARJETA_DEMO' AND pg.estado='aprobado'
              AND pg.fecha >= DATE_SUB(CURRENT_DATE(), INTERVAL " . ($dias - 1) . " DAY)
            GROUP BY DATE(pg.fecha) ORDER BY fecha");
        $q->execute();
        $montos=[];
        foreach ($q->fetchAll() as $r) $montos[$r['fecha']] = (float)$r['total'];
        $result=[];
        for ($i=$dias-1;$i>=0;$i--) {
            $fecha=date('Y-m-d', strtotime("-$i day"));
            $result[]=['fecha'=>$fecha, 'monto'=>$montos[$fecha]??0];
        }
        return $result;
    }
}
