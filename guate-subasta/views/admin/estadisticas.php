<?php
$datos=ReporteEstadisticas::filas($db);
$diarios=ReporteEstadisticas::pagosDiarios($db,7);
$totalDemo=array_sum(array_map(fn($x)=>(float)$x['cobrado_demo'],$datos));
$totalPujas=array_sum(array_map(fn($x)=>(int)$x['total_pujas'],$datos));
$totalCanceladas=array_sum(array_map(fn($x)=>(int)$x['canceladas'],$datos));
$porEstado=[];
foreach($datos as $d) $porEstado[$d['estado']]=($porEstado[$d['estado']]??0)+1;
$top=$datos;
usort($top,fn($a,$b)=>(float)$b['monto_actual']<=>(float)$a['monto_actual']);
$top=array_slice($top,0,8);
$jsEstado=[]; foreach($porEstado as $estado=>$total) $jsEstado[]=['nombre'=>$estado,'total'=>$total];
$jsSubastas=array_map(fn($r)=>['nombre'=>'#'.$r['id'].' '.mb_substr($r['articulo'],0,13),'monto'=>(float)$r['monto_actual']],$top);
$jsPagos=array_map(fn($r)=>['nombre'=>date('d/m',strtotime($r['fecha'])),'monto'=>(float)$r['monto']],$diarios);
$jsFlags=JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT;
?>
<style>
.gs-stats-actions{display:flex;gap:10px;flex-wrap:wrap;margin:18px 0 22px}
.gs-stats-actions a,.gs-stats-actions button,.gs-chart button{border:0;cursor:pointer;border-radius:8px;padding:10px 14px;background:#1756bf;color:#fff;font-weight:600;text-decoration:none}
.gs-stats-actions .gs-alt,.gs-chart button{background:#edf3fc;color:#12315d;border:1px solid #cbd7ec}
.gs-report-cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin:12px 0 20px}
.gs-report-card{background:#fff;border:1px solid #e0e6ef;border-radius:14px;padding:20px}
.gs-report-card small{display:block;color:#596777;margin-bottom:8px}
.gs-report-card strong{font-size:27px;color:#092c5d}
.gs-report-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px}
.gs-chart{min-width:0;background:#fff;padding:20px;border:1px solid #e0e6ef;border-radius:14px}
.gs-chart h2{margin:0 0 6px;font-size:18px;color:#092c5d}
.gs-chart p{margin:0 0 14px;color:#566478;font-size:13px}
.gs-chart canvas{width:100%;height:auto;display:block;max-width:760px;background:#fff}
.gs-chart button{margin-top:10px;font-size:13px}
.gs-report-table{width:100%;border-collapse:collapse}
.gs-report-table th,.gs-report-table td{padding:12px 9px;text-align:left;border-bottom:1px solid #e5eaf1}
.gs-scroll{overflow-x:auto}
@media(max-width:850px){.gs-report-grid{grid-template-columns:1fr}}
@media print{.gs-stats-actions,.gs-chart button,aside,header,nav{display:none!important}.shell{display:block!important}main{width:100%!important;margin:0!important;padding:0!important}.gs-chart{break-inside:avoid}.gs-report-grid{grid-template-columns:repeat(2,1fr)}body{background:white!important}}
</style>
<h1>Estadísticas administrativas</h1>
<p>Gráficas con información de MySQL. Los pagos corresponden exclusivamente a tarjetas ficticias de demostración.</p>
<div class="gs-stats-actions">
  <a href="index.php?action=exportar_estadisticas">⬇ Descargar datos CSV</a>
  <button type="button" class="gs-alt" onclick="window.print()">🖨 Imprimir / Guardar reporte como PDF</button>
</div>
<div class="gs-report-cards">
 <div class="gs-report-card"><small>Subastas</small><strong><?=count($datos)?></strong></div>
 <div class="gs-report-card"><small>Pujas vigentes</small><strong><?=$totalPujas?></strong></div>
 <div class="gs-report-card"><small>Pujas canceladas</small><strong><?=$totalCanceladas?></strong></div>
 <div class="gs-report-card"><small>Compras con tarjeta demo</small><strong>Q <?=number_format($totalDemo,2)?></strong></div>
</div>
<div class="gs-report-grid">
  <section class="gs-chart"><h2>Montos por subasta</h2><p>Hasta 8 subastas, ordenadas por monto actual.</p><canvas id="gs-grafica-subastas" width="760" height="390"></canvas><button type="button" data-gs-download="gs-grafica-subastas">⬇ Descargar PNG</button></section>
  <section class="gs-chart"><h2>Estados de las subastas</h2><p>Distribución entre activas, programadas, cerradas y finalizadas.</p><canvas id="gs-grafica-estados" width="760" height="390"></canvas><button type="button" data-gs-download="gs-grafica-estados">⬇ Descargar PNG</button></section>
  <section class="gs-chart"><h2>Pagos por día</h2><p>Últimos 7 días, en quetzales (solo compras aprobadas).</p><canvas id="gs-grafica-pagos" width="760" height="390"></canvas><button type="button" data-gs-download="gs-grafica-pagos">⬇ Descargar PNG</button></section>
  <section class="gs-chart"><h2>Participación en pujas</h2><p>Comparación entre ofertas vigentes y canceladas.</p><canvas id="gs-grafica-pujas" width="760" height="390"></canvas><button type="button" data-gs-download="gs-grafica-pujas">⬇ Descargar PNG</button></section>
</div>
<section class="panel" style="margin-top:20px">
  <h2>Detalle por subasta</h2>
  <div class="gs-scroll"><table class="gs-report-table"><thead><tr><th>ID</th><th>Artículo</th><th>Estado</th><th>Monto actual</th><th>Pujas</th><th>Canceladas</th><th>Pago demo</th><th>Recibo</th></tr></thead><tbody>
  <?php foreach($datos as $d): ?><tr>
    <td>#<?=(int)$d['id']?></td><td><?=e($d['articulo'])?></td><td><?=e($d['estado'])?></td>
    <td>Q <?=number_format((float)$d['monto_actual'],2)?></td><td><?=(int)$d['total_pujas']?></td>
    <td><?=(int)$d['canceladas']?></td><td>Q <?=number_format((float)$d['cobrado_demo'],2)?></td>
    <td><?php if((float)$d['cobrado_demo']>0): ?><a href="index.php?page=factura&amp;id=<?=(int)$d['id']?>">Ver recibo</a><?php else: ?>—<?php endif; ?></td>
  </tr><?php endforeach; ?>
  <?php if(!$datos): ?><tr><td colspan="8">Aún no hay subastas registradas.</td></tr><?php endif; ?>
  </tbody></table></div>
</section>
<p><a class="button" href="index.php?page=auditoria">Ver bitácora completa</a></p>
<script>
(()=>{
 const auctionData=<?=json_encode($jsSubastas,$jsFlags)?>;
 const stateData=<?=json_encode($jsEstado,$jsFlags)?>;
 const paymentData=<?=json_encode($jsPagos,$jsFlags)?>;
 const bidData=[{nombre:'Vigentes',total:<?=$totalPujas?>},{nombre:'Canceladas',total:<?=$totalCanceladas?>}];
 const colors=['#2460c5','#1b9d83','#efa73b','#c64874','#7059b6','#45acc1'];
 const money=x=>'Q '+Number(x||0).toLocaleString('es-GT',{maximumFractionDigits:0});
 function ctxFor(id){const c=document.getElementById(id),x=c.getContext('2d'); x.fillStyle='#fff';x.fillRect(0,0,c.width,c.height);x.font='14px Arial';return {c,x}}
 function blank(x,message='Sin datos todavía'){x.fillStyle='#66748a';x.font='21px Arial';x.textAlign='center';x.fillText(message,380,190);x.textAlign='left'}
 function bars(id,items){const {x}=ctxFor(id);if(!items.length){blank(x);return}const max=Math.max(1,...items.map(i=>i.monto));const y0=306,H=248,l=60,usable=680;const w=usable/items.length;
  x.strokeStyle='#e5eaf2';x.fillStyle='#637185';x.font='12px Arial';for(let k=0;k<5;k++){const y=y0-k*H/4;x.beginPath();x.moveTo(l,y);x.lineTo(740,y);x.stroke();x.fillText(money(max*k/4),4,y+4)}
  items.forEach((it,i)=>{const h=H*(it.monto/max),bw=Math.max(8,w*0.58),bx=l+i*w+(w-bw)/2;x.fillStyle=colors[i%colors.length];x.fillRect(bx,y0-h,bw,h);x.fillStyle='#24344a';x.font='12px Arial';x.textAlign='center';x.fillText(money(it.monto),bx+bw/2,Math.max(38,y0-h-10));x.save();x.translate(bx+bw/2,333);x.rotate(-Math.PI/7);x.fillText(it.nombre,0,0);x.restore()});x.textAlign='left';}
 function doughnut(id,items){const {x}=ctxFor(id);const total=items.reduce((s,a)=>s+Number(a.total),0);if(!total){blank(x);return}let theta=-Math.PI/2;items.forEach((it,i)=>{const end=theta+Math.PI*2*it.total/total;x.beginPath();x.arc(270,185,128,theta,end);x.arc(270,185,70,end,theta,true);x.closePath();x.fillStyle=colors[i%colors.length];x.fill();theta=end});x.fillStyle='#183860';x.textAlign='center';x.font='bold 27px Arial';x.fillText(total,270,193);x.textAlign='left';items.forEach((it,i)=>{x.fillStyle=colors[i%colors.length];x.fillRect(470,85+i*44,16,16);x.fillStyle='#26374d';x.font='16px Arial';x.fillText(it.nombre+' ('+it.total+')',498,99+i*44)})}
 function line(id,items){const {x}=ctxFor(id);if(!items.length){blank(x);return}const max=Math.max(1,...items.map(i=>i.monto));const left=65,right=715,top=50,bottom=314;x.strokeStyle='#e5eaf2';x.font='12px Arial';x.fillStyle='#60718b';for(let k=0;k<5;k++){let y=bottom-k*(bottom-top)/4;x.beginPath();x.moveTo(left,y);x.lineTo(right,y);x.stroke();x.fillText(money(max*k/4),4,y+4)}
   const pos=items.map((it,i)=>({x:left+(right-left)*i/Math.max(1,items.length-1),y:bottom-it.monto/max*(bottom-top)}));x.lineWidth=3;x.strokeStyle='#2460c5';x.beginPath();pos.forEach((p,i)=>i?x.lineTo(p.x,p.y):x.moveTo(p.x,p.y));x.stroke();pos.forEach((p,i)=>{x.beginPath();x.arc(p.x,p.y,5,0,Math.PI*2);x.fillStyle='#1b9d83';x.fill();x.fillStyle='#25374c';x.textAlign='center';x.font='12px Arial';x.fillText(items[i].nombre,p.x,345);x.fillText(money(items[i].monto),p.x,Math.max(22,p.y-12))});x.textAlign='left';}
 bars('gs-grafica-subastas',auctionData);doughnut('gs-grafica-estados',stateData);line('gs-grafica-pagos',paymentData);doughnut('gs-grafica-pujas',bidData);
 document.querySelectorAll('[data-gs-download]').forEach(btn=>btn.addEventListener('click',()=>{
  const canvas=document.getElementById(btn.dataset.gsDownload);if(!canvas)return;
  const link=document.createElement('a');link.download=btn.dataset.gsDownload+'_'+new Date().toISOString().slice(0,10)+'.png';link.href=canvas.toDataURL('image/png');link.click();
 }));
})();
</script>
