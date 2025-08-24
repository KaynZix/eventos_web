<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cartelera de Eventos — Cacho e’ Cabra</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .vertical { writing-mode: vertical-rl; text-orientation: mixed; letter-spacing: .15em; }
    .card { background: rgba(0,0,0,.55); backdrop-filter: blur(2px); }
    .stroke { text-shadow: 0 2px 6px rgba(0,0,0,.6); }
  </style>
</head>
<body class="bg-neutral-900 text-white">
   <body class="bg-neutral-900 text-white">
    <!-- Header -->
    @include('partials.header')
  <!-- CARTELERA -->
  <section class="relative min-h-screen">
    <img
      src="https://images.unsplash.com/photo-1555396273-367ea4eb4db5?q=80&w=1400&auto=format&fit=crop"
      alt="Patio del restobar con iluminación"
      class="absolute inset-0 w-full h-full object-cover"
    >
    <div class="absolute inset-0 bg-black/55"></div>
    <div class="absolute top-0 left-0 right-0 h-[12px] bg-red-600"></div>

    <!-- GRID DE EVENTOS -->
    <div class="relative z-10 grid grid-cols-12 gap-4 px-4 sm:px-6 lg:px-10 py-8 md:py-12">

      <!-- DESTACADO IZQUIERDA (Switch) -->
      <div class="col-span-12 md:col-span-4 lg:col-span-4 flex items-end">
        <div class="w-full">
          <div class="flex items-center gap-4">
            <img src="https://i.imgur.com/1zv9j5y.png"
                 alt="Switch"
                 class="w-28 h-28 sm:w-36 sm:h-36 rounded-full object-cover ring-4 ring-white/10 shadow-xl">
            <div class="hidden sm:block text-5xl font-extrabold stroke">Switch</div>
          </div>

          <div class="mt-5 max-w-[420px] card rounded-xl px-5 py-4 shadow-lg">
            <div class="flex items-end gap-3">
              <div class="text-5xl sm:text-6xl font-black leading-none">23</div>
              <div class="mb-1">
                <div class="uppercase font-extrabold tracking-widest">Sábado</div>
                <div class="text-sm font-semibold opacity-90">22:00 hrs</div>
              </div>
            </div>
            <div class="mt-3">
              <div class="text-lg font-extrabold uppercase">Switch</div>
              <div class="text-sm opacity-90 uppercase">Rock latino</div>
            </div>

            <!-- Botón Más detalles -->
            <div class="mt-4">
              <a href="/mesas.php"
                 class="inline-block bg-red-600 hover:bg-red-700 focus:ring-4 focus:ring-red-500/30
                        text-white font-extrabold tracking-widest uppercase px-4 py-2 rounded">
                Más detalles
              </a>
            </div>
          </div>
        </div>
      </div>

      <!-- COLUMNA DERECHA (panel con tarjetas) -->
      <div class="col-span-12 md:col-span-8 grid grid-cols-12 gap-4">

        <!-- Mes AGOSTO (vertical) -->
        <div class="hidden md:flex col-span-1 justify-center">
          <div class="vertical uppercase text-sm font-extrabold tracking-widest">Agosto</div>
        </div>

        <!-- 29 Agosto - Fito Páez -->
        <article class="col-span-12 md:col-span-5 lg:col-span-5 card rounded-xl p-4 sm:p-5 shadow-lg">
          <div class="flex items-end gap-3">
            <div class="text-5xl sm:text-6xl font-black leading-none">29</div>
            <div class="mb-1">
              <div class="uppercase font-extrabold tracking-widest">Viernes</div>
              <div class="text-sm font-semibold opacity-90">22:00 hrs</div>
            </div>
          </div>
          <div class="mt-2 uppercase font-extrabold">Tributo</div>
          <div class="text-sm uppercase opacity-90">Fito Páez</div>

          <!-- Botón Más detalles -->
          <div class="mt-4">
            <a href="/mesas.php"
               class="inline-block bg-red-600 hover:bg-red-700 focus:ring-4 focus:ring-red-500/30
                      text-white font-extrabold tracking-widest uppercase px-4 py-2 rounded">
              Más detalles
            </a>
          </div>
        </article>

        <!-- 30 Agosto - Bon Jovi -->
        <article class="col-span-12 md:col-span-6 lg:col-span-6 card rounded-xl p-4 sm:p-5 shadow-lg">
          <div class="flex items-end gap-3">
            <div class="text-5xl sm:text-6xl font-black leading-none">30</div>
            <div class="mb-1">
              <div class="uppercase font-extrabold tracking-widest">Sábado</div>
              <div class="text-sm font-semibold opacity-90">22:00 hrs</div>
            </div>
          </div>
          <div class="mt-2 uppercase font-extrabold">Tributo</div>
          <div class="text-sm uppercase opacity-90">Jon Bon Jovi</div>

          <!-- Botón Más detalles -->
          <div class="mt-4">
            <a href="/mesas.php"
               class="inline-block bg-red-600 hover:bg-red-700 focus:ring-4 focus:ring-red-500/30
                      text-white font-extrabold tracking-widest uppercase px-4 py-2 rounded">
              Más detalles
            </a>
          </div>
        </article>

        <!-- Mes SEPTIEMBRE (vertical) -->
        <div class="hidden md:flex col-span-1 justify-center">
          <div class="vertical uppercase text-sm font-extrabold tracking-widest">Septiembre</div>
        </div>

        <!-- 5 Sept - Gustavo Cerati -->
        <article class="col-span-12 md:col-span-5 lg:col-span-5 card rounded-xl p-4 sm:p-5 shadow-lg">
          <div class="flex items-end gap-3">
            <div class="text-5xl sm:text-6xl font-black leading-none">5</div>
            <div class="mb-1">
              <div class="uppercase font-extrabold tracking-widest">Viernes</div>
              <div class="text-sm font-semibold opacity-90">22:00 hrs</div>
            </div>
          </div>
          <div class="mt-2 uppercase font-extrabold">Tributo</div>
          <div class="text-sm uppercase opacity-90">Gustavo Cerati</div>

          <!-- Botón Más detalles -->
          <div class="mt-4">
            <a href="/mesas.php"
               class="inline-block bg-red-600 hover:bg-red-700 focus:ring-4 focus:ring-red-500/30
                      text-white font-extrabold tracking-widest uppercase px-4 py-2 rounded">
              Más detalles
            </a>
          </div>
        </article>

        <!-- 6 Sept - Cuecas Urbanas -->
        <article class="col-span-12 md:col-span-6 lg:col-span-6 card rounded-xl p-4 sm:p-5 shadow-lg">
          <div class="flex items-end gap-3">
            <div class="text-5xl sm:text-6xl font-black leading-none">6</div>
            <div class="mb-1">
              <div class="uppercase font-extrabold tracking-widest">Sábado</div>
              <div class="text-sm font-semibold opacity-90">22:00 hrs</div>
            </div>
          </div>
          <div class="mt-2 uppercase font-extrabold">Este sí que es lote</div>
          <div class="text-sm uppercase opacity-90">Cuecas urbanas</div>

          <!-- Botón Más detalles -->
          <div class="mt-4">
            <a href="/mesas.php"
               class="inline-block bg-red-600 hover:bg-red-700 focus:ring-4 focus:ring-red-500/30
                      text-white font-extrabold tracking-widest uppercase px-4 py-2 rounded">
              Más detalles
            </a>
          </div>
        </article>

        <!-- Mes SEPTIEMBRE (vertical) -->
        <div class="hidden md:flex col-span-1 justify-center">
          <div class="vertical uppercase text-sm font-extrabold tracking-widest">Septiembre</div>
        </div>

        <!-- 13 Sept - Los Cantineros -->
        <article class="col-span-12 md:col-span-5 lg:col-span-5 card rounded-xl p-4 sm:p-5 shadow-lg">
          <div class="flex items-end gap-3">
            <div class="text-5xl sm:text-6xl font-black leading-none">13</div>
            <div class="mb-1">
              <div class="uppercase font-extrabold tracking-widest">Sábado</div>
              <div class="text-sm font-semibold opacity-90">22:00 hrs</div>
            </div>
          </div>
          <div class="mt-2 uppercase font-extrabold">Los Cantineros</div>
          <div class="text-sm uppercase opacity-90">Boleros</div>

          <!-- Botón Más detalles -->
          <div class="mt-4">
            <a href="/mesas.php"
               class="inline-block bg-red-600 hover:bg-red-700 focus:ring-4 focus:ring-red-500/30
                      text-white font-extrabold tracking-widest uppercase px-4 py-2 rounded">
              Más detalles
            </a>
          </div>
        </article>

        <!-- 27 Sept - Tributo Luis Miguel -->
        <article class="col-span-12 md:col-span-6 lg:col-span-6 card rounded-xl p-4 sm:p-5 shadow-lg">
          <div class="flex items-end gap-3">
            <div class="text-5xl sm:text-6xl font-black leading-none">27</div>
            <div class="mb-1">
              <div class="uppercase font-extrabold tracking-widest">Sábado</div>
              <div class="text-sm font-semibold opacity-90">22:00 hrs</div>
            </div>
          </div>
          <div class="mt-2 uppercase font-extrabold">Tributo</div>
          <div class="text-sm uppercase opacity-90">Luis Miguel</div>

          <!-- Botón Más detalles -->
          <div class="mt-4">
            <a href="/mesas.php"
               class="inline-block bg-red-600 hover:bg-red-700 focus:ring-4 focus:ring-red-500/30
                      text-white font-extrabold tracking-widest uppercase px-4 py-2 rounded">
              Más detalles
            </a>
          </div>
        </article>

      </div>
    </div>
  </section>

</body>
</html>
