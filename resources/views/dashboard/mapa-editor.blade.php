<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Editor de mapa · {{ $evento->nombre }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
    .card {
        background: rgba(17, 24, 39, .6);
        border: 1px solid rgba(255, 255, 255, .08);
        border-radius: 12px;
    }

    .map-shell {
        overflow: auto;
    }

    /* Lienzo fijo (coordenadas 1000x700) */
    #lienzo {
        width: 1000px;
        height: 700px;
        position: relative;
        border-radius: 12px;
        overflow: hidden;
        background:
            linear-gradient(#ffffff10 1px, transparent 1px),
            linear-gradient(90deg, #ffffff10 1px, transparent 1px);
        background-size: 24px 24px;
        background-color: #0b0f19;
        /* //CODIGO_MESAS: color de fondo del mapa */
    }

    #mesas-layer {
        position: absolute;
        inset: 0;
    }

    /* ===================== VARIABLES AJUSTABLES ===================== */
    :root {

        /* //ZONAS_OVERLAY */
        --zona-borde: #ef4444;
        --zona-opacidad: .20;

        /* //CODIGO_MESAS: colores de mesas y sillas */
        --mesa-stroke: #e5e7eb;
        --mesa-fill: #0ea5e9;
        /* tablero 1–4 */
        --chair-fill: #1f2937;
        /* sillas 1–4 */

        /* //CODIGO_MESAS: colores de etiqueta 5–8 */
        --mesa-label-bg: #ffffff;
        --mesa-label-fg: #111827;
        --mesa-label-bd: #111827;

        /* //CODIGO_MESAS: tamaños por rango */
        --t12-w: 55px;
        --t12-h: 30px;
        /* 1–2 horizontal */
        --t34-w: 45px;
        --t34-h: 45px;
        /* 3–4 cuadrada  */
        --t58-w: 90px;
        --t58-h: 45px;
        /* 5–8 horizontal */
    }

    /* //ZONAS_OVERLAY */
    #zonas-overlay {
        position: absolute;
        inset: 0;
        pointer-events: none;
    }

    /* no bloquea arrastre de mesas */
    .zona-box {
        position: absolute;
        border-radius: 8px;
        border: 2px dashed var(--zona-borde);
        background: rgba(239, 68, 68, var(--zona-opacidad));
    }

    .zona-box .tag {
        position: absolute;
        left: 0;
        top: -22px;
        font-size: 11px;
        line-height: 1;
        padding: 2px 6px;
        border-radius: 6px;
        white-space: nowrap;
        background: rgba(0, 0, 0, .55);
        border: 1px solid rgba(255, 255, 255, .15);
    }

    /* ===================== MESAS ===================== */
    .mesa {
        position: absolute;
        user-select: none;
        cursor: move;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 11px;
        line-height: 1;
        text-align: center;
        transform-origin: center center;
    }

    .mesa .icon {
        width: 100%;
        height: 100%;
        display: block;
    }

    /* Botones por mesa (girar / borrar) */
    .mesa .actions {
        position: absolute;
        right: 2px;
        top: 2px;
        display: flex;
        gap: 4px;
        z-index: 2;
        opacity: 0;
        transition: opacity .15s ease;
    }

    .mesa:hover .actions {
        opacity: 1;
    }

    .mesa .actions button {
        background: rgba(0, 0, 0, .55);
        border: 1px solid rgba(255, 255, 255, .2);
        color: #fff;
        font-size: 10px;
        padding: 2px 6px;
        border-radius: 6px;
    }

    /* Tamaños por tipo (toman valores de :root) */
    .mesa.t12 {
        width: var(--t12-w);
        height: var(--t12-h);
    }

    .mesa.t34 {
        width: var(--t34-w);
        height: var(--t34-h);
    }

    .mesa.t58 {
        width: var(--t58-w);
        height: var(--t58-h);
    }

    /* Etiqueta para mesas 5–8 */
    .label-58 {
        width: 100%;
        height: 100%;
        background: var(--mesa-label-bg);
        color: var(--mesa-label-fg);
        display: flex;
        align-items: center;
        justify-content: center;
        border: 3px solid var(--mesa-label-bd);
        font-weight: 700;
        font-size: 12px;
    }
    </style>

