<?php

declare(strict_types=1);

namespace Tym\Smart\Http\Message;

use Psr\Http\Message\StreamInterface;
use Tym\Smart\Http\Message\UploadedFile;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;

/**
 * @author Yvan Tchuente <yvantchuente@gmail.com>
 */
class UploadedFileFactory implements UploadedFileFactoryInterface
{
    public function createUploadedFile(
        StreamInterface $stream,
        int $size = null,
        int $error = \UPLOAD_ERR_OK,
        string $clientFilename = null,
        string $clientMediaType = null
    ): UploadedFileInterface {
        $uploadedFile = new UploadedFile($stream, $size, $error, $clientFilename, $clientMediaType);
        return $uploadedFile;
    }
}
