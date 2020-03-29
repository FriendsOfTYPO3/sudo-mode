<?php
declare(strict_types = 1);
namespace FriendsOfTYPO3\SudoMode\Http;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use TYPO3\CMS\Core\Http\ServerRequestInstructionInterface;
use TYPO3\CMS\Core\Http\Stream;
use TYPO3\CMS\Core\Http\Uri;

/**
 * @internal Note that this is not public API yet.
 */
class ServerRequestInstruction implements \JsonSerializable, ServerRequestInstructionInterface
{
    /**
     * @var string
     */
    protected $requestTarget;

    /**
     * @var string
     */
    protected $method;

    /**
     * @var \Psr\Http\Message\UriInterface
     */
    protected $uri;

    /**
     * @var StreamInterface
     */
    protected $body;

    /**
     * @var array|null
     */
    protected $parsedBody;

    /**
     * @var array
     */
    protected $queryParams;

    public static function fromServerRequest(ServerRequestInterface $request): self
    {
        $target = new static();
        $target->requestTarget = $request->getRequestTarget();
        $target->method = $request->getMethod();
        $target->uri = $request->getUri();
        $target->body = $request->getBody();
        $target->parsedBody = $request->getParsedBody();
        $target->queryParams = $request->getQueryParams();
        return $target;
    }

    public static function fromJsonArray(array $data): self
    {
        $target = new static();
        $target->requestTarget = $data['requestTarget'];
        $target->method = $data['method'];
        $target->uri = new Uri($data['uri']);
        $target->body = new Stream('php://temp', $data['body']['mode']);
        $target->body->write($data['body']['contents']);
        $target->parsedBody = $data['parsedBody'];
        $target->queryParams = $data['queryParams'];
        return $target;
    }

    protected function __construct()
    {
        // avoid creating class instances directly
    }

    public function jsonSerialize(): array
    {
        return [
            'requestTarget' => $this->requestTarget,
            'method' => $this->method,
            'uri' => (string)$this->uri,
            'body' => [
                'mode' => $this->body->getMetadata('mode'),
                'contents' => (string)$this->body,
            ],
            'parsedBody' => $this->parsedBody,
            'queryParams' => $this->queryParams,
        ];
    }

    /**
     * Applies instructions to given ServerRequest.
     *
     * @param ServerRequestInterface $request
     * @return ServerRequestInterface
     */
    public function applyTo(ServerRequestInterface $request): ServerRequestInterface
    {
        return $request
            ->withRequestTarget($this->requestTarget)
            ->withMethod($this->method)
            ->withUri($this->uri)
            ->withBody($this->body)
            ->withParsedBody($this->parsedBody)
            ->withQueryParams($this->queryParams);
    }

    /**
     * @return string
     */
    public function getRequestTarget(): string
    {
        return $this->requestTarget;
    }

    /**
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * @return \Psr\Http\Message\UriInterface
     */
    public function getUri(): \Psr\Http\Message\UriInterface
    {
        return $this->uri;
    }

    /**
     * @return StreamInterface
     */
    public function getBody(): StreamInterface
    {
        return $this->body;
    }

    /**
     * @return array
     */
    public function getParsedBody(): ?array
    {
        return $this->parsedBody;
    }

    /**
     * @return array
     */
    public function getQueryParams(): array
    {
        return $this->queryParams;
    }
}
