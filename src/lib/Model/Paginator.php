<?php declare(strict_types=1);

namespace Daniel\Vote\Model;

class Paginator {
    const PARAM = 'page';
    protected string $uri;
    protected int $page;
    /**
     * @var array<string> $qs
     */
    protected array $qs = [];
    public function __construct(string $uri, string $qs = null) {
        $this->uri = $uri;
        parse_str((string) $qs, $this->qs);
    }

    public function url(int $offset): string {
        $qs = $this->qs;
        if (!(isset($qs[self::PARAM]) && is_numeric($qs[self::PARAM]))) {
            $qs[self::PARAM] = 1;
        }
        $qs[self::PARAM] += $offset;
        $qs[self::PARAM] = max($qs[self::PARAM], 1);
        return $this->uri . '?' . http_build_query($qs);
    }
}