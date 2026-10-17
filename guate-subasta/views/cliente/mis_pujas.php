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
      $cerrada = in_array($p['estado'], ['cerrada', 'finalizada'], true) || strtotime($p['fecha_fin']) <= time();
    ?>
      <tr>
        <td>
          <strong><?=e($p['articulo'])?></strong><br>
          <small style="opacity:0.75;"><?=e($p['categoria'] ?? '')?></small>
        </td>
        <td>Q <?=number_format($p['monto'], 2)?></td>
        <td><strong>Q <?=number_format($p['monto_actual'], 2)?></strong></td>
        <td><span class="badge"><?=e($cerrada ? 'cerrada' : $p['estado'])?></span></td>
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
            <?php else: ?>
              <span style="background:#d97706; color:#fff; padding:4px 8px; border-radius:4px; font-size:0.85em; font-weight:bold;">
                Puja superada
              </span>
            <?php endif; ?>
          <?php endif; ?>
        </td>
        <td><?=e($p['fecha'])?></td>
        <td>
          <a class="button" href="index.php?page=detalle&id=<?=(int)$p['subasta_id']?>" style="padding:6px 10px; font-size:0.9em;">
            <?= (!$cerrada && !$esMaxima) ? 'Mejorar puja' : 'Ver subasta' ?>
          </a>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
<?php endif; ?>