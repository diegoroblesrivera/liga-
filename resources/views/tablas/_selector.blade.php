@if ($torneos->count() > 1)
    <form method="GET" class="mb-4">
        <label for="torneo" class="mr-2 text-sm text-gray-600">Torneo:</label>
        <select id="torneo" name="torneo" onchange="this.form.submit()"
                class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            @foreach ($torneos as $t)
                <option value="{{ $t->id }}" @selected($torneo && $torneo->id === $t->id)>{{ $t->nombre }}</option>
            @endforeach
        </select>
    </form>
@endif
