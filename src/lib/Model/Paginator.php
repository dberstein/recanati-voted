<?php

declare(strict_types=1);

namespace Daniel\Vote\Model;

class Paginator
{
    public const PARAM = 'page';

    /**
     * @var string $uri
     */
    protected string $uri;

    /**
     * @var int $page
     */
    protected int $page;

    /**
     * @var array<string> $qs
     */
    protected array $qs = [];

    /**
     * @param string $uri
     * @param ?string $qs
     */
    public function __construct(string $uri, string $qs = null)
    {
        $this->uri = $uri;
        parse_str((string) $qs, $this->qs);
    }

    /**
     * @param int $offset
     */
    public function url(int $offset): string
    {
        $qs = $this->qs;
        if (!(isset($qs[self::PARAM]) && is_numeric($qs[self::PARAM]))) {
            $qs[self::PARAM] = 1;
        }
        $qs[self::PARAM] += $offset;
        $qs[self::PARAM] = max($qs[self::PARAM], 1);
        return $this->uri . '?' . http_build_query($qs);
    }
}
