//  HELPERS 
function tipoIcon(tipo){const icons={medicamento:'fa-pills',consulta:'fa-stethoscope',exercicio:'fa-running',urgente:'fa-exclamation-triangle',geral:'fa-bell'};return icons[tipo]||'fa-bell';}

function formatDatahora(dt){
  try{
    const d=new Date(dt);const now=new Date();
    const mins=Math.round((d-now)/60000);
    if(Math.abs(mins)<60)return mins<0?Math.abs(mins)+'min atrás':'em '+mins+'min';
    if(Math.abs(mins)<1440)return mins<0?Math.round(Math.abs(mins)/60)+'h atrás':'em '+Math.round(mins/60)+'h';
    return d.toLocaleDateString('pt-PT',{day:'2-digit',month:'2-digit'})+' '+d.toTimeString().slice(0,5);
  }catch{return dt;}
}

// MODALS 
function fecharModal(id){const el=document.getElementById(id);if(el)el.classList.remove('open');}
document.addEventListener('click',function(e){
  if(e.target.classList.contains('modal-overlay'))e.target.classList.remove('open');
});

//  TOASTS 
function showToast(msg,type='info'){
  const c=document.getElementById('toastContainer');
  if(!c)return;
  const icons={success:'fa-check-circle',danger:'fa-exclamation-circle',warning:'fa-exclamation-triangle',info:'fa-info-circle'};
  const colors={success:'#10b981',danger:'#ef4444',warning:'#f59e0b',info:'#2563eb'};
  const t=document.createElement('div');
  t.className='toast '+type;
  t.innerHTML=`<i class="fas ${icons[type]||'fa-bell'} toast-icon" style="color:${colors[type]||'#2563eb'}"></i><span class="toast-msg">${msg}</span><button class="toast-close" onclick="this.parentElement.remove()">×</button>`;
  c.appendChild(t);
  setTimeout(()=>{t.style.animation='slideOut .3s ease forwards';setTimeout(()=>t.remove(),300);},4000);
}

//  SIDEBAR MOBILE 
function toggleSidebar(){
  const s = document.getElementById('sidebar');
  const b = document.getElementById('sidebarBackdrop');
  s.classList.toggle('open');
  if(b) b.classList.toggle('open', s.classList.contains('open'));
}
function closeSidebar(){
  document.getElementById('sidebar').classList.remove('open');
  const b = document.getElementById('sidebarBackdrop');
  if(b) b.classList.remove('open');
}
document.addEventListener('click',function(e){
  if(window.innerWidth<=900&&!e.target.closest('.sidebar')&&!e.target.closest('#sidebarToggle')&&!e.target.closest('.topbar-menu-btn'))closeSidebar();
  if(!e.target.closest('#notifPanel')&&!e.target.closest('#bell-btn')){
    const p=document.getElementById('notifPanel');if(p)p.classList.remove('open');
  }
});

//  BADGES — lê da BD via AJAX 
function atualizarBadges(){
  fetch(BASE_ROOT+'api/badges.php')
    .then(r=>r.json())
    .then(data=>{
      const count=data.lembretes_nao_lidos||0;
      const bellCount=document.getElementById('bell-count');
      const badgeSidebar=document.getElementById('badge-sidebar');
      const bellBtn=document.getElementById('bell-btn');
      if(bellCount){bellCount.textContent=count;bellCount.style.display=count>0?'flex':'none';}
      if(badgeSidebar){badgeSidebar.textContent=count;badgeSidebar.style.display=count>0?'inline':'none';}
      if(bellBtn){if(count>0)bellBtn.classList.add('pulsar');else bellBtn.classList.remove('pulsar');}
    })
    .catch(()=>{});
}

//  NOTIF PANEL — lê da BD via AJAX 
function toggleNotifPanel(){
  const p=document.getElementById('notifPanel');
  if(!p)return;
  p.classList.toggle('open');
  if(p.classList.contains('open'))renderNotifPanel();
}

function renderNotifPanel(){
  const el=document.getElementById('notif-list');
  if(!el)return;
  el.innerHTML='<div style="padding:16px;text-align:center;color:var(--text-muted);font-size:13px">A carregar...</div>';
  fetch(BASE_ROOT+'api/badges.php?lembretes=1')
    .then(r=>r.json())
    .then(data=>{
      const lems=data.lembretes||[];
      if(!lems.length){
        el.innerHTML='<div style="padding:24px;text-align:center;color:var(--text-muted);font-size:13px"><i class="fas fa-check-circle" style="font-size:24px;display:block;margin-bottom:8px;color:var(--secondary)"></i>Sem notificações pendentes</div>';
        return;
      }
      const cores={medicamento:'#3b82f6',consulta:'#f59e0b',exercicio:'#10b981',urgente:'#ef4444',geral:'#7c3aed'};
      el.innerHTML=lems.map(l=>`
        <div style="padding:12px 16px;border-bottom:1px solid var(--border);display:flex;align-items:flex-start;gap:10px">
          <div style="width:28px;height:28px;border-radius:50%;background:${cores[l.tipo]||'#7c3aed'};display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <i class="fas ${tipoIcon(l.tipo)}" style="font-size:11px;color:#fff"></i>
          </div>
          <div style="flex:1">
            <div style="font-weight:700;font-size:12.5px">${l.titulo}</div>
            <div style="font-size:11.5px;color:var(--text-muted);margin-top:2px">${formatDatahora(l.datahora)}</div>
          </div>
          <a href="${BASE_ROOT}lembretes_action.php?action=lida&id=${l.id}" onclick="setTimeout(()=>{renderNotifPanel();atualizarBadges();},300)" style="color:var(--secondary);font-size:13px;padding:2px 6px" title="Marcar lida"><i class="fas fa-check"></i></a>
          ${l.prioridade==='urgente'?'<span style="width:8px;height:8px;border-radius:50%;background:#ef4444;flex-shrink:0;margin-top:4px"></span>':''}
        </div>`).join('');
    })
    .catch(()=>{el.innerHTML='<div style="padding:16px;text-align:center;color:var(--danger)">Erro ao carregar</div>';});
}

function marcarTodasLidas(){
  const csrfToken = document.querySelector('meta[name=csrf-token]')?.content || '';
  fetch(BASE_ROOT+'lembretes_action.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded','X-CSRF-Token':csrfToken},body:'action=todas_lidas&csrf_token='+encodeURIComponent(csrfToken)})
    .then(()=>{renderNotifPanel();atualizarBadges();showToast('Todos os lembretes marcados como lidos','success');})
    .catch(()=>{});
}

//  STOCK BAIXO — aviso 
function verificarStockBaixo(){
  fetch(BASE_ROOT+'api/badges.php?stock=1')
    .then(r=>r.json())
    .then(data=>{
      if(data.stock_baixo&&data.stock_baixo.length){
        setTimeout(()=>{
          showToast('⚠️ Stock baixo: '+data.stock_baixo[0].nome+' ('+data.stock_baixo[0].quantidade+' un.)','danger');
        },3000);
      }
    })
    .catch(()=>{});
}

//  INIT 
(function init(){
  atualizarBadges();
  verificarStockBaixo();
})();


//  PARTILHA DE CONSULTA 
function copiarLink(url) {
  navigator.clipboard.writeText(url).then(() => {
    showToast('Link copiado para a área de transferência! 🔗', 'success');
  }).catch(() => {
    // fallback para browsers antigos
    const el = document.createElement('textarea');
    el.value = url;
    document.body.appendChild(el);
    el.select();
    document.execCommand('copy');
    document.body.removeChild(el);
    showToast('Link copiado! 🔗', 'success');
  });
}


