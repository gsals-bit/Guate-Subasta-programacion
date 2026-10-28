<?php if (!$factura): ?>
<h1>Comprobante no disponible</h1>
<section class="panel narrow">
  <div class="empty">
    <p>No existe un pago de tarjeta ficticia aprobado para esta subasta, o no tienes permiso para consultarlo.</p>
    <a class="button sm" href="index.php">Volver al inicio</a>
  </div>
</section>
<?php else:
$referencia=(string)$factura['referencia_demo'];
$dominio=preg_replace('/[^a-zA-Z0-9.\-:\[\]]/', '', (string)($_SERVER['HTTP_HOST']??'localhost'));
$esHttps=(!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off');
$urlQr=($esHttps?'https':'http').'://'.$dominio.base_url('index.php').'?page=verificar_factura&ref='.rawurlencode($referencia);
if (!isset($ic, $icons)) { $ic = fn($d) => ''; $icons = []; }
?>
<a class="back-link gs-back" href="index.php?page=mis-pujas">
  <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
  Volver a mis pujas
</a>
<h1 class="gs-back">Comprobante de compra</h1>
<div class="gs-fact-actions gs-back">
  <button type="button" id="gs-descargar-factura" class="gold">Descargar PDF</button>
  <button type="button" class="ghost" onclick="window.print()">Imprimir</button>
</div>

<article id="gs-factura-pdf" class="gs-fact-paper">
  <div class="gs-fact-top">
    <div>
      <div class="gs-fact-logo"><?=$ic($icons['gavel'] ?? '')?>Guate-Subasta</div>
      <p>Confirmación de compra en subasta</p>
      <span class="gs-stamp">Comprobante de demostración</span>
    </div>
    <div class="gs-fact-ref">
      <h3>Referencia</h3>
      <strong><?=e($referencia)?></strong>
      <p>Pago #<?=(int)$factura['pago_id']?></p>
      <p><?=e(date('d/m/Y h:i A',strtotime($factura['fecha_pago'])))?></p>
    </div>
  </div>

  <div class="gs-fact-section">
    <div>
      <h3>Comprador</h3>
      <p><strong><?=e($factura['comprador'])?></strong></p>
      <p><?=e($factura['comprador_email'])?></p>
    </div>
    <div>
      <h3>Detalle de la operación</h3>
      <p>Subasta #<?=(int)$factura['subasta_id']?></p>
      <p>Orden #<?=(int)$factura['orden_id']?></p>
      <p>Estado: <strong>Pagado (simulación)</strong></p>
      <p>Método: tarjeta ficticia</p>
    </div>
  </div>

  <h3>Artículo adquirido</h3>
  <table class="gs-fact-table">
    <thead><tr><th>Descripción</th><th>Cant.</th><th>Precio unitario</th><th>Importe</th></tr></thead>
    <tbody><tr>
      <td>
        <strong><?=e($factura['articulo'])?></strong>
        <p><?=e($factura['descripcion']?:'Sin descripción registrada')?></p>
        <small>Categoría: <?=e($factura['categoria'])?><?php if($factura['modelo']): ?>, modelo: <?=e($factura['modelo'])?><?php endif; ?></small>
      </td>
      <td>1</td>
      <td>Q <?=number_format((float)$factura['total'],2)?></td>
      <td>Q <?=number_format((float)$factura['total'],2)?></td>
    </tr></tbody>
  </table>
  <div class="gs-fact-total"><span>Total registrado</span> Q <?=number_format((float)$factura['total'],2)?></div>

  <div class="gs-fact-foot">
    <div>
      <h3>Verificación por código QR</h3>
      <p>Escanea el código para verificar la referencia de este pago de demostración.</p>
      <p style="overflow-wrap:anywhere;font-size:.75rem"><a href="<?=e($urlQr)?>" target="_blank" rel="noopener">Comprobar referencia</a></p>
    </div>
    <div class="gs-qr" id="gs-codigo-qr" aria-label="Código QR de verificación"></div>
  </div>
  <p class="gs-fact-note">Este documento acredita exclusivamente una transacción simulada del proyecto académico Guate-Subasta. No se ha realizado un cargo bancario real, no contiene datos de una tarjeta real y NO es factura fiscal FEL ni documento tributario autorizado. El código QR verifica solamente el registro en esta base de datos de demostración. Si la aplicación sigue instalada en localhost, la verificación funciona únicamente en ese equipo.</p>
</article>

<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/html2pdf.js@0.10.1/dist/html2pdf.bundle.min.js"></script>
<script>
(function(){
 const qrTexto=<?=json_encode($urlQr, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT)?>;
 const referencia=<?=json_encode($referencia, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT)?>;
 const qr=document.getElementById('gs-codigo-qr');
 if(typeof QRCode==='function') new QRCode(qr,{text:qrTexto,width:136,height:136,colorDark:'#142c4b',colorLight:'#ffffff',correctLevel:QRCode.CorrectLevel.M});
 else qr.textContent='QR no disponible sin conexión a biblioteca. Usa el enlace de verificación.';
 const boton=document.getElementById('gs-descargar-factura');
 boton.addEventListener('click',async function(){
  if(typeof html2pdf==='undefined'){alert('No se cargó la biblioteca de PDF. Usa «Imprimir» y elige guardar como PDF.');window.print();return;}
  const txt=boton.textContent;boton.disabled=true;boton.textContent='Generando PDF...';
  try {await html2pdf().set({margin:8,filename:'GuateSubasta_'+referencia+'.pdf',image:{type:'jpeg',quality:0.98},html2canvas:{scale:2,useCORS:true,backgroundColor:'#fff'},jsPDF:{unit:'mm',format:'a4',orientation:'portrait'},pagebreak:{mode:['avoid-all','css']}}).from(document.getElementById('gs-factura-pdf')).save();}
  catch(err){alert('No se pudo crear el PDF. Usa «Imprimir» y elige guardar como PDF.');}
  finally{boton.disabled=false;boton.textContent=txt;}
 });
})();
</script>
<?php endif; ?>