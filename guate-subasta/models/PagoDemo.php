<?php
/**
 * Pago con tarjeta FICTICIA: no se comunica con bancos ni procesa tarjetas reales.
 * Registra un pago aprobado en ordenes_pago, pagos y auditoria.
 */
class PagoDemo {
    public function __construct(private PDO $db) {}

    public function detalle(int $sid, int $uid): ?array {
        (new Subasta($this->db))->actualizarEstados();
        $q = $this->db->prepare("
            SELECT s.id, s.fecha_fin, s.estado, a.nombre AS articulo,
                   a.precio_base, a.creado_por,
                   p.usuario_id AS ganador_id, p.monto
            FROM subastas s
            JOIN articulos a ON a.id = s.articulo_id
            JOIN pujas p ON p.id = (
                SELECT p2.id FROM pujas p2
                WHERE p2.subasta_id = s.id AND p2.cancelada = 0
                  AND p2.estado <> 'cancelada'
                ORDER BY p2.monto DESC, p2.fecha ASC, p2.id ASC LIMIT 1
            )
            WHERE s.id = ? AND s.fecha_fin <= NOW() AND s.estado <> 'cancelada'
        ");
        $q->execute([$sid]);
        $registro = $q->fetch();
        if (!$registro || (int)$registro['ganador_id'] !== $uid) {
            return null;
        }
        $q = $this->db->prepare('SELECT estado, referencia_demo FROM ordenes_pago WHERE subasta_id = ?');
        $q->execute([$sid]);
        $registro['pago'] = $q->fetch() ?: null;
        return $registro;
    }

    public function pagar(int $sid, int $uid, array $tarjetaDemo = []): array {
        // Campos predefinidos, EXCLUSIVAMENTE ficticios. No se almacena CVV.
        if (($tarjetaDemo['numero'] ?? '') !== 'DEMO-0000-0000-0000'
            || ($tarjetaDemo['vencimiento'] ?? '') !== '12/99'
            || ($tarjetaDemo['cvv'] ?? '') !== '000'
            || empty($tarjetaDemo['acepta'])) {
            return [false, 'Utiliza el boton de llenado automatico y confirma que se trata de una tarjeta ficticia.'];
        }
        if ($sid <= 0 || $uid <= 0) {
            return [false, 'Solicitud de pago no valida.'];
        }

        try {
            $this->db->beginTransaction();

            // Bloqueo para evitar que dos solicitudes registren el mismo pago.
            $q = $this->db->prepare('SELECT id, fecha_fin, estado, articulo_id FROM subastas WHERE id = ? FOR UPDATE');
            $q->execute([$sid]);
            $subasta = $q->fetch();
            if (!$subasta || $subasta['estado'] === 'cancelada') {
                throw new RuntimeException('Subasta no encontrada o cancelada.');
            }
            $q = $this->db->prepare('SELECT (fecha_fin <= NOW()) FROM subastas WHERE id = ?');
            $q->execute([$sid]);
            if (!(bool)$q->fetchColumn()) {
                throw new RuntimeException('La subasta todavia no ha finalizado.');
            }

            // Comprobar ganador usando solo pujas que siguen activas.
            $q = $this->db->prepare("
                SELECT usuario_id, monto
                FROM pujas
                WHERE subasta_id = ? AND cancelada = 0 AND estado <> 'cancelada'
                ORDER BY monto DESC, fecha ASC, id ASC LIMIT 1
            ");
            $q->execute([$sid]);
            $ganador = $q->fetch();
            if (!$ganador || (int)$ganador['usuario_id'] !== $uid) {
                throw new RuntimeException('Solo el ganador puede realizar este pago.');
            }

            $q = $this->db->prepare('SELECT id, estado FROM ordenes_pago WHERE subasta_id = ? FOR UPDATE');
            $q->execute([$sid]);
            $orden = $q->fetch();
            if ($orden && $orden['estado'] === 'pagado') {
                throw new RuntimeException('Esta subasta ya tiene un pago registrado.');
            }

            // Si habia una compra vieja con tokens en esta DEMO,
            // la anulamos y reponemos su saldo ANTES de registrar tarjeta.
            $q = $this->db->prepare('SELECT id, usuario_id, tokens_gastados FROM compras_tokens WHERE subasta_id = ? FOR UPDATE');
            $q->execute([$sid]);
            $compraAnterior = $q->fetch();
            if ($compraAnterior) {
                if ((int)$compraAnterior['usuario_id'] !== $uid) {
                    throw new RuntimeException('Existe una compra anterior de otra cuenta. Requiere revision del administrador.');
                }
                $q = $this->db->prepare('UPDATE billeteras_tokens SET saldo = saldo + ? WHERE usuario_id = ?');
                $q->execute([(int)$compraAnterior['tokens_gastados'], $uid]);
                if ($q->rowCount() !== 1) {
                    throw new RuntimeException('No se pudo revertir la compra anterior con tokens.');
                }
                $this->db->prepare('DELETE FROM compras_tokens WHERE id = ?')
                    ->execute([(int)$compraAnterior['id']]);
                $this->db->prepare('INSERT INTO auditoria(usuario_id, evento, detalle) VALUES(?, ?, ?)')
                    ->execute([$uid, 'REVERSO_TOKENS_DEMO',
                        "Subasta #{$sid}: compra anterior de prueba revertida; " .
                        (int)$compraAnterior['tokens_gastados'] . ' tokens reintegrados por cambio a tarjeta ficticia']);
            }

            $monto = (float)$ganador['monto'];
            $referencia = 'TARJETA-DEMO-' . strtoupper(bin2hex(random_bytes(5)));
            if (!$orden) {
                $q = $this->db->prepare("
                    INSERT INTO ordenes_pago(subasta_id, usuario_id, monto, comision, estado, referencia_demo)
                    VALUES(?, ?, ?, 0, 'pagado', ?)
                ");
                $q->execute([$sid, $uid, $monto, $referencia]);
                $ordenId = (int)$this->db->lastInsertId();
            } else {
                $ordenId = (int)$orden['id'];
                $q = $this->db->prepare("
                    UPDATE ordenes_pago SET usuario_id = ?, monto = ?, comision = 0,
                    estado = 'pagado', referencia_demo = ? WHERE id = ?
                ");
                $q->execute([$uid, $monto, $referencia, $ordenId]);
            }

            // No guardamos numero de tarjeta, vencimiento ni CVV.
            $q = $this->db->prepare("
                INSERT INTO pagos(orden_id, monto, metodo, estado)
                VALUES(?, ?, 'TARJETA_DEMO', 'aprobado')
            ");
            $q->execute([$ordenId, $monto]);
            $this->db->prepare("UPDATE subastas SET estado = 'finalizada' WHERE id = ?")
                ->execute([$sid]);
            $this->db->prepare("UPDATE articulos SET estado = 'adjudicado' WHERE id = ?")
                ->execute([(int)$subasta['articulo_id']]);
            $this->db->prepare('INSERT INTO auditoria(usuario_id, evento, detalle) VALUES(?, ?, ?)')
                ->execute([$uid, 'PAGO_TARJETA_DEMO',
                    "Subasta #{$sid} | Referencia {$referencia} | Q " . number_format($monto, 2) . ' | SIN COBRO REAL']);

            $this->db->commit();
            return [true, 'Compra con tarjeta ficticia registrada. Referencia: ' . $referencia];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return [false, $e->getMessage()];
        }
    }
}
