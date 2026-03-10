@props(['media' => collect(), 'size' => null, 'rounded' => false])

@php
    $imageUrls = [];
    $imageItems = [];

    foreach ($media as $item) {
        if (str_starts_with($item->mime_type, 'image/')) {
            $imageUrls[] = $item->getUrl();
            $imageItems[] = $item;
        }
    }

    $cardStyle = $size ? "width:{$size}px; height:{$size}px;" : null;
    $cardClass = $size ? '' : 'aspect-square';
    $roundedClass = $rounded ? 'rounded-full' : 'rounded-lg';
@endphp

<div
    x-data="{
        lightboxOpen: false,
        lightboxSrc: '',
        lightboxImages: [],
        lightboxIndex: 0,
        openLightbox(images, index) {
            this.lightboxImages = images;
            this.lightboxIndex = index;
            this.lightboxSrc = images[index];
            this.lightboxOpen = true;
        },
        closeLightbox() {
            this.lightboxOpen = false;
        },
        nextImage() {
            this.lightboxIndex = (this.lightboxIndex + 1) % this.lightboxImages.length;
            this.lightboxSrc = this.lightboxImages[this.lightboxIndex];
        },
        prevImage() {
            this.lightboxIndex = (this.lightboxIndex - 1 + this.lightboxImages.length) % this.lightboxImages.length;
            this.lightboxSrc = this.lightboxImages[this.lightboxIndex];
        }
    }"
    @keydown.escape.window="closeLightbox()"
>
    @if ($media->isEmpty())
        <p class="text-sm text-gray-400 dark:text-gray-500 italic">No files uploaded.</p>
    @else
        <div class="flex flex-wrap gap-3">
            @foreach ($media as $item)
                @if (str_starts_with($item->mime_type, 'image/'))
                    @php $imgIndex = array_search($item, $imageItems, true); @endphp
                    <div
                        class="relative group cursor-pointer {{ $roundedClass }} overflow-hidden border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 {{ $cardClass }}"
                        @if ($cardStyle) style="{{ $cardStyle }}" @endif
                        @click="openLightbox({{ json_encode($imageUrls) }}, {{ $imgIndex }})"
                    >
                        <img
                            src="{{ $item->hasGeneratedConversion('thumbnail') ? $item->getUrl('thumbnail') : ($item->hasGeneratedConversion('preview') ? $item->getUrl('preview') : $item->getUrl()) }}"
                            alt="{{ $item->name }}"
                            class="w-full h-full object-cover transition-transform duration-200 group-hover:scale-105"
                        >
                        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/25 transition-colors duration-200 flex items-center justify-center">
                            <x-heroicon-o-magnifying-glass-plus class="w-8 h-8 text-white opacity-0 group-hover:opacity-100 transition-opacity duration-200 drop-shadow-lg" />
                        </div>
                    </div>
                @else
                    {{-- PDF or other file --}}
                    <a
                        href="{{ $item->getUrl() }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="group flex flex-col items-center justify-center gap-2 {{ $roundedClass }} border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-3 {{ $cardClass }} hover:border-primary-500 hover:bg-primary-50 dark:hover:bg-primary-950 transition-colors duration-200"
                        @if ($cardStyle) style="{{ $cardStyle }}" @endif
                    >
                        <x-heroicon-o-document class="w-7 h-7 text-red-500 group-hover:text-red-600 transition-colors duration-200 flex-shrink-0" />
                        <span class="text-[9px] font-bold uppercase tracking-wider text-red-500 bg-red-50 dark:bg-red-950 px-1.5 py-0.5 rounded-full leading-none">
                            PDF
                        </span>
                    </a>
                @endif
            @endforeach
        </div>
    @endif

    {{-- Lightbox overlay --}}
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
        <div class="flex items-center justify-center gap-4 w-full max-h-[90vh]" @click.self="closeLightbox()">
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

            {{-- Image + close button anchored to its top-right corner --}}
            <div class="relative flex-1 min-w-0 flex items-center justify-center max-h-[90vh]">
                <img
                    :src="lightboxSrc"
                    class="max-w-full max-h-[90vh] w-auto h-auto object-contain rounded-lg shadow-2xl select-none"
                    alt="Full size preview"
                    draggable="false"
                >

                {{-- Close button pinned to the top-right corner of the image --}}
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

        {{-- Counter — sits below the image row, horizontally centered --}}
        <div
            x-show="lightboxImages.length > 1"
            class="text-white text-sm bg-black/50 px-3 py-1 rounded-full select-none"
            x-text="`${lightboxIndex + 1} / ${lightboxImages.length}`"
        ></div>
    </div>
</div>
