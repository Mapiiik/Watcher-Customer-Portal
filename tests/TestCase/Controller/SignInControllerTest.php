<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\SignInController;
use App\Crm\SignInStatus;
use App\Test\Support\FakeCrm;
use App\Test\Support\PortalTestCase;
use PHPUnit\Framework\Attributes\UsesClass;

#[UsesClass(SignInController::class)]
class SignInControllerTest extends PortalTestCase
{
    public function testTheSignInPageIsShownWithTheOperator(): void
    {
        $this->get('/sign-in');

        $this->assertResponseOk();
        $this->assertResponseContains('name="login"');
        $this->assertResponseContains('autocomplete="current-password"');
        $this->assertResponseContains('Test Operator, s.r.o.');
        $this->assertResponseNotContains('Sign out');
    }

    public function testAPageAsksForSigningInAndComesBackAfterIt(): void
    {
        $this->get('/invoices?page=2');
        $this->assertRedirect('/sign-in');
        $this->assertSession('/invoices?page=2', 'Portal.returnTo');

        // the session does not pass from one request of a test to the next on its own
        $this->session(['Portal' => ['returnTo' => '/invoices']]);
        $this->post('/sign-in', ['login' => 'jan.novak', 'password' => FakeCrm::PASSWORD]);

        $this->assertRedirect('/invoices');
        $this->assertSession(FakeCrm::TOKEN, 'Portal.customer.token');
        $this->assertSame([['jan.novak', FakeCrm::PASSWORD, self::IP]], $this->crm->signIns);
    }

    public function testAWrongPasswordIsRefused(): void
    {
        $this->post('/sign-in', ['login' => 'jan.novak', 'password' => 'wrong']);

        $this->assertResponseOk();
        $this->assertResponseContains('The login or the password is not right.');
        $this->assertResponseContains('value="jan.novak"');
        $this->assertSessionNotHasKey('Portal.customer');
    }

    public function testTooManyAttemptsAreSaidSo(): void
    {
        $this->crm->wrongPassword = SignInStatus::Blocked;
        $this->post('/sign-in', ['login' => 'jan.novak', 'password' => 'wrong']);

        $this->assertResponseContains('Too many failed attempts');
    }

    public function testTheReturnAddressIsAPageOfThePortalOnly(): void
    {
        $this->session(['Portal' => ['returnTo' => '//attacker.example/']]);
        $this->post('/sign-in', ['login' => 'jan.novak', 'password' => FakeCrm::PASSWORD]);

        $this->assertRedirect('/');
    }

    public function testSigningOutForgetsTheCustomer(): void
    {
        $this->signedIn();
        $this->post('/sign-out');

        $this->assertRedirect('/sign-in');
        $this->assertSessionNotHasKey('Portal.customer');
    }

    public function testAFormFromAnotherSiteIsRefused(): void
    {
        $this->configRequest(['headers' => ['Origin' => 'https://attacker.example']]);
        $this->post('/sign-in', ['login' => 'jan.novak', 'password' => FakeCrm::PASSWORD]);

        $this->assertResponseCode(403);
        $this->assertSame([], $this->crm->signIns);
    }

    public function testAFormWithoutTheTokenOfTheSessionIsRefused(): void
    {
        $this->_csrfToken = false;
        $this->post('/sign-in', ['login' => 'jan.novak', 'password' => FakeCrm::PASSWORD]);

        $this->assertResponseCode(403);
        $this->assertSame([], $this->crm->signIns);
    }

    public function testAnotherIpv4AddressIsNoLongerSignedIn(): void
    {
        $this->signedIn();
        $this->comingFrom('198.51.100.7');
        $this->get('/');

        $this->assertRedirect('/sign-in');
    }

    public function testAnExpiredSignInSendsToSigningIn(): void
    {
        $this->signedIn();
        $this->crm->failure = 'signed-out';
        $this->get('/invoices');

        $this->assertRedirect('/sign-in');
        $this->assertSessionNotHasKey('Portal.customer');
        $this->assertFlashMessage('Your sign-in has expired, please sign in again.');
    }

    public function testTheCrmNotAnsweringIsAnErrorPage(): void
    {
        $this->signedIn();
        $this->crm->failure = 'unavailable';
        $this->get('/invoices');

        $this->assertResponseCode(503);
        $this->assertResponseContains('The customer portal is not available right now.');
        $this->assertResponseNotContains('fake CRM');
    }

    public function testTheOldAddressesSendToTheNewPages(): void
    {
        $this->get('/index.php');
        $this->assertRedirect('/');
        $this->assertResponseCode(301);

        $this->get('/reports/customer-data.php');
        $this->assertRedirect('/details');

        $this->signedIn();
        $this->get('/reports/invoices.php?invoice=' . FakeCrm::INVOICE_ID);
        $this->assertRedirect('/invoices/' . FakeCrm::INVOICE_ID);
    }
}
