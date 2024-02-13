# Convert a pdf to an image

[![Latest Version on Packagist](https://img.shields.io/packagist/v/spatie/pdf-to-image.svg?style=flat-square)](https://packagist.org/packages/spatie/pdf-to-image)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](.github/LICENSE.md)
[![Quality Score](https://img.shields.io/scrutinizer/g/spatie/pdf-to-image.svg?style=flat-square)](https://scrutinizer-ci.com/g/spatie/pdf-to-image)
[![StyleCI](https://styleci.io/repos/38419604/shield?branch=master)](https://styleci.io/repos/38419604)
[![Total Downloads](https://img.shields.io/packagist/dt/spatie/pdf-to-image.svg?style=flat-square)](https://packagist.org/packages/spatie/pdf-to-image)

This package provides an easy to work with class to convert PDF's to images. It is based on the [`spatie/pdf-to-image`](https://github.com/spatie/pdf-to-image) library, which relied on Imagick for the conversion. This library relies on GD instead.

## Requirements

You should have [Ghostscript](http://www.ghostscript.com/) installed. See [issues regarding Ghostscript](#issues-regarding-ghostscript).

## Installation

The package can be installed via composer:
``` bash
composer require drenso/pdf-to-image
```

## Usage

Converting a pdf to an image is easy.

```php
$pdf = new Drenso\PdfToImage\Pdf($pathToPdf);
$pdf->saveImage($pathToWhereImageShouldBeStored);
```

If the path you pass to `saveImage` has the extensions `jpg`, `jpeg`, or `png` the image will be saved in that format.
Otherwise the output will be a jpg.

## Other methods

You can get the total number of pages in the pdf:
```php
$pdf->getNumberOfPages(); //returns an int
```

By default the first page of the pdf will be rendered. If you want to render another page you can do so:
```php
$pdf->setPage(2)
    ->saveImage($pathToWhereImageShouldBeStored); //saves the second page
```

You can override the output format:
```php
$pdf->setOutputFormat(ExportFormatEnum::PNG)
    ->saveImage($pathToWhereImageShouldBeStored); //the output wil be a png, no matter what
```

You can set the quality of compression (this depends on the export format, see the GD documentation for more details):
```php
$pdf->setCompressionQuality(100); // sets the compression quality to maximum
```

You can specify the width to scale down the resulting image:
```php
$pdf
   ->setWidth(400)
   ->saveImage($pathToWhereImageShouldBeStored);
```

## Issues regarding Ghostscript

This package uses Ghostscript through Imagick. For this to work Ghostscripts `gs` command should be accessible from the PHP process. For the PHP CLI process (e.g. Laravel's asynchronous jobs, commands, etc...) this is usually already the case. 

However for PHP on FPM (e.g. when running this package "in the browser") you might run into the following problem:

```
Uncaught ImagickException: FailedToExecuteCommand 'gs'
```

This can be fixed by adding the following line at the end of your `php-fpm.conf` file and restarting PHP FPM. If you're unsure where the `php-fpm.conf` file is located you can check `phpinfo()`. If you are using Laravel Valet the `php-fpm.conf` file will be located in the `/usr/local/etc/php/YOUR-PHP-VERSION` directory.

```
env[PATH] = /usr/local/bin:/usr/bin:/bin
```

This will instruct PHP FPM to look for the `gs` binary in the right places.

## Testing

``` bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](https://github.com/spatie/.github/blob/main/CONTRIBUTING.md) for details.

## Credits

- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](.github/LICENSE.md) for more information.
