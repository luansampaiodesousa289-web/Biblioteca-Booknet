document.addEventListener('DOMContentLoaded',()=>{document.querySelector('.toast')&&setTimeout(()=>document.querySelector('.toast').remove(),4200);document.querySelectorAll('.menu-toggle').forEach(b=>b.addEventListener('click',()=>{
  const sidebar=document.querySelector('.sidebar');
  const nav=document.querySelector('.nav nav');
  if(sidebar) sidebar.classList.toggle('open');
  if(nav) nav.classList.toggle('open');
  const expanded=!!(sidebar?.classList.contains('open')||nav?.classList.contains('open'));
  b.setAttribute('aria-expanded',expanded?'true':'false');
  b.setAttribute('aria-label',expanded?'Fechar menu':'Abrir menu');
}));document.querySelectorAll('[data-confirm]').forEach(b=>b.addEventListener('click',e=>{if(!confirm(b.dataset.confirm))e.preventDefault()}));const input=document.querySelector('[data-catalog-search]');if(input){input.addEventListener('keydown',e=>{if(e.key==='Enter'){e.preventDefault();input.form?.requestSubmit()}})}const file=document.querySelector('[data-cover-input]');if(file)file.addEventListener('change',()=>{const img=document.querySelector('[data-cover-preview]');if(file.files[0]&&img)img.src=URL.createObjectURL(file.files[0])})});


function setBooknetTheme(theme){
  if(theme!=='dark' && theme!=='light') return;
  document.documentElement.dataset.theme=theme==='dark'?'dark':'';
  try{localStorage.setItem('booknet-theme',theme);}catch(e){}
  document.querySelectorAll('[data-theme-choice]').forEach(el=>{
    const active=el.dataset.themeChoice===theme;
    el.classList.toggle('theme-active',active);
    el.setAttribute('aria-pressed',active?'true':'false');
  });
}

document.addEventListener('DOMContentLoaded',()=>{
  let theme='light';
  try{theme=localStorage.getItem('booknet-theme')||'light';}catch(e){}
  setBooknetTheme(theme);
});


// Menu lateral no celular: fundo escuro, fecha ao tocar fora, num link ou ao apertar Esc.
document.addEventListener('DOMContentLoaded',()=>{
  const sidebar=document.querySelector('.sidebar');
  if(!sidebar) return;
  const backdrop=document.createElement('div');
  backdrop.className='sidebar-backdrop';
  document.body.appendChild(backdrop);
  const sync=()=>backdrop.classList.toggle('show',sidebar.classList.contains('open'));
  new MutationObserver(sync).observe(sidebar,{attributes:true,attributeFilter:['class']});
  const close=()=>sidebar.classList.remove('open');
  backdrop.addEventListener('click',close);
  sidebar.addEventListener('click',e=>{if(e.target.closest('a'))close()});
  document.addEventListener('keydown',e=>{if(e.key==='Escape')close()});
});


// Menu do site público: fecha ao tocar num link.
document.addEventListener('DOMContentLoaded',()=>{
  const nav=document.querySelector('.nav nav');
  nav&&nav.addEventListener('click',e=>{
  if(e.target.closest('a')){
    nav.classList.remove('open');
    document.querySelector('.nav .menu-toggle')?.setAttribute('aria-expanded','false');
    document.querySelector('.nav .menu-toggle')?.setAttribute('aria-label','Abrir menu');
  }
});
});
