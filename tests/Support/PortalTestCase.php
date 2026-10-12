<?php
declare(strict_types=1);

namespace App\Test\Support;

use App\Crm\CustomerPortalApi;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Override;

/**
 * Requests to the portal, with the fake CRM and a customer who comes from one address.
 */
abstract class PortalTestCase extends TestCase
{
    use IntegrationTestTrait;

    protected const IP = '192.0.2.10';

    protected FakeCrm $crm;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->crm = new FakeCrm();
        $this->mockService(CustomerPortalApi::class, fn(): CustomerPortalApi => $this->crm);
        $this->configRequest(['environment' => ['REMOTE_ADDR' => self::IP, 'HTTPS' => 'on']]);
        $this->enableCsrfToken();
    }

    /**
     * The next requests come from another address.
     */
    protected function comingFrom(string $ip): void
    {
        $this->_request['environment']['REMOTE_ADDR'] = $ip;
    }

    /**
     * The next requests come from a signed-in customer.
     */
    protected function signedIn(): void
    {
        $this->session(['Portal' => ['customer' => [
            'token' => FakeCrm::TOKEN,
            'loginId' => 'login-1',
            'login' => 'jan.novak',
            'customerNumber' => '110009',
            'customerName' => 'Jan Novak',
            'ip' => self::IP,
            'passwordMinLength' => 8,
        ]]]);
    }
}
