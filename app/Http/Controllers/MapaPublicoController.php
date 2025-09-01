<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Evento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MapaEditorController extends Controller
{
    /** Carga editor de mapa */
    public function edit(Evento $evento)
    {
        $cols   = Schema::getColumnListing('escenario');
        $hasX   = in_array('x', $cols);
        $hasY   = in_array('y', $cols);
        $hasRot = in_array('rot', $cols);

        $q = DB::table('escenario as e')
            ->join('mesa as m', 'm.id', '=', 'e.id_mesa')
            ->where('e.id_evento', $evento->id)
            ->select('e.id', 'e.id_mesa', 'm.sillas');

        $q->addSelect($hasX ? 'e.x' : DB::raw('0 as x'));
        $q->addSelect($hasY ? 'e.y' : DB::raw('0 as y'));
        $q->addSelect($hasRot ? 'e.rot' : DB::raw('0 as rot'));

        $mesas = $q->orderBy('e.id')->get();

        return view('dashboard.mapa-editor', compact('evento', 'mesas'));
    }

    /** Guarda TODO el layout + precio base por silla */
    public function save(Request $request, Evento $evento)
    {
        try {
            $data = $request->validate([
                'mesas'                => 'array',
                'mesas.*.sillas'       => 'required|integer|min:1|max:8',
                'mesas.*.x'            => 'required|integer|min:0',
                'mesas.*.y'            => 'required|integer|min:0',
                'mesas.*.rot'          => 'nullable|integer|in:0,90,180,270',
                'precio_silla_base'    => 'nullable|numeric|min:0',
            ]);

            $mesas = $data['mesas'] ?? [];

            DB::transaction(function () use ($evento, $mesas, $data) {
                // 1) Precio por silla (si existe la columna)
                $colsEvento = Schema::getColumnListing('eventos');
                $precioCol = null;
                foreach (['precio_silla_base', 'precio_silla'] as $c) {
                    if (in_array($c, $colsEvento)) { $precioCol = $c; break; }
                }
                if ($precioCol !== null && array_key_exists('precio_silla_base', $data)) {
                    DB::table('eventos')
                        ->where('id', $evento->id)
                        ->update([$precioCol => $data['precio_silla_base'] ?? 0]);
                }

                // 2) Borrar layout anterior
                $mesaIds = DB::table('escenario')
                    ->where('id_evento', $evento->id)
                    ->pluck('id_mesa');

                if ($mesaIds->count()) {
                    DB::table('escenario')->where('id_evento', $evento->id)->delete();
                    DB::table('mesa')->whereIn('id', $mesaIds)->delete();
                }

                // 3) Insertar nuevas mesas
                $colsEsc   = Schema::getColumnListing('escenario');
                $hasEstado = in_array('estado', $colsEsc);
                $hasX      = in_array('x', $colsEsc);
                $hasY      = in_array('y', $colsEsc);
                $hasRot    = in_array('rot', $colsEsc);
                $hasHora   = in_array('hora_reserva', $colsEsc);

                foreach ($mesas as $m) {
                    $mesaId = DB::table('mesa')->insertGetId([
                        'sillas' => (int) $m['sillas'],
                    ]);

                    $payload = [
                        'id_evento'    => (int) $evento->id,
                        'id_mesa'      => (int) $mesaId,
                        'id_escenario' => (int) $evento->id_escenario,
                    ];
                    if ($hasX)      $payload['x'] = (int) $m['x'];
                    if ($hasY)      $payload['y'] = (int) $m['y'];
                    if ($hasRot)    $payload['rot'] = (int) ($m['rot'] ?? 0);
                    if ($hasEstado) $payload['estado'] = 'disponible';
                    if ($hasHora)   $payload['hora_reserva'] = null;

                    DB::table('escenario')->insert($payload);
                }
            });

            return response()->json(['ok' => true]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'msg' => $e->getMessage()], 500);
        }
    }
}
