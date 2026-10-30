@props(['media' => collect(), 'size' => null, 'rounded' => false])

@php
    // ─── Card styling ────────────────────────────────────────────────────────
    $cardStyle    = $size ? "width:{$size}px; height:{$size}px;" : null;
    $cardClass    = $size ? '' : 'w-full aspect-square';
    $gridClass    = $size ? 'flex flex-wrap gap-3' : 'grid grid-cols-[repeat(auto-fill,minmax(10rem,1fr))] gap-3';
    $roundedClass = $rounded ? 'rounded-full' : 'rounded-lg';

    // ─── File-type detection ─────────────────────────────────────────────────
    // Returns [kind, label, icon, text colour, badge bg] for a media item.
    // kind: image | video | pdf | word | excel | powerpoint | file
    $describe = function ($mediaItem): array {
        $mime = strtolower((string) $mediaItem->mime_type);
        $ext  = strtolower(pathinfo((string) $mediaItem->file_name, PATHINFO_EXTENSION));

        return match (true) {
            str_starts_with($mime, 'image/')
                => ['image', strtoupper($ext), null, null, null],

            str_starts_with($mime, 'video/')
                => ['video', strtoupper($ext), 'heroicon-o-play-circle', 'text-purple-500', 'bg-purple-50 dark:bg-purple-950'],

            $mime === 'application/pdf' || $ext === 'pdf'
                => ['pdf', 'PDF', 'heroicon-o-document', 'text-red-500', 'bg-red-50 dark:bg-red-950'],

            in_array($ext, ['doc', 'docx', 'odt', 'rtf'])
                || str_contains($mime, 'msword')
                || str_contains($mime, 'wordprocessingml')
                => ['word', strtoupper($ext), 'heroicon-o-document-text', 'text-blue-600', 'bg-blue-50 dark:bg-blue-950'],

            in_array($ext, ['xls', 'xlsx', 'ods', 'csv'])
                || str_contains($mime, 'ms-excel')
                || str_contains($mime, 'spreadsheetml')
                || $mime === 'text/csv'
                => ['excel', strtoupper($ext), 'heroicon-o-table-cells', 'text-green-600', 'bg-green-50 dark:bg-green-950'],

            in_array($ext, ['ppt', 'pptx', 'odp'])
                || str_contains($mime, 'ms-powerpoint')
                || str_contains($mime, 'presentationml')
                => ['powerpoint', strtoupper($ext), 'heroicon-o-presentation-chart-bar', 'text-indigo-500', 'bg-indigo-50 dark:bg-indigo-950'],

            default
                => ['file', strtoupper($ext) ?: 'FILE', 'heroicon-o-document', 'text-gray-500', 'bg-gray-100 dark:bg-gray-700'],
        };
    };

    // ─── Pre-compute per-item data ────────────────────────────────────────────
    // $lightboxItems — ordered list of {type, src, thumb} fed to the lightbox
    //                  (images, videos and PDFs share one lightbox sequence)
    $lightboxItems = [];

    $items = $media->map(function ($mediaItem) use (&$lightboxItems, $describe) {
        [$kind, $label, $icon, $iconColor, $badgeBg] = $describe($mediaItem);

        $lightboxIndex = null;

        // Pick the best available thumbnail conversion. For images we fall back to the
        // original; for every other type a thumbnail only exists if the host app has an
        // image generator for it (see README "File thumbnails") — otherwise an icon is shown.
        // To add a new conversion (e.g. 'medium'), insert another match arm here.
        $thumbUrl = match (true) {
            $mediaItem->hasGeneratedConversion('thumbnail') => $mediaItem->getUrl('thumbnail'),
            $mediaItem->hasGeneratedConversion('preview')   => $mediaItem->getUrl('preview'),
            $kind === 'image'                                => $mediaItem->getUrl(),
            default                                          => null,
        };

        if ($kind === 'image') {
            $lightboxIndex   = count($lightboxItems);
            $lightboxItems[] = ['type' => 'image', 'src' => $mediaItem->getUrl(), 'thumb' => $thumbUrl];
        }

        if ($kind === 'video') {
            $lightboxIndex   = count($lightboxItems);
            $lightboxItems[] = ['type' => 'video', 'src' => $mediaItem->getUrl(), 'thumb' => $thumbUrl, 'mime' => (string) $mediaItem->mime_type];
        }

        if ($kind === 'pdf') {
            // Previewed inline via the browser's native PDF viewer (<iframe>), scrollable like the image view.
            $lightboxIndex   = count($lightboxItems);
            $lightboxItems[] = ['type' => 'pdf', 'src' => $mediaItem->getUrl(), 'thumb' => $thumbUrl, 'name' => (string) $mediaItem->file_name];
        }

        return [
            'media'         => $mediaItem,
            'kind'          => $kind,
            'label'         => $label,
            'icon'          => $icon,
            'iconColor'     => $iconColor,
            'badgeBg'       => $badgeBg,
            'thumbUrl'      => $thumbUrl,
            'lightboxIndex' => $lightboxIndex,
        ];
    });
@endphp

