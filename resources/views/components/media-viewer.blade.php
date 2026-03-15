@props(['media' => collect(), 'size' => null, 'rounded' => false])

@php
    // ─── Card styling ────────────────────────────────────────────────────────
    $cardStyle    = $size ? "width:{$size}px; height:{$size}px;" : null;
    $cardClass    = $size ? '' : 'aspect-square';
    $roundedClass = $rounded ? 'rounded-full' : 'rounded-lg';

    // ─── Pre-compute per-item data ────────────────────────────────────────────
    // $imageUrls  — ordered list of full-size image URLs fed to the lightbox
    // $imageIndex — counter that assigns each image its lightbox position
    $imageUrls  = [];
    $imageIndex = 0;

    $items = $media->map(function ($mediaItem) use (&$imageUrls, &$imageIndex) {
        $isImage = str_starts_with($mediaItem->mime_type, 'image/');

        $lightboxIndex = null;
        $thumbUrl      = null;

        if ($isImage) {
            // Reserve a lightbox slot for this image
            $lightboxIndex = $imageIndex++;
            $imageUrls[]   = $mediaItem->getUrl();

            // Pick the best available thumbnail conversion
            // To add a new conversion (e.g. 'medium'), insert another match arm here
            $thumbUrl = match (true) {
                $mediaItem->hasGeneratedConversion('thumbnail') => $mediaItem->getUrl('thumbnail'),
                $mediaItem->hasGeneratedConversion('preview')   => $mediaItem->getUrl('preview'),
                default                                          => $mediaItem->getUrl(),
            };
        }

        return [
            'media'         => $mediaItem,
            'isImage'       => $isImage,
            'thumbUrl'      => $thumbUrl,            // null for non-images
            'lightboxIndex' => $lightboxIndex,       // null for non-images
            'extension'     => strtoupper(pathinfo($mediaItem->file_name, PATHINFO_EXTENSION)),
        ];
    });
@endphp

<div
    x-data="{
        lightboxOpen: false,
        lightboxSrc: '',
        lightboxImages: [],
        lightboxIndex: 0,
        openLightbox(images, index) {
            this.lightboxImages = images;
            this.lightboxIndex  = index;
            this.lightboxSrc    = images[index];
            this.lightboxOpen   = true;
        },
        closeLightbox() {
            this.lightboxOpen = false;
        },
        nextImage() {
            this.lightboxIndex = (this.lightboxIndex + 1) % this.lightboxImages.length;
            this.lightboxSrc   = this.lightboxImages[this.lightboxIndex];
        },
        prevImage() {
            this.lightboxIndex = (this.lightboxIndex - 1 + this.lightboxImages.length) % this.lightboxImages.length;
            this.lightboxSrc   = this.lightboxImages[this.lightboxIndex];
        }
    }"
    @keydown.escape.window="closeLightbox()"
>
    {{-- ─── Empty state ──────────────────────────────────────────────────── --}}
    @if ($media->isEmpty())
        <p class="text-sm text-gray-400 dark:text-gray-500 italic">No files uploaded.</p>

    {{-- ─── Media grid ───────────────────────────────────────────────────── --}}
    @else
        <div class="flex flex-wrap gap-3">
            @foreach ($items as $item)

                {{-- Image card — opens lightbox on click --}}
                @if ($item['isImage'])
                    <div
                        class="relative group cursor-pointer {{ $roundedClass }} overflow-hidden border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 {{ $cardClass }}"
                        @if ($cardStyle) style="{{ $cardStyle }}" @endif
                        @click="openLightbox({{ json_encode($imageUrls) }}, {{ $item['lightboxIndex'] }})"
                    >
                        <img
                            src="{{ $item['thumbUrl'] }}"
                            alt="{{ $item['media']->name }}"
                            class="w-full h-full object-cover transition-transform duration-200 group-hover:scale-105"
                        >
                        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/25 transition-colors duration-200 flex items-center justify-center">
                            <x-heroicon-o-magnifying-glass-plus class="w-8 h-8 text-white opacity-0 group-hover:opacity-100 transition-opacity duration-200 drop-shadow-lg" />
                        </div>
                    </div>

                {{-- File card — opens file in a new tab --}}
                @else
                    <a
                        href="{{ $item['media']->getUrl() }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="group flex flex-col items-center justify-center gap-2 {{ $roundedClass }} border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-3 {{ $cardClass }} hover:border-primary-500 hover:bg-primary-50 dark:hover:bg-primary-950 transition-colors duration-200"
                        @if ($cardStyle) style="{{ $cardStyle }}" @endif
                    >
                        <x-heroicon-o-document class="w-7 h-7 text-red-500 group-hover:text-red-600 transition-colors duration-200 flex-shrink-0" />
                        <span class="text-[9px] font-bold uppercase tracking-wider text-red-500 bg-red-50 dark:bg-red-950 px-1.5 py-0.5 rounded-full leading-none">
                            {{ $item['extension'] }}
                        </span>
                    </a>
                @endif

            @endforeach
        </div>
    @endif

    {{-- ─── Lightbox overlay ─────────────────────────────────────────────── --}}
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
            <div style="position:relative; display:inline-block; max-height:calc(100vh - 8rem);">
                <img
                    :src="lightboxSrc"
                    style="display:block; max-width:80vw; max-height:calc(100vh - 8rem); width:auto; height:auto;"
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

        {{-- Counter badge — only shown when there are multiple images --}}
        <div
            x-show="lightboxImages.length > 1"
            class="text-white text-sm bg-black/50 px-3 py-1 rounded-full select-none"
            x-text="`${lightboxIndex + 1} / ${lightboxImages.length}`"
        ></div>
    </div>
</div>
