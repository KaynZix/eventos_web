<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Cacho e' Cabra — Hero</title>
  <!-- Tailwind CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-neutral-900 text-white">

  <!-- HERO -->
  <section class="min-h-screen grid grid-cols-1 lg:grid-cols-2">
    <!-- LADO IZQUIERDO (fondo negro) -->
    <div class="bg-black flex items-center">
      <div class="w-11/12 max-w-xl mx-auto py-12 lg:py-0">
        <!-- LOGO (texto simulando el logo de la imagen) -->
        <div class="mb-6">
          <div class="flex items-center gap-2">
            <span class="inline-block text-4xl sm:text-5xl font-black tracking-tight">CA</span>
            <!-- ají “logo” simple -->
            <span class="inline-block w-2 h-8 bg-red-600 rounded-full relative -mt-1">
              <span class="absolute -top-2 left-1/2 -translate-x-1/2 w-2 h-2 bg-green-600 rounded-full"></span>
            </span>
            <span class="inline-block text-4xl sm:text-5xl font-black tracking-tight">HO</span>
          </div>
          <div class="text-3xl sm:text-4xl font-extrabold -mt-1 tracking-tight">
            E’CABRA
          </div>
        </div>

        <!-- TITULAR -->
        <h1 class="font-extrabold leading-tight tracking-tight
                   text-4xl sm:text-5xl md:text-6xl">
          EL RESTOBAR DE LA<br/>
          MÚSICA EN VIVO<br/>
          EN LOS ANDES
        </h1>

        <!-- BOTÓN -->
        <div class="mt-10">
          <a href="{{ route('eventos')  }}"
             class="inline-block bg-red-600 hover:bg-red-700 focus:ring-4 focus:ring-red-500/30
                    text-white font-extrabold tracking-widest uppercase
                    px-8 py-4 text-lg rounded">
            Próximos eventos
          </a>
        </div>
      </div>
    </div>

    <!-- LADO DERECHO (imagen con marco rojo) -->
    <div class="bg-neutral-900 flex items-center justify-center p-6 lg:p-10">
      <div class="border-[14px] border-red-600">
        <!-- Cambia el src por tu imagen real -->
        <img src="https://images.unsplash.com/photo-1555396273-367ea4eb4db5?q=80&w=1400&auto=format&fit=crop"
             alt="Cacho e' Cabra Restaurant"
             class="block max-h-[80vh] lg:max-h-[85vh] object-cover">
      </div>
    </div>
  </section>

</body>
</html>
