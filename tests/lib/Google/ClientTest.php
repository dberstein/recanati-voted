<?php

declare(strict_types=1);

require __DIR__ . '/../../../vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use Daniel\Vote\Google\Client;

final class ClientTest extends TestCase
{
    public function testGetAuthUrl(): void
    {
        $client = new Client('null', 'null', 'http://null');
        $this->assertEquals(
            $client->getAuthUrl(),
            'https://accounts.google.com/o/oauth2/v2/auth?response_type=code&access_type=online&client_id=null&redirect_uri=http%3A%2F%2Fnull&state&scope=email%20profile&approval_prompt=auto'
        );
    }
}
