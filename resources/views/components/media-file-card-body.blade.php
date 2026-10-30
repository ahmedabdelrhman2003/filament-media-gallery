{{-- Body of a PDF / document card. Expects $item from media-viewer.blade.php.        --}}
{{-- With a generated thumbnail: first-page preview + small type badge in the corner.  --}}
{{-- Without one: centred type icon + extension badge.                                 --}}
@if ($item['thumbUrl'])
    <img
        src="{{ $item['thumbUrl'] }}"
        alt="{{ $item['media']->name }}"
        loading="lazy"
        class="w-full h-full object-cover object-top transition-transform duration-200 group-hover:scale-105"
    >
    <div class="absolute inset-0 bg-black/0 group-hover:bg-black/15 transition-colors duration-200"></div>
    <span class="absolute bottom-2 left-2 flex items-center gap-1 text-[9px] font-bold uppercase tracking-wider text-gray-900 dark:text-white bg-white/90 dark:bg-gray-900/90 px-1.5 py-0.5 rounded-full leading-none shadow">
        <x-dynamic-component :component="$item['icon']" class="w-3 h-3 {{ $item['iconColor'] }}" />
        {{ $item['label'] }}
    </span>
@else
    <x-dynamic-component :component="$item['icon']" class="w-7 h-7 {{ $item['iconColor'] }} transition-transform duration-200 group-hover:scale-110 flex-shrink-0" />
    <span class="text-[9px] font-bold uppercase tracking-wider text-gray-900 dark:text-white {{ $item['badgeBg'] }} px-1.5 py-0.5 rounded-full leading-none">
        {{ $item['label'] }}
    </span>
@endif
