<?php

namespace Myragon\Http;

use Myragon\Support\Box;

class Request
{
    /** @var string */
    public const METHOD_GET = "GET";

    /** @var string */
    public const METHOD_POST = "POST";

    /** @var string */
    public const METHOD_PUT = "PUT";

    /** @var string */
    public const METHOD_PATCH = "PATCH";

    /** @var string */
    public const METHOD_DELETE = "DELETE";

    /** @var string */
    public const METHOD_HEAD = "HEAD";

    /** @var string */
    public const METHOD_OPTIONS = "OPTIONS";

    /**
     * @var Box Contains URL query parameters (equivalent to $_GET).
     */
    public readonly Box $query;

    /**
     * @var Box Contains form data from POST requests (equivalent to $_POST).
     */
    public readonly Box $form;

    /**
     * @var Box Contains parsed data from the raw request body, e.g., JSON payload.
     */
    public readonly Box $body;

    /**
     * @var Box Contains information about uploaded files (equivalent to $_FILES).
     */
    public readonly Box $files;

    /**
     * @var Box Contains server and execution environment variables (equivalent to $_SERVER).
     */
    public readonly Box $server;

    /**
     * @var Box Contains cookie data (equivalent to $_COOKIE).
     */
    public readonly Box $cookie;

    /**
     * @var Box Contains HTTP request headers.
     */
    public readonly Box $headers;

    /**
     * Request constructor.
     *
     * @param array<int|string,mixed> $query   Query parameters.
     * @param array<int|string,mixed> $form    Form data.
     * @param array<int|string,mixed> $body    Raw request body data.
     * @param array<int|string,mixed> $files   Uploaded file data.
     * @param array<int|string,mixed> $server  Server and environment variables.
     * @param array<int|string,mixed> $cookie  Cookie data.
     * @param array<int|string,mixed> $headers HTTP headers.
     */
    public function __construct(
        array $query = [],
        array $form = [],
        array $body = [],
        array $files = [],
        array $server = [],
        array $cookie = [],
        array $headers = [],
    ) {
        $this->query = new Box($query);
        $this->form = new Box($form);
        $this->body = new Box($body);
        $this->files = new Box($files);
        $this->server = new Box($server);
        $this->cookie = new Box($cookie);
        $this->headers = new Box($headers);
    }

    /**
     * Creates a Request instance populated with data from PHP's global variables.
     *
     * @return self
     */
    public static function fromGlobals(): self
    {
        return new self(
            $_GET,
            $_POST,
            static::parseRequestBody(),
            $_FILES,
            $_SERVER,
            $_COOKIE,
            getallheaders() ?: [], // getallheaders() might not be available in all SAPI or return false.
        );
    }

    /**
     * Parses the raw request body, currently supporting JSON.
     *
     * @return array<int|string,mixed> Decoded body data, or an empty array if invalid or empty.
     */
    public static function parseRequestBody(): array
    {
        $rawBody = file_get_contents('php://input');
        if (empty($rawBody)) {
            return [];
        }

        // json_validate requires PHP 8.3+. Provides a quick check before decoding.
        if (!json_validate($rawBody)) {
            return [];
        }

        try {
            // Decodes JSON with errors throwing JsonException for consistent handling.
            return json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return [];
        }
    }

    /**
     * Returns the HTTP method of the current request.
     * Example: "GET"
     *
     * @return string The request method
     */
    public function getMethod(): string
    {
        return $this->server->get('REQUEST_METHOD');
    }

    /**
     * Returns the full URL of the current request.
     * Example: "example.com/users/1?active=true"
     *
     * @return string The full request URL.
     */
    public function getUrl(): string
    {
        return $this->getHost() . (($uri = $this->getUri()) !== '/' ? $uri : '');
    }

    /**
     * Returns the request URI including query string.
     * Example: "/users/1?active=true"
     *
     * @return string The request URI.
     */
    public function getUri(): string
    {
        return $this->server->get('REQUEST_URI', '/');
    }

    /**
     * Returns the host name from the request.
     * Example: "example.com"
     *
     * @return string The request host.
     */
    public function getHost(): string
    {
        return $this->server->get('HTTP_HOST', '');
    }

    /**
     * Returns the path component of the request URI.
     * Example: "/users/1" from "/users/1?active=true"
     *
     * @return string The request path.
     */
    public function getPath(): string
    {
        $parsed = parse_url($this->getUri());

        return $parsed && $parsed['path'] ? $parsed['path'] : '/';
    }

    /**
     * Returns the query string component of the request URI.
     * Example: "active=true" from "/users/1?active=true"
     *
     * @return string The query string.
     */
    public function getQuery(): string
    {
        return parse_url($this->getUri())['query'] ?? '';
    }

    /**
     * Retrieves form data.
     *
     * @param string|null $key     Specific key to retrieve, or null for the entire Box.
     * @param mixed       $default Default value if key is not found.
     * @return mixed Form data Box or a specific value.
     */
    public function form(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->form;
        }

        return $this->form->get($key, $default);
    }

    /**
     * Retrieves JSON body data.
     *
     * @param string|null $key     Specific key to retrieve, or null for the entire Box.
     * @param mixed       $default Default value if key is not found.
     * @return mixed JSON body data Box or a specific value.
     */
    public function json(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->body;
        }

        return $this->body->get($key, $default);
    }
}
