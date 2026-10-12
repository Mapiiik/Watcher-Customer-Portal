<?php
declare(strict_types=1);

namespace App\Portal;

use Cake\Core\Configure;

/**
 * The operator the portal is for, as its environment names them. Everything but the name may be
 * left out, and what is left out is not shown.
 */
final readonly class Operator
{
    /**
     * @param string $name
     * @param string $website With its scheme.
     * @param string $address A line for the footer.
     * @param string $privacyPolicyUrl
     * @param string $supportEmail
     * @param string $supportPhone
     * @param string $paymentAccount The account invoices are paid to.
     */
    public function __construct(
        public string $name,
        public string $website = '',
        public string $address = '',
        public string $privacyPolicyUrl = '',
        public string $supportEmail = '',
        public string $supportPhone = '',
        public string $paymentAccount = '',
    ) {
    }

    /**
     * The operator the environment names.
     *
     * @return self
     */
    public static function fromConfig(): self
    {
        $setting = static fn(string $key): string => (string)Configure::read('Portal.operator.' . $key);

        return new self(
            $setting('name'),
            $setting('website'),
            $setting('address'),
            $setting('privacyPolicyUrl'),
            $setting('supportEmail'),
            $setting('supportPhone'),
            $setting('paymentAccount'),
        );
    }

    /**
     * The support phone number as a tel: link takes it.
     *
     * @return string
     */
    public function phoneLink(): string
    {
        return (string)preg_replace('/[^0-9+]/', '', $this->supportPhone);
    }

    /**
     * The website without its scheme, as it is written for people.
     */
    public function websiteName(): string
    {
        return (string)preg_replace('#^https?://#', '', rtrim($this->website, '/'));
    }
}
