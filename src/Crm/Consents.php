<?php
declare(strict_types=1);

namespace App\Crm;

/**
 * Which messages the customer wants. Saving them from the portal also confirms the consent to
 * the processing of personal data, which the CRM labels.
 */
final readonly class Consents
{
    /**
     * @param bool $billing Correspondence about the monthly billing.
     * @param bool $outages Information about outages and faults.
     * @param bool $commercial Commercial communications.
     */
    public function __construct(
        public bool $billing,
        public bool $outages,
        public bool $commercial,
    ) {
    }

    /**
     * As the CRM takes them.
     *
     * @return array<string, int>
     */
    public function toForm(): array
    {
        return [
            'agree_gdpr' => 1,
            'agree_mailing_billing' => (int)$this->billing,
            'agree_mailing_outages' => (int)$this->outages,
            'agree_mailing_commercial' => (int)$this->commercial,
        ];
    }
}
