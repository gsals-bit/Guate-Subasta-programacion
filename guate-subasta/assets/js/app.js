document.querySelectorAll('form').forEach(f=>f.addEventListener('submit',()=>{const b=f.querySelector('button');if(b){b.disabled=true;b.textContent='Procesando...';}}));
// Ampliación de imágenes con teclado y clic.
document.addEventListener('click', function(ev){
 const target=ev.target.closest('img.zoomable'); if(!target)return;
 const overlay=document.createElement('div'); overlay.className='zoom-overlay';overlay.tabIndex=0;
 const img=document.createElement('img');img.src=target.src;img.alt=target.alt;overlay.appendChild(img);
 overlay.addEventListener('click',()=>overlay.remove());document.body.appendChild(overlay);overlay.focus();
});
document.addEventListener('keydown',ev=>{if(ev.key==='Escape')document.querySelector('.zoom-overlay')?.remove();if((ev.key==='Enter'||ev.key===' ')&&ev.target.matches('img.zoomable')){ev.preventDefault();ev.target.click();}});
