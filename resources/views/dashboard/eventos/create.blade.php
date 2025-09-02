<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Nuevo evento</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-neutral-950 text-neutral-100">
    <div class="max-w-xl mx-auto p-6">
        <h1 class="text-2xl font-bold mb-4">Nuevo evento</h1>

        <form method="POST" action="{{ route('dashboard.eventos.store') }}" enctype="multipart/form-data"
            class="space-y-3">
            @csrf

            @if ($errors->any())
            <div class="px-3 py-2 rounded bg-red-600/20 text-red-300">
                <ul class="list-disc ml-4 text-sm">
                    @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
                </ul>
            </div>
            @endif

            <div>
                <label class="block text-sm mb-1">Nombre</label>
                <input name="nombre" value="{{ old('nombre') }}" class="w-full px-3 py-2 rounded bg-neutral-800"
                    required>
            </div>

            <div>
                <label class="block text-sm mb-1">Género</label>
                <input name="genero" value="{{ old('genero') }}" class="w-full px-3 py-2 rounded bg-neutral-800">
            </div>

            <div class="flex items-center gap-2">
                <input type="checkbox" id="tributo" name="tributo" value="1" {{ old('tributo')?'checked':'' }}>
                <label for="tributo">¿Tributo?</label>
            </div>

            <label class="block text-sm mb-1">Precio base por silla ($)</label>
            <input type="number" name="precio_silla_base" min="0" step="1" value="{{ old('precio_silla_base', 0) }}"
                class="w-full px-3 py-2 rounded bg-neutral-800" required>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm mb-1">Fecha</label>
                    <input type="date" name="fecha" value="{{ old('fecha') }}"
                        class="w-full px-3 py-2 rounded bg-neutral-800" required>
                </div>
                <div>
                    <label class="block text-sm mb-1">Escenario</label>
                    <select name="id_escenario" class="w-full px-3 py-2 rounded bg-neutral-800" required>
                        <option value="1" {{ old('id_escenario')==1?'selected':'' }}>Escenario 1</option>
                        <option value="2" {{ old('id_escenario')==2?'selected':'' }}>Escenario 2</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm mb-1">Hora inicio</label>
                    <input type="time" name="hora_inicio" value="{{ old('hora_inicio') }}"
                        class="w-full px-3 py-2 rounded bg-neutral-800" required>
                </div>
                <div>
                    <label class="block text-sm mb-1">Hora término</label>
                    <input type="time" name="hora_termino" value="{{ old('hora_termino') }}"
                        class="w-full px-3 py-2 rounded bg-neutral-800" required>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-2">
                <div>
                    <label class="block text-sm mb-1">Imagen (archivo)</label>
                    <input type="file" name="imagen_file" class="w-full">
                </div>
                <div>
                    <label class="block text-sm mb-1">o URL de imagen</label>
                    <input type="text" name="imagen_url" value="{{ old('imagen_url') }}" placeholder="https://..."
                        class="w-full px-3 py-2 rounded bg-neutral-800">
                </div>
            </div>

            <div class="pt-2 flex gap-2">
                <button class="px-4 py-2 rounded bg-emerald-600 text-white">Crear y configurar mapa</button>
                <a href="{{ route('dashboard.eventos.index') }}" class="px-4 py-2 rounded bg-neutral-800">Cancelar</a>
            </div>
        </form>
    </div>
</body>

</html>