<?php
declare(strict_types=1);

namespace App\Test\TestCase\Portal;

use App\Portal\ChangeRequest;
use App\Test\Support\FakeCrm;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ChangeRequest::class)]
class ChangeRequestTest extends TestCase
{
    public function testTheSameDetailsAreNoChange(): void
    {
        $request = ChangeRequest::fromForm((new FakeCrm())->details, [
            'first_name' => ' Jan ',
            'invoice_delivery_type' => '1',
            'emails' => ['e1' => ['email' => 'jan@example.com', 'billing' => '1', 'outages' => '1', 'commercial' => '0']],
            'phones' => ['p1' => ['phone' => '+420 777 123 456']],
            'addresses' => ['a1' => [
                'remove' => '0', 'street' => 'Main street', 'number' => '299', 'city' => 'Springfield', 'zip' => '512 43', 'country_id' => '1',
            ]],
        ]);

        $this->assertTrue($request->isEmpty());
    }

    public function testRemovedAndAddedContactsAndAddresses(): void
    {
        $request = ChangeRequest::fromForm((new FakeCrm())->details, [
            'invoice_delivery_type' => '0',
            'emails' => ['e1' => ['email' => '']],
            'phones' => ['p1' => ['phone' => ''], 'new' => ['phone' => '+420 604 000 000']],
            'addresses' => [
                'a1' => ['remove' => '1'],
                'new' => ['type' => '1', 'street' => 'Side street', 'number' => '7', 'city' => 'Shelbyville', 'zip' => '512 44', 'country_id' => '2'],
            ],
        ]);

        $this->assertSame([
            'Invoice delivery: By e-mail → Not sent',
            'E-mail removed: jan@example.com (billing, outages)',
            'Phone removed: +420 777 123 456',
            'Phone added: +420 604 000 000',
            'Installation address removed: Main street 299, 512 43 Springfield, Czech Republic',
            'Billing address added: Side street 7, 512 44 Shelbyville, Croatia',
        ], $request->changes);
    }

    public function testANoteAloneIsWorthSending(): void
    {
        $request = ChangeRequest::fromForm((new FakeCrm())->details, ['note' => 'Call me, please.']);

        $this->assertFalse($request->isEmpty());
        $this->assertSame([], $request->changes);
        $this->assertSame('Call me, please.', $request->note);
    }
}
