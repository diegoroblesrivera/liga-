{{-- Botón "Borrar pichanga" (solo organizador), con advertencia de que no se puede recuperar.
     Requiere: $pichanga. Opcional: $clases para el botón. --}}
@php
    $miembros = $pichanga->miembros()->count();
    $aviso = "¿Borrar la pichanga \"{$pichanga->nombre}\"?\n\n"
        ."Se eliminarán PARA SIEMPRE todas sus fechas, la asistencia, los invitados, los pagos y los comprobantes subidos, "
        ."y sus {$miembros} ".($miembros === 1 ? 'miembro dejará' : 'miembros dejarán')." de verla.\n\n"
        ."Esta acción NO se puede recuperar.";
@endphp
<form method="POST" action="{{ route('pichangas.destroy', $pichanga) }}"
      data-confirm="{{ $aviso }}" onsubmit="return confirm(this.dataset.confirm)">
    @csrf
    @method('DELETE')
    <button type="submit" class="{{ $clases ?? 'text-sm font-medium text-red-600 hover:text-red-800' }}">
        Borrar pichanga
    </button>
</form>
