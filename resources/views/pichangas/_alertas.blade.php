@if (session('status'))
    <div class="rounded-md bg-green-50 p-4 text-sm text-green-700">{{ session('status') }}</div>
@endif

@if ($errors->any())
    <div class="rounded-md bg-red-50 p-4 text-sm text-red-700">
        @foreach ($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
@endif
