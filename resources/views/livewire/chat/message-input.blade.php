<div class="shrink-0 border-t border-brand/10 bg-white p-3" x-data="plazaMessageInput($wire)" x-on:livewire-upload-start="uploading = true" x-on:livewire-upload-finish="uploading = false" x-on:livewire-upload-error="uploading = false">
    @if($canSend)
        <div x-cloak x-show="pending" class="mb-2 rounded-xl bg-brand-success-soft p-3 text-sm" role="status"><span class="whitespace-pre-wrap break-words" x-text="pending?.body || 'Imagen adjunta'"></span><span class="ml-2 text-xs" x-text="error ? 'Sin confirmar' : 'Enviando…'"></span><button type="button" x-show="error" x-on:click="submit()" class="ml-2 min-h-11 font-black underline">Reintentar</button></div>
        <form x-on:submit.prevent="submit()" class="flex items-end gap-2">
            <label class="grid size-11 shrink-0 cursor-pointer place-items-center rounded-full bg-brand-surface" title="Adjuntar imágenes"><span aria-hidden="true">＋</span><span class="sr-only">Adjuntar hasta tres imágenes</span><input type="file" class="sr-only" wire:model="images" accept="image/jpeg,image/png,image/webp" multiple x-bind:disabled="sending || !!pending"></label>
            <label class="min-w-0 flex-1"><span class="sr-only">Mensaje</span><textarea x-model="draft" x-on:input.debounce.1500ms="typing()" x-on:keydown.ctrl.enter.prevent="submit()" maxlength="2000" rows="2" placeholder="Escribe un mensaje…" class="max-h-36 min-h-11 w-full resize-y rounded-xl border border-brand/15 bg-brand-surface p-3 text-sm" x-bind:disabled="!!pending"></textarea></label>
            <button class="min-h-11 shrink-0 rounded-full bg-brand-orange px-4 py-3 text-sm font-black text-white disabled:opacity-50" x-bind:disabled="sending || uploading" type="submit">Enviar</button>
        </form>
        @if($images)<p class="mt-1 text-xs text-brand-copy">{{ count($images) }} imágenes seleccionadas · máximo 3, de 5 MB cada una. <button type="button" wire:click="clearImages" x-bind:disabled="sending || !!pending" class="min-h-11 font-bold underline">Quitar imágenes</button></p>@endif
        @error('images')<p class="text-sm text-brand-danger-warm" role="alert">{{ $message }}</p>@enderror
        @error('images.*')<p class="text-sm text-brand-danger-warm" role="alert">{{ $message }}</p>@enderror
        @error('body')<p class="text-sm text-brand-danger-warm" role="alert">{{ $message }}</p>@enderror
        <p x-show="error" x-text="error" class="mt-1 text-sm text-brand-danger-warm" role="alert"></p>
    @else<p class="text-center text-sm text-brand-copy">No se pueden enviar mensajes en este chat.</p>@endif
</div>
