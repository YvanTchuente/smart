<?php

declare(strict_types=1);

namespace Tym\Smart\Http\Message;

use Tym\Smart\Http\Message\Uri;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\UriFactoryInterface;

/**
 * @author Yvan Tchuente <yvantchuente@gmail.com>
 */
class UriFactory implements UriFactoryInterface
{
    public function createUri(string $uri = ''): UriInterface
    {
        return new Uri($uri);
    }
}
