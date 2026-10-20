<x-mail::message>
# ¡Hola{{ $invitacion->nombre ? ' '.$invitacion->nombre : '' }}! ⚽

@if ($invitacion->tipo === 'dt')
{{ $invitadoPor ?? 'El organizador' }} te registró como **Director Técnico de {{ $equipo }}** en el campeonato **{{ $liga }}**.

Completa tu inscripción para administrar tu equipo: cargar el plantel, el color de la camiseta, el escudo y la foto del equipo.
@else
{{ $invitadoPor ?? 'Tu DT' }} te sumó al plantel de **{{ $equipo }}** en el campeonato **{{ $liga }}**@if ($ficha?->dorsal) con la camiseta **#{{ $ficha->dorsal }}**@endif.

Completa tu inscripción para ver tus partidos, tus goles y tus logros, y subir tu foto.
@endif

<x-mail::button :url="$url">
Completar inscripción
</x-mail::button>

Puedes entrar con tu cuenta de Google o crear una contraseña. Si ya tienes cuenta en {{ config('app.name') }}, solo inicia sesión.

Si no esperabas este correo, ignóralo.

Saludos,<br>
{{ config('app.name') }}
</x-mail::message>
