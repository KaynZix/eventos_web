<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Zonas de precio · {{ $evento->nombre }}</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<script src="https://cdn.tailwindcss.com"></script>

<style>
  /* Panel y tarjeta */
  .card{ background:rgba(17,24,39,.6); border:1px solid rgba(255,255,255,.08); border-radius:12px; }

  /* Lienzo fijo 1000x700 (coincide con tus coordenadas de mesas) */
  .map-shell{ overflow:auto; }
  #lienzo{
    width:1000px; height:700px; position:relative; border-radius:12px; overflow:hidden;
    background:
      linear-gradient(#ffffff10 1px, transparent 1px),
      linear-gradient(90deg,#ffffff10 1px, transparent 1px);
    background-size:24px 24px; background-color:#0b0f19;
  }

  /* Plano SVG solo visual */
  #plano{ position:absolute; inset:0; pointer-events:none; }

  /* Zona seleccionable/arrastrable/redimensionable */
  .zbox{
    position:absolute; border:2px dashed #fff; border-radius:10px;
    box-shadow: inset 0 0 0 1px rgba(255,255,255,.12);
    cursor:move;
  }
  .zbox .tag{
    position:absolute; left:8px; top:-22px; font-size:12px;
    background:rgba(0,0,0,.6); border:1px solid rgba(255,255,255,.2);
    padding:2px 6px; border-radius:6px; white-space:nowrap; color:#fff;
  }
  .zbox.selected{ outline:2px solid #f59e0b; }

  /* Manillas de resize */
  .z-handle{
    position:absolute; width:12px; height:12px; background:#f59e0b; border:2px solid #111;
    border-radius:50%; z-index:2;
  }
  .z-nw{ left:-7px; top:-7px; cursor:nwse-resize; }
  .z-ne{ right:-7px; top:-7px; cursor:nesw-resize; }
  .z-sw{ left:-7px; bottom:-7px; cursor:nesw-resize; }
  .z-se{ right:-7px; bottom:-7px; cursor:nwse-resize; }

  /* Botones compactos */
  .btn{ padding:.55rem .9rem; border-radius:.6rem; }
</style>
</head>
<body class="bg-neutral-950 text-neutral-100">

<header class="sticky top-0 z-20 bg-neutral-900/80 backdrop-blur border-b border-white/10">
  <div class="max-w-7xl mx-auto px-4 h-14 flex items-center justify-between">
    <div class="font-black tracking-tight">Zonas de precio — {{ $evento->nombre }}</div>
        <a href="{{ route('dashboard.eventos.mapa', $evento) }}"
        class="btn bg-neutral-800 hover:bg-neutral-700">← Volver al mapa</a>
    </div>
</header>

<div class="max-w-7xl mx-auto p-4 md:p-6 grid grid-cols-1 md:grid-cols-[320px_1fr] gap-6">

  <!-- Lateral -->
  <aside class="card p-4 space-y-4">
    <div class="text-sm font-semibold">Zona seleccionada</div>
    <div class="grid grid-cols-2 gap-3">
      <label class="text-xs">Nombre
        <input id="in-nombre" class="w-full rounded bg-neutral-800 border border-white/10 px-2 py-2" placeholder="Opcional">
      </label>
      <label class="text-xs">Color
        <input id="in-color" type="color" class="w-full rounded bg-neutral-800 border border-white/10 px-1 py-1 h-[42px]">
      </label>
    </div>
    <label class="text-xs block">Recargo por silla ($)
      <input id="in-factor" type="number" min="0" value="0"
             class="w-full rounded bg-neutral-800 border border-white/10 px-2 py-2">
    </label>

    <div class="flex gap-2 pt-2 border-t border-white/10">
      <button id="btn-add" class="btn bg-emerald-600 hover:bg-emerald-500 text-black font-semibold w-full">+ Agregar zona</button>
      <button id="btn-del" class="btn bg-red-600 hover:bg-red-500 w-full" disabled>Eliminar</button>
    </div>

    <div class="space-y-2 pt-2 border-t border-white/10">
      <button id="btn-save" class="btn w-full bg-blue-600 hover:bg-blue-500">Guardar zonas</button>
      <p class="text-xs text-neutral-400">
        El guardado <b>reemplaza</b> todas las zonas del evento (igual que el guardado del mapa de mesas).
      </p>
    </div>
  </aside>

  <!-- Lienzo -->
  <section class="card p-3">
    <div class="flex items-center justify-between mb-2">
      <h2 class="font-semibold">Mapa del evento</h2>
      <div class="text-xs text-neutral-400">
        {{ \Illuminate\Support\Carbon::parse($evento->fecha)->format('d/m/Y') }}
        · {{ substr($evento->hora_inicio,0,5) }}–{{ substr($evento->hora_termino,0,5) }}
      </div>
    </div>

    <div class="map-shell rounded-lg">
      <div id="lienzo">
        <!-- Plano SVG (mismo que el editor de mesas) -->
        <svg id="plano" viewBox="0 0 1000 700" preserveAspectRatio="xMidYMid meet">
          <polygon fill="none" stroke="#5b6475" stroke-width="4"
                   points="400,80 980,80 980,180 800,300 800,650 50,650 50,300 140,240 100,165 240,80 280,155" />
          @if($evento->id_escenario == 1)
            <rect x="600" y="60" width="320" height="80" fill="#b91c1c" />
            <text x="760" y="110" fill="white" font-size="22" text-anchor="middle" font-weight="700">ESCENARIO 1</text>
            <rect x="-90" y="150" width="120" height="400" fill="#5b6475" opacity=".6" />
          @else
            <rect x="-90" y="150" width="120" height="400" fill="#7f1d1d" opacity=".8" />
            <text x="150" y="205" transform="rotate(-90,140,360)" fill="white" font-size="22" text-anchor="middle" font-weight="700">ESCENARIO 2</text>
            <rect x="600" y="60" width="320" height="80" fill="#5b6475" opacity=".9" />
          @endif
          <line x1="540" y1="70" x2="540" y2="90" stroke="#5b6475" stroke-width="4" />
          <line x1="580" y1="70" x2="580" y2="90" stroke="#5b6475" stroke-width="4" />
          <line x1="50" y1="300" x2="400" y2="80"  stroke="#5b6475" stroke-width="4" opacity=".7" />
          <line x1="50" y1="350" x2="400" y2="130" stroke="#5b6475" stroke-width="4" opacity=".7" />
          <line x1="400" y1="130" x2="600" y2="130" stroke="#5b6475" stroke-width="4" opacity=".7" />
          <line x1="320" y1="180" x2="320" y2="500" stroke="#5b6475" stroke-width="4" opacity=".7" />
          <line x1="50" y1="500" x2="680" y2="500" stroke="#5b6475" stroke-width="6" opacity=".6"/>
          <line x1="50" y1="550" x2="680" y2="550" stroke="#5b6475" stroke-width="6" opacity=".6"/>
          <line x1="820" y1="360" x2="780" y2="360" stroke="#5b6475" stroke-width="4" />
          <line x1="820" y1="420" x2="780" y2="420" stroke="#5b6475" stroke-width="4" />
 
          <line x1="790" y1="470" x2="680" y2="470" stroke="#027000ff" stroke-width="150" />
          <line x1="740" y1="525" x2="640" y2="525" stroke="#027000ff" stroke-width="40" />
          <line x1="580" y1="525" x2="480" y2="525" stroke="#027000ff" stroke-width="40" />
          <line x1="420" y1="525" x2="320" y2="525" stroke="#027000ff" stroke-width="40" />

          <circle cx="750" cy="250" r="25" fill="green" opacity="1" />
          <circle cx="600" cy="300" r="20" fill="green" opacity="1" />
          <circle cx="500" cy="300" r="20" fill="green" opacity="1" />
          <circle cx="400" cy="300" r="20" fill="green" opacity="1" />
        </svg>
        <!-- Contenedor de zonas -->
        <div id="zones"></div>
      </div>
    </div>
  </section>
</div>

<script>
  // --- Endpoints
  const token   = document.querySelector('meta[name="csrf-token"]').content;
  const dataUrl = "{{ route('dashboard.eventos.zonas.data',   $evento) }}";
  const saveUrl = "{{ route('dashboard.eventos.zonas.save',   $evento) }}";

  // --- UI refs
  const zonesWrap  = document.getElementById('zones');
  const btnAdd     = document.getElementById('btn-add');
  const btnDel     = document.getElementById('btn-del');
  const btnSave    = document.getElementById('btn-save');
  const inNombre   = document.getElementById('in-nombre');
  const inColor    = document.getElementById('in-color');
  const inFactor   = document.getElementById('in-factor');

  // Estado
  let selected = null;   // div.zbox seleccionado

  const rgba = (hex, a=0.2) => {
    let h = (hex||'#ef4444').replace('#',''); if (h.length===3) h = h.split('').map(c=>c+c).join('');
    const r = parseInt(h.slice(0,2),16), g = parseInt(h.slice(2,4),16), b = parseInt(h.slice(4,6),16);
    return `rgba(${r},${g},${b},${a})`;
  };

  function makeHandle(cls){ const h=document.createElement('div'); h.className='z-handle '+cls; return h; }

  function selectBox(el){
    zonesWrap.querySelectorAll('.zbox').forEach(b=>b.classList.remove('selected'));
    selected = el || null;
    if (selected) {
      selected.classList.add('selected');
      inNombre.value = selected.dataset.nombre || '';
      inColor.value  = selected.dataset.color || '#ef4444';
      inFactor.value = selected.dataset.factor || '0';
      btnDel.disabled = false;
    } else {
      inNombre.value = ''; inColor.value='#ef4444'; inFactor.value='0';
      btnDel.disabled = true;
    }
  }

  function createBox({id=null, nombre='', x=60, y=60, w=220, h=140, factor=0, color='#ef4444'}){
    const el = document.createElement('div');
    el.className = 'zbox';
    el.style.left   = x+'px'; el.style.top = y+'px';
    el.style.width  = w+'px'; el.style.height = h+'px';
    el.style.borderColor = rgba(color, .85);
    el.style.background  = rgba(color, .18);
    el.dataset.id = id || '';
    el.dataset.nombre = nombre || '';
    el.dataset.color = color;
    el.dataset.factor = factor|0;

    const tag = document.createElement('div');
    tag.className='tag';
    tag.textContent = (nombre? nombre+' · ' : '') + `+$${Number(factor).toLocaleString('es-CL')}/silla`;
    el.appendChild(tag);

    // Manillas
    ['nw','ne','sw','se'].forEach(pos => el.appendChild(makeHandle('z-'+pos)));

    // Click select
    el.addEventListener('mousedown', (e)=> {
      if (!e.target.classList.contains('z-handle')) selectBox(el);
    });

    // Drag
    let dragging=false, ox=0, oy=0, startX=0,startY=0;
    el.addEventListener('mousedown',(e)=>{
      if (e.target.classList.contains('z-handle')) return; // resize usa otro handler
      dragging=true;
      startX = el.offsetLeft; startY = el.offsetTop;
      ox = e.clientX; oy = e.clientY;
      e.preventDefault();
    });
    window.addEventListener('mousemove',(e)=>{
      if (!dragging) return;
      const dx = e.clientX-ox, dy = e.clientY-oy;
      const nx = Math.max(0, Math.min(1000-el.offsetWidth,  startX+dx));
      const ny = Math.max(0, Math.min(700 -el.offsetHeight, startY+dy));
      el.style.left = nx+'px'; el.style.top = ny+'px';
    });
    window.addEventListener('mouseup',()=> dragging=false);

    // Resize (4 esquinas)
    const start = {w:0,h:0,x:0,y:0, mx:0,my:0, corner:''}; let resizing=false;
    el.querySelectorAll('.z-handle').forEach(hn=>{
      hn.addEventListener('mousedown',(e)=>{
        resizing=true; start.w=el.offsetWidth; start.h=el.offsetHeight;
        start.x=el.offsetLeft; start.y=el.offsetTop; start.mx=e.clientX; start.my=e.clientY;
        start.corner = [...hn.classList].find(c=>/^z-/.test(c)).slice(2); // nw|ne|sw|se
        e.stopPropagation(); e.preventDefault();
      });
    });
    window.addEventListener('mousemove',(e)=>{
      if (!resizing) return;
      const dx=e.clientX-start.mx, dy=e.clientY-start.my;
      let nx=start.x, ny=start.y, nw=start.w, nh=start.h;

      if (start.corner.includes('e')) nw = Math.min(1000 - start.x, Math.max(10, start.w + dx));
      if (start.corner.includes('s')) nh = Math.min(700  - start.y, Math.max(10, start.h + dy));
      if (start.corner.includes('w')) { nx = Math.max(0, start.x + dx); nw = Math.max(10, start.w - dx); }
      if (start.corner.includes('n')) { ny = Math.max(0, start.y + dy); nh = Math.max(10, start.h - dy); }

      // límites
      if (nx + nw > 1000) nw = 1000 - nx;
      if (ny + nh > 700)  nh = 700  - ny;

      el.style.left = nx+'px'; el.style.top = ny+'px';
      el.style.width= nw+'px'; el.style.height= nh+'px';
    });
    window.addEventListener('mouseup',()=> resizing=false);

    zonesWrap.appendChild(el);
    return el;
  }

  // Inputs laterales -> zona seleccionada
  inNombre.addEventListener('input', ()=>{
    if (!selected) return;
    selected.dataset.nombre = inNombre.value;
    selected.querySelector('.tag').textContent =
      (inNombre.value ? inNombre.value+' · ' : '') + `+$${Number(selected.dataset.factor||0).toLocaleString('es-CL')}/silla`;
  });
  inColor.addEventListener('input', ()=>{
    if (!selected) return;
    selected.dataset.color = inColor.value;
    selected.style.borderColor = rgba(inColor.value,.85);
    selected.style.background  = rgba(inColor.value,.18);
  });
  inFactor.addEventListener('input', ()=>{
    if (!selected) return;
    selected.dataset.factor = String(parseInt(inFactor.value||'0',10));
    selected.querySelector('.tag').textContent =
      (selected.dataset.nombre ? selected.dataset.nombre+' · ' : '') + `+$${Number(selected.dataset.factor||0).toLocaleString('es-CL')}/silla`;
  });

  // Botones
  btnAdd.addEventListener('click', ()=>{
    const el = createBox({ nombre:'', factor:3000, color:'#ef4444' });
    selectBox(el);
  });
  btnDel.addEventListener('click', ()=>{
    if (!selected) return;
    const next = selected.nextElementSibling || selected.previousElementSibling;
    selected.remove();
    selectBox(next && next.classList.contains('zbox') ? next : null);
  });

  btnSave.addEventListener('click', async ()=>{
    const zonas = [...zonesWrap.querySelectorAll('.zbox')].map(el=>({
      nombre: el.dataset.nombre || null,
      x: Math.round(el.offsetLeft),
      y: Math.round(el.offsetTop),
      w: Math.round(el.offsetWidth),
      h: Math.round(el.offsetHeight),
      factor: parseInt(el.dataset.factor||'0',10),
      color: el.dataset.color || '#ef4444',
    }));
    try{
      const r = await fetch(saveUrl, {
        method:'POST',
        headers:{'Content-Type':'application/json', 'X-CSRF-TOKEN':token, 'Accept':'application/json'},
        body: JSON.stringify({ zonas })
      });
      const j = await r.json().catch(()=>({}));
      if (!r.ok || !j.ok) { alert(j.msg || 'No se pudo guardar.'); return; }
      alert('Zonas guardadas.');
    }catch(e){ console.error(e); alert('Error de red al guardar.'); }
  });

  // Cargar existentes
  (async function load(){
    try{
      const r = await fetch(dataUrl); const list = await r.json();
      list.forEach(z => createBox(z));
    }catch(e){ /* sin zonas */ }
  })();
</script>
</body>
</html>