<div
    x-data="{
        lightboxOpen: false,
        backdropDown: false,
        lightboxItems: @js($lightboxItems),
        lightboxIndex: 0,
        get current() { return this.lightboxItems[this.lightboxIndex] ?? null; },
        openLightbox(index) {
            this.lightboxIndex = index;
            this.lightboxOpen  = true;
            document.body.style.overflow = 'hidden';
            this.$nextTick(() => this.scrollThumb(index));
        },
        closeLightbox() {
            this.lightboxOpen = false;
            document.body.style.overflow = '';
        },
        goTo(index) {
            this.lightboxIndex = index;
            this.scrollThumb(index);
        },
        nextImage() {
            this.goTo((this.lightboxIndex + 1) % this.lightboxItems.length);
        },
        prevImage() {
            this.goTo((this.lightboxIndex - 1 + this.lightboxItems.length) % this.lightboxItems.length);
        },
        scrollThumb(index) {
            const strip = this.$refs.thumbStrip;
            if (!strip) return;
            const thumb = strip.querySelectorAll('[data-thumb]')[index];
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
        <div class="{{ $gridClass }}">
            @foreach ($items as $item)

                {{-- Image card — opens lightbox on click --}}
                @if ($item['kind'] === 'image')
                    <div
                        class="relative group cursor-pointer {{ $roundedClass }} overflow-hidden border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 {{ $cardClass }}"
                        @if ($cardStyle) style="{{ $cardStyle }}" @endif
                        @click="openLightbox({{ $item['lightboxIndex'] }})"
                    >
                        <img
                            src="{{ $item['thumbUrl'] }}"
                            alt="{{ $item['media']->name }}"
                            loading="lazy"
                            class="w-full h-full object-cover transition-transform duration-200 group-hover:scale-105"
                        >
                        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/25 transition-colors duration-200 flex items-center justify-center">
                            <x-heroicon-o-magnifying-glass-plus class="w-8 h-8 text-white opacity-0 group-hover:opacity-100 transition-opacity duration-200 drop-shadow-lg" />
                        </div>
                    </div>

                {{-- Video card — muted preview, opens lightbox player on click --}}
                @elseif ($item['kind'] === 'video')
                    <div
                        class="relative group cursor-pointer {{ $roundedClass }} overflow-hidden border border-gray-200 dark:border-gray-700 bg-gray-900 {{ $cardClass }}"
                        @if ($cardStyle) style="{{ $cardStyle }}" @endif
                        @click="openLightbox({{ $item['lightboxIndex'] }})"
                    >
                        @if ($item['thumbUrl'])
                            <img src="{{ $item['thumbUrl'] }}" alt="{{ $item['media']->name }}" loading="lazy" class="w-full h-full object-cover">
                        @else
                            <video
                                src="{{ $item['media']->getUrl() }}#t=0.1"
                                preload="metadata"
                                muted
                                playsinline
                                class="w-full h-full object-cover pointer-events-none"
                            ></video>
                        @endif
                        <div class="absolute inset-0 bg-black/30 group-hover:bg-black/50 transition-colors duration-200 flex items-center justify-center">
                            <x-heroicon-s-play class="w-10 h-10 text-white drop-shadow-lg transition-transform duration-200 group-hover:scale-110" />
                        </div>
                        <span class="absolute bottom-2 right-2 text-[9px] font-bold uppercase tracking-wider text-white bg-black/60 px-1.5 py-0.5 rounded-full leading-none">
                            {{ $item['label'] }}
                        </span>
                    </div>

                {{-- PDF card — opens inline preview in the lightbox --}}
                @elseif ($item['kind'] === 'pdf')
                    <div
                        role="button"
                        tabindex="0"
                        title="{{ $item['media']->file_name }}"
                        class="relative group cursor-pointer flex flex-col items-center justify-center gap-2 {{ $roundedClass }} overflow-hidden border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 {{ $item['thumbUrl'] ? '' : 'p-3' }} {{ $cardClass }} hover:border-primary-500 hover:bg-primary-50 dark:hover:bg-primary-950 transition-colors duration-200"
                        @if ($cardStyle) style="{{ $cardStyle }}" @endif
                        @click="openLightbox({{ $item['lightboxIndex'] }})"
                        @keydown.enter.prevent="openLightbox({{ $item['lightboxIndex'] }})"
                    >
                        @include('media-gallery::components.media-file-card-body', ['item' => $item])
                    </div>

                {{-- Document card — opens file in a new tab --}}
                @else
                    <a
                        href="{{ $item['media']->getUrl() }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        title="{{ $item['media']->file_name }}"
                        class="relative group flex flex-col items-center justify-center gap-2 {{ $roundedClass }} overflow-hidden border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 {{ $item['thumbUrl'] ? '' : 'p-3' }} {{ $cardClass }} hover:border-primary-500 hover:bg-primary-50 dark:hover:bg-primary-950 transition-colors duration-200"
                        @if ($cardStyle) style="{{ $cardStyle }}" @endif
                    >
                        @include('media-gallery::components.media-file-card-body', ['item' => $item])
                    </a>
                @endif

            @endforeach
        </div>
    @endif

    @include('media-gallery::components.media-lightbox')
</div>
