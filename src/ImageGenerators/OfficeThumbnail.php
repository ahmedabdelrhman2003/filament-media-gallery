<?php

namespace AhmedAbdelrhman\FilamentMediaGallery\ImageGenerators;

use Illuminate\Support\Collection;
use Spatie\MediaLibrary\Conversions\Conversion;
use Symfony\Component\Process\Process;

/**
 * Renders the first page of Word / Excel / PowerPoint / OpenDocument files to a JPEG
 * so Spatie conversions (e.g. `thumbnail`) can be generated for Office documents.
 *
 * Requires LibreOffice (`soffice` or `libreoffice` binary) to convert the document to PDF,
 * then renders page 1 via PdfThumbnail (pdftoppm or Ghostscript).
 * Opt in by adding this class to `image_generators` in config/media-library.php.
 */
class OfficeThumbnail extends PdfThumbnail
{
    public function convert(string $file, ?Conversion $conversion = null): ?string
    {
        $soffice = $this->binary('soffice') ?? $this->binary('libreoffice');

        if ($soffice === null) {
            return null;
        }

        $dir = pathinfo($file, PATHINFO_DIRNAME);
        $pdf = $dir . '/' . pathinfo($file, PATHINFO_FILENAME) . '.pdf';

        // Isolated profile dir avoids lock-ups when another LibreOffice instance is running.
        $profile = sys_get_temp_dir() . '/media-gallery-soffice-' . getmypid();

        $process = new Process([
            $soffice, '--headless', '--norestore', "-env:UserInstallation=file://{$profile}",
            '--convert-to', 'pdf', '--outdir', $dir, $file,
        ]);
        $process->setTimeout(120);
        $process->run();

        if (! file_exists($pdf)) {
            return null;
        }

        $image = parent::convert($pdf, $conversion);

        @unlink($pdf);

        return $image;
    }

    public function requirementsAreInstalled(): bool
    {
        return ($this->binary('soffice') !== null || $this->binary('libreoffice') !== null)
            && parent::requirementsAreInstalled();
    }

    public function supportedExtensions(): Collection
    {
        return collect(['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'odt', 'ods', 'odp', 'rtf']);
    }

    public function supportedMimeTypes(): Collection
    {
        return collect([
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'application/vnd.oasis.opendocument.text',
            'application/vnd.oasis.opendocument.spreadsheet',
            'application/vnd.oasis.opendocument.presentation',
            'application/rtf',
        ]);
    }
}
