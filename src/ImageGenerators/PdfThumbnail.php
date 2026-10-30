<?php

namespace AhmedAbdelrhman\FilamentMediaGallery\ImageGenerators;

use Illuminate\Support\Collection;
use Spatie\MediaLibrary\Conversions\Conversion;
use Spatie\MediaLibrary\Conversions\ImageGenerators\ImageGenerator;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * Renders the first page of a PDF to a JPEG so Spatie conversions (e.g. `thumbnail`)
 * can be generated for PDF files — WITHOUT the Imagick extension.
 *
 * Uses the `pdftoppm` binary (poppler-utils) when available, otherwise Ghostscript (`gs`).
 * Opt in by adding this class to `image_generators` in config/media-library.php,
 * BEFORE Spatie's own `Pdf` generator (which requires Imagick + spatie/pdf-to-image).
 */
class PdfThumbnail extends ImageGenerator
{
    public const DPI = 72;

    public function convert(string $file, ?Conversion $conversion = null): ?string
    {
        $page   = $conversion ? max(1, $conversion->getPdfPageNumber()) : 1;
        $target = pathinfo($file, PATHINFO_DIRNAME) . '/' . pathinfo($file, PATHINFO_FILENAME);

        if ($bin = $this->binary('pdftoppm')) {
            // -singlefile writes exactly "$target.jpg"
            $this->run([$bin, '-jpeg', '-r', (string) static::DPI, '-f', (string) $page, '-l', (string) $page, '-singlefile', $file, $target]);

            return file_exists("{$target}.jpg") ? "{$target}.jpg" : null;
        }

        if ($bin = $this->binary('gs')) {
            $this->run([
                $bin, '-q', '-dNOPAUSE', '-dBATCH', '-dSAFER', '-sDEVICE=jpeg', '-dJPEGQ=85',
                '-r' . static::DPI, "-dFirstPage={$page}", "-dLastPage={$page}",
                "-sOutputFile={$target}.jpg", $file,
            ]);

            return file_exists("{$target}.jpg") ? "{$target}.jpg" : null;
        }

        return null;
    }

    public function requirementsAreInstalled(): bool
    {
        return $this->binary('pdftoppm') !== null || $this->binary('gs') !== null;
    }

    public function supportedExtensions(): Collection
    {
        return collect(['pdf']);
    }

    public function supportedMimeTypes(): Collection
    {
        return collect(['application/pdf']);
    }

    protected function binary(string $name): ?string
    {
        static $cache = [];

        return $cache[$name] ??= (new ExecutableFinder)->find($name);
    }

    protected function run(array $command): void
    {
        $process = new Process($command);
        $process->setTimeout(60);
        $process->run();
    }
}
