<?php

declare(strict_types=1);

namespace {{NAMESPACE}};

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

class {{NAME}}
{
    /** @var ResponseFactoryInterface */
    protected $responseFactory;

    /** @var StreamFactoryInterface */
    protected $streamFactory;

    public function __construct(ResponseFactoryInterface $responseFactory, StreamFactoryInterface $streamFactory)
    {
        $this->responseFactory = $responseFactory;
        $this->streamFactory = $streamFactory;
    }

    public function {{METHOD}}(ServerRequestInterface $request): ResponseInterface
    {
        // Do awesome stuff

        return $this->responseFactory->createResponse()->withBody(
            $this->streamFactory->createStream('Response content from {{NAME}} with route path: ' . $request->getAttribute('route')->getPath())
        );
    }
}
