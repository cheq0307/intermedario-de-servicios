@props(['listing', 'compact' => false, 'href' => null])
@php
    $listingType = $listing->type->value;
    $typeLabel = $listingType === 'product' ? 'Producto' : 'Servicio';
    $categoryName = $listing->category?->name ?? 'Rubro por actualizar';
    $communityName = $listing->vendor?->user?->community?->name ?? 'Tu comunidad';
    $image = $listing->post?->media?->firstWhere('type', 'image');
    $canBuy = $listingType === 'product'
        && $listing->price_type->value === 'fixed'
        && $listing->price_amount !== null;
    $destination = $href ?: ($canBuy
        ? route('products.checkout', $listing)
        : route('profile.show', $listing->vendor->user));
@endphp

<a {{ $attributes->class([
    'group flex min-w-0 flex-col overflow-hidden rounded-[1.4rem] border border-plaza-line/80 bg-white shadow-sm transition hover:-translate-y-1 hover:border-plaza-orange/35 hover:shadow-market-card-hover',
]) }} href="{{ $destination }}">
    <div class="relative {{ $compact ? 'h-32' : 'h-40' }} overflow-hidden bg-gradient-to-br from-plaza-mint via-plaza-cream to-brand-gold-highlight">
        @if($image)
            <img class="size-full object-cover transition duration-500 group-hover:scale-105" src="{{ $image->url }}" alt="{{ $image->alt_text ?: $listing->name }}" loading="lazy">
        @else
            <span class="grid size-full place-items-center px-4 text-center text-xs font-black text-brand-muted">{{ $typeLabel }} disponible en {{ $communityName }}</span>
        @endif
        @if($listing->post?->activePromotion)
            <span class="absolute right-3 top-3 rounded-full bg-plaza-ink/90 px-2.5 py-1 text-[10px] font-black text-white shadow-sm">Patrocinado</span>
        @endif
    </div>

    <div class="flex flex-1 flex-col p-4">
        <p class="text-[10px] font-black uppercase tracking-[.12em] text-plaza-orange" aria-label="Ruta local de la publicación">{{ $typeLabel }} · {{ $categoryName }}</p>
        <h3 class="mt-2 line-clamp-2 min-h-12 text-base font-black leading-6 text-plaza-ink">{{ $listing->name }}</h3>
        <p class="mt-1 truncate text-xs font-bold text-brand-muted">{{ $listing->vendor->display_name }}</p>
        <div class="mt-auto flex items-end justify-between gap-3 pt-4">
            <span class="min-w-0">
                <strong class="block truncate text-sm text-brand-success">
                    @if($listing->price_amount === null)
                        Cotizar
                    @else
                        {{ $listing->price_type->value === 'starting_at' ? 'Desde ' : '' }}${{ number_format($listing->price_amount / 100, 2) }}
                    @endif
                </strong>
                <span class="mt-1 flex items-center gap-1 truncate text-[11px] font-bold text-brand-muted">
                    <svg class="size-3 shrink-0 text-plaza-orange" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2"/></svg>
                    {{ $communityName }}
                </span>
            </span>
            <span class="shrink-0 rounded-full bg-plaza-ink px-3 py-2 text-[11px] font-black text-white">{{ $canBuy ? 'Comprar' : 'Ver perfil' }}</span>
        </div>
    </div>
</a>
