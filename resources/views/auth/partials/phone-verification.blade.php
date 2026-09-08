@if(session('status'))<p class="mt-4 text-brand-success" role="status">{{ session('status') }}</p>@endif
@foreach($errors->all() as $error)<p class="mt-3 text-red-700" role="alert">{{ $error }}</p>@endforeach
@if(!$configured)<p class="mt-4 text-brand-warning">El envío de SMS aún no está habilitado.</p>@endif
<form method="POST" action="{{ route($identity instanceof \App\Models\AdminUser ? 'admin.phone.send' : 'phone.send') }}" class="mt-5 space-y-3">
    @csrf
    <label class="block">Teléfono celular (México)<input class="mt-2 w-full rounded-xl border p-3" type="tel" name="phone" value="{{ old('phone',$identity->phone) }}" required pattern="[0-9]{10}" maxlength="10" inputmode="numeric" autocomplete="tel-national"></label>
    <button class="min-h-11 rounded-xl bg-brand px-5 py-3 font-bold text-white disabled:opacity-50" @disabled(!$configured)>Enviar código por SMS</button>
</form>
<form method="POST" action="{{ route($identity instanceof \App\Models\AdminUser ? 'admin.phone.verify' : 'phone.verify') }}" class="mt-6 space-y-3">
    @csrf
    <label class="block">Código de 6 dígitos<input class="mt-2 w-full rounded-xl border p-3" name="code" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" required></label>
    <button class="min-h-11 rounded-xl bg-brand px-5 py-3 font-bold text-white disabled:opacity-50" @disabled(!$configured)>Verificar teléfono</button>
</form>
