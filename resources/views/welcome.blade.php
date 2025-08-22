<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - Eventos</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-white flex flex-col min-h-screen">

    <!-- Header -->
    @include('partials.header')

    <!-- Contenido principal -->
    <main class="flex-grow">
        <div class="container mx-auto p-4 grid grid-cols-3 gap-4">

            <!-- Afiches de eventos -->
            <div class="col-span-2 grid grid-cols-2 gap-3">

                <!-- === CARD con altura fija y botón Ver más === -->
                <div class="bg-white border rounded-lg shadow-md overflow-hidden h-60 flex flex-col">
                    <img src="https://media.discordapp.net/attachments/1337344915104862230/1407124676701982854/image.png?ex=68a8eafa&is=68a7997a&hm=376d479af0ab9d56c4d8c418bc6ed5bd558c76a1753ed29faf7c2781f963bbba&=&format=webp&quality=lossless&width=754&height=640" alt="Evento 1" class="w-full h-28 object-cover">
                    <div class="p-3 flex flex-col flex-grow">
                        <h3 class="text-base font-bold">Nombre del Evento</h3>
                        <p class="text-xs text-gray-600 mb-2">Fecha: 25 de Agosto, 2025</p>

                        <div class="mt-auto">
                            <!-- Botón que abre el modal y envía datos -->
                            <button
                                type="button"
                                class="btn-ver-mas inline-block bg-blue-600 text-white px-3 py-1.5 text-sm rounded-lg hover:bg-blue-700"
                                data-title="Nombre del evento"
                                data-subtitle="Descripción del evento"
                                data-desc="Lorem ipsum dolor sit amet consectetur adipiscing elit mi"
                                data-seats="4"
                                data-img="https://media.discordapp.net/attachments/1337344915104862230/1407124676701982854/image.png?ex=68a8eafa&is=68a7997a&hm=376d479af0ab9d56c4d8c418bc6ed5bd558c76a1753ed29faf7c2781f963bbba&=&format=webp&quality=lossless&width=754&height=640">
                                Ver más
                            </button>
                        </div>
                    </div>
                </div>

<!-- === MODAL Reutilizable === -->
<div id="eventModal" class="fixed inset-0 z-50 hidden" aria-hidden="true">
  <!-- Fondo -->
  <div id="eventModalBackdrop" class="absolute inset-0 bg-black/50"></div>

  <!-- Contenido -->
  <div class="relative mx-auto mt-10 w-[94%] max-w-3xl">
    <div class="bg-white rounded-xl shadow-2xl overflow-hidden">
      <!-- Cerrar -->
      <button id="modalClose"
        class="absolute right-3 top-3 w-9 h-9 rounded-full bg-gray-100 hover:bg-gray-200 grid place-items-center"
        aria-label="Cerrar">
        ✕
      </button>

      <!-- Layout interior -->
      <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-5 items-center">
        <!-- Imagen -->
        <div>
          <img id="modalImg" src="" alt="Imagen del evento"
               class="w-full h-48 md:h-56 object-cover rounded-md">
        </div>

        <!-- Texto -->
        <div class="pr-1">
          <h3 id="modalTitle" class="text-2xl font-extrabold text-purple-600 leading-tight">
            Nombre del evento
          </h3>
          <p id="modalSubtitle" class="text-lg font-semibold text-purple-600 mt-1">
            Descripción del evento
          </p>
          <p id="modalDesc" class="text-sm text-gray-700 mt-2">
            Lorem ipsum dolor sit amet consectetur adipiscing elit mi
          </p>

          <p class="mt-4 text-lg font-semibold text-purple-700">
            Mesas disponibles: <span id="modalSeats">4</span>
          </p>

          <button
            class="mt-4 inline-block rounded-full px-6 py-3 font-semibold text-white bg-purple-600 hover:bg-purple-700">
            Reserva aquí
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- === SCRIPT: abrir/cerrar y poblar modal === -->
<script>
  (function () {
    const modal = document.getElementById('eventModal');
    const backdrop = document.getElementById('eventModalBackdrop');
    const closeBtn = document.getElementById('modalClose');

    const elImg = document.getElementById('modalImg');
    const elTitle = document.getElementById('modalTitle');
    const elSubtitle = document.getElementById('modalSubtitle');
    const elDesc = document.getElementById('modalDesc');
    const elSeats = document.getElementById('modalSeats');

    function openModal(data) {
      elImg.src = data.img || '';
      elTitle.textContent = data.title || '';
      elSubtitle.textContent = data.subtitle || '';
      elDesc.textContent = data.desc || '';
      elSeats.textContent = data.seats || '0';

      modal.classList.remove('hidden');
      modal.setAttribute('aria-hidden', 'false');
      document.body.classList.add('overflow-hidden');
    }

    function closeModal() {
      modal.classList.add('hidden');
      modal.setAttribute('aria-hidden', 'true');
      document.body.classList.remove('overflow-hidden');
    }

    // Delegación: cualquier .btn-ver-mas abre el modal con sus data-*
    document.addEventListener('click', (e) => {
      const btn = e.target.closest('.btn-ver-mas');
      if (btn) {
        openModal({
          img: btn.dataset.img,
          title: btn.dataset.title,
          subtitle: btn.dataset.subtitle,
          desc: btn.dataset.desc,
          seats: btn.dataset.seats
        });
      }
    });

    // Cerrar por fondo, botón o tecla Esc
    backdrop.addEventListener('click', closeModal);
    closeBtn.addEventListener('click', closeModal);
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') closeModal();
    });
  })();
</script>


            </div>

            <!-- Banners -->
            <div class="flex flex-col gap-4">
                <div class="bg-purple-500 h-16 rounded-lg shadow-md"></div>
                <div class="bg-purple-500 h-16 rounded-lg shadow-md"></div>
                <div class="bg-purple-500 h-16 rounded-lg shadow-md"></div>
                <div class="bg-purple-500 h-16 rounded-lg shadow-md"></div>
                <div class="bg-purple-500 h-16 rounded-lg shadow-md"></div>
                <div class="bg-purple-500 h-16 rounded-lg shadow-md"></div>
                <div class="bg-purple-500 h-16 rounded-lg shadow-md"></div>
                <div class="bg-purple-500 h-16 rounded-lg shadow-md"></div>
                <div class="bg-purple-500 h-16 rounded-lg shadow-md"></div>
                <div class="bg-purple-500 h-16 rounded-lg shadow-md"></div>
            </div>

        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-6 mt-8">
        <div class="container mx-auto text-center">
            <p>&copy; 2025 Eventos. Todos los derechos reservados.</p>
            <p class="text-sm text-gray-400">Diseñado por TuNombre</p>
        </div>
    </footer>

</body>

</html>