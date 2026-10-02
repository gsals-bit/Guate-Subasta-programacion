<?php
class Puja {
    public function __construct(private PDO $db) {}
    public function realizar(int $subastaId,int $usuarioId,float $monto): array {
        try {
            $this->db->beginTransaction();
            $q=$this->db->prepare("SELECT * FROM subastas WHERE id=? FOR UPDATE");
            $q->execute([$subastaId]); $s=$q->fetch();
            if (!$s) throw new Exception('Subasta no encontrada.');
            $now=new DateTime();
            if ($now < new DateTime($s['fecha_inicio']) || $now > new DateTime($s['fecha_fin'])) throw new Exception('La subasta no está en horario activo.');
            if (in_array($s['estado'],['finalizada','cancelada'],true)) throw new Exception('La subasta ya no acepta pujas.');
            $min=(float)$s['monto_actual']+(float)$s['incremento_minimo'];
            if ($monto < $min) throw new Exception('La puja mínima permitida es Q '.number_format($min,2));
            $this->db->prepare("UPDATE subastas SET monto_actual=?,estado='activa' WHERE id=?")->execute([$monto,$subastaId]);
            $this->db->prepare("INSERT INTO pujas(subasta_id,usuario_id,monto) VALUES(?,?,?)")->execute([$subastaId,$usuarioId,$monto]);
            $this->db->prepare("INSERT INTO auditoria(usuario_id,evento,detalle) VALUES(?,?,?)")
                ->execute([$usuarioId,'PUJA',"Subasta $subastaId - Q $monto"]);
            $this->db->commit();
            return [true,'Puja registrada Existosamente.'];
        } catch(Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return [false,$e->getMessage()];
        }
    }
    public function historialUsuario(int $uid): array {
        $q=$this->db->prepare("SELECT p.*,a.nombre articulo,s.estado FROM pujas p JOIN subastas s ON s.id=p.subasta_id JOIN articulos a ON a.id=s.articulo_id WHERE p.usuario_id=? ORDER BY p.fecha DESC");
        $q->execute([$uid]); return $q->fetchAll();
    }
    public function historialSubasta(int $sid): array {
        $q=$this->db->prepare("SELECT p.*,u.nombre usuario FROM pujas p JOIN usuarios u ON u.id=p.usuario_id WHERE p.subasta_id=? ORDER BY p.monto DESC,p.fecha DESC");
        $q->execute([$sid]); return $q->fetchAll();
    }
}
