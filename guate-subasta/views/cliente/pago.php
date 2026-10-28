<a class="back-link" href="index.php?page=mis-pujas">
  <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
  Volver a mis pujas
</a>
<div class="title-row">
  <h1>Pagar tu compra</h1>
  <span class="badge warn">Demostración</span>
</div>

<?php if (!$pago): ?>
  <section class="panel narrow">
    <div class="empty">
      <p>Solo el ganador puede pagar un artículo, y únicamente cuando la subasta ya finalizó.</p>
      <a class="button sm" href="index.php?page=mis-pujas">Volver a mis pujas</a>
    </div>
  </section>
<?php else:
  $pagado = (($pago['pago']['estado'] ?? '') === 'pagado');
?>
<p>Este pago es una simulación: no se hace ningún cargo bancario.</p>

<div class="pay-grid">
  <section class="panel">
    <div class="price-label">Total a pagar</div>
    <div class="pay-total">Q <?=number_format((float)$pago['monto'], 2)?></div>
    <div class="summary-line"><span>Artículo</span><b><?=e($pago['articulo'])?></b></div>
    <div class="summary-line"><span>Subasta</span><b>#<?=(int)$pago['id']?></b></div>

    <div class="cc" aria-hidden="true">
      <div class="cc-top"><span>Tarjeta ficticia</span><span class="cc-chip"></span></div>
      <div class="cc-num" id="tarjeta-vista"><?= $pagado ? 'DEMO •••• •••• 0000' : '•••• •••• •••• ••••' ?></div>
      <div class="cc-name" id="titular-vista"><?= $pagado ? 'USUARIO DEMOSTRACION' : 'TITULAR DE PRUEBA' ?></div>
    </div>
  </section>

  <section class="panel">
  <?php if ($pagado): ?>
    <div class="notice success" style="margin-top:0">
      <strong>Compra registrada correctamente</strong>
      <p>Este es un comprobante de prueba. No se realizó ningún cargo bancario.</p>
    </div>
    <div class="summary-line"><span>Medio de pago</span><b>Tarjeta ficticia</b></div>
    <div class="summary-line"><span>Importe</span><b>Q <?=number_format((float)$pago['monto'], 2)?></b></div>
    <div class="summary-line"><span>Referencia</span><b><?=e($pago['pago']['referencia_demo'] ?? '')?></b></div>
    <div class="row-actions" style="margin-top:18px">
      <a class="button gold" style="margin-top:0" href="index.php?page=factura&amp;id=<?=(int)$pago['id']?>">Ver y descargar comprobante con QR</a>
      <a class="button ghost" style="margin-top:0" href="index.php?page=mis-pujas">Mis pujas</a>
    </div>
  <?php else: ?>
    <h2>Datos de la tarjeta</h2>
    <div class="notice warn" style="margin-top:0"><strong>Solo demostración.</strong> No introduzcas datos de tarjetas bancarias reales.</div>
    <form method="post" action="index.php?action=pagar_demo" id="pago-demo-form">
      <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
      <input type="hidden" name="subasta_id" value="<?=(int)$pago['id']?>">
      <label for="demo-nombre">Nombre del titular ficticio</label>
      <input id="demo-nombre" readonly required placeholder="Titular de prueba">
      <label for="demo-numero">Número ficticio</label>
      <input id="demo-numero" name="demo_numero" readonly required placeholder="Se llena automáticamente">
      <div class="form-row">
        <div><label for="demo-vencimiento">Vencimiento</label><input id="demo-vencimiento" name="demo_vencimiento" readonly required></div>
        <div><label for="demo-cvv">CVV ficticio</label><input id="demo-cvv" name="demo_cvv" readonly required></div>
      </div>
      <input type="hidden" name="demo_confirmacion" id="demo-confirmacion" value="0">
      <button type="button" id="rellenar-demo" class="ghost block">Llenar con datos ficticios</button>
      <button type="submit" id="confirmar-demo" class="gold block" disabled>Pagar Q <?=number_format((float)$pago['monto'], 2)?></button>
    </form>
    <script>
    (function () {
      const rellenar = document.getElementById('rellenar-demo');
      const confirmar = document.getElementById('confirmar-demo');
      const formulario = document.getElementById('pago-demo-form');
      if (!rellenar || !confirmar || !formulario) return;
      rellenar.addEventListener('click', function () {
        document.getElementById('demo-nombre').value = 'USUARIO DEMOSTRACION';
        document.getElementById('demo-numero').value = 'DEMO-0000-0000-0000';
        document.getElementById('demo-vencimiento').value = '12/99';
        document.getElementById('demo-cvv').value = '000';
        document.getElementById('demo-confirmacion').value = '1';
        document.getElementById('tarjeta-vista').textContent = 'DEMO •••• •••• 0000';
        document.getElementById('titular-vista').textContent = 'USUARIO DEMOSTRACION';
        confirmar.disabled = false;
      });
      formulario.addEventListener('submit', function () {
        confirmar.disabled = true;
        confirmar.textContent = 'Registrando el pago de prueba...';
      });
    })();
    </script>
  <?php endif; ?>
  </section>
</div>
<?php endif; ?>