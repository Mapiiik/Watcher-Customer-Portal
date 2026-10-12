<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\ErrorController;
use App\Test\Support\PortalTestCase;
use PHPUnit\Framework\Attributes\UsesClass;

#[UsesClass(ErrorController::class)]
class ErrorPagesTest extends PortalTestCase
{
    public function testASignedInCustomerCanSignOutOfAnErrorPage(): void
    {
        $this->signedIn();
        $this->crm->failure = 'unavailable';
        $this->get('/');

        $this->assertResponseCode(503);
        $this->assertResponseContains('Sign out');
        $this->assertResponseContains('aria-current');
    }

    public function testAPageThatDoesNotExist(): void
    {
        $this->get('/nowhere');

        $this->assertResponseCode(404);
        $this->assertResponseContains('This page does not exist.');
        $this->assertResponseNotContains('Sign out');
    }
}
