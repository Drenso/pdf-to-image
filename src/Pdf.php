<?php

namespace Drenso\PdfToImage;

use Drenso\PdfToImage\Enum\ExportFormatEnum;
use Drenso\PdfToImage\Exceptions\PageDoesNotExist;
use Drenso\PdfToImage\Exceptions\PdfDoesNotExist;
use GdImage;
use Random\Engine\Secure;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;

class Pdf
{
    protected ?string $cacheDir = null;
    protected ?int $width = null;
    protected ?ExportFormatEnum $outputFormat = null;
    protected int $page = 1;
    protected int $compressionQuality = -1;
    private ?int $numberOfPages = null;
    private Filesystem $filesystem;

    public function __construct(private readonly string $pdfFile, protected readonly int $resolution = 144)
    {
        $this->filesystem = new Filesystem();
        if (! $this->filesystem->exists($pdfFile)) {
            throw new PdfDoesNotExist("File `{$pdfFile}` does not exist");
        }
    }

    public function setWidth(int $width): self
    {
        $this->width = $width;

        return $this;
    }

    public function setOutputFormat(ExportFormatEnum $outputFormat): self
    {
        $this->outputFormat = $outputFormat;

        return $this;
    }

    public function getOutputFormat(): ExportFormatEnum
    {
        return $this->outputFormat;
    }

    public function setPage(int $page): self
    {
        if ($page > $this->getNumberOfPages() || $page < 1) {
            throw new PageDoesNotExist("Page {$page} does not exist");
        }
        $this->page = $page;

        return $this;
    }

    public function getNumberOfPages(): int
    {
        if ($this->numberOfPages === null) {
            $this->prepareImages();
            $this->numberOfPages = (new Finder())->in($this->cacheDir)->name('*.png')->count();
        }

        return $this->numberOfPages;
    }

    public function saveImage(string $pathToImage): bool
    {
        if (is_dir($pathToImage)) {
            $pathToImage = rtrim($pathToImage, '\/').DIRECTORY_SEPARATOR.$this->page.$this->outputFormat->getExtension();
        }
        $imageData = $this->getImageData($pathToImage);

        return $this->outputFormat->export($imageData, $pathToImage, $this->compressionQuality);
    }

    public function saveAllPagesAsImages(string $directory, string $prefix = ''): array
    {
        $numberOfPages = $this->getNumberOfPages();

        if ($numberOfPages === 0) {
            return [];
        }

        return array_map(function ($pageNumber) use ($directory, $prefix) {
            $this->setPage($pageNumber);
            $destination = "{$directory}".DIRECTORY_SEPARATOR."{$prefix}{$pageNumber}.{$this->outputFormat}";
            $this->saveImage($destination);

            return $destination;
        }, range(1, $numberOfPages));
    }

    public function getImageData(string $pathToImage): GdImage
    {
        $this->prepareImages();
        $this->outputFormat ??= ExportFormatEnum::fromFileName($pathToImage);
        $pageName = $this->cacheDir . DIRECTORY_SEPARATOR . sprintf('%03d.png', $this->page);
        $originalImage = imagecreatefrompng($pageName);

        if ($this->width === null) {
            return $originalImage;
        }

        $imageSize = getimagesize($pageName);
        // Never grow the image
        if ($imageSize[0] < $this->width) {
            return $originalImage;
        }
        // Calculate scaled height
        $newHeight = round((float)$imageSize[1] * ($this->width / $imageSize[0]));
        // Resize in new image
        $resizedImage = imagecreatetruecolor($this->width, $newHeight);
        imagecopyresampled($resizedImage, $originalImage, 0, 0, 0, 0, $this->width, $newHeight, $imageSize[0], $imageSize[1]);

        return $resizedImage;
    }

    public function setCompressionQuality(int $compressionQuality): self
    {
        $this->compressionQuality = $compressionQuality;

        return $this;
    }

    public function prepareImages(): void
    {
        if ($this->cacheDir) {
            return;
        }

        // Convert to PNG files, so GD can be used for the following processing
        $this->cacheDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'pdftoimage' . DIRECTORY_SEPARATOR . bin2hex((new Secure())->generate());
        $this->filesystem->mkdir($this->cacheDir);
        (new Process([
            'gs',
            '-dSAFER',
            '-dBATCH',
            '-dNOPAUSE',
            '-sDEVICE=png16m',
            '-dTextAlphaBits=4',
            '-dGraphicsAlphaBits=4',
            '-r' . $this->resolution,
            '-sOutputFile=' . $this->cacheDir . DIRECTORY_SEPARATOR . '%03d.png',
            $this->pdfFile])
        )->mustRun();
    }
}
