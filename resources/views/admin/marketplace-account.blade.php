<x-admin-layout title="Mi cuenta de Plaza Local" section="identity">
    <h1 class="text-2xl font-black">Mi cuenta de Plaza Local</h1>
    @if(session('status'))<p class="mt-4 text-brand-success">{{ session('status') }}</p>@endif
    @foreach($errors->all() as $error)<p class="mt-3 text-red-700">{{ $error }}</p>@endforeach
    @if($linked)
        <p class="mt-4">Tu cuenta de Plaza Local ya está vinculada. Su contraseña y acceso son independientes.</p>
        <a class="mt-4 inline-block text-brand-success" href="{{ route('login') }}">Entrar a Plaza Local</a>
    @else
        <p class="mt-3">Se utilizarán el correo y teléfono verificados de tu cuenta administrativa.</p>
        <form method="POST" action="{{ route('admin.identity.create') }}" class="mt-5 max-w-lg space-y-4">@csrf
            <label class="block">Comunidad<select class="mt-2 w-full rounded-xl border p-3" name="community_id" required>@foreach($communities as $community)<option value="{{ $community->id }}">{{ $community->name }}</option>@endforeach</select></label>
            <label class="block">Contraseña para Plaza Local<input class="mt-2 w-full rounded-xl border p-3" type="password" name="password" required autocomplete="new-password"></label>
            <label class="block">Confirmar contraseña<input class="mt-2 w-full rounded-xl border p-3" type="password" name="password_confirmation" required autocomplete="new-password"></label>
            <button class="rounded-xl bg-brand p-3 text-white font-bold">Crear mi cuenta de Plaza Local</button>
        </form>
    @endif
</x-admin-layout>
