<?php

namespace Myragon\Http;

class Response
{
    protected string $content;

    protected int $code;

    /**
     * @var string[]
     */
    protected array $headers;

    /**
     * @param string|null $content
     * @param int $code
     * @param string[] $headers
     */
    public function __construct(
        ?string $content = '',
        int $code = 200,
        array $headers = []
    ) {
        $this->content = $content ?? '';
        $this->code = $code;
        $this->headers = $headers;
    }
}
