<section class="panel verify">
<?php if (!$comprobante): ?>
  <div class="verify-icon fail">
    <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/></svg>
  </div>
  <h1>No se pudo verificar</h1>
  <p class="lead">La referencia no existe o no tiene un pago de demostración aprobado.</p>
<?php else: ?>
  <div class="verify-icon ok">
    <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
  </div>
  <h1>Comprobante verificado</h1>
  <p class="lead">La referencia corresponde a una compra simulada registrada.</p>

  <div class="summary-line"><span>Referencia</span><b><?=e($comprobante['referencia_demo'])?></b></div>
  <div class="summary-line"><span>Artículo</span><b><?=e($comprobante['articulo'])?></b></div>
  <div class="summary-line"><span>Monto</span><b>Q <?=number_format((float)$comprobante['total'],2)?></b></div>
  <div class="summary-line"><span>Fecha</span><b><?=e(date('d/m/Y h:i A',strtotime($comprobante['fecha_pago'])))?></b></div>
<?php endif; ?>

  <div class="notice warn">
    <strong>Importante:</strong> esto no es una factura fiscal ni prueba de un cobro bancario real. La verificación funciona solo contra la base de datos académica de Guate-Subasta.
  </div>
  <a class="button sm ghost" style="margin-top:18px" href="index.php">Ir al inicio</a>
</section>