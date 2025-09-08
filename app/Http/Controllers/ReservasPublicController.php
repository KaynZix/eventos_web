<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cookie;
use App\Models\Evento;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

class ReservasPublicController extends Controller
{
    /**
     * Inicia la reserva: valida mesas, crea holds (8 min),
     * guarda en sesión y deja un cookie para reanudar.
     * Recibe: evento_id, llegada (HH:MM), mesas[escenario_id...], flow(promos|pago)
     */
    public function start(Request $r)
    {
        $data = $r->validate([
            'evento_id' => 'required|integer',
            'llegada'   => 'required|string',
            'mesas'     => 'required|array|min:1',
            'mesas.*'   => 'integer',
            'flow'      => 'required|in:promos,pago',
        ]);

        $evento = Evento::findOrFail($data['evento_id']);

        // Limpia holds vencidos (higiene)
        DB::table('mesas_hold')->where('expires_at', '<=', DB::raw('UTC_TIMESTAMP()'))->delete();

        // Validar que las mesas existen, pertenecen al evento y no están reservadas
        $esc = DB::table('escenario as e')
            ->leftJoin('reserva_mesa as rm', 'rm.escenario_id', '=', 'e.id')
            ->leftJoin('reserva as r', function($q) use ($evento){
                $q->on('r.id','=','rm.reserva_id')->where('r.id_evento',$evento->id);
            })
            ->where('e.id_evento',$evento->id)
            ->whereIn('e.id',$data['mesas'])
            ->select('e.id', DB::raw('CASE WHEN r.id IS NULL THEN 0 ELSE 1 END as reservada'))
            ->get();

        if ($esc->count() !== count($data['mesas'])) {
            return response()->json(['ok'=>false,'msg'=>'Mesas inválidas.'], 422);
        }
        if ($esc->firstWhere('reservada',1)) {
            return response()->json(['ok'=>false,'msg'=>'Alguna mesa ya está reservada.'], 422);
        }

        // Crear HOLDs (8 minutos) de forma atómica
        $token     = Str::uuid()->toString();
        $sessionId = $r->session()->getId();
        $expiresAt = Carbon::now()->addMinutes(8);

        DB::beginTransaction();
        try {
            foreach ($data['mesas'] as $escId) {
                // Impide hold si ya hay uno activo para esa mesa
                $inserted = DB::insert(
                    "INSERT INTO mesas_hold (evento_id, escenario_id, session_id, hold_token, expires_at, created_at, updated_at)
                    SELECT ?, ?, ?, ?, DATE_ADD(UTC_TIMESTAMP(), INTERVAL 8 MINUTE), UTC_TIMESTAMP(), UTC_TIMESTAMP()
                    FROM DUAL
                    WHERE NOT EXISTS (
                      SELECT 1 FROM mesas_hold h
                      WHERE h.evento_id = ? AND h.escenario_id = ? AND h.expires_at > UTC_TIMESTAMP()
                    )
                    ", [$evento->id, $escId, $sessionId, $token, $evento->id, $escId]);

                if (!$inserted) {
                    DB::rollBack();
                    return response()->json(['ok'=>false,'msg'=>'Una mesa fue tomada por otro usuario.'], 409);
                }
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['ok'=>false,'msg'=>'No se pudo bloquear las mesas.'], 500);
        }

        // Cookie para poder reanudar (vigencia igual/menor al hold)
        Cookie::queue(cookie(
            'reserva_hold',
            json_encode([
                'token'     => $token,
                'evento_id' => $evento->id,
                'llegada'   => $data['llegada'],
                'flow'      => $data['flow'], // promos|pago
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            10 // minutos
        ));

        // Guardar “reserva en curso” + info del hold en sesión
        session([
            'reserva' => [
                'evento_id' => $evento->id,
                'llegada'   => $data['llegada'],
                'mesas'     => array_values($data['mesas']),
                'promos'    => [],
                'hold'      => [
                    'token'      => $token,
                    'expires_at' => $expiresAt->toIso8601String(),
                ],
            ]
        ]);

        return response()->json([
            'ok'       => true,
            'redirect' => $data['flow']==='promos'
                ? route('reservas.promos')
                : route('reservas.checkout'),
        ]);
    }

    /** Segundos restantes del hold; <=0 si expiró */
    protected function remainingSecondsOrFail(): int
    {
        $hold = session('reserva.hold');
        if (!$hold) return 0;
        return Carbon::now()->diffInSeconds(Carbon::parse($hold['expires_at']), false);
    }

    /** Página de selección de promos (no suma al total) */
    public function promos()
    {
        $reserva = session('reserva');
        if (!$reserva) return redirect()->route('welcome');

        $left = $this->remainingSecondsOrFail();
        if ($left <= 0) {
            return redirect()->route('mapa.publico', $reserva['evento_id'])
                ->with('err','Tu tiempo para completar la reserva expiró.');
        }

        $promos = [
            ['id'=>1,'nombre'=>'Promo 2 schop + papas'],
            ['id'=>2,'nombre'=>'Promo Nachos + 2 bebestibles'],
            ['id'=>3,'nombre'=>'Cerveza 1L + papas'],
        ];

        return view('publico.promos', [
            'reserva'     => $reserva,
            'promos'      => $promos,
            'secondsLeft' => $left,
        ]);
    }

    public function promosStore(Request $r)
    {
        $reserva = session('reserva');
        if (!$reserva) return redirect()->route('welcome');

        $promos = $r->validate(['promos'=>'array'])['promos'] ?? [];
        $reserva['promos'] = array_values($promos);
        session(['reserva'=>$reserva]);

        return redirect()->route('reservas.checkout');
    }

    /** Checkout: datos del cliente y confirmación SIN pago (por ahora) */
    public function checkout()
    {
        $reserva = session('reserva');
        if (!$reserva) return redirect()->route('welcome');

        $left = $this->remainingSecondsOrFail();
        if ($left <= 0) {
            return redirect()->route('mapa.publico', $reserva['evento_id'])
                ->with('err','Tu tiempo para completar la reserva expiró.');
        }

        $evento = Evento::findOrFail($reserva['evento_id']);
        $total  = 0;

        return view('publico.checkout', [
            'reserva'     => $reserva,
            'evento'      => $evento,
            'total'       => $total,
            'secondsLeft' => $left,
        ]);
    }

    /** Crea la reserva y asocia mesas en pivote reserva_mesa */
    public function checkoutStore(Request $r)
    {
        $reserva = session('reserva');
        if (!$reserva) return redirect()->route('eventos.index');

        // Hold vigente
        $left = $this->remainingSecondsOrFail();
        if ($left <= 0) {
            return redirect()->route('mapa.publico', $reserva['evento_id'])
                ->with('err','El tiempo expiró, vuelve a elegir las mesas.');
        }

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
                'mesa'           => 0,
                'id_evento'      => $reserva['evento_id'],
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            foreach ($reserva['mesas'] as $escenarioId) {
                DB::table('reserva_mesa')->insert([
                    'reserva_id'   => $reservaId,
                    'escenario_id' => $escenarioId,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);
            }

            // Liberar holds de este usuario/token
            DB::table('mesas_hold')->where('hold_token', $reserva['hold']['token'])->delete();

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors('No se pudo guardar la reserva: '.$e->getMessage())->withInput();
        }

        // Limpia sesión y cookie
        session()->forget('reserva');
        Cookie::queue(Cookie::forget('reserva_hold'));

        return redirect()->route('reservas.ok')->with('ok', 'Reserva creada #'.$reservaId);
    }

    public function ok()
    {
        return view('publico.ok');
    }

    /** Cancelar: libera holds, borra cookie y limpia sesión */
    public function cancel(Request $r)
    {
        $reserva = session('reserva');

        if ($reserva && isset($reserva['hold']['token'])) {
            DB::table('mesas_hold')->where('hold_token', $reserva['hold']['token'])->delete();
        } else {
            // Si no hay sesión, intenta por cookie
            $cookie = $r->cookie('reserva_hold');
            if ($cookie) {
                $meta = json_decode($cookie, true);
                if (!empty($meta['token'])) {
                    DB::table('mesas_hold')->where('hold_token', $meta['token'])->delete();
                }
            }
        }

        session()->forget('reserva');
        Cookie::queue(Cookie::forget('reserva_hold'));

        return redirect()->route('eventos.index')->with('ok', 'Reserva cancelada.');
    }

    /** Reanudar reserva a partir del cookie si los holds siguen vigentes */
    public function resume(Request $r)
    {
        // 1) Si ya hay reserva en sesión y hold vigente, continuar
        $reserva = session('reserva');
        if ($reserva && isset($reserva['hold']['token'])) {
            $left = $this->remainingSecondsOrFail();
            if ($left > 0) {
                return redirect()->route('reservas.checkout');
            }
        }

        // 2) Intentar por cookie
        $cookie = $r->cookie('reserva_hold');
        if (!$cookie) {
            return redirect()->route('eventos.index')->with('err','No hay una reserva activa para reanudar.');
        }

        $meta = json_decode($cookie, true);
        if (!$meta || empty($meta['token']) || empty($meta['evento_id'])) {
            return redirect()->route('eventos.index')->with('err','No hay una reserva activa para reanudar.');
        }

        // 3) Cargar holds vigentes
        $holds = DB::table('mesas_hold')
            ->where('hold_token', $meta['token'])
            ->where('evento_id', $meta['evento_id'])
            ->where('expires_at', '>', now())
            ->get(['escenario_id','expires_at']);

        if ($holds->isEmpty()) {
            Cookie::queue(Cookie::forget('reserva_hold'));
            return redirect()->route('mapa.publico', $meta['evento_id'])
                ->with('err', 'El tiempo de tu reserva expiró. Vuelve a elegir las mesas.');
        }

        // 4) Reconstruir sesión "reserva"
        $expiresAt = Carbon::parse($holds->min('expires_at'));
        session([
            'reserva' => [
                'evento_id' => (int)$meta['evento_id'],
                'llegada'   => $meta['llegada'] ?? '22:00',
                'mesas'     => $holds->pluck('escenario_id')->map(fn($v)=>(int)$v)->values()->all(),
                'promos'    => [],
                'hold'      => [
                    'token'      => $meta['token'],
                    'expires_at' => $expiresAt->toIso8601String(),
                ],
            ]
        ]);

        // 5) Redirigir según flow original
        if (($meta['flow'] ?? 'pago') === 'promos') {
            return redirect()->route('reservas.promos')->with('ok','Reanudaste tu reserva.');
        }
        return redirect()->route('reservas.checkout')->with('ok','Reanudaste tu reserva.');
    }
}
