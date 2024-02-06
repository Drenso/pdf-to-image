<?php

namespace Drenso\PdfToImage;

use Drenso\PdfToImage\Enum\ExportFormatEnum;
use Drenso\PdfToImage\Exceptions\PageDoesNotExist;
use Drenso\PdfToImage\Exceptions\PdfDoesNotExist;
use GdImage;
use Random\Engine\Secure;

class Pdf
{
    protected string $cacheDir;
    protected ?int $width = null;
    protected ?ExportFormatEnum $outputFormat = null;
    protected int $page = 1;
    protected int $compressionQuality = -1;
    private ?int $numberOfPages = null;

    public function __construct(private readonly string $pdfFile, protected readonly int $resolution = 144)
    {
        if (! file_exists($pdfFile)) {
            throw new PdfDoesNotExist("File `{$pdfFile}` does not exist");
        }

        // Convert to PNG files, so GD can be used for the following processing
        $this->cacheDir = '/tmp/pdftoimage/' . bin2hex((new Secure())->generate());
        @mkdir($this->cacheDir, recursive: true);
        exec(sprintf('gs -dSAFER -dBATCH -sDEVICE=png16m -dTextAlphaBits=4 -dGraphicsAlphaBits=4 -r%s -sOutputFile=%s %s', $this->resolution, $this->cacheDir . '/%03d.png', $this->pdfFile));
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
            $files = scandir($this->cacheDir);
            $this->numberOfPages = count(array_filter($files, fn (string $filename) => str_ends_with($filename, '.png')));
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
            $destination = "{$directory}/{$prefix}{$pageNumber}.{$this->outputFormat}";
            $this->saveImage($destination);

            return $destination;
        }, range(1, $numberOfPages));
    }

    public function getImageData(string $pathToImage): GdImage
    {
        $this->outputFormat ??= ExportFormatEnum::fromFileName($pathToImage);
        $pageName = $this->cacheDir . sprintf('/%03d.png', $this->page);
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
        $newHeight = $imageSize[1] * ($this->width / $imageSize[0]);
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
}
