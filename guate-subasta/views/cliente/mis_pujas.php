<?php
// Pre-calcular la puja máxima del usuario por cada subasta para saber cuál es su oferta principal
$maxPujasUsuario = [];
foreach ($pujas as $p) {
    $sid = $p['subasta_id'];
    if (!empty($p['cancelada']) || (($p['estado_puja'] ?? 'activa') === 'cancelada')) continue;
    if (!isset($maxPujasUsuario[$sid]) || (float)$p['monto'] > $maxPujasUsuario[$sid]) {
        $maxPujasUsuario[$sid] = (float)$p['monto'];
    }
}
?>
<h1>Mis pujas</h1>
<p>Revisa el historial de tus ofertas y verifica en tiempo real si vas liderando las subastas.</p>

<?php if(empty($pujas)): ?>
  <section class="panel">
    <p>Aún no has realizado ninguna puja.</p>
    <a class="button" href="index.php?page=catalogo">Explorar subastas disponibles</a>
  </section>
<?php else: ?>
  <table>
    <tr>
      <th>Artículo</th>
      <th>Tu Puja</th>
      <th>Precio Actual</th>
      <th>Estado Subasta</th>
      <th>Situación</th>
      <th>Fecha</th>
      <th>Acción</th>
    </tr>
    <?php foreach($pujas as $p): 
      $cancelada = !empty($p['cancelada']) || (($p['estado_puja'] ?? 'activa') === 'cancelada');
      $esMiMejorPuja = !$cancelada && isset($maxPujasUsuario[$p['subasta_id']])
          && abs($maxPujasUsuario[$p['subasta_id']] - (float)$p['monto']) < 0.01;
      $esLiderGlobal = abs($maxPujasUsuario[$p['subasta_id']] - (float)$p['monto_actual']) < 0.01;
      $esMaxima = abs((float)$p['monto'] - (float)$p['monto_actual']) < 0.01; 
      
      $cerrada = in_array(strtolower($p['estado']), ['cerrada', 'finalizada']) || strtotime($p['fecha_fin']) <= time();
      $estadoReal = $cerrada ? 'cerrada' : (empty($p['estado']) ? 'activa' : strtolower($p['estado']));

      $bgEstado = '#16a34a'; 
      if ($estadoReal === 'cerrada') $bgEstado = '#dc2626'; 
      if ($estadoReal === 'programada') $bgEstado = '#d97706'; 
      
      $fechaFormateada = date('d/m/Y h:i:s A', strtotime($p['fecha']));
    ?>
      <tr>
        <td>
          <strong><?=e($p['articulo'])?></strong><br>
          <small style="opacity:0.75;"><?=e($p['categoria'] ?? '')?></small>
        </td>
        <td>Q <?=number_format($p['monto'], 2)?></td>
        <td><strong>Q <?=number_format($p['monto_actual'], 2)?></strong></td>
        <td>
          <span class="badge" style="background:<?=$bgEstado?>; color:#fff; padding:4px 8px; border-radius:4px; font-size:0.8em; font-weight:bold; display:inline-block; text-transform:capitalize;">
            <?=e($estadoReal)?>
          </span>
        </td>
        <td>
          <?php if($cancelada): ?><span style="color:#a33">Cancelada</span>
          <?php elseif($cerrada): ?>
            <?php if($esMaxima): ?>
              <span style="background:#16a34a; color:#fff; padding:4px 8px; border-radius:4px; font-size:0.85em; font-weight:bold;">
                Adjudicada (Ganador)
              </span>
            <?php else: ?>
              <span style="background:#4b5563; color:#fff; padding:4px 8px; border-radius:4px; font-size:0.85em;">
                No adjudicada
              </span>
            <?php endif; ?>
          <?php else: ?>
            <?php if($esLiderGlobal && $esMiMejorPuja): ?>
              <span style="background:#2563eb; color:#fff; padding:4px 8px; border-radius:4px; font-size:0.85em; font-weight:bold;">
                Vas ganando
              </span>
            <?php elseif(!$esLiderGlobal && $esMiMejorPuja): ?>
              <span style="background:#d97706; color:#fff; padding:4px 8px; border-radius:4px; font-size:0.85em; font-weight:bold;">
                Puja superada
              </span>
            <?php else: ?>
              <span style="background:#6b7280; color:#fff; padding:4px 8px; border-radius:4px; font-size:0.85em;">
                Puja anterior
              </span>
            <?php endif; ?>
          <?php endif; ?>
        </td>
        <td><?=e($fechaFormateada)?></td>
        <td>
          <?php 
            $mostrarMejorar = (!$cerrada && !$esLiderGlobal && $esMiMejorPuja); 
          ?>
          <a class="button" href="index.php?page=detalle&id=<?=(int)$p['subasta_id']?>" style="padding:6px 10px; font-size:0.9em;">
            <?= $mostrarMejorar ? 'Mejorar puja' : 'Ver subasta' ?>
          </a>
          <?php if (!$cancelada && !$cerrada && $estadoReal==='activa'): ?>
          <form method="post" action="index.php?action=cancelar_puja" onsubmit="return confirm('¿Cancelar esta puja? Se conservará en la bitácora.');" style="margin-top:6px">
            <input type="hidden" name="csrf" value="<?=csrf_token()?>">
            <input type="hidden" name="puja_id" value="<?=(int)$p['id']?>">
            <button type="submit" style="background:#9f2630">Cancelar puja</button>
          </form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
<?php endif; ?>

<script>
// Forzar recarga si el usuario navega hacia esta pagina usando el boton "Atras" del navegador
window.addEventListener('pageshow', function(event) {
    if (event.persisted) {
        window.location.reload();
    }
});
</script>