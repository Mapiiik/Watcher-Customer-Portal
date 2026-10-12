<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\BrandingController;
use App\Controller\InvoicesController;
use App\Controller\OverviewController;
use App\Crm\Invoice;
use App\Test\Support\FakeCrm;
use App\Test\Support\PortalTestCase;
use Cake\Core\Configure;
use Override;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * The pages a signed-in customer reads: the overview, the invoices, and the operator's branding.
 */
#[UsesClass(OverviewController::class)]
#[UsesClass(InvoicesController::class)]
#[UsesClass(BrandingController::class)]
class PagesTest extends PortalTestCase
{
    #[Override]
    protected function tearDown(): void
    {
        Configure::write('Portal.profile', Configure::read('Portal.defaultProfile'));
        parent::tearDown();
    }

    public function testTheOverviewShowsWhatIsLeftToPayAndTheConnections(): void
    {
        $this->signedIn();
        $this->get('/');

        $this->assertResponseOk();
        $this->assertResponseContains('Hello, Jan Novak');
        $this->assertResponseContains('€1,490.00');
        $this->assertResponseContains('2026100231');
        $this->assertResponseContains('192.0.2.37');
        $this->assertResponseContains('NET7Q2K9');
        $this->assertResponseContains('aria-current="page">Overview');
        $this->assertResponseContains('Sign out');
        $this->assertHeader('Cache-Control', 'no-store');
    }

    public function testTheOverviewHasACodeForEveryUnpaidInvoice(): void
    {
        Configure::write('Portal.payment', ['code' => 'spayd', 'iban' => 'CZ6508000000192000145399']);
        $this->crm->invoices[] = new Invoice(
            '0b0f0b2c-6a36-4a3c-9f5e-9d1c5d6f1a04',
            '2026090199',
            '110009',
            '2026-09-01',
            '2026-09-15',
            '',
            'Router',
            990.0,
            990.0,
        );
        $this->signedIn();
        $this->get('/');

        $this->assertResponseContains('alt="Payment code for invoice 2026100231"');
        $this->assertResponseContains('alt="Payment code for invoice 2026090199"');
        $this->assertResponseNotContains('alt="Payment code for invoice 2026090198"');
        $this->assertResponseContains('src="data:image/png;base64,');
    }

    public function testTheOverviewHasNoCodesUnlessTheyAreSetUp(): void
    {
        $this->signedIn();
        $this->get('/');

        $this->assertResponseOk();
        $this->assertResponseNotContains('pay-codes');
    }

    public function testTheOverviewOfACustomerWhoPaidAll(): void
    {
        $this->crm->invoices = [
            new Invoice('0b0f0b2c-6a36-4a3c-9f5e-9d1c5d6f1a03', '1', '1', '2026-09-01', '2026-09-15', '2026-09-10', '', 10.0, 0.0),
        ];
        $this->signedIn();
        $this->get('/');

        $this->assertResponseContains('All your invoices are paid. Thank you.');
    }

    public function testTheInvoicesAreListedWithTheirState(): void
    {
        $this->signedIn();
        $this->get('/invoices');

        $this->assertResponseOk();
        $this->assertResponseContains('/invoices/' . FakeCrm::INVOICE_ID);
        $this->assertResponseContains('Paid');
        $this->assertResponseContains('Left to pay');
    }

    public function testAnInvoiceIsDownloadedAsTheCustomersOwnPdf(): void
    {
        $this->signedIn();
        $this->get('/invoices/' . FakeCrm::INVOICE_ID);

        $this->assertResponseOk();
        $this->assertContentType('application/pdf');
        $this->assertHeader('Cache-Control', 'private, no-store');
        $this->assertHeaderContains('Content-Disposition', 'attachment; filename="Invoice-2026100231.pdf"');
        $this->assertSame('%PDF-1.4 test', (string)$this->_response?->getBody());
    }

    public function testAnInvoiceThatIsNotTheCustomersIsNotFound(): void
    {
        $this->signedIn();
        $this->get('/invoices/0b0f0b2c-6a36-4a3c-9f5e-9d1c5d6f1aff');

        $this->assertResponseCode(404);
    }

    public function testTheBrandingServesOnlyTheProfilesAssets(): void
    {
        Configure::write('Portal.profile', TESTS . 'Fixture' . DS . 'profile');

        $this->get('/branding/colors.css');
        $this->assertResponseOk();
        $this->assertContentType('text/css');
        $this->assertHeaderContains('Content-Security-Policy', 'sandbox');

        $this->get('/branding/profile.php');
        $this->assertResponseCode(404);

        $this->get('/branding/..%2Fprofile.php');
        $this->assertResponseCode(404);
    }

    public function testThePagesOfAProfileCarryItsLookAndName(): void
    {
        Configure::write('Portal.profile', TESTS . 'Fixture' . DS . 'profile');
        $this->get('/sign-in');

        $this->assertResponseContains('<title>Test Operator | Portal | Sign in</title>');
        $this->assertResponseContains('href="/branding/colors.css"');
        $this->assertResponseContains('rel="icon" href="/branding/favicon-32.png" type="image/png" sizes="32x32"');
        $this->assertResponseContains('<img src="/branding/logo.svg"');
    }
}
