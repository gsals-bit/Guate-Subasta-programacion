<?php if (!$factura): ?>
<h1>Comprobante no disponible</h1>
<section class="panel"><p>No existe un pago de tarjeta ficticia aprobado para esta subasta, o no tienes permiso para consultarlo.</p><a class="button" href="index.php">Volver al inicio</a></section>
<?php else:
$referencia=(string)$factura['referencia_demo'];
$dominio=preg_replace('/[^a-zA-Z0-9.\-:\[\]]/', '', (string)($_SERVER['HTTP_HOST']??'localhost'));
$esHttps=(!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off');
$urlQr=($esHttps?'https':'http').'://'.$dominio.base_url('index.php').'?page=verificar_factura&ref='.rawurlencode($referencia);
?>
<style>
.gs-fact-actions{display:flex;flex-wrap:wrap;gap:12px;margin:16px 0}
.gs-fact-actions button,.gs-fact-actions a{background:#1558c4;color:#fff;padding:12px 17px;border:0;border-radius:8px;cursor:pointer;text-decoration:none;font-size:14px;font-weight:bold}
.gs-fact-paper{background:#fff;color:#192b42;border:1px solid #d9e1ec;border-radius:12px;padding:32px;max-width:800px;margin:auto;font:15px/1.5 Arial,sans-serif}
.gs-fact-top{display:flex;justify-content:space-between;gap:15px;border-bottom:3px solid #15355e;padding-bottom:16px}
.gs-fact-logo{font-size:24px;color:#15355e;font-weight:bold}
.gs-fact-flag{color:#985700;font-weight:700;letter-spacing:.2px}
.gs-fact-section{display:grid;grid-template-columns:1fr 1fr;gap:18px;padding:22px 0}
.gs-fact-paper h3{color:#164376;font-size:15px;margin:0 0 8px}
.gs-fact-paper p{margin:5px 0}
.gs-fact-table{width:100%;border-collapse:collapse;margin:8px 0 24px}
.gs-fact-table th{background:#eaf1fa;color:#14355c}
.gs-fact-table th,.gs-fact-table td{padding:11px;border-bottom:1px solid #dce3ee;text-align:left;vertical-align:top}
.gs-fact-total{display:flex;justify-content:flex-end;font-size:24px;color:#15355e;font-weight:bold}
.gs-fact-foot{display:flex;justify-content:space-between;align-items:center;gap:20px;border-top:1px solid #dce3ee;margin-top:25px;padding-top:20px}
.gs-qr{width:146px;height:146px;display:flex;align-items:center;justify-content:center;background:#fff}
.gs-qr img,.gs-qr canvas{width:136px!important;height:136px!important}
.gs-fact-note{font-size:12px;color:#66768d;margin-top:16px;line-height:1.5}
@media(max-width:650px){.gs-fact-paper{padding:16px}.gs-fact-section{grid-template-columns:1fr}.gs-fact-top,.gs-fact-foot{flex-direction:column;align-items:flex-start}.gs-fact-table{font-size:12px}}
@media print{header,nav,aside,.gs-fact-actions,.gs-back{display:none!important}.shell{display:block!important}main{width:100%!important;margin:0!important;padding:0!important}.gs-fact-paper{border:0;border-radius:0;max-width:none;padding:0}.gs-fact-section{grid-template-columns:1fr 1fr}body{background:#fff!important}@page{size:A4;margin:13mm}}
</style>
<h1 class="gs-back">Comprobante de compra</h1>
<div class="gs-fact-actions">
 <button type="button" id="gs-descargar-factura">⬇ Descargar comprobante PDF</button>
 <button type="button" onclick="window.print()">🖨 Imprimir / Guardar como PDF</button>
 <a href="index.php?page=mis-pujas">Volver a mis pujas</a>
</div>
<article id="gs-factura-pdf" class="gs-fact-paper">
 <div class="gs-fact-top"><div><div class="gs-fact-logo">⚖ Guate-Subasta</div><p>Confirmación de compra en subasta</p><div class="gs-fact-flag">COMPROBANTE DE DEMOSTRACIÓN</div></div><div><h3>Referencia</h3><strong><?=e($referencia)?></strong><p><strong>Pago #<?=(int)$factura['pago_id']?></strong></p><p><?=e(date('d/m/Y H:i',strtotime($factura['fecha_pago'])))?></p></div></div>
 <div class="gs-fact-section"><div><h3>COMPRADOR</h3><p><strong><?=e($factura['comprador'])?></strong></p><p><?=e($factura['comprador_email'])?></p></div><div><h3>DETALLE DE LA OPERACIÓN</h3><p>Subasta #<?=(int)$factura['subasta_id']?></p><p>Orden #<?=(int)$factura['orden_id']?></p><p>Estado: <strong>Pagado (simulación)</strong></p><p>Método: Tarjeta ficticia</p></div></div>
 <h3>DETALLE DEL ARTÍCULO ADQUIRIDO</h3>
 <table class="gs-fact-table"><thead><tr><th>Descripción</th><th>Cant.</th><th>Precio unitario</th><th>Importe</th></tr></thead><tbody><tr>
   <td><strong><?=e($factura['articulo'])?></strong><p><?=e($factura['descripcion']?:'Sin descripción registrada')?></p><small>Categoría: <?=e($factura['categoria'])?><?php if($factura['modelo']): ?> · Modelo: <?=e($factura['modelo'])?><?php endif; ?></small></td>
   <td>1</td><td>Q <?=number_format((float)$factura['total'],2)?></td><td>Q <?=number_format((float)$factura['total'],2)?></td>
 </tr></tbody></table>
 <div class="gs-fact-total">Total registrado: Q <?=number_format((float)$factura['total'],2)?></div>
 <div class="gs-fact-foot"><div><h3>VERIFICACIÓN POR CÓDIGO QR</h3><p>Escanea para verificar la referencia de este pago de demostración.</p><p style="overflow-wrap:anywhere;font-size:11px"><a href="<?=e($urlQr)?>" target="_blank" rel="noopener">Comprobar referencia</a></p></div><div class="gs-qr" id="gs-codigo-qr" aria-label="Código QR de verificación"></div></div>
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
  if(typeof html2pdf==='undefined'){alert('No se cargó la biblioteca de PDF. Usa «Imprimir / Guardar como PDF».');window.print();return;}
  const txt=boton.textContent;boton.disabled=true;boton.textContent='Generando PDF...';
  try {await html2pdf().set({margin:8,filename:'GuateSubasta_'+referencia+'.pdf',image:{type:'jpeg',quality:0.98},html2canvas:{scale:2,useCORS:true,backgroundColor:'#fff'},jsPDF:{unit:'mm',format:'a4',orientation:'portrait'},pagebreak:{mode:['avoid-all','css']}}).from(document.getElementById('gs-factura-pdf')).save();}
  catch(err){alert('No se pudo crear el PDF. Usa «Imprimir / Guardar como PDF».');}
  finally{boton.disabled=false;boton.textContent=txt;}
 });
})();
</script>
<?php endif; ?>
