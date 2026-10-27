<?php
/** Programación, horarios justos (máximo 30 minutos) y notificaciones. PHP >= 8.0. */
class AgendaSubastas {
    private const DURACION_MAXIMA = 30;

    /** El servidor siempre valida el tiempo; el valor del formulario no es confiable. */
    private function duracion(mixed $valor): int {
        $valor = trim((string)$valor);
        if (!preg_match('/^(?:[1-9]|[12][0-9]|30)$/D', $valor)) {
            throw new RuntimeException('La duración debe ser un número entero entre 1 y 30 minutos.');
        }
        return (int)$valor;
    }
    private DateTimeZone $zona;

    public function __construct(private PDO $db) {
        $this->zona = new DateTimeZone('America/Guatemala');
    }

    private function fecha(?string $texto): DateTimeImmutable {
        $texto = trim((string)$texto);
        $formato = str_contains($texto, 'T') ? '!Y-m-d\TH:i' : '!Y-m-d H:i:s';
        $d = DateTimeImmutable::createFromFormat($formato, $texto, $this->zona);
        $err = DateTimeImmutable::getLastErrors();
        if (!$d || ($err !== false && ($err['warning_count'] || $err['error_count'])) ||
            $d->format(str_contains($texto, 'T') ? 'Y-m-d\TH:i' : 'Y-m-d H:i:s') !== $texto) {
            throw new RuntimeException('Ingresa una fecha y hora válidas.');
        }
        return $d;
    }

    private function ahora(): DateTimeImmutable {
        return new DateTimeImmutable('now', $this->zona);
    }

