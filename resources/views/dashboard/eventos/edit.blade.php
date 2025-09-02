<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Editar evento</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-neutral-950 text-neutral-100">
    <div class="max-w-xl mx-auto p-6">
        <h1 class="text-2xl font-bold mb-4">Editar evento</h1>

        @if(session('ok'))
        <div class="mb-3 px-3 py-2 rounded bg-emerald-600/20 text-emerald-300">{{ session('ok') }}</div>
        @endif

        <form method="POST" action="{{ route('dashboard.eventos.update', $evento) }}" enctype="multipart/form-data"
            class="space-y-3">
            @php
            use Illuminate\Support\Str;
            use Illuminate\Support\Facades\Storage;
            use Illuminate\Support\Carbon;

            // Formatos correctos para inputs nativos
            $fechaVal = old('fecha', Carbon::parse($evento->fecha)->format('Y-m-d'));
            $hiniVal = old('hora_inicio', Str::substr($evento->hora_inicio, 0, 5));
            $hfinVal = old('hora_termino', Str::substr($evento->hora_termino, 0, 5));

            // URL mostráble para imagen actual
            $imgActual = $evento->imagen;
            if ($imgActual && !Str::startsWith($imgActual, ['http://', 'https://', '/storage'])) {
            $imgActual = Storage::url($imgActual); // ej. public/... -> /storage/...
            }
            @endphp

            @csrf @method('PUT')

            @if ($errors->any())
            <div class="px-3 py-2 rounded bg-red-600/20 text-red-300">
                <ul class="list-disc ml-4 text-sm">
                    @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
                </ul>
            </div>
            @endif

            <div>
                <label class="block text-sm mb-1">Nombre</label>
                <input name="nombre" value="{{ old('nombre',$evento->nombre) }}"
                    class="w-full px-3 py-2 rounded bg-neutral-800" required>
            </div>

            <div>
                <label class="block text-sm mb-1">Género</label>
                <input name="genero" value="{{ old('genero',$evento->genero) }}"
                    class="w-full px-3 py-2 rounded bg-neutral-800">
            </div>

            <div class="flex items-center gap-2">
                <input type="checkbox" id="tributo" name="tributo" value="1"
                    {{ old('tributo', (int)$evento->tributo) ? 'checked' : '' }}>
                <label for="tributo">¿Tributo?</label>
            </div>

            <label class="block text-sm mb-1">Precio base por silla ($)</label>
            <input type="number" name="precio_silla_base" min="0" step="1"
                value="{{ old('precio_silla_base', $evento->precio_silla_base) }}"
                class="w-full px-3 py-2 rounded bg-neutral-800" required>


            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm mb-1">Fecha</label>
                    <input type="date" name="fecha" value="{{ $fechaVal }}"
                        class="w-full px-3 py-2 rounded bg-neutral-800" required>
                </div>
                <div>
                    <label class="block text-sm mb-1">Escenario</label>
                    <select name="id_escenario" class="w-full px-3 py-2 rounded bg-neutral-800" required>
                        <option value="1" @selected(old('id_escenario',$evento->id_escenario)==1)>Escenario 1</option>
                        <option value="2" @selected(old('id_escenario',$evento->id_escenario)==2)>Escenario 2</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm mb-1">Hora inicio</label>
                    <input type="time" name="hora_inicio" value="{{ $hiniVal }}"
                        class="w-full px-3 py-2 rounded bg-neutral-800" required>
                </div>
                <div>
                    <label class="block text-sm mb-1">Hora término</label>
                    <input type="time" name="hora_termino" value="{{ $hfinVal }}"
                        class="w-full px-3 py-2 rounded bg-neutral-800" required>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-2">
                @if($imgActual)
                <div>
                    <label class="block text-sm mb-1">Imagen actual</label>
                    <img src="{{ $imgActual }}" alt="Imagen evento" class="rounded w-full max-h-48 object-cover">
                </div>
                @endif
                <div>
                    <label class="block text-sm mb-1">Reemplazar imagen (archivo)</label>
                    <input type="file" name="imagen_file" accept="image/*" class="w-full">
                </div>
                <div>
                    <label class="block text-sm mb-1">o nueva URL</label>
                    <input type="text" name="imagen_url" value="{{ old('imagen_url') }}" placeholder="https://..."
                        class="w-full px-3 py-2 rounded bg-neutral-800">
                </div>
            </div>

            <div class="pt-2 flex flex-wrap gap-2">
                <button class="px-4 py-2 rounded bg-emerald-600 text-white">Guardar</button>
                <a href="{{ route('dashboard.eventos.index') }}" class="px-4 py-2 rounded bg-neutral-800">Volver</a>
                <a href="{{ route('dashboard.eventos.mapa', $evento->id) }}"
                    class="px-4 py-2 rounded bg-sky-700 hover:bg-sky-600 text-white">Ir al mapa</a>
            </div>
        </form>
    </div>
</body>

</html>