</head>

<body class="bg-neutral-950 text-neutral-100">

    <header class="sticky top-0 z-20 bg-neutral-900/80 backdrop-blur border-b border-white/10">
        <div class="max-w-7xl mx-auto px-4 h-14 flex items-center justify-between">
            <div class="font-black tracking-tight">Editor de mapa</div>
            <div class="text-sm text-neutral-300">
                <a href="{{ route('dashboard.eventos.index') }}"
                class="btn bg-neutral-800 hover:bg-neutral-700">← Volver al mapa</a>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto p-4 md:p-6 grid grid-cols-1 md:grid-cols-[320px_1fr] gap-6">

        {{-- Lateral --}}
        <aside class="card p-4 space-y-4">
            <div>
                <div class="text-xs text-neutral-400">Evento</div>
                <div class="font-bold">{{ $evento->nombre }}</div>
                <div class="text-xs text-neutral-400 mt-1">Escenario por defecto: {{ $evento->id_escenario }}</div>
            </div>

            {{-- Agregar mesas --}}
            <div class="space-y-2">
                <div class="text-sm font-semibold">Agregar mesas</div>
                <div class="grid grid-cols-2 gap-2">
                    <label class="text-xs text-neutral-300">
                        Sillas
                        <input id="inp-sillas" type="number" min="1" max="8" value="4"
                            class="w-full rounded bg-neutral-800 border border-white/10 px-2 py-1">
                    </label>
                    <label class="text-xs text-neutral-300">
                        Cantidad
                        <input id="inp-cant" type="number" min="1" max="50" value="1"
                            class="w-full rounded bg-neutral-800 border border-white/10 px-2 py-1">
                    </label>
                </div>
                <button id="btn-add" type="button" class="w-full rounded bg-neutral-800 hover:bg-neutral-700 px-3 py-2">
                    + Agregar mesas
                </button>
                <p class="text-xs text-neutral-400">
                    1–2 y 5–8: <b>horizontal</b>. 3–4: <b>cuadrada</b>.<br>
                    Puedes <b>girar</b> y <b>borrar</b> cada mesa.
                </p>
            </div>

            {{-- Zonas de precio (módulo aparte) --}}
            <div class="space-y-2 pt-2 border-t border-white/10">
                <a href="{{ route('dashboard.eventos.zonas.editor', $evento) }}"
                    class="w-full inline-flex items-center justify-center rounded bg-amber-600 hover:bg-amber-500 px-3 py-2 font-bold">
                    Zonas de precio
                </a>


                <p class="text-xs text-neutral-400">
                    Las zonas se crean/editar en una vista aparte. Aquí solo editas el mapa de mesas.
                </p>
            </div>

            <label class="flex items-center gap-2 text-sm mt-2">
                <input id="toggleZonas" type="checkbox" class="accent-amber-500" checked>
                Mostrar zonas de precio
            </label>

            {{-- Acciones --}}
            <div class="space-y-2 pt-2 border-t border-white/10">
                <div class="text-sm font-semibold">Acciones</div>
                <button id="guardar" type="button" class="w-full rounded bg-blue-600 hover:bg-blue-500 px-3 py-2">
                    Guardar cambios
                </button>
                <button id="limpiar" type="button" class="w-full rounded bg-neutral-800 hover:bg-neutral-700 px-3 py-2">
                    Limpiar
                </button>
            </div>
        </aside>

        {{-- Lienzo --}}
        <section class="card p-3">
            <div class="flex items-center justify-between mb-2">
                <h2 class="font-semibold">Mapa del evento</h2>
                <div class="text-xs text-neutral-400">Escenario {{ $evento->id_escenario }}</div>
            </div>

            <div class="map-shell rounded-lg">
                <div id="lienzo">
                    {{-- PLANO SVG (1000x700) --}}
                    <svg class="absolute inset-0 w-full h-full pointer-events-none" viewBox="0 0 1000 700"
                        preserveAspectRatio="xMidYMid meet">
                        <polygon fill="none" stroke="#5b6475" stroke-width="4"
                            points="400,80 980,80 980,180 800,300 800,650 50,650 50,300 140,240 100,165 240,80 280,155" />

                        @if($evento->id_escenario == 1)
                        <rect x="600" y="60" width="320" height="80" fill="#b91c1c" />
                        <text x="760" y="110" fill="white" font-size="22" text-anchor="middle"
                            font-weight="700">ESCENARIO 1</text>
                        <rect x="-90" y="150" width="120" height="400" fill="#5b6475" opacity=".6" />
                        @else
                        <rect x="-90" y="150" width="120" height="400" fill="#7f1d1d" opacity=".8" />
                        <text x="150" y="205" transform="rotate(-90,140,360)" fill="white" font-size="22"
                            text-anchor="middle" font-weight="700">ESCENARIO 2</text>
                        <rect x="600" y="60" width="320" height="80" fill="#5b6475" opacity=".9" />
                        @endif

                        <line x1="790" y1="470" x2="680" y2="470" stroke="#027000ff" stroke-width="150" />
                        <line x1="740" y1="525" x2="640" y2="525" stroke="#027000ff" stroke-width="40" />
                        <line x1="580" y1="525" x2="480" y2="525" stroke="#027000ff" stroke-width="40" />
                        <line x1="420" y1="525" x2="320" y2="525" stroke="#027000ff" stroke-width="40" />

                        <circle cx="750" cy="250" r="25" fill="green" opacity="1" />
                        <circle cx="600" cy="300" r="20" fill="green" opacity="1" />       
                        <circle cx="500" cy="300" r="20" fill="green" opacity="1" />       
                        <circle cx="400" cy="300" r="20" fill="green" opacity="1" />

                        <line x1="540" y1="70" x2="540" y2="90" stroke="#5b6475" stroke-width="4" />
                        <line x1="580" y1="70" x2="580" y2="90" stroke="#5b6475" stroke-width="4" />
                        <line x1="50" y1="300" x2="400" y2="80" stroke="#5b6475" stroke-width="4" opacity=".7" />
                        <line x1="50" y1="350" x2="400" y2="130" stroke="#5b6475" stroke-width="4" opacity=".7" />
                        <line x1="400" y1="130" x2="600" y2="130" stroke="#5b6475" stroke-width="4" opacity=".7" />
                        <line x1="320" y1="180" x2="320" y2="500" stroke="#5b6475" stroke-width="4" opacity=".7" />
                        <line x1="50" y1="500" x2="680" y2="500" stroke="#5b6475" stroke-width="6" opacity=".6" />
                        <line x1="50" y1="550" x2="680" y2="550" stroke="#5b6475" stroke-width="6" opacity=".6" />
                        <line x1="820" y1="360" x2="780" y2="360" stroke="#5b6475" stroke-width="4" />
                        <line x1="820" y1="420" x2="780" y2="420" stroke="#5b6475" stroke-width="4" />   
                    </svg>
                    <div id="zonas-overlay"></div> <!-- overlay visual -->
                    <div id="mesas-layer"></div> <!-- mesas arrastrables -->

                    {{-- Capa de mesas --}}
                    <div id="mesas-layer"></div>
                </div>
            </div>
        </section>
    </div>

    <script>
    /* ====== FIX: si hay dos #mesas-layer, deja uno solo ====== */
    (function fixDuplicateLayer() {
        const layers = document.querySelectorAll('#mesas-layer');
        if (layers.length > 1) {
            for (let i = 1; i < layers.length; i++) layers[i].remove();
        }
    })();

    /* ====== refs básicas ====== */
    const token = document.querySelector('meta[name="csrf-token"]').content;
    const lienzo = document.getElementById('lienzo');
    let layer = document.getElementById('mesas-layer'); // capa única ya saneada
    const overlayEl = document.getElementById('zonas-overlay');
    if (overlayEl) overlayEl.style.pointerEvents = 'none'; // el overlay no bloquea el drag

    // Datos inyectados desde Blade
    const existentes = @json($mesas ?? []); // [{sillas,x,y,rot}, ...]
    const zonas = @json($zonas ?? []); // [{x,y,w,h,color,factor,nombre}, ...]
    const saveUrl = "{{ route('dashboard.eventos.mapa.mesas.save', $evento->id) }}";

    /* ====== polígono del salón (viewBox 1000x700) ====== */
    const polySala = [
        [400, 80],
        [980, 80],
        [980, 180],
        [800, 300],
        [800, 650],
        [50, 650],
        [50, 300],
        [140, 240],
        [100, 165],
        [240, 80],
        [280, 155]
    ];

    /* ====== utilidades coords ====== */
    function toViewBox(xPx, yPx) {
        const r = lienzo.getBoundingClientRect();
        return [xPx / r.width * 1000, yPx / r.height * 700];
    }

    function fromViewBox(x, y) {
        const r = lienzo.getBoundingClientRect();
        return [x / 1000 * r.width, y / 700 * r.height];
    }

    function pointInPoly([x, y], poly) {
        let inside = false;
        for (let i = 0, j = poly.length - 1; i < poly.length; j = i++) {
            const [xi, yi] = poly[i], [xj, yj] = poly[j];
            const inter = ((yi > y) != (yj > y)) && (x < (xj - xi) * (y - yi) / (yj - yi) + xi);
            if (inter) inside = !inside;
        }
        return inside;
    }

    function randomPointInside() {
        for (let t = 0; t < 250; t++) {
            const vx = 120 + Math.random() * 760,
                vy = 120 + Math.random() * 460;
            if (pointInPoly([vx, vy], polySala)) return fromViewBox(vx, vy);
        }
        return [20, 20];
    }

    /* ===========================================================
       DIBUJO DE MESAS (usa variables CSS definidas en el <style>)
       =========================================================== */
    function svg12(n) { // 1–2 sillas → horizontal
        return `
  <svg class="icon" viewBox="0 0 120 70">
    <rect x="30" y="10" rx="12" ry="12" width="60" height="50"
          fill="var(--mesa-fill)" stroke="var(--mesa-stroke)" stroke-width="3"/>
    ${n>=1 ? `<rect x="5"  y="27" width="18" height="16" fill="var(--chair-fill)" stroke="var(--mesa-stroke)" stroke-width="3"/>` : ``}
    ${n>=2 ? `<rect x="97" y="27" width="18" height="16" fill="var(--chair-fill)" stroke="var(--mesa-stroke)" stroke-width="3"/>` : ``}
  </svg>`;
    }

    function svg34(n) { // 3–4 sillas → cuadrada
        return `
  <svg class="icon" viewBox="0 0 90 90">
    <rect x="20" y="20" rx="10" ry="10" width="50" height="50"
          fill="var(--mesa-fill)" stroke="var(--mesa-stroke)" stroke-width="3"/>
    ${n>=1 ? `<rect x="38" y="4"  width="14" height="12" fill="var(--chair-fill)" stroke="var(--mesa-stroke)" stroke-width="3"/>` : ``}
    ${n>=2 ? `<rect x="38" y="74" width="14" height="12" fill="var(--chair-fill)" stroke="var(--mesa-stroke)" stroke-width="3"/>` : ``}
    ${n>=3 ? `<rect x="4"  y="38" width="12" height="14" fill="var(--chair-fill)" stroke="var(--mesa-stroke)" stroke-width="3"/>` : ``}
    ${n>=4 ? `<rect x="74" y="38" width="12" height="14" fill="var(--chair-fill)" stroke="var(--mesa-stroke)" stroke-width="3"/>` : ``}
  </svg>`;
    }

    function label58(n) {
        return `<div class="label-58">Mesa de ${n}</div>`;
    }

    function sizeClassFor(n) {
        if (n <= 2) return 't12';
        if (n <= 4) return 't34';
        return 't58';
    }

    function buildMesaContent(sillas) {
        if (sillas <= 2) return svg12(sillas);
        if (sillas <= 4) return svg34(sillas);
        return label58(sillas);
    }

    /* ===========================================================
       CREAR / GIRAR / ELIMINAR / ARRASTRAR
       =========================================================== */

    function addMesaNode({
        idMesa = null,   // ← NUEVO
        sillas,
        x,
        y,
        rot = 0
        }) {
        sillas = Math.max(1, Math.min(8, parseInt(sillas, 10) || 1));

        const el = document.createElement('div');
        el.className = `mesa ${sizeClassFor(sillas)}`;
        el.dataset.sillas = String(sillas);
        el.dataset.rot = String(rot | 0);
        el.dataset.idMesa = idMesa != null ? String(idMesa) : "";  // ← NUEVO
        el.innerHTML = `
            <div class="actions">
            <button type="button" data-rot title="Girar 90°">⟳</button>
            <button type="button" data-del title="Eliminar">✕</button>
            </div>
            ${buildMesaContent(sillas)}
        `;

        if (x == null || y == null) [x, y] = randomPointInside();
        el.style.left = Math.round(x) + 'px';
        el.style.top  = Math.round(y) + 'px';
        el.style.transform = `rotate(${(rot|0)}deg)`;

        // rotar
        el.querySelector('[data-rot]').addEventListener('click', ev => {
            ev.stopPropagation();
            const cur = (parseInt(el.dataset.rot || '0', 10) + 90) % 360;
            el.dataset.rot = String(cur);
            el.style.transform = `rotate(${cur}deg)`;
            markDirty();
        });

        // eliminar
        el.querySelector('[data-del]').addEventListener('click', ev => {
            ev.stopPropagation();
            el.remove();
            markDirty();
        });

        makeDraggable(el);
        layer.appendChild(el);
        markDirty();
        return el;
        }


    function makeDraggable(el) {
        let dragging = false,
            offX = 0,
            offY = 0,
            prevX = 0,
            prevY = 0;

        el.addEventListener('mousedown', e => {
            if (e.target.closest('.actions')) return; // no arrastrar desde los botones
            dragging = true;
            const r = lienzo.getBoundingClientRect();
            offX = e.clientX - r.left - el.offsetLeft;
            offY = e.clientY - r.top - el.offsetTop;
            prevX = el.offsetLeft;
            prevY = el.offsetTop;
            el.style.zIndex = 10;
        });

        window.addEventListener('mousemove', e => {
            if (!dragging) return;
            const r = lienzo.getBoundingClientRect();
            const W = el.offsetWidth,
                H = el.offsetHeight;
            const x = Math.min(r.width - W, Math.max(0, e.clientX - r.left - offX));
            const y = Math.min(r.height - H, Math.max(0, e.clientY - r.top - offY));
            el.style.left = x + 'px';
            el.style.top = y + 'px';
        });

        window.addEventListener('mouseup', () => {
            if (!dragging) return;
            dragging = false;
            el.style.zIndex = 1;

            // validación: centro debe quedar dentro del polígono
            const cx = el.offsetLeft + el.offsetWidth / 2;
            const cy = el.offsetTop + el.offsetHeight / 2;
            const [vx, vy] = toViewBox(cx, cy);
            if (!pointInPoly([vx, vy], polySala)) {
                el.style.left = prevX + 'px';
                el.style.top = prevY + 'px';
                return;
            }
            if (el.offsetLeft !== prevX || el.offsetTop !== prevY) markDirty();
        });
    }

    /* ===========================================================
       OVERLAY de ZONAS (solo visual)
       =========================================================== */
    function rgba(hex, a) {
        let h = (hex || '#ef4444').replace('#', '');
        if (h.length === 3) h = h.split('').map(c => c + c).join('');
        const r = parseInt(h.slice(0, 2), 16),
            g = parseInt(h.slice(2, 4), 16),
            b = parseInt(h.slice(4, 6), 16);
        return `rgba(${r},${g},${b},${a})`;
    }

    function renderZonasOverlay(visible = true) {
        if (!overlayEl) return;
        overlayEl.innerHTML = '';
        overlayEl.style.display = visible ? '' : 'none';
        if (!visible) return;

        zonas.forEach(z => {
            const box = document.createElement('div');
            box.className = 'zona-box';
            box.style.left = (z.x | 0) + 'px';
            box.style.top = (z.y | 0) + 'px';
            box.style.width = (z.w | 0) + 'px';
            box.style.height = (z.h | 0) + 'px';
            const color = z.color || '#ef4444';
            box.style.border = `2px dashed ${color}`;
            box.style.background = rgba(color, .20);

            const tag = document.createElement('div');
            tag.className = 'tag';
            tag.textContent = `${z.nombre ?? 'Zona'} · +$${Number(z.factor).toLocaleString('es-CL')}/silla`;
            box.appendChild(tag);

            overlayEl.appendChild(box);
        });
    }
    const toggleZonas = document.getElementById('toggleZonas');
    if (toggleZonas) toggleZonas.addEventListener('change', () => renderZonasOverlay(toggleZonas.checked));
    renderZonasOverlay(true);

    /* ===========================================================
       CARGA / LOTE / GUARDAR / LIMPIAR
       =========================================================== */
    let isDirty = false;
    const markDirty = () => {
        isDirty = true;
    };
    const clearDirty = () => {
        isDirty = false;
    };
    window.addEventListener('beforeunload', e => {
        if (!isDirty) return;
        e.preventDefault();
        e.returnValue = '';
    });

    existentes.forEach(m => addMesaNode({
        idMesa: m.id_mesa,       // <— NUEVO
        sillas: +m.sillas,
        x: m.x, 
        y: m.y, 
        rot: +(m.rot || 0)
    }));

    document.getElementById('btn-add').addEventListener('click', () => {
        const sillas = Math.max(1, Math.min(8, parseInt(document.getElementById('inp-sillas').value || '4',
            10)));
        const cantidad = Math.max(1, Math.min(50, parseInt(document.getElementById('inp-cant').value || '1',
            10)));
        for (let i = 0; i < cantidad; i++) addMesaNode({
            sillas
        });
    });

    document.getElementById('guardar').addEventListener('click', async () => {
        const mesas = Array.from(layer.querySelectorAll('.mesa')).map(el => ({
            id_mesa: el.dataset.idMesa ? parseInt(el.dataset.idMesa, 10) : null, // <— NUEVO
            sillas: parseInt(el.dataset.sillas, 10),
            x: Math.round(el.offsetLeft),
            y: Math.round(el.offsetTop),
            rot: parseInt(el.dataset.rot || '0', 10),
        }));
        try {
            const res = await fetch(saveUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    mesas
                })
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok || !data.ok) {
                alert(data.msg || 'No se pudo guardar.');
                return;
            }
            clearDirty();
            alert('Guardado');
        } catch (e) {
            console.error(e);
            alert('No se pudo guardar.');
        }
    });

    document.getElementById('limpiar').addEventListener('click', () => {
        layer.innerHTML = '';
        markDirty();
    });
    </script>


</body>

</html>