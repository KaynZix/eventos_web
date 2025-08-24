<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mapa de Mesas • Cacho e’ Cabra</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .btn-hour { @apply font-extrabold text-lg rounded px-4 py-3 text-center; }
    .btn-red  { @apply bg-red-600 hover:bg-red-700 text-white uppercase font-extrabold tracking-widest rounded px-5 py-4; }
    .panel    { @apply bg-neutral-800/90 ring-1 ring-white/10 rounded-xl; }
  </style>
</head>
<body class="bg-neutral-900 text-white">

  <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="grid grid-cols-12 gap-6">

      <!-- MAPA -->
      <div class="col-span-12 lg:col-span-8 panel p-4">
        <h2 class="text-xl font-extrabold uppercase tracking-widest mb-3">Mapa de mesas</h2>

        <!-- SVG plano (ejemplo) -->
        <div class="relative">
          <svg id="mapa" viewBox="0 0 1000 600" class="w-full h-[520px] bg-neutral-900 rounded-lg">
            <!-- Marco -->
            <rect x="20" y="20" width="960" height="560" fill="none" stroke="#3f3f46" stroke-width="2"/>

            <!-- Escenario 2 -->
            <rect x="20" y="150" width="90" height="300" fill="#8B0E0E" rx="6"/>
            <text x="65" y="300" fill="#ffffff" font-size="22" font-weight="800" text-anchor="middle" transform="rotate(-90,65,300)">
              ESCENARIO 2
            </text>

            <!-- Escenario 1 -->
            <rect x="430" y="80" width="240" height="60" fill="#E11D21" rx="6"/>
            <text x="550" y="118" fill="#ffffff" font-size="22" font-weight="800" text-anchor="middle">
              ESCENARIO 1
            </text>

            <!-- Entrada -->
            <text x="820" y="325" fill="#ffffff" font-size="16" font-weight="700" text-anchor="middle"
                  transform="rotate(-90,820,325)">ENTRADA</text>

            <!-- Áreas de verde decorativo (opcional) -->
            <g opacity=".35">
              <circle cx="420" cy="270" r="16" fill="#22c55e"/>
              <circle cx="470" cy="310" r="16" fill="#22c55e"/>
              <circle cx="520" cy="270" r="16" fill="#22c55e"/>
            </g>
            <!-- Las MESAS se inyectan por JS -->
          </svg>
        </div>

        <!-- Leyenda -->
        <div class="mt-4 flex flex-wrap gap-4 text-sm">
          <div class="flex items-center gap-2"><span class="inline-block w-4 h-4 rounded bg-emerald-500"></span>Disponible</div>
          <div class="flex items-center gap-2"><span class="inline-block w-4 h-4 rounded bg-rose-500"></span>Ocupada</div>
          <div class="flex items-center gap-2"><span class="inline-block w-4 h-4 rounded bg-yellow-400"></span>Tu selección</div>
        </div>
      </div>

      <!-- LATERAL DERECHA -->
      <aside class="col-span-12 lg:col-span-4 panel p-5">
        <h3 class="text-center text-lg font-extrabold uppercase tracking-widest">Indicar hora de llegada</h3>

        <!-- HORARIOS -->
        <div id="slots" class="grid grid-cols-2 gap-3 mt-5"></div>

        <!-- ACCIÓN -->
        <div class="mt-6 space-y-3">
          <!-- AHORA ES UN <a> QUE REDIRIGE A /promocion.php -->
          <a id="btnReservaPedido" href="#" class="btn-red w-full block text-center">
            Reservar y agregar pedido anticipado
          </a>
          <button id="btnSoloReserva" class="btn-red w-full">
            Solo reservar
          </button>
        </div>

        <!-- RESUMEN -->
        <div class="mt-6 text-sm">
          <div><span class="opacity-70">Hora:</span> <b id="resHora">—</b></div>
          <div class="mt-1"><span class="opacity-70">Mesas:</span> <b id="resMesas">Ninguna</b></div>
        </div>

        <!-- FORM oculto para "Solo reservar" -->
        <form id="formReserva" action="/reservar" method="GET" class="hidden">
          <input type="hidden" name="mesas" id="inputMesas">
          <input type="hidden" name="hora" id="inputHora">
          <input type="hidden" name="pedidoAnticipado" id="inputPedido">
        </form>
      </aside>
    </div>
  </section>

  <script>
    /* ====== CONFIGURACIÓN RÁPIDA ====== */
    const horarios = [
      { label: "20:00", disponible: true  },
      { label: "20:30", disponible: true  },
      { label: "21:00", disponible: true  },
      { label: "21:30", disponible: true  },
      { label: "22:00", disponible: true  },
      { label: "22:30", disponible: false },
      { label: "23:00", disponible: false },
    ];

    const mesas = [
      { id:"A1", type:"rect",  x:360, y:170, w:44, h:24,  status:"free" },
      { id:"A2", type:"rect",  x:410, y:170, w:44, h:24,  status:"busy" },
      { id:"A3", type:"rect",  x:460, y:170, w:44, h:24,  status:"free" },
      { id:"A4", type:"rect",  x:510, y:170, w:44, h:24,  status:"free" },
      { id:"A5", type:"rect",  x:560, y:170, w:44, h:24,  status:"busy" },
      { id:"A6", type:"rect",  x:610, y:170, w:44, h:24,  status:"free" },

      { id:"B1", type:"circle", x:420, y:260, r:16, status:"free" },
      { id:"B2", type:"circle", x:470, y:300, r:16, status:"busy" },
      { id:"B3", type:"circle", x:520, y:260, r:16, status:"free" },
      { id:"B4", type:"circle", x:570, y:300, r:16, status:"free" },

      { id:"C1", type:"rect",  x:320, y:420, w:60, h:28,  status:"free" },
      { id:"C2", type:"rect",  x:400, y:420, w:60, h:28,  status:"free" },
      { id:"C3", type:"rect",  x:480, y:420, w:60, h:28,  status:"busy" },
      { id:"C4", type:"rect",  x:560, y:420, w:60, h:28,  status:"free" },
      { id:"C5", type:"rect",  x:640, y:420, w:60, h:28,  status:"free" },
    ];

    /* ====== LÓGICA ====== */
    const svg = document.getElementById('mapa');
    const selected = new Set();
    let selectedHour = null;

    // Pintar mesas
    mesas.forEach(m => {
      const ns = "http://www.w3.org/2000/svg";
      let el;
      if (m.type === "circle") {
        el = document.createElementNS(ns, "circle");
        el.setAttribute("cx", m.x);
        el.setAttribute("cy", m.y);
        el.setAttribute("r",  m.r);
      } else {
        el = document.createElementNS(ns, "rect");
        el.setAttribute("x", m.x);
        el.setAttribute("y", m.y);
        el.setAttribute("width",  m.w);
        el.setAttribute("height", m.h);
        el.setAttribute("rx", 6);
      }

      const fillFree = "#d6d3d1";
      const fillBusy = "#a1a1aa";
      el.setAttribute("fill", m.status === "free" ? fillFree : fillBusy);
      el.setAttribute("stroke", "transparent");
      el.style.cursor = m.status === "free" ? "pointer" : "not-allowed";

      el.setAttribute("role", "button");
      el.setAttribute("aria-label", `Mesa ${m.id} ${m.status === "free" ? "disponible" : "ocupada"}`);

      el.addEventListener("click", () => {
        if (m.status !== "free") return;
        if (selected.has(m.id)) {
          selected.delete(m.id);
          el.setAttribute("stroke", "transparent");
          el.setAttribute("stroke-width", 0);
          el.setAttribute("fill", fillFree);
        } else {
          selected.add(m.id);
          el.setAttribute("stroke", "#facc15");
          el.setAttribute("stroke-width", 4);
          el.setAttribute("fill", "#fbbf24");
        }
        renderSummary();
      });

      svg.appendChild(el);

      const label = document.createElementNS(ns, "text");
      label.setAttribute("x", m.type === "circle" ? m.x : m.x + (m.w/2));
      label.setAttribute("y", m.type === "circle" ? (m.y + 5) : (m.y + (m.h/2) + 5));
      label.setAttribute("fill", "#111827");
      label.setAttribute("font-size", "12");
      label.setAttribute("font-weight", "700");
      label.setAttribute("text-anchor", "middle");
      label.textContent = m.id;
      svg.appendChild(label);
    });

    // Pintar horarios
    const slots = document.getElementById("slots");
    horarios.forEach(h => {
      const btn = document.createElement("button");
      btn.type = "button";
      btn.textContent = h.label;
      btn.dataset.value = h.label;
      btn.className = "btn-hour " + (h.disponible ? "bg-green-500 hover:bg-green-600" : "bg-red-600 opacity-90 cursor-not-allowed");
      if (!h.disponible) btn.disabled = true;

      btn.addEventListener("click", () => {
        selectedHour = h.label;
        [...slots.children].forEach(c => c.classList.remove("ring-4","ring-yellow-400"));
        btn.classList.add("ring-4","ring-yellow-400");
        renderSummary();
      });

      slots.appendChild(btn);
    });

    // Resumen + inputs del form
    function renderSummary() {
      document.getElementById("resHora").textContent  = selectedHour ?? "—";
      document.getElementById("resMesas").textContent = selected.size ? [...selected].join(", ") : "Ninguna";
      document.getElementById("inputHora").value   = selectedHour ?? "";
      document.getElementById("inputMesas").value  = [...selected].join(",");
    }

    // === Redirección a /promocion.php con parámetros ===
    document.getElementById("btnReservaPedido").addEventListener("click", (e) => {
      e.preventDefault();
      if (!selectedHour || selected.size === 0) {
        alert("Selecciona al menos una mesa y una hora de llegada.");
        return;
      }
      const mesasParam = encodeURIComponent([...selected].join(","));
      const horaParam  = encodeURIComponent(selectedHour);
      window.location.href = `/promocion.php?mesas=${mesasParam}&hora=${horaParam}`;
    });

    // Envío de formulario para "Solo reservar"
    function submitReserva(pedidoAnticipado) {
      if (!selectedHour || selected.size === 0) {
        alert("Selecciona al menos una mesa y una hora de llegada.");
        return;
      }
      document.getElementById("inputPedido").value = pedidoAnticipado ? "1" : "0";
      document.getElementById("formReserva").submit();
    }
    document.getElementById("btnSoloReserva").addEventListener("click",   () => submitReserva(false));
  </script>
</body>
</html>
