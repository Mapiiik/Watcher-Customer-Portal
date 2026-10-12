<?php
declare(strict_types=1);

namespace App\Crm;

/**
 * The CRM's answer to a login and password: granted with the customer's token, or why not.
 */
final readonly class SignInAnswer
{
    /**
     * @param \App\Crm\SignInStatus $status
     * @param string $token The customer's token, when granted.
     * @param string $loginId
     * @param string $login
     * @param string $customerNumber
     * @param string $customerName
     * @param int $passwordMinLength The shortest password the customer may set.
     */
    public function __construct(
        public SignInStatus $status,
        public string $token = '',
        public string $loginId = '',
        public string $login = '',
        public string $customerNumber = '',
        public string $customerName = '',
        public int $passwordMinLength = 8,
    ) {
    }

    /**
     * An answer without a token.
     *
     * @param \App\Crm\SignInStatus $status Why.
     * @return self
     */
    public static function refused(SignInStatus $status): self
    {
        return new self($status);
    }

    /**
     * Whether the customer is let in.
     *
     * @return bool
     */
    public function granted(): bool
    {
        return $this->status === SignInStatus::Granted;
    }
}
