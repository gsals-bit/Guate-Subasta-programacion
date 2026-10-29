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
<div class="page-head">
  <div>
    <h1>Estadísticas</h1>
    <p>Datos tomados de MySQL. Los pagos son compras con tarjeta ficticia de demostración.</p>
  </div>
  <div class="audit-actions">
    <a class="button ghost" href="index.php?action=exportar_estadisticas">Descargar datos CSV</a>
    <button type="button" class="ghost" onclick="window.print()">Imprimir o guardar como PDF</button>
  </div>
</div>

<div class="stats kpi">
  <div><small>Subastas</small><b><?=count($datos)?></b></div>
  <div><small>Pujas vigentes</small><b><?=$totalPujas?></b></div>
  <div><small>Pujas canceladas</small><b><?=$totalCanceladas?></b></div>
  <div><small>Compras con tarjeta demo</small><b>Q <?=number_format($totalDemo,2)?></b></div>
</div>

<div class="chart-grid">
  <section class="chart-card">
    <h2>Montos por subasta</h2>
    <p>Las 8 subastas con mayor monto actual.</p>
    <canvas id="gs-grafica-subastas" width="760" height="390"></canvas>
    <button type="button" class="sm ghost" data-gs-download="gs-grafica-subastas">Descargar PNG</button>
  </section>
  <section class="chart-card">
    <h2>Estados de las subastas</h2>
    <p>Reparto entre activas, programadas, cerradas y finalizadas.</p>
    <canvas id="gs-grafica-estados" width="760" height="390"></canvas>
    <button type="button" class="sm ghost" data-gs-download="gs-grafica-estados">Descargar PNG</button>
  </section>
  <section class="chart-card">
    <h2>Pagos por día</h2>
    <p>Últimos 7 días, en quetzales (solo compras aprobadas).</p>
    <canvas id="gs-grafica-pagos" width="760" height="390"></canvas>
    <button type="button" class="sm ghost" data-gs-download="gs-grafica-pagos">Descargar PNG</button>
  </section>
  <section class="chart-card">
    <h2>Participación en pujas</h2>
    <p>Ofertas vigentes frente a canceladas.</p>
    <canvas id="gs-grafica-pujas" width="760" height="390"></canvas>
    <button type="button" class="sm ghost" data-gs-download="gs-grafica-pujas">Descargar PNG</button>
  </section>
</div>

<section class="panel table-panel" style="margin-top:22px">
  <div class="panel-head" style="padding:14px 12px 0">
    <h2>Detalle por subasta</h2>
  </div>
  <?php if(!$datos): ?>
    <div class="empty" style="padding:6px 12px 14px"><p>Todavía no hay subastas registradas.</p></div>
  <?php else: ?>
  <table>
    <thead><tr><th>Artículo</th><th>Estado</th><th>Monto actual</th><th>Pujas</th><th>Canceladas</th><th>Pago demo</th><th>Recibo</th></tr></thead>
    <tbody>
    <?php foreach($datos as $d): ?>
      <tr>
        <td><strong><?=e($d['articulo'])?></strong><br><small class="muted">Subasta #<?=(int)$d['id']?></small></td>
        <td><span class="badge <?=e(strtolower((string)$d['estado']))?>"><?=e($d['estado'])?></span></td>
        <td>Q <?=number_format((float)$d['monto_actual'],2)?></td>
        <td><?=(int)$d['total_pujas']?></td>
        <td><?=(int)$d['canceladas']?></td>
        <td>Q <?=number_format((float)$d['cobrado_demo'],2)?></td>
        <td><?php if((float)$d['cobrado_demo']>0): ?><a href="index.php?page=factura&amp;id=<?=(int)$d['id']?>">Ver recibo</a><?php else: ?><span class="muted">&mdash;</span><?php endif; ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</section>

<p class="audit-actions" style="margin-top:20px"><a class="button sm ghost" href="index.php?page=auditoria">Ver la bitácora completa</a></p>

