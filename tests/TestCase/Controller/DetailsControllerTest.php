<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\DetailsController;
use App\Controller\PasswordController;
use App\Test\Support\FakeCrm;
use App\Test\Support\PortalTestCase;
use Cake\TestSuite\EmailTrait;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * What a customer changes: the consents, a request for a change of their details, the password.
 */
#[UsesClass(DetailsController::class)]
#[UsesClass(PasswordController::class)]
class DetailsControllerTest extends PortalTestCase
{
    use EmailTrait;

    /**
     * A change of the last name, an e-mail and an address, with a new e-mail and a note.
     */
    private const CHANGE = [
        'first_name' => 'Jan',
        'last_name' => 'Novak-Smith',
        'emails' => [
            'e1' => ['email' => 'jan@example.com', 'billing' => '1', 'outages' => '0', 'commercial' => '0'],
            'new' => ['email' => 'second@example.com', 'billing' => '1', 'outages' => '1', 'commercial' => '0'],
        ],
        'phones' => ['p1' => ['phone' => '+420 777 123 456'], 'new' => ['phone' => '']],
        'addresses' => [
            'a1' => ['remove' => '0', 'street' => 'Main street', 'number' => '300', 'city' => 'Springfield', 'zip' => '512 43', 'country_id' => '1'],
            'new' => ['type' => '1', 'street' => '', 'number' => '', 'city' => ''],
        ],
        'note' => 'Please also send the invoices by post.',
    ];

    public function testTheDetailsShowTheConsentsAsTheyAre(): void
    {
        $this->signedIn();
        $this->get('/details');

        $this->assertResponseOk();
        $this->assertResponseContains('jan@example.com');
        $this->assertResponseContains('Main street 299');
        $this->assertResponseContains('By ticking below I choose which messages I want to receive from Test Operator, s.r.o.');
        $this->assertResponseContains('I want to receive correspondence about the monthly billing');
        $this->assertResponseContains('name="agree_mailing_billing" value="1" checked="checked"');
        $this->assertResponseNotContains('name="agree_mailing_commercial" value="1" checked="checked"');
    }

    public function testTheConsentsAreSavedStraightAway(): void
    {
        $this->signedIn();
        $this->post('/details/consents', [
            'agree_mailing_billing' => '1',
            'agree_mailing_outages' => '0',
            'agree_mailing_commercial' => '1',
        ]);

        $this->assertRedirect('/details');
        $this->assertNotNull($this->crm->savedConsents);
        $this->assertSame(
            ['agree_gdpr' => 1, 'agree_mailing_billing' => 1, 'agree_mailing_outages' => 0, 'agree_mailing_commercial' => 1],
            $this->crm->savedConsents->toForm(),
        );
        $this->assertNoMailSent();
    }

    public function testTheChangeFormHoldsTheCurrentDetails(): void
    {
        $this->signedIn();
        $this->get('/details/change');

        $this->assertResponseOk();
        $this->assertResponseContains('name="emails[e1][email]"');
        $this->assertResponseContains('value="jan@example.com"');
        $this->assertResponseContains('name="addresses[a1][street]"');
        $this->assertResponseContains('value="1980-04-01"');
    }

    public function testAPartOfTheFormHoldsOnlyItsFields(): void
    {
        $this->signedIn();
        $this->get('/details/change?part=contacts');

        $this->assertResponseOk();
        $this->assertResponseContains('name="part" value="contacts"');
        $this->assertResponseContains('id="emails-e1-billing" checked="checked"');
        $this->assertResponseNotContains('id="emails-e1-commercial" checked="checked"');
        $this->assertResponseNotContains('name="addresses[a1][street]"');
        $this->assertResponseNotContains('name="last_name"');
        $this->assertResponseContains('name="note"');
    }

    public function testBillingAndInstallationEachHoldTheirOwnAddresses(): void
    {
        $this->signedIn();
        $this->get('/details/change?part=billing');

        $this->assertResponseContains('name="invoice_delivery_type"');
        $this->assertResponseNotContains('name="addresses[a1][street]"');
        $this->assertResponseContains('name="addresses[new][type]"');
        $this->assertResponseNotContains('<option value="0">Installation address</option>');

        $this->get('/details/change?part=installation');

        $this->assertResponseContains('name="addresses[a1][street]"');
        $this->assertResponseContains('Contracts: S-110009-01');
        $this->assertResponseNotContains('name="addresses[new][type]"');
        $this->assertResponseNotContains('name="invoice_delivery_type"');
    }

