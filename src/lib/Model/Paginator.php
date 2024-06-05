<?php declare(strict_types=1);

namespace Daniel\Vote\Model;

class Paginator {
    protected string $uri;
    protected int $page;
    protected array $qs = [];
    public function __construct($uri, $qs = null) {
        $this->uri = $uri;
        parse_str((string) $qs, $this->qs);
    }

    public function url($offset): string {
        $qs = $this->qs;
        if (!(isset($qs['page']) && is_numeric($qs['page']))) {
            $qs['page'] = 1;
        }
        $qs['page'] += $offset;
        $qs['page'] = max($qs['page'], 1);
        return $this->uri . '?' . http_build_query($qs);
    }
}