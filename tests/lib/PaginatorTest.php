<?php

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use Daniel\Vote\Model\Paginator;

final class PaginatorTest extends TestCase
{
    public function testNext(): void
    {
        $paginator = new Paginator('/', 'dummy=data&page=2');
        $this->assertEquals($paginator->url(-1), '/?dummy=data&page=1');
        $this->assertEquals($paginator->url(1), '/?dummy=data&page=3');
        $paginator = new Paginator('/q/1234', 'dummy=data&page=2');
        $this->assertEquals($paginator->url(-1), '/q/1234?dummy=data&page=1');
        $this->assertEquals($paginator->url(1), '/q/1234?dummy=data&page=3');
        $paginator = new Paginator('/x', 'dummy=data');
        $this->assertEquals($paginator->url(0), '/x?dummy=data&page=1');
        $this->assertEquals($paginator->url(-1), '/x?dummy=data&page=1');
        $this->assertEquals($paginator->url(1), '/x?dummy=data&page=2');
    }
}
