<?php
declare(strict_types=1);

namespace App\Test\Support;

use App\Crm\Address;
use App\Crm\Consents;
use App\Crm\Contract;
use App\Crm\CrmUnavailable;
use App\Crm\CustomerDetails;
use App\Crm\CustomerPortalApi;
use App\Crm\Email;
use App\Crm\Invoice;
use App\Crm\Phone;
use App\Crm\SignedOut;
use App\Crm\SignInAnswer;
use App\Crm\SignInStatus;

/**
 * Watcher CRM as the tests want it to answer, keeping what the portal asked.
 */
final class FakeCrm implements CustomerPortalApi
{
    public const TOKEN = 'customer-token';

    public const PASSWORD = 'correct horse';

    public const INVOICE_ID = '0b0f0b2c-6a36-4a3c-9f5e-9d1c5d6f1a01';

    public SignInStatus $wrongPassword = SignInStatus::Denied;

    /**
     * Thrown by every question but signing in, when set.
     */
    public ?string $failure = null;

    public ?int $passwordTooShort = null;

    /**
     * @var list<array{0: string, 1: string, 2: string}>
     */
    public array $signIns = [];

    public ?Consents $savedConsents = null;

    public ?string $savedPassword = null;

    public CustomerDetails $details;

    /**
     * @var list<\App\Crm\Invoice>
     */
    public array $invoices;

    public function __construct()
    {
        $this->details = new CustomerDetails(
            '110009',
            'Jan Novak',
            array_merge(array_fill_keys(CustomerDetails::FIELDS, ''), [
                'first_name' => 'Jan',
                'last_name' => 'Novak',
                'date_of_birth' => '1980-04-01',
                'invoice_delivery_type' => '1',
            ]),
            new Consents(true, true, false),
            [new Email('e1', 'jan@example.com', true, true, false)],
            [new Phone('p1', '+420 777 123 456', true, true, false)],
            [new Address('a1', Address::INSTALLATION, [
                'company' => '', 'title' => '', 'first_name' => '', 'last_name' => '', 'suffix' => '',
                'street' => 'Main street', 'number' => '299', 'city' => 'Springfield', 'zip' => '512 43', 'country_id' => '1',
            ])],
            [
                new Contract(
                    'S-110009-01',
                    'Wireless internet',
                    'NET7Q2K9',
                    new Address('a1', Address::INSTALLATION, [
                        'street' => 'Main street', 'number' => '299', 'city' => 'Springfield', 'zip' => '512 43',
                    ]),
                    ['Internet Standard (30 / 8 Mbps)', 'Public IPv4 address'],
                    ['192.0.2.37'],
                    ['198.51.100.0/29'],
                ),
                new Contract('T-110009-02', 'Television', '', null, ['Television M'], [], []),
            ],
            ['1' => 'Czech Republic', '2' => 'Croatia'],
        );
        $this->invoices = [
            new Invoice(self::INVOICE_ID, '2026100231', '110009', '2026-10-01', '2026-10-15', '', 'Internet 10/2026', 1490.0, 1490.0),
            new Invoice('0b0f0b2c-6a36-4a3c-9f5e-9d1c5d6f1a02', '2026090198', '110009', '2026-09-01', '2026-09-15', '2026-09-12', 'Internet 9/2026', 1490.0, 0.0),
        ];
    }

    public function signIn(string $login, string $password, string $ip): SignInAnswer
    {
        $this->signIns[] = [$login, $password, $ip];
        if ($password !== self::PASSWORD) {
            return SignInAnswer::refused($this->wrongPassword);
        }

        return new SignInAnswer(SignInStatus::Granted, self::TOKEN, 'login-1', $login, '110009', 'Jan Novak', 8);
    }

    public function customer(string $token): CustomerDetails
    {
        $this->check($token);

        return $this->details;
    }

    public function saveConsents(string $token, Consents $consents): void
    {
        $this->check($token);
        $this->savedConsents = $consents;
    }

    public function setPassword(string $token, string $password): ?int
    {
        $this->check($token);
        if ($this->passwordTooShort !== null) {
            return $this->passwordTooShort;
        }
        $this->savedPassword = $password;

        return null;
    }

    public function invoices(string $token): array
    {
        $this->check($token);

        return $this->invoices;
    }

    public function invoicePdf(string $token, string $id): ?string
    {
        $this->check($token);

        return $id === self::INVOICE_ID ? '%PDF-1.4 test' : null;
    }

    private function check(string $token): void
    {
        if ($this->failure === 'unavailable') {
            throw new CrmUnavailable('The fake CRM is down.');
        }
        if ($this->failure === 'signed-out' || $token !== self::TOKEN) {
            throw new SignedOut('The fake CRM no longer takes the token.');
        }
    }
}
