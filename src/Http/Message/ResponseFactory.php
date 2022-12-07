<?php

declare(strict_types=1);

namespace Tym\Smart\Http\Message;

use Tym\Smart\Http\Message\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseFactoryInterface;

/**
 * @author Yvan Tchuente <yvantchuente@gmail.com>
 */
class ResponseFactory implements ResponseFactoryInterface
{
    public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
    {
        $response = new Response($code, $reasonPhrase);
        return $response;
    }
}
