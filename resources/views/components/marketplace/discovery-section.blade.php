@props(['title', 'carouselId', 'href' => null, 'linkLabel' => 'Ver todo'])

<section {{ $attributes->class(['min-w-0']) }} data-discovery-section data-market-carousel aria-labelledby="{{ $carouselId }}-title">
    <div class="mb-3 flex items-center justify-between gap-3">
        <h2 id="{{ $carouselId }}-title" class="min-w-0 truncate text-lg font-black text-brand">{{ $title }}</h2>
        <div class="flex shrink-0 items-center gap-1">
            @if($href)
                <a class="mr-2 hidden text-xs font-black text-brand-success sm:inline" href="{{ $href }}">{{ $linkLabel }}</a>
            @endif
            <button class="hidden size-11 place-items-center rounded-full text-xl font-black text-brand transition-colors hover:bg-brand-success-soft focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-orange sm:grid" type="button" data-carousel-prev aria-label="Ver elementos anteriores de {{ $title }}">‹</button>
            <button class="hidden size-11 place-items-center rounded-full text-xl font-black text-brand transition-colors hover:bg-brand-success-soft focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-orange sm:grid" type="button" data-carousel-next aria-label="Ver más elementos de {{ $title }}">›</button>
        </div>
    </div>
    <div id="{{ $carouselId }}" class="flex snap-x snap-mandatory gap-3 overflow-x-auto pb-3 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden" data-carousel-track tabindex="0" aria-label="{{ $title }}">
        {{ $slot }}
    </div>
</section>
