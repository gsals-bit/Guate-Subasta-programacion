<?php
// Puja máxima vigente del usuario por subasta (ignora las canceladas)
$maxPujasUsuario = [];
foreach ($pujas as $p) {
    $sid = $p['subasta_id'];
    if (!empty($p['cancelada']) || (($p['estado_puja'] ?? 'activa') === 'cancelada')) continue;
    if (!isset($maxPujasUsuario[$sid]) || (float)$p['monto'] > $maxPujasUsuario[$sid]) {
        $maxPujasUsuario[$sid] = (float)$p['monto'];
    }
}

// Primera pasada: calcular la situación de cada puja y el resumen superior
$filas = [];
$resumen = ['ganando' => [], 'superada' => [], 'adjudicada' => []];
foreach ($pujas as $p) {
    $sid = $p['subasta_id'];
    $cancelada = !empty($p['cancelada']) || (($p['estado_puja'] ?? 'activa') === 'cancelada');
    $mejor = $maxPujasUsuario[$sid] ?? 0.0;
    $esMiMejorPuja = !$cancelada && isset($maxPujasUsuario[$sid]) && abs($mejor - (float)$p['monto']) < 0.01;
    $esLiderGlobal = abs($mejor - (float)$p['monto_actual']) < 0.01;
    $esMaxima = abs((float)$p['monto'] - (float)$p['monto_actual']) < 0.01;

    $cerrada = in_array(strtolower($p['estado']), ['cerrada', 'finalizada']) || strtotime($p['fecha_fin']) <= time();
    $estadoReal = $cerrada ? 'cerrada' : (empty($p['estado']) ? 'activa' : strtolower($p['estado']));

    if ($cancelada)                                $sit = 'cancelada';
    elseif ($cerrada)                              $sit = $esMaxima ? 'adjudicada' : 'no_adjudicada';
    elseif ($esLiderGlobal && $esMiMejorPuja)      $sit = 'ganando';
    elseif (!$esLiderGlobal && $esMiMejorPuja)     $sit = 'superada';
    else                                           $sit = 'anterior';

    if (isset($resumen[$sit])) $resumen[$sit][$sid] = true;

    $p['_cancelada'] = $cancelada;
    $p['_cerrada'] = $cerrada;
    $p['_estado'] = $estadoReal;
    $p['_sit'] = $sit;
    $filas[] = $p;
}

$situaciones = [
    'cancelada'     => ['badge cancelada', 'Cancelada'],
    'adjudicada'    => ['badge', 'Adjudicada, ganaste'],
    'no_adjudicada' => ['badge cerrada', 'No adjudicada'],
    'ganando'       => ['badge', 'Vas ganando'],
    'superada'      => ['badge warn', 'Puja superada'],
    'anterior'      => ['badge muted', 'Puja anterior'],
];
?>
<h1>Mis pujas</h1>
<p>Revisa tus ofertas y mira en qué subastas vas ganando.</p>

<?php if(empty($pujas)): ?>
  <section class="panel">
    <div class="empty">
      <p>Todavía no has hecho ninguna puja.</p>
      <a class="button sm" href="index.php?page=catalogo">Explorar subastas</a>
    </div>
  </section>
<?php else: ?>
  <div class="chips">
    <span class="chip win"><b><?=count($resumen['ganando'])?></b> vas ganando</span>
    <span class="chip lost"><b><?=count($resumen['superada'])?></b> superadas</span>
    <span class="chip"><b><?=count($resumen['adjudicada'])?></b> adjudicadas</span>
  </div>

  <section class="panel table-panel">
    <table>
      <tr>
        <th>Artículo</th>
        <th>Tu puja</th>
        <th>Precio actual</th>
        <th>Subasta</th>
        <th>Situación</th>
        <th>Fecha</th>
        <th></th>
      </tr>
      <?php foreach($filas as $p):
        [$claseSit, $textoSit] = $situaciones[$p['_sit']];
      ?>
        <tr>
          <td>
            <strong><?=e($p['articulo'])?></strong><br>
            <small class="muted"><?=e($p['categoria'] ?? '')?></small>
          </td>
          <td>Q <?=number_format((float)$p['monto'], 2)?></td>
          <td><strong>Q <?=number_format((float)$p['monto_actual'], 2)?></strong></td>
          <td><span class="badge <?=e($p['_estado'])?>"><?=e($p['_estado'])?></span></td>
          <td><span class="<?=$claseSit?>"><?=$textoSit?></span></td>
          <td><?=e(date('d/m/Y h:i A', strtotime($p['fecha'])))?></td>
          <td>
            <div class="row-actions">
              <?php if($p['_sit'] === 'superada'): ?>
                <a class="button sm gold" href="index.php?page=detalle&id=<?=(int)$p['subasta_id']?>">Mejorar puja</a>
              <?php else: ?>
                <a class="button sm ghost" href="index.php?page=detalle&id=<?=(int)$p['subasta_id']?>">Ver subasta</a>
              <?php endif; ?>
              <?php if (!$p['_cancelada'] && !$p['_cerrada'] && $p['_estado'] === 'activa'): ?>
                <form method="post" action="index.php?action=cancelar_puja" onsubmit="return confirm('¿Cancelar esta puja? Se conservará en la bitácora.');">
                  <input type="hidden" name="csrf" value="<?=csrf_token()?>">
                  <input type="hidden" name="puja_id" value="<?=(int)$p['id']?>">
                  <button type="submit" class="sm danger">Cancelar</button>
                </form>
              <?php endif; ?>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
    </table>
  </section>
<?php endif; ?>

<script>
// Forzar recarga si el usuario vuelve a esta página con el botón "Atrás" del navegador
window.addEventListener('pageshow', function(event) {
    if (event.persisted) {
        window.location.reload();
    }
});
</script>