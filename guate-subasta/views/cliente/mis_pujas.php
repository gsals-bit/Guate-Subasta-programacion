<?php
// Pre-calcular la puja máxima del usuario por cada subasta para saber si actualmente va ganando en ella
$maxPujasUsuario = [];
foreach ($pujas as $p) {
    $sid = $p['subasta_id'];
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
      $esMaxima = abs((float)$p['monto'] - (float)$p['monto_actual']) < 0.01;
      
      // Verifica si la puja máxima del usuario en esta subasta es la que va ganando
      $esLiderActual = abs($maxPujasUsuario[$p['subasta_id']] - (float)$p['monto_actual']) < 0.01;
      
      $cerrada = in_array(strtolower($p['estado']), ['cerrada', 'finalizada']) || strtotime($p['fecha_fin']) <= time();
      $estadoReal = $cerrada ? 'cerrada' : (empty($p['estado']) ? 'activa' : strtolower($p['estado']));

      // Color de la etiqueta de estado de subasta
      $bgEstado = '#16a34a'; // Verde para activa
      if ($estadoReal === 'cerrada') $bgEstado = '#dc2626'; // Rojo para cerrada
      if ($estadoReal === 'programada') $bgEstado = '#d97706'; // Naranja para programada
      
      // Formatear la fecha a 12 horas con segundos
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
          <?php if($cerrada): ?>
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
            <?php if($esMaxima): ?>
              <span style="background:#2563eb; color:#fff; padding:4px 8px; border-radius:4px; font-size:0.85em; font-weight:bold;">
                Vas ganando
              </span>
            <?php elseif($esLiderActual): ?>
              <span style="background:#6b7280; color:#fff; padding:4px 8px; border-radius:4px; font-size:0.85em;">
                Puja anterior (Lideras con otra)
              </span>
            <?php else: ?>
              <span style="background:#d97706; color:#fff; padding:4px 8px; border-radius:4px; font-size:0.85em; font-weight:bold;">
                Puja superada
              </span>
            <?php endif; ?>
          <?php endif; ?>
        </td>
        <td><?=e($fechaFormateada)?></td>
        <td>
          <?php 
            // Solo permite mejorar si la subasta está activa y el usuario NO es el líder actual
            $mostrarMejorar = (!$cerrada && !$esLiderActual); 
          ?>
          <a class="button" href="index.php?page=detalle&id=<?=(int)$p['subasta_id']?>" style="padding:6px 10px; font-size:0.9em;">
            <?= $mostrarMejorar ? 'Mejorar puja' : 'Ver subasta' ?>
          </a>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
<?php endif; ?>