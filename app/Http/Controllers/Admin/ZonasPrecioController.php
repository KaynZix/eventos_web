<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Evento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ZonasPrecioController extends Controller
{
    // Página del editor visual
    public function editor(Evento $evento)
    {
        // No enviamos zonas aquí; el front las pide por AJAX a /zonas/data
        return view('dashboard.zonas.editor', compact('evento'));
    }

    // Zonas del evento (JSON)
    public function data(Evento $evento)
    {
        $rows = DB::table('evento_zona_precio')
            ->where('evento_id', $evento->id)
            ->orderBy('id')
            ->get(['id','nombre','x','y','w','h','factor','color']);

        return response()->json($rows);
    }

    // Guarda TODO (reemplaza)
    public function saveAll(Request $request, Evento $evento)
    {
        $data = $request->validate([
            'zonas'           => 'array',
            'zonas.*.nombre'  => 'nullable|string|max:80',
            'zonas.*.x'       => 'required|integer|min:0',
            'zonas.*.y'       => 'required|integer|min:0',
            'zonas.*.w'       => 'required|integer|min:10',
            'zonas.*.h'       => 'required|integer|min:10',
            'zonas.*.factor' => 'required|integer|min:0|max:100000000',
            'zonas.*.color'   => 'required|string|max:16',
        ]);

        $zonas = $data['zonas'] ?? [];

        DB::transaction(function () use ($evento, $zonas) {
            DB::table('evento_zona_precio')
                ->where('evento_id', $evento->id)
                ->delete();

            foreach ($zonas as $z) {
                DB::table('evento_zona_precio')->insert([
                    'evento_id' => $evento->id,
                    'nombre'    => $z['nombre'] ?? null,
                    'x'         => (int)$z['x'],
                    'y'         => (int)$z['y'],
                    'w'         => (int)$z['w'],
                    'h'         => (int)$z['h'],
                    'factor'    => (int)$z['factor'],   // recargo fijo por silla
                    'color'     => $z['color'],
                    'created_at'=> now(),
                    'updated_at'=> now(),
                ]);
            }
        });

        return response()->json(['ok' => true]);
    }
}