<script>
(()=>{
 const auctionData=<?=json_encode($jsSubastas,$jsFlags)?>;
 const stateData=<?=json_encode($jsEstado,$jsFlags)?>;
 const paymentData=<?=json_encode($jsPagos,$jsFlags)?>;
 const bidData=[{nombre:'Vigentes',total:<?=$totalPujas?>},{nombre:'Canceladas',total:<?=$totalCanceladas?>}];
 const colors=['#2f5bea','#0f9d74','#e8a317','#d6403f','#7c5cf0','#2aa7c0'];
 const INK='#121a2e',MUTED='#667089',GRID='#e3e6ee';
 const font=(px,w)=>(w?w+' ':'')+px+'px "DM Sans", Arial, sans-serif';
 const money=x=>'Q '+Number(x||0).toLocaleString('es-GT',{maximumFractionDigits:0});
 const cap=s=>String(s).charAt(0).toUpperCase()+String(s).slice(1);
 function ctxFor(id){const c=document.getElementById(id),x=c.getContext('2d');x.fillStyle='#fff';x.fillRect(0,0,c.width,c.height);x.font=font(14);return {c,x}}
 function blank(x,message='Sin datos todavía'){x.fillStyle=MUTED;x.font=font(21);x.textAlign='center';x.fillText(message,380,190);x.textAlign='left'}
 function bars(id,items){const {x}=ctxFor(id);if(!items.length){blank(x);return}const max=Math.max(1,...items.map(i=>i.monto));const y0=306,H=248,l=60,usable=680;const w=usable/items.length;
  x.strokeStyle=GRID;x.fillStyle=MUTED;x.font=font(12);for(let k=0;k<5;k++){const y=y0-k*H/4;x.beginPath();x.moveTo(l,y);x.lineTo(740,y);x.stroke();x.fillText(money(max*k/4),4,y+4)}
  items.forEach((it,i)=>{const h=H*(it.monto/max),bw=Math.max(8,w*0.58),bx=l+i*w+(w-bw)/2;x.fillStyle=colors[0];x.fillRect(bx,y0-h,bw,h);x.fillStyle=INK;x.font=font(12,'600');x.textAlign='center';x.fillText(money(it.monto),bx+bw/2,Math.max(38,y0-h-10));x.save();x.translate(bx+bw/2,333);x.rotate(-Math.PI/7);x.fillStyle=MUTED;x.font=font(12);x.fillText(it.nombre,0,0);x.restore()});x.textAlign='left';}
 function doughnut(id,items){const {x}=ctxFor(id);const total=items.reduce((s,a)=>s+Number(a.total),0);if(!total){blank(x);return}let theta=-Math.PI/2;items.forEach((it,i)=>{const end=theta+Math.PI*2*it.total/total;x.beginPath();x.arc(270,185,128,theta,end);x.arc(270,185,70,end,theta,true);x.closePath();x.fillStyle=colors[i%colors.length];x.fill();theta=end});x.fillStyle=INK;x.textAlign='center';x.font=font(30,'bold');x.fillText(total,270,195);x.textAlign='left';items.forEach((it,i)=>{x.fillStyle=colors[i%colors.length];x.fillRect(470,85+i*44,16,16);x.fillStyle=INK;x.font=font(16);x.fillText(cap(it.nombre)+' ('+it.total+')',498,99+i*44)})}
 function line(id,items){const {x}=ctxFor(id);if(!items.length){blank(x);return}const max=Math.max(1,...items.map(i=>i.monto));const left=65,right=715,top=50,bottom=314;x.strokeStyle=GRID;x.font=font(12);x.fillStyle=MUTED;for(let k=0;k<5;k++){let y=bottom-k*(bottom-top)/4;x.beginPath();x.moveTo(left,y);x.lineTo(right,y);x.stroke();x.fillText(money(max*k/4),4,y+4)}
   const pos=items.map((it,i)=>({x:left+(right-left)*i/Math.max(1,items.length-1),y:bottom-it.monto/max*(bottom-top)}));x.lineWidth=3;x.strokeStyle=colors[0];x.beginPath();pos.forEach((p,i)=>i?x.lineTo(p.x,p.y):x.moveTo(p.x,p.y));x.stroke();x.lineWidth=2.5;pos.forEach((p,i)=>{x.beginPath();x.arc(p.x,p.y,5.5,0,Math.PI*2);x.fillStyle='#fff';x.fill();x.strokeStyle=colors[0];x.stroke();x.fillStyle=MUTED;x.textAlign='center';x.font=font(12);x.fillText(items[i].nombre,p.x,345);x.fillStyle=INK;x.font=font(12,'600');x.fillText(money(items[i].monto),p.x,Math.max(22,p.y-14))});x.textAlign='left';}
 function drawAll(){bars('gs-grafica-subastas',auctionData);doughnut('gs-grafica-estados',stateData);line('gs-grafica-pagos',paymentData);doughnut('gs-grafica-pujas',bidData)}
 drawAll();
 if(document.fonts&&document.fonts.ready) document.fonts.ready.then(drawAll);
 document.querySelectorAll('[data-gs-download]').forEach(btn=>btn.addEventListener('click',()=>{
  const canvas=document.getElementById(btn.dataset.gsDownload);if(!canvas)return;
  const link=document.createElement('a');link.download=btn.dataset.gsDownload+'_'+new Date().toISOString().slice(0,10)+'.png';link.href=canvas.toDataURL('image/png');link.click();
 }));
})();
</script>