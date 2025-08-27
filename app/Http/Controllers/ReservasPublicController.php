<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Evento;

class ReservasPublicController extends Controller
{
    /**
     * Recibe: evento_id, llegada (HH:MM), mesas [escenario_id...], flow (promos|pago)
     * Valida que las mesas pertenezcan al evento y no estén reservadas.
     * Guarda en sesión la "reserva en curso".
     */
    public function start(Request $r)
    {
        $data = $r->validate([
            'evento_id' => 'required|integer',
            'llegada'   => 'required|string',     // HH:MM
            'mesas'     => 'required|array|min:1',
            'mesas.*'   => 'integer',
            'flow'      => 'required|in:promos,pago',
        ]);

        $evento = Evento::findOrFail($data['evento_id']);

        // Validar que las mesas existen y pertenecen al evento y no estén reservadas ya
        $escenarios = DB::table('escenario as e')
            ->leftJoin('reserva_mesa as rm', 'rm.escenario_id', '=', 'e.id')
            ->leftJoin('reserva as r', function($q) use ($evento) {
                $q->on('r.id', '=', 'rm.reserva_id')->where('r.id_evento', $evento->id);
            })
            ->where('e.id_evento', $evento->id)
            ->whereIn('e.id', $data['mesas'])
            ->select('e.id', DB::raw('CASE WHEN rm.id IS NULL THEN 0 ELSE 1 END as reservada'))
            ->get();

        if ($escenarios->count() !== count($data['mesas'])) {
            return response()->json(['ok'=>false,'msg'=>'Hay mesas inválidas en la selección'], 422);
        }
        if ($escenarios->firstWhere('reservada', 1)) {
            return response()->json(['ok'=>false,'msg'=>'Alguna mesa ya fue reservada'], 422);
        }

        // Guardar en sesión
        session([
            'reserva' => [
                'evento_id' => $evento->id,
                'llegada'   => $data['llegada'],
                'mesas'     => array_values($data['mesas']),
                'promos'    => [], // se llenará en el paso de promos
            ]
        ]);

        return response()->json([
            'ok'=>true,
            'redirect' => $data['flow']==='promos'
                ? route('reservas.promos')
                : route('reservas.checkout'),
        ]);
    }

    /** Página de selección de promos (no suma al total) */
    public function promos()
    {
        $reserva = session('reserva');
        if (!$reserva) return redirect()->route('welcome');

        // Puedes cargar productos reales si tienes tabla productos,
        // aquí dejo un arreglo de ejemplo:
        $promos = [
            ['id'=>1,'nombre'=>'Promo 2 schop + papas'],
            ['id'=>2,'nombre'=>'Promo Nachos + 2 bebestibles'],
            ['id'=>3,'nombre'=>'Cerveza 1L + papas'],
        ];
        return view('publico.promos', compact('reserva','promos'));
    }

    public function promosStore(Request $r)
    {
        $reserva = session('reserva');
        if (!$reserva) return redirect()->route('welcome');

        $promos = $r->input('promos', []); // array de IDs
        $reserva['promos'] = $promos;
        session(['reserva'=>$reserva]);

        return redirect()->route('reservas.checkout');
    }

    /** Checkout: datos del cliente y confirmación SIN pago (por ahora) */
    public function checkout()
    {
        $reserva = session('reserva');
        if (!$reserva) return redirect()->route('welcome');

        $evento = Evento::findOrFail($reserva['evento_id']);
        // Precio opcional: 0 por ahora
        $total = 0;

        return view('publico.checkout', compact('reserva','evento','total'));
    }

    /** Crea la reserva y asocia mesas en pivote reserva_mesa */
    public function checkoutStore(Request $r)
    {
        $reserva = session('reserva');
        if (!$reserva) return redirect()->route('welcome');

        $data = $r->validate([
            'nombre'     => 'required|string|max:80',
            'correo'     => 'required|email|max:120',
            'numero_wsp' => 'required|string|max:20',
        ]);

        DB::beginTransaction();
        try {
            $reservaId = DB::table('reserva')->insertGetId([
                'nombre_cliente' => $data['nombre'],
                'correo'         => $data['correo'],
                'numero_wsp'     => $data['numero_wsp'],
                'hora_reserva'   => $reserva['llegada'],
                'mesa'           => 0, // campo legacy; no lo usamos (múltiples mesas)
                'id_evento'      => $reserva['evento_id'],
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            foreach ($reserva['mesas'] as $escenarioId) {
                DB::table('reserva_mesa')->insert([
                    'reserva_id'  => $reservaId,
                    'escenario_id'=> $escenarioId,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors('No se pudo guardar la reserva: '.$e->getMessage())->withInput();
        }

        // Podríamos enviar correo aquí (más adelante).
        session()->forget('reserva');

        return redirect()->route('reservas.ok')->with('ok', 'Reserva creada #'.$reservaId);
    }

    public function ok()
    {
        return view('publico.ok');
    }
}
