<?php

namespace AhmedAbdelrhman\FilamentMediaGallery\Infolists\Components;

use Filament\Infolists\Components\Entry;
use Illuminate\Support\Collection;

/**
 * MediaGalleryEntry — Custom Filament Infolist Entry
 *
 * Renders a Spatie Media Library collection as an interactive media grid inside
 * a Filament infolist. Automatically detects media type:
 *   - Images  → thumbnail grid with click-to-open Alpine.js lightbox
 *   - PDFs    → icon card that opens the file in a new browser tab
 *
 * Built on top of Filament's Entry base class. Renders via the Blade view
 * `media-gallery::infolist-entries.media-gallery`, which delegates to the
 * reusable `x-media-gallery-viewer` component.
 *
 * Dependencies:
 *   - Filament 3, 4, or 5
 *   - Spatie Media Library (any model using HasMedia / InteractsWithMedia)
 *   - Alpine.js (already bundled with Filament)
 *   - Tailwind CSS (already bundled with Filament)
 */
class MediaGalleryEntry extends Entry
{
    protected string $view = 'media-gallery::infolist-entries.media-gallery';

    protected string $collection = '';

    protected ?string $relation = null;

    protected ?int $size = 250;

    protected bool $rounded = false;

    public function collection(string $collection): static
    {
        $this->collection = $collection;

        return $this;
    }

    public function getCollection(): string
    {
        return $this->collection;
    }

    public function fromRelation(string $relation): static
    {
        $this->relation = $relation;

        return $this;
    }

    public function size(int $pixels): static
    {
        $this->size = $pixels;

        return $this;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function rounded(bool $condition = true): static
    {
        $this->rounded = $condition;

        return $this;
    }

    public function getRounded(): bool
    {
        return $this->rounded;
    }

    /**
     * Load and return the Spatie media items for the configured collection.
     *
     * @return \Illuminate\Support\Collection<int, \Spatie\MediaLibrary\MediaCollections\Models\Media>
     */
    public function getMediaItems(): Collection
    {
        $record = $this->getRecord();

        if ($record === null) {
            return collect();
        }

        $mediaOwner = $this->relation
            ? $record->{$this->relation}
            : $record;

        if ($mediaOwner === null) {
            return collect();
        }

        return $mediaOwner->getMedia($this->collection);
    }
}
