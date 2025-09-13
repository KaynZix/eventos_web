<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReservaCreada extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public array $evento,     // ['id'=>..,'nombre'=>..]
        public array $cliente,    // ['nombre'=>..,'correo'=>..,'numero_wsp'=>..]
        public array $items,      // [['escenario_id'=>..,'sillas'=>..,'precio'=>..], ...]
        public int   $total,      // total en CLP
        public int   $reservaId,  // id reserva
        public string $llegada    // "HH:MM:SS"
    ) {}

    public function build()
    {
        return $this->subject('Confirmación de reserva #'.$this->reservaId.' — '.$this->evento['nombre'])
                    ->markdown('emails.reserva.creada');
    }
}
