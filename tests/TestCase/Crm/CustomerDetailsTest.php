<?php
declare(strict_types=1);

namespace App\Test\TestCase\Crm;

use App\Crm\Address;
use App\Crm\CustomerDetails;
use App\Crm\Fields;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(CustomerDetails::class)]
class CustomerDetailsTest extends TestCase
{
    public function testTheAddressesComeInTheOrderTheCrmLooksForAnInvoiceAddress(): void
    {
        $details = CustomerDetails::fromAnswer(new Fields(['customer' => ['addresses' => [
            ['id' => 'i1', 'type' => Address::INSTALLATION],
            ['id' => 'i2', 'type' => Address::INSTALLATION],
            ['id' => 'b1', 'type' => Address::BILLING],
            ['id' => 'd1', 'type' => Address::DELIVERY],
            ['id' => 'p1', 'type' => Address::PERMANENT],
        ]]]));

        $this->assertSame(
            ['b1', 'p1', 'i1', 'i2', 'd1'],
            array_map(static fn(Address $address): string => $address->id, $details->addresses),
        );
    }
}
