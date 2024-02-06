<?php

use Drenso\PdfToImage\Exceptions\PageDoesNotExist;
use Drenso\PdfToImage\Exceptions\PdfDoesNotExist;
use Drenso\PdfToImage\Pdf;

beforeEach(function () {
    $this->testFile = __DIR__.'/files/test.pdf';
    $this->multipageTestFile = __DIR__.'/files/multipage-test.pdf';
    $this->remoteFileUrl = 'https://tcd.blackboard.com/webapps/dur-browserCheck-BBLEARN/samples/sample.pdf';
});


it('will throw an exception when try to convert a non existing file', function () {
    new Pdf('pdfdoesnotexists.pdf');
})->throws(PdfDoesNotExist::class);

it('will throw an exception when passed an invalid page number', function ($invalidPage) {
    (new Pdf($this->testFile))->setPage(100);
})
->throws(PageDoesNotExist::class)
->with([5, 0, -1]);

it('will correctly return the number of pages in pdf file', function () {
    $pdf = new Pdf($this->multipageTestFile);

    expect($pdf->getNumberOfPages())->toEqual(3);
});

it('will accept a custom specified resolution', function () {
    $image = (new Pdf($this->testFile, resolution: 150))
        ->getImageData('test.jpg');

    expect(imageresolution($image)[0])->toEqual(150)
        ->and(imageresolution($image)[1])->toEqual(150);
});

it('will convert a specified page', function () {
    $image = (new Pdf($this->multipageTestFile))
        ->setPage(2)
        ->getImageData('page-2.jpg');

    expect($image)->toBeInstanceOf(GdImage::class);
});

it('will create a thumbnail at specified width', function () {
    $image = (new Pdf($this->multipageTestFile))
       ->setWidth(400)
       ->getImageData('test.jpg');

    expect(imagesx($image))->toBe(400);
});