    public function testAChangeIsShownToBeCheckedBeforeItIsSent(): void
    {
        $this->signedIn();
        $this->post('/details/change', ['part' => 'customer'] + self::CHANGE);

        $this->assertResponseOk();
        $this->assertResponseContains('Check your request');
        $this->assertResponseContains('Last name: Novak → Novak-Smith');
        $this->assertResponseContains('E-mail added: second@example.com (billing, outages)');
        $this->assertResponseContains('href="/details/change?part=customer&amp;edit=1"');
        $this->assertNoMailSent();
        $this->assertSession('customer', 'Portal.changeDraft.part');
        $this->assertSession('Novak-Smith', 'Portal.changeDraft.form.last_name');
    }

    public function testACheckedChangeIsSentByMail(): void
    {
        // before signing in, which would write the whole of Portal over it
        $this->session(['Portal.changeDraft' => ['part' => 'customer', 'form' => self::CHANGE]]);
        $this->signedIn();
        $this->post('/details/change/send');

        $this->assertRedirect('/details');
        $this->assertMailCount(1);
        $this->assertMailSentTo('contracts@example.invalid');
        $this->assertMailSubjectContains('110009');
        $this->assertMailContainsText('Last name: Novak → Novak-Smith');
        $this->assertMailContainsText('E-mail: jan@example.com (billing, outages) → jan@example.com (billing)');
        $this->assertMailContainsText('E-mail added: second@example.com (billing, outages)');
        $this->assertMailContainsText('Installation address: Main street 299, 512 43 Springfield, Czech Republic → Main street 300, 512 43 Springfield, Czech Republic');
        $this->assertMailContainsText('Please also send the invoices by post.');
        $this->assertMailContainsText('Login: jan.novak');
        $this->assertSessionNotHasKey('Portal.changeDraft');
    }

    public function testEditingBringsBackWhatWasWritten(): void
    {
        $this->session(['Portal.changeDraft' => ['part' => 'customer', 'form' => self::CHANGE]]);
        $this->signedIn();
        $this->get('/details/change?part=customer&edit=1');

        $this->assertResponseOk();
        $this->assertResponseContains('value="Novak-Smith"');
    }

    public function testWhatIsAddedStaysInSightWhenEditing(): void
    {
        $this->session(['Portal.changeDraft' => ['part' => 'contacts', 'form' => self::CHANGE]]);
        $this->signedIn();
        $this->get('/details/change?part=contacts&edit=1');

        $this->assertResponseContains('value="second@example.com"');
        $this->assertResponseContains('<details class="more" open>');
        $this->assertResponseContains('<details class="more">');
    }

    public function testNothingChangedSendsNothing(): void
    {
        $this->signedIn();
        $this->post('/details/change', ['part' => 'customer', 'first_name' => 'Jan', 'last_name' => 'Novak']);

        $this->assertRedirect('/details/change?part=customer');
        $this->assertNoMailSent();
        $this->assertFlashMessage('Nothing has changed, there is nothing to send.');
        $this->assertSessionNotHasKey('Portal.changeDraft');
    }

    public function testTheDetailsShowWhichContractsUseAnAddress(): void
    {
        $this->signedIn();
        $this->get('/details');

        $this->assertResponseContains('Contracts: S-110009-01');
        $this->assertResponseContains('href="/details/change?part=installation"');
        $this->assertResponseContains('Without a billing address, invoices go to the installation address.');
    }

    public function testThePasswordIsChangedWithTheCurrentOne(): void
    {
        $this->signedIn();
        $this->post('/password', [
            'current_password' => FakeCrm::PASSWORD,
            'new_password' => 'a new horse',
            'new_password_again' => 'a new horse',
        ]);

        $this->assertRedirect('/');
        $this->assertSame('a new horse', $this->crm->savedPassword);
        $this->assertSame([['jan.novak', FakeCrm::PASSWORD, self::IP]], $this->crm->signIns);
    }

    public function testAWrongCurrentPasswordChangesNothing(): void
    {
        $this->signedIn();
        $this->post('/password', [
            'current_password' => 'wrong',
            'new_password' => 'a new horse',
            'new_password_again' => 'a new horse',
        ]);

        $this->assertResponseOk();
        $this->assertResponseContains('The current password is not right.');
        $this->assertNull($this->crm->savedPassword);
    }

    public function testANewPasswordHasToBeLongEnoughAndTypedTwice(): void
    {
        $this->signedIn();
        $this->post('/password', ['current_password' => FakeCrm::PASSWORD, 'new_password' => 'short', 'new_password_again' => 'short']);
        $this->assertResponseContains('The new password has to be at least 8 characters long.');

        $this->post('/password', ['current_password' => FakeCrm::PASSWORD, 'new_password' => 'a new horse', 'new_password_again' => 'a new mouse']);
        $this->assertResponseContains('The new passwords do not match.');

        $this->crm->passwordTooShort = 12;
        $this->post('/password', ['current_password' => FakeCrm::PASSWORD, 'new_password' => 'a new horse', 'new_password_again' => 'a new horse']);
        $this->assertResponseContains('The new password has to be at least 12 characters long.');

        $this->assertNull($this->crm->savedPassword);
    }
}
