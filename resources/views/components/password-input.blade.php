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
            class="w-full rounded-2xl border border-brand-forest-deep/15 bg-white px-4 py-3.5 pr-28 outline-none transition focus:border-brand-forest-strong focus:ring-4 focus:ring-brand-forest-strong/10"
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
            class="absolute right-2 top-1/2 inline-flex min-h-10 -translate-y-1/2 appearance-none items-center gap-2 rounded-xl border-0 bg-brand-mint-soft px-3 text-sm font-black text-brand-forest shadow-none transition hover:bg-brand-success-pale focus:outline-none focus:ring-2 focus:ring-brand-forest-strong/25"
            type="button"
            data-password-toggle="{{ $id }}"
            aria-label="Mostrar {{ mb_strtolower($label) }}"
            aria-controls="{{ $id }}"
            aria-pressed="false"
        >
            <svg class="size-4 shrink-0" data-password-eye aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z" />
                <circle cx="12" cy="12" r="2.5" />
            </svg>
            <span data-password-toggle-label>Mostrar</span>
        </button>
    </div>
    @error($name) <span class="mt-2 block text-sm font-bold text-red-600">{{ $message }}</span> @enderror
</div>
