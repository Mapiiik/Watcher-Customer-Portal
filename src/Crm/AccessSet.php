<?php
declare(strict_types=1);

namespace App\Crm;

/**
 * How setting a password with a link went: the login to sign in with, the length a password has
 * to have, or neither, when the link no longer works.
 */
final readonly class AccessSet
{
    /**
     * @param string $login The login the password is now of.
     * @param int $minLength The shortest password allowed, when this one was shorter.
     */
    private function __construct(
        public string $login = '',
        public int $minLength = 0,
    ) {
    }

    /**
     * @param string $login The login the password is now of.
     * @return self
     */
    public static function saved(string $login): self
    {
        return new self($login);
    }

    /**
     * @param int $minLength The shortest password allowed.
     * @return self
     */
    public static function tooShort(int $minLength): self
    {
        return new self('', max(1, $minLength));
    }

    /**
     * @return self
     */
    public static function invalid(): self
    {
        return new self();
    }

    /**
     * @return bool
     */
    public function isSaved(): bool
    {
        return $this->login !== '';
    }
}
