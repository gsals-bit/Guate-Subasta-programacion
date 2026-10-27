<section class="panel" style="max-width:660px;margin:24px auto">
<h1>Verificación de comprobante DEMO</h1>
<?php if (!$comprobante): ?>
 <p style="color:#ac2830">Referencia no encontrada o sin pago de demostración aprobado.</p>
<?php else: ?>
 <p style="color:#167a47;font-weight:bold">✓ La referencia corresponde a una compra simulada registrada.</p>
 <p><strong>Referencia:</strong> <?=e($comprobante['referencia_demo'])?></p>
 <p><strong>Artículo:</strong> <?=e($comprobante['articulo'])?></p>
 <p><strong>Monto:</strong> Q <?=number_format((float)$comprobante['total'],2)?></p>
 <p><strong>Fecha:</strong> <?=e(date('d/m/Y H:i',strtotime($comprobante['fecha_pago'])))?></p>
<?php endif; ?>
 <hr><p><strong>Importante:</strong> Esto NO es una factura fiscal ni prueba de un cobro bancario real. La verificación funciona solamente contra la base de datos académica de Guate-Subasta.</p>
 <a href="index.php">Ir al inicio</a>
</section>
