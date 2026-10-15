<div class="grid2">
  <section class="panel">
    <h1><?=e($subasta['articulo'])?></h1>
    <span class="badge"><?=e($subasta['estado'])?></span>
    <p><strong>Categoría:</strong> <?=e($subasta['categoria'])?></p>
    <p><strong>Descripción:</strong> <?=e($subasta['descripcion'])?></p>
    <p><strong>Certificado:</strong> <?=e($subasta['certificado'] ?: 'N/A')?></p>
    <hr style="margin:15px 0;">
    <p><strong>Finaliza:</strong> <?=e($subasta['fecha_fin'])?></p>
    <h3>Precio Actual: Q <?=number_format($subasta['monto_actual'], 2)?></h3>
    <p><small>Incremento mínimo requerido: Q <?=number_format($subasta['incremento_minimo'], 2)?></small></p>
    
    <?php if($subasta['estado'] === 'activa'): ?>
      <form method="post" action="index.php?action=pujar" style="margin-top:20px;">
        <input type="hidden" name="csrf" value="<?=csrf_token()?>">
        <input type="hidden" name="subasta_id" value="<?=$subasta['id']?>">
        <label>Monto de tu puja (Q)</label>
        <input type="number" step=".01" min="<?=($subasta['monto_actual'] + $subasta['incremento_minimo'])?>" name="monto" required>
        <button type="submit">Realizar Puja</button>
      </form>
    <?php elseif($subasta['estado'] === 'programada'): ?>
      <div style="background:#fef3c7; color:#92400e; padding:10px; border-radius:4px; margin-top:20px;">
        Esta subasta aún no inicia. Podrás pujar a partir de: <?=e($subasta['fecha_inicio'])?>
      </div>
    <?php else: ?>
      <div style="background:#fee2e2; color:#991b1b; padding:10px; border-radius:4px; margin-top:20px;">
        Esta subasta ya finalizó.
      </div>
    <?php endif; ?>

    <?php if($subasta['estado'] === 'cerrada' && in_array($_SESSION['user']['rol'], ['Cliente', 'Subastador', 'Administrador'])): ?>
      <form method="post" action="index.php?action=eliminar_subasta" onsubmit="return confirm('¿Seguro que deseas eliminar esta subasta cerrada?');" style="margin-top:15px;">
        <input type="hidden" name="csrf" value="<?=csrf_token()?>">
        <input type="hidden" name="subasta_id" value="<?=(int)$subasta['id']?>">
        <button type="submit" style="background:#dc2626; color:#fff; border:none; padding:8px 12px; border-radius:4px; cursor:pointer; width:100%;">Eliminar Subasta</button>
      </form>
    <?php endif; ?>
  </section>

  <section>
    <h2>Historial de Pujas</h2>
    <?php if(empty($historial)): ?>
      <p>Aún no hay pujas para este artículo. ¡Sé el primero!</p>
    <?php else: ?>
      <table>
        <tr>
          <th>Fecha</th>
          <th>Usuario</th>
          <th>Monto</th>
        </tr>
        <?php foreach($historial as $h): ?>
          <tr>
            <td><?=e($h['fecha'])?></td>
            <td><?=e($h['usuario'])?></td>
            <td>Q <?=number_format($h['monto'], 2)?></td>
          </tr>
        <?php endforeach; ?>
      </table>
    <?php endif; ?>
  </section>
</div>
