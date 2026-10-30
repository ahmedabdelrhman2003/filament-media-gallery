{{-- ─── Lightbox overlay ─────────────────────────────────────────────── --}}
{{-- Rendered via @include inside media-viewer.blade.php.               --}}
{{-- Alpine state (lightboxOpen, lightboxItems, lightboxIndex, current) --}}
{{-- Items: {type: image|video|pdf, src, thumb, name?}                   --}}
{{-- is inherited from the parent scope.                                --}}
<div
    x-show="lightboxOpen"
    x-cloak
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 flex flex-col items-center justify-center gap-4 p-6"
    style="display:none; background-color:rgba(0,0,0,0.9); z-index:9999;"
    role="dialog"
    aria-modal="true"
    {{-- Close only when the pointer went DOWN and UP on the backdrop itself, so a drag
         that starts on the video seek bar and ends on the backdrop does not close it. --}}
    @pointerdown="backdropDown = ($event.target === $el || $event.target.dataset.backdrop !== undefined)"
    @click="if (backdropDown && ($event.target === $el || $event.target.dataset.backdrop !== undefined)) closeLightbox(); backdropDown = false"
>
    {{-- Main row: prev button + media + next button --}}
    <div class="flex items-center justify-center gap-4 w-full" data-backdrop>

        {{-- Prev button --}}
        <button
            x-show="lightboxItems.length > 1"
            @click="prevImage()"
            type="button"
            class="flex-shrink-0 p-2 rounded-full bg-white/10 hover:bg-white/25 text-white transition-colors duration-200"
            aria-label="Previous"
        >
            <x-heroicon-o-chevron-left class="w-6 h-6" />
        </button>

        {{-- Media with close button pinned to its top-right corner --}}
        <div style="position:relative; display:inline-block; max-height:calc(100vh - 14rem);">

            {{-- Image --}}
            <template x-if="current && current.type === 'image'">
                <img
                    :src="current.src"
                    style="display:block; max-width:80vw; max-height:calc(100vh - 14rem); width:auto; height:auto;"
                    class="object-contain rounded-lg shadow-2xl select-none"
                    alt="Full size preview"
                    draggable="false"
                >
            </template>

            {{-- Video (x-if so the element is destroyed, and playback stops, on navigate/close) --}}
            <template x-if="lightboxOpen && current && current.type === 'video'">
                <div
                    x-data="{
                        paused: true,
                        wasPaused: true,
                        started: false,     {{-- becomes true after the initial autoplay --}}
                        flashPause: false,  {{-- brief ⏸ icon shown when resuming after a pause --}}
                        flashTimer: null,
                        onPlay() {
                            this.paused = false;
                            if (this.started) {
                                this.flashPause = true;
                                clearTimeout(this.flashTimer);
                                this.flashTimer = setTimeout(() => this.flashPause = false, 600);
                            }
                            this.started = true;
                        },
                    }"
                    style="position:relative; display:inline-block;"
                >
                    <video
                        :src="current.src"
                        controls
                        autoplay
                        playsinline
                        style="display:block; max-width:80vw; max-height:calc(100vh - 14rem); width:auto; height:auto; min-width:min(80vw, 40rem);"
                        class="rounded-lg shadow-2xl bg-black"
                        @play="onPlay()"
                        @pause="paused = true; flashPause = false"
                        {{-- Click on the picture toggles play/pause. Chrome toggles natively AFTER our
                             listener runs, Firefox does not toggle at all, so: remember the state at
                             pointerdown, let the browser do its thing, then enforce the opposite state. --}}
                        @pointerdown="wasPaused = $el.paused"
                        @click="
                            if ($event.clientY > $el.getBoundingClientRect().bottom - 56) return;
                            const el = $el, target = !wasPaused;
                            setTimeout(() => { if (target && !el.paused) el.pause(); if (!target && el.paused) el.play(); }, 0);
                        "
                    ></video>
                    {{-- Brief pause icon when playback resumes, fades out (does not intercept clicks) --}}
                    <div
                        x-show="flashPause"
                        x-transition:leave="transition ease-out duration-500"
                        x-transition:leave-start="opacity-100 scale-100"
                        x-transition:leave-end="opacity-0 scale-125"
                        class="absolute inset-0 flex items-center justify-center pointer-events-none"
                        style="padding-bottom:56px;"
                    >
                        <span class="flex items-center justify-center w-24 h-24 rounded-full bg-black/50 shadow-2xl">
                            <x-heroicon-s-pause class="w-12 h-12 text-white" />
                        </span>
                    </div>

                    {{-- Big play icon while paused (does not intercept clicks) --}}
                    <div
                        x-show="paused"
                        x-transition.opacity.duration.150ms
                        class="absolute inset-0 flex items-center justify-center pointer-events-none"
                        style="padding-bottom:56px;"
                    >
                        <span class="flex items-center justify-center w-24 h-24 rounded-full bg-black/50 shadow-2xl">
                            <x-heroicon-s-play class="w-12 h-12 text-white translate-x-0.5" />
                        </span>
                    </div>
                </div>
            </template>

            {{-- PDF — browser-native viewer in an iframe, scrollable in place --}}
            <template x-if="lightboxOpen && current && current.type === 'pdf'">
                <iframe
                    :src="current.src + '#toolbar=0&navpanes=0'"
                    :title="current.name"
                    style="display:block; width:80vw; height:calc(100vh - 14rem); border:0;"
                    class="rounded-lg shadow-2xl bg-white"
                ></iframe>
            </template>

            {{-- Open-in-new-tab (PDF only) --}}
            <template x-if="current && current.type === 'pdf'">
                <a
                    :href="current.src"
                    target="_blank"
                    rel="noopener noreferrer"
                    style="position:absolute; top:-14px; right:30px; z-index:10000;"
                    class="flex items-center justify-center w-9 h-9 rounded-full bg-white/20 border border-white/30 hover:bg-white/40 text-white shadow-lg"
                    aria-label="Open in new tab"
                    title="Open in new tab"
                >
                    <x-heroicon-o-arrow-top-right-on-square class="w-5 h-5" />
                </a>
            </template>

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
            x-show="lightboxItems.length > 1"
            @click="nextImage()"
            type="button"
            class="flex-shrink-0 p-2 rounded-full bg-white/10 hover:bg-white/25 text-white transition-colors duration-200"
            aria-label="Next"
        >
            <x-heroicon-o-chevron-right class="w-6 h-6" />
        </button>

    </div>

    {{-- Thumbnail strip — only shown when there are multiple items --}}
    <div
        x-show="lightboxItems.length > 1"
        x-ref="thumbStrip"
        class="flex gap-2 px-2"
        style="overflow-x:auto; overflow-y:hidden; max-width:90vw; padding-bottom:4px;"
    >
        <template x-for="(item, i) in lightboxItems" :key="i">
            <button
                type="button"
                data-thumb
                @click="goTo(i)"
                :class="i === lightboxIndex
                    ? 'ring-2 ring-white scale-110 opacity-100'
                    : 'opacity-50 hover:opacity-80'"
                class="flex-shrink-0 w-16 h-16 rounded-lg overflow-hidden transition-all duration-200 focus:outline-none bg-gray-800 flex items-center justify-center"
                :aria-label="`Go to item ${i + 1}`"
            >
                <template x-if="item.thumb">
                    <img
                        :src="item.thumb"
                        class="w-full h-full object-cover"
                        draggable="false"
                        :alt="`Thumbnail ${i + 1}`"
                    >
                </template>
                <template x-if="!item.thumb && item.type === 'video'">
                    <x-heroicon-s-play class="w-7 h-7 text-white" />
                </template>
                <template x-if="!item.thumb && item.type === 'pdf'">
                    <x-heroicon-o-document class="w-7 h-7 text-red-400" />
                </template>
            </button>
        </template>
    </div>

</div>
