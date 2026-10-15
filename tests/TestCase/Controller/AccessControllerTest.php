<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\AccessController;
use App\Crm\AccessRequestStatus;
use App\Test\Support\FakeCrm;
use App\Test\Support\PortalTestCase;
use Cake\Core\Configure;
use Cake\TestSuite\EmailTrait;
use Cake\TestSuite\TestEmailTransport;
use Override;
use PHPUnit\Framework\Attributes\UsesClass;

#[UsesClass(AccessController::class)]
class AccessControllerTest extends PortalTestCase
{
    use EmailTrait;

    private const SAME_MESSAGE = 'If the customer number and the e-mail belong together';

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        Configure::write('App.fullBaseUrl', 'https://portal.example.net');
    }

    public function testTheSignInPageLeadsToIt(): void
    {
        Configure::write('Portal.operator.supportEmail', 'help@example.net');
        Configure::write('Portal.operator.supportPhone', '+420 123 456 789');
        $this->get('/sign-in');

        $this->assertResponseContains('href="/access"');
        $this->assertResponseContains(
            'Need help? Contact us: <a href="tel:+420123456789">+420 123 456 789</a>, '
            . '<a href="mailto:help@example.net">help@example.net</a>',
        );
    }

    public function testALinkIsSentToTheEmailTypedOnTheAddressOfThePortal(): void
    {
        $this->configRequest(['headers' => ['Host' => 'attacker.example']]);
        $this->post('/access', ['customer_number' => '110009', 'email' => 'jan@example.com']);

        $this->assertRedirect('/sign-in');
        $this->assertFlashMessageContains(self::SAME_MESSAGE);
        $this->assertSame([['110009', 'jan@example.com', self::IP]], $this->crm->accessRequests);
        $this->assertMailCount(1);
        $this->assertMailSentTo('jan@example.com');
        $this->assertMailContainsText('https://portal.example.net/access/set?token=' . FakeCrm::LINK_TOKEN);
        $this->assertStringNotContainsString('attacker.example', $this->mailText());
    }

    public function testNoLinkIsSentWithoutTheAddressOfThePortalOutsideDebug(): void
    {
        Configure::write('App.fullBaseUrl', false);
        $this->post('/access', ['customer_number' => '110009', 'email' => 'jan@example.com']);

        $this->assertFlashMessageContains(self::SAME_MESSAGE);
        $this->assertNoMailSent();
    }

    public function testANumberAndAnEmailThatDoNotBelongTogetherAreToldTheSame(): void
    {
        $this->crm->accessRequestStatus = AccessRequestStatus::Unknown;
        $this->post('/access', ['customer_number' => '110009', 'email' => 'someone@example.com']);

        $this->assertRedirect('/sign-in');
        $this->assertFlashMessageContains(self::SAME_MESSAGE);
        $this->assertNoMailSent();
    }

    public function testALockedAccessIsToldInTheMailNotOnThePage(): void
    {
        $this->crm->accessRequestStatus = AccessRequestStatus::Locked;
        Configure::write('Portal.operator.supportPhone', '+420 123 456 789');
        $this->post('/access', ['customer_number' => '110009', 'email' => 'jan@example.com']);

        $this->assertFlashMessageContains(self::SAME_MESSAGE);
        $this->assertMailSentTo('jan@example.com');
        $this->assertMailContainsText('locked on purpose');
        $this->assertMailContainsText('+420 123 456 789');
        $this->assertStringNotContainsString('/access/set', $this->mailText());
    }

    public function testTooManyRequestsAreSaidSo(): void
    {
        $this->crm->accessRequestStatus = AccessRequestStatus::Blocked;
        $this->post('/access', ['customer_number' => '110009', 'email' => 'jan@example.com']);

        $this->assertResponseOk();
        $this->assertResponseContains('Too many requests');
        $this->assertNoMailSent();
    }

    public function testThePasswordIsSetWithTheLinkAndTheCustomerSignedIn(): void
    {
        $this->get('/access/set?token=' . FakeCrm::LINK_TOKEN);
        $this->assertResponseOk();
        $this->assertResponseContains('jan.novak');

        $this->post('/access/set?token=' . FakeCrm::LINK_TOKEN, [
            'password' => FakeCrm::PASSWORD,
            'password_again' => FakeCrm::PASSWORD,
        ]);

        $this->assertRedirect('/');
        $this->assertSame(['jan.novak', FakeCrm::PASSWORD, self::IP], $this->crm->accessSet);
        $this->assertSession(FakeCrm::TOKEN, 'Portal.customer.token');
        $this->assertMailSentTo('jan@example.com');
        $this->assertMailContainsText('has been changed');
        $this->assertMailContainsText(self::IP);
    }

    public function testALoginIsMadeWhereThereIsNone(): void
    {
        $this->crm->offeredLogins = [];
        $this->get('/access/set?token=' . FakeCrm::LINK_TOKEN);
        $this->assertResponseContains('novak.jan');

        $this->post('/access/set?token=' . FakeCrm::LINK_TOKEN, [
            'password' => 'a new password',
            'password_again' => 'a new password',
        ]);

        $this->assertSame('novak.jan', $this->crm->accessSet[0] ?? null);
        $this->assertMailContainsText('has been made');
    }

    public function testOneOfSeveralLoginsIsChosen(): void
    {
        $this->crm->offeredLogins = ['login-1' => 'jan.novak', 'login-2' => 'jan.novak.2'];
        $this->get('/access/set?token=' . FakeCrm::LINK_TOKEN);
        $this->assertResponseContains('<option value="login-2">jan.novak.2</option>');

        $this->post('/access/set?token=' . FakeCrm::LINK_TOKEN, [
            'login_id' => 'login-2',
            'password' => 'a new password',
            'password_again' => 'a new password',
        ]);

        $this->assertSame('jan.novak.2', $this->crm->accessSet[0] ?? null);
    }

    public function testPasswordsThatDoNotMatchOrAreShortAreRefused(): void
    {
        $url = '/access/set?token=' . FakeCrm::LINK_TOKEN;
        $this->post($url, ['password' => 'a new password', 'password_again' => 'another password']);
        $this->assertResponseContains('The new passwords do not match.');

        $this->post($url, ['password' => 'short', 'password_again' => 'short']);
        $this->assertResponseContains('at least 8 characters');

        $this->assertNull($this->crm->accessSet);
        $this->assertNoMailSent();
    }

    public function testASpentLinkSendsToAskForANewOne(): void
    {
        $this->crm->linkSpent = true;
        $this->get('/access/set?token=' . FakeCrm::LINK_TOKEN);

        $this->assertRedirect('/access');
        $this->assertFlashMessageContains('The link no longer works.');
    }

    /**
     * The text of the one mail sent.
     */
    private function mailText(): string
    {
        $messages = TestEmailTransport::getMessages();
        $this->assertCount(1, $messages);

        return $messages[0]->getBodyString();
    }
}
