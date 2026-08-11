@props([
    'id',
    'label',
    'name' => 'password',
    'autocomplete' => 'current-password',
    'minlength' => null,
    'autofocus' => false,
    'requirements' => null,
])

<div class="block">
    <label class="text-sm font-black" for="{{ $id }}">{{ $label }}</label>
    <div class="relative mt-2">
        <input
            id="{{ $id }}"
            class="w-full rounded-2xl border border-[#17352b]/15 bg-white px-4 py-3.5 pr-20 outline-none transition focus:border-[#1f6b4f] focus:ring-4 focus:ring-[#1f6b4f]/10"
            type="password"
            name="{{ $name }}"
            required
            autocomplete="{{ $autocomplete }}"
            @if($minlength) minlength="{{ $minlength }}" @endif
            @if($autofocus) autofocus @endif
            @if($requirements === 'primary') data-password @endif
            @if($requirements === 'confirmation') data-password-confirmation @endif
        >
        <button
            class="absolute inset-y-0 right-0 rounded-r-2xl px-4 text-sm font-black text-[#1f6b4f] transition hover:bg-[#e6f1eb] focus:outline-none focus:ring-4 focus:ring-inset focus:ring-[#1f6b4f]/15"
            type="button"
            data-password-toggle="{{ $id }}"
            aria-label="Mostrar {{ mb_strtolower($label) }}"
            aria-controls="{{ $id }}"
            aria-pressed="false"
        >Ver</button>
    </div>
    @error($name) <span class="mt-2 block text-sm font-bold text-red-600">{{ $message }}</span> @enderror
</div>
