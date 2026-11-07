<?php
declare(strict_types=1);

namespace App\Test\TestCase\Error;

use App\Error\PortalErrorLogger;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;

class PortalErrorLoggerTest extends TestCase
{
    public function testTheQueryOfARequestStaysOutOfTheLog(): void
    {
        $request = (new ServerRequest(['url' => '/access/set?token=the-token-of-a-link']))
            ->withHeader('Referer', 'https://portal.example.net/access/set?token=the-token-of-a-link');

        $context = (new PortalErrorLogger())->getRequestContext($request);

        $this->assertStringContainsString('Request URL: /access/set', $context);
        $this->assertStringContainsString('Referer URL: https://portal.example.net/access/set', $context);
        $this->assertStringNotContainsString('the-token-of-a-link', $context);
    }
}
