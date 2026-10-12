<?php
declare(strict_types=1);

namespace App\Portal;

use App\Crm\SignInAnswer;
use Cake\Http\Session;

/**
 * A customer signed in, as the session keeps them: the CRM's token for them and what the pages
 * show about them without asking the CRM.
 */
final readonly class SignedIn
{
    private const SESSION_KEY = 'Portal.customer';

    /**
     * @param string $token The CRM's token for the customer.
     * @param string $loginId
     * @param string $login
     * @param string $customerNumber
     * @param string $customerName
     * @param string $ip Where they signed in from.
     * @param int $passwordMinLength The shortest password they may set.
     */
    public function __construct(
        public string $token,
        public string $loginId,
        public string $login,
        public string $customerNumber,
        public string $customerName,
        public string $ip,
        public int $passwordMinLength,
    ) {
    }

    /**
     * The customer the CRM let in, from the address they signed in from.
     */
    public static function fromAnswer(SignInAnswer $answer, string $ip): self
    {
        return new self(
            $answer->token,
            $answer->loginId,
            $answer->login,
            $answer->customerNumber,
            $answer->customerName,
            $ip,
            $answer->passwordMinLength,
        );
    }

    /**
     * The customer signed in to the session, unless the session has none. An IPv4 address has to
     * stay the one they signed in from; IPv6 addresses change on their own, so they may.
     */
    public static function fromSession(Session $session, string $ip): ?self
    {
        $values = $session->read(self::SESSION_KEY);
        if (!is_array($values)) {
            return null;
        }
        $text = static fn(string $key): string => is_string($values[$key] ?? null) ? $values[$key] : '';
        $customer = new self(
            $text('token'),
            $text('loginId'),
            $text('login'),
            $text('customerNumber'),
            $text('customerName'),
            $text('ip'),
            is_int($values['passwordMinLength'] ?? null) ? $values['passwordMinLength'] : 8,
        );
        if ($customer->token === '') {
            return null;
        }
        if (filter_var($customer->ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false && $customer->ip !== $ip) {
            return null;
        }

        return $customer;
    }

    /**
     * Keeps the customer in the session, under a new session id, so that one known before
     * signing in is worth nothing after it.
     */
    public function store(Session $session): void
    {
        $session->renew();
        $this->keep($session);
    }

    /**
     * The same customer with the token the CRM gave on checking their password again.
     */
    public function withToken(string $token, Session $session): self
    {
        $customer = new self(
            $token,
            $this->loginId,
            $this->login,
            $this->customerNumber,
            $this->customerName,
            $this->ip,
            $this->passwordMinLength,
        );
        $customer->keep($session);

        return $customer;
    }

    /**
     * Signs the customer out of the session.
     *
     * @param \Cake\Http\Session $session The session.
     * @return void
     */
    public static function forget(Session $session): void
    {
        $session->delete(self::SESSION_KEY);
        $session->renew();
    }

    /**
     * @param \Cake\Http\Session $session The session.
     * @return void
     */
    private function keep(Session $session): void
    {
        $session->write(self::SESSION_KEY, [
            'token' => $this->token,
            'loginId' => $this->loginId,
            'login' => $this->login,
            'customerNumber' => $this->customerNumber,
            'customerName' => $this->customerName,
            'ip' => $this->ip,
            'passwordMinLength' => $this->passwordMinLength,
        ]);
    }
}
