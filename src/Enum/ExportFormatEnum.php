<?php

namespace Drenso\PdfToImage\Enum;

use GdImage;

enum ExportFormatEnum
{
    case JPG;
    case PNG;
    case JPEG;

    public function getExtension(): string
    {
        return match ($this) {
            self::JPG => '.jpg',
            self::PNG => '.png',
            self::JPEG => '.jpeg',
        };
    }

    public static function fromFileName(string $fileName): ExportFormatEnum
    {
        return match (pathinfo($fileName, PATHINFO_EXTENSION)) {
            'png' => ExportFormatEnum::PNG,
            'jpeg' => ExportFormatEnum::JPEG,
            default => ExportFormatEnum::JPG,
        };
    }

    public function export(GdImage $image, string $fileName, int $quality = -1): bool
    {
        return match ($this) {
            self::JPG, self::JPEG => imagejpeg($image, $fileName, $quality),
            self::PNG => imagepng($image, $fileName, $quality),
        };
    }
}