    private function registrarAviso(int $sid, int $autor, string $tipo, string $articulo,
                                     DateTimeImmutable $inicio, DateTimeImmutable $fin): void {
        $minutos = (int)(($fin->getTimestamp() - $inicio->getTimestamp()) / 60);
        $mensaje = $tipo === 'programada'
          ? 'Se programó «'.$articulo.'» para el '.$inicio->format('d/m/Y H:i').'. Duración: '.$minutos.' minuto(s).' 
          : ($tipo === 'reprogramada'
            ? 'Se cambió la programación de «'.$articulo.'»: inicia '.$inicio->format('d/m/Y H:i').' y termina '.$fin->format('H:i').'. Duración: '.$minutos.' minuto(s).'
            : 'Cambió el cierre de «'.$articulo.'». Nuevo cierre: '.$fin->format('d/m/Y H:i').'.');
        $titulo = $tipo === 'programada' ? 'Nueva subasta programada'
            : ($tipo === 'reprogramada' ? 'Subasta reprogramada' : 'Horario de cierre actualizado');
        $q = $this->db->prepare('INSERT INTO avisos_subastas (subasta_id, creado_por, tipo, titulo, mensaje, fecha_inicio, fecha_fin) VALUES (?,?,?,?,?,?,?)');
        $q->execute([$sid, $autor, $tipo, $titulo, mb_substr($mensaje,0,500),
                     $inicio->format('Y-m-d H:i:s'), $fin->format('Y-m-d H:i:s')]);
        $avisoId = (int)$this->db->lastInsertId();
        // Solo a usuarios que habilitaron voluntariamente los correos de nuevas subastas.
        $q = $this->db->prepare("INSERT INTO avisos_subastas_correos (aviso_id, usuario_id, correo_destino)
            SELECT ?, u.id, u.email FROM usuarios u
            JOIN preferencias_avisos_subastas p ON p.usuario_id=u.id AND p.correo_habilitado=1
            WHERE u.activo=1 AND u.id<>? AND u.email LIKE '%@%.%'");
        $q->execute([$avisoId,$autor]);
    }

    /** Cierre calculado en el servidor: de 1 a 30 minutos. */
    public function programar(int $articuloId, int $usuarioId, string $inicioTexto, float $incremento, mixed $minutosTexto = 30): array {
        try {
            $minutos = $this->duracion($minutosTexto);
            $inicio = $this->fecha($inicioTexto);
            $ahora = $this->ahora();
            if ($inicio < $ahora->modify('+2 minutes')) throw new RuntimeException('Programa el inicio con al menos 2 minutos de anticipación.');
            if ($inicio > $ahora->modify('+365 days')) throw new RuntimeException('No puedes programar subastas a más de un año.');
            if (!is_finite($incremento) || $incremento < 0.01 || $incremento > 999999999) throw new RuntimeException('Ingresa un incremento mínimo válido.');
            if ($articuloId <= 0 || $usuarioId <= 0) throw new RuntimeException('Artículo o usuario no válido.');
            $fin = $inicio->modify('+'.$minutos.' minutes');
            $this->db->beginTransaction();
            $q = $this->db->prepare('SELECT id,nombre,creado_por,precio_base,archivado FROM articulos WHERE id=? FOR UPDATE');
            $q->execute([$articuloId]);
            $a = $q->fetch(PDO::FETCH_ASSOC);
            if (!$a || (int)$a['creado_por'] !== $usuarioId) throw new RuntimeException('Solo puedes programar subastas de tus propios artículos.');
            if ((int)$a['archivado'] === 1) throw new RuntimeException('El artículo está archivado.');
            $q = $this->db->prepare("SELECT COUNT(*) FROM subastas WHERE articulo_id=?");
            $q->execute([$articuloId]);
            if ((int)$q->fetchColumn() > 0) throw new RuntimeException('Este artículo ya tuvo una subasta (programada, activa o finalizada) y no se puede programar otra vez.');
            $q = $this->db->prepare("INSERT INTO subastas(articulo_id,fecha_inicio,fecha_fin,incremento_minimo,monto_actual,estado) VALUES (?,?,?,?,?,'programada')");
            $q->execute([$articuloId,$inicio->format('Y-m-d H:i:s'),$fin->format('Y-m-d H:i:s'),$incremento,$a['precio_base']]);
            $sid = (int)$this->db->lastInsertId();
            $this->registrarAviso($sid,$usuarioId,'programada',(string)$a['nombre'],$inicio,$fin);
            $q = $this->db->prepare('INSERT INTO auditoria(usuario_id,evento,detalle) VALUES (?,?,?)');
            $q->execute([$usuarioId,'PROGRAMAR_SUBASTA','Subasta #'.$sid.' de artículo #'.$articuloId.' - '.$inicio->format('Y-m-d H:i').' a '.$fin->format('H:i').' - Duración: '.$minutos.' minutos']);
            $this->db->commit();
            return [true, 'Subasta #'.$sid.' programada por '.$minutos.' minuto(s). Se creó una notificación para los usuarios.'];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return [false, $e->getMessage()];
        }
    }

    public function detallePropietario(int $subastaId, int $usuarioId): ?array {
        $q=$this->db->prepare('SELECT s.*,a.nombre AS articulo,a.creado_por FROM subastas s JOIN articulos a ON a.id=s.articulo_id WHERE s.id=? AND a.creado_por=?');
        $q->execute([$subastaId,$usuarioId]);
        return $q->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /** Programadas: se puede modificar hora y duración (1-30 min). Activas: únicamente adelantar cierre. */
    public function cambiar(int $subastaId, int $usuarioId, string $valor, mixed $minutosTexto = null): array {
        try {
            if ($subastaId <= 0 || $usuarioId <= 0) throw new RuntimeException('Parámetros inválidos.');
            $solicitud = $this->fecha($valor);
            $this->db->beginTransaction();
            $q=$this->db->prepare('SELECT s.*,a.nombre AS articulo,a.creado_por FROM subastas s JOIN articulos a ON a.id=s.articulo_id WHERE s.id=? FOR UPDATE');
            $q->execute([$subastaId]); $s=$q->fetch(PDO::FETCH_ASSOC);
            if (!$s || (int)$s['creado_por'] !== $usuarioId) throw new RuntimeException('Solo el creador puede modificar el horario.');
            $now=$this->ahora();
            $inicio=$this->fecha((string)$s['fecha_inicio']);
            $fin=$this->fecha((string)$s['fecha_fin']);
            if (in_array($s['estado'],['cancelada','cerrada','finalizada'],true) || $now >= $fin) throw new RuntimeException('La subasta ya cerró; no se puede modificar.');
            $activo=($now >= $inicio);
            if (!$activo) {
                if ($solicitud < $now->modify('+2 minutes')) throw new RuntimeException('El nuevo inicio debe tener al menos 2 minutos de anticipación.');
                if ($solicitud > $now->modify('+365 days')) throw new RuntimeException('La fecha supera un año.');
                $minutos = $minutosTexto === null
                    ? (int)(($fin->getTimestamp() - $inicio->getTimestamp()) / 60)
                    : $this->duracion($minutosTexto);
                $minutos = $this->duracion($minutos);
                $nuevoInicio=$solicitud; $nuevoFin=$solicitud->modify('+'.$minutos.' minutes');
                if ($nuevoInicio == $inicio && $nuevoFin == $fin) throw new RuntimeException('No hay cambios en el horario.');
                // Subasta todavía sin iniciar: cambio de fecha y hora, mismo monto y pujas intactas.
                $q=$this->db->prepare("UPDATE subastas SET fecha_inicio=?,fecha_fin=?,estado='programada' WHERE id=?");
                $q->execute([$nuevoInicio->format('Y-m-d H:i:s'),$nuevoFin->format('Y-m-d H:i:s'),$subastaId]);
                $tipo='reprogramada';
            } else {
                // En una subasta activa ya no se puede mover el inicio ni alargar el cierre.
                $limite=$inicio->modify('+'.self::DURACION_MAXIMA.' minutes');
                if ($now >= $limite) throw new RuntimeException('Esta subasta ya alcanzó el límite máximo y debe cerrar.');
                if ($solicitud < $now->modify('+2 minutes')) throw new RuntimeException('El nuevo cierre debe dejar al menos 2 minutos para los participantes.');
                if ($solicitud >= $fin) throw new RuntimeException('Durante una subasta activa solo puedes adelantar el cierre; no extenderlo.');
                if ($solicitud > $limite) throw new RuntimeException('La duración total no puede superar 30 minutos.');
                $nuevoInicio=$inicio; $nuevoFin=$solicitud;
                $q=$this->db->prepare('UPDATE subastas SET fecha_fin=? WHERE id=?');
                $q->execute([$nuevoFin->format('Y-m-d H:i:s'),$subastaId]);
                $tipo='cierre_modificado';
            }
            $this->registrarAviso($subastaId,$usuarioId,$tipo,(string)$s['articulo'],$nuevoInicio,$nuevoFin);
            $q=$this->db->prepare('INSERT INTO auditoria(usuario_id,evento,detalle) VALUES (?,?,?)');
            $q->execute([$usuarioId,'CAMBIAR_HORARIO_SUBASTA','Subasta #'.$subastaId.' de '.$inicio->format('Y-m-d H:i:s').' / '.$fin->format('Y-m-d H:i:s').' a '.$nuevoInicio->format('Y-m-d H:i:s').' / '.$nuevoFin->format('Y-m-d H:i:s')]);
            $this->db->commit();
            return [true,'Horario actualizado. Se avisó del cambio a los usuarios.'];
        } catch(Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return [false,$e->getMessage()];
        }
    }

    public function noLeidos(int $userId): int {
        $q=$this->db->prepare('SELECT COUNT(*) FROM avisos_subastas v LEFT JOIN avisos_subastas_leidos l ON l.aviso_id=v.id AND l.usuario_id=? WHERE v.creado_por<>? AND l.aviso_id IS NULL');
        $q->execute([$userId,$userId]); return (int)$q->fetchColumn();
    }

    public function listado(int $userId): array {
        $q=$this->db->prepare('SELECT v.*, a.nombre articulo, (l.aviso_id IS NOT NULL) AS leido FROM avisos_subastas v JOIN subastas s ON s.id=v.subasta_id JOIN articulos a ON a.id=s.articulo_id LEFT JOIN avisos_subastas_leidos l ON l.aviso_id=v.id AND l.usuario_id=? WHERE v.creado_por<>? ORDER BY v.id DESC LIMIT 150');
        $q->execute([$userId,$userId]); return $q->fetchAll(PDO::FETCH_ASSOC);
    }

    public function marcarLeidos(int $userId): void {
        $q=$this->db->prepare('INSERT IGNORE INTO avisos_subastas_leidos(usuario_id,aviso_id) SELECT ?,v.id FROM avisos_subastas v WHERE v.creado_por<>?');
        $q->execute([$userId,$userId]);
    }

    public function correoActivo(int $userId): bool {
        $q=$this->db->prepare('SELECT correo_habilitado FROM preferencias_avisos_subastas WHERE usuario_id=?');
        $q->execute([$userId]); return (bool)$q->fetchColumn();
    }

    public function configurarCorreo(int $userId, bool $activo): void {
        $q=$this->db->prepare('INSERT INTO preferencias_avisos_subastas(usuario_id,correo_habilitado) VALUES (?,?) ON DUPLICATE KEY UPDATE correo_habilitado=VALUES(correo_habilitado)');
        $q->execute([$userId,$activo?1:0]);
        if (!$activo) {
            $q=$this->db->prepare("DELETE FROM avisos_subastas_correos WHERE usuario_id=? AND estado IN ('pendiente','error')");
            $q->execute([$userId]);
        }
    }
}
