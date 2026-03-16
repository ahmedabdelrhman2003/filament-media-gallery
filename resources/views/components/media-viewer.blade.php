@props(['media' => collect(), 'size' => null, 'rounded' => false])

@php
    // ─── Card styling ────────────────────────────────────────────────────────
    $cardStyle    = $size ? "width:{$size}px; height:{$size}px;" : null;
    $cardClass    = $size ? '' : 'aspect-square';
    $roundedClass = $rounded ? 'rounded-full' : 'rounded-lg';

    // ─── Pre-compute per-item data ────────────────────────────────────────────
    // $imageUrls  — ordered list of full-size image URLs fed to the lightbox
    // $thumbUrls  — ordered list of thumbnail URLs fed to the lightbox strip
    // $imageIndex — counter that assigns each image its lightbox position
    $imageUrls  = [];
    $thumbUrls  = [];
    $imageIndex = 0;

    $items = $media->map(function ($mediaItem) use (&$imageUrls, &$thumbUrls, &$imageIndex) {
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

            $thumbUrls[] = $thumbUrl;
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
        lightboxThumbs: [],
        lightboxIndex: 0,
        openLightbox(images, thumbs, index) {
            this.lightboxImages = images;
            this.lightboxThumbs = thumbs;
            this.lightboxIndex  = index;
            this.lightboxSrc    = images[index];
            this.lightboxOpen   = true;
            this.$nextTick(() => this.scrollThumb(index));
        },
        closeLightbox() {
            this.lightboxOpen = false;
        },
        goTo(index) {
            this.lightboxIndex = index;
            this.lightboxSrc   = this.lightboxImages[index];
            this.scrollThumb(index);
        },
        nextImage() {
            this.goTo((this.lightboxIndex + 1) % this.lightboxImages.length);
        },
        prevImage() {
            this.goTo((this.lightboxIndex - 1 + this.lightboxImages.length) % this.lightboxImages.length);
        },
        scrollThumb(index) {
            const strip = this.$refs.thumbStrip;
            if (!strip) return;
            const thumb = strip.children[index];
            if (thumb) thumb.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
        }
    }"
    @keydown.escape.window="closeLightbox()"
    @keydown.arrow-right.window="if (lightboxOpen) nextImage()"
    @keydown.arrow-left.window="if (lightboxOpen) prevImage()"
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
                        @click="openLightbox({{ json_encode($imageUrls) }}, {{ json_encode($thumbUrls) }}, {{ $item['lightboxIndex'] }})"
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

    @include('media-gallery::components.media-lightbox')
</div>
