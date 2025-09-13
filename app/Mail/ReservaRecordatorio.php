<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;   // <- cola
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReservaRecordatorio extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public array $evento,     // ['id','nombre','fecha','hora']
        public array $cliente,    // ['nombre','correo','numero_wsp']
        public array $items,      // [['escenario_id','sillas','precio']]
        public int   $total,
        public int   $reservaId
    ) {}

    public function build()
    {
        return $this->subject('Recordatorio: tu reserva #'.$this->reservaId.' — '.$this->evento['nombre'])
                    ->markdown('emails.reserva.recordatorio');
    }
}
