<?php

namespace AhmedAbdelrhman\FilamentMediaGallery;

use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class FilamentMediaGalleryServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'media-gallery');

        Blade::component('media-gallery::components.media-viewer', 'media-gallery-viewer');

        FilamentAsset::register([
            Css::make('media-gallery', __DIR__ . '/../resources/dist/media-gallery.css'),
        ], 'ahmed-abdelrhman/filament-media-gallery');

        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/media-gallery'),
        ], 'media-gallery-views');
    }
}
