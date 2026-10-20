<?php

namespace App\Mail;

use App\Models\Invitacion;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

// Correo de la carga masiva: invita al DT o al jugador a completar su inscripción.
class InvitacionEquipo extends Mailable
{
    public function __construct(public Invitacion $invitacion, public string $equipo, public string $liga, public ?string $invitadoPor)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->invitacion->tipo === 'dt'
            ? "Eres DT de {$this->equipo} en {$this->liga}"
            : "Te sumaron a {$this->equipo} en {$this->liga}");
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.invitacion-equipo', with: [
            'url' => route('invitacion.show', $this->invitacion->token),
            'ficha' => $this->invitacion->jugador,
        ]);
    }
}
