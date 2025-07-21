<?php

namespace Myragon\Http;

use InvalidArgumentException;
use RuntimeException;

class Response
{
    /**
     * @var int HTTP status code
     */
    protected int $statusCode;

    /**
     * @var array<string,string[]> HTTP headers
     */
    protected array $headers;

    /**
     * @var string Body content
     */
    protected string $body;

    /**
     * @var array<int,string> Standard HTTP status phrases
     */
    protected static array $phrases = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Payload Too Large',
        414 => 'URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Range Not Satisfiable',
        417 => 'Expectation Failed',
        421 => 'Misdirected Request',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        451 => 'Unavailable For Legal Reasons',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        510 => 'Not Extended',
        511 => 'Network Authentication Required',
    ];

    /**
     * Constructor.
     *
     * @param string $body       The response body content.
     * @param int    $statusCode The HTTP status code.
     * @param array<string,string|string[]>  $headers    An array of headers.
     */
    public function __construct(string $body = '', int $statusCode = 200, array $headers = [])
    {
        $this->setStatusCode($statusCode);
        $this->setHeaders($headers);
        $this->setBody($body);
    }

    /**
     * Set the HTTP status code.
     *
     * @param int $statusCode The HTTP status code.
     * @return $this
     */
    public function setStatusCode(int $statusCode): self
    {
        if ($statusCode < 100 || $statusCode >= 600) {
            throw new InvalidArgumentException("Invalid HTTP status code: {$statusCode}");
        }
        $this->statusCode = $statusCode;
        return $this;
    }

    /**
     * Get the HTTP status code.
     *
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Set all headers.
     *
     * @param array<string,string|string[]> $headers An array of headers.
     * @return $this
     */
    public function setHeaders(array $headers): self
    {
        $this->headers = [];
        foreach ($headers as $name => $value) {
            $this->setHeader($name, $value);
        }
        return $this;
    }

    /**
     * Add or set a header.
     *
     * @param string $name  The header name.
     * @param string|string[] $value The header value(s).
     * @param bool $replace  Whether to replace existing header(s) with the same name.
     * @return $this
     */
    public function setHeader(string $name, string|array $value, bool $replace = true): self
    {
        $name = $this->normalizeHeaderName($name);
        $value = (array) $value; // Ensure value is always an array for consistency

        if ($replace || !isset($this->headers[$name])) {
            $this->headers[$name] = $value;
        } else {
            $this->headers[$name] = array_merge($this->headers[$name], $value);
        }
        return $this;
    }

    /**
     * Get all headers.
     *
     * @return array<string,string[]>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Get a specific header.
     *
     * @param string $name The header name.
     * @return string[]|null An array of values for the header, or null if not found.
     */
    public function getHeader(string $name): ?array
    {
        $name = $this->normalizeHeaderName($name);
        return $this->headers[$name] ?? null;
    }

    /**
     * Set the response body.
     *
     * @param string $body The response body content.
     * @return $this
     */
    public function setBody(string $body): self
    {
        $this->body = $body;
        return $this;
    }

    /**
     * Get the response body.
     *
     * @return string
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * Get the HTTP status phrase for a given code.
     *
     * @param int $code The status code.
     * @return string
     */
    protected function getReasonPhrase(int $code): string
    {
        return self::$phrases[$code] ?? 'Unknown Status';
    }

    /**
     * Normalize header name (e.g., "Content-Type" to "Content-Type").
     *
     * @param string $name The header name.
     * @return string
     */
    protected function normalizeHeaderName(string $name): string
    {
        // Simple normalization: capitalize first letter of each word separated by hyphen
        // e.g., 'content-type' -> 'Content-Type'
        return implode('-', array_map('ucfirst', explode('-', $name)));
    }

    /**
     * Sends the HTTP response to the client.
     * This method actually sends the headers and echoes the body.
     * IMPORTANT: This should be called only once and as the last step.
     */
    public function send(): void
    {
        if (headers_sent($file, $line)) {
            throw new RuntimeException("Headers already sent in {$file} on line {$line}. Cannot send response.");
        }

        // 1. Send status line
        header(sprintf(
            'HTTP/%s %s %s',
            '1.1', // Assuming HTTP/1.1
            $this->statusCode,
            $this->getReasonPhrase($this->statusCode)
        ), true, $this->statusCode);

        // 2. Send headers
        foreach ($this->headers as $name => $values) {
            foreach ($values as $value) {
                header(sprintf('%s: %s', $name, $value), false); // false for "append header", true to replace (handled by setHeader)
            }
        }

        // 3. Send body
        echo $this->body;
    }

    /**
     * Static helper to create a JSON response.
     *
     * @param array<int|string,mixed> $data
     * @param int $statusCode
     * @param array<string,string|string[]> $headers
     * @return self
     */
    public static function json(array $data, int $statusCode = 200, array $headers = []): self
    {
        $headers['Content-Type'] = 'application/json';

        return new self(json_encode($data) ?: '{}', $statusCode, $headers);
    }

    /**
     * Static helper to create a redirect response.
     *
     * @param string $location
     * @param int $statusCode
     * @param array<string,string|string[]> $headers
     * @return self
     */
    public static function redirect(string $location, int $statusCode = 302, array $headers = []): self
    {
        $headers['Location'] = $location;

        return new self('', $statusCode, $headers); // Redirects usually have empty body
    }
}
