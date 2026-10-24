<?php
class Token {
    public function __construct(private PDO $db) {}
    public function saldo(int $uid): int {
        $q=$this->db->prepare('SELECT saldo FROM billeteras_tokens WHERE usuario_id=?');
        $q->execute([$uid]); return (int)($q->fetchColumn() ?: 0);
    }
    public function comprar(int $sid, int $uid): array {
        try {
            $this->db->beginTransaction();
            $q=$this->db->prepare("SELECT s.id,s.fecha_fin,s.monto_actual,a.creado_por, a.nombre FROM subastas s JOIN articulos a ON a.id=s.articulo_id WHERE s.id=? FOR UPDATE");
            $q->execute([$sid]); $s=$q->fetch();
            if (!$s || strtotime($s['fecha_fin']) > time()) throw new Exception('La subasta aún no ha finalizado.');
            if ((int)$s['creado_por'] === $uid) throw new Exception('No puedes comprar tu propio artículo.');
            $q=$this->db->prepare('SELECT usuario_id,monto FROM pujas WHERE subasta_id=? ORDER BY monto DESC, fecha ASC LIMIT 1');
            $q->execute([$sid]); $winner=$q->fetch();
            if (!$winner || (int)$winner['usuario_id'] !== $uid) throw new Exception('Solo el ganador puede comprar el artículo.');
            $q=$this->db->prepare('SELECT id FROM compras_tokens WHERE subasta_id=?');
            $q->execute([$sid]);
            if ($q->fetch()) throw new Exception('Esta subasta ya fue comprada.');
            // Demo: 1 token = Q1 de precio de puja. Sin pagos reales.
            $costo=(int)ceil((float)$winner['monto']);
            $q=$this->db->prepare('UPDATE billeteras_tokens SET saldo=saldo-? WHERE usuario_id=? AND saldo>=?');
            $q->execute([$costo,$uid,$costo]);
            if ($q->rowCount() !== 1) throw new Exception('Tokens insuficientes para comprar.');
            $this->db->prepare('INSERT INTO compras_tokens(subasta_id,usuario_id,tokens_gastados) VALUES(?,?,?)')->execute([$sid,$uid,$costo]);
            $this->db->prepare("UPDATE articulos SET estado='adjudicado' WHERE id=(SELECT articulo_id FROM subastas WHERE id=?)")->execute([$sid]);
            $this->db->prepare('INSERT INTO auditoria(usuario_id,evento,detalle) VALUES(?,?,?)')->execute([$uid,'COMPRA_TOKENS',"Subasta #$sid, tokens: $costo"]);
            $this->db->commit();
            return [true, 'Compra simulada registrada. Se descontaron '.$costo.' tokens.'];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return [false,$e->getMessage()];
        }
    }
}
