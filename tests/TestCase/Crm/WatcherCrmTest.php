<?php
declare(strict_types=1);

namespace App\Test\TestCase\Crm;

use App\Crm\Consents;
use App\Crm\CrmUnavailable;
use App\Crm\SignedOut;
use App\Crm\SignInStatus;
use App\Crm\WatcherCrm;
use Cake\Http\Client;
use Cake\Http\Client\Request;
use Cake\Http\TestSuite\HttpClientTrait;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(WatcherCrm::class)]
class WatcherCrmTest extends TestCase
{
    use HttpClientTrait;

    private const API = 'https://crm.example.net/api/customer-portal';

    private WatcherCrm $crm;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->crm = new WatcherCrm(new Client(), self::API, 'portal-key');
    }

    public function testSigningInSendsTheAddressAndKeepsTheToken(): void
    {
        $this->mockClientPost(self::API . '/login', $this->newClientResponse(200, [], (string)json_encode([
            'status' => 'granted',
            'token' => 'customer-token',
            'login' => ['id' => 'login-1', 'login' => 'jan.novak', 'rights' => 1],
            'customer' => ['id' => 'c1', 'number' => '110009', 'name' => 'Jan Novak'],
            'password_min_length' => 10,
        ])), [
            'match' => static function (Request $request): bool {
                parse_str((string)$request->getBody(), $form);

                return $request->getHeaderLine('Authorization') === 'Bearer portal-key'
                    && $form === ['login' => 'jan.novak', 'password' => 'correct horse', 'ip' => '192.0.2.10'];
            },
        ]);

        $answer = $this->crm->signIn('jan.novak', 'correct horse', '192.0.2.10');

        $this->assertTrue($answer->granted());
        $this->assertSame('customer-token', $answer->token);
        $this->assertSame('110009', $answer->customerNumber);
        $this->assertSame(10, $answer->passwordMinLength);
    }

    public function testARefusalSaysWhy(): void
    {
        $this->mockClientPost(self::API . '/login', $this->newClientResponse(200, [], '{"status":"blocked"}'));

        $this->assertSame(SignInStatus::Blocked, $this->crm->signIn('jan.novak', 'x', '192.0.2.10')->status);
    }

    public function testTheCustomerAndTheInvoicesCarryTheToken(): void
    {
        $token = ['match' => static fn(Request $request): bool => $request->getHeaderLine(WatcherCrm::TOKEN_HEADER) === 'customer-token'];
        $this->mockClientGet(self::API . '/customer', $this->newClientResponse(200, [], (string)json_encode([
            'customer' => [
                'number' => '110009', 'name' => 'Jan Novak', 'agree_mailing_billing' => true,
                'emails' => [['id' => 'e1', 'email' => 'jan@example.com', 'use_for_billing' => true]],
                'addresses' => [['id' => 'a1', 'type' => 0, 'street' => 'Main street', 'number' => '299', 'country_id' => 1]],
                'ip_addresses' => [['ip_address' => '192.0.2.37', 'contract_number' => 'S-1', 'subscriber_verification_code' => null]],
            ],
            'countries' => ['1' => 'Czech Republic'],
        ])), $token);
        $this->mockClientGet(self::API . '/invoices', $this->newClientResponse(200, [], (string)json_encode([
            'invoices' => [['id' => 'i1', 'number' => '2026100231', 'total' => '1490.00', 'debt' => 1490, 'due_date' => '2026-10-15']],
        ])), $token);

        $details = $this->crm->customer('customer-token');
        $invoices = $this->crm->invoices('customer-token');

        $this->assertSame('Jan Novak', $details->name);
        $this->assertTrue($details->consents->billing);
        $this->assertSame('jan@example.com', $details->emails[0]->email);
        $this->assertSame('Main street 299', $details->addresses[0]->street());
        $this->assertSame('Czech Republic', $details->country('1'));
        $this->assertSame('', $details->connections[0]->verificationCode);
        $this->assertSame(1490.0, $invoices[0]->total);
        $this->assertFalse($invoices[0]->isPaid());
    }

    public function testConsentsAndAPasswordAreSent(): void
    {
        $this->mockClientPost(self::API . '/consents', $this->newClientResponse(200, [], '{"status":"saved"}'), [
            'match' => static function (Request $request): bool {
                parse_str((string)$request->getBody(), $form);

                return $form === ['agree_gdpr' => '1', 'agree_mailing_billing' => '1', 'agree_mailing_outages' => '0', 'agree_mailing_commercial' => '0'];
            },
        ]);
        $this->mockClientPost(self::API . '/password', $this->newClientResponse(422, [], '{"status":"too_short","min_length":12}'));

        $this->crm->saveConsents('customer-token', new Consents(true, false, false));
        $this->assertSame(12, $this->crm->setPassword('customer-token', 'short'));
    }

    public function testAForeignInvoiceIsNotFound(): void
    {
        $this->mockClientGet(self::API . '/invoices/i2/download', $this->newClientResponse(404, [], ''));

        $this->assertNull($this->crm->invoicePdf('customer-token', 'i2'));
    }

    public function testAnExpiredTokenSignsOut(): void
    {
        $this->mockClientGet(self::API . '/customer', $this->newClientResponse(401, [], '{}'));

        $this->expectException(SignedOut::class);
        $this->crm->customer('customer-token');
    }

    public function testARefusedKeyIsReportedAsSuch(): void
    {
        $this->mockClientGet(self::API . '/invoices', $this->newClientResponse(302, ['Location: /users/login'], ''));

        $this->expectException(CrmUnavailable::class);
        $this->expectExceptionMessage('HTTP 302, check CRM_API_KEY and its role');
        $this->crm->invoices('customer-token');
    }

    public function testNoAnswerIsTheCrmBeingUnavailable(): void
    {
        $crm = new WatcherCrm(new Client(['timeout' => 1]), 'https://127.0.0.1:1/api', 'portal-key');

        $this->expectException(CrmUnavailable::class);
        $this->expectExceptionMessage('Watcher CRM did not answer GET /invoices');
        $crm->invoices('customer-token');
    }
}
