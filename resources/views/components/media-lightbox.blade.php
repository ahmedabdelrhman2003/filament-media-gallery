{{-- ─── Lightbox overlay ─────────────────────────────────────────────── --}}
{{-- Rendered via @include inside media-viewer.blade.php.               --}}
{{-- Alpine state (lightboxOpen, lightboxSrc, lightboxImages,           --}}
{{-- lightboxThumbs, lightboxIndex) is inherited from the parent scope. --}}
<div
    x-show="lightboxOpen"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 flex flex-col items-center justify-center gap-4 p-6"
    style="display:none; background-color:rgba(0,0,0,0.85); backdrop-filter:blur(4px); z-index:9999;"
    @click.self="closeLightbox()"
>
    {{-- Main row: prev button + image + next button --}}
    <div class="flex items-center justify-center gap-4 w-full" @click.self="closeLightbox()">

        {{-- Prev button --}}
        <button
            x-show="lightboxImages.length > 1"
            @click="prevImage()"
            type="button"
            class="flex-shrink-0 p-2 rounded-full bg-white/10 hover:bg-white/25 text-white transition-colors duration-200"
            aria-label="Previous"
        >
            <x-heroicon-o-chevron-left class="w-6 h-6" />
        </button>

        {{-- Image with close button pinned to its top-right corner --}}
        <div style="position:relative; display:inline-block; max-height:calc(100vh - 14rem);">
            <img
                :src="lightboxSrc"
                style="display:block; max-width:80vw; max-height:calc(100vh - 14rem); width:auto; height:auto;"
                class="object-contain rounded-lg shadow-2xl select-none"
                alt="Full size preview"
                draggable="false"
            >
            <button
                @click="closeLightbox()"
                type="button"
                style="position:absolute; top:-14px; right:-14px; z-index:10000;"
                class="flex items-center justify-center w-9 h-9 rounded-full bg-white/20 border border-white/30 hover:bg-white/40 text-white shadow-lg"
                aria-label="Close"
            >
                <x-heroicon-o-x-mark class="w-5 h-5" />
            </button>
        </div>

        {{-- Next button --}}
        <button
            x-show="lightboxImages.length > 1"
            @click="nextImage()"
            type="button"
            class="flex-shrink-0 p-2 rounded-full bg-white/10 hover:bg-white/25 text-white transition-colors duration-200"
            aria-label="Next"
        >
            <x-heroicon-o-chevron-right class="w-6 h-6" />
        </button>

    </div>

    {{-- Thumbnail strip — only shown when there are multiple images --}}
    <div
        x-show="lightboxImages.length > 1"
        x-ref="thumbStrip"
        class="flex gap-2 px-2"
        style="overflow-x:auto; overflow-y:hidden; max-width:90vw; padding-bottom:4px;"
    >
        <template x-for="(thumb, i) in lightboxThumbs" :key="i">
            <button
                type="button"
                @click="goTo(i)"
                :class="i === lightboxIndex
                    ? 'ring-2 ring-white scale-110 opacity-100'
                    : 'opacity-50 hover:opacity-80'"
                class="flex-shrink-0 w-16 h-16 rounded-lg overflow-hidden transition-all duration-200 focus:outline-none"
                :aria-label="`Go to image ${i + 1}`"
            >
                <img
                    :src="thumb"
                    class="w-full h-full object-cover"
                    draggable="false"
                    :alt="`Thumbnail ${i + 1}`"
                >
            </button>
        </template>
    </div>

</div>
