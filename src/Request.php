<?php

namespace Myragon\Http;

class Request
{
    /**
     * @var array<int|string,mixed>
     */
    protected array $query = [];

    /**
     * @var array<int|string,mixed>
     */
    protected array $body = [];

    /**
     * @var array<int|string,mixed>
     */
    protected array $files = [];

    /**
     * @var array<string,mixed>
     */
    protected array $server = [];

    /**
     * @var array<int|string,mixed>
     */
    protected array $cookie = [];

    /**
     * @param array<int|string,mixed> $query
     * @param array<int|string,mixed> $body
     * @param array<int|string,mixed> $files
     * @param array<string,mixed> $server
     * @param array<int|string,mixed> $cookie
     */
    public function __construct(
        array $query = [],
        array $body = [],
        array $files = [],
        array $server = [],
        array $cookie = [],
    ) {
        $this->query = $query;
        $this->body = $body;
        $this->files = $files;
        $this->server = $server;
        $this->cookie = $cookie;
    }
}
