<h1>Comprar artículo con tarjeta · Demostración</h1>
<?php if (!$pago): ?>
<section class="panel">
    <p>Solo el ganador puede comprar un artículo cuando la subasta haya finalizado.</p>
    <a class="button" href="index.php?page=mis-pujas">Volver a mis pujas</a>
</section>
<?php else: ?>
<section class="panel" style="max-width:690px;margin:16px auto;background:#171719;color:#f7f1e8;border-radius:20px;padding:30px">
    <p style="letter-spacing:2px;color:#e0bb80">GUATE-SUBASTA · COMPRA CON TARJETA DE PRUEBA</p>
    <h2 style="font-size:40px;color:#fff;margin-bottom:5px">Q <?=number_format((float)$pago['monto'],2)?></h2>
    <p><?=e($pago['articulo'])?> · Subasta #<?=(int)$pago['id']?></p>
    <?php if (($pago['pago']['estado'] ?? '') === 'pagado'): ?>
        <div style="background:#153b30;color:#d6f8e4;padding:20px;border-radius:12px;margin-top:20px">
            <h2 style="color:#d6f8e4">Compra registrada correctamente</h2>
            <p><strong>Medio:</strong> Tarjeta ficticia (demostración)</p>
            <p><strong>Importe:</strong> Q <?=number_format((float)$pago['monto'],2)?></p>
            <p><strong>Referencia:</strong> <?=e($pago['pago']['referencia_demo'] ?? '')?></p>
            <p>Este es un comprobante de prueba. No se realizó ningún cargo bancario.</p>
        </div>
        <a class="button" href="index.php?page=factura&amp;id=<?=(int)$pago['id']?>" style="margin-top:14px">⬇ Ver y descargar comprobante con QR</a>
        <a class="button" href="index.php?page=mis-pujas" style="margin-left:8px">Mis pujas</a>
    <?php else: ?>
        <div style="background:linear-gradient(120deg,#171e34,#404e84);padding:25px;border-radius:18px;min-height:150px;margin:22px 0;color:white">
            <div style="font-size:22px">▣ TARJETA FICTICIA</div>
            <div style="font-size:24px;letter-spacing:3px;margin-top:26px" id="tarjeta-vista">•••• •••• •••• ••••</div>
            <div id="titular-vista" style="margin-top:18px">TITULAR DE PRUEBA</div>
        </div>
        <p style="color:#f6dca6"><strong>Solo demostración.</strong> No introduzcas datos de tarjetas bancarias reales.</p>
        <form method="post" action="index.php?action=pagar_demo" id="pago-demo-form">
            <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
            <input type="hidden" name="subasta_id" value="<?=(int)$pago['id']?>">
            <label for="demo-nombre">Nombre del titular ficticio</label>
            <input id="demo-nombre" readonly required placeholder="Titular de prueba">
            <label for="demo-numero">Número ficticio</label>
            <input id="demo-numero" name="demo_numero" readonly required placeholder="Se llena automaticamente">
            <div style="display:flex;gap:12px;flex-wrap:wrap">
                <div style="flex:1;min-width:150px"><label for="demo-vencimiento">Vencimiento</label><input id="demo-vencimiento" name="demo_vencimiento" readonly required></div>
                <div style="flex:1;min-width:150px"><label for="demo-cvv">CVV ficticio</label><input id="demo-cvv" name="demo_cvv" readonly required></div>
            </div>
            <input type="hidden" name="demo_confirmacion" id="demo-confirmacion" value="0">
            <button type="button" id="rellenar-demo" style="margin:15px 0;background:#d0ae65;color:#171717">Llenar tarjeta ficticia automáticamente</button>
            <button type="submit" id="confirmar-demo" disabled style="width:100%">Registrar compra de prueba por Q <?=number_format((float)$pago['monto'],2)?></button>
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
                confirmar.textContent = 'Registrando compra de prueba...';
            });
        })();
        </script>
    <?php endif; ?>
</section>
<?php endif; ?>